{{-- The homepage hero's picture (2026-10-29, the owner: "the phone video in the first section is somewhat redundant to
     so many phone videos so change the animation to something better"): a whole season growing in a few seconds. A lot
     of palay, drawn, grows from transplant to harvest while the day count runs; the sky turns with the weather (sun,
     a rainy afternoon, a typhoon watch); and at each moment Anee's note for it slides in, the work the app sets on its
     day. The expected harvest fills underneath. Played only while on screen; still, mid season, when motion is not
     welcome. The words and the moments are the app's own kinds of task, not a promise. --}}
@php
    // Clumps of palay, row by row from the back to the front: [y of the base, scale, how many across].
    $hsRows = [[134, .46, 19], [146, .56, 17], [160, .68, 15], [177, .82, 13], [198, .98, 11], [224, 1.18, 9], [252, 1.4, 7]];
    $hsSeed = 7;
    $hsRnd = function () use (&$hsSeed) { $hsSeed = ($hsSeed * 9301 + 49297) % 233280; return $hsSeed / 233280; };
    // A clump: six blades fanning out from its base, and two heads that show once it flowers.
    $hsClump = function (float $s) {
        $b = '';
        foreach ([[-14, -46, -26], [-7, -54, -12], [0, -60, -2], [6, -55, 10], [13, -47, 22], [-2, -50, 4]] as [$cx, $ty, $tx]) {
            $b .= sprintf('<path d="M0 0 Q%.1f %.1f %.1f %.1f"/>', $cx * $s * .6, $ty * $s * .55, $tx * $s, $ty * $s);
        }
        $h = sprintf('<path class="hs-head" d="M%.1f %.1f q%.1f %.1f %.1f %.1f"/><path class="hs-head" d="M%.1f %.1f q%.1f %.1f %.1f %.1f"/>',
            -2 * $s, -52 * $s, -8 * $s, 2 * $s, -12 * $s, 14 * $s, 4 * $s, -50 * $s, 8 * $s, 3 * $s, 11 * $s, 15 * $s);

        return $b . $h;
    };
    // Each moment of the season: the day count it comes at, the stage, the sky, Anee's note.
    $hsMoments = [
        [0, 'plant', 'sun', '🌱', 'Transplanted in the safest week', 'From twenty years of your town\'s weather'],
        [14, 'till', 'sun', '🧪', 'First urea, at early tillering', '1 bag on Lot 2, before 10 AM'],
        [34, 'till', 'rain', '🌧️', 'Rain at 3 PM. Spray moved to 7 AM', 'So it dries before the rain'],
        [48, 'pi', 'sun', '🔍', 'Stem borer check: all clear', 'No spray needed this week'],
        [62, 'flower', 'sun', '🌾', 'Flowering: keep 5 cm of water', 'The stage that decides the grain'],
        [84, 'ripen', 'storm', '🌀', 'Typhoon watch: field drained early', 'Strong stems, no lodging'],
        [100, 'harvest', 'sun', '🎉', 'Harvest window opens', 'Up to 8 tons from this hectare'],
    ];
    $hsStages = [['plant', 'Plant'], ['till', 'Tillering'], ['pi', 'Panicle'], ['flower', 'Flowering'], ['ripen', 'Ripening'], ['harvest', 'Harvest']];
@endphp
<div class="hs" data-hs aria-label="A season of palay growing with Anee, from transplant to harvest" role="img">
    <div class="hs-card">
        <div class="hs-top">
            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="80" height="80">
            <div class="hs-who"><b>Lot 2 · Palay</b><small><i aria-hidden="true"></i>Anee is watching your field</small></div>
            <div class="hs-dat"><small>DAT</small><b data-hs-dat>0</b></div>
        </div>
        <div class="hs-scene">
            <svg viewBox="0 0 400 250" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
                <defs>
                    <linearGradient id="hsSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7cc0f2"/><stop offset="1" stop-color="#dff1fb"/></linearGradient>
                    <linearGradient id="hsStorm" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3f4b5c"/><stop offset="1" stop-color="#9aa6b2"/></linearGradient>
                    <linearGradient id="hsWater" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b9dcf0" stop-opacity=".75"/><stop offset="1" stop-color="#7fb4d0" stop-opacity=".55"/></linearGradient>
                    <linearGradient id="hsMud" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8a7a55"/><stop offset="1" stop-color="#5f5236"/></linearGradient>
                </defs>
                <rect width="400" height="250" fill="url(#hsSky)"/>
                <rect class="hs-storm" width="400" height="250" fill="url(#hsStorm)"/>
                <g class="hs-sun"><circle cx="322" cy="46" r="26" fill="#fde68a" opacity=".45"/><circle cx="322" cy="46" r="17" fill="#fbbf24"/></g>
                <g class="hs-clouds" fill="#fff">
                    <g class="hs-cl a"><ellipse cx="80" cy="50" rx="34" ry="12"/><ellipse cx="100" cy="42" rx="22" ry="13"/></g>
                    <g class="hs-cl b"><ellipse cx="230" cy="34" rx="28" ry="9"/><ellipse cx="246" cy="28" rx="17" ry="10"/></g>
                </g>
                <g class="hs-dark" fill="#5b6675">
                    <ellipse cx="90" cy="40" rx="70" ry="22"/><ellipse cx="210" cy="30" rx="80" ry="24"/><ellipse cx="340" cy="44" rx="72" ry="22"/>
                </g>
                <path d="M0 112 Q60 94 120 104 T240 98 T400 102 V122 H0Z" fill="#6f9a72" opacity=".55"/>
                <path d="M0 118 Q80 106 170 114 T400 110 V128 H0Z" fill="#4f7d4a" opacity=".75"/>
                <rect y="122" width="400" height="128" fill="url(#hsMud)"/>
                <rect class="hs-water" y="122" width="400" height="128" fill="url(#hsWater)"/>
                @foreach ($hsRows as $ri => [$y, $s, $n])
                    <g class="hs-row" style="--r: {{ $ri }}">
                        @for ($i = 0; $i < $n; $i++)
                            @php $x = (400 / $n) * ($i + .5) + ($hsRnd() - .5) * (18 * $s); @endphp
                            <g transform="translate({{ round($x, 1) }} {{ $y }})"><g class="hs-c" style="--d: {{ round($hsRnd() * -4, 2) }}s">{!! $hsClump($s) !!}</g></g>
                        @endfor
                    </g>
                @endforeach
                <g class="hs-rain" stroke="#dbeafe" stroke-width="1.4" stroke-linecap="round">
                    @for ($i = 0; $i < 46; $i++)
                        @php $rx = round($hsRnd() * 440 - 20); $ry = round($hsRnd() * 250 - 40); @endphp
                        <line x1="{{ $rx }}" y1="{{ $ry }}" x2="{{ $rx - 6 }}" y2="{{ $ry + 16 }}"/>
                    @endfor
                </g>
            </svg>
            <span class="hs-sky" data-hs-sky>☀️ Sunny, 31°</span>
        </div>
        <div class="hs-notes">
                @foreach ($hsMoments as $mi => [$at, $st, $sky, $e, $t, $w])
                    <div class="hs-note" data-at="{{ $at }}" data-stage="{{ $st }}" data-sky="{{ $sky }}">
                        <span class="hs-note-e" aria-hidden="true">{{ $e }}</span>
                        <span><b>{{ $t }}</b><small>{{ $w }}</small></span>
                    </div>
                @endforeach
        </div>
        <div class="hs-track" aria-hidden="true">
            <div class="hs-line"><i data-hs-line></i></div>
            <div class="hs-stops">
                @foreach ($hsStages as [$k, $l])<span data-stop="{{ $k }}">{{ $l }}</span>@endforeach
            </div>
        </div>
        <div class="hs-yield">
            <span>Expected harvest</span>
            <div class="hs-ybar"><i data-hs-ybar></i></div>
            <b><span data-hs-y>0.0</span> t/ha</b>
        </div>
    </div>
</div>

@once
@push('head')
<style>
    /* ---- the hero's season (2026-10-29) ---- */
    .hs { position: relative; z-index: 1; width: min(100%, 30rem); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .hs-card { position: relative; padding: .8rem; border-radius: 1.8rem; background: linear-gradient(160deg, rgb(255 255 255 / .96), rgb(244 249 238 / .94));
        box-shadow: 0 2px 8px rgb(0 0 0 / .18), 0 50px 90px -40px rgb(0 0 0 / .85), 0 0 0 1px rgb(255 255 255 / .5); color: #14210c; }
    .hs-top { display: flex; align-items: center; gap: .65rem; padding: .25rem .35rem .7rem; }
    .hs-top img { width: 2.5rem; height: 2.5rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #f5c518; }
    .hs-who { flex: 1; min-width: 0; }
    .hs-who b { display: block; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; line-height: 1.2; }
    .hs-who small { display: flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 700; color: #4a7c2a; }
    .hs-who small i { width: .45rem; height: .45rem; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 3px rgb(34 197 94 / .25); animation: hsLive 1.6s ease-in-out infinite; }
    @keyframes hsLive { 50% { opacity: .35; } }
    .hs-dat { display: grid; justify-items: center; min-width: 3.6rem; padding: .3rem .55rem; border-radius: .9rem; color: #fff; background: linear-gradient(135deg, #3d6823, #24400f); }
    .hs-dat small { font-size: .58rem; font-weight: 900; letter-spacing: .12em; color: #d9f99d; }
    .hs-dat b { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
    /* The field. */
    .hs-scene { position: relative; aspect-ratio: 16 / 9.4; border-radius: 1.2rem; overflow: hidden; background: #7cc0f2; }
    .hs-scene svg { display: block; width: 100%; height: 100%; }
    .hs-storm { opacity: var(--storm, 0); transition: opacity .8s ease; }
    .hs-dark { opacity: var(--storm, 0); transition: opacity .8s ease; }
    .hs-sun { opacity: calc(1 - var(--storm, 0)); transition: opacity .8s ease; }
    .hs-cl { animation: hsDrift 18s linear infinite; }
    .hs-cl.b { animation-duration: 26s; animation-delay: -9s; }
    @keyframes hsDrift { from { transform: translateX(-60px); } to { transform: translateX(420px); } }
    .hs-water { opacity: var(--water, 1); transition: opacity .8s ease; }
    .hs-c { stroke: #4f9a2e; stroke: color-mix(in oklab, #d8b63c calc(var(--ripe, 0) * 100%), #45952b); stroke-width: 2.1; stroke-linecap: round; fill: none;
        transform-box: fill-box; transform-origin: 50% 100%; transform: scale(calc(.35 + .65 * var(--g, 0)), var(--g, .15));
        animation: hsSway 3.6s ease-in-out infinite; animation-delay: var(--d, 0s); }
    @keyframes hsSway { 0%, 100% { rotate: calc(var(--wind, 1) * -2deg); } 50% { rotate: calc(var(--wind, 1) * 2.5deg); } }
    .hs-head { stroke: #e8c547; stroke-width: 2.6; opacity: var(--head, 0); }
    .hs-rain { opacity: var(--rain, 0); transition: opacity .6s ease; animation: hsRain .5s linear infinite; }
    @keyframes hsRain { from { transform: translate(0, 0); } to { transform: translate(-6px, 18px); } }
    .hs-sky { position: absolute; top: .6rem; left: .6rem; padding: .28rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #14210c;
        background: rgb(255 255 255 / .85); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); transition: background-color .5s ease, color .5s ease; }
    .hs-sky.is-storm { color: #fff; background: rgb(30 41 59 / .78); }
    .hs-sky.is-rain { color: #0c4a6e; background: rgb(224 242 254 / .9); }
    /* Anee's note for the moment, sliding up over the field. */
    .hs-notes { position: relative; height: 3.5rem; margin-top: -1.6rem; padding: 0 .6rem; z-index: 2; }
    .hs-note { position: absolute; left: .6rem; right: .6rem; top: 0; bottom: 0; display: flex; align-items: center; gap: .55rem; padding: .5rem .7rem .5rem .5rem; border-radius: 1rem; background: #fff;
        box-shadow: 0 0 0 1px #e3ecd9, 0 14px 28px -14px rgb(20 33 12 / .55); opacity: 0; transform: translateY(14px) scale(.97); transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1); }
    /* The old note leaves quickly, the new one comes in just after, so their words never cross. */
    .hs-note:not(.is-on) { transition-duration: .18s; }
    .hs-note.is-on { opacity: 1; transform: none; transition-delay: .16s; }
    .hs-note-e { flex: none; display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border-radius: .8rem; font-size: 1.15rem; background: #f0f7e6; }
    .hs-note b { display: block; font-size: .84rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .hs-note small { display: block; font-size: .72rem; line-height: 1.3; color: #4b5563; }
    /* The stages, a line that fills along them. */
    .hs-track { padding: .75rem .35rem .2rem; }
    .hs-line { height: 4px; border-radius: 999px; background: #e3ecd9; overflow: hidden; }
    .hs-line i { display: block; height: 100%; width: var(--p, 0%); border-radius: inherit; background: linear-gradient(90deg, #6b9f3d, #f5c518); }
    .hs-stops { display: flex; justify-content: space-between; margin-top: .4rem; }
    .hs-stops span { font-size: .62rem; font-weight: 800; color: #9ca3af; transition: color .3s ease; }
    .hs-stops span.is-on { color: #3d6823; }
    .hs-stops span.is-now { color: #14210c; }
    /* The expected harvest. */
    .hs-yield { display: flex; align-items: center; gap: .6rem; margin-top: .55rem; padding: .55rem .7rem; border-radius: 1rem; background: #fffaf0; box-shadow: inset 0 0 0 1px #f3e2a6; }
    .hs-yield > span { flex: none; font-size: .7rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #8a6400; }
    .hs-ybar { flex: 1; height: .5rem; border-radius: 999px; background: #f6ebc4; overflow: hidden; }
    .hs-ybar i { display: block; height: 100%; width: var(--y, 0%); border-radius: inherit; background: linear-gradient(90deg, #f5c518, #e6ad12); }
    .hs-yield b { flex: none; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: #14210c; font-variant-numeric: tabular-nums; }
    @media (max-width: 520px) {
        .hs-card { padding: .6rem; border-radius: 1.4rem; }
        .hs-note b { font-size: .78rem; } .hs-note small { font-size: .66rem; }
        .hs-stops span { font-size: .55rem; }
        .hs-yield > span { font-size: .6rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hs-cl, .hs-c, .hs-rain, .hs-who small i { animation: none !important; }
        .hs, .hs-note, .hs-storm, .hs-dark, .hs-sun, .hs-water { transition: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* The season, in fifteen seconds: the day count runs 0 to 110, the crop grows and ripens, the sky follows the
       moment, Anee's note for it slides in, and the harvest fills; it rests at harvest, then starts again. */
    const hs = document.querySelector('[data-hs]');
    if (!hs) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const notes = [...hs.querySelectorAll('.hs-note')];
    const at = notes.map((n) => Number(n.dataset.at));
    const stops = [...hs.querySelectorAll('[data-stop]')];
    const order = stops.map((s) => s.dataset.stop);
    const $ = (s) => hs.querySelector(s);
    const datEl = $('[data-hs-dat]'), yEl = $('[data-hs-y]'), sky = $('[data-hs-sky]'), line = $('[data-hs-line]'), ybar = $('[data-hs-ybar]');
    const SKIES = { sun: ['☀️ Sunny, 31°', ''], rain: ['🌧️ Rain at 3 PM', 'is-rain'], storm: ['🌀 Typhoon signal 2 nearby', 'is-storm'] };
    const RUN = 15000, REST = 2600, END = 110;
    const ease = (k) => k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2;
    let t0 = 0, raf = 0, last = -1, lastStage = '';
    const paint = (dat) => {
        const g = Math.min(1, dat / 70), ripe = Math.max(0, Math.min(1, (dat - 70) / 30)), head = Math.max(0, Math.min(1, (dat - 58) / 14));
        let k = 0;
        at.forEach((a, i) => { if (dat >= a) k = i; });
        const n = notes[k], skyKey = n.dataset.sky;
        const storm = skyKey === 'storm' ? 1 : skyKey === 'rain' ? .55 : 0;
        hs.style.setProperty('--g', (.15 + .85 * g).toFixed(3));
        hs.style.setProperty('--ripe', ripe.toFixed(3));
        hs.style.setProperty('--head', head.toFixed(3));
        hs.style.setProperty('--storm', storm);
        hs.style.setProperty('--rain', skyKey === 'sun' ? 0 : 1);
        hs.style.setProperty('--wind', skyKey === 'storm' ? 3 : 1);
        hs.style.setProperty('--water', dat < 82 ? 1 : Math.max(0, 1 - (dat - 82) / 6));
        datEl.textContent = Math.round(dat);
        line.style.setProperty('--p', (dat / END * 100).toFixed(1) + '%');
        const y = 8 * Math.min(1, dat / 100);
        yEl.textContent = y.toFixed(1);
        ybar.style.setProperty('--y', (y / 8 * 100).toFixed(1) + '%');
        if (k !== last) {
            notes.forEach((x, i) => x.classList.toggle('is-on', i === k));
            const [say, cls] = SKIES[skyKey] || SKIES.sun;
            sky.textContent = say; sky.className = 'hs-sky ' + cls;
            last = k;
        }
        const st = n.dataset.stage;
        if (st !== lastStage) {
            const si = order.indexOf(st);
            stops.forEach((s, i) => { s.classList.toggle('is-on', i <= si); s.classList.toggle('is-now', i === si); });
            lastStage = st;
        }
    };
    const frame = (now) => {
        if (!t0) t0 = now;
        const e = (now - t0) % (RUN + REST);
        paint(e < RUN ? END * ease(e / RUN) : END);
        raf = requestAnimationFrame(frame);
    };
    if (calm) { paint(64); return; }
    paint(0);
    const run = (on) => { cancelAnimationFrame(raf); raf = 0; if (on) { t0 = 0; raf = requestAnimationFrame(frame); } };
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => run(e.isIntersecting)), { threshold: .2 }).observe(hs);
    else run(true);
})();
</script>
@endpush
@endonce
