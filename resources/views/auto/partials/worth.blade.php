{{-- Why Anee Auto's price is worth it (2026-10-15, the owner: "explain why the
     1000 credits is worth it, like if it can increase your yield or prevent
     damages like lodging. Make it persuasive.").

     Honest persuasion: what each kind of season mistake can cost, what Anee
     does about it, and a small calculator on the farmer's own numbers (yield
     and price) that says how little of the harvest she must save to pay for
     herself. Examples, never promises; the last line says so.

     $tone: 'dark' (inside the green Anee Auto card, the default) or 'light'
     (a page of its own). Peso figures on the Philippine face only. --}}
@php
    $awTone = ($tone ?? 'dark') === 'light' ? 'light' : 'dark';
    $awPrice = (int) \App\Support\AiPrices::of('auto');
    $awPh = \App\Support\Region::ph();
    // What a lot's season of Anee Auto costs in pesos, from the credit packs on sale.
    $awPeso = null;
    if ($awPh) {
        try {
            $per = \Illuminate\Support\Facades\DB::table('anisystem_ai_credit_packs')->where('deleteStatus', 1)->where('isActive', 1)->where('credits', '>', 0)
                ->get(['credits', 'price'])->map(fn ($p) => (float) $p->price / (float) $p->credits);
            if ($per->isNotEmpty()) {
                $awPeso = [(int) round($per->min() * $awPrice / 10) * 10, (int) round($per->max() * $awPrice / 10) * 10];
            }
        } catch (\Throwable $e) {
            $awPeso = null;
        }
    }
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
    .aw-item em { display: inline-block; margin-top: .3rem; padding: .08rem .5rem; border-radius: 999px; font-style: normal; font-size: .68rem; font-weight: 800; color: #2b3a1c; background: #f5c518; }
    /* The calculator on the farmer's own numbers. */
    .aw-calc { padding: .85rem .9rem; border-radius: 1.1rem; background: var(--aw-card); box-shadow: inset 0 0 0 1px var(--aw-line); display: grid; gap: .7rem; }
    .aw-calc h4 { font-size: .8rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--aw-gold); }
    .aw-row { display: grid; gap: .3rem; }
    .aw-row label { display: flex; justify-content: space-between; gap: .5rem; font-size: .78rem; font-weight: 700; color: var(--aw-soft); }
    .aw-row label b { color: var(--aw-head); font-variant-numeric: tabular-nums; }
    .aw-row input[type=range] { width: 100%; accent-color: #f5c518; }
    .aw-out { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .aw-out > div { padding: .6rem .65rem; border-radius: .9rem; background: rgb(0 0 0 / .14); text-align: center; }
    .aw.is-light .aw-out > div { background: #fff; box-shadow: inset 0 0 0 1px var(--aw-line); }
    .aw-out b { display: block; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: var(--aw-gold); font-variant-numeric: tabular-nums; }
    .aw-out small { display: block; margin-top: .1rem; font-size: .68rem; line-height: 1.35; color: var(--aw-soft); }
    .aw-say { font-size: .82rem; line-height: 1.5; color: var(--aw-ink); }
    .aw-say b { color: var(--aw-gold); }
    .aw-fine { font-size: .68rem; line-height: 1.45; color: var(--aw-soft); opacity: .85; }
    @media (prefers-reduced-motion: reduce) { .aw-item { opacity: 1; transform: none; } .aw.is-shown .aw-item { animation: none; } }
</style>
@endonce
<section class="aw {{ $awTone === 'light' ? 'is-light' : '' }}" id="{{ $awId }}" aria-labelledby="{{ $awId }}T" data-aw
         data-price="{{ $awPrice }}" @if ($awPeso) data-peso-lo="{{ $awPeso[0] }}" data-peso-hi="{{ $awPeso[1] }}" @endif>
    <div class="aw-head">
        <img src="{{ $awIdea }}" alt="">
        <div>
            <h3 id="{{ $awId }}T">Why {{ number_format($awPrice) }} credits is worth it</h3>
            <p>@if ($awPeso) About <b>₱{{ number_format($awPeso[0]) }} to ₱{{ number_format($awPeso[1]) }}</b> a lot for the whole season. @else {{ number_format($awPrice) }} credits a lot for the whole season. @endif
                One season mistake usually costs far more than that. Anee is there every day to stop them.</p>
        </div>
    </div>

    <div class="aw-list">
        <div class="aw-item" style="--i:0"><span class="aw-ico" aria-hidden="true">🌾</span><div><b>Less lodging before harvest</b>
            <p>Too much nitrogen, or nitrogen at the wrong stage, makes the crop tall and weak, and wind lays it flat. She splits and times every bag to the crop's stage and the weather, and warns you before a strong wind or typhoon.</p>
            <em>A flattened field can lose a tenth of its grain or more</em></div></div>
        <div class="aw-item" style="--i:1"><span class="aw-ico" aria-hidden="true">🧪</span><div><b>Fertilizer that stays in the field</b>
            <p>Urea spread before a heavy rain washes away. She moves it to the next dry morning, so every bag you bought feeds the crop.</p>
            <em>One wasted bag of urea is more than a thousand pesos</em></div></div>
        <div class="aw-item" style="--i:2"><span class="aw-ico" aria-hidden="true">🐛</span><div><b>Pests caught early, sprays only when needed</b>
            <p>She tells you when to walk the field for hoppers, stem borers and disease at the stages they strike, and when not to spray at all, so the spiders and wasps that protect your crop live.</p>
            <em>Each spray you skip saves the chemical and a day of labor</em></div></div>
        <div class="aw-item" style="--i:3"><span class="aw-ico" aria-hidden="true">📈</span><div><b>A plan made for more harvest</b>
            <p>The right seed amount, the right water, nitrogen when the leaves ask for it and harvest at the right ripeness. Following good practice step by step is how fields close the gap to their potential.</p>
            <em>Every extra sack is yours</em></div></div>
        <div class="aw-item" style="--i:4"><span class="aw-ico" aria-hidden="true">🌧️</span><div><b>Work moved around the rain and your crew</b>
            <p>Every morning she reads the forecast and your workers' days, and moves the work so nothing is done in the rain or left undone.</p>
            <em>No more wasted trips and idle workers</em></div></div>
        <div class="aw-item" style="--i:5"><span class="aw-ico" aria-hidden="true">⏰</span><div><b>Your time back</b>
            <p>No guessing what to do today. She writes it, you follow, and you ask her anything, any time.</p>
            <em>A farm technician beside you all season</em></div></div>
    </div>

    @if ($awPeso)
    <div class="aw-calc" data-aw-calc>
        <h4>Try it on your own field</h4>
        <div class="aw-row"><label for="{{ $awId }}Y">Your usual harvest a hectare <b data-aw-yv></b></label>
            <input type="range" id="{{ $awId }}Y" min="1" max="10" step="0.5" value="4" data-aw-y></div>
        <div class="aw-row"><label for="{{ $awId }}P">Price you get a kilo <b data-aw-pv></b></label>
            <input type="range" id="{{ $awId }}P" min="10" max="40" step="1" value="20" data-aw-p></div>
        <div class="aw-out">
            <div><b data-aw-be>0</b><small>kilos a hectare she must save to pay for herself</small></div>
            <div><b data-aw-5>0</b><small>back for you if she saves just 5 percent of the harvest</small></div>
        </div>
        <p class="aw-say" data-aw-say></p>
    </div>
    @endif
    <p class="aw-fine">These are examples to help you weigh it, not promises. Your harvest still depends on the weather, your field and how closely the plan is followed. Anee says the exact price before you start.</p>
</section>
<script>
(() => {
    const box = document.getElementById(@json($awId));
    if (!box || box.dataset.awReady) return;
    box.dataset.awReady = '1';
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    // The cards rise in when the section comes into view (or when the fold opens).
    const show = () => box.classList.add('is-shown');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting && e.boundingClientRect.height > 0) { show(); io.disconnect(); } }), { threshold: .15 });
        io.observe(box);
    } else show();

    const calc = box.querySelector('[data-aw-calc]');
    if (!calc) return;
    const lo = Number(box.dataset.pesoLo), hi = Number(box.dataset.pesoHi), cost = (lo + hi) / 2;
    const y = calc.querySelector('[data-aw-y]'), p = calc.querySelector('[data-aw-p]');
    const fmt = (n) => Math.round(n).toLocaleString('en-PH');
    const tween = (el, to, pre) => {
        const from = Number(el.dataset.v || 0);
        el.dataset.v = String(to);
        if (reduce()) { el.textContent = (pre || '') + fmt(to); return; }
        const t0 = performance.now(), d = 450;
        const step = (t) => { const k = Math.min(1, (t - t0) / d), e = 1 - Math.pow(1 - k, 3); el.textContent = (pre || '') + fmt(from + (to - from) * e); if (k < 1) requestAnimationFrame(step); };
        requestAnimationFrame(step);
    };
    const paint = () => {
        const tons = Number(y.value), price = Number(p.value);
        calc.querySelector('[data-aw-yv]').textContent = tons + ' tons';
        calc.querySelector('[data-aw-pv]').textContent = '₱' + price;
        const harvest = tons * 1000 * price;
        const breakEven = cost / price;
        tween(calc.querySelector('[data-aw-be]'), breakEven);
        tween(calc.querySelector('[data-aw-5]'), harvest * 0.05, '₱');
        const share = breakEven / (tons * 1000) * 100;
        const times = (harvest * 0.05) / cost;
        calc.querySelector('[data-aw-say]').innerHTML = 'Your hectare is worth about <b>₱' + fmt(harvest) + '</b> at harvest. Anee pays for herself if she saves <b>' + (share < 1 ? 'less than 1 percent' : 'about ' + share.toFixed(1).replace(/\.0$/, '') + ' percent') + '</b> of it, roughly ' + fmt(breakEven / 50) + ' ' + (breakEven / 50 >= 1.5 ? 'sacks' : 'sack') + ' of 50 kilos. Saving 5 percent gives back <b>' + times.toFixed(1).replace(/\.0$/, '') + ' times</b> what she costs.';
    };
    y.addEventListener('input', paint);
    p.addEventListener('input', paint);
    paint();
})();
</script>
