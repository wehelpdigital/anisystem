{{-- The homepage hero's picture (2026-10-29, the owner: Anee "like it is now but there's changing animations (different
     animation per phase) about the benefits like higher yield, pest protection, complete nutrition, anti lodging"). Anee
     stays in the middle; around her one benefit at a time plays its own animation, and its card names it under her:
       yield      two piles of rice sacks, lying down, stack up one by one: the usual way stops, Anee's goes higher
                  with an arrow up and a faded sack on top, the pile not yet done. No tonnage or cavans anywhere in
                  the hero (the owner: specific yield numbers set the wrong expectations)
       pests      a shield draws itself round her and the pests that fly at it bounce off
       nutrition  eight nutrients, flat see through orbs each in its own color, fly in; each fills its own part of
                  the ring round her, then floats, a dashed ring with a dot turning round it, and sends its drop in to her;
                  the smaller micronutrients circle further out (the card says all macro and micro, never a count)
       lodging    a severe wind: streaks of every length and speed, gusts sweeping across, leaves torn past her, the
                  sky darkened, and Anee unmoved in the middle
       timing     a sky split in two: the sun's rays on one side, strong rain and lightning on the other
     Each card (the owner: the titles were "too simple") carries the benefit's name, a heading, a line, a number and a
     bar that fills while its turn lasts, in the benefit's own color. Played only while on screen; the first benefit at
     rest when motion is not welcome (every element's resting style is its finished state, the animations only bring it
     there). The circle leans toward the pointer (the hero's data-tilt). The drawings (the sack, the rice plant) are
     symbols drawn once, at the top. --}}
@php
    // Each benefit's card: its color, its name, a heading (the <em> part in color), a line, and a number.
    $hbPhases = [
        ['k' => 'yield', 'c' => '#f5c518', 'g' => 'rgb(245 197 24 / .28)', 'e' => '🌾', 'kick' => 'Higher yield',
            'h' => 'A Bigger Harvest, <em>Every Season</em>', 'w' => 'Every decision made for more grain per hectare', 'pre' => 'Bigger', 'n' => '▲', 'u' => 'every season'],
        ['k' => 'pests', 'c' => '#34d399', 'g' => 'rgb(52 211 153 / .26)', 'e' => '🛡️', 'kick' => 'Pest and disease protection',
            'h' => 'Caught Early. <em>Stopped Fast.</em>', 'w' => 'Anee spots the threat and names what works', 'pre' => 'Knows', 'n' => '229', 'u' => 'pests and diseases'],
        ['k' => 'nutri', 'c' => '#a3e635', 'g' => 'rgb(163 230 53 / .24)', 'e' => '🧪', 'kick' => 'Complete nutrition',
            'h' => 'Every Nutrient. <em>Nothing Wasted.</em>', 'w' => 'The right amount for your soil and your crop', 'pre' => '', 'n' => 'All', 'u' => 'macro and micro nutrients'],
        ['k' => 'lodge', 'c' => '#7dd3fc', 'g' => 'rgb(125 211 252 / .24)', 'e' => '🌀', 'kick' => 'Anti lodging',
            'h' => 'Stems That <em>Stand Strong</em>', 'w' => 'Through wind and rain, all the way to harvest', 'pre' => 'Built for', 'n' => 'Storms', 'u' => 'and strong winds'],
        ['k' => 'time', 'c' => '#fb923c', 'g' => 'rgb(251 146 60 / .26)', 'e' => '🌦️', 'kick' => 'Weather smart timing',
            'h' => 'The Right Day, <em>Every Time</em>', 'w' => 'Your work set around the rain, hour by hour', 'pre' => '', 'n' => '20', 'u' => 'years of weather read'],
    ];
    $hbBugs = ['🐛', '🦗', '🐀', '🐌', '🪲'];
    // The nutrients, each in its own color.
    $hbNut = [['N', '#16a34a'], ['P', '#ea580c'], ['K', '#9333ea'], ['Zn', '#0284c7'], ['S', '#ca8a04'], ['Mg', '#0d9488'], ['Ca', '#64748b'], ['B', '#db2777']];
    // The smaller micronutrients that circle further out.
    $hbMicro = [['Fe', '#b45309'], ['Mn', '#7c3aed'], ['Cu', '#0e7490'], ['Mo', '#4d7c0f'], ['Cl', '#2563eb'], ['Ni', '#be123c'], ['Si', '#a16207']];
    // The severe wind: each streak's height (%), length (%), thickness (px), speed and start; then the leaves it tears past.
    $hbGust = [];
    for ($i = 0; $i < 18; $i++) {
        $hbGust[] = [6 + ($i * 37) % 88, 18 + ($i * 11) % 22, 1 + $i % 3, round(.55 + (($i * 13) % 35) / 100, 2), round((($i * 29) % 90) / 100, 2)];
    }
    $hbLeaves = [[18, 1.7, 0, 1.3], [36, 2.1, .5, 1], [54, 1.8, 1.1, 1.45], [70, 2.3, .3, 1.1], [82, 1.9, .8, 1.25], [44, 2.2, 1.5, .95]];
    // The rain on the storm's side: where each streak falls (%), how long and thick it is, how fast it falls, and when it
    // starts.
    $hbRain = [];
    for ($i = 0; $i < 60; $i++) {
        $hbRain[] = [42 + ($i * 37) % 59, 6 + ($i * 7) % 6, 1.2 + ($i % 3) * .4, round(.38 + (($i * 13) % 30) / 100, 2), round((($i * 17) % 70) / 100, 2)];
    }
@endphp
<div class="hb" data-hb role="img" aria-label="Anee, your smart farm technician, and what she brings: higher yield, pest and disease protection, complete nutrition, anti lodging and weather smart timing">
    <svg class="hb-defs" width="0" height="0" aria-hidden="true" focusable="false"><symbol id="hbSack" viewBox="0 0 64 28"><path d="M5 7Q3 2 8 3Q32 0 56 3Q61 2 59 7Q62 14 59 21Q61 26 56 25Q32 28 8 25Q3 26 5 21Q2 14 5 7Z" style="fill: var(--skb); stroke: var(--sko, none); stroke-width: 1; stroke-dasharray: 3 2"/><path d="M8 5Q32 2 56 5Q57.5 8.5 55 10.5Q32 8 9 10.5Q6.5 8.5 8 5Z" fill="#fff" style="opacity: var(--skh, .34)"/><path d="M6.5 20Q32 24.5 57.5 20Q58.5 23.5 55 24.3Q32 27 9 24.3Q5.5 23.5 6.5 20Z" fill="#000" style="opacity: var(--sks, .16)"/><path d="M9 12.6H50M9 15.4H50" stroke="#fff" stroke-width=".5" style="stroke-opacity: var(--skw, .14)"/><path d="M18 9.4H43M18 18.6H43" stroke-width="1.3" style="stroke: var(--skp, transparent)"/><ellipse cx="30.5" cy="14" rx="6.5" ry="3" style="fill: var(--skp, transparent)"/><path d="M51.5 4Q53.6 14 51.5 24" fill="none" stroke="#000" stroke-width=".9" stroke-dasharray="1.6 1.3" style="stroke-opacity: var(--skm, .35)"/></symbol></svg>
    <div class="hb-stage">
        {{-- A field of ripe palay behind her (2026-10-29, the owner: "aside from anee's photo maybe we can add this photo as
             well ... you can mask this photo"): a round window with soft edges, slowly drifting, darker in the middle so
             her ring stands out. --}}
        <span class="hb-photo" aria-hidden="true"><img src="{{ asset('images/site/photos/golden-palay.webp') }}" srcset="{{ asset('images/site/photos/golden-palay-640.webp') }} 640w, {{ asset('images/site/photos/golden-palay.webp') }} 1080w" sizes="(min-width: 640px) 31rem, 23rem" alt="" width="1080" height="608" decoding="async"></span>
        <span class="hb-orbit" aria-hidden="true"></span>
        <span class="hb-orbit is-in" aria-hidden="true"></span>

        {{-- Higher yield: two piles of sacks; the usual way stops, Anee's keeps going and is not done yet. --}}
        <div class="hb-ph" data-ph="yield" aria-hidden="true">
            <div class="hb-pile is-them">
                <span class="hb-sacks">
                    @for ($i = 0; $i < 6; $i++)<svg class="hb-sack{{ $i === 5 ? ' is-part' : '' }}" viewBox="0 0 64 28" style="--k: {{ $i }}"><use href="#hbSack"/></svg>@endfor
                </span>
                <small>The usual way</small>
            </div>
            <div class="hb-pile is-anee">
                <span class="hb-pile-up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></span>
                <span class="hb-sacks">
                    @for ($i = 0; $i < 9; $i++)<svg class="hb-sack{{ $i === 6 || $i === 7 ? ' is-more' : '' }}{{ $i === 8 ? ' is-ghost' : '' }}" viewBox="0 0 64 28" style="--k: {{ $i }}"><use href="#hbSack"/></svg>@endfor
                </span>
                <small>With Anee</small>
            </div>
            <span class="hb-badge is-up">▲ And still climbing</span>
        </div>

        {{-- Pest protection: a shield draws itself, the pests bounce off it. --}}
        <div class="hb-ph" data-ph="pests" aria-hidden="true">
            <svg class="hb-shield" viewBox="0 0 100 100"><circle cx="50" cy="50" r="30" pathLength="100"/><circle class="b" cx="50" cy="50" r="33.5" pathLength="100"/></svg>
            @foreach ($hbBugs as $i => $b)
                <span class="hb-bug" style="--a: {{ 20 + $i * 72 }}deg; --k: {{ $i }}">{{ $b }}<i></i></span>
            @endforeach
            {{-- Protected, as a shield (the owner: "protected should be an animated shield icon"): it pops in, its edge
                 draws, it fills, the check draws, then it beats, rings and shines while the pests bounce off. --}}
            <span class="hb-shico">
                <svg viewBox="0 0 48 56">
                    <defs>
                        <linearGradient id="hbShG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6ee7b7"/><stop offset="1" stop-color="#047857"/></linearGradient>
                        <clipPath id="hbShC"><path d="M24 3L43 10V27C43 40 35 49 24 53C13 49 5 40 5 27V10Z"/></clipPath>
                    </defs>
                    <path class="f" d="M24 3L43 10V27C43 40 35 49 24 53C13 49 5 40 5 27V10Z" fill="url(#hbShG)"/>
                    <path class="f" d="M24 3L5 10V27C5 40 13 49 24 53Z" fill="#fff" opacity=".14"/>
                    <g clip-path="url(#hbShC)"><rect class="sh" x="-30" y="-12" width="12" height="84" fill="#fff" opacity=".5"/></g>
                    <path class="o" d="M24 3L43 10V27C43 40 35 49 24 53C13 49 5 40 5 27V10Z" fill="none" stroke="#d1fae5" stroke-width="2.2" stroke-linejoin="round" pathLength="100"/>
                    <path class="c" d="M15 28.5l6.5 6.5L33.5 22" fill="none" stroke="#fff" stroke-width="4.6" stroke-linecap="round" stroke-linejoin="round" pathLength="100"/>
                </svg>
                <i></i>
            </span>
        </div>

        {{-- Complete nutrition: each nutrient flies in, fills its own part of the ring, then feeds her. --}}
        <div class="hb-ph" data-ph="nutri" aria-hidden="true">
            <svg class="hb-nring" viewBox="0 0 100 100">
                <circle class="t" cx="50" cy="50" r="26"/>
                @foreach ($hbNut as $i => [$n, $c])
                    <circle class="s" cx="50" cy="50" r="26" pathLength="100" transform="rotate(-90 50 50)" style="stroke: {{ $c }}; stroke-dashoffset: {{ 4.75 - $i * 12.5 }}; --k: {{ $i }}"/>
                @endforeach
            </svg>
            @foreach ($hbNut as $i => [$n, $c])
                <i class="hb-feed" style="--a: {{ $i * 45 }}deg; --k: {{ $i }}; --c: {{ $c }}"></i>
            @endforeach
            @foreach ($hbNut as $i => [$n, $c])
                <span class="hb-nut" style="--a: {{ $i * 45 }}deg; --k: {{ $i }}; --c: {{ $c }}">{{ $n }}</span>
            @endforeach
            <div class="hb-micro">
                @foreach ($hbMicro as $i => [$n, $c])
                    <span class="hb-mic" style="--a: {{ round(22.5 + $i * 360 / count($hbMicro), 1) }}deg; --k: {{ $i }}; --c: {{ $c }}"><b>{{ $n }}</b></span>
                @endforeach
            </div>
        </div>

        {{-- Anti lodging: a severe wind (the owner: no palay drawings, no check, "make the windy more severe"): streaks of
             every length and speed, gusts sweeping across, leaves torn past, and Anee unmoved in the middle. --}}
        <div class="hb-ph" data-ph="lodge" aria-hidden="true">
            <span class="hb-gloom"></span>
            @foreach ($hbGust as [$y, $w, $h, $d, $k])
                <i class="hb-wind" style="--y: {{ $y }}%; --w: {{ $w }}%; --h: {{ $h }}px; --d: {{ $d }}s; --k: {{ $k }}s"></i>
            @endforeach
            <svg class="hb-swirl" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M-10 26C15 14 35 38 60 26S95 18 115 30" pathLength="100"/>
                <path d="M-10 52C20 40 38 64 66 52S100 44 115 56" pathLength="100"/>
                <path d="M-10 76C18 68 42 88 70 76S102 70 115 80" pathLength="100"/>
                <path d="M-10 40C22 30 44 52 72 40S104 34 115 44" pathLength="100"/>
            </svg>
            @foreach ($hbLeaves as [$y, $d, $k, $sz])
                <span class="hb-leaf" style="--y: {{ $y }}%; --d: {{ $d }}s; --k: {{ $k }}s; font-size: {{ $sz }}rem">🍃</span>
            @endforeach
        </div>

        {{-- Weather smart timing: the sun's rays on one side, strong rain on the other, the work on the dry morning. --}}
        <div class="hb-ph" data-ph="time" aria-hidden="true">
            <div class="hb-sky">
                <span class="hb-rays"></span>
                <span class="hb-sunglow"></span>
                <div class="hb-storm">
                    <span class="hb-clouds"></span>
                    <svg class="hb-bolt is-a" viewBox="0 0 40 100" preserveAspectRatio="none"><path d="M26 0L15 36L23 38L11 66L19 67L7 100"/><path class="br" d="M19 51L29 58L26 71"/></svg>
                    <svg class="hb-bolt is-b" viewBox="0 0 40 100" preserveAspectRatio="none"><path d="M21 0L12 31L20 32L9 60L17 61L6 88"/></svg>
                    @foreach ($hbRain as [$x, $h, $w, $d, $k])<i style="left: {{ $x }}%; width: {{ $w }}px; height: {{ $h }}cqw; --d: {{ $d }}s; --k: {{ $k }}s"></i>@endforeach
                    <span class="hb-flash"></span>
                    <span class="hb-flash is-b"></span>
                </div>
            </div>
        </div>

        {{-- Anee herself, as a short silent film (2026-10-29, the owner's: she smiles, winks, gets an idea under a
             lightbulb and points), cut square for the circle (public/videos/site/anee-idea.mp4, from y 350 of the 1280
             by 2274 original so the bulb shows at the top and her chin at the bottom). It plays while the hero is on
             screen; when motion is not welcome only its first frame shows. --}}
        @php
            $hbFilm = fn (string $f) => asset('videos/site/' . $f) . (is_file(public_path('videos/site/' . $f)) ? '?v=' . filemtime(public_path('videos/site/' . $f)) : '');
        @endphp
        <div class="hb-core">
            <span class="hb-halo" aria-hidden="true"></span>
            <video class="hb-anee" muted loop playsinline preload="auto" poster="{{ $hbFilm('anee-idea.webp') }}" width="480" height="480" aria-hidden="true">
                <source src="{{ $hbFilm('anee-idea.mp4') }}" type="video/mp4">
            </video>
        </div>
    </div>
    <div class="hb-cards">
        @foreach ($hbPhases as $i => $p)
            <div class="hb-card {{ $i === 0 ? 'is-on' : '' }}" data-card="{{ $p['k'] }}" style="--c: {{ $p['c'] }}; --cg: {{ $p['g'] }}">
                <span class="hb-card-i" aria-hidden="true">{{ $p['e'] }}</span>
                <span class="hb-card-t">
                    <span class="hb-card-k">{{ $p['kick'] }}</span>
                    <b>{!! $p['h'] !!}</b>
                    <small>{{ $p['w'] }}</small>
                </span>
                <span class="hb-card-s">
                    @if ($p['pre'])<i>{{ $p['pre'] }}</i>@endif
                    <b class="{{ preg_match('/\d/', $p['n']) ? '' : 'is-word' }}">{{ $p['n'] }}</b>
                    <small>{{ $p['u'] }}</small>
                </span>
                <i class="hb-card-bar" aria-hidden="true"></i>
            </div>
        @endforeach
    </div>
</div>

@once
@push('head')
<style>
    /* ---- the hero: Anee and her benefits, one at a time (2026-10-29) ---- */
    .hb { position: relative; z-index: 1; width: min(100%, 31rem); transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .hb-defs { position: absolute; width: 0; height: 0; overflow: hidden; }
    .hb-stage { position: relative; aspect-ratio: 1; container-type: inline-size; }
    .hb-stage::before { content: ''; position: absolute; inset: 3%; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(8 16 6 / .62), rgb(8 16 6 / .38) 70%, rgb(8 16 6 / 0)); }
    .hb-photo { position: absolute; inset: 4%; border-radius: 999px; overflow: hidden; pointer-events: none;
        -webkit-mask: radial-gradient(closest-side, #000 70%, rgb(0 0 0 / .6) 86%, transparent 100%); mask: radial-gradient(closest-side, #000 70%, rgb(0 0 0 / .6) 86%, transparent 100%); }
    .hb-photo img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: 60% 58%; transform: scale(1.12); animation: hbDrift 26s ease-in-out infinite alternate; }
    @keyframes hbDrift { from { transform: scale(1.12) translate(2%, 1%); } to { transform: scale(1.24) translate(-3%, -2%); } }
    .hb-photo::after { content: ''; position: absolute; inset: 0;
        background: radial-gradient(closest-side, rgb(6 12 4 / .78) 0 33%, rgb(6 12 4 / .36) 62%, rgb(6 12 4 / .5) 100%); }
    .hb-orbit { position: absolute; inset: 9%; border-radius: 999px; border: 1px dashed rgb(255 255 255 / .2); animation: hbSpin 70s linear infinite; }
    .hb-orbit.is-in { inset: 24%; border: 1px solid rgb(168 204 126 / .18); animation-direction: reverse; animation-duration: 50s; }
    @keyframes hbSpin { to { transform: rotate(360deg); } }
    /* Anee, always in the middle. */
    .hb-core { position: absolute; left: 50%; top: 50%; z-index: 5; width: 36%; aspect-ratio: 1; transform: translate(-50%, -50%); }
    .hb-halo { position: absolute; inset: -12%; border-radius: 999px; opacity: .8; filter: blur(10px); animation: hbSpin 6s linear infinite;
        background: conic-gradient(from 0deg, rgb(245 197 24 / 0), rgb(245 197 24 / .75), rgb(163 230 53 / .55), rgb(245 197 24 / 0) 70%); }
    .hb-core .hb-anee { position: relative; display: block; width: 100%; height: 100%; border-radius: 999px; object-fit: cover; background: #a9c79a;
        box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f5c518, 0 30px 60px -20px rgb(0 0 0 / .8); }
    /* A benefit's layer: seen while its turn lasts. Each element rests in its finished state; .is-play runs it in. */
    .hb-ph { position: absolute; inset: 0; opacity: 0; transition: opacity .45s ease; pointer-events: none; }
    .hb-ph.is-on { opacity: 1; }
    .hb-badge { position: absolute; z-index: 6; left: 50%; top: 4%; transform: translateX(-50%); padding: .35rem .8rem; border-radius: 999px; white-space: nowrap;
        font-size: .8rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -10px rgb(0 0 0 / .7); }
    .hb-ph.is-play .hb-badge { animation: hbPop .6s cubic-bezier(.22,1,.36,1) 2.2s both; }
    @keyframes hbPop { from { opacity: 0; transform: translateX(-50%) translateY(10px) scale(.8); } }
    @keyframes hbFade { from { opacity: 0; } }
    /* Yield: two piles of rice sacks lying down, either side of her, the same width and the same distance from her. The
       usual way stops at about 5.4 t; Anee's goes on to 8, its last two the brightest, and a faded sack on top says the
       pile is not done. */
    .hb-pile { position: absolute; z-index: 4; bottom: 12cqw; display: grid; grid-template-columns: 16cqw; justify-items: center; gap: 1.4cqw; }
    .hb-pile.is-them { left: 5cqw; }
    .hb-pile.is-anee { right: 5cqw; }
    .hb-pile-up { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px; color: #14210c; background: #f5c518;
        box-shadow: 0 0 0 5px rgb(245 197 24 / .25), 0 0 22px rgb(245 197 24 / .6); }
    .hb-pile-up svg { width: 1.3rem; height: 1.3rem; }
    .hb-ph.is-play .hb-pile-up { animation: hbPopN .5s cubic-bezier(.22,1,.36,1) 1.9s both, hbUp 1.1s ease-in-out 2.5s infinite; }
    @keyframes hbUp { 50% { transform: translateY(-5px); } }
    .hb-sacks { display: flex; flex-direction: column-reverse; align-items: center; width: 100%; }
    .hb-sack { display: block; width: 100%; height: auto; aspect-ratio: 64 / 28; overflow: visible; --skb: #e4e0d2; --skp: #9aa88c; filter: drop-shadow(0 2px 2px rgb(0 0 0 / .4)); }
    .hb-sack + .hb-sack { margin-bottom: -1.7cqw; }
    .hb-sack:nth-child(4n + 2) { translate: .8cqw 0; }
    .hb-sack:nth-child(4n) { translate: -.7cqw 0; }
    .hb-sack.is-part { width: 58%; }
    .hb-pile.is-anee .hb-sack { --skb: #efc12f; --skp: #3d6823; }
    .hb-pile.is-anee .hb-sack.is-more { --skb: #f8d54e; filter: drop-shadow(0 0 7px rgb(245 197 24 / .75)); }
    .hb-pile.is-anee .hb-sack.is-ghost { --skb: rgb(245 197 24 / .26); --sko: #fde68a; --skp: transparent; --skh: 0; --sks: 0; --skw: 0; --skm: 0; filter: drop-shadow(0 0 6px rgb(245 197 24 / .5)); }
    .hb-ph.is-play .hb-sack { animation: hbSack .45s cubic-bezier(.34,1.56,.64,1) calc(.3s + var(--k) * .2s) both; }
    .hb-ph.is-play .hb-sack.is-ghost { animation: hbSack .45s cubic-bezier(.34,1.56,.64,1) 1.9s both, hbGhost 1.4s ease-in-out 2.4s infinite; }
    @keyframes hbSack { from { opacity: 0; transform: translateY(-7cqw) scale(.8); } }
    @keyframes hbGhost { 50% { opacity: .4; } }
    .hb-pile small { padding: .22rem .6rem; border-radius: 999px; white-space: nowrap; font-size: .72rem; font-weight: 800; color: #fff; background: rgb(10 14 7 / .72); }
    .hb-pile.is-anee small { color: #14210c; background: #fff6d6; }
    @keyframes hbPopN { from { opacity: 0; transform: scale(.7); } }
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
    /* The shield icon on top: pops in, its edge draws, it fills, the check draws; then it beats, rings and shines. */
    .hb-shico { position: absolute; z-index: 6; left: 50%; top: 1.5%; width: 12cqw; aspect-ratio: 48 / 56; transform: translateX(-50%);
        filter: drop-shadow(0 0 10px rgb(52 211 153 / .7)) drop-shadow(0 8px 14px rgb(0 0 0 / .5)); }
    .hb-shico svg { display: block; width: 100%; height: 100%; overflow: visible; }
    .hb-shico .o, .hb-shico .c { stroke-dasharray: 100; }
    .hb-shico i { position: absolute; inset: -12%; border-radius: 42% 42% 50% 50%; border: 2px solid #34d399; opacity: 0; }
    .hb-ph.is-play .hb-shico { animation: hbShPop .7s cubic-bezier(.34,1.56,.64,1) .2s both, hbShBeat 1.6s ease-in-out 2s infinite; }
    .hb-ph.is-play .hb-shico .o { animation: hbShDraw .9s ease-out .3s both; }
    .hb-ph.is-play .hb-shico .f { animation: hbFade .5s ease-out .9s both; }
    .hb-ph.is-play .hb-shico .c { animation: hbShDraw .45s ease-out 1.3s both; }
    .hb-ph.is-play .hb-shico .sh { animation: hbShine 2.4s ease-in-out 1.6s infinite; }
    .hb-ph.is-play .hb-shico i { animation: hbShRing 1.6s ease-out 2s infinite; }
    @keyframes hbShPop { from { opacity: 0; transform: translateX(-50%) scale(.3) rotate(-15deg); } }
    @keyframes hbShBeat { 0%, 30%, 100% { scale: 1; } 12% { scale: 1.09; } }
    @keyframes hbShDraw { from { stroke-dashoffset: 100; } }
    @keyframes hbShine { 0% { transform: translateX(-10px) rotate(20deg); } 45%, 100% { transform: translateX(78px) rotate(20deg); } }
    @keyframes hbShRing { 0% { opacity: .8; transform: scale(.85); } 100% { opacity: 0; transform: scale(1.55); } }
    /* Nutrition: each nutrient in its own color flies in and fills its own part of the ring, then beats and sends a drop
       in to her. */
    .hb-nring { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; filter: drop-shadow(0 0 5px rgb(255 255 255 / .18)); }
    .hb-nring circle { fill: none; stroke-width: 3.4; }
    .hb-nring .t { stroke: rgb(255 255 255 / .1); }
    .hb-nring .s { stroke-linecap: round; stroke-dasharray: 9.5 90.5; }
    .hb-ph.is-play .hb-nring .s { animation: hbSeg .55s cubic-bezier(.22,1,.36,1) calc(.8s + var(--k) * .12s) both; }
    @keyframes hbSeg { from { stroke-dasharray: 0 100; } }
    .hb-feed { position: absolute; left: 50%; top: 50%; width: .5rem; height: .5rem; margin: -.25rem 0 0 -.25rem; border-radius: 999px; opacity: 0;
        background: var(--c); box-shadow: 0 0 10px var(--c); transform: rotate(var(--a)) translateY(-31cqw); }
    .hb-ph.is-play .hb-feed { animation: hbFeed 2.2s ease-in calc(1.8s + var(--k) * .17s) infinite; }
    @keyframes hbFeed { 0% { opacity: 0; transform: rotate(var(--a)) translateY(-31cqw); } 8% { opacity: 1; } 45%, 100% { opacity: 0; transform: rotate(var(--a)) translateY(-19cqw) scale(.4); } }
    /* Each nutrient a flat, see through orb in its own color: a tinted glass disc with a bright edge, a dashed ring with a
       dot turning round it, and a gentle float. */
    .hb-nut { position: absolute; left: 50%; top: 50%; width: 2.6rem; height: 2.6rem; margin: -1.3rem 0 0 -1.3rem; display: grid; place-items: center; border-radius: 999px;
        font-size: .88rem; font-weight: 900; color: #fff; text-shadow: 0 1px 3px rgb(0 0 0 / .35);
        background: color-mix(in srgb, var(--c) 42%, transparent); border: 1.5px solid color-mix(in srgb, var(--c) 70%, #fff);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: 0 0 16px color-mix(in srgb, var(--c) 45%, transparent);
        transform: rotate(var(--a)) translateY(-35cqw) rotate(calc(var(--a) * -1)); }
    .hb-nut::before { content: ''; position: absolute; inset: -7px; border-radius: 999px; border: 1px dashed color-mix(in srgb, var(--c) 70%, #fff); opacity: .75;
        background: radial-gradient(circle at 50% 0, color-mix(in srgb, var(--c) 60%, #fff) 0 2.5px, transparent 3px); }
    .hb-ph.is-play .hb-nut { animation: hbIn .7s cubic-bezier(.22,1,.36,1) calc(.15s + var(--k) * .12s) both, hbFloat 2.6s ease-in-out calc(1s + var(--k) * .2s) infinite; }
    .hb-ph.is-play .hb-nut::before { animation: hbSpin calc(4s + var(--k) * .35s) linear infinite; }
    .hb-ph.is-play .hb-nut:nth-of-type(even)::before { animation-direction: reverse; }
    @keyframes hbIn { from { opacity: 0; transform: rotate(calc(var(--a) - 50deg)) translateY(-56cqw) rotate(calc((var(--a) - 50deg) * -1)) scale(.5); } }
    @keyframes hbFloat { 50% { translate: 0 -5px; } }
    /* The micronutrients: smaller see through orbs on an outer ring that turns; each letter stays upright. */
    .hb-micro { position: absolute; inset: 0; }
    .hb-ph.is-play .hb-micro { animation: hbSpin 38s linear infinite; }
    .hb-mic { position: absolute; left: 50%; top: 50%; width: 1.7rem; height: 1.7rem; margin: -.85rem 0 0 -.85rem;
        transform: rotate(var(--a)) translateY(-45cqw) rotate(calc(var(--a) * -1)); }
    .hb-mic b { display: grid; place-items: center; width: 100%; height: 100%; border-radius: 999px; font-size: .62rem; font-weight: 900; color: #fff;
        background: color-mix(in srgb, var(--c) 40%, transparent); border: 1px solid color-mix(in srgb, var(--c) 70%, #fff);
        backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); box-shadow: 0 0 10px color-mix(in srgb, var(--c) 40%, transparent); }
    .hb-ph.is-play .hb-mic { animation: hbMicIn .6s cubic-bezier(.22,1,.36,1) calc(1.2s + var(--k) * .1s) both; }
    .hb-ph.is-play .hb-mic b { animation: hbSpin 38s linear infinite reverse; }
    @keyframes hbMicIn { from { opacity: 0; scale: .2; } }
    /* Lodging: a severe wind. The sky darkens, streaks of every length and speed race across, gusts sweep through in
       curves, and leaves are torn past her. */
    .hb-gloom { position: absolute; inset: -12%; background: radial-gradient(closest-side, rgb(15 23 42 / .55), rgb(15 23 42 / .4) 55%, rgb(15 23 42 / 0)); }
    .hb-ph.is-play .hb-gloom { animation: hbFade .8s ease-out both; }
    .hb-wind { position: absolute; z-index: 3; left: -35%; top: var(--y); width: var(--w); height: var(--h); border-radius: 999px; opacity: 0;
        background: linear-gradient(90deg, transparent, rgb(224 242 254 / .95), transparent); }
    .hb-ph.is-play .hb-wind { animation: hbWind var(--d) cubic-bezier(.4,0,.7,1) var(--k) infinite; }
    @keyframes hbWind { 0% { opacity: 0; transform: translateX(0); } 15% { opacity: 1; } 100% { opacity: 0; transform: translateX(170cqw); } }
    .hb-swirl { position: absolute; inset: 0; z-index: 3; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
    .hb-swirl path { fill: none; stroke: rgb(224 242 254 / .8); stroke-width: 2.2; stroke-linecap: round; vector-effect: non-scaling-stroke;
        stroke-dasharray: 26 130; stroke-dashoffset: 26; }
    .hb-ph.is-play .hb-swirl path { animation: hbGust 1.2s cubic-bezier(.45,0,.55,1) infinite; }
    .hb-ph.is-play .hb-swirl path:nth-child(2) { animation-delay: .3s; }
    .hb-ph.is-play .hb-swirl path:nth-child(3) { animation-delay: .6s; }
    .hb-ph.is-play .hb-swirl path:nth-child(4) { animation-delay: .9s; }
    @keyframes hbGust { to { stroke-dashoffset: -104; } }
    .hb-leaf { position: absolute; z-index: 4; left: -12%; top: var(--y); line-height: 1; opacity: 0; }
    .hb-ph.is-play .hb-leaf { animation: hbLeaf var(--d) linear var(--k) infinite; }
    @keyframes hbLeaf { 0% { opacity: 0; transform: translate(0, 0) rotate(0); } 10% { opacity: 1; } 50% { transform: translate(60cqw, -6cqw) rotate(300deg); }
        90% { opacity: 1; } 100% { opacity: 0; transform: translate(125cqw, 4cqw) rotate(640deg); } }
    /* Timing: the sun's rays on one side, strong rain with lightning on the other. No hard edge: the whole sky reaches
       past the circle and fades out into the hero (the owner: "i don't like that it is enclosed in circle"). The sun
       sits where it did, about a quarter in and a quarter down. */
    .hb-sky { position: absolute; inset: -14%; pointer-events: none;
        -webkit-mask: radial-gradient(closest-side, #000 50%, rgb(0 0 0 / .55) 74%, transparent 100%); mask: radial-gradient(closest-side, #000 50%, rgb(0 0 0 / .55) 74%, transparent 100%); }
    .hb-rays { position: absolute; left: -58.75%; top: -57.2%; width: 180%; height: 180%;
        background: repeating-conic-gradient(from 0deg, rgb(255 238 170 / .34) 0deg 5deg, rgb(255 238 170 / 0) 5deg 14deg);
        -webkit-mask: radial-gradient(closest-side, #000 5%, rgb(0 0 0 / .55) 28%, transparent 60%); mask: radial-gradient(closest-side, #000 5%, rgb(0 0 0 / .55) 28%, transparent 60%); }
    .hb-ph.is-play .hb-rays { animation: hbRaysIn 1s cubic-bezier(.22,1,.36,1) both, hbSpin 36s linear infinite; }
    @keyframes hbRaysIn { from { opacity: 0; scale: .4; } }
    .hb-sunglow { position: absolute; inset: 0;
        background: radial-gradient(circle at 31.25% 32.8%, rgb(255 251 225 / .95) 0 3.3%, rgb(253 224 71 / .7) 5.8%, rgb(251 146 60 / .28) 16%, rgb(251 146 60 / 0) 35%); }
    .hb-ph.is-play .hb-sunglow { animation: hbFade .9s ease-out both; }
    .hb-storm { position: absolute; inset: 0; background: linear-gradient(180deg, rgb(15 23 42 / .96), rgb(30 41 59 / .85) 45%, rgb(51 65 85 / .55));
        -webkit-mask: linear-gradient(100deg, transparent 44%, #000 60%); mask: linear-gradient(100deg, transparent 44%, #000 60%); }
    .hb-ph.is-play .hb-storm { animation: hbStormIn 1s cubic-bezier(.22,1,.36,1) .6s both; }
    @keyframes hbStormIn { from { opacity: 0; transform: translateX(30%); } }
    .hb-clouds { position: absolute; left: 38%; right: -12%; top: -8%; height: 38%;
        background: radial-gradient(closest-side, rgb(71 85 105 / .95), rgb(71 85 105 / 0)) 10% 70% / 46% 70% no-repeat,
            radial-gradient(closest-side, rgb(51 65 85 / .95), rgb(51 65 85 / 0)) 55% 40% / 55% 85% no-repeat,
            radial-gradient(closest-side, rgb(30 41 59 / .95), rgb(30 41 59 / 0)) 95% 60% / 50% 75% no-repeat; }
    .hb-storm i { position: absolute; top: -14%; border-radius: 2px; opacity: 0; rotate: 14deg;
        background: linear-gradient(rgb(219 234 254 / 0), rgb(219 234 254 / .9)); }
    .hb-ph.is-play .hb-storm i { animation: hbFall var(--d) linear calc(.7s + var(--k)) infinite; }
    @keyframes hbFall { 0% { opacity: 0; transform: translateY(0); } 12% { opacity: 1; } 100% { opacity: .8; transform: translateY(140cqw); } }
    /* Lightning: two bolts out of the clouds, each with its own flash of light round it, at their own times. */
    .hb-bolt { position: absolute; overflow: visible; opacity: 0; fill: none; stroke: #f8fafc; stroke-width: 1.8; stroke-linejoin: round; stroke-linecap: round;
        filter: drop-shadow(0 0 3px #e0f2fe) drop-shadow(0 0 10px #93c5fd) drop-shadow(0 0 22px rgb(96 165 250 / .8)); }
    .hb-bolt .br { stroke-width: 1.1; }
    .hb-bolt.is-a { left: 69%; top: 15%; width: 9%; height: 34%; }
    .hb-bolt.is-b { left: 83%; top: 21%; width: 6%; height: 24%; }
    .hb-ph.is-play .hb-bolt.is-a { animation: hbBolt 2.6s linear 1.8s infinite; }
    .hb-ph.is-play .hb-bolt.is-b { animation: hbBolt 3.4s linear 2.9s infinite; }
    @keyframes hbBolt { 0%, 10%, 100% { opacity: 0; } 1.5% { opacity: 1; } 3.5% { opacity: .15; } 5.5% { opacity: .95; } 8% { opacity: .3; } }
    .hb-flash { position: absolute; inset: 0; opacity: 0; background: radial-gradient(circle at 73% 26%, rgb(226 232 240 / .85), rgb(226 232 240 / 0) 55%); }
    .hb-flash.is-b { background: radial-gradient(circle at 86% 30%, rgb(226 232 240 / .7), rgb(226 232 240 / 0) 45%); }
    .hb-ph.is-play .hb-flash { animation: hbFlash 2.6s linear 1.8s infinite; }
    .hb-ph.is-play .hb-flash.is-b { animation: hbFlash 3.4s linear 2.9s infinite; }
    @keyframes hbFlash { 0%, 9%, 100% { opacity: 0; } 2% { opacity: .6; } 4% { opacity: .1; } 6% { opacity: .4; } }
    /* The benefit's card under her, in its own color: name, heading, line, a number, and a bar that fills while its turn
       lasts. All five share one grid cell, so the cards' row is as tall as the tallest. */
    .hb-cards { position: relative; display: grid; margin-top: -.6rem; }
    .hb-card { grid-area: 1 / 1; position: relative; display: flex; align-items: center; gap: .9rem; padding: .9rem 1rem 1rem .9rem; border-radius: 1.35rem; overflow: hidden; color: #fff;
        background: linear-gradient(135deg, rgb(26 40 18 / .95), rgb(9 16 7 / .95)); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1), inset 0 1px 0 rgb(255 255 255 / .14), 0 26px 50px -22px rgb(0 0 0 / .9), 0 0 44px -20px var(--c);
        opacity: 0; visibility: hidden; transform: translateY(14px) scale(.97); transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s; }
    .hb-card.is-on { opacity: 1; visibility: visible; transform: none;
        transition: opacity .5s cubic-bezier(.22,1,.36,1) .15s, transform .5s cubic-bezier(.22,1,.36,1) .15s, visibility 0s; }
    .hb-card::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--c), transparent); }
    .hb-card::after { content: ''; position: absolute; left: -3.5rem; top: -3.5rem; width: 10rem; height: 10rem; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, var(--cg), transparent); }
    .hb-card-i { position: relative; z-index: 1; flex: none; display: grid; place-items: center; width: 3.1rem; height: 3.1rem; border-radius: 1rem; font-size: 1.55rem;
        background: linear-gradient(145deg, var(--cg), rgb(255 255 255 / .05)); box-shadow: inset 0 0 0 1.5px var(--c), 0 0 22px -6px var(--c); }
    .hb-card.is-on .hb-card-i { animation: hbIcon .7s cubic-bezier(.34,1.56,.64,1) .25s both; }
    @keyframes hbIcon { from { opacity: 0; transform: scale(.5) rotate(-20deg); } }
    .hb-card-t { position: relative; z-index: 1; min-width: 0; flex: 1; }
    .hb-card-k { display: block; font-size: .66rem; font-weight: 900; letter-spacing: .12em; line-height: 1.3; text-transform: uppercase; color: var(--c); }
    .hb-card-t b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: 1.14rem; font-weight: 800; line-height: 1.2; letter-spacing: -.01em; }
    .hb-card-t b em { font-style: normal; color: var(--c); }
    .hb-card-t small { display: block; margin-top: .25rem; font-size: .79rem; line-height: 1.35; color: rgb(255 255 255 / .7); }
    .hb-card.is-on .hb-card-k, .hb-card.is-on .hb-card-t b, .hb-card.is-on .hb-card-t small { animation: hbLine .55s cubic-bezier(.22,1,.36,1) both; }
    .hb-card.is-on .hb-card-k { animation-delay: .25s; } .hb-card.is-on .hb-card-t b { animation-delay: .35s; } .hb-card.is-on .hb-card-t small { animation-delay: .45s; }
    @keyframes hbLine { from { opacity: 0; transform: translateX(-10px); } }
    .hb-card-s { position: relative; z-index: 1; flex: none; display: grid; justify-items: center; max-width: 6.2rem; padding-left: .95rem; text-align: center;
        border-left: 1px solid rgb(255 255 255 / .12); }
    .hb-card-s i { font-style: normal; font-size: .6rem; font-weight: 800; letter-spacing: .06em; line-height: 1.2; text-transform: uppercase; color: rgb(255 255 255 / .6); }
    .hb-card-s b { margin-top: .1rem; white-space: nowrap; font-family: var(--font-heading); font-size: 1.6rem; font-weight: 900; line-height: 1; color: var(--c);
        text-shadow: 0 0 18px var(--cg); }
    .hb-card-s b.is-word { font-size: 1.25rem; }
    .hb-card-s small { margin-top: .2rem; font-size: .6rem; font-weight: 800; letter-spacing: .06em; line-height: 1.2; text-transform: uppercase; color: rgb(255 255 255 / .6); }
    .hb-card.is-on .hb-card-s { animation: hbPopN .55s cubic-bezier(.34,1.56,.64,1) .5s both; }
    .hb-card-bar { position: absolute; left: 0; bottom: 0; width: 100%; height: 3px; transform: scaleX(0); transform-origin: 0 50%;
        background: linear-gradient(90deg, var(--c), rgb(255 255 255 / .85)); }
    .hb-card.is-on .hb-card-bar { animation: hbBar 4.4s linear both; }
    @keyframes hbBar { to { transform: scaleX(1); } }
    @media (max-width: 639.98px) {
        .hb { width: min(100%, 23rem); }
        .hb-badge { font-size: .7rem; padding: .28rem .65rem; }
        .hb-bug { font-size: 1.15rem; }
        .hb-nut { width: 2.05rem; height: 2.05rem; margin: -1.025rem 0 0 -1.025rem; font-size: .72rem; }
        .hb-pile-up { width: 1.8rem; height: 1.8rem; } .hb-pile-up svg { width: 1rem; height: 1rem; }
        .hb-mic { width: 1.35rem; height: 1.35rem; margin: -.675rem 0 0 -.675rem; } .hb-mic b { font-size: .52rem; }
        .hb-pile small { font-size: .6rem; padding: .18rem .45rem; }
        .hb-card { gap: .65rem; padding: .75rem .8rem .85rem .7rem; border-radius: 1.15rem; }
        .hb-card-i { width: 2.5rem; height: 2.5rem; border-radius: .8rem; font-size: 1.25rem; }
        .hb-card-k { font-size: .56rem; letter-spacing: .08em; }
        .hb-card-t b { font-size: .95rem; } .hb-card-t small { font-size: .7rem; }
        .hb-card-s { max-width: 4.6rem; padding-left: .65rem; } .hb-card-s b { font-size: 1.2rem; } .hb-card-s b.is-word { font-size: .98rem; } .hb-card-s i, .hb-card-s small { font-size: .5rem; letter-spacing: .03em; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hb, .hb-photo img, .hb-orbit, .hb-halo, .hb-ph, .hb-ph *, .hb-card, .hb-card * { animation: none !important; transition: none !important; }
        .hb-card-bar { display: none; }
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
    const phases = [...hb.querySelectorAll('.hb-ph')], cards = [...hb.querySelectorAll('.hb-card')];
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const STAY = 4400;
    let n = 0, timer = 0, live = false;
    // A number that counts up to its own value (data-to, data-dp places) over data-ms.
    const count = (p) => p.querySelectorAll('[data-to]').forEach((el) => {
        const to = +el.dataset.to, dp = +(el.dataset.dp || 0), ms = +(el.dataset.ms || 1500), t0 = performance.now();
        const tick = (t) => { const k = Math.min(1, (t - t0) / ms); el.textContent = (to * (1 - Math.pow(1 - k, 3))).toFixed(dp); if (k < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
    });
    const show = (i) => {
        phases.forEach((p, j) => { p.classList.toggle('is-on', j === i); if (j !== i) p.classList.remove('is-play'); });
        cards.forEach((c, j) => { c.classList.remove('is-on'); if (j === i) { void c.offsetWidth; c.classList.add('is-on'); } });
        const p = phases[i];
        p.classList.remove('is-play'); void p.offsetWidth;
        if (!calm) { p.classList.add('is-play'); count(p); }
    };
    const step = () => { if (!live) return; show(n); n = (n + 1) % phases.length; timer = setTimeout(step, STAY); };
    if (calm) { show(0); return; }
    show(0);
    // Anee's film plays along with the benefits, and rests with them.
    const film = hb.querySelector('.hb-anee');
    const run = (on) => {
        if (on === live) return;
        live = on; clearTimeout(timer);
        if (on) { step(); film && film.play().catch(() => {}); } else if (film) film.pause();
    };
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => run(e.isIntersecting)), { threshold: .2 }).observe(hb);
    else run(true);
})();
</script>
@endpush
@endonce
