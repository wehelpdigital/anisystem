{{-- Anee Auto, shown as what it is: the service that runs the whole season (2026-10-28, the owner: "in /how-it-works i
     want you to fix how the anee auto part is presented. its our main or most expensive service so make it look good in
     that part but still looks good in mobile"). Right below its step on the public page: a morning as the farmer gets it
     (played on a loop while it is on screen), the five steps before she takes over, what she does every day, the same
     hectare with and without her, and the plan it comes with. Never its price: that is said inside the app. --}}
@php
    $axPlan = \App\Support\HowItWorks::planOf('auto');
    $axFace = fn (string $f) => asset('images/anee/emoji/' . $f . '.png');
    $axFlow = [
        ['🧑‍🌾', 'Tell her once', 'Your lots and crop, your soil, your water and your workers. Answered once, kept for every season.'],
        ['📅', 'She finds the safest week', 'Twenty years of your town\'s weather and storms, the ENSO outlook, and the price at harvest.'],
        ['🔬', 'She ranks the varieties', 'For your place and planting month: the newest releases, the trials and real harvests near you.'],
        ['🎯', 'You pick a goal you can afford', 'Low, middle or full budget. She balances the fertilizer to it, every bag at its stage.'],
        ['📋', 'She writes every task, then runs it', 'From land preparation to harvest. Every morning, today\'s work for you and each worker.'],
    ];
    $axFeats = [
        ['appointment.png', 'The safest planting week', 'With the harvest price beside it'],
        ['icons/biotechnology.png', 'The right variety for your lot', 'Ranked by real harvests near you'],
        ['icons/biostimulant.png', 'Balanced nutrients for your goal', 'Every bag at the stage it counts'],
        ['icons/storm.svg', 'Weather that moves the plan', 'Rain on a spray day? She moves it'],
        ['time.png', "Today's work at 6 AM", 'For you and each worker, by email too'],
        ['icons/chat.png', 'Ask her anything', 'A photo, a video or a voice note'],
        ['sack.png', 'The shed and the payroll', 'What to buy soon, and payday counted'],
        ['anee/avatar-160.jpg', 'A report when it is done', 'What went well, and what to change'],
    ];
    // The same hectare of palay, a field that could give about 5.6 tons, at 20 pesos a kilo (as the in-app story tells it).
    $axLoss = [['⏰', 'Fertilizer late or at the wrong stage', '0.4'], ['🐛', 'A pest seen too late', '0.4'], ['🌀', 'A typhoon week with no plan', '0.2'], ['🌾', 'Lodging: nitrogen at the wrong time', '0.9']];
    $axGain = [['🎯', 'Fertilizer on time, by stage', '0.4'], ['🔍', 'Weekly scouting, an early spray', '0.4'], ['🌀', 'Work moved around the storm', '0.2'], ['🌾', 'Nitrogen split by stage: the crop stands', '0.9']];
@endphp
<section class="hwx" id="hw-auto-more" aria-labelledby="hwx-h">
    <div class="hwx-glow" aria-hidden="true"></div>
    <header class="hwx-top" data-x>
        <span class="hwx-badge"><i aria-hidden="true">⭐</i>Our flagship<em>Anee does every step</em></span>
        <h2 class="hwx-h" id="hwx-h">Anee runs your whole season. <em>You just follow.</em></h2>
        <p class="hwx-sub">Every analysis anee.io has, done for your own lots and kept up to date every morning. <b>You will not have to figure out what to do each day:</b> she plans it, tells you and your workers, and moves the work herself when the weather turns.</p>
    </header>

    <div class="hwx-grid">
        {{-- A morning with Anee Auto, played on a loop: the plan, the work ticked, the rain that moves a spray. --}}
        <div class="hwx-demo" data-x style="--d: 120ms" aria-hidden="true">
            <div class="hwx-card">
                <div class="hwx-card-h">
                    <img src="{{ $axFace('wave') }}" alt="" width="44" height="44" loading="lazy">
                    <div><small>Good morning! Today on Lot 1</small><b>Palay · DAT 28 · ☀️ 31°, rain at 3 PM</b></div>
                </div>
                <ul class="hwx-tasks">
                    <li style="--k: 0"><i></i><span>💧 Irrigate: 3 to 5 cm of water</span><em>Done</em></li>
                    <li style="--k: 1"><i></i><span>🧪 Urea, 1 bag: the topdress at tillering</span><em>Done</em></li>
                    <li style="--k: 2"><i></i><span>🐛 Scout the edges for stem borer</span><em>Done</em></li>
                </ul>
                <div class="hwx-mail"><span>📧</span>Sent to Mang Ben and Aling Rosa at 6:00 AM</div>
                <div class="hwx-watch"><i></i>Anee is watching the weather over Lot 1</div>
                <div class="hwx-toast">
                    <img src="{{ $axFace('thinking') }}" alt="" width="36" height="36" loading="lazy">
                    <p><b>Rain at 3 PM.</b> I moved the afternoon spray to Thursday, 7 AM, when the leaves are dry.</p>
                </div>
            </div>
            <p class="hwx-demo-cap">A real morning on Anee Auto, in the app and by email</p>
        </div>

        {{-- The five steps before she takes over. --}}
        <div class="hwx-flow-w">
            <p class="hwx-kick" data-x style="--d: 160ms">Five steps, then she takes over</p>
            <ol class="hwx-flow">
                @foreach ($axFlow as $i => [$emo, $title, $line])
                    <li data-x style="--d: {{ 220 + $i * 110 }}ms"><span class="hwx-n">{{ $i + 1 }}</span><div><b><i aria-hidden="true">{{ $emo }}</i>{{ $title }}</b><p>{{ $line }}</p></div></li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- What she does, every day of the season. --}}
    <p class="hwx-kick is-c" data-x>Every day of the season, she</p>
    <div class="hwx-feats">
        @foreach ($axFeats as $i => [$icon, $title, $line])
            <div class="hwx-feat" data-x style="--d: {{ 80 + $i * 60 }}ms">
                <img src="{{ asset('images/' . $icon) }}" alt="" width="40" height="40" loading="lazy" @class(['is-face' => str_starts_with($icon, 'anee/')])>
                <b>{{ $title }}</b><span>{{ $line }}</span>
            </div>
        @endforeach
    </div>

    {{-- The same hectare, farmed the old way and the precise way. --}}
    <div class="hwx-vs" data-x>
        <p class="hwx-kick is-c">The same hectare of palay</p>
        <h3 class="hwx-vs-h">Traditional farming against <em>precision farming with Anee Auto</em></h3>
        <div class="hwx-vs-g">
            <div class="hwx-col is-old">
                <p class="hwx-col-h">😟 Traditional farming, what you do now</p>
                <div class="hwx-bar"><i class="keep" style="--w: .661">3.7 t</i>@foreach ($axLoss as $k => $x)<i class="lost" style="--w: {{ round($x[2] / 5.6, 3) }}; --k: {{ $k + 1 }}"></i>@endforeach</div>
                <ul>@foreach ($axLoss as $k => [$e, $t, $v])<li style="--k: {{ $k }}"><span>{{ $e }} {{ $t }}</span><b>−{{ $v }} t</b></li>@endforeach</ul>
                <p class="hwx-tot"><span>Harvest worth</span><b>₱<span data-hw-count="74000">74,000</span></b></p>
            </div>
            <div class="hwx-col is-new">
                <p class="hwx-col-h"><img src="{{ $axFace('starstruck') }}" alt="" width="26" height="26" loading="lazy"> Precision farming with Anee Auto</p>
                <div class="hwx-bar"><i class="keep" style="--w: .661">3.7 t</i>@foreach ($axGain as $k => $x)<i class="gain" style="--w: {{ round($x[2] / 5.6, 3) }}; --k: {{ $k + 1 }}"></i>@endforeach</div>
                <ul>@foreach ($axGain as $k => [$e, $t, $v])<li style="--k: {{ $k }}"><span>{{ $e }} {{ $t }}</span><b>+{{ $v }} t</b></li>@endforeach</ul>
                <p class="hwx-tot"><span>Harvest worth</span><b>₱<span data-hw-count="112000">112,000</span></b></p>
            </div>
        </div>
        <p class="hwx-net"><b>About ₱<span data-hw-count="38000">38,000</span> more</b> from one hectare, with quicker decisions, the right answer for your own field, balanced nutrients, and the planting and price forecasts behind every step.</p>
        <p class="hwx-fine">An example on a field that could give about 5.6 tons, at ₱20 a kilo. Not a promise: your season depends on your field, the weather and the work done.</p>
    </div>

    <div class="hwx-cta" data-x>
        <img src="{{ $axFace('salute') }}" alt="" width="64" height="64" loading="lazy">
        <div>
            <b>Let Anee run your next season</b>
            <span>{{ $axPlan['says'] }}. Start free, then switch it on when you are ready.</span>
        </div>
        <div class="hwx-cta-b">
            <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
            <a href="{{ route('pricing') }}" class="hw-ghost">See the plans</a>
        </div>
    </div>
