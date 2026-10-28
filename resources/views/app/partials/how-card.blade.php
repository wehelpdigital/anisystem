{{-- How anee.io works, on the dashboard (2026-10-01; rewritten 2026-10-28, the owner: "i don't like the animation on
     that card, remove it or make the whole card look better"; "when you click or open it, the page that appears
     reloads twice, please fix. if it needs time to load you can show a loader ... rewrite it entirely").

     A still card: the season's steps on a ring around Anee, the ring turning a little only under the pointer. It
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
    /* The ring: still, turning an eighth under the pointer. */
    .hwc-ring { position: relative; grid-row: span 2; width: 6.2rem; height: 6.2rem; }
    @media (min-width: 768px) { .hwc-ring { width: 8.4rem; height: 8.4rem; grid-row: span 2; } }
    .hwc-ring svg { width: 100%; height: 100%; transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .hwc-open:hover .hwc-ring svg { transform: rotate(45deg); }
    .hwc-ring svg g g { transition: transform .7s cubic-bezier(.22,1,.36,1); transform-box: fill-box; transform-origin: center; }
    .hwc-open:hover .hwc-ring svg g g { transform: rotate(-45deg); }
    .hwc-ring img { position: absolute; left: 50%; top: 50%; width: 42%; height: 42%; transform: translate(-50%, -50%); border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 3px #f5c518, 0 0 24px rgb(245 197 24 / .35); }
    @media (prefers-reduced-motion: reduce) { .hwc-ring svg, .hwc-ring svg g g, .hwc-go { transition: none; } .hwc-open:hover .hwc-ring svg, .hwc-open:hover .hwc-ring svg g g { transform: none; } }
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
        <span class="hwc-ring" aria-hidden="true">
            <svg viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="46" fill="none" stroke="rgb(168 204 126 / .3)" stroke-width="1.5" stroke-dasharray="2 4"/>
                <g>
                    @foreach ($hwcStages as $k => $s)
                        @php $a = deg2rad(-90 + $k * 360 / count($hwcStages)); $x = 60 + 46 * cos($a); $y = 60 + 46 * sin($a); @endphp
                        <g>
                            <circle cx="{{ round($x, 1) }}" cy="{{ round($y, 1) }}" r="10.5" fill="#1a2c12" stroke="{{ $s['key'] === 'auto' ? '#f5c518' : 'rgb(168 204 126 / .55)' }}" stroke-width="1.3"/>
                            <g transform="translate({{ round($x - 6.6, 1) }} {{ round($y - 6.6, 1) }}) scale(.55)" fill="none" stroke="{{ $s['key'] === 'auto' ? '#f5c518' : '#a8cc7e' }}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $s['glyph'] }}"/></g>
                        </g>
                    @endforeach
                </g>
            </svg>
            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">
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
                // From the buttons under a step, keep its top in view.
                if (!st.classList.contains('hwt-r')) panels[k].closest('.hwt-steps').scrollIntoView({ block: 'start', behavior: 'smooth' });
                return;
            }
            const tool = e.target.closest('[data-hwt-tool]');
            if (tool && sheet) { openSheet(tool.dataset.hwtTool, tool); return; }
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
        let back = null;
        const openSheet = (key, from) => {
            const t = data[key];
            if (!t) return;
            back = from;
            sheet.querySelector('[data-hwt-body]').innerHTML =
                (t.shot ? `<div class="hwt-shot"><div><img src="${esc(t.shot)}" alt="${esc(t.name)} in the app" loading="lazy"></div></div>` : '')
                + `<div class="hwt-sh-h"><img src="${esc(t.icon)}" alt=""><div><b>${esc(t.name)}</b><small>${t.anee ? '✨ Anee does the work · ' : ''}${esc(t.short)}</small></div></div>`
                + `<p class="hwt-sh-p">${esc(t.what)}</p>`
                + (t.gets && t.gets.length ? `<div class="hwt-sh-g">${t.gets.map((g) => `<div>${esc(g)}</div>`).join('')}</div>` : '')
                + `<p class="hwt-sh-plan">${esc(t.plan)}${t.more ? '. ' + esc(t.more) : ''}</p>`
                + (t.href ? `<a class="hwt-sh-go" href="${esc(t.href)}">${esc(t.go || 'Open it now')} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></a>` : '');
            sheet.querySelector('.hwt-sheet-in').scrollTop = 0;
            sheet.hidden = false;
            requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('is-on')));
            setTimeout(() => sheet.querySelector('.hwt-sheet-x').focus({ preventScroll: true }), 60);
        };
        const closeSheet = () => {
            if (sheet.hidden) return;
            sheet.classList.remove('is-on');
            setTimeout(() => { sheet.hidden = true; back?.focus({ preventScroll: true }); }, 380);
        };
        // Escape closes the sheet first, then the tour.
        b.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sheet && !sheet.hidden) { e.stopPropagation(); closeSheet(); } });
        b.addEventListener('stm:closed', () => { if (sheet) { sheet.classList.remove('is-on'); sheet.hidden = true; } });
    };
    const open = () => window.aneeStory.open(box, card, init);
    card.addEventListener('click', open);
    if (location.hash === '#how-it-works') setTimeout(open, 400);
    window.addEventListener('hashchange', () => { if (location.hash === '#how-it-works') open(); });
    box.addEventListener('stm:closed', () => { if (location.hash === '#how-it-works') history.replaceState(null, '', location.pathname + location.search); });
})();
</script>
@endpush
