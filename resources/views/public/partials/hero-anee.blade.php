{{-- The homepage hero's picture (2026-10-29, the owner chose "Anee at the center"): Anee in the middle, the things she
     reads around her (the weather, your soil, El Niño or La Niña, the satellite, prices, the variety). In turn, the
     sources a question needs light up, their light flows along the lines into her, she thinks, and her answer slides
     out as a card. Played only while on screen; one answer shown, still, when motion is not welcome. The card leans
     toward the pointer (the hero's data-tilt). --}}
@php
    // What she reads, around her, clockwise from the top: [key, icon, name, small line].
    $haSources = [
        ['wx', '🌦️', 'Weather', 'Your farm, by the hour'],
        ['sat', '🛰️', 'Satellite', 'Your field from space'],
        ['price', '📈', 'Prices', 'Farmgate, by month'],
        ['var', '🌾', 'Variety', 'Registered, ranked'],
        ['enso', '🌀', 'El Niño, La Niña', 'The months ahead'],
        ['soil', '🪨', 'Your soil', 'And your lot\'s history'],
    ];
    $haR = 40;   // the circle they sit on, in percent of the stage
    $haPos = [];
    foreach ($haSources as $i => $src) {
        $a = deg2rad(-90 + $i * 60);
        $haPos[$src[0]] = [round(50 + $haR * cos($a), 2), round(50 + $haR * sin($a), 2)];
    }
    // Her answers, each with the sources it read.
    $haAnswers = [
        [['wx', 'soil'], '🌧️', 'Rain at 3 PM today', 'I moved the spray on Lot 2 to 7 AM, so it dries before the rain.'],
        [['enso', 'var', 'wx'], '📅', 'Plant in the second week of June', 'The safest window for your variety this year.'],
        [['sat', 'soil'], '🛰️', 'A weak corner in Lot 2', 'The north end is paler than the rest. Walk it first.'],
        [['price', 'var'], '💰', 'Prices usually peak in February', 'Drying and keeping your harvest may pay this year.'],
    ];
@endphp
<div class="ha" data-ha role="img" aria-label="Anee, your smart farm technician, reading the weather, the satellite, prices, the variety, El Niño and La Niña and your soil, then answering">
    <div class="ha-stage">
        <span class="ha-orbit" aria-hidden="true"></span>
        <span class="ha-orbit is-in" aria-hidden="true"></span>
        <svg class="ha-lines" viewBox="0 0 100 100" aria-hidden="true">
            @foreach ($haPos as $k => [$x, $y])
                <path class="ha-base" d="M{{ $x }} {{ $y }} L50 50" pathLength="100"/>
                <path class="ha-flow" data-flow="{{ $k }}" d="M{{ $x }} {{ $y }} L50 50" pathLength="100"/>
            @endforeach
        </svg>
        @foreach ($haSources as [$k, $e, $n, $w])
            <span class="ha-src" data-src="{{ $k }}" style="left: {{ $haPos[$k][0] }}%; top: {{ $haPos[$k][1] }}%; --i: {{ $loop->index }}">
                <i aria-hidden="true">{{ $e }}</i><span><b>{{ $n }}</b><small>{{ $w }}</small></span>
            </span>
        @endforeach
        <div class="ha-core">
            <span class="ha-halo" aria-hidden="true"></span>
            <span class="ha-ring" aria-hidden="true"></span><span class="ha-ring is-2" aria-hidden="true"></span>
            <img src="{{ asset('images/anee/avatar-512.jpg') }}" alt="" width="256" height="256">
            <span class="ha-name">Anee<small><i aria-hidden="true"></i><span data-ha-state>Reading your farm</span></small></span>
        </div>
    </div>
    <div class="ha-answers" aria-live="off">
        @foreach ($haAnswers as $ai => [$reads, $e, $t, $w])
            <div class="ha-ans" data-reads="{{ implode(',', $reads) }}">
                <span class="ha-ans-e" aria-hidden="true">{{ $e }}</span>
                <span><b>{{ $t }}</b><small>{{ $w }}</small></span>
            </div>
        @endforeach
    </div>
</div>

@once
@push('head')
<style>
    /* ---- the hero's Anee at the center (2026-10-29) ---- */
    .ha { position: relative; z-index: 1; width: min(100%, 31rem); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .ha-stage { position: relative; aspect-ratio: 1; }
    .ha-orbit { position: absolute; inset: 10%; border-radius: 999px; border: 1px dashed rgb(255 255 255 / .22); animation: haSpin 70s linear infinite; }
    .ha-orbit.is-in { inset: 26%; border: 1px solid rgb(168 204 126 / .2); animation-direction: reverse; animation-duration: 50s; }
    @keyframes haSpin { to { transform: rotate(360deg); } }
    .ha-lines { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .ha-base { fill: none; stroke: rgb(255 255 255 / .16); stroke-width: .35; stroke-dasharray: 1 1.6; }
    .ha-flow { fill: none; stroke: #f5c518; stroke-width: .9; stroke-linecap: round; stroke-dasharray: 14 86; stroke-dashoffset: 14; opacity: 0;
        filter: drop-shadow(0 0 1.2px rgb(245 197 24 / .9)); }
    .ha-flow.is-on { opacity: 1; animation: haFlow 1.1s cubic-bezier(.45,0,.25,1) forwards; }
    @keyframes haFlow { from { stroke-dashoffset: 14; } to { stroke-dashoffset: -88; } }
    /* A source: a white pill on the circle. */
    .ha-src { position: absolute; z-index: 2; display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .7rem .38rem .38rem; border-radius: 999px; white-space: nowrap;
        transform: translate(-50%, -50%); color: #14210c; background: rgb(255 255 255 / .94);
        box-shadow: 0 10px 24px -12px rgb(0 0 0 / .7), 0 0 0 1px rgb(255 255 255 / .6); transition: box-shadow .35s ease, background-color .35s ease;
        animation: haBob 6s ease-in-out infinite; animation-delay: calc(var(--i) * -1s); }
    @keyframes haBob { 50% { translate: 0 -5px; } }
    .ha-src i { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 999px; font-style: normal; font-size: 1rem; background: #f0f7e6; transition: background-color .35s ease, transform .35s ease; }
    .ha-src b { display: block; font-size: .78rem; font-weight: 800; line-height: 1.15; }
    .ha-src small { display: block; font-size: .64rem; font-weight: 600; color: #6b7280; line-height: 1.2; }
    .ha-src.is-on { background: #fff; box-shadow: 0 0 0 3px #f5c518, 0 0 26px -2px rgb(245 197 24 / .75), 0 12px 26px -12px rgb(0 0 0 / .7); }
    .ha-src.is-on i { background: #fde68a; transform: scale(1.12); }
    /* Anee. */
    .ha-core { position: absolute; left: 50%; top: 50%; z-index: 3; width: 34%; aspect-ratio: 1; transform: translate(-50%, -50%); }
    .ha-halo { position: absolute; inset: -12%; border-radius: 999px; opacity: .85;
        background: conic-gradient(from 0deg, rgb(245 197 24 / 0), rgb(245 197 24 / .7), rgb(163 230 53 / .55), rgb(245 197 24 / 0) 70%); filter: blur(10px); animation: haSpin 6s linear infinite; }
    .ha-core img { position: relative; display: block; width: 100%; height: 100%; border-radius: 999px; object-fit: cover; background: #1a2c12;
        box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f5c518, 0 30px 60px -20px rgb(0 0 0 / .8); }
    .ha-ring { position: absolute; inset: -8px; border-radius: 999px; border: 2px solid rgb(245 197 24 / .8); opacity: 0; pointer-events: none; }
    .ha.is-think .ha-ring { animation: haRing 1.4s ease-out infinite; }
    .ha.is-think .ha-ring.is-2 { animation-delay: .7s; }
    @keyframes haRing { 0% { opacity: .9; transform: scale(1); } 100% { opacity: 0; transform: scale(1.45); } }
    .ha-name { position: absolute; left: 50%; top: calc(100% + .9rem); transform: translateX(-50%); display: grid; justify-items: center; white-space: nowrap;
        font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #fff; text-shadow: 0 2px 12px rgb(0 0 0 / .6); }
    .ha-name small { display: inline-flex; align-items: center; gap: .35rem; margin-top: .15rem; font-family: var(--font-sans, inherit); font-size: .7rem; font-weight: 800; color: #d9f99d; }
    .ha-name small i { width: .45rem; height: .45rem; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 3px rgb(34 197 94 / .3); animation: haLive 1.4s ease-in-out infinite; }
    @keyframes haLive { 50% { opacity: .35; } }
    /* Her answer, sliding out under the circle. */
    .ha-answers { position: relative; height: 4.3rem; margin-top: -1.2rem; }
    .ha-ans { position: absolute; left: 0; right: 0; top: 0; display: flex; align-items: center; gap: .7rem; padding: .7rem .9rem .7rem .7rem; border-radius: 1.2rem; color: #14210c;
        background: rgb(255 255 255 / .97); box-shadow: 0 0 0 1px rgb(245 197 24 / .55), 0 20px 40px -18px rgb(0 0 0 / .8);
        opacity: 0; transform: translateY(16px) scale(.97); transition: opacity .2s ease, transform .2s ease; }
    .ha-ans.is-on { opacity: 1; transform: none; transition: opacity .5s cubic-bezier(.22,1,.36,1) .1s, transform .5s cubic-bezier(.22,1,.36,1) .1s; }
    .ha-ans-e { flex: none; display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .9rem; font-size: 1.3rem; background: #fff6d6; }
    .ha-ans b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.25; }
    .ha-ans small { display: block; margin-top: .1rem; font-size: .8rem; line-height: 1.35; color: #4b5563; }
    @media (max-width: 639.98px) {
        .ha { width: min(100%, 24rem); }
        .ha-src { gap: .3rem; padding: .3rem .5rem .3rem .3rem; }
        .ha-src i { width: 1.5rem; height: 1.5rem; font-size: .85rem; }
        .ha-src b { font-size: .68rem; }
        .ha-src small { display: none; }
        .ha-name { font-size: .92rem; top: calc(100% + .6rem); }
        .ha-answers { height: 4.6rem; margin-top: -.6rem; }
        .ha-ans b { font-size: .86rem; } .ha-ans small { font-size: .74rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .ha, .ha-orbit, .ha-halo, .ha-src, .ha-ring, .ha-name small i, .ha-flow { animation: none !important; transition: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* Anee at the center: for each answer in turn, its sources light up one after another and their light flows into
       her, she thinks, and the answer slides out; it stays a while, then the next. Only while on screen. */
    const ha = document.querySelector('[data-ha]');
    if (!ha) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const answers = [...ha.querySelectorAll('.ha-ans')];
    const src = (k) => ha.querySelector('[data-src="' + k + '"]');
    const flow = (k) => ha.querySelector('[data-flow="' + k + '"]');
    const state = ha.querySelector('[data-ha-state]');
    let n = 0, timers = [], live = false;
    const later = (ms, fn) => timers.push(setTimeout(fn, ms));
    const clear = () => { timers.forEach(clearTimeout); timers = []; };
    const reset = () => {
        ha.querySelectorAll('.ha-src.is-on, .ha-flow.is-on').forEach((x) => x.classList.remove('is-on'));
        ha.classList.remove('is-think');
    };
    const show = (i) => answers.forEach((a, j) => a.classList.toggle('is-on', j === i));
    const cycle = () => {
        if (!live) return;
        reset();
        const a = answers[n], reads = a.dataset.reads.split(',');
        state.textContent = 'Reading your farm';
        reads.forEach((k, j) => later(250 + j * 420, () => {
            src(k).classList.add('is-on');
            const f = flow(k); f.classList.remove('is-on'); void f.getBBox(); f.classList.add('is-on');
        }));
        const t = 250 + reads.length * 420 + 700;
        later(t, () => { ha.classList.add('is-think'); state.textContent = 'Thinking'; });
        later(t + 900, () => { ha.classList.remove('is-think'); state.textContent = 'Answered in seconds'; show(n); });
        later(t + 900 + 3600, () => { n = (n + 1) % answers.length; cycle(); });
    };
    if (calm) { show(0); answers[0].dataset.reads.split(',').forEach((k) => src(k).classList.add('is-on')); return; }
    show(0);
    const run = (on) => {
        if (on === live) return;
        live = on; clear();
        if (on) cycle(); else reset();
    };
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => run(e.isIntersecting)), { threshold: .2 }).observe(ha);
    else run(true);
})();
</script>
@endpush
@endonce
