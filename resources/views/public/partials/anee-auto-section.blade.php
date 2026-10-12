{{-- ANEE AUTO on the homepage (2026-10-12, the owner): a section of its own,
     for the farmer who does not want to build schedules and run analyses:
     answer once, then just follow what Anee says. How it works in five steps,
     a real looking phone playing her day in a loop, with bubbles of her faces
     rising behind it (she is busy with everything), and the door to sign up.
     No prices here. Expects $signup.

     The ads landing page (/start, 2026-10-13) shows it too: there it passes
     $aaLook 'site' (the site's heading classes, which /start dresses) and
     $aaText, the mother app's words for the kicker, headline (*stars* mark
     the green words), sub and button; any left blank keep these. --}}
@php
    $aaE = fn (string $n) => \App\Support\AneeEmoji::url($n);
    $aaSite = ($aaLook ?? 'home') === 'site';
    $aaSay = fn (string $k, string $own) => trim((string) (($aaText ?? [])[$k] ?? '')) !== '' ? trim((string) $aaText[$k]) : $own;
    // [face, what she is doing, centre %, size rem, delay s, rise s, sway rem]: two columns, either side of the phone.
    $aaBubbles = [
        // Left of the phone: one every 2.5 s, all at one speed, so they never run into each other.
        ['thinking', 'Reading the forecast', 5, 3.3, 0, 15, .6],
        ['idea', null, 6.5, 2.4, 2.5, 15, .4],
        ['starstruck', 'Looking up your variety', 5, 3, 5, 15, .6],
        ['leaf', null, 6.5, 2.4, 7.5, 15, .4],
        ['wink', 'Moving a task to a dry day', 5, 2.9, 10, 15, .6],
        ['cheer', null, 6.5, 2.4, 12.5, 15, .4],
        // Right of the phone, half a step behind.
        ['thumbsup', 'Timing the fertilizer', 95, 3.2, 1.25, 15, -.6],
        ['salute', null, 93.5, 2.3, 3.75, 15, -.4],
        ['concerned', 'Watching for typhoons', 95, 3.1, 6.25, 15, -.6],
        ['happy', null, 93.5, 2.3, 8.75, 15, -.4],
        ['yes', 'Checking your soil', 95, 2.8, 11.25, 15, -.6],
        ['grin', 'Emailing your workers', 95, 2.7, 13.75, 15, -.4],
    ];
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
                <div class="hp-auto-bubbles">
                    @foreach ($aaBubbles as [$bf, $bl, $bx, $bs, $bd, $bt, $bw])
                        <span class="hp-auto-bub{{ $bl ? ' has-say' : '' }}" style="--x:{{ $bx }};--s:{{ $bs }}rem;--d:{{ $bd }}s;--t:{{ $bt }}s;--w:{{ $bw }}rem">
                            <span class="hp-auto-bub-in"><img src="{{ $aaE($bf) }}" alt="" loading="lazy">@if ($bl)<em>{{ $bl }}</em>@endif</span>
                        </span>
                    @endforeach
                </div>
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

    /* The stage: a glow, her faces rising like bubbles, and the phone in front. */
    .hp-auto-stage { position: relative; display: grid; place-items: center; min-height: 44rem; isolation: isolate; }
    @media (max-width: 639.98px) { .hp-auto-stage { min-height: 40rem; margin: 0 -1rem; } }
    .hp-auto-glow { position: absolute; inset: 8% 6%; z-index: -2; border-radius: 50%; filter: blur(30px);
        background: radial-gradient(closest-side, rgb(245 197 24 / .45), rgb(107 159 61 / .22) 60%, transparent); animation: hpAutoBreath 8s ease-in-out infinite; }
    @keyframes hpAutoBreath { 50% { transform: scale(1.08); opacity: .8; } }
    /* The bubbles' box reaches 4rem past the stage on each side, so a word at the edge is not cut off by the fade; --x still counts across the stage. */
    .hp-auto-bubbles { position: absolute; inset: 0 -4rem; z-index: -1;
        -webkit-mask-image: linear-gradient(180deg, transparent, #000 14%, #000 82%, transparent); mask-image: linear-gradient(180deg, transparent, #000 14%, #000 82%, transparent); }
    .hp-auto-bub { position: absolute; left: calc(4rem + (100% - 8rem) * var(--x) / 100); bottom: -6rem; width: 6.6rem; margin-left: -3.3rem; display: flex; justify-content: center; animation: hpBubRise var(--t) linear var(--d) infinite both; }
    .hp-auto-bub-in { display: inline-flex; flex-direction: column; align-items: center; gap: .3rem; animation: hpBubSway calc(var(--t) / 3) ease-in-out var(--d) infinite alternate; }
    .hp-auto-bub img { width: var(--s); height: var(--s); flex: none; padding: .3rem; border-radius: 999px; background: rgb(255 255 255 / .85);
        box-shadow: 0 10px 22px -12px rgb(70 55 0 / .55), inset 0 0 0 1px rgb(255 255 255 / .9); backdrop-filter: blur(4px); }
    .hp-auto-bub em { font-style: normal; max-width: 6.6rem; text-align: center; line-height: 1.25; padding: .25rem .5rem; border-radius: .8rem; font-size: .64rem; font-weight: 800; color: #2d4a17;
        background: rgb(255 255 255 / .9); box-shadow: 0 8px 18px -12px rgb(70 55 0 / .6); }
    @keyframes hpBubRise { 0% { transform: translateY(0) scale(.6); opacity: 0; } 8% { opacity: 1; transform: translateY(-4rem) scale(1); } 85% { opacity: 1; } 100% { transform: translateY(-50rem) scale(1.02); opacity: 0; } }
    @keyframes hpBubSway { from { transform: translateX(calc(var(--w) * -1)); } to { transform: translateX(var(--w)); } }
    /* Two columns under 1200px leave the phone no room for words beside it: the faces alone. */
    @media (min-width: 1024px) and (max-width: 1199.98px) { .hp-auto-bub em { display: none; } }
    @media (max-width: 639.98px) { .hp-auto-bub em { display: none; } .hp-auto-bubbles { inset: 0; } .hp-auto-bub { left: clamp(2rem, calc(var(--x) * 1%), calc(100% - 2rem)); } }

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
        .hp-auto-phone, .hp-auto-glow, .hp-auto-bub-in, .hp-auto-typing i { animation: none; }
        .hp-auto-bub { animation: none; bottom: auto; top: calc(10% + var(--d) * 4%); opacity: .9; }
        html.js .hp-auto-steps li { opacity: 1; translate: none; transition: none; }
    }
</style>
@push('scripts')
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
