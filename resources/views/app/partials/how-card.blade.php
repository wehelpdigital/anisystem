{{-- How anee.io works, on the dashboard (2026-10-01; rewritten 2026-10-28, the owner: "i don't like the animation on
     that card, remove it or make the whole card look better"; "when you click or open it, the page that appears
     reloads twice, please fix. if it needs time to load you can show a loader ... rewrite it entirely").

     A card with Anee in a turning halo, the sun, the rain and the crop circling her (2026-10-28, again the owner). It
     opens the tour (app.how-tour) full screen in partials/story-modal's shell: fetched the first time, Anee's face
     while it loads, drawn once. #how-it-works in the address opens it on arrival. --}}
@php $hwcStages = \App\Support\HowItWorks::stages(); @endphp
@include('partials.story-modal')

@push('head')
<style>
    .hwc { position: relative; overflow: hidden; isolation: isolate; border-radius: 1.25rem; color: #eef4e6;
        background: radial-gradient(30rem 16rem at 100% 0%, rgb(168 204 126 / .28), transparent 70%), linear-gradient(135deg, #16290e, #0d1609 72%);
        box-shadow: 0 20px 44px -30px rgb(13 22 9 / .9); }
    .hwc::before { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 18px 18px;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 55%); mask-image: linear-gradient(90deg, transparent, #000 55%); }
    /* Beside a taller card the content keeps together in the middle, not spread top and bottom. */
    .hwc { display: flex; }
    .hwc-open { flex: 1; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .9rem 1rem; align-items: center; align-content: center; width: 100%; padding: 1.1rem 1.1rem 1.15rem; text-align: left; cursor: pointer; }
    @media (min-width: 768px) { .hwc-open { padding: 1.3rem 1.5rem; gap: 1rem 1.6rem; } }
    .hwc-kick { display: inline-flex; align-items: center; gap: .45rem; font-size: .66rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #a8cc7e; }
    .hwc-h { display: block; margin-top: .35rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .hwc-p { display: block; margin-top: .3rem; font-size: .86rem; line-height: 1.5; color: #b9caa8; }
    /* The steps as words under the line, a dot between each. */
    .hwc-steps { display: flex; flex-wrap: wrap; gap: .25rem .4rem; margin-top: .7rem; font-size: .7rem; font-weight: 800; color: #8ea47a; }
    .hwc-steps span { display: inline-flex; align-items: center; gap: .4rem; }
    .hwc-steps span + span::before { content: ''; width: 3px; height: 3px; border-radius: 999px; background: #5a7346; }
    .hwc-steps span:last-child { color: #f5c518; }
    @media (max-width: 520px) { .hwc-steps { display: none; } }
    .hwc-go { grid-column: 1; justify-self: start; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .7rem 1.15rem; border-radius: .95rem; font-weight: 800; font-size: .92rem;
        color: #3b2f00; background: #f5c518; box-shadow: 0 10px 22px -12px rgb(245 197 24 / .8); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
    .hwc-go svg { width: 1.05rem; height: 1.05rem; }
    .hwc-open:hover .hwc-go { transform: translateY(-2px); box-shadow: 0 16px 28px -14px rgb(245 197 24 / .9); }
    .hwc-open:focus-visible { outline: 2px solid #f5c518; outline-offset: -4px; border-radius: 1.25rem; }
    /* Anee in her season (2026-10-28, the owner: "the icons in the circles ... is not centered in the circle ... create
       a new better animation, no need to indicate the steps really just make it look great animated"): her face in a
       turning halo of gold and green, the sun, the rain and the crop circling her, ripples and a few sparks. */
    .hwc-art { --s: 6.6rem; position: relative; grid-row: span 2; width: var(--s); height: var(--s); }
    @media (min-width: 768px) { .hwc-art { --s: 8.6rem; } }
    .hwc-art > * { position: absolute; left: 50%; top: 50%; border-radius: 999px; }
    .hwc-art .glow { width: 120%; height: 120%; margin: -60% 0 0 -60%; background: radial-gradient(circle, rgb(245 197 24 / .28), rgb(168 204 126 / .1) 45%, transparent 68%);
        animation: hwcGlow 4.2s ease-in-out infinite; }
    .hwc-art .track { width: 92%; height: 92%; margin: -46% 0 0 -46%; border: 1px dashed rgb(168 204 126 / .32); }
    .hwc-art .ripple { width: 52%; height: 52%; margin: -26% 0 0 -26%; border: 2px solid rgb(245 197 24 / .5); animation: hwcRipple 3s cubic-bezier(.22,1,.36,1) infinite; }
    .hwc-art .ripple.r2 { animation-delay: 1.5s; }
    .hwc-art .halo { width: 56%; height: 56%; margin: -28% 0 0 -28%; background: conic-gradient(from 0deg, #f5c518, #a8cc7e 35%, rgb(168 204 126 / 0) 60%, #f5c518);
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3.5px), #000 calc(100% - 3px)); mask: radial-gradient(farthest-side, transparent calc(100% - 3.5px), #000 calc(100% - 3px));
        animation: hwcSpin 3.6s linear infinite; }
    .hwc-art .face { width: 46%; height: 46%; margin: -23% 0 0 -23%; object-fit: cover; box-shadow: 0 0 0 2px #16290e, 0 8px 22px -6px rgb(0 0 0 / .6); animation: hwcBob 3.4s ease-in-out infinite; }
    /* Three companions on one circle, each a third of the way round, the circle turning; each stays upright. */
    .hwc-art .orbit { width: 92%; height: 92%; margin: -46% 0 0 -46%; animation: hwcSpin 16s linear infinite; }
    .hwc-art .orbit i { position: absolute; left: 50%; top: 0; width: 1.65rem; height: 1.65rem; margin: -.825rem 0 0 -.825rem; display: grid; place-items: center; border-radius: 999px;
        font-style: normal; font-size: .82rem; line-height: 1; background: #1a2c12; box-shadow: 0 0 0 1.5px rgb(168 204 126 / .55), 0 6px 14px -6px rgb(0 0 0 / .7);
        animation: hwcSpin 16s linear infinite reverse; }
    @media (min-width: 768px) { .hwc-art .orbit i { width: 2rem; height: 2rem; margin: -1rem 0 0 -1rem; font-size: 1rem; } }
    .hwc-art .orbit.o2 { transform: rotate(120deg); animation-name: hwcSpin120; }
    .hwc-art .orbit.o3 { transform: rotate(240deg); animation-name: hwcSpin240; }
    .hwc-art .orbit.o2 i { animation-name: hwcBack120; } .hwc-art .orbit.o3 i { animation-name: hwcBack240; }
    .hwc-art .orbit.o1 i { box-shadow: 0 0 0 1.5px #f5c518, 0 0 14px rgb(245 197 24 / .45); }
    .hwc-art .spark { width: 5px; height: 5px; margin: 0; background: #fff3c4; box-shadow: 0 0 8px #f5c518; animation: hwcTwinkle 2.4s ease-in-out infinite; }
    .hwc-art .spark.s1 { left: 14%; top: 22%; } .hwc-art .spark.s2 { left: 86%; top: 30%; animation-delay: .8s; } .hwc-art .spark.s3 { left: 74%; top: 88%; animation-delay: 1.6s; width: 4px; height: 4px; }
    .hwc-open:hover .hwc-art .halo { animation-duration: 1.6s; }
    @keyframes hwcSpin { to { transform: rotate(360deg); } }
    @keyframes hwcSpin120 { from { transform: rotate(120deg); } to { transform: rotate(480deg); } }
    @keyframes hwcSpin240 { from { transform: rotate(240deg); } to { transform: rotate(600deg); } }
    @keyframes hwcBack120 { from { transform: rotate(-120deg); } to { transform: rotate(-480deg); } }
    @keyframes hwcBack240 { from { transform: rotate(-240deg); } to { transform: rotate(-600deg); } }
    @keyframes hwcRipple { from { transform: scale(1); opacity: .9; } to { transform: scale(1.75); opacity: 0; } }
    @keyframes hwcGlow { 50% { transform: scale(1.08); opacity: .7; } }
    @keyframes hwcBob { 50% { transform: translateY(-3px); } }
    @keyframes hwcTwinkle { 0%, 100% { transform: scale(.4); opacity: 0; } 50% { transform: scale(1); opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .hwc-art *, .hwc-go { animation: none !important; transition: none; } .hwc-art .ripple, .hwc-art .spark { display: none; } }
    html.sm-still .hwc-art * { animation: none !important; } html.sm-still .hwc-art .ripple, html.sm-still .hwc-art .spark { display: none; }
</style>
@endpush

<section class="hwc" aria-labelledby="hwcH">
    <button type="button" class="hwc-open" id="hwcOpen" aria-haspopup="dialog" aria-controls="hwTour">
        <span>
            <span class="hwc-kick">How anee.io works</span>
            <span class="hwc-h" id="hwcH">See how Anee helps at every step</span>
            <span class="hwc-p">{{ \App\Support\HowItWorks::stepWord(true) }} steps, from the first plan to the last sack.</span>
            <span class="hwc-steps" aria-hidden="true">@foreach ($hwcStages as $s)<span>{{ $s['word'] }}</span>@endforeach</span>
        </span>
        <span class="hwc-art" aria-hidden="true">
            <span class="glow"></span>
            <span class="track"></span>
            <span class="ripple"></span><span class="ripple r2"></span>
            <span class="halo"></span>
            <img class="face" src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">
            <span class="orbit o1"><i>☀️</i></span>
            <span class="orbit o2"><i>💧</i></span>
            <span class="orbit o3"><i>🌾</i></span>
            <span class="spark s1"></span><span class="spark s2"></span><span class="spark s3"></span>
        </span>
        <span class="hwc-go">
            <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z"/></svg>
            Open the tour
        </span>
    </button>
</section>

@push('sheets')
<div class="stm" id="hwTour" role="dialog" aria-modal="true" aria-labelledby="hwTourT" data-url="{{ route('how.tour') }}" hidden>
    <div class="stm-bg" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="stm-bar">
        <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">
        <div class="min-w-0"><b id="hwTourT">How anee.io works</b><small>{{ \App\Support\HowItWorks::stepWord(true) }} steps, with Anee at every one</small></div>
        <button type="button" class="stm-x" aria-label="Close the tour"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        <span class="stm-prog" aria-hidden="true"><i></i></span>
    </div>
    <div class="stm-body" tabindex="-1">
        <div class="stm-load" role="status"><span class="stm-load-face"><img src="{{ \App\Support\AneeEmoji::url('wave') }}" alt=""></span><b data-wait="Anee is getting the tour ready">Anee is getting the tour ready</b></div>
        <div class="stm-host"></div>
    </div>
    <nav class="stm-dots" aria-label="Parts of the tour"></nav>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const card = document.getElementById('hwcOpen'), box = document.getElementById('hwTour');
    if (!card || !box) return;
    // The tour's own moves, once it is drawn: the steps, a tool's sheet, the door to Anee Auto.
    const init = (b) => {
        const data = JSON.parse(b.querySelector('[data-hwt-data]')?.textContent || '{}');
        const sheet = b.querySelector('[data-hwt-sheet]');
        // The sheet covers the whole story, not the part scrolled to.
        if (sheet) b.appendChild(sheet);
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const tabs = [...b.querySelectorAll('.hwt-r')], panels = [...b.querySelectorAll('.hwt-panel')];
        const rail = b.querySelector('.hwt-rail'), railBox = rail?.closest('.hwt-railbox');
        if (rail && railBox) {
            const edge = () => railBox.classList.toggle('is-end', rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 8);
            rail.addEventListener('scroll', edge, { passive: true });
            window.addEventListener('resize', edge);
            edge();
        }
        const show = (k) => {
            if (!panels[k]) return;
            tabs.forEach((t, i) => t.setAttribute('aria-selected', i === k ? 'true' : 'false'));
            panels.forEach((p, i) => { p.hidden = i !== k; p.classList.remove('is-in'); });
            void panels[k].offsetWidth;
            panels[k].classList.add('is-in');
            tabs[k].scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        };
        b.addEventListener('click', (e) => {
            const st = e.target.closest('[data-hwt-step]');
            if (st && !st.disabled) {
                const k = Number(st.dataset.hwtStep);
                show(k);
                return;
            }
            const tool = e.target.closest('[data-hwt-tool]');
            if (tool && sheet) { openSheet(tool.dataset.hwtTool, tool); return; }
            // A plan opens to its tools, and closes again (several may stand open).
            const plan = e.target.closest('[data-hwt-plan]');
            if (plan) {
                const w = plan.closest('.hwt-plw'), on = !w.classList.contains('is-open');
                w.classList.toggle('is-open', on);
                plan.setAttribute('aria-expanded', String(on));
                return;
            }
            if (e.target.closest('[data-hwt-close]')) { closeSheet(); return; }
            const auto = e.target.closest('[data-hwt-auto]');
            const autoCard = document.getElementById('aneeAutoCard');
            if (auto && autoCard) {
                e.preventDefault();
                b.addEventListener('stm:closed', () => {
                    autoCard.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    setTimeout(() => autoCard.querySelector('[data-ac-more]')?.click(), 500);
                }, { once: true });
                window.aneeStory.close(b);
            }
        });
        tabs.forEach((t, i) => t.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' && e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
            e.preventDefault();
            const k = (i + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : tabs.length - 1)) % tabs.length;
            show(k); tabs[k].focus();
        }));
        let back = null, hideT = null;
        /* A tool's sheet (2026-10-28, the owner: "opening tools info or clicking the tools in this section ... is glitchy").
           Three things made it jump: a sheet closing hid the next one opened within its fade (the timer is cleared now),
           the phone picture loaded after the sheet rose and pushed it taller (its shape is kept now), and the story's
           drifting light repainted under the rising sheet (it rests while a sheet is open). */
        const openSheet = (key, from) => {
            const t = data[key];
            if (!t) return;
            clearTimeout(hideT);
            back = from;
            sheet.querySelector('[data-hwt-body]').innerHTML =
                (t.shot ? `<div class="hwt-shot"><div><img src="${esc(t.shot)}" alt="${esc(t.name)} in the app" width="540" height="1169" decoding="async"></div></div>` : '')
                + `<div class="hwt-sh-h"><img src="${esc(t.icon)}" alt=""><div><b>${esc(t.name)}</b><small>${t.anee ? '✨ Anee does the work · ' : ''}${esc(t.short)}</small></div></div>`
                + `<p class="hwt-sh-p">${esc(t.what)}</p>`
                + (t.gets && t.gets.length ? `<div class="hwt-sh-g">${t.gets.map((g) => `<div>${esc(g)}</div>`).join('')}</div>` : '')
                + `<p class="hwt-sh-plan">${esc(t.plan)}${t.more ? '. ' + esc(t.more) : ''}</p>`
                + (t.href ? `<a class="hwt-sh-go" href="${esc(t.href)}">${esc(t.go || 'Open it now')} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></a>` : '');
            sheet.querySelector('.hwt-sheet-in').scrollTop = 0;
            b.classList.add('is-sheet');
            sheet.hidden = false;
            requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('is-on')));
            setTimeout(() => sheet.querySelector('.hwt-sheet-x').focus({ preventScroll: true }), 60);
        };
        const closeSheet = () => {
            if (sheet.hidden || !sheet.classList.contains('is-on')) return;
            sheet.classList.remove('is-on');
            clearTimeout(hideT);
            hideT = setTimeout(() => { sheet.hidden = true; b.classList.remove('is-sheet'); back?.focus({ preventScroll: true }); }, 420);
        };
        // Escape closes the sheet first, then the tour.
        b.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sheet && !sheet.hidden) { e.stopPropagation(); closeSheet(); } });
        b.addEventListener('stm:closed', () => { clearTimeout(hideT); b.classList.remove('is-sheet'); if (sheet) { sheet.classList.remove('is-on'); sheet.hidden = true; } });
    };
    const open = () => window.aneeStory.open(box, card, init);
    card.addEventListener('click', open);
    if (location.hash === '#how-it-works') setTimeout(open, 400);
    window.addEventListener('hashchange', () => { if (location.hash === '#how-it-works') open(); });
    box.addEventListener('stm:closed', () => { if (location.hash === '#how-it-works') history.replaceState(null, '', location.pathname + location.search); });
})();
</script>
@endpush
