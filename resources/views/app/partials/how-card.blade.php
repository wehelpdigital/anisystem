{{-- How anee.io works, on the dashboard (2026-10-01): a card that opens the
     same seven step picture as the public /how-it-works page, full screen.
     The tour itself is public/how/journey ($hwMode 'app': its links open the
     tools in the app). It is mounted the first time the card is opened, and
     the modal grows out of the card and folds back into it. #how-it-works
     in the address opens it on arrival. --}}
@php $hwcStages = \App\Support\HowItWorks::stages(); @endphp

@push('head')
<style>
    .hwc { position: relative; overflow: hidden; isolation: isolate; border-radius: 1.25rem; color: #eef4e6;
        background: radial-gradient(28rem 14rem at 85% -20%, rgb(168 204 126 / .3), transparent 70%), linear-gradient(135deg, #14250d, #0d1609 70%);
        box-shadow: 0 20px 44px -30px rgb(13 22 9 / .9); }
    .hwc::before { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(rgb(255 255 255 / .12) 1px, transparent 1.5px); background-size: 18px 18px;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 40%); mask-image: linear-gradient(90deg, transparent, #000 40%); }
    .hwc-open { display: grid; gap: 1rem; width: 100%; padding: 1.1rem 1.1rem 1.15rem; text-align: left; cursor: pointer; }
    @media (min-width: 768px) { .hwc-open { grid-template-columns: minmax(0, 1fr) auto; align-items: center; padding: 1.25rem 1.4rem; gap: 1.4rem; } }
    .hwc-kick { display: inline-flex; align-items: center; gap: .45rem; font-size: .66rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #a8cc7e; }
    .hwc-h { display: block; margin-top: .35rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .hwc-p { display: block; margin-top: .3rem; font-size: .86rem; line-height: 1.5; color: #b9caa8; }
    /* A face for each step on a dotted line, a light running along it and each face waking as it passes. */
    .hwc-art { position: relative; display: flex; align-items: center; justify-content: space-between; gap: .35rem; margin-top: .9rem; max-width: 22rem; }
    .hwc-art::before { content: ''; position: absolute; left: 1rem; right: 1rem; top: 50%; height: 2px; margin-top: -1px;
        background: radial-gradient(circle, rgb(255 255 255 / .35) 1px, transparent 1.4px) left center / 8px 2px repeat-x; }
    .hwc-art::after { content: ''; position: absolute; left: 1rem; top: 50%; width: 2.2rem; height: 3px; margin-top: -1.5px; border-radius: 3px;
        background: linear-gradient(90deg, transparent, #f5c518); box-shadow: 0 0 10px #f5c518; animation: hwcRun 4.8s linear infinite; }
    @keyframes hwcRun { from { transform: translateX(0); opacity: 0; } 8% { opacity: 1; } 92% { opacity: 1; } to { transform: translateX(calc(min(22rem, 100vw - 5rem) - 4.2rem)); opacity: 0; } }
    .hwc-art i { position: relative; z-index: 1; width: 2.3rem; height: 2.3rem; border-radius: 999px; overflow: hidden; border: 2px solid rgb(168 204 126 / .4); background: #1a2c12;
        animation: hwcWake 4.8s var(--ease, cubic-bezier(.22,1,.36,1)) infinite; animation-delay: calc(var(--k) * 3.6s / var(--last, 5)); }
    .hwc-art img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(.7) brightness(.8); animation: hwcFace 4.8s ease infinite; animation-delay: calc(var(--k) * 3.6s / var(--last, 5)); }
    @keyframes hwcWake { 0%, 100% { transform: none; border-color: rgb(168 204 126 / .4); } 6% { transform: scale(1.18); border-color: #f5c518; } 16% { transform: none; border-color: #f5c518; } 40% { border-color: rgb(168 204 126 / .4); } }
    @keyframes hwcFace { 0%, 100% { filter: grayscale(.7) brightness(.8); } 6%, 30% { filter: none; } }
    .hwc-go { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .7rem 1.15rem; border-radius: .95rem; font-weight: 800; font-size: .92rem;
        color: #3b2f00; background: #f5c518; box-shadow: 0 10px 22px -12px rgb(245 197 24 / .8); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
    .hwc-go svg { width: 1.05rem; height: 1.05rem; }
    .hwc-open:hover .hwc-go { transform: translateY(-2px); box-shadow: 0 16px 28px -14px rgb(245 197 24 / .9); }
    .hwc-open:focus-visible { outline: 2px solid #f5c518; outline-offset: -4px; border-radius: 1.25rem; }

    /* The full screen tour. */
    html.hwm-lock { overflow: hidden; }
    .hwm { position: fixed; inset: 0; z-index: 260; display: flex; flex-direction: column; background: #0d1609; }
    .hwm[hidden] { display: none; }
    .hwm-bar { position: relative; z-index: 5; flex: none; display: flex; align-items: center; gap: .65rem; padding: calc(.6rem + env(safe-area-inset-top, 0px)) .8rem .6rem 1rem;
        background: rgb(13 22 9 / .92); border-bottom: 1px solid rgb(255 255 255 / .08); color: #fff; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
    .hwm-bar img { width: 2rem; height: 2rem; border-radius: 999px; object-fit: cover; }
    .hwm-bar b { font-family: var(--font-heading); font-size: 1rem; }
    .hwm-bar small { display: block; font-size: .7rem; color: #a8cc7e; font-weight: 700; }
    .hwm-x { margin-left: auto; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; color: #fff; background: rgb(255 255 255 / .08);
        transition: background .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .hwm-x:hover { background: rgb(255 255 255 / .16); transform: rotate(90deg); }
    .hwm-x svg { width: 1.2rem; height: 1.2rem; }
    .hwm-scroll { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
    .hwm-scroll .hw-stage { scroll-margin-top: 1rem; }
    /* backwards, not both: a transform held after the entrance would make the
       tour the box its position: fixed tool sheet is placed in. */
    .hwm-bar > div, .hwm-scroll > * { animation: hwmIn .5s .18s cubic-bezier(.22,1,.36,1) backwards; }
    @keyframes hwmIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .hwc-art::after, .hwc-art i, .hwc-art img, .hwm-bar > div, .hwm-scroll > * { animation: none; }
        .hwc-art img { filter: none; }
        .hwc-go, .hwm-x { transition: none; }
    }
</style>
@endpush

<section class="hwc" aria-labelledby="hwcH">
    <button type="button" class="hwc-open" id="hwcOpen" aria-haspopup="dialog" aria-controls="hwModal">
        <span>
            <span class="hwc-kick">How anee.io works</span>
            <span class="hwc-h" id="hwcH">See how Anee helps at every step</span>
            <span class="hwc-p">Seven steps, from the first plan to the last sack. Tap any tool to see what it does for your farm.</span>
            <span class="hwc-art" aria-hidden="true" style="--last: {{ max(1, count($hwcStages) - 1) }}">
                @foreach ($hwcStages as $k => $s)
                    <i style="--k: {{ $k }}"><img src="{{ asset('images/anee/emoji/' . $s['face'] . '.png') }}" alt=""></i>
                @endforeach
            </span>
        </span>
        <span class="hwc-go">
            <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z"/></svg>
            Open the tour
        </span>
    </button>
</section>

@push('sheets')
<div class="hwm" id="hwModal" role="dialog" aria-modal="true" aria-labelledby="hwmTitle" hidden>
    <div class="hwm-bar">
        <div class="flex items-center gap-2.5 min-w-0">
            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">
            <div class="min-w-0"><b id="hwmTitle">How anee.io works</b><small>Seven steps, with Anee at every one</small></div>
        </div>
        <button type="button" class="hwm-x" id="hwmClose" aria-label="Close the tour">
            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="hwm-scroll" id="hwmScroll">
        @include('public.how.journey', ['hwMode' => 'app'])
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const card = document.getElementById('hwcOpen'), modal = document.getElementById('hwModal');
    const scroll = document.getElementById('hwmScroll'), closeBtn = document.getElementById('hwmClose');
    if (!card || !modal) return;
    const EASE = 'cubic-bezier(.22,1,.36,1)';
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // The card's own box, as a clip on the full screen: the tour grows out of it and folds back in.
    const fromCard = () => {
        const r = card.getBoundingClientRect();
        return `inset(${Math.max(0, r.top)}px ${Math.max(0, innerWidth - r.right)}px ${Math.max(0, innerHeight - r.bottom)}px ${Math.max(0, r.left)}px round 1.25rem)`;
    };
    let busy = false;
    const open = () => {
        if (busy || !modal.hidden) return;
        busy = true;
        modal.hidden = false;
        document.documentElement.classList.add('hwm-lock');
        scroll.scrollTop = 0;
        const done = () => {
            busy = false;
            window.HowItWorks?.mount(modal.querySelector('[data-hw]'), { scroller: scroll });
            closeBtn.focus({ preventScroll: true });
        };
        if (still() || typeof modal.animate !== 'function') { done(); return; }
        modal.animate([{ clipPath: fromCard() }, { clipPath: 'inset(0px 0px 0px 0px round 0px)' }], { duration: 560, easing: EASE }).onfinish = done;
    };
    const close = () => {
        if (busy || modal.hidden) return;
        busy = true;
        const done = () => {
            modal.hidden = true;
            document.documentElement.classList.remove('hwm-lock');
            busy = false;
            card.focus({ preventScroll: true });
            if (location.hash === '#how-it-works') history.replaceState(null, '', location.pathname + location.search);
        };
        if (still() || typeof modal.animate !== 'function') { done(); return; }
        modal.animate([{ clipPath: 'inset(0px 0px 0px 0px round 0px)' }, { clipPath: fromCard() }], { duration: 460, easing: EASE }).onfinish = done;
    };
    card.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', (e) => {
        if (modal.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); close(); }
    });
    if (location.hash === '#how-it-works') setTimeout(open, 400);
    window.addEventListener('hashchange', () => { if (location.hash === '#how-it-works') open(); });
})();
</script>
@endpush
