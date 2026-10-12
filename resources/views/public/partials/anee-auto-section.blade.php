{{-- ANEE AUTO on the homepage (2026-10-12, the owner): a section of its own,
     for the farmer who does not want to build schedules and run analyses:
     answer once, then just follow what Anee says. How it works in five steps,
     a real looking phone playing her day in a loop, a neural network at work
     on both sides of it (2026-10-13, the owner: the faces gave way to "a
     modern animation of AI deep thinking"): what she reads lights up, and
     pulses run through the layers into the phone. Then the door to sign up.
     No prices here. Expects $signup.

     The ads landing page (/start, 2026-10-13) shows it too: there it passes
     $aaLook 'site' (the site's heading classes, which /start dresses) and
     $aaText, the mother app's words for the kicker, headline (*stars* mark
     the green words), sub and button; any left blank keep these. --}}
@php
    $aaE = fn (string $n) => \App\Support\AneeEmoji::url($n);
    $aaSite = ($aaLook ?? 'home') === 'site';
    $aaSay = fn (string $k, string $own) => trim((string) (($aaText ?? [])[$k] ?? '')) !== '' ? trim((string) $aaText[$k]) : $own;
    // What she reads, the network's inputs: left side, then right side.
    $aaInputs = [['Forecast', 'El Niño', 'Your soil', 'Variety'], ['Crop stage', 'Fertilizer', 'Workers', 'Typhoons']];
@endphp
<section class="{{ $aaSite ? 'hp-auto is-site' : 'hp-sec hp-auto' }}" id="anee-auto" aria-labelledby="hpAutoH">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="hp-head reveal">
            <p class="{{ $aaSite ? 'fx-kicker' : 'hp-kick' }}">{{ $aaSay('kicker', 'The Easiest Way to Precision Farming') }}</p>
            <h2 class="{{ $aaSite ? 'fx-h' : 'hp-h2' }}" id="hpAutoH">{!! \App\Support\LandingPage::marked($aaSay('headline', 'Do Not Want Farming to Get Complicated? *Just Follow Anee.*')) !!}</h2>
            <p class="{{ $aaSite ? 'fx-p' : 'hp-p' }}">{{ $aaSay('sub', 'Building schedules, running analyses, checking the weather every morning, timing the fertilizer: not everyone has the time for it, and nobody should have to guess. With Anee Auto you answer a few questions about your farm once. Anee works out the whole season, tells you what to do each day, and changes the plan herself when the rain comes or a worker cannot.') }}</p>
            <ul class="hp-auto-skip" aria-label="What you no longer have to do">
                <li>No schedules to build</li>
                <li>No analyses to run one by one</li>
                <li>No guessing the weather</li>
                <li>No missed fertilizer days</li>
                <li>No guessing what to spray</li>
            </ul>
        </div>

        <div class="hp-auto-grid">
            <ol class="hp-auto-steps reveal">
                <li style="--k:0"><span class="hp-auto-n">1</span><div><b>Tell Anee about your farm, once</b><p>Your lots and where they are, the crop and variety, the planting day, what your soil looks like, where your water comes from, your workers and the problems of past seasons. Simple choices, no forms to puzzle over.</p></div></li>
                <li style="--k:1"><span class="hp-auto-n">2</span><div><b>She analyzes everything</b><p>She looks up your variety, reads El Niño or La Niña and the forecast for your town, reads your soil from its signs and the place, and checks your fertilizer against what your crop needs for a realistic harvest.</p></div></li>
                <li style="--k:2"><span class="hp-auto-n">3</span><div><b>She plans the whole season</b><p>Land preparation, the seedbed, the water, every fertilizer at the moment the crop needs it most, prevention before pests and diseases come, with the active ingredient, the safety and what not to mix, all the way to harvest.</p></div></li>
                <li style="--k:3"><span class="hp-auto-n">4</span><div><b>You just follow, day by day</b><p>Open the app and see today's work and this week's, simple, with the reason one tap away. Each morning the plan also goes by email to you and to each of your workers.</p></div></li>
                <li style="--k:4"><span class="hp-auto-n">5</span><div><b>She keeps it on track</b><p>Rain coming, no workers, no budget yet? Tap Can't today and she moves the work to the best day near it. Send a photo, a video or a voice note from the field, and she reads it and adjusts the plan.</p></div></li>
            </ol>

            {{-- The stage: her faces rising behind a phone that plays her day. --}}
            <div class="hp-auto-stage reveal" aria-hidden="true">
                <div class="hp-auto-glow"></div>
                {{-- Drawn by the script to fit the stage; under reduced motion it stays still. --}}
                <svg class="hp-auto-net" data-auto-net data-inputs='@json($aaInputs)'></svg>
                <span class="hp-auto-ring"></span><span class="hp-auto-ring is-2"></span>
                <div class="hp-auto-phone">
                    <i class="hp-auto-key is-power"></i><i class="hp-auto-key is-up"></i><i class="hp-auto-key is-down"></i>
                    <div class="hp-auto-screen">
                        <div class="hp-auto-status">
                            <b>6:00</b><span class="hp-auto-island"></span>
                            <span class="hp-auto-sig"><svg viewBox="0 0 18 12"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5" width="3" height="7" rx="1"/><rect x="10" y="2.5" width="3" height="9.5" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1" opacity=".35"/></svg><svg viewBox="0 0 25 12"><rect x=".5" y=".5" width="21" height="11" rx="3" fill="none" stroke="currentColor"/><rect x="2" y="2" width="15" height="8" rx="1.6"/><rect x="22.5" y="4" width="2" height="4" rx="1"/></svg></span>
                        </div>
                        <div class="hp-auto-bar"><img src="{{ $aaE('salute') }}" alt=""><span><b>Anee Auto</b><small>Lot 1 · DAT 21 · Palay</small></span><em>● Online</em></div>
                        <div class="hp-auto-chat" data-auto-chat>
                            <div class="hp-auto-day">Today</div>
                            <div class="hp-auto-b" data-msg><img src="{{ $aaE('happy') }}" alt=""><div>Magandang umaga! ☀️ Rain is likely this afternoon, so we put the fertilizer on this morning.</div></div>
                            <div class="hp-auto-b" data-msg><img src="{{ $aaE('thumbsup') }}" alt=""><div><b>Today's work</b>
                                <span class="hp-auto-t"><i>🧪</i>Second fertilizer, Lot 1<em>Cannot wait</em></span>
                                <span class="hp-auto-t"><i>💧</i>Keep 3 cm of water</span>
                                <span class="hp-auto-t"><i>👀</i>Walk the field for stem borer</span></div></div>
                            <div class="hp-auto-b is-me" data-msg><div>🙅 Can't spray tomorrow, no workers</div></div>
                            <div class="hp-auto-b" data-msg><img src="{{ $aaE('wink') }}" alt=""><div>No problem. I moved it to Thursday, the next dry morning with two hands free. 👍</div></div>
                            <div class="hp-auto-b is-me" data-msg><div><span class="hp-auto-photo">🌾</span>Yellow tips on the lower leaves</div></div>
                            <div class="hp-auto-b" data-msg><img src="{{ $aaE('thinking') }}" alt=""><div>Early nitrogen hunger. Your next fertilizer was on Friday: I moved it to Wednesday. 💪</div></div>
                            <div class="hp-auto-b hp-auto-typing" data-typing><img src="{{ $aaE('thinking') }}" alt=""><div><i></i><i></i><i></i></div></div>
                        </div>
                        <div class="hp-auto-compose"><span>Ask Anee about your season</span><b>➤</b></div>
                        <span class="hp-auto-home"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hp-auto-cta reveal">
            <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">{{ $aaSay('cta', 'Let Anee plan my season') }}</a>
            <span>Answer once. Anee does the thinking; you do the farming.</span>
        </div>
    </div>
</section>
@once
<style>
    /* Its own ground: a warm morning between Anee's dark section and the analyses. */
    .hp-auto { position: relative; overflow-x: clip; padding-top: 4.5rem; padding-bottom: 4.5rem;
        background: radial-gradient(55% 45% at 12% 8%, rgb(245 197 24 / .18) 0%, transparent 70%), radial-gradient(50% 50% at 92% 90%, rgb(107 159 61 / .14) 0%, transparent 70%), linear-gradient(180deg, #fffdf5 0%, #fbf7e8 100%); }
    .hp-auto .hp-head { text-align: center; max-width: 48rem; margin: 0 auto; }
    .hp-auto.is-site .fx-h { font-size: clamp(1.7rem, 4vw, 2.6rem); line-height: 1.15; text-wrap: balance; }
    .hp-auto.is-site .fx-h em { font-style: normal; color: #4a7c2a; }
    .hp-auto-skip { display: flex; flex-wrap: wrap; justify-content: center; gap: .45rem; margin: 1.3rem 0 0; padding: 0; list-style: none; }
    .hp-auto-skip li { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .8rem; border-radius: 999px; font-size: .84rem; font-weight: 700; color: #4b3a00;
        background: #fff; border: 1px solid #f1df9c; box-shadow: 0 6px 14px -12px rgb(120 90 0 / .6); }
    .hp-auto-skip li::before { content: ''; width: .95rem; height: .95rem; flex: none; border-radius: 999px; background: #4a7c2a url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center / 70% no-repeat; }
    .hp-auto-grid { display: grid; gap: 2.5rem; align-items: center; margin-top: 2.8rem; }
    @media (min-width: 1024px) { .hp-auto-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 3rem; } }
    .hp-auto-steps { display: grid; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .hp-auto-steps li { position: relative; display: flex; gap: .9rem; padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #ece4c6;
        box-shadow: 0 16px 34px -28px rgb(70 55 0 / .55); }
    .hp-auto-steps li:not(:last-child)::after { content: ''; position: absolute; left: calc(1.1rem + 1.05rem); top: calc(1rem + 2.2rem); bottom: -.85rem; width: 2px;
        background: linear-gradient(#f5c518, rgb(245 197 24 / .15)); }
    .hp-auto-n { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: grid; place-items: center; font-family: var(--font-heading); font-weight: 800; color: #2b3a1c; background: #f5c518;
        box-shadow: 0 0 0 4px #fff7d1; position: relative; z-index: 1; }
    .hp-auto-steps b { display: block; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: #1f2a17; }
    .hp-auto-steps p { margin-top: .25rem; font-size: .9rem; line-height: 1.6; color: #55604a; }
    html.js .hp-auto-steps li { opacity: 0; translate: -1rem 0; transition: opacity .55s cubic-bezier(.22,1,.36,1), translate .55s cubic-bezier(.22,1,.36,1); transition-delay: calc(.15s + var(--k) * .14s); }
    html.js .hp-auto-steps.is-visible li { opacity: 1; translate: none; }

    /* The stage: a glow, a neural network at work on both sides, and the phone in front. */
    .hp-auto-stage { position: relative; display: grid; place-items: center; min-height: 44rem; isolation: isolate; }
    @media (max-width: 639.98px) { .hp-auto-stage { min-height: 40rem; margin: 0 -1rem; } }
    .hp-auto-glow { position: absolute; inset: 8% 6%; z-index: -2; border-radius: 50%; filter: blur(30px);
        background: radial-gradient(closest-side, rgb(245 197 24 / .45), rgb(107 159 61 / .22) 60%, transparent); animation: hpAutoBreath 8s ease-in-out infinite; }
    @keyframes hpAutoBreath { 50% { transform: scale(1.08); opacity: .8; } }
    /* The network: thin links, nodes that light when a pulse reaches them,
       gold pulses running in toward the phone, the inputs named. */
    .hp-auto-net { position: absolute; inset: 0; z-index: -1; width: 100%; height: 100%; overflow: visible;
        -webkit-mask-image: linear-gradient(180deg, transparent, #000 10%, #000 90%, transparent); mask-image: linear-gradient(180deg, transparent, #000 10%, #000 90%, transparent); }
    .hp-auto-net .ln { stroke: rgb(74 124 42 / .2); stroke-width: 1; fill: none; transition: stroke .5s ease; }
    .hp-auto-net .ln.is-hot { stroke: rgb(217 173 18 / .55); transition-duration: .12s; }
    .hp-auto-net .nd { fill: #fff; stroke: #a9cc87; stroke-width: 1.5; transition: fill .5s ease, stroke .5s ease; }
    .hp-auto-net .nd.is-in { fill: #f3f9ec; stroke: #4a7c2a; stroke-width: 2; }
    .hp-auto-net .nd.is-hot { fill: #f5c518; stroke: #d9ad12; transition-duration: .1s; }
    .hp-auto-net .halo { fill: rgb(245 197 24 / .35); opacity: 0; transform-box: fill-box; transform-origin: center; }
    .hp-auto-net .halo.is-on { animation: hpNetHalo .8s ease-out; }
    @keyframes hpNetHalo { from { opacity: 1; transform: scale(.6); } to { opacity: 0; transform: scale(2.4); } }
    .hp-auto-net .pl { fill: #f5c518; filter: drop-shadow(0 0 4px rgb(245 197 24 / .9)); }
    .hp-auto-net .tg rect { fill: rgb(255 255 255 / .92); stroke: #e3ead9; }
    .hp-auto-net .tg text { font-size: 10.5px; font-weight: 800; fill: #2d4a17; font-family: inherit; }
    .hp-auto-net .tg.is-hot rect { fill: #fff6d1; stroke: #e9c63e; }
    /* Thinking rings: slow waves out from behind the phone. */
    .hp-auto-ring { position: absolute; left: 50%; top: 50%; z-index: -1; width: 18rem; height: 18rem; margin: -9rem 0 0 -9rem; border-radius: 999px;
        border: 1.5px solid rgb(74 124 42 / .28); opacity: 0; animation: hpAutoRing 6s cubic-bezier(.22,1,.36,1) infinite; pointer-events: none; }
    .hp-auto-ring.is-2 { animation-delay: 3s; }
    @keyframes hpAutoRing { 0% { opacity: .9; transform: scale(.6); } 100% { opacity: 0; transform: scale(2.1); } }

    /* The phone: frame, side keys, the island, the status bar, the home line. */
    .hp-auto-phone { position: relative; width: clamp(16rem, 74vw, 20.5rem); aspect-ratio: 9 / 19; padding: .62rem; border-radius: 3rem;
        background: linear-gradient(145deg, #2a2f2a, #0d100c 40%, #1d211c); box-shadow: 0 50px 90px -40px rgb(30 45 15 / .85), 0 0 0 1px rgb(255 255 255 / .08), inset 0 0 0 2px rgb(255 255 255 / .06);
        animation: hpAutoFloat 7s ease-in-out infinite; }
    @keyframes hpAutoFloat { 50% { transform: translateY(-10px) rotate(-.8deg); } }
    .hp-auto-key { position: absolute; width: 4px; border-radius: 3px; background: #2f342e; }
    .hp-auto-key.is-power { right: -3px; top: 26%; height: 4.2rem; }
    .hp-auto-key.is-up { left: -3px; top: 20%; height: 2.6rem; }
    .hp-auto-key.is-down { left: -3px; top: calc(20% + 3.2rem); height: 2.6rem; }
    .hp-auto-screen { position: relative; height: 100%; overflow: hidden; border-radius: 2.45rem; background: linear-gradient(180deg, #f3f8ec, #f8fbf4); display: flex; flex-direction: column; }
    .hp-auto-status { position: relative; display: flex; align-items: center; justify-content: space-between; padding: .7rem 1.35rem .2rem; font-size: .72rem; color: #111; }
    .hp-auto-status b { font-weight: 800; }
    .hp-auto-island { position: absolute; left: 50%; top: .5rem; width: 5.6rem; height: 1.45rem; margin-left: -2.8rem; border-radius: 999px; background: #0b0d0b; }
    .hp-auto-sig { display: inline-flex; gap: .3rem; align-items: center; }
    .hp-auto-sig svg { width: 1rem; height: .7rem; fill: currentColor; }
    .hp-auto-sig svg + svg { width: 1.35rem; }
    .hp-auto-bar { display: flex; align-items: center; gap: .55rem; margin-top: .35rem; padding: .45rem .9rem .6rem; border-bottom: 1px solid #e3eadb; background: rgb(255 255 255 / .7); }
    .hp-auto-bar img { width: 2.2rem; height: 2.2rem; border-radius: 999px; background: #e4efd4; }
    .hp-auto-bar span { min-width: 0; flex: 1; }
    .hp-auto-bar b { display: block; font-size: .86rem; font-weight: 800; color: #1f2a17; }
    .hp-auto-bar small { display: block; font-size: .66rem; color: #6b7a5c; }
    .hp-auto-bar em { font-style: normal; font-size: .62rem; font-weight: 800; color: #3d8b2a; }
    .hp-auto-chat { flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; gap: .5rem; padding: .6rem .7rem; }
    .hp-auto-day { align-self: center; font-size: .6rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #8a977c; }
    .hp-auto-b { display: flex; gap: .4rem; align-items: flex-end; transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1); }
    .hp-auto-b img { flex: none; width: 1.75rem; height: 1.75rem; border-radius: 999px; background: #e4efd4; }
    .hp-auto-b > div { max-width: 82%; padding: .5rem .65rem; border-radius: 1rem 1rem 1rem .3rem; background: #fff; border: 1px solid #e3eadb; font-size: .74rem; line-height: 1.45; color: #1f2a17;
        box-shadow: 0 6px 14px -12px rgb(30 45 15 / .5); }
    .hp-auto-b.is-me { justify-content: flex-end; }
    .hp-auto-b.is-me > div { background: #dff0cc; border-color: #c4dfa8; border-radius: 1rem 1rem .3rem 1rem; }
    .hp-auto-photo { display: block; height: 3.2rem; margin: -.2rem -.3rem .35rem; border-radius: .6rem; font-size: 1.6rem; line-height: 3.2rem; text-align: center;
        background: linear-gradient(160deg, #9bc46a, #4a7c2a); }
    .hp-auto-t { display: flex; align-items: center; gap: .35rem; margin-top: .3rem; padding: .35rem .45rem; border-radius: .6rem; background: #fbfcf8; border: 1px solid #e3eadb; font-size: .68rem; font-weight: 700; }
    .hp-auto-t i { font-style: normal; } .hp-auto-t em { margin-left: auto; font-style: normal; font-size: .58rem; font-weight: 800; color: #8a5a00; background: #fff4d6; padding: 0 .35rem; border-radius: 999px; }
    .hp-auto-typing > div { display: inline-flex; gap: .25rem; padding: .6rem .7rem; }
    .hp-auto-typing i { width: .4rem; height: .4rem; border-radius: 999px; background: #8fbf5f; animation: hpAutoDot 1s ease-in-out infinite; }
    .hp-auto-typing i:nth-child(2) { animation-delay: .15s; } .hp-auto-typing i:nth-child(3) { animation-delay: .3s; }
    @keyframes hpAutoDot { 50% { transform: translateY(-3px); opacity: .45; } }
    .hp-auto-compose { display: flex; align-items: center; gap: .5rem; margin: 0 .6rem .9rem; padding: .5rem .55rem .5rem .85rem; border-radius: 999px; background: #fff; border: 1px solid #e3eadb; font-size: .7rem; color: #8a977c; }
    .hp-auto-compose span { flex: 1; }
    .hp-auto-compose b { width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .7rem; color: #fff; background: #4a7c2a; }
    .hp-auto-home { position: absolute; left: 50%; bottom: .4rem; width: 6.5rem; height: .28rem; margin-left: -3.25rem; border-radius: 999px; background: #1a1d19; opacity: .8; }
    /* The loop (JS): a message is hidden until its turn. */
    html.js .hp-auto-chat [data-msg].is-wait, html.js .hp-auto-chat [data-typing].is-wait { display: none; }
    .hp-auto-b.is-in { animation: hpAutoPop .5s cubic-bezier(.22,1,.36,1) both; }
    @keyframes hpAutoPop { from { opacity: 0; transform: translateY(10px) scale(.97); } }
    .hp-auto-chat.is-fading .hp-auto-b, .hp-auto-chat.is-fading .hp-auto-day { opacity: 0; transform: translateY(-8px); }

    /* The line sits under the button (the owner, 2026-10-13). */
    .hp-auto-cta { display: flex; flex-direction: column; align-items: center; gap: .75rem; margin-top: 2.6rem; text-align: center; }
    .hp-auto-cta span { font-size: .92rem; font-weight: 700; color: #55604a; }
    @media (prefers-reduced-motion: reduce) {
        .hp-auto-phone, .hp-auto-glow, .hp-auto-typing i, .hp-auto-ring { animation: none; }
        html.js .hp-auto-steps li { opacity: 1; translate: none; transition: none; }
    }
</style>
@push('scripts')
<script>
(() => {
    /* The network beside Anee's phone: inputs on the outside (what she
       reads), two hidden layers, and the phone's edge as the output. Pulses
       start at an input, hop layer by layer into the phone, and light every
       node and link they pass. Drawn again when the stage changes size;
       runs only while on screen; still under reduced motion. */
    const svg = document.querySelector('[data-auto-net]');
    if (!svg) return;
    const stage = svg.parentElement, phone = stage.querySelector('.hp-auto-phone');
    const NS = 'http://www.w3.org/2000/svg';
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let inputs = [[], []];
    try { inputs = JSON.parse(svg.dataset.inputs || '[[],[]]'); } catch (_) {}
    const mk = (tag, attrs, parent) => { const e = document.createElementNS(NS, tag); for (const k in attrs) e.setAttribute(k, attrs[k]); (parent || svg).appendChild(e); return e; };
    let net = null, raf = 0, on = false, last = 0, spawnAt = 0, pulses = [];
    const draw = () => {
        const W = stage.clientWidth, H = stage.clientHeight;
        if (!W || !H) return;
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.textContent = '';
        pulses = [];
        const pr = phone.getBoundingClientRect(), sr = stage.getBoundingClientRect();
        const pl = pr.left - sr.left, prr = pr.right - sr.left, pt = pr.top - sr.top, ph = pr.height;
        const room = pl;                       // the space beside the phone, each side
        const small = room < 90, words = room >= 96;
        const sides = [];
        const gLn = mk('g', {}), gHalo = mk('g', {}), gNd = mk('g', {}), gPl = mk('g', {}), gTg = mk('g', {});
        [0, 1].forEach((side) => {
            const dir = side ? -1 : 1;            // left side runs right, toward the phone
            const outer = side ? W - Math.max(14, room * .12) : Math.max(14, room * .12);
            const edge = side ? prr + 4 : pl - 4;
            const span = edge - outer;
            const layers = small
                ? [inputs[side].length, 0].map((n, i) => ({ n: n || 3, x: outer + span * [0, .55][i] }))
                : [{ n: inputs[side].length, x: outer }, { n: 5, x: outer + span * .38 }, { n: 4, x: outer + span * .7 }];
            const ys = (n, top, bot) => Array.from({ length: n }, (_, i) => top + (bot - top) * (n === 1 ? .5 : i / (n - 1)));
            const top = pt + ph * .1, bot = pt + ph * .9;
            const L = layers.map((ly, li) => ys(ly.n, top + li * ph * .05, bot - li * ph * .05).map((y) => ({ x: ly.x + (li === 0 ? (Math.random() - .5) * 6 : 0), y, li })));
            // The output: points on the phone's edge.
            L.push(ys(4, pt + ph * .3, pt + ph * .72).map((y) => ({ x: edge, y, li: L.length, out: true })));
            const links = [];
            for (let li = 0; li < L.length - 1; li++) {
                L[li].forEach((a, ai) => L[li + 1].forEach((b, bi) => {
                    // Not every link: a sparse net reads as a network, a full one as a mesh.
                    if (L[li].length * L[li + 1].length > 12 && (ai + bi * 2) % 3 === 2) return;
                    const el = mk('line', { x1: a.x.toFixed(1), y1: a.y.toFixed(1), x2: b.x.toFixed(1), y2: b.y.toFixed(1), class: 'ln' }, gLn);
                    links.push({ a, b, el });
                }));
            }
            L.forEach((layer) => layer.forEach((n) => {
                if (n.out) return;
                const r = n.li === 0 ? (small ? 4 : 5.5) : (small ? 3 : 4);
                n.halo = mk('circle', { cx: n.x.toFixed(1), cy: n.y.toFixed(1), r: r * 2, class: 'halo' }, gHalo);
                n.el = mk('circle', { cx: n.x.toFixed(1), cy: n.y.toFixed(1), r, class: 'nd' + (n.li === 0 ? ' is-in' : '') }, gNd);
            }));
            // The inputs' names, beside them on the outside... or above them where the side is narrow.
            if (words) {
                L[0].forEach((n, i) => {
                    const label = inputs[side][i];
                    if (!label) return;
                    const g = mk('g', { class: 'tg' }, gTg);
                    const t = mk('text', { y: (n.y - 11).toFixed(1), 'text-anchor': 'middle' }, g);
                    t.textContent = label;
                    const w = label.length * 6.2 + 14;
                    const cx = Math.min(Math.max(n.x, w / 2 + 2), W - w / 2 - 2);
                    t.setAttribute('x', cx.toFixed(1));
                    const rect = mk('rect', { x: (cx - w / 2).toFixed(1), y: (n.y - 24).toFixed(1), width: w.toFixed(1), height: 18, rx: 9 }, g);
                    g.insertBefore(rect, t);
                    n.tag = g;
                });
            }
            sides.push({ L, links, dir });
        });
        net = { sides, gPl };
    };
    const heat = (el, cls, ms) => { if (!el) return; el.classList.add(cls); clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove(cls), ms); };
    const spawn = () => {
        const side = net.sides[Math.random() < .5 ? 0 : 1];
        // A route: one node per layer, each step along a link that exists.
        let n = side.L[0][Math.floor(Math.random() * side.L[0].length)];
        const hops = [];
        while (true) {
            const outs = side.links.filter((k) => k.a === n);
            if (!outs.length) break;
            const k = outs[Math.floor(Math.random() * outs.length)];
            hops.push(k); n = k.b;
        }
        if (!hops.length) return;
        heat(hops[0].a.el, 'is-hot', 500); heat(hops[0].a.tag, 'is-hot', 900);
        if (hops[0].a.halo) { hops[0].a.halo.classList.remove('is-on'); void hops[0].a.halo.getBBox(); hops[0].a.halo.classList.add('is-on'); }
        pulses.push({ hops, i: 0, t: 0, dot: mk('circle', { r: 2.6, class: 'pl' }, net.gPl) });
    };
    const tick = (now) => {
        if (!on) { raf = 0; return; }
        const dt = Math.min(64, now - (last || now)); last = now;
        if (now >= spawnAt) { spawn(); spawnAt = now + 260 + Math.random() * 260; }
        pulses = pulses.filter((p) => {
            const k = p.hops[p.i];
            const len = Math.hypot(k.b.x - k.a.x, k.b.y - k.a.y) || 1;
            p.t += dt * .16 / len;
            if (p.t >= 1) {
                heat(k.el, 'is-hot', 300);
                heat(k.b.el, 'is-hot', 420);
                if (k.b.halo) { k.b.halo.classList.remove('is-on'); void k.b.halo.getBBox(); k.b.halo.classList.add('is-on'); }
                p.i++; p.t = 0;
                if (p.i >= p.hops.length) { p.dot.remove(); return false; }
                return true;
            }
            p.dot.setAttribute('cx', (k.a.x + (k.b.x - k.a.x) * p.t).toFixed(1));
            p.dot.setAttribute('cy', (k.a.y + (k.b.y - k.a.y) * p.t).toFixed(1));
            return true;
        });
        raf = requestAnimationFrame(tick);
    };
    const start = () => { if (still || raf || !net) return; on = true; last = 0; raf = requestAnimationFrame(tick); };
    const stop = () => { on = false; };
    draw();
    let rt = 0;
    new ResizeObserver(() => { clearTimeout(rt); rt = setTimeout(() => { const was = on; stop(); cancelAnimationFrame(raf); raf = 0; draw(); if (was) start(); }, 150); }).observe(stage);
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => (e.isIntersecting ? start() : stop())), { threshold: .15 }).observe(stage);
    else start();
})();
</script>
<script>
(() => {
    /* Anee's day on the phone, in a loop: a message, her typing, the next,
       then the whole chat fades and starts again. Only while it is on screen;
       under reduced motion every message simply shows. */
    const chat = document.querySelector('[data-auto-chat]');
    if (!chat) return;
    const msgs = [...chat.querySelectorAll('[data-msg]')], typing = chat.querySelector('[data-typing]');
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) { typing.remove(); return; }
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));
    let running = false, seen = false;
    const play = async () => {
        if (running) return;
        running = true;
        while (seen) {
            chat.classList.remove('is-fading');
            msgs.forEach((m) => { m.classList.add('is-wait'); m.classList.remove('is-in'); });
            typing.classList.add('is-wait');
            await wait(600);
            for (const m of msgs) {
                if (!seen) break;
                const mine = m.classList.contains('is-me');
                if (!mine) { typing.classList.remove('is-wait'); chat.appendChild(typing); await wait(1100); typing.classList.add('is-wait'); }
                m.classList.remove('is-wait'); m.classList.add('is-in');
                await wait(mine ? 1500 : 2300);
            }
            await wait(2600);
            chat.classList.add('is-fading');
            await wait(700);
        }
        running = false;
    };
    msgs.forEach((m) => m.classList.add('is-wait'));
    typing.classList.add('is-wait');
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((es) => es.forEach((e) => { seen = e.isIntersecting; if (seen) play(); }), { threshold: .3 }) : null;
    if (io) io.observe(chat); else { msgs.forEach((m) => m.classList.remove('is-wait')); }
})();
</script>
@endpush
@endonce
