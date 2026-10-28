{{-- The homepage hero's picture (2026-10-29, the owner: Anee "like it is now but there's changing animations (different
     animation per phase) about the benefits like higher yield, pest protection, complete nutrition, anti lodging"). Anee
     stays in the middle; around her one benefit at a time plays its own animation, and its card names it under her:
       yield      golden heads grow out all round her while a gauge fills
       pests      a shield draws itself round her and the pests that fly at it bounce off
       nutrition  the nutrients fly in to a ring round her and it fills, balanced
       lodging    a row of palay bends in the gusts and springs back up, still standing
       timing     the sun, then the rain cloud, and the work set on the dry morning
     Played only while on screen; the first benefit at rest when motion is not welcome (every element's resting style is
     its finished state, the animations only bring it there). The circle leans toward the pointer (the hero's data-tilt). --}}
@php
    $hbPhases = [
        ['yield', '🌾', 'Higher yield', 'Every decision timed for a bigger harvest'],
        ['pests', '🛡️', 'Pest and disease protection', 'Caught early, with what works'],
        ['nutri', '🧪', 'Complete nutrition', 'Every nutrient the crop needs, none wasted'],
        ['lodge', '🌀', 'Anti lodging', 'Strong stems that stand through the storm'],
        ['time', '🌦️', 'Weather smart timing', 'The right day, never into the rain'],
    ];
    $hbStalk = '<svg viewBox="0 0 20 60" aria-hidden="true"><path d="M10 60 C10 40 10 22 11 6" stroke="#5aa02c" stroke-width="2.2" fill="none" stroke-linecap="round"/><path d="M10 44 C4 38 2 30 3 24" stroke="#6fb33a" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M10 36 C16 30 18 22 17 15" stroke="#6fb33a" stroke-width="2" fill="none" stroke-linecap="round"/><path class="g" d="M11 6 C16 9 18 16 17 24" stroke="#f5c518" stroke-width="3.2" fill="none" stroke-linecap="round" stroke-dasharray="1.2 2.2"/></svg>';
    $hbBugs = ['🐛', '🦗', '🐀', '🐌', '🪲'];
    $hbNut = ['N', 'P', 'K', 'Zn', 'S', 'Mg', 'Ca', 'B'];
@endphp
<div class="hb" data-hb role="img" aria-label="Anee, your smart farm technician, and what she brings: higher yield, pest and disease protection, complete nutrition, anti lodging and weather smart timing">
    <div class="hb-stage">
        <span class="hb-orbit" aria-hidden="true"></span>
        <span class="hb-orbit is-in" aria-hidden="true"></span>

        {{-- Higher yield: heads grow out all round her, a gauge fills. --}}
        <div class="hb-ph" data-ph="yield" aria-hidden="true">
            <span class="hb-gauge is-gold"></span>
            @for ($i = 0; $i < 10; $i++)
                <span class="hb-stalk" style="--a: {{ $i * 36 }}deg; --k: {{ $i }}">{!! $hbStalk !!}</span>
            @endfor
            @for ($i = 0; $i < 9; $i++)
                <i class="hb-grain" style="--x: {{ 18 + $i * 8 }}%; --k: {{ $i }}"></i>
            @endfor
            <span class="hb-badge is-up" style="--k: 0">↑ More harvest</span>
        </div>

        {{-- Pest protection: a shield draws itself, the pests bounce off it. --}}
        <div class="hb-ph" data-ph="pests" aria-hidden="true">
            <svg class="hb-shield" viewBox="0 0 100 100"><circle cx="50" cy="50" r="30" pathLength="100"/><circle class="b" cx="50" cy="50" r="33.5" pathLength="100"/></svg>
            @foreach ($hbBugs as $i => $b)
                <span class="hb-bug" style="--a: {{ 20 + $i * 72 }}deg; --k: {{ $i }}">{{ $b }}<i></i></span>
            @endforeach
            <span class="hb-badge is-shield" style="--k: 0">✓ Protected</span>
        </div>

        {{-- Complete nutrition: the nutrients fly in to their ring, it fills. --}}
        <div class="hb-ph" data-ph="nutri" aria-hidden="true">
            <span class="hb-gauge is-green"></span>
            @foreach ($hbNut as $i => $n)
                <span class="hb-nut" style="--a: {{ $i * 45 }}deg; --k: {{ $i }}">{{ $n }}</span>
            @endforeach
            <span class="hb-badge is-bal" style="--k: 0">100% balanced</span>
        </div>

        {{-- Anti lodging: the gusts come, the palay bends and stands back up. --}}
        <div class="hb-ph" data-ph="lodge" aria-hidden="true">
            @for ($i = 0; $i < 6; $i++)
                <i class="hb-wind" style="--y: {{ 12 + $i * 13 }}%; --k: {{ $i }}"></i>
            @endfor
            <div class="hb-row">
                @for ($i = 0; $i < 11; $i++)
                    <span class="hb-tall" style="--k: {{ $i }}">{!! $hbStalk !!}</span>
                @endfor
            </div>
            <span class="hb-badge is-stand" style="--k: 0">✓ Still standing</span>
        </div>

        {{-- Weather smart timing: sun, the rain coming, the work on the dry morning. --}}
        <div class="hb-ph" data-ph="time" aria-hidden="true">
            <span class="hb-sun">☀️</span>
            <span class="hb-cloud">🌧️<i></i><i></i><i></i></span>
            <span class="hb-slot is-am">Thu 7 AM<b>✓ Spray</b></span>
            <span class="hb-slot is-pm">Thu 3 PM<b>🌧️ Rain</b></span>
        </div>

        <div class="hb-core">
            <span class="hb-halo" aria-hidden="true"></span>
            <img src="{{ asset('images/anee/avatar-512.jpg') }}" alt="" width="256" height="256">
        </div>
    </div>
    <div class="hb-cards">
        @foreach ($hbPhases as $i => [$k, $e, $t, $w])
            <div class="hb-card {{ $i === 0 ? 'is-on' : '' }}" data-card="{{ $k }}">
                <span class="hb-card-e" aria-hidden="true">{{ $e }}</span>
                <span><b>{{ $t }}</b><small>{{ $w }}</small></span>
            </div>
        @endforeach
    </div>
    <div class="hb-dots" aria-hidden="true">
        @foreach ($hbPhases as $i => $p)<i class="{{ $i === 0 ? 'is-on' : '' }}"></i>@endforeach
    </div>
</div>

@once
@push('head')
<style>
    /* ---- the hero: Anee and her benefits, one at a time (2026-10-29) ---- */
    @property --hbFill { syntax: '<percentage>'; inherits: false; initial-value: 0%; }
    .hb { position: relative; z-index: 1; width: min(100%, 31rem); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .hb-stage { position: relative; aspect-ratio: 1; container-type: inline-size; }
    .hb-stage::before { content: ''; position: absolute; inset: 3%; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(8 16 6 / .62), rgb(8 16 6 / .38) 70%, rgb(8 16 6 / 0)); }
    .hb-orbit { position: absolute; inset: 9%; border-radius: 999px; border: 1px dashed rgb(255 255 255 / .2); animation: hbSpin 70s linear infinite; }
    .hb-orbit.is-in { inset: 24%; border: 1px solid rgb(168 204 126 / .18); animation-direction: reverse; animation-duration: 50s; }
    @keyframes hbSpin { to { transform: rotate(360deg); } }
    /* Anee, always in the middle. */
    .hb-core { position: absolute; left: 50%; top: 50%; z-index: 5; width: 36%; aspect-ratio: 1; transform: translate(-50%, -50%); }
    .hb-halo { position: absolute; inset: -12%; border-radius: 999px; opacity: .8; filter: blur(10px); animation: hbSpin 6s linear infinite;
        background: conic-gradient(from 0deg, rgb(245 197 24 / 0), rgb(245 197 24 / .75), rgb(163 230 53 / .55), rgb(245 197 24 / 0) 70%); }
    .hb-core img { position: relative; display: block; width: 100%; height: 100%; border-radius: 999px; object-fit: cover; background: #1a2c12;
        box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f5c518, 0 30px 60px -20px rgb(0 0 0 / .8); }
    /* A benefit's layer: seen while its turn lasts. Each element rests in its finished state; .is-play runs it in. */
    .hb-ph { position: absolute; inset: 0; opacity: 0; transition: opacity .45s ease; pointer-events: none; }
    .hb-ph.is-on { opacity: 1; }
    .hb-badge { position: absolute; z-index: 6; left: 50%; top: 4%; transform: translateX(-50%); padding: .35rem .8rem; border-radius: 999px; white-space: nowrap;
        font-size: .8rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -10px rgb(0 0 0 / .7); }
    .hb-badge.is-shield { background: #a7f3d0; color: #064e3b; }
    .hb-badge.is-bal { background: #d9f99d; }
    .hb-badge.is-stand { background: #fff; }
    .hb-ph.is-play .hb-badge { animation: hbPop .6s cubic-bezier(.22,1,.36,1) 2.2s both; }
    @keyframes hbPop { from { opacity: 0; transform: translateX(-50%) translateY(10px) scale(.8); } }
    /* The ring gauge round Anee (yield gold, nutrition green). */
    .hb-gauge { position: absolute; left: 50%; top: 50%; width: 52%; aspect-ratio: 1; transform: translate(-50%, -50%); border-radius: 999px; --hbFill: 100%;
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 6px)); mask: radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 6px)); }
    .hb-gauge.is-gold { background: conic-gradient(#f5c518 var(--hbFill), rgb(255 255 255 / .12) 0); filter: drop-shadow(0 0 8px rgb(245 197 24 / .7)); }
    .hb-gauge.is-green { background: conic-gradient(#a3e635 var(--hbFill), rgb(255 255 255 / .12) 0); filter: drop-shadow(0 0 8px rgb(163 230 53 / .6)); }
    .hb-ph.is-play .hb-gauge { animation: hbFill 2.4s cubic-bezier(.45,0,.25,1) .3s both; }
    @keyframes hbFill { from { --hbFill: 0%; } }
    /* Yield: the heads, out all round her. */
    .hb-stalk { position: absolute; left: 50%; top: 50%; width: 6.5cqw; height: 19cqw; margin: -19cqw 0 0 -3.25cqw; transform-origin: 50% 100%;
        transform: rotate(var(--a)) translateY(-26cqw); }
    .hb-stalk svg { display: block; width: 100%; height: 100%; transform-origin: 50% 100%; }
    .hb-ph.is-play .hb-stalk svg { animation: hbGrow 1s cubic-bezier(.22,1,.36,1) calc(.2s + var(--k) * .09s) both; }
    @keyframes hbGrow { from { transform: scaleY(0); opacity: 0; } 60% { opacity: 1; } }
    .hb-grain { position: absolute; bottom: 8%; left: var(--x); width: .45rem; height: .7rem; border-radius: 999px; background: #f5c518; opacity: 0; box-shadow: 0 0 8px rgb(245 197 24 / .8); }
    .hb-ph.is-play .hb-grain { animation: hbRise 2.4s ease-out calc(1s + var(--k) * .18s) infinite; }
    @keyframes hbRise { 0% { opacity: 0; transform: translateY(0); } 20% { opacity: 1; } 100% { opacity: 0; transform: translateY(-14cqw); } }
    /* Pests: the shield, then the pests that fly at it and bounce off. */
    .hb-shield { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .hb-shield circle { fill: rgb(52 211 153 / .08); stroke: #34d399; stroke-width: 1.4; stroke-dasharray: 100; stroke-dashoffset: 0; filter: drop-shadow(0 0 3px rgb(52 211 153 / .8)); }
    .hb-shield circle.b { fill: none; stroke-width: .5; stroke-dasharray: 2 3; opacity: .7; }
    .hb-ph.is-play .hb-shield circle:not(.b) { animation: hbDraw 1.1s cubic-bezier(.45,0,.25,1) .2s both; }
    @keyframes hbDraw { from { stroke-dashoffset: 100; fill: rgb(52 211 153 / 0); } }
    .hb-ph.is-play .hb-shield { animation: hbPulse 1.2s ease-out 1.3s 3; }
    @keyframes hbPulse { 50% { filter: drop-shadow(0 0 10px rgb(52 211 153 / .9)); } }
    .hb-bug { position: absolute; left: 50%; top: 50%; width: 2.2rem; height: 2.2rem; margin: -1.1rem 0 0 -1.1rem; display: grid; place-items: center; font-size: 1.5rem; opacity: 0;
        transform: rotate(var(--a)) translateY(-44cqw) rotate(calc(var(--a) * -1)); }
    .hb-bug i { position: absolute; inset: -20%; border-radius: 999px; border: 2px solid #34d399; opacity: 0; }
    .hb-ph.is-play .hb-bug { animation: hbBug 2s cubic-bezier(.45,0,.25,1) calc(1.1s + var(--k) * .28s) both; }
    .hb-ph.is-play .hb-bug i { animation: hbHit .5s ease-out calc(1.95s + var(--k) * .28s) both; }
    @keyframes hbBug {
        0% { opacity: 0; transform: rotate(var(--a)) translateY(-56cqw) rotate(calc(var(--a) * -1)); }
        15% { opacity: 1; }
        45% { opacity: 1; transform: rotate(var(--a)) translateY(-35cqw) rotate(calc(var(--a) * -1)); }
        100% { opacity: 0; transform: rotate(var(--a)) translateY(-58cqw) rotate(calc(var(--a) * -1 + 60deg)) scale(.6); } }
    @keyframes hbHit { 0% { opacity: .9; transform: scale(.4); } 100% { opacity: 0; transform: scale(1.5); } }
    /* Nutrition: the nutrients come to their ring. */
    .hb-nut { position: absolute; left: 50%; top: 50%; width: 2.5rem; height: 2.5rem; margin: -1.25rem 0 0 -1.25rem; display: grid; place-items: center; border-radius: 999px;
        font-size: .85rem; font-weight: 900; color: #14210c; background: #fff; box-shadow: 0 0 0 3px #a3e635, 0 8px 18px -8px rgb(0 0 0 / .7);
        transform: rotate(var(--a)) translateY(-35cqw) rotate(calc(var(--a) * -1)); }
    .hb-nut:nth-child(-n+4) { background: #fef9c3; }
    .hb-ph.is-play .hb-nut { animation: hbIn .7s cubic-bezier(.22,1,.36,1) calc(.15s + var(--k) * .12s) both; }
    @keyframes hbIn { from { opacity: 0; transform: rotate(calc(var(--a) - 50deg)) translateY(-56cqw) rotate(calc((var(--a) - 50deg) * -1)) scale(.5); } }
    /* Lodging: the gusts, and a row of palay that bends and stands back up. */
    .hb-wind { position: absolute; left: -10%; top: var(--y); width: 30%; height: 2px; border-radius: 999px; opacity: 0;
        background: linear-gradient(90deg, transparent, rgb(219 234 254 / .9), transparent); }
    .hb-ph.is-play .hb-wind { animation: hbWind 1.2s linear calc(var(--k) * .17s) infinite; }
    @keyframes hbWind { 0% { opacity: 0; transform: translateX(0); } 20% { opacity: 1; } 100% { opacity: 0; transform: translateX(120cqw); } }
    .hb-row { position: absolute; left: 6%; right: 6%; bottom: 9%; height: 28%; display: flex; justify-content: space-between; align-items: flex-end; }
    .hb-tall { display: block; width: 7%; height: 100%; transform-origin: 50% 100%; }
    .hb-tall:nth-child(n+4):nth-child(-n+8) { height: 62%; }
    .hb-tall svg { display: block; width: 100%; height: 100%; }
    .hb-ph.is-play .hb-tall { animation: hbBend 1.6s ease-in-out calc(.3s + var(--k) * .05s) 2; }
    @keyframes hbBend { 0%, 100% { transform: rotate(0); } 35% { transform: rotate(22deg); } 55% { transform: rotate(9deg); } 75% { transform: rotate(14deg); } }
    /* Timing: the sun, then the rain cloud, and the work on the dry morning. */
    .hb-sun { position: absolute; left: 10%; top: 10%; font-size: 3rem; filter: drop-shadow(0 0 14px rgb(253 224 71 / .8)); }
    .hb-ph.is-play .hb-sun { animation: hbSun 3.6s ease-in-out .1s both; }
    @keyframes hbSun { 0% { opacity: 0; transform: scale(.5) rotate(-40deg); } 25% { opacity: 1; transform: none; } }
    .hb-cloud { position: absolute; right: 8%; top: 12%; font-size: 3rem; }
    .hb-cloud i { position: absolute; top: 80%; width: 2px; height: .8rem; border-radius: 999px; background: #bfdbfe; opacity: 0; }
    .hb-cloud i:nth-child(1) { left: 30%; } .hb-cloud i:nth-child(2) { left: 50%; animation-delay: .25s !important; } .hb-cloud i:nth-child(3) { left: 70%; animation-delay: .5s !important; }
    .hb-ph.is-play .hb-cloud { animation: hbCloud 1.2s cubic-bezier(.22,1,.36,1) .9s both; }
    .hb-ph.is-play .hb-cloud i { animation: hbDrop .8s linear 1.8s infinite; }
    @keyframes hbCloud { from { opacity: 0; transform: translateX(3rem); } }
    @keyframes hbDrop { 0% { opacity: 0; transform: translateY(0); } 30% { opacity: 1; } 100% { opacity: 0; transform: translateY(1.4rem); } }
    .hb-slot { position: absolute; z-index: 6; display: grid; gap: .1rem; padding: .45rem .7rem; border-radius: .9rem; font-size: .72rem; font-weight: 800; color: #4b5563; background: #fff;
        box-shadow: 0 12px 24px -12px rgb(0 0 0 / .7); }
    .hb-slot b { font-size: .86rem; color: #14210c; }
    .hb-slot.is-am { left: 3%; bottom: 14%; box-shadow: 0 0 0 3px #a3e635, 0 12px 24px -12px rgb(0 0 0 / .7); }
    .hb-slot.is-pm { right: 3%; bottom: 14%; opacity: .85; }
    .hb-ph.is-play .hb-slot.is-pm { animation: hbPopS .5s cubic-bezier(.22,1,.36,1) 1.6s both; }
    .hb-ph.is-play .hb-slot.is-am { animation: hbPopS .5s cubic-bezier(.22,1,.36,1) 2.3s both; }
    @keyframes hbPopS { from { opacity: 0; transform: translateY(12px) scale(.85); } }
    /* The benefit's card under her, and the dots. */
    .hb-cards { position: relative; height: 4.3rem; margin-top: -.4rem; }
    .hb-card { position: absolute; left: 0; right: 0; top: 0; display: flex; align-items: center; gap: .7rem; padding: .7rem .9rem .7rem .7rem; border-radius: 1.2rem; color: #14210c;
        background: rgb(255 255 255 / .97); box-shadow: 0 0 0 1px rgb(245 197 24 / .55), 0 20px 40px -18px rgb(0 0 0 / .8);
        opacity: 0; transform: translateY(14px) scale(.97); transition: opacity .2s ease, transform .2s ease; }
    .hb-card.is-on { opacity: 1; transform: none; transition: opacity .5s cubic-bezier(.22,1,.36,1) .15s, transform .5s cubic-bezier(.22,1,.36,1) .15s; }
    .hb-card-e { flex: none; display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .9rem; font-size: 1.35rem; background: #fff6d6; }
    .hb-card b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; line-height: 1.2; }
    .hb-card small { display: block; margin-top: .1rem; font-size: .8rem; line-height: 1.35; color: #4b5563; }
    .hb-dots { display: flex; justify-content: center; gap: .4rem; margin-top: .7rem; }
    .hb-dots i { width: .5rem; height: .5rem; border-radius: 999px; background: rgb(255 255 255 / .3); transition: width .35s cubic-bezier(.22,1,.36,1), background-color .35s ease; }
    .hb-dots i.is-on { width: 1.4rem; background: #f5c518; }
    @media (max-width: 639.98px) {
        .hb { width: min(100%, 23rem); }
        .hb-badge { font-size: .7rem; padding: .28rem .65rem; }
        .hb-bug { font-size: 1.15rem; }
        .hb-nut { width: 2rem; height: 2rem; margin: -1rem 0 0 -1rem; font-size: .72rem; }
        .hb-sun, .hb-cloud { font-size: 2.2rem; }
        .hb-slot { font-size: .62rem; padding: .35rem .55rem; } .hb-slot b { font-size: .74rem; }
        .hb-card b { font-size: .92rem; } .hb-card small { font-size: .74rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hb, .hb-orbit, .hb-halo, .hb-ph, .hb-ph *, .hb-card, .hb-dots i { animation: none !important; transition: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* One benefit at a time: its layer and its card come in, its animation plays from the start, then the next. Only
       while on screen. */
    const hb = document.querySelector('[data-hb]');
    if (!hb) return;
    const phases = [...hb.querySelectorAll('.hb-ph')], cards = [...hb.querySelectorAll('.hb-card')], dots = [...hb.querySelectorAll('.hb-dots i')];
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const STAY = 4400;
    let n = 0, timer = 0, live = false;
    const show = (i) => {
        phases.forEach((p, j) => { p.classList.toggle('is-on', j === i); if (j !== i) p.classList.remove('is-play'); });
        cards.forEach((c, j) => c.classList.toggle('is-on', j === i));
        dots.forEach((d, j) => d.classList.toggle('is-on', j === i));
        const p = phases[i];
        p.classList.remove('is-play'); void p.offsetWidth;
        if (!calm) p.classList.add('is-play');
    };
    const step = () => { if (!live) return; show(n); n = (n + 1) % phases.length; timer = setTimeout(step, STAY); };
    if (calm) { show(0); return; }
    show(0);
    const run = (on) => { if (on === live) return; live = on; clearTimeout(timer); if (on) step(); };
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => run(e.isIntersecting)), { threshold: .2 }).observe(hb);
    else run(true);
})();
</script>
@endpush
@endonce
