{{-- Why Anee Auto's price is worth it (2026-10-15, the owner: "explain why the
     1000 credits is worth it, like if it can increase your yield or prevent
     damages like lodging. Make it persuasive.").

     Honest persuasion: what each kind of season mistake can cost and what
     Anee does about it, then a question that leaves the sum to the farmer.
     Examples, never promises; the last line says so.

     Second pass the same day (the owner): no price in the heading or the
     intro, five cards (the fertilizer card went), short lines, gold tags only
     where they add something, and the "imagine" question in place of the
     calculator.

     $tone: 'dark' (inside the green Anee Auto card, the default) or 'light'
     (a page of its own). --}}
@php
    $awTone = ($tone ?? 'dark') === 'light' ? 'light' : 'dark';
    $awIdea = \App\Support\AneeEmoji::url('idea');
    $awId = 'aw' . substr(md5(uniqid('', true)), 0, 6);
@endphp
@once
<style>
    .aw { --aw-ink: #eaf3df; --aw-soft: #cfe3b9; --aw-card: rgb(255 255 255 / .07); --aw-line: rgb(255 255 255 / .12); --aw-gold: #f5c518; --aw-head: #fff;
        display: grid; gap: .85rem; color: var(--aw-ink); }
    .aw.is-light { --aw-ink: #1f2a17; --aw-soft: #4b5563; --aw-card: #f6faf1; --aw-line: #dfe8d4; --aw-gold: #b88a00; --aw-head: #14210c;
        padding: 1.2rem; border-radius: 1.4rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6); }
    html.dark .aw.is-light { --aw-ink: #e8efe1; --aw-soft: #b9caa8; --aw-card: #1a2114; --aw-line: #2b3a1c; --aw-head: #fff; --aw-gold: #f5c518; background: #141a10; border-color: #2b3a1c; }
    .aw-head { display: flex; align-items: center; gap: .7rem; }
    .aw-head img { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; background: rgb(255 255 255 / .12); padding: .15rem; }
    .aw-head h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; line-height: 1.25; color: var(--aw-head); }
    .aw-head p { margin-top: .15rem; font-size: .8rem; line-height: 1.45; color: var(--aw-soft); }
    .aw-head b { color: var(--aw-gold); }
    /* The mistakes she heads off: a card each. */
    .aw-list { display: grid; gap: .5rem; }
    @container (min-width: 620px) { .aw-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .aw-item { display: flex; gap: .65rem; align-items: flex-start; padding: .7rem .75rem; border-radius: 1rem; background: var(--aw-card); box-shadow: inset 0 0 0 1px var(--aw-line);
        opacity: 0; transform: translateY(8px); }
    .aw.is-shown .aw-item { animation: awIn .5s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--i) * 90ms + .1s); }
    @keyframes awIn { to { opacity: 1; transform: none; } }
    .aw-ico { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.15rem; background: rgb(245 197 24 / .16); }
    .aw-item b { display: block; font-size: .86rem; font-weight: 800; color: var(--aw-head); line-height: 1.3; }
    .aw-item p { margin-top: .15rem; font-size: .76rem; line-height: 1.45; color: var(--aw-soft); }
    /* Five cards in two columns: the last one takes the whole row, so none sits alone. */
    @container (min-width: 620px) { .aw-list > .aw-item:last-child:nth-child(odd) { grid-column: 1 / -1; } }
    /* The question that closes it: the farmer does the sum. */
    .aw-ask { display: flex; gap: .75rem; align-items: flex-start; padding: .9rem 1rem; border-radius: 1.1rem; background: rgb(245 197 24 / .12); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45);
        opacity: 0; transform: translateY(8px); }
    .aw.is-shown .aw-ask { animation: awIn .5s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--i) * 90ms + .1s); }
    .aw-ask-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.25rem; background: rgb(245 197 24 / .22); }
    .aw-ask b { display: block; font-family: var(--font-heading); font-size: .98rem; font-weight: 800; line-height: 1.3; color: var(--aw-head); }
    .aw-ask p { margin-top: .25rem; font-size: .82rem; line-height: 1.5; color: var(--aw-ink); }
    .aw-ask p strong { color: var(--aw-head); }
    .aw.is-light .aw-ask { background: #fffbeb; box-shadow: inset 0 0 0 1px #fde68a; }
    html.dark .aw.is-light .aw-ask { background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .aw-fine { font-size: .68rem; line-height: 1.45; color: var(--aw-soft); opacity: .85; }
    @media (prefers-reduced-motion: reduce) { .aw-item, .aw-ask { opacity: 1; transform: none; } .aw.is-shown .aw-item, .aw.is-shown .aw-ask { animation: none; } }
</style>
@endonce
<section class="aw {{ $awTone === 'light' ? 'is-light' : '' }}" id="{{ $awId }}" aria-labelledby="{{ $awId }}T" data-aw>
    <div class="aw-head">
        <img src="{{ $awIdea }}" alt="">
        <div>
            <h3 id="{{ $awId }}T">Why spending with Anee Auto is worth it</h3>
            <p>One season mistake usually costs far more than Anee Auto does. Anee is there every day to stop them.</p>
        </div>
    </div>

    <div class="aw-list">
        <div class="aw-item" style="--i:0"><span class="aw-ico" aria-hidden="true">🌾</span><div><b>Less lodging before harvest</b>
            <p>With Anee, you avoid lodging that could cost you up to 40 percent of your potential yield. She times every bag of fertilizer to the crop's stage and the weather, and warns you before a strong wind or typhoon.</p></div></div>
        <div class="aw-item" style="--i:1"><span class="aw-ico" aria-hidden="true">🐛</span><div><b>Pests caught early, disease cured accurately</b>
            <p>She tells you when to check the field and the right cure for what you find, before the damage spreads.</p></div></div>
        <div class="aw-item" style="--i:2"><span class="aw-ico" aria-hidden="true">📈</span><div><b>A plan made for more harvest</b>
            <p>The right seed amount, the right water, and nutrients when the plant asks for them. Following good practice step by step is how fields close the gap to their potential.</p></div></div>
        <div class="aw-item" style="--i:3"><span class="aw-ico" aria-hidden="true">🌧️</span><div><b>Work moved around the rain and your crew</b>
            <p>Every morning she reads the forecast and your workers' days. When rain is coming or a worker cannot come, she moves the work to the best day it can be done, so nothing is done in the rain or left undone.</p></div></div>
        <div class="aw-item" style="--i:4"><span class="aw-ico" aria-hidden="true">⏰</span><div><b>Your time back</b>
            <p>No guessing what to do today. She writes it, you follow, and you ask her anything, any time.</p></div></div>
    </div>

    <div class="aw-ask" style="--i:5"><span class="aw-ask-ico" aria-hidden="true">🤔</span><div><b>Imagine your whole season</b>
        <p>Think of what you will spend on Anee. Now think of how much more she can help you harvest, and how much of your yield she can protect and secure from lodging, pests, disease and bad weather. <strong>Which is bigger?</strong></p></div></div>
    <p class="aw-fine">These are examples to help you weigh it, not promises. Your harvest still depends on the weather, your field and how closely the plan is followed. Anee says the exact price before you start.</p>
</section>
<script>
(() => {
    const box = document.getElementById(@json($awId));
    if (!box || box.dataset.awReady) return;
    box.dataset.awReady = '1';
    // The cards rise in when the section comes into view (or when the fold opens).
    const show = () => box.classList.add('is-shown');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting && e.boundingClientRect.height > 0) { show(); io.disconnect(); } }), { threshold: .15 });
        io.observe(box);
    } else show();
})();
</script>
