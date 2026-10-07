@extends('layouts.app')
@section('title', $item->title . ' · ' . ($partner['name'] ?? 'The Stash'))
@section('page-title', $item->title)
@section('page-subtitle', ($partner['name'] ?? 'The Stash') . ' ' . ($type['label'] ?? '') . ($date ? ' · ' . $date : ''))
@section('back', \App\Support\BackTo::url(route('stash.shelf', [$item->partner, $item->type])))
@section('body-class', 'hide-tabbar')

@section('content')
<style>
    /* ---- The Stash reader (2026-10-07): PDF.js, pages drawn as they come
       into view and let go when far away, so a 90 MB magazine opens at page
       one on a phone.

       Zoom (owner, the same day: "I can't read it because I can't zoom it
       in"): the pages live in their own window that scrolls both ways, so a
       zoomed page is moved with one finger. Two fingers pinch it bigger or
       smaller around the spot between them, a double tap zooms in on the
       spot tapped (and out again), the + and − buttons and Ctrl with the
       wheel or a trackpad pinch work on a computer. While the fingers move,
       the pages are only stretched (smooth); when they lift, the pages are
       laid out at the new size and drawn again, sharp. House curve; still
       when asked. */
    .rd-bar { position: sticky; top: var(--app-head, 3.6rem); z-index: 6; display: flex; align-items: center; gap: .35rem; margin: -1rem -1rem 0; padding: .5rem .75rem;
        background: rgb(15 23 12 / .92); color: #e8efe1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 10px 24px -18px rgb(0 0 0 / .9); }
    @media (min-width: 640px) { .rd-bar { margin: -1rem -1.5rem 0; padding: .55rem 1.5rem; gap: .4rem; } }
    @media (min-width: 768px) { .rd-bar { margin-top: -2rem; } }
    .rd-btn { display: inline-grid; place-items: center; min-width: 2.5rem; height: 2.5rem; padding: 0 .55rem; border-radius: .7rem; border: 0; cursor: pointer; font-size: .8rem; font-weight: 800;
        color: #e8efe1; background: rgb(255 255 255 / .08); transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .rd-btn:hover { background: rgb(255 255 255 / .16); }
    .rd-btn:disabled { opacity: .4; cursor: default; }
    .rd-btn svg { width: 1.1rem; height: 1.1rem; }
    .rd-zoom { min-width: 3.6rem; font-variant-numeric: tabular-nums; }
    .rd-zoom.is-zoomed { color: #1f1500; background: #f5c518; }
    .rd-page { display: inline-flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 800; white-space: nowrap; }
    .rd-page input { width: 3.1rem; height: 2.3rem; padding: 0 .3rem; border-radius: .55rem; border: 1px solid rgb(255 255 255 / .2); background: rgb(255 255 255 / .06); color: #fff; text-align: center; font-weight: 800; }
    .rd-sp { flex: 1 1 auto; }
    @media (max-width: 400px) { .rd-hide-sm { display: none; } .rd-bar { gap: .25rem; padding-inline: .5rem; } .rd-btn { min-width: 2.35rem; } }
    .rd-prog { position: absolute; left: 0; bottom: 0; height: 2px; background: #f5c518; width: 0; transition: width .28s cubic-bezier(.22,1,.36,1); }

    /* The window the pages live in: it scrolls both ways, and only the
       pinch is ours (one finger is the browser's own smooth scrolling). */
    .rd-view { position: relative; margin: 0 -1rem; height: var(--rd-h, 75vh); overflow: auto; overscroll-behavior: contain; touch-action: pan-x pan-y;
        background: var(--color-gray-100); -webkit-overflow-scrolling: touch; }
    @media (min-width: 640px) { .rd-view { margin: 0 -1.5rem; } }
    .rd-pages { width: max-content; min-width: 100%; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; gap: .8rem; padding: 1rem .75rem 1.5rem;
        transform-origin: 0 0; }
    .rd-pages.is-gliding { transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rd-p { position: relative; flex: none; width: var(--rd-pw, 100%); background: #fff; border-radius: .4rem; box-shadow: 0 16px 34px -24px rgb(0 0 0 / .8); overflow: hidden; }
    .rd-p canvas { position: absolute; inset: 0; display: block; width: 100%; height: 100%; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rd-p canvas.is-on { opacity: 1; }
    .rd-p::before { content: attr(data-n); position: absolute; inset: 0; display: grid; place-items: center; font-weight: 800; font-size: 1.4rem; color: #cbd5e1; }
    .rd-wait { display: grid; place-items: center; gap: .6rem; padding: 4rem 1rem; text-align: center; color: var(--color-gray-500); font-size: .9rem; }
    .rd-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-gray-200); border-top-color: #4a7c2a; animation: rdSpin .9s linear infinite; }
    @keyframes rdSpin { to { transform: rotate(360deg); } }
    .rd-info { position: sticky; left: 0; max-width: min(56rem, 100vw); margin: 0 auto 1.5rem; padding: .85rem 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        font-size: .82rem; line-height: 1.55; color: var(--color-gray-600); }
    .rd-info b { color: var(--color-gray-900); }
    /* "Pinch to zoom": said once, on a touch screen, then never again. */
    .rd-tip { position: absolute; z-index: 7; left: 50%; bottom: 1.25rem; translate: -50% .5rem; padding: .6rem .95rem; border-radius: 999px; background: rgb(15 23 12 / .9); color: #fff;
        font-size: .82rem; font-weight: 700; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .28s cubic-bezier(.22,1,.36,1), translate .28s cubic-bezier(.22,1,.36,1); }
    .rd-tip.is-on { opacity: 1; translate: -50% 0; }
    .rd-wrap { position: relative; }
    .rd-hide-sm-inv { display: none; }
    @media (max-width: 400px) { .rd-hide-sm-inv { display: inline; } }
    @media (prefers-reduced-motion: reduce) { .rd-p canvas, .rd-btn, .rd-prog, .rd-tip { transition: none; } .rd-pages.is-gliding { transition: none; } .rd-spin { animation: none; } }
</style>

<div class="rd-bar" id="rdBar">
    <button type="button" class="rd-btn" id="rdPrev" aria-label="Previous page"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
    <span class="rd-page"><input type="number" id="rdNum" min="1" value="1" inputmode="numeric" aria-label="Page"> <span id="rdOf">of …</span></span>
    <button type="button" class="rd-btn" id="rdNext" aria-label="Next page"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
    <span class="rd-sp"></span>
    <button type="button" class="rd-btn" id="rdOut" aria-label="Zoom out"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 12h12"/></svg></button>
    <button type="button" class="rd-btn rd-zoom" id="rdFit" aria-label="Fit the page to the screen">Fit</button>
    <button type="button" class="rd-btn" id="rdIn" aria-label="Zoom in"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 6v12M6 12h12"/></svg></button>
    <a class="rd-btn rd-hide-sm" href="{{ route('stash.file', $item->id) }}" download="{{ $item->slug }}.pdf" aria-label="Download"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg></a>
    <i class="rd-prog" id="rdProg"></i>
</div>

<div class="rd-wrap">
    <div class="rd-view" id="rdView">
        @if ($item->status !== 'ready')
            <div class="rd-wait"><div class="rd-spin"></div><p>This issue is still being brought into anee.io. Please check back in a little while.</p></div>
        @else
            <div class="rd-pages" id="rdPages"><div class="rd-wait" id="rdWait"><div class="rd-spin"></div><p id="rdWaitSays">Opening the magazine…</p></div></div>
        @endif
        <div class="rd-info">
            <b>{{ $item->title }}</b>@if ($date) · {{ $date }}@endif<br>
            @if ($item->blurb){{ \Illuminate\Support\Str::limit($item->blurb, 500) }}<br>@endif
            From {{ $partner['full'] ?? '' }}@if ($item->sourcePost) · <a href="{{ $item->sourcePost }}" target="_blank" rel="noopener" class="underline">the original page</a>@endif.
            <span class="rd-hide-sm-inv"> <a href="{{ route('stash.file', $item->id) }}" download="{{ $item->slug }}.pdf" class="underline">Download the PDF</a>.</span>
        </div>
    </div>
    <div class="rd-tip" id="rdTip" aria-hidden="true">Pinch or double tap to zoom</div>
</div>
@endsection

@push('scripts')
@if ($item->status === 'ready')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" integrity="sha512-q+4liFwdPC/bNdhUpZx6aXDx/h77yEQtn4I1slHydcbZK34nLaR3cAeYSJshoxIOq3mjEf7xJE8YWIUHMn+oCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endif
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const view = $('rdView');
    if (!view) return;

    /* The window fills the screen under the bar, and follows the phone's own
       bars as they come and go. */
    const fit = () => {
        const top = Math.max($('rdBar').getBoundingClientRect().bottom, 0);
        view.style.setProperty('--rd-h', Math.max(320, window.innerHeight - top) + 'px');
    };
    fit();
    let fr = 0;
    addEventListener('resize', () => { cancelAnimationFrame(fr); fr = requestAnimationFrame(() => { fit(); relayout(true); }); });

    const FILE = @json(route('stash.file', $item->id));
    const box = $('rdPages');
    const lib = window.pdfjsLib;
    let relayout = () => {};
    if (!box) return;
    if (!lib) { $('rdWaitSays').textContent = 'The reader could not load. Use the download button instead.'; return; }
    lib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    const MIN = .6, MAX = 4, MAXPX = 8e6;   // zoom range; the most pixels one page's drawing may hold
    let doc = null, zoom = 1, ratio = 1.414, pages = [], live = new Map(), current = 1;

    // The width a page has at zoom 1: the window's width, up to a magazine page on a desk.
    const fitWidth = () => Math.min(view.clientWidth - 24, 56 * 16);
    const label = () => {
        const b = $('rdFit');
        const pct = Math.round(zoom * 100);
        b.textContent = Math.abs(zoom - 1) < .02 ? 'Fit' : pct + '%';
        b.classList.toggle('is-zoomed', Math.abs(zoom - 1) >= .02);
        $('rdIn').disabled = zoom >= MAX - .01;
        $('rdOut').disabled = zoom <= MIN + .01;
    };
    const size = () => {
        box.style.setProperty('--rd-pw', Math.round(fitWidth() * zoom) + 'px');
        pages.forEach((el) => { if (!el.dataset.ar) el.style.aspectRatio = '1 / ' + ratio; });
    };

    // Draw one page at its present size (a drawing already there stays until the new one is ready).
    const draw = async (n) => {
        if (live.has(n)) return;
        const el = pages[n - 1];
        const token = {};
        live.set(n, token);
        try {
            const page = await doc.getPage(n);
            if (live.get(n) !== token) return;
            const vp1 = page.getViewport({ scale: 1 });
            if (!el.dataset.ar) { el.dataset.ar = 1; el.style.aspectRatio = vp1.width + ' / ' + vp1.height; }
            let s = (el.clientWidth / vp1.width) * dpr;
            if (vp1.width * vp1.height * s * s > MAXPX) s = Math.sqrt(MAXPX / (vp1.width * vp1.height));
            const vp = page.getViewport({ scale: s });
            const c = document.createElement('canvas');
            c.width = Math.floor(vp.width); c.height = Math.floor(vp.height);
            await page.render({ canvasContext: c.getContext('2d', { alpha: false }), viewport: vp }).promise;
            if (live.get(n) !== token) { c.width = c.height = 0; return; }
            const old = el.querySelector('canvas');
            el.appendChild(c);
            requestAnimationFrame(() => {
                c.classList.add('is-on');
                if (old) setTimeout(() => { old.width = old.height = 0; old.remove(); }, 300);
            });
        } catch (_) { live.delete(n); }
    };
    const drop = (n) => { live.delete(n); const el = pages[n - 1]; el?.querySelectorAll('canvas').forEach((c) => { c.width = c.height = 0; c.remove(); }); };
    // Which pages are near the window, drawn; far ones let go (fewer kept while zoomed in).
    const near = () => {
        const v = view.getBoundingClientRect();
        const reach = zoom > 1.5 ? 600 : 1400;
        pages.forEach((el, i) => {
            const r = el.getBoundingClientRect();
            const on = r.bottom > v.top - reach && r.top < v.bottom + reach;
            if (on) draw(i + 1); else if (live.has(i + 1)) drop(i + 1);
        });
    };
    // Which page is under the middle of the window.
    const track = () => {
        const v = view.getBoundingClientRect(), mid = v.top + v.height / 2;
        let best = current, bestD = Infinity;
        for (let i = Math.max(0, current - 5); i < Math.min(pages.length, current + 5); i++) {
            const r = pages[i].getBoundingClientRect();
            const d = r.top <= mid && r.bottom >= mid ? 0 : Math.min(Math.abs(r.top - mid), Math.abs(r.bottom - mid));
            if (d < bestD) { bestD = d; best = i + 1; }
        }
        current = best;
        if (document.activeElement !== $('rdNum')) $('rdNum').value = current;
        $('rdProg').style.width = (pages.length ? (current / pages.length) * 100 : 0) + '%';
    };
    let tick = 0, settle = 0;
    view.addEventListener('scroll', () => {
        cancelAnimationFrame(tick); tick = requestAnimationFrame(track);
        clearTimeout(settle); settle = setTimeout(near, 120);
    }, { passive: true });

    const go = (n) => {
        n = Math.max(1, Math.min(pages.length, n));
        current = n;
        view.scrollTo({ top: pages[n - 1].offsetTop - 12, behavior: reduce() ? 'auto' : 'smooth' });
    };
    $('rdPrev').addEventListener('click', () => go(current - 1));
    $('rdNext').addEventListener('click', () => go(current + 1));
    $('rdNum').addEventListener('change', () => go(Number($('rdNum').value) || 1));

    /* ---- zoom ---- */
    // The spot under a point of the screen: the page there and where on it.
    const anchorAt = (cx, cy) => {
        const v = view.getBoundingClientRect();
        let el = pages[current - 1] || pages[0], bestD = Infinity;
        for (let i = Math.max(0, current - 4); i < Math.min(pages.length, current + 4); i++) {
            const r = pages[i].getBoundingClientRect();
            const d = cy < r.top ? r.top - cy : cy > r.bottom ? cy - r.bottom : 0;
            if (d < bestD) { bestD = d; el = pages[i]; }
        }
        const r = el.getBoundingClientRect();
        return { el, fx: (cx - r.left) / r.width, fy: (cy - r.top) / r.height, vx: cx - v.left, vy: cy - v.top };
    };
    // Put that spot back under the same point after the pages change size.
    const restore = (a) => {
        view.scrollLeft = a.el.offsetLeft + a.fx * a.el.offsetWidth - a.vx;
        view.scrollTop = a.el.offsetTop + a.fy * a.el.offsetHeight - a.vy;
    };
    const clamp = (z) => Math.max(MIN, Math.min(MAX, z));
    // Lay the pages out at a new zoom, keeping the spot `a` where it is, then draw them again, sharp.
    const commit = (z, a) => {
        box.classList.remove('is-gliding');
        box.style.transform = '';
        zoom = clamp(z);
        size();
        if (a) restore(a);
        label();
        [...live.keys()].forEach((n) => live.delete(n));   // the old drawings stay until the new ones are ready
        near();
        track();
    };
    relayout = (keep) => { if (!pages.length) return; const v = view.getBoundingClientRect(); commit(zoom, keep ? anchorAt(v.left + v.width / 2, v.top + v.height / 2) : null); };
    // The stretch while zooming: the spot under (ox, oy) grows in place, moved by (dx, dy).
    const stretch = (k, origin, dx = 0, dy = 0) => {
        box.style.transformOrigin = origin.x + 'px ' + origin.y + 'px';
        box.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(' + k + ')';
    };
    // A spot of the screen in the pages' own coordinates (where the stretch grows from).
    const local = (cx, cy) => { const b = box.getBoundingClientRect(); return { x: cx - b.left, y: cy - b.top }; };
    // Zoom to z around a point of the screen, gliding there.
    const zoomTo = (z, cx, cy) => {
        z = clamp(z);
        if (Math.abs(z - zoom) < .01) return;
        const a = anchorAt(cx, cy);
        if (reduce()) { commit(z, a); return; }
        box.classList.add('is-gliding');
        stretch(z / zoom, local(cx, cy));
        setTimeout(() => commit(z, a), 290);
    };
    const middle = () => { const v = view.getBoundingClientRect(); return [v.left + v.width / 2, v.top + v.height / 2]; };
    $('rdIn').addEventListener('click', () => zoomTo(zoom * 1.35, ...middle()));
    $('rdOut').addEventListener('click', () => zoomTo(zoom / 1.35, ...middle()));
    $('rdFit').addEventListener('click', () => { const [x, y] = middle(); zoomTo(1, view.getBoundingClientRect().left, y); });

    // Two fingers: pinch around the spot between them, and move with them.
    let pinch = null;
    const two = (t) => ({ d: Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY), x: (t[0].clientX + t[1].clientX) / 2, y: (t[0].clientY + t[1].clientY) / 2 });
    view.addEventListener('touchstart', (e) => {
        if (e.touches.length !== 2 || !pages.length) return;
        const p = two(e.touches);
        pinch = { d0: p.d, x0: p.x, y0: p.y, x: p.x, y: p.y, k: 1, a: anchorAt(p.x, p.y), o: local(p.x, p.y) };
        box.classList.remove('is-gliding');
        tip(false);
    }, { passive: true });
    view.addEventListener('touchmove', (e) => {
        if (!pinch || e.touches.length !== 2) return;
        e.preventDefault();
        const p = two(e.touches);
        pinch.k = clamp(zoom * p.d / pinch.d0) / zoom;
        pinch.x = p.x; pinch.y = p.y;
        stretch(pinch.k, pinch.o, p.x - pinch.x0, p.y - pinch.y0);
    }, { passive: false });
    const endPinch = () => {
        if (!pinch) return;
        const p = pinch; pinch = null;
        const v = view.getBoundingClientRect();
        commit(zoom * p.k, { ...p.a, vx: p.x - v.left, vy: p.y - v.top });
        tapAt = 0;   // a pinch is not a tap
    };
    view.addEventListener('touchend', (e) => { if (pinch && e.touches.length < 2) endPinch(); }, { passive: true });
    view.addEventListener('touchcancel', endPinch, { passive: true });

    // A double tap: in on the spot tapped, or back to the whole page.
    let tapAt = 0, tapX = 0, tapY = 0, moved = false, sx = 0, sy = 0;
    view.addEventListener('touchstart', (e) => { if (e.touches.length === 1) { moved = false; sx = e.touches[0].clientX; sy = e.touches[0].clientY; } }, { passive: true });
    view.addEventListener('touchmove', (e) => { if (e.touches.length === 1 && Math.hypot(e.touches[0].clientX - sx, e.touches[0].clientY - sy) > 10) moved = true; }, { passive: true });
    view.addEventListener('touchend', (e) => {
        if (e.touches.length || moved || pinch || !pages.length) return;
        const t = e.changedTouches[0], now = Date.now();
        if (now - tapAt < 320 && Math.hypot(t.clientX - tapX, t.clientY - tapY) < 30) {
            tapAt = 0;
            e.preventDefault();
            zoomTo(zoom < 1.6 ? 2.5 : 1, t.clientX, t.clientY);
        } else { tapAt = now; tapX = t.clientX; tapY = t.clientY; }
    });
    // On a computer: Ctrl with the wheel, or a trackpad pinch (which the browser sends as one).
    let wheelK = 1, wheelEnd = 0, wheelA = null, wheelO = null;
    view.addEventListener('wheel', (e) => {
        if (!e.ctrlKey || !pages.length) return;
        e.preventDefault();
        if (!wheelA) { wheelA = anchorAt(e.clientX, e.clientY); wheelO = local(e.clientX, e.clientY); box.classList.remove('is-gliding'); }
        wheelK = clamp(zoom * wheelK * Math.exp(-e.deltaY * .01)) / zoom;
        stretch(wheelK, wheelO);
        clearTimeout(wheelEnd);
        wheelEnd = setTimeout(() => { commit(zoom * wheelK, wheelA); wheelK = 1; wheelA = null; }, 180);
    }, { passive: false });

    // Said once on a touch screen: how to zoom.
    const tip = (on) => $('rdTip').classList.toggle('is-on', on);
    const tipOnce = () => {
        if (!matchMedia('(pointer: coarse)').matches) return;
        try { if (localStorage.getItem('rdZoomTip')) return; localStorage.setItem('rdZoomTip', '1'); } catch (_) { return; }
        tip(true); setTimeout(() => tip(false), 3200);
    };

    (async () => {
        try {
            const task = lib.getDocument({ url: FILE, rangeChunkSize: 1 << 20, disableAutoFetch: true, withCredentials: true });
            task.onProgress = (p) => { const w = $('rdWaitSays'); if (w && p.total) w.textContent = 'Opening the magazine… ' + Math.round(p.loaded / p.total * 100) + '%'; };
            doc = await task.promise;
            const first = await doc.getPage(1);
            const base = first.getViewport({ scale: 1 });
            ratio = base.height / base.width;
            $('rdOf').textContent = 'of ' + doc.numPages;
            $('rdNum').max = doc.numPages;
            box.innerHTML = '';
            for (let i = 1; i <= doc.numPages; i++) {
                const el = document.createElement('div');
                el.className = 'rd-p'; el.dataset.n = i;
                box.appendChild(el);
                pages.push(el);
            }
            fit();
            size();
            label();
            near();
            track();
            tipOnce();
        } catch (err) {
            const w = $('rdWaitSays'); if (w) w.textContent = 'The magazine could not open here. Use the download button to read it.';
        }
    })();
})();
</script>
@endpush
