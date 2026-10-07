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
       one on a phone. House curve; still when asked. */
    .rd-bar { position: sticky; top: var(--app-head, 3.6rem); z-index: 6; display: flex; align-items: center; gap: .4rem; margin: -1rem -1rem 0; padding: .5rem .75rem;
        background: rgb(15 23 12 / .92); color: #e8efe1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 10px 24px -18px rgb(0 0 0 / .9); }
    @media (min-width: 640px) { .rd-bar { margin: -1rem -1.5rem 0; padding: .55rem 1.5rem; } }
    @media (min-width: 768px) { .rd-bar { margin-top: -2rem; } }
    .rd-btn { display: inline-grid; place-items: center; min-width: 2.3rem; height: 2.3rem; padding: 0 .55rem; border-radius: .7rem; border: 0; cursor: pointer; font-size: .78rem; font-weight: 800;
        color: #e8efe1; background: rgb(255 255 255 / .08); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .rd-btn:hover { background: rgb(255 255 255 / .16); }
    .rd-btn svg { width: 1.05rem; height: 1.05rem; }
    .rd-page { display: inline-flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 800; white-space: nowrap; }
    .rd-page input { width: 3.2rem; height: 2.1rem; padding: 0 .35rem; border-radius: .55rem; border: 1px solid rgb(255 255 255 / .2); background: rgb(255 255 255 / .06); color: #fff; text-align: center; font-weight: 800; }
    .rd-sp { flex: 1 1 auto; }
    .rd-prog { position: absolute; left: 0; bottom: 0; height: 2px; background: #f5c518; width: 0; transition: width .28s cubic-bezier(.22,1,.36,1); }
    .rd-pages { display: grid; justify-items: center; gap: .8rem; padding: 1rem 0 3rem; }
    .rd-p { position: relative; width: 100%; max-width: var(--rd-w, 56rem); background: #fff; border-radius: .4rem; box-shadow: 0 16px 34px -24px rgb(0 0 0 / .8); overflow: hidden; }
    .rd-p canvas { display: block; width: 100%; height: 100%; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rd-p canvas.is-on { opacity: 1; }
    .rd-p::before { content: attr(data-n); position: absolute; inset: 0; display: grid; place-items: center; font-weight: 800; font-size: 1.4rem; color: #cbd5e1; }
    .rd-wait { display: grid; place-items: center; gap: .6rem; padding: 4rem 1rem; text-align: center; color: var(--color-gray-500); font-size: .9rem; }
    .rd-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-gray-200); border-top-color: #4a7c2a; animation: rdSpin .9s linear infinite; }
    @keyframes rdSpin { to { transform: rotate(360deg); } }
    .rd-info { max-width: 56rem; margin: 1rem auto 0; padding: .85rem 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .82rem; line-height: 1.55; color: var(--color-gray-600); }
    .rd-info b { color: var(--color-gray-900); }
    @media (prefers-reduced-motion: reduce) { .rd-p canvas, .rd-btn, .rd-prog { transition: none; } .rd-spin { animation: none; } }
</style>

<div class="rd-bar" id="rdBar">
    <button type="button" class="rd-btn" id="rdPrev" aria-label="Previous page"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
    <span class="rd-page"><input type="number" id="rdNum" min="1" value="1" inputmode="numeric" aria-label="Page"> <span id="rdOf">of …</span></span>
    <button type="button" class="rd-btn" id="rdNext" aria-label="Next page"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
    <span class="rd-sp"></span>
    <button type="button" class="rd-btn" id="rdOut" aria-label="Smaller">−</button>
    <button type="button" class="rd-btn" id="rdFit" aria-label="Fit the width">Fit</button>
    <button type="button" class="rd-btn" id="rdIn" aria-label="Bigger">+</button>
    <a class="rd-btn" href="{{ route('stash.file', $item->id) }}" download="{{ $item->slug }}.pdf" aria-label="Download"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg></a>
    <i class="rd-prog" id="rdProg"></i>
</div>

@if ($item->status !== 'ready')
    <div class="rd-wait"><div class="rd-spin"></div><p>This issue is still being brought into anee.io. Please check back in a little while.</p></div>
@else
    <div class="rd-pages" id="rdPages"><div class="rd-wait" id="rdWait"><div class="rd-spin"></div><p id="rdWaitSays">Opening the magazine…</p></div></div>
@endif
<div class="rd-info">
    <b>{{ $item->title }}</b>@if ($date) · {{ $date }}@endif<br>
    @if ($item->blurb){{ \Illuminate\Support\Str::limit($item->blurb, 500) }}<br>@endif
    From {{ $partner['full'] ?? '' }}@if ($item->sourcePost) · <a href="{{ $item->sourcePost }}" target="_blank" rel="noopener" class="underline">the original page</a>@endif.
</div>
@endsection

@push('scripts')
@if ($item->status === 'ready')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" integrity="sha512-q+4liFwdPC/bNdhUpZx6aXDx/h77yEQtn4I1slHydcbZK34nLaR3cAeYSJshoxIOq3mjEf7xJE8YWIUHMn+oCQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(() => {
    const FILE = @json(route('stash.file', $item->id));
    const $ = (id) => document.getElementById(id);
    const lib = window.pdfjsLib;
    if (!lib) { $('rdWaitSays').textContent = 'The reader could not load. Use the download button instead.'; return; }
    lib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    let doc = null, scale = 1, base = null, pages = [], live = new Map(), current = 1;
    const box = $('rdPages');
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    const width = () => Math.min(box.clientWidth, 56 * 16 * scale);

    const layout = () => {
        // Every page gets its box at once, sized from page one's shape, so the scroll bar is honest.
        box.style.setProperty('--rd-w', (56 * scale) + 'rem');
        pages.forEach((el) => { el.style.aspectRatio = base.width + ' / ' + base.height; });
    };
    const draw = async (n) => {
        if (live.has(n)) return;
        const el = pages[n - 1];
        const token = {};
        live.set(n, token);
        try {
            const page = await doc.getPage(n);
            if (live.get(n) !== token) return;
            const vp1 = page.getViewport({ scale: 1 });
            el.style.aspectRatio = vp1.width + ' / ' + vp1.height;
            const vp = page.getViewport({ scale: (el.clientWidth / vp1.width) * dpr });
            const c = document.createElement('canvas');
            c.width = Math.floor(vp.width); c.height = Math.floor(vp.height);
            await page.render({ canvasContext: c.getContext('2d', { alpha: false }), viewport: vp }).promise;
            if (live.get(n) !== token) return;
            el.querySelector('canvas')?.remove();
            el.appendChild(c);
            requestAnimationFrame(() => c.classList.add('is-on'));
        } catch (_) { live.delete(n); }
    };
    const drop = (n) => { live.delete(n); const c = pages[n - 1]?.querySelector('canvas'); if (c) { c.width = c.height = 0; c.remove(); } };
    let io = null;
    const watch = () => {
        io?.disconnect();
        io = new IntersectionObserver((es) => es.forEach((e) => {
            const n = Number(e.target.dataset.n);
            if (e.isIntersecting) draw(n); else if (live.has(n) && Math.abs(n - current) > 4) drop(n);
        }), { rootMargin: '1200px 0px' });
        pages.forEach((el) => io.observe(el));
    };
    // Which page is under the middle of the screen.
    const track = () => {
        const mid = innerHeight / 2;
        let best = current, bestD = Infinity;
        for (let i = Math.max(0, current - 4); i < Math.min(pages.length, current + 4); i++) {
            const r = pages[i].getBoundingClientRect();
            const d = Math.abs((r.top + r.bottom) / 2 - mid);
            if (d < bestD) { bestD = d; best = i + 1; }
        }
        if (best !== current || document.activeElement !== $('rdNum')) { current = best; $('rdNum').value = current; }
        $('rdProg').style.width = (pages.length ? (current / pages.length) * 100 : 0) + '%';
    };
    let tr = 0;
    addEventListener('scroll', () => { cancelAnimationFrame(tr); tr = requestAnimationFrame(track); }, { passive: true });
    const go = (n) => {
        n = Math.max(1, Math.min(pages.length, n));
        current = n;
        const top = pages[n - 1].getBoundingClientRect().top + scrollY - ($('rdBar').getBoundingClientRect().bottom + 8);
        scrollTo({ top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    };
    $('rdPrev').addEventListener('click', () => go(current - 1));
    $('rdNext').addEventListener('click', () => go(current + 1));
    $('rdNum').addEventListener('change', () => go(Number($('rdNum').value) || 1));
    const rescale = (s) => {
        const keep = current;
        scale = Math.max(.6, Math.min(2.5, s));
        layout();
        [...live.keys()].forEach(drop);
        requestAnimationFrame(() => { go(keep); setTimeout(() => pages.forEach((el, i) => { const r = el.getBoundingClientRect(); if (r.bottom > -1200 && r.top < innerHeight + 1200) draw(i + 1); }), 350); });
    };
    $('rdIn').addEventListener('click', () => rescale(scale + .25));
    $('rdOut').addEventListener('click', () => rescale(scale - .25));
    $('rdFit').addEventListener('click', () => rescale(1));

    (async () => {
        try {
            const task = lib.getDocument({ url: FILE, rangeChunkSize: 1 << 20, disableAutoFetch: true, withCredentials: true });
            task.onProgress = (p) => { const w = $('rdWaitSays'); if (w && p.total) w.textContent = 'Opening the magazine… ' + Math.round(p.loaded / p.total * 100) + '%'; };
            doc = await task.promise;
            const first = await doc.getPage(1);
            base = first.getViewport({ scale: 1 });
            $('rdOf').textContent = 'of ' + doc.numPages;
            $('rdNum').max = doc.numPages;
            box.innerHTML = '';
            for (let i = 1; i <= doc.numPages; i++) {
                const el = document.createElement('div');
                el.className = 'rd-p'; el.dataset.n = i;
                box.appendChild(el);
                pages.push(el);
            }
            layout();
            watch();
            track();
        } catch (err) {
            const w = $('rdWaitSays'); if (w) w.textContent = 'The magazine could not open here. Use the download button to read it.';
        }
    })();
})();
</script>
@endif
@endpush
