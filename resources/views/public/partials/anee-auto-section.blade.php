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
    {{-- Her thinking, across the whole section (2026-10-13, the owner: "extend
         the web through the section"): drawn by the script to fit it; still
         under reduced motion. --}}
    <svg class="hp-auto-web" data-auto-net data-inputs='@json($aaInputs)' aria-hidden="true"></svg>
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
    /* The web: a mesh of nodes over the whole section, every path leading to
       Anee's phone. Thin links, nodes that light as a pulse passes, gold
       pulses with a tail, the things she reads named at the far ends. */
    .hp-auto > .max-w-6xl { position: relative; z-index: 1; }
    .hp-auto-web { position: absolute; inset: 0; z-index: 0; width: 100%; height: 100%; pointer-events: none;
        -webkit-mask-image: linear-gradient(180deg, transparent, #000 7%, #000 93%, transparent); mask-image: linear-gradient(180deg, transparent, #000 7%, #000 93%, transparent); }
    .hp-auto-web .ln { stroke: rgb(74 124 42 / .17); stroke-width: 1; fill: none; transition: stroke .6s ease, stroke-width .6s ease; }
    .hp-auto-web .ln.is-in { stroke: rgb(74 124 42 / .26); stroke-dasharray: 3 4; }
    .hp-auto-web .ln.is-hot { stroke: rgb(217 173 18 / .7); stroke-width: 1.6; transition-duration: .12s; }
    .hp-auto-web .nd { fill: #fbfdf7; stroke: #b3d293; stroke-width: 1.4; transition: fill .6s ease, stroke .6s ease; }
    .hp-auto-web .nd.is-src { fill: #f3f9ec; stroke: #4a7c2a; stroke-width: 2; }
    .hp-auto-web .nd.is-hot { fill: #f5c518; stroke: #d9ad12; transition-duration: .1s; }
    .hp-auto-web .halo { fill: rgb(245 197 24 / .32); opacity: 0; transform-box: fill-box; transform-origin: center; }
    .hp-auto-web .halo.is-on { animation: hpNetHalo .9s ease-out; }
    @keyframes hpNetHalo { from { opacity: 1; transform: scale(.5); } to { opacity: 0; transform: scale(2.6); } }
    .hp-auto-web .pl { fill: #fff7d1; filter: drop-shadow(0 0 4px rgb(245 197 24)) drop-shadow(0 0 10px rgb(245 197 24 / .7)); }
    .hp-auto-web .tl { fill: none; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
    .hp-auto-web .rip { fill: none; stroke: #f5c518; stroke-width: 2; transform-box: fill-box; transform-origin: center; animation: hpNetRip .9s cubic-bezier(.22,1,.36,1) forwards; }
    @keyframes hpNetRip { from { opacity: .9; transform: scale(.3); } to { opacity: 0; transform: scale(3.2); } }
    .hp-auto-web .nd.tw { animation: hpNetTw 2.4s ease-in-out; }
    @keyframes hpNetTw { 50% { fill: #fff3c4; stroke: #e9c63e; } }
    /* The web leans a little toward the pointer, as if it is listening. */
    .hp-auto-web { translate: var(--wx, 0) var(--wy, 0); transition: translate .6s cubic-bezier(.22,1,.36,1); }
    /* Each pulse that reaches the phone lights its edge for a moment. */
    .hp-auto-phone { transition: box-shadow .5s ease; }
    .hp-auto-phone.is-fed { box-shadow: 0 0 0 2px rgb(245 197 24 / .55), 0 0 40px 6px rgb(245 197 24 / .35), 0 40px 80px -40px rgb(20 33 12 / .6); transition-duration: .12s; }
    /* A field of faint dots drifting behind the web. */
    .hp-auto::before { content: ''; position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .55;
        background-image: radial-gradient(rgb(74 124 42 / .18) 1px, transparent 1.6px); background-size: 22px 22px; animation: hpAutoDots 40s linear infinite;
        -webkit-mask-image: radial-gradient(70% 70% at 50% 55%, #000, transparent); mask-image: radial-gradient(70% 70% at 50% 55%, #000, transparent); }
    @keyframes hpAutoDots { to { background-position: 220px 110px; } }
    .hp-auto-web .tg rect { fill: rgb(255 255 255 / .94); stroke: #dfe9d3; }
    .hp-auto-web .tg text { font-size: 11px; font-weight: 800; fill: #2d4a17; font-family: inherit; }
    .hp-auto-web .tg.is-hot rect { fill: #fff3c4; stroke: #e2b72a; }
    .hp-auto-web .tg { transition: opacity .6s ease; }
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
        .hp-auto-phone, .hp-auto-glow, .hp-auto-typing i, .hp-auto-ring, .hp-auto::before { animation: none; }
        .hp-auto-web { translate: none; transition: none; }
        html.js .hp-auto-steps li { opacity: 1; translate: none; transition: none; }
    }
</style>
@push('scripts')
<script>
(() => {
    /* The web across the section: nodes on a loose grid over the whole of
       it (none on the words, none on the phone), each joined to its nearest
       neighbours. Every node knows its next step toward Anee's phone, so a
       pulse that starts anywhere, often at one of the things she reads (named
       at the far ends), hops node by node and runs into the phone. Drawn
       again when the section changes size; runs only while on screen; still
       under reduced motion. */
    const svg = document.querySelector('[data-auto-net]');
    if (!svg) return;
    const sec = svg.closest('section'), phone = sec.querySelector('.hp-auto-phone');
    const NS = 'http://www.w3.org/2000/svg';
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let inputs = [[], []];
    try { inputs = JSON.parse(svg.dataset.inputs || '[[],[]]'); } catch (_) {}
    const names = [...(inputs[0] || []), ...(inputs[1] || [])];
    const mk = (tag, attrs, parent) => { const e = document.createElementNS(NS, tag); for (const k in attrs) e.setAttribute(k, attrs[k]); (parent || svg).appendChild(e); return e; };
    const rnd = (a, b) => a + Math.random() * (b - a);
    let web = null, raf = 0, on = false, last = 0, spawnAt = 0, pulses = [], cometN = 0;
    const draw = () => {
        const W = sec.clientWidth, H = sec.clientHeight;
        if (!W || !H || !phone) return;
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.textContent = '';
        pulses = [];
        const defs = mk('defs', {});
        const sr = sec.getBoundingClientRect();
        const rel = (el) => { const r = el.getBoundingClientRect(); return { l: r.left - sr.left, t: r.top - sr.top, r: r.right - sr.left, b: r.bottom - sr.top }; };
        const P = rel(phone);
        const hub = { x: (P.l + P.r) / 2, y: (P.t + P.b) / 2 };
        const words = [...sec.querySelectorAll('.hp-head > *, .hp-auto-steps li, .hp-auto-cta > *')].map(rel);
        const inRect = (x, y, a, pad) => x > a.l - pad && x < a.r + pad && y > a.t - pad && y < a.b + pad;
        const small = W < 640;
        const cell = small ? 74 : 104;
        const cols = Math.max(3, Math.round(W / cell)), rows = Math.max(3, Math.round(H / cell));
        const cw = W / cols, ch = H / rows;
        const nodes = [];
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const x = (c + .5 + rnd(-.32, .32)) * cw, y = (r + .5 + rnd(-.32, .32)) * ch;
                if (inRect(x, y, P, 14) || words.some((a) => inRect(x, y, a, 8))) continue;
                nodes.push({ x, y, d: Math.hypot(x - hub.x, y - hub.y), nb: [] });
            }
        }
        // Links: each node to its three nearest, once.
        const seen = new Set(), links = [];
        const gLn = mk('g', {}), gHalo = mk('g', {}), gNd = mk('g', {}), gPl = mk('g', {}), gTg = mk('g', {});
        const reach = Math.max(cw, ch) * 2.1;
        nodes.forEach((a, i) => {
            nodes.map((b, j) => [j, Math.hypot(a.x - b.x, a.y - b.y)]).filter(([j, dd]) => j !== i && dd < reach)
                .sort((u, v) => u[1] - v[1]).slice(0, 3).forEach(([j]) => {
                    const key = Math.min(i, j) + ':' + Math.max(i, j);
                    if (seen.has(key)) return;
                    seen.add(key);
                    const b = nodes[j];
                    const el = mk('line', { x1: a.x.toFixed(1), y1: a.y.toFixed(1), x2: b.x.toFixed(1), y2: b.y.toFixed(1), class: 'ln' }, gLn);
                    const k = { a, b, el };
                    links.push(k); a.nb.push([b, k]); b.nb.push([a, k]);
                });
        });
        // The phone's edge, seen from a point: where a pulse goes in.
        const edge = (n) => {
            const dx = n.x - hub.x, dy = n.y - hub.y;
            const hw = (P.r - P.l) / 2 + 4, hh = (P.b - P.t) / 2 + 4;
            const f = Math.min(hw / Math.max(Math.abs(dx), 1e-6), hh / Math.max(Math.abs(dy), 1e-6));
            return { x: hub.x + dx * f, y: hub.y + dy * f };
        };
        // Each node's next step: the neighbour closest to the phone, if it is
        // closer than the node itself; nodes near the phone run straight in.
        const near = Math.max(P.r - P.l, P.b - P.t) / 2 + Math.max(cw, ch) * 1.25;
        nodes.forEach((n) => {
            if (n.d < near) {
                const e = edge(n);
                n.next = 'in';
                n.inLink = { a: n, b: { x: e.x, y: e.y, end: true }, el: mk('line', { x1: n.x.toFixed(1), y1: n.y.toFixed(1), x2: e.x.toFixed(1), y2: e.y.toFixed(1), class: 'ln is-in' }, gLn) };
                return;
            }
            let best = null;
            n.nb.forEach(([b, k]) => { if (b.d < n.d - 6 && (!best || b.d < best[0].d)) best = [b, k]; });
            n.next = best;
        });
        // A node leads in when its chain reaches the phone.
        const leads = (n) => { let m = n, i = 0; while (m && m.next && m.next !== 'in' && i++ < 60) m = m.next[0]; return !!(m && m.next === 'in'); };
        nodes.forEach((n) => { n.leads = leads(n); });
        nodes.forEach((n) => {
            n.halo = mk('circle', { cx: n.x.toFixed(1), cy: n.y.toFixed(1), r: 7, class: 'halo' }, gHalo);
            n.el = mk('circle', { cx: n.x.toFixed(1), cy: n.y.toFixed(1), r: small ? 2.4 : 3, class: 'nd' }, gNd);
        });
        // What she reads: the farthest leading node in each of eight directions round the phone.
        const sources = [];
        if (!small) {
            const room = (n) => n.x > 60 && n.x < W - 60 && n.y > 40 && n.y < H - 40 && !words.some((a) => inRect(n.x, n.y, a, 34));
            for (let k = 0; k < 8 && k < names.length; k++) {
                const from = -Math.PI + (k / 8) * 2 * Math.PI, to = from + (2 * Math.PI) / 8;
                let best = null;
                nodes.forEach((n) => {
                    if (!n.leads || n.src || !room(n)) return;
                    const ang = Math.atan2(n.y - hub.y, n.x - hub.x);
                    if (ang >= from && ang < to && (!best || n.d > best.d)) best = n;
                });
                if (!best) continue;
                best.src = true;
                best.el.setAttribute('class', 'nd is-src');
                best.el.setAttribute('r', 5);
                const label = names[sources.length % names.length];
                const g = mk('g', { class: 'tg' }, gTg);
                const w = label.length * 6.6 + 16;
                const cx = Math.min(Math.max(best.x, w / 2 + 4), W - w / 2 - 4);
                mk('rect', { x: (cx - w / 2).toFixed(1), y: (best.y - 27).toFixed(1), width: w.toFixed(1), height: 19, rx: 9.5 }, g);
                const t = mk('text', { x: cx.toFixed(1), y: (best.y - 13.5).toFixed(1), 'text-anchor': 'middle' }, g);
                t.textContent = label;
                best.tag = g;
                sources.push(best);
            }
        }
        web = { nodes: nodes.filter((n) => n.leads), sources, gPl, defs };
    };
    const heat = (el, cls, ms) => { if (!el) return; el.classList.add(cls); clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove(cls), ms); };
    const ping = (n) => { if (!n.halo) return; n.halo.classList.remove('is-on'); void n.halo.getBBox(); n.halo.classList.add('is-on'); };
    const spawn = () => {
        if (!web || !web.nodes.length) return;
        // Most pulses start where she reads; the rest anywhere on the web.
        const pool = web.sources.length && Math.random() < .45 ? web.sources : web.nodes;
        const n = pool[Math.floor(Math.random() * pool.length)];
        const hops = [];
        let m = n, guard = 0;
        while (m && guard++ < 60) {
            if (m.next === 'in') { hops.push(m.inLink); break; }
            if (!m.next) break;
            hops.push({ a: m, b: m.next[0], el: m.next[1].el }); m = m.next[0];
        }
        if (!hops.length || hops[hops.length - 1].b.end !== true) return;
        heat(n.el, 'is-hot', 520); heat(n.tag, 'is-hot', 1100); ping(n);
        // A comet: a bright head and a fading tail drawn through where it has been.
        // Each tail has its own gradient, laid along it from its end to the head, so it fades the right way whichever way it runs.
        const gid = 'hpAutoC' + (++cometN);
        const grad = mk('linearGradient', { id: gid, gradientUnits: 'userSpaceOnUse' }, web.defs);
        mk('stop', { offset: '0', 'stop-color': '#f5c518', 'stop-opacity': '0' }, grad);
        mk('stop', { offset: '1', 'stop-color': '#f5c518', 'stop-opacity': '.9' }, grad);
        pulses.push({ hops, i: 0, t: 0, trail: [], grad, tail: mk('polyline', { class: 'tl', stroke: 'url(#' + gid + ')' }, web.gPl), dot: mk('circle', { r: 3, class: 'pl' }, web.gPl) });
    };
    const tick = (now) => {
        if (!on) { raf = 0; return; }
        const dt = Math.min(64, now - (last || now)); last = now;
        if (now >= spawnAt && pulses.length < 28) { spawn(); spawnAt = now + rnd(150, 330); }
        pulses = pulses.filter((p) => {
            const k = p.hops[p.i];
            const len = Math.hypot(k.b.x - k.a.x, k.b.y - k.a.y) || 1;
            p.t += dt * .17 / len;
            if (p.t >= 1) {
                heat(k.el, 'is-hot', 360);
                if (k.b.el) { heat(k.b.el, 'is-hot', 420); ping(k.b); }
                p.i++; p.t = 0;
                if (p.i >= p.hops.length) {
                    // It reached the phone: a ripple where it went in, and the phone's edge lights.
                    const r = mk('circle', { cx: k.b.x.toFixed(1), cy: k.b.y.toFixed(1), r: 6, class: 'rip' }, web.gPl);
                    setTimeout(() => r.remove(), 950);
                    phone.classList.add('is-fed'); clearTimeout(phone._fed); phone._fed = setTimeout(() => phone.classList.remove('is-fed'), 260);
                    p.dot.remove(); p.tail.remove(); p.grad.remove(); return false;
                }
                return true;
            }
            const x = k.a.x + (k.b.x - k.a.x) * p.t, y = k.a.y + (k.b.y - k.a.y) * p.t;
            p.trail.unshift([x, y]); p.trail.length = Math.min(p.trail.length, 14);
            p.dot.setAttribute('cx', x.toFixed(1)); p.dot.setAttribute('cy', y.toFixed(1));
            // Oldest point first, so the gradient fades toward the tail.
            const pts = p.trail.slice().reverse();
            if (pts.length > 1) {
                p.tail.setAttribute('points', pts.map((q) => q[0].toFixed(1) + ',' + q[1].toFixed(1)).join(' '));
                p.grad.setAttribute('x1', pts[0][0].toFixed(1)); p.grad.setAttribute('y1', pts[0][1].toFixed(1));
                p.grad.setAttribute('x2', x.toFixed(1)); p.grad.setAttribute('y2', y.toFixed(1));
            }
            return true;
        });
        // Now and then a node twinkles.
        if (web.nodes.length && Math.random() < dt / 260) {
            const n = web.nodes[Math.floor(Math.random() * web.nodes.length)];
            if (n.el && !n.el.classList.contains('is-hot')) { n.el.classList.remove('tw'); void n.el.getBBox(); n.el.classList.add('tw'); }
        }
        raf = requestAnimationFrame(tick);
    };
    // The web leans toward the pointer (a few pixels, desk only).
    if (!still && matchMedia('(pointer: fine)').matches) {
        sec.addEventListener('pointermove', (e) => {
            const r = sec.getBoundingClientRect();
            svg.style.setProperty('--wx', (((e.clientX - r.left) / r.width - .5) * 14).toFixed(1) + 'px');
            svg.style.setProperty('--wy', (((e.clientY - r.top) / r.height - .5) * 10).toFixed(1) + 'px');
        });
        sec.addEventListener('pointerleave', () => { svg.style.setProperty('--wx', '0px'); svg.style.setProperty('--wy', '0px'); });
    }
    const start = () => { if (still || raf || !web) return; on = true; last = 0; raf = requestAnimationFrame(tick); };
    const stop = () => { on = false; };
    // Drawn once the section's words have taken their places (the reveal moves them).
    setTimeout(draw, 900);
    let rt = 0, lastW = 0, lastH = 0;
    new ResizeObserver(() => {
        const W = sec.clientWidth, H = sec.clientHeight;
        if (Math.abs(W - lastW) < 2 && Math.abs(H - lastH) < 40) return;
        lastW = W; lastH = H;
        clearTimeout(rt);
        rt = setTimeout(() => { const was = on; stop(); cancelAnimationFrame(raf); raf = 0; draw(); if (was) start(); }, 200);
    }).observe(sec);
    if ('IntersectionObserver' in window) new IntersectionObserver((es) => es.forEach((e) => (e.isIntersecting ? (web ? start() : setTimeout(start, 1000)) : stop())), { threshold: .05 }).observe(sec);
    else setTimeout(start, 1000);
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
