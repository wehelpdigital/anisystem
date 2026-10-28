{{-- Anee Auto on the dashboard (2026-10-14, the owner: "really simple and
     expandable"). Since 2026-10-28 (the owner: "we don't want the how it works
     when it expands, its too long ... show a modal box, a full screen modal
     explaining in an animated way what is anee auto"): one line of what she
     does, the start button, and How it works, which opens the story
     (auto.how-story) full screen in partials/story-modal's shell. The start
     button needs auto.partials.start on the page. --}}
@php
    $acFace = \App\Support\AneeEmoji::url('salute');
    $acWave = \App\Support\AneeEmoji::url('wave');
@endphp
@once
<style>
    .ac { position: relative; overflow: hidden; isolation: isolate; border-radius: 1.4rem; color: #eaf3df;
        background: radial-gradient(120% 160% at 100% 0%, #6aa33a 0%, #2d5016 48%, #10200a 100%); box-shadow: 0 24px 50px -34px rgb(20 33 12 / .8); }
    .ac::before { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none; opacity: .5;
        background-image: radial-gradient(circle at 15% 30%, rgb(255 255 255 / .55) 0 1px, transparent 1.6px), radial-gradient(circle at 70% 70%, rgb(255 255 255 / .35) 0 1px, transparent 1.6px);
        background-size: 120px 110px, 180px 150px; animation: acDrift 32s linear infinite; }
    @keyframes acDrift { to { background-position: 120px 110px, -180px 150px; } }
    /* A slow glow that breathes behind Anee. */
    .ac::after { content: ''; position: absolute; z-index: -1; left: -3rem; top: -3rem; width: 11rem; height: 11rem; border-radius: 999px;
        background: radial-gradient(circle, rgb(245 197 24 / .35), transparent 65%); animation: acGlow 5s ease-in-out infinite; }
    @keyframes acGlow { 50% { transform: scale(1.18); opacity: .7; } }
    .ac-top { display: flex; align-items: center; gap: .9rem; padding: 1rem 1rem 1rem 1.05rem; }
    .ac-face { position: relative; flex: none; width: 3.4rem; height: 3.4rem; border-radius: 999px; padding: .2rem; background: rgb(255 255 255 / .14);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .18); animation: acBob 3.8s ease-in-out infinite; }
    .ac-face img { width: 100%; height: 100%; object-fit: contain; }
    @keyframes acBob { 50% { transform: translateY(-3px) rotate(-3deg); } }
    .ac-txt { min-width: 0; flex: 1; }
    .ac-kick { font-size: .66rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #f5c518; }
    .ac-txt h2 { margin-top: .1rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; line-height: 1.22; color: #fff; }
    .ac-txt p { margin-top: .2rem; font-size: .8rem; line-height: 1.45; color: #cfe3b9; }
    .ac-acts { display: flex; align-items: center; gap: .5rem; padding: 0 1rem 1rem 1.05rem; flex-wrap: wrap; }
    .ac-go { position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: .45rem; padding: .7rem 1.15rem; border-radius: 999px; font-size: .9rem; font-weight: 800;
        color: #1d330d; background: #f5c518; box-shadow: 0 12px 26px -14px rgb(245 197 24 / .8); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ac-go:hover { transform: translateY(-2px); }
    .ac-go::after { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 30%, rgb(255 255 255 / .5) 50%, transparent 70%); transform: translateX(-100%); animation: acShine 3.4s ease-in-out infinite; }
    @keyframes acShine { 55%, 100% { transform: translateX(100%); } }
    .ac-go svg { width: 1rem; height: 1rem; }
    .ac-more { display: inline-flex; align-items: center; gap: .3rem; padding: .62rem .9rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #eaf3df;
        background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .16); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .ac-more:hover { background: rgb(255 255 255 / .16); }
    .ac-more svg { width: .95rem; height: .95rem; }
    @media (prefers-reduced-motion: reduce) {
        .ac::before, .ac::after, .ac-face, .ac-go::after { animation: none; }
    }
</style>
@endonce
<div class="ac-c">
<section class="ac" id="aneeAutoCard" aria-labelledby="acTitle">
    <div class="ac-top">
        <span class="ac-face"><img src="{{ $acFace }}" alt=""></span>
        <div class="ac-txt">
            <span class="ac-kick">Anee Auto</span>
            <h2 id="acTitle">Anee plans your season. You just follow.</h2>
            <p>She writes it, then keeps it on track every day.</p>
        </div>
    </div>
    <div class="ac-acts">
        <button type="button" class="ac-go" data-auto-start>Start Anee Auto
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></button>
        <button type="button" class="ac-more" data-ac-more aria-haspopup="dialog" aria-controls="acStory">How it works
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z"/></svg></button>
    </div>
</section>
</div>
@include('partials.story-modal')
@push('sheets')
<div class="stm" id="acStory" role="dialog" aria-modal="true" aria-labelledby="acStoryT" data-url="{{ route('auto.how') }}" hidden>
    <div class="stm-bg" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="stm-bar">
        <img src="{{ $acFace }}" alt="">
        <div class="min-w-0"><b id="acStoryT">Anee Auto</b><small>How it works</small></div>
        <button type="button" class="stm-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        <span class="stm-prog" aria-hidden="true"><i></i></span>
    </div>
    <div class="stm-body" tabindex="-1">
        <div class="stm-load" role="status"><span class="stm-load-face"><img src="{{ $acWave }}" alt=""></span><b data-wait="Anee is getting ready to show you">Anee is getting ready to show you</b></div>
        <div class="stm-host"></div>
    </div>
    <nav class="stm-dots" aria-label="Parts of the story"></nav>
</div>
@endpush
@push('scripts')
<script>
(() => {
    const card = document.getElementById('aneeAutoCard');
    const btn = card?.querySelector('[data-ac-more]'), box = document.getElementById('acStory');
    if (!card || !btn || !box) return;
    // Once the story is drawn: the worth cards rise when their part comes in, and Start closes the story first.
    const init = (b) => {
        b.addEventListener('stm:scene', (e) => e.detail.querySelectorAll('[data-aw]').forEach((w) => w.classList.add('is-shown')));
        b.addEventListener('click', (e) => {
            if (!e.target.closest('[data-acs-start]')) return;
            b.addEventListener('stm:closed', () => card.querySelector('[data-auto-start]')?.click(), { once: true });
            window.aneeStory.close(b);
        });
    };
    btn.addEventListener('click', () => window.aneeStory.open(box, btn, init));
})();
</script>
@endpush
