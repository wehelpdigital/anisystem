{{-- Anee Auto on the dashboard (2026-10-14, the owner: "really simple and
     expandable"): one line of what she does and the start button, folded;
     open, her morning plays in a phone and the price shows. Folded by
     default, remembered per farm. The start button needs
     auto.partials.start on the page. --}}
@php
    $acPrice = \App\Support\AiPrices::of('auto');
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
    .ac-more svg { width: .95rem; height: .95rem; transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .ac.is-open .ac-more svg { transform: rotate(180deg); }
    .ac-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .45s cubic-bezier(.22,1,.36,1); }
    .ac.is-open .ac-fold { grid-template-rows: 1fr; }
    .ac-fold > div { min-height: 0; overflow: hidden; }
    .ac-in { display: grid; gap: 1rem; padding: 0 1rem 1.1rem 1.05rem; opacity: 0; transform: translateY(8px); transition: opacity .35s ease, transform .45s cubic-bezier(.22,1,.36,1); }
    .ac.is-open .ac-in { opacity: 1; transform: none; }
    @container (min-width: 620px) { .ac-in { grid-template-columns: minmax(0, 1fr) 16rem; align-items: center; } }
    .ac-c { container-type: inline-size; }
    .ac-steps { display: grid; gap: .5rem; counter-reset: ac; }
    .ac-step { display: flex; gap: .6rem; align-items: flex-start; font-size: .84rem; line-height: 1.45; color: #e8f1de; }
    .ac-step::before { counter-increment: ac; content: counter(ac); flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center;
        font-size: .72rem; font-weight: 900; color: #1d330d; background: #f5c518; }
    .ac-price { font-size: .78rem; color: #cfe3b9; } .ac-price b { color: #fff; }
    .ac-worth { grid-column: 1 / -1; padding-top: .9rem; border-top: 1px solid rgb(255 255 255 / .12); }
    /* Her morning, in a phone */
    .ac-phone { margin: 0 auto; width: min(100%, 16rem); padding: .55rem; border-radius: 1.7rem; background: #0b1406; border: 1px solid rgb(255 255 255 / .12); box-shadow: 0 24px 50px -28px rgb(0 0 0 / .8); }
    .ac-screen { border-radius: 1.25rem; padding: .6rem .5rem; background: #f6f9f1; display: grid; gap: .4rem; min-height: 15.5rem; align-content: start; }
    .ac-b { display: flex; gap: .35rem; align-items: flex-end; opacity: 0; transform: translateY(8px) scale(.97); }
    .ac.is-open .ac-b { animation: acPop .45s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--d) * .9s + .35s); }
    @keyframes acPop { to { opacity: 1; transform: none; } }
    .ac-b img { flex: none; width: 1.45rem; height: 1.45rem; border-radius: 999px; background: #e4efd4; }
    .ac-b div { padding: .4rem .55rem; border-radius: .85rem .85rem .85rem .25rem; background: #fff; border: 1px solid #e3eadb; font-size: .68rem; line-height: 1.4; color: #1f2a17; }
    .ac-b.is-me { justify-content: flex-end; } .ac-b.is-me div { background: #e7f2da; border-color: #cfe3b8; border-radius: .85rem .85rem .25rem .85rem; }
    .ac-t { display: flex; gap: .35rem; align-items: center; padding: .32rem .45rem; border-radius: .6rem; background: #f3f8ec; font-size: .64rem; font-weight: 700; color: #1f2a17; margin-top: .22rem; }
    .ac-t em { margin-left: auto; font-style: normal; font-size: .58rem; font-weight: 800; color: #8a5a00; background: #fff4d6; padding: 0 .35rem; border-radius: 999px; }
    @media (prefers-reduced-motion: reduce) {
        .ac::before, .ac::after, .ac-face, .ac-go::after { animation: none; }
        .ac-fold, .ac-in, .ac-more svg { transition: none; }
        .ac.is-open .ac-b { animation: none; opacity: 1; transform: none; }
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
        <button type="button" class="ac-more" data-ac-more aria-expanded="false" aria-controls="acFold">How it works
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
    </div>
    <div class="ac-fold" id="acFold"><div>
        <div class="ac-in">
            <div>
                <div class="ac-steps">
                    <div class="ac-step">Tell her about your lots, soil and water once.</div>
                    <div class="ac-step">She writes the whole season from your own plan.</div>
                    <div class="ac-step">Every morning she says what to do today, moved around the rain and your workers.</div>
                </div>
                <p class="ac-price mt-3"><b>{{ number_format($acPrice) }} credits per lot</b>, for the whole season.</p>
            </div>
            <div class="ac-phone" aria-hidden="true">
                <div class="ac-screen">
                    <div class="ac-b" style="--d:0"><img src="{{ $acWave }}" alt=""><div>Magandang umaga! Rain this afternoon, so we fertilize Lot 1 this morning.</div></div>
                    <div class="ac-b" style="--d:1"><img src="{{ $acFace }}" alt=""><div><b>Today</b>
                        <div class="ac-t">🧪 Second fertilizer, Lot 1<em>Now</em></div>
                        <div class="ac-t">💧 Keep 3 cm of water</div></div></div>
                    <div class="ac-b is-me" style="--d:2"><div>No workers tomorrow 🙅</div></div>
                    <div class="ac-b" style="--d:3"><img src="{{ $acFace }}" alt=""><div>Moved to Thursday, the next dry morning. 👍</div></div>
                </div>
            </div>
            {{-- Why the price is worth it (2026-10-15, the owner), the whole width under the steps and the phone. --}}
            <div class="ac-worth">@include('auto.partials.worth')</div>
        </div>
    </div></div>
</section>
</div>
<script>
(() => {
    /* Folded by default; the choice is kept per farm. */
    const card = document.getElementById('aneeAutoCard');
    const btn = card?.querySelector('[data-ac-more]');
    if (!card || !btn) return;
    const KEY = 'acOpen:' + @json(\App\Support\WorkerContext::effectiveOwnerId());
    const set = (open) => { card.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
    try { set(localStorage.getItem(KEY) === '1'); } catch (_) { set(false); }
    btn.addEventListener('click', () => {
        const open = !card.classList.contains('is-open');
        // Replay her morning each time it opens.
        if (open) card.querySelectorAll('.ac-b').forEach((b) => { b.style.animation = 'none'; void b.offsetWidth; b.style.animation = ''; });
        set(open);
        try { localStorage.setItem(KEY, open ? '1' : '0'); } catch (_) {}
    });
})();
</script>