</section>

@once
@push('head')
<style>
    /* ---------- Anee Auto, the flagship (public/how/auto-band) ---------- */
    .hwx { position: relative; z-index: 1; isolation: isolate; max-width: 68rem; margin: 2.6rem auto 0; padding: 2.2rem 1.1rem 1.6rem; border-radius: 2rem; overflow: hidden;
        background: radial-gradient(40rem 22rem at 85% -10%, rgb(245 197 24 / .16), transparent 60%), linear-gradient(170deg, #1a2e10 0%, #101c0a 55%, #0d1609 100%);
        box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35), 0 40px 80px -40px rgb(0 0 0 / .9); }
    @media (min-width: 768px) { .hwx { padding: 3rem 2.4rem 2.2rem; } }
    .hwx-glow { position: absolute; inset: -30% -10% auto auto; width: 32rem; height: 32rem; z-index: -1; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(245 197 24 / .18), transparent 70%); animation: hwxGlow 10s ease-in-out infinite; }
    @keyframes hwxGlow { 50% { transform: translate(-3rem, 2rem) scale(1.1); opacity: .7; } }
    .hwx [data-x] { opacity: 0; transform: translateY(16px); transition: opacity .7s var(--ease) var(--d, 0ms), transform .7s var(--ease) var(--d, 0ms); }
    .hwx.is-in [data-x] { opacity: 1; transform: none; }
    .hwx-top { text-align: center; max-width: 44rem; margin: 0 auto; }
    .hwx-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .5rem .35rem .75rem; border-radius: 999px; font-size: .72rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase;
        color: #2b2100; background: linear-gradient(90deg, #f5c518, #ffe27a 50%, #f5c518); background-size: 200% 100%; animation: hwxShine 3.2s linear infinite; box-shadow: 0 10px 24px -12px rgb(245 197 24 / .9); }
    .hwx-badge i { font-style: normal; }
    .hwx-badge em { font-style: normal; padding: .15rem .5rem; border-radius: 999px; letter-spacing: .04em; text-transform: none; font-size: .7rem; color: #fff; background: #2b2100; }
    @keyframes hwxShine { to { background-position: -200% 0; } }
    .hwx-h { margin-top: 1rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.7rem, 4.6vw, 2.8rem); line-height: 1.12; letter-spacing: -.015em; color: #fff; text-wrap: balance; }
    .hwx-h em { font-style: normal; color: var(--acc); }
    .hwx-sub { margin-top: .9rem; font-size: 1rem; line-height: 1.65; color: var(--mute); }
    .hwx-sub b { color: #fff; }
    .hwx-kick { font-size: .72rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: var(--acc); }
    .hwx-kick.is-c { margin-top: 2.6rem; text-align: center; }

    .hwx-grid { display: grid; gap: 2rem; margin-top: 2.2rem; align-items: center; }
    @media (min-width: 900px) { .hwx-grid { grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); gap: 3rem; } }

    /* The morning, on a loop of nine seconds while it is on screen. */
    .hwx-demo { display: grid; justify-items: center; gap: .7rem; }
    .hwx-card { position: relative; width: 100%; max-width: 24rem; padding: 1rem 1rem 5.6rem; border-radius: 1.5rem; color: #1f2a17; background: #fbfdf8; box-shadow: 0 30px 60px -28px rgb(0 0 0 / .9), 0 0 0 6px rgb(255 255 255 / .06); overflow: hidden; }
    .hwx-card-h { display: flex; align-items: center; gap: .65rem; padding-bottom: .75rem; border-bottom: 1px solid #e6eedc; }
    .hwx-card-h img { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; background: #eef6e4; }
    .hwx-card-h small { display: block; font-size: .7rem; font-weight: 800; color: #4a7c2a; }
    .hwx-card-h b { display: block; font-size: .84rem; font-weight: 800; color: #1f2a17; }
    .hwx-tasks { display: grid; gap: .45rem; margin: .8rem 0 0; padding: 0; list-style: none; }
    .hwx-tasks li { display: flex; align-items: center; gap: .55rem; padding: .55rem .6rem; border-radius: .85rem; font-size: .8rem; font-weight: 700; background: #fff; box-shadow: inset 0 0 0 1px #e3ecd8; }
    .hwx-tasks li span { flex: 1; min-width: 0; }
    .hwx-tasks li i { flex: none; position: relative; width: 1.2rem; height: 1.2rem; border-radius: .4rem; box-shadow: inset 0 0 0 2px #b6cf9a; }
    .hwx-tasks li i::after { content: ''; position: absolute; left: .38rem; top: .16rem; width: .34rem; height: .62rem; border: solid #fff; border-width: 0 .17rem .17rem 0; transform: rotate(45deg) scale(0); }
    .hwx-tasks li em { flex: none; font-style: normal; font-size: .64rem; font-weight: 900; color: #4a7c2a; opacity: 0; }
    .hwx.is-live .hwx-tasks li { animation: hwxRow 9s var(--ease) infinite; animation-delay: calc(.6s + var(--k) * 1.1s); }
    .hwx.is-live .hwx-tasks li i { animation: hwxBox 9s var(--ease) infinite; animation-delay: calc(.6s + var(--k) * 1.1s); }
    .hwx.is-live .hwx-tasks li i::after { animation: hwxTick 9s var(--ease) infinite; animation-delay: calc(.6s + var(--k) * 1.1s); }
    .hwx.is-live .hwx-tasks li em { animation: hwxDone 9s var(--ease) infinite; animation-delay: calc(.6s + var(--k) * 1.1s); }
    @keyframes hwxRow { 0%, 6% { background: #fff; } 10%, 82% { background: #f1f7ea; } 92%, 100% { background: #fff; } }
    @keyframes hwxBox { 0%, 6% { background: transparent; box-shadow: inset 0 0 0 2px #b6cf9a; } 10%, 82% { background: #4a7c2a; box-shadow: inset 0 0 0 2px #4a7c2a; } 92%, 100% { background: transparent; box-shadow: inset 0 0 0 2px #b6cf9a; } }
    @keyframes hwxTick { 0%, 6% { transform: rotate(45deg) scale(0); } 11%, 82% { transform: rotate(45deg) scale(1); } 92%, 100% { transform: rotate(45deg) scale(0); } }
    @keyframes hwxDone { 0%, 7% { opacity: 0; } 12%, 82% { opacity: 1; } 92%, 100% { opacity: 0; } }
    .hwx-mail { display: flex; align-items: center; gap: .4rem; margin-top: .7rem; font-size: .7rem; font-weight: 700; color: #5b6b4c; }
    /* The room the rain's word comes into: until then, Anee watching the sky. */
    .hwx-watch { position: absolute; left: .8rem; right: .8rem; bottom: .8rem; display: flex; align-items: center; gap: .5rem; height: 3.6rem; padding: 0 .8rem; border-radius: 1rem;
        font-size: .74rem; font-weight: 700; color: #5b6b4c; background: #f1f6ea; box-shadow: inset 0 0 0 1px #e0ebd3; }
    .hwx-watch i { flex: none; width: .55rem; height: .55rem; border-radius: 999px; background: #4a7c2a; }
    .hwx.is-live .hwx-watch i { animation: hwxBlink 1.6s ease-in-out infinite; }
    @keyframes hwxBlink { 50% { opacity: .25; transform: scale(.7); } }
    .hwx-toast { position: absolute; left: .8rem; right: .8rem; bottom: .8rem; display: flex; gap: .55rem; align-items: flex-start; padding: .7rem .75rem; border-radius: 1rem;
        color: #fff; background: #1f3512; box-shadow: 0 16px 30px -14px rgb(0 0 0 / .8), inset 0 0 0 1px rgb(245 197 24 / .5); opacity: 0; transform: translateY(120%); }
    .hwx-toast img { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; background: #eef6e4; }
    .hwx-toast p { margin: 0; font-size: .76rem; line-height: 1.45; color: #e3edd8; }
    .hwx-toast b { color: var(--acc); }
    .hwx.is-live .hwx-toast { animation: hwxToast 9s var(--ease) infinite; }
    @keyframes hwxToast { 0%, 48% { opacity: 0; transform: translateY(120%); } 54%, 86% { opacity: 1; transform: none; } 92%, 100% { opacity: 0; transform: translateY(120%); } }
    .hwx-demo-cap { font-size: .72rem; font-weight: 700; color: var(--soft); text-align: center; }

    /* The five steps: a line that fills as they come in. */
    .hwx-flow { position: relative; display: grid; gap: 1rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    .hwx-flow::before { content: ''; position: absolute; left: 1.05rem; top: 1rem; bottom: 1rem; width: 2px; background: rgb(168 204 126 / .2); }
    .hwx-flow::after { content: ''; position: absolute; left: 1.05rem; top: 1rem; bottom: 1rem; width: 2px; background: linear-gradient(180deg, var(--acc), var(--leaf)); transform-origin: top; transform: scaleY(0); transition: transform 1.6s var(--ease) .3s; }
    .hwx.is-in .hwx-flow::after { transform: none; }
    .hwx-flow li { position: relative; display: flex; gap: .85rem; align-items: flex-start; }
    .hwx-n { position: relative; z-index: 1; flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; font-weight: 900; color: #2b2100; background: var(--acc); box-shadow: 0 0 0 4px #13230c; }
    .hwx.is-live .hwx-flow li:last-child .hwx-n { animation: hwxRing 2.6s ease-out infinite; }
    @keyframes hwxRing { 0% { box-shadow: 0 0 0 4px #13230c, 0 0 0 4px rgb(245 197 24 / .6); } 100% { box-shadow: 0 0 0 4px #13230c, 0 0 0 16px rgb(245 197 24 / 0); } }
    .hwx-flow b { display: flex; align-items: center; gap: .4rem; font-size: 1rem; font-weight: 800; color: #fff; }
    .hwx-flow b i { font-style: normal; }
    .hwx-flow p { margin-top: .2rem; font-size: .86rem; line-height: 1.55; color: var(--mute); }

    /* What she does every day. */
    .hwx-feats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-top: 1rem; }
    @media (min-width: 900px) { .hwx-feats { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; } }
    .hwx-feat { display: grid; align-content: start; gap: .3rem; padding: .9rem .85rem; border-radius: 1.2rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08);
        transition: opacity .7s var(--ease) var(--d, 0ms), transform .45s var(--ease), background-color .3s var(--ease), box-shadow .3s var(--ease); }
    .hwx-feat:hover { transform: translateY(-3px); background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    .hwx-feat img { width: 2.5rem; height: 2.5rem; padding: .3rem; border-radius: .8rem; object-fit: contain; background: #f4f8ee; }
    .hwx-feat img.is-face { padding: 0; border-radius: 999px; }
    .hwx-feat b { margin-top: .25rem; font-size: .88rem; font-weight: 800; line-height: 1.3; color: #fff; }
    .hwx-feat span { font-size: .76rem; line-height: 1.45; color: var(--mute); }

    /* The same hectare, both ways. */
    .hwx-vs { margin-top: 2.6rem; }
    .hwx-vs-h { margin: .5rem auto 0; max-width: 36rem; text-align: center; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.25rem, 3.4vw, 1.75rem); line-height: 1.25; color: #fff; text-wrap: balance; }
    .hwx-vs-h em { font-style: normal; color: var(--acc); }
    .hwx-vs-g { display: grid; gap: .8rem; margin-top: 1.3rem; }
    @media (min-width: 768px) { .hwx-vs-g { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; } }
    .hwx-col { padding: 1rem; border-radius: 1.3rem; background: rgb(255 255 255 / .04); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .hwx-col.is-new { background: linear-gradient(160deg, rgb(245 197 24 / .1), rgb(245 197 24 / .03)); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    .hwx-col-h { display: flex; align-items: center; gap: .45rem; font-size: .92rem; font-weight: 800; color: #fff; }
    .hwx-col-h img { width: 1.6rem; height: 1.6rem; }
    .hwx-bar { display: flex; gap: 2px; height: 1.6rem; margin-top: .8rem; }
    .hwx-bar i { display: grid; place-items: center; flex: var(--w) 1 0%; min-width: 0; border-radius: .4rem; font-style: normal; font-size: .62rem; font-weight: 900; color: #1d330d;
        clip-path: inset(0 100% 0 0 round .4rem); transition: clip-path .7s var(--ease) calc(.3s + var(--k, 0) * .35s); }
    .hwx.is-in .hwx-bar i { clip-path: inset(0 0 0 0 round .4rem); }
    .hwx-bar i.keep { background: #8fc96a; }
    .hwx-bar i.lost { background: #b23d35 repeating-linear-gradient(135deg, rgb(255 255 255 / .16) 0 3px, transparent 3px 7px); }
    .hwx-bar i.gain { background: linear-gradient(180deg, #fde68a, #f5c518 55%, #e6ad12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); }
    .hwx-col ul { display: grid; gap: .3rem; margin: .8rem 0 0; padding: 0; list-style: none; }
    .hwx-col li { display: flex; align-items: center; gap: .5rem; font-size: .8rem; line-height: 1.35; color: var(--ink); }
    .hwx-col li span { flex: 1; min-width: 0; }
    .hwx-col li b { flex: none; font-weight: 900; font-variant-numeric: tabular-nums; }
    .hwx-col.is-old li b { color: #f19b93; } .hwx-col.is-new li b { color: var(--acc); }
    .hwx-tot { display: flex; align-items: baseline; justify-content: space-between; margin-top: .9rem; padding-top: .7rem; border-top: 1px solid rgb(255 255 255 / .08); }
    .hwx-tot span { font-size: .78rem; font-weight: 700; color: var(--mute); }
    .hwx-tot b { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
    .hwx-col.is-new .hwx-tot b { color: var(--acc); }
    .hwx-net { margin: 1rem auto 0; max-width: 40rem; text-align: center; font-size: .9rem; line-height: 1.6; color: var(--mute); }
    .hwx-net b { color: var(--acc); }
    .hwx-fine { margin: .5rem auto 0; max-width: 40rem; text-align: center; font-size: .7rem; line-height: 1.5; color: var(--soft); }

    /* The way in. */
    .hwx-cta { display: grid; justify-items: center; gap: .9rem; margin-top: 2.4rem; padding: 1.3rem; border-radius: 1.4rem; text-align: center; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); }
    @media (min-width: 768px) { .hwx-cta { grid-template-columns: auto minmax(0, 1fr) auto; justify-items: start; align-items: center; text-align: left; gap: 1.2rem; } }
    .hwx-cta img { width: 4rem; height: 4rem; }
    .hwx-cta b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #fff; }
    .hwx-cta span { display: block; margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: var(--mute); }
    .hwx-cta-b { display: flex; flex-wrap: wrap; gap: .55rem; justify-content: center; }

    @media (prefers-reduced-motion: reduce) {
        .hwx [data-x] { opacity: 1; transform: none; transition: none; }
        .hwx-glow, .hwx-badge, .hwx-watch i, .hwx-tasks li, .hwx-tasks li i, .hwx-tasks li i::after, .hwx-tasks li em, .hwx-n { animation: none !important; }
        .hwx-toast { animation: none !important; opacity: 1; transform: none; }
        .hwx-bar i { clip-path: none; transition: none; } .hwx-flow::after { transform: none; transition: none; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // Anee Auto's band shows itself once on screen (is-in) and plays its morning only while seen (is-live). The page's
    // tallies count up the first time they are seen (data-hw-count, the hero's and the band's).
    const band = document.getElementById('hw-auto-more');
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const count = (el) => {
        if (el.dataset.counted) return;
        el.dataset.counted = '1';
        const to = Number(el.dataset.hwCount) || 0, fmt = (v) => Math.round(v).toLocaleString('en-US');
        if (still || to < 2) { el.textContent = fmt(to); return; }
        const t0 = performance.now(), dur = Math.min(1500, 600 + to / 60);
        const step = (t) => { const k = Math.min(1, (t - t0) / dur); el.textContent = fmt(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); };
        requestAnimationFrame(step);
    };
    const seen = new IntersectionObserver((list) => list.forEach((en) => { if (en.isIntersecting) { count(en.target); seen.unobserve(en.target); } }), { threshold: 0.6 });
    document.querySelectorAll('[data-hw-count]').forEach((el) => seen.observe(el));
    if (!band) return;
    new IntersectionObserver((list) => list.forEach((en) => {
        if (en.isIntersecting && en.intersectionRatio > 0.08) band.classList.add('is-in');
        band.classList.toggle('is-live', en.isIntersecting);
    }), { threshold: [0, 0.08] }).observe(band);
})();
</script>
@endpush
@endonce
