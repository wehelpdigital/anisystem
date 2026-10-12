{{-- ANEE AUTO on the homepage (2026-10-12, the owner): a section of its own,
     for the farmer who does not want to build schedules and run analyses:
     answer once, then just follow what Anee says. How it works in five steps,
     a phone with her morning in it, and the door to sign up. No prices here.
     Expects $signup. --}}
@php $aaFace = \App\Support\AneeEmoji::url('salute'); $aaSmile = \App\Support\AneeEmoji::url('happy'); @endphp
<section class="hp-sec hp-auto" id="anee-auto" aria-labelledby="hpAutoH">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="hp-head reveal">
            <p class="hp-kick"><span class="hp-auto-new">New</span> Anee Auto</p>
            <h2 class="hp-h2" id="hpAutoH">Do Not Want Farming to Get Complicated? <em>Just Follow Anee.</em></h2>
            <p class="hp-p">Building schedules, running analyses, checking the weather every morning, timing the fertilizer: not everyone has the time for it, and nobody should have to guess. With Anee Auto you answer a few questions about your farm once. Anee works out the whole season, tells you what to do each day, and changes the plan herself when the rain comes or a worker cannot.</p>
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

            <div class="hp-auto-phone reveal" aria-hidden="true">
                <div class="hp-auto-screen">
                    <div class="hp-auto-bar"><img src="{{ $aaFace }}" alt=""><span><b>Anee Auto</b><small>Lot 1 · DAT 21 · Palay</small></span></div>
                    <div class="hp-auto-b" style="--d:0"><img src="{{ $aaSmile }}" alt=""><div>Magandang umaga! Rain is likely this afternoon, so we put the fertilizer on this morning.</div></div>
                    <div class="hp-auto-b" style="--d:1"><img src="{{ $aaSmile }}" alt=""><div><b>Today</b>
                        <span class="hp-auto-t"><i>🧪</i>Second fertilizer, Lot 1<em>Cannot wait</em></span>
                        <span class="hp-auto-t"><i>💧</i>Keep 3 cm of water</span>
                        <span class="hp-auto-t"><i>👀</i>Walk the field for stem borer</span></div></div>
                    <div class="hp-auto-b is-me" style="--d:2"><div>🙅 Can't spray tomorrow, no workers</div></div>
                    <div class="hp-auto-b" style="--d:3"><img src="{{ $aaSmile }}" alt=""><div>No problem. I moved it to Thursday, the next dry morning with two hands free. 👍</div></div>
                </div>
            </div>
        </div>

        <div class="hp-auto-cta reveal">
            <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Let Anee plan my season</a>
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
    .hp-auto-new { display: inline-block; margin-right: .35rem; padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; letter-spacing: .06em; color: #2b3a1c; background: #f5c518; }
    .hp-auto-skip { display: flex; flex-wrap: wrap; justify-content: center; gap: .45rem; margin: 1.3rem 0 0; padding: 0; list-style: none; }
    .hp-auto-skip li { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .8rem; border-radius: 999px; font-size: .84rem; font-weight: 700; color: #4b3a00;
        background: #fff; border: 1px solid #f1df9c; box-shadow: 0 6px 14px -12px rgb(120 90 0 / .6); }
    .hp-auto-skip li::before { content: ''; width: .95rem; height: .95rem; flex: none; border-radius: 999px; background: #4a7c2a url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center / 70% no-repeat; }
    .hp-auto-grid { display: grid; gap: 2.5rem; align-items: center; margin-top: 2.8rem; }
    @media (min-width: 1024px) { .hp-auto-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 3.5rem; } }
    .hp-auto-steps { display: grid; gap: .75rem; margin: 0; padding: 0; list-style: none; counter-reset: none; }
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
    /* The phone with her morning, the messages arriving one by one. */
    .hp-auto-phone { width: min(100%, 20rem); margin: 0 auto; padding: .75rem; border-radius: 2.3rem; background: #10180b; box-shadow: 0 40px 80px -36px rgb(30 45 15 / .75), inset 0 0 0 1px rgb(255 255 255 / .1);
        animation: hpAutoFloat 7s ease-in-out infinite; }
    @keyframes hpAutoFloat { 50% { transform: translateY(-8px) rotate(-.6deg); } }
    .hp-auto-screen { min-height: 25rem; padding: .7rem .65rem; border-radius: 1.75rem; background: #f6f9f1; display: grid; align-content: start; gap: .55rem; }
    .hp-auto-bar { display: flex; align-items: center; gap: .5rem; padding: .3rem .25rem .6rem; border-bottom: 1px solid #e3eadb; }
    .hp-auto-bar img { width: 2rem; height: 2rem; border-radius: 999px; background: #e4efd4; }
    .hp-auto-bar b { display: block; font-size: .82rem; font-weight: 800; color: #1f2a17; }
    .hp-auto-bar small { display: block; font-size: .66rem; color: #6b7a5c; }
    .hp-auto-b { display: flex; gap: .4rem; align-items: flex-end; }
    .hp-auto-b img { flex: none; width: 1.7rem; height: 1.7rem; border-radius: 999px; background: #e4efd4; }
    .hp-auto-b > div { padding: .5rem .65rem; border-radius: .95rem .95rem .95rem .25rem; background: #fff; border: 1px solid #e3eadb; font-size: .74rem; line-height: 1.45; color: #1f2a17; }
    .hp-auto-b.is-me { justify-content: flex-end; }
    .hp-auto-b.is-me > div { background: #e7f2da; border-color: #cfe3b8; border-radius: .95rem .95rem .25rem .95rem; }
    .hp-auto-t { display: flex; align-items: center; gap: .35rem; margin-top: .3rem; padding: .35rem .45rem; border-radius: .6rem; background: #fbfcf8; border: 1px solid #e3eadb; font-size: .68rem; font-weight: 700; }
    .hp-auto-t i { font-style: normal; } .hp-auto-t em { margin-left: auto; font-style: normal; font-size: .58rem; font-weight: 800; color: #8a5a00; background: #fff4d6; padding: 0 .35rem; border-radius: 999px; }
    html.js .hp-auto-b { opacity: 0; translate: 0 10px; }
    html.js .hp-auto-phone.is-visible .hp-auto-b { animation: hpAutoPop .5s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(.5s + var(--d) * 1.1s); }
    @keyframes hpAutoPop { to { opacity: 1; translate: none; } }
    .hp-auto-cta { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .8rem 1.2rem; margin-top: 2.6rem; text-align: center; }
    .hp-auto-cta span { font-size: .92rem; font-weight: 700; color: #55604a; }
    @media (prefers-reduced-motion: reduce) {
        .hp-auto-phone { animation: none; }
        html.js .hp-auto-steps li, html.js .hp-auto-b { opacity: 1; translate: none; transition: none; animation: none; }
    }
</style>
@endonce
