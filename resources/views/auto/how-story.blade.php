{{-- Anee Auto, how it works (2026-10-28, the owner: "in the dashboard, we don't want the how it works when it
     expands, its too long ... when that button is clicked, show a modal box, a full screen modal explaining in an
     animated way what is anee auto. make it look great with graphics, graphs, animations. surprise me. it should
     look good, persuasive, but simple enough to easily understand").

     A story in partials/story-modal's shell, fetched the first time the card's How it works opens (route
     auto.how) and drawn once. Seven scenes: what she is, the three steps, the work moved around the rain (a
     chart), what she reads each morning, a season at a glance (a chart), why it is worth it (auto.partials.worth)
     and the start. The charts are examples and say so. The script is in auto.partials.card. --}}
@php
    $asPrice = \App\Support\AiPrices::of('auto');
    $asFace = fn (string $f) => \App\Support\AneeEmoji::url($f);
    // Ten days of an example forecast, rain in mm: the fertilizer planned on the 4th day meets 26 mm.
    $asRain = [0, 2, 0, 26, 18, 4, 0, 0, 12, 1];
    $asDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed'];
    $asRainMax = max($asRain);
    // An example rice season of 110 days: the stages, then the work on their days.
    $asStages = [['Seedling', 0, 20], ['Tillering', 20, 45], ['Panicle', 45, 65], ['Flowering', 65, 80], ['Ripening', 80, 110]];
    $asWork = [
        ['🧪', 'Fertilizer', [[0, 'At planting'], [14, 'Early tillering'], [42, 'Panicle starts'], [62, 'Booting']]],
        ['🐛', 'Scouting', [[10, ''], [24, ''], [38, ''], [52, ''], [66, ''], [80, '']]],
        ['🛡️', 'Sprays when needed', [[30, ''], [70, '']]],
        ['💧', 'Water', [[0, 'Keep 3 cm'], [35, 'Dry a few days'], [95, 'Drain']]],
        ['🌾', 'Harvest', [[110, 'Day 110']]],
    ];
    $asInputs = [['🌦️', 'The forecast', 'Rain, wind and heat for your farm'], ['🌱', 'Each lot\'s stage', 'Counted from the day it was planted'],
        ['👷', 'Your crew\'s days', 'Who works when, half days too'], ['📦', 'The shed', 'What is in stock, what to buy'],
        ['📸', 'Your reports', 'Photos, notes and voice notes'], ['🐛', 'Pests near you', 'What the season and the area bring'],
        ['💰', 'Prices at harvest', 'When it pays to sell']];
@endphp
<style>
    .acs { --e: cubic-bezier(.22,1,.36,1); }
    .acs-card { border-radius: 1.3rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); }

    /* ---------- 1. Anee, and her morning ---------- */
    .acs-hero { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 900px) { .acs-hero { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 3rem; } }
    .acs-me { position: relative; display: block; width: 5.2rem; height: 5.2rem; margin-bottom: .6rem; border-radius: 999px; padding: .3rem; background: rgb(255 255 255 / .08); animation: acsBob 3.4s ease-in-out infinite; }
    .acs-me img { width: 100%; height: 100%; object-fit: contain; }
    .acs-me::after { content: ''; position: absolute; inset: -8px; border-radius: 999px; border: 2px solid rgb(245 197 24 / .4); animation: acsRing 2.6s var(--e) infinite; }
    @keyframes acsBob { 50% { transform: translateY(-5px) rotate(-4deg); } }
    @keyframes acsRing { from { transform: scale(.9); opacity: 1; } to { transform: scale(1.35); opacity: 0; } }
    .acs-chips { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: 1.2rem; }
    .acs-chips span { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #eaf3df; background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    .acs-phone { width: min(100%, 19rem); margin: 0 auto; padding: .6rem; border-radius: 2rem; background: #070d04; box-shadow: 0 30px 60px -30px rgb(0 0 0 / .9), inset 0 0 0 1px rgb(255 255 255 / .12); }
    .acs-screen { display: grid; gap: .5rem; align-content: start; min-height: 21rem; padding: .8rem .65rem; border-radius: 1.5rem; background: #f6f9f1; }
    .acs-top { display: flex; align-items: center; gap: .45rem; padding-bottom: .4rem; border-bottom: 1px solid #e3eadb; font-size: .72rem; font-weight: 800; color: #1f2a17; }
    .acs-top img { width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #e4efd4; }
    .acs-top small { display: block; font-size: .6rem; font-weight: 700; color: #4a7c2a; }
    .acs-b { display: flex; gap: .4rem; align-items: flex-end; opacity: 0; transform: translateY(10px) scale(.96); }
    .stm-scene.is-in .acs-b { animation: acsPop .5s var(--e) forwards; animation-delay: calc(var(--k) * .95s + .5s); }
    @keyframes acsPop { to { opacity: 1; transform: none; } }
    .acs-b img { flex: none; width: 1.55rem; height: 1.55rem; border-radius: 999px; background: #e4efd4; }
    .acs-b div { padding: .45rem .6rem; border-radius: .9rem .9rem .9rem .25rem; background: #fff; border: 1px solid #e3eadb; font-size: .72rem; line-height: 1.42; color: #1f2a17; }
    .acs-b.is-me { justify-content: flex-end; }
    .acs-b.is-me div { background: #e7f2da; border-color: #cfe3b8; border-radius: .9rem .9rem .25rem .9rem; }
    .acs-t { display: flex; gap: .35rem; align-items: center; margin-top: .25rem; padding: .32rem .45rem; border-radius: .6rem; background: #f3f8ec; font-size: .66rem; font-weight: 700; color: #1f2a17; }
    .acs-t em { margin-left: auto; padding: 0 .35rem; border-radius: 999px; font-style: normal; font-size: .58rem; font-weight: 800; color: #8a5a00; background: #fff4d6; }

    /* ---------- 2. three steps ---------- */
    .acs-steps { display: grid; gap: .8rem; margin-top: 1.6rem; }
    @media (min-width: 900px) { .acs-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; } }
    .acs-step { position: relative; display: grid; gap: .5rem; padding: 1.1rem; }
    .acs-step > b { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #fff; }
    .acs-step > p { font-size: .84rem; line-height: 1.55; color: var(--mute); }
    .acs-n { position: absolute; right: 1rem; top: .9rem; width: 1.9rem; height: 1.9rem; border-radius: 999px; display: grid; place-items: center; font-weight: 900; font-size: .85rem; color: #1d330d; background: var(--gold); }
    .acs-art { height: 8.5rem; border-radius: 1rem; background: rgb(0 0 0 / .22); display: grid; place-items: center; overflow: hidden; }
    /* a lot drawn, its facts landing on it */
    .acs-lot { position: relative; width: 12rem; height: 7rem; }
    .acs-lot svg { width: 100%; height: 100%; }
    .acs-lot path { fill: rgb(168 204 126 / .12); stroke: var(--leaf); stroke-width: 2; stroke-dasharray: 1; stroke-dashoffset: 1; }
    .stm-scene.is-in .acs-lot path { animation: acsDraw 1.6s .3s var(--e) forwards; }
    @keyframes acsDraw { to { stroke-dashoffset: 0; } }
    .acs-lot span { position: absolute; translate: -50% -50%; white-space: nowrap; padding: .15rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; color: #1d330d; background: var(--leaf);
        box-shadow: 0 4px 10px -4px rgb(0 0 0 / .6); opacity: 0; transform: scale(.6); }
    .acs-lot span.is-farm { padding: .22rem .65rem; font-size: .7rem; background: var(--gold); box-shadow: 0 0 0 3px rgb(245 197 24 / .25), 0 0 18px rgb(245 197 24 / .45); }
    .stm-scene.is-in .acs-lot span { animation: acsPop .45s var(--e) forwards; animation-delay: calc(1.3s + var(--k) * .22s); }
    /* a month of tasks filling in */
    .acs-cal { display: grid; grid-template-columns: repeat(7, 1.15rem); gap: .3rem; }
    .acs-cal i { width: 1.15rem; height: 1.15rem; border-radius: .3rem; background: rgb(255 255 255 / .07); }
    .acs-cal i.on { background: rgb(255 255 255 / .07); }
    .stm-scene.is-in .acs-cal i.on { animation: acsFill .4s var(--e) forwards; animation-delay: calc(.4s + var(--k) * 70ms); }
    @keyframes acsFill { to { background: var(--c); transform: scale(1.06); } }
    /* this morning's card sliding in */
    .acs-note { width: 13rem; padding: .6rem .7rem; border-radius: .9rem; background: #f6f9f1; color: #1f2a17; box-shadow: 0 14px 30px -16px rgb(0 0 0 / .9); opacity: 0; transform: translateY(-14px); }
    .stm-scene.is-in .acs-note { animation: acsPop .55s 1s var(--e) forwards; }
    .acs-note small { display: flex; align-items: center; gap: .35rem; font-size: .6rem; font-weight: 800; color: #4a7c2a; }
    .acs-note small img { width: 1rem; height: 1rem; border-radius: 999px; }
    .acs-note b { display: block; margin-top: .2rem; font-size: .76rem; }
    .acs-note div { margin-top: .25rem; font-size: .66rem; line-height: 1.45; color: #4b5563; }

    /* ---------- 3. the rain chart ---------- */
    .acs-rain { position: relative; margin-top: 1.6rem; padding: 5.2rem 1rem 1rem; }
    .acs-rain-u { position: absolute; right: 1rem; top: .9rem; font-size: .62rem; font-weight: 800; color: #9fd0f5; }
    .acs-rain-g { position: relative; display: grid; grid-template-columns: repeat(10, minmax(0, 1fr)); gap: .35rem; align-items: end; height: 10rem; border-bottom: 1px solid rgb(255 255 255 / .12); }
    .acs-rain-g > div { position: relative; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; }
    .acs-rain-g i { display: block; width: min(100%, 2.2rem); border-radius: .4rem .4rem 0 0; background: linear-gradient(180deg, #7cc4f5, #3b82c4); transform-origin: bottom; transform: scaleY(0); transition: transform .9s var(--e) var(--d); min-height: 2px; }
    .stm-scene.is-in .acs-rain-g i { transform: none; }
    .acs-rain-g em { position: absolute; bottom: calc(var(--h) + 4px); font-style: normal; font-size: .62rem; font-weight: 800; color: #9fd0f5; opacity: 0; transition: opacity .4s var(--d); }
    .stm-scene.is-in .acs-rain-g em { opacity: 1; }
    .acs-rain-x { display: grid; grid-template-columns: repeat(10, minmax(0, 1fr)); gap: .35rem; margin-top: .35rem; text-align: center; font-size: .66rem; font-weight: 800; color: var(--mute); }
    .acs-rain-x span.is-wet { color: #9fd0f5; }
    /* The task: planned on the wet day, then moved to the dry morning before it. */
    /* Column i of the ten starts at 1rem + i * (the row + one gap) / 10. */
    .acs-task { position: absolute; top: .9rem; left: calc(1rem + (100% - 1.65rem) * 3 / 10); width: calc((100% - 5.15rem) / 10); display: flex; justify-content: center; }
    .acs-task span { white-space: nowrap; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #1d330d; background: var(--gold); box-shadow: 0 8px 18px -8px rgb(245 197 24 / .9); }
    .stm-scene.is-in .acs-task:not(.is-ghost) { animation: acsMove 1.1s 2.2s var(--e) forwards; }
    @keyframes acsMove { 0% { left: calc(1rem + (100% - 1.65rem) * 3 / 10); top: .9rem; } 45% { top: .1rem; } 100% { left: calc(1rem + (100% - 1.65rem) * 2 / 10); top: .9rem; } }
    .acs-task.is-ghost { opacity: 0; top: 2.6rem; }
    .stm-scene.is-in .acs-task.is-ghost { animation: acsGhost 2.6s 2.2s ease forwards; }
    .acs-task.is-ghost span { background: transparent; color: var(--gold); box-shadow: inset 0 0 0 1.5px rgb(245 197 24 / .6); }
    @keyframes acsGhost { 0% { opacity: 0; } 30% { opacity: .9; } 100% { opacity: .55; } }
    .acs-say { display: flex; gap: .7rem; align-items: flex-start; margin-top: 1rem; padding: .85rem 1rem; border-radius: 1rem; background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .acs-say img { flex: none; width: 2.4rem; height: 2.4rem; }
    .acs-say p { font-size: .86rem; line-height: 1.5; color: var(--ink); }

    /* ---------- 4. what she reads ---------- */
    .acs-orbit { display: grid; gap: 1.2rem; margin-top: 1.6rem; align-items: center; }
    @media (min-width: 900px) { .acs-orbit { grid-template-columns: minmax(0, 1.2fr) auto minmax(0, .8fr); gap: 1.6rem; } }
    .acs-ins { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .acs-in { display: flex; gap: .55rem; align-items: center; padding: .65rem .7rem; border-radius: 1rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .acs-in > span { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.15rem; background: rgb(168 204 126 / .12); }
    .acs-in b { display: block; font-size: .8rem; font-weight: 800; color: #fff; line-height: 1.25; }
    .acs-in small { display: block; font-size: .68rem; line-height: 1.35; color: var(--mute); }
    .acs-in:last-child:nth-child(odd) { grid-column: 1 / -1; }
    .acs-mid { position: relative; justify-self: center; width: 7rem; height: 7rem; border-radius: 999px; display: grid; place-items: center; background: radial-gradient(circle, rgb(245 197 24 / .25), transparent 70%); }
    .acs-mid img { width: 4.6rem; height: 4.6rem; }
    .acs-mid::before, .acs-mid::after { content: ''; position: absolute; inset: 0; border-radius: 999px; border: 2px dashed rgb(245 197 24 / .45); animation: acsSpin 18s linear infinite; }
    .acs-mid::after { inset: -14px; border-color: rgb(168 204 126 / .3); animation-direction: reverse; animation-duration: 26s; }
    @keyframes acsSpin { to { transform: rotate(360deg); } }
    .acs-out { padding: 1rem; border-radius: 1.2rem; background: #f6f9f1; color: #1f2a17; box-shadow: 0 24px 48px -26px rgb(0 0 0 / .9); }
    .acs-out > small { display: flex; align-items: center; gap: .4rem; font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .acs-out > b { display: block; margin-top: .25rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; }
    .acs-out .acs-t { font-size: .74rem; padding: .45rem .55rem; }

    /* ---------- 5. the season chart ---------- */
    .acs-gantt { margin-top: 1.6rem; padding: 1rem; overflow: hidden; }
    .acs-g-row { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr); gap: .6rem; align-items: center; min-height: 2.1rem; }
    @media (max-width: 560px) { .acs-g-row { grid-template-columns: 2.2rem minmax(0, 1fr); } .acs-g-row > span b { display: none; } }
    .acs-g-row + .acs-g-row { border-top: 1px solid rgb(255 255 255 / .06); }
    .acs-g-row > span { display: flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 800; color: #fff; }
    .acs-g-row > span i { font-style: normal; font-size: 1rem; }
    .acs-g-track { position: relative; height: 1.6rem; }
    .acs-g-st { position: absolute; top: .15rem; bottom: .15rem; left: calc(var(--a) * 100%); width: calc(var(--w) * 100%); border-radius: .4rem; background: var(--c); display: grid; place-items: center;
        font-size: .6rem; font-weight: 800; color: #10200a; overflow: hidden; white-space: nowrap; transform-origin: left; transform: scaleX(0); transition: transform .7s var(--e) var(--d); }
    .stm-scene.is-in .acs-g-st { transform: none; }
    .acs-g-dot { position: absolute; top: 50%; left: calc(var(--a) * 100%); width: .8rem; height: .8rem; margin: -.4rem 0 0 -.4rem; border-radius: 999px; background: var(--c, var(--gold)); box-shadow: 0 0 0 3px rgb(11 20 7 / .9);
        opacity: 0; transform: scale(.3); transition: opacity .35s var(--d), transform .45s var(--e) var(--d); }
    .stm-scene.is-in .acs-g-dot { opacity: 1; transform: none; }
    .acs-g-dot[data-tip]:hover::after { content: attr(data-tip); position: absolute; left: 50%; bottom: 1.2rem; transform: translateX(-50%); white-space: nowrap; padding: .2rem .5rem; border-radius: .5rem; font-size: .64rem; font-weight: 800; color: #1d330d; background: var(--gold); }
    .acs-g-axis { position: relative; height: 1.2rem; margin-top: .3rem; }
    .acs-g-axis span { position: absolute; transform: translateX(-50%); white-space: nowrap; font-size: .62rem; font-weight: 800; color: var(--mute); }
    .acs-g-axis span:first-child { transform: none; } .acs-g-axis span:last-child { transform: translateX(-100%); }
    .acs-gantt { --lc: 8.1rem; }
    @media (max-width: 560px) { .acs-gantt { --lc: 2.8rem; } }
    .acs-g-now { position: absolute; top: 0; bottom: 0; left: var(--lc); width: 2px; background: var(--gold); box-shadow: 0 0 10px var(--gold); opacity: 0; }
    .stm-scene.is-in .acs-g-now { animation: acsSweep 5s 1.4s linear forwards; }
    @keyframes acsSweep { 0% { left: var(--lc); opacity: 1; } 95% { opacity: 1; } 100% { left: 100%; opacity: 0; } }
    .acs-g-body { position: relative; }

    /* ---------- 7. start ---------- */
    .acs-start { display: grid; gap: 1.2rem; align-items: center; padding: 1.4rem; border-radius: 1.6rem; background: linear-gradient(135deg, rgb(245 197 24 / .18), rgb(106 163 58 / .14)); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    @media (min-width: 900px) { .acs-start { grid-template-columns: auto minmax(0, 1fr) auto; padding: 2rem; gap: 1.6rem; } }
    .acs-start > img { width: 5.5rem; height: 5.5rem; animation: acsBob 3.4s ease-in-out infinite; }
    .acs-price { font-family: var(--font-heading); font-size: clamp(1.5rem, 4vw, 2.2rem); font-weight: 800; line-height: 1.1; color: #fff; }
    .acs-price small { display: block; margin-top: .3rem; font-family: inherit; font-size: .9rem; font-weight: 700; color: var(--mute); }
    .acs-go { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .95rem 1.5rem; border-radius: 999px; font-size: 1rem; font-weight: 800;
        color: #1d330d; background: var(--gold); box-shadow: 0 16px 34px -16px rgb(245 197 24 / .9); transition: transform .28s var(--e); }
    .acs-go:hover { transform: translateY(-2px); }
    .acs-go::after { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 30%, rgb(255 255 255 / .55) 50%, transparent 70%); transform: translateX(-100%); animation: acsShine 3.2s ease-in-out infinite; }
    @keyframes acsShine { 55%, 100% { transform: translateX(100%); } }
    .acs-go svg { width: 1.1rem; height: 1.1rem; }

    @media (prefers-reduced-motion: reduce) {
        .acs-me, .acs-me::after, .acs-mid::before, .acs-mid::after, .acs-start > img, .acs-go::after { animation: none !important; }
        .acs-b, .acs-lot span, .acs-note { opacity: 1; transform: none; animation: none !important; }
        .acs-lot path { stroke-dashoffset: 0; animation: none !important; }
        .acs-cal i.on { background: var(--c); animation: none !important; }
        .acs-rain-g i, .acs-g-st, .acs-g-dot, .acs-rain-g em { transition: none !important; }
        .acs-task:not(.is-ghost) { animation: none !important; left: calc(1rem + (100% - 1.65rem) * 2 / 10); }
        .acs-task.is-ghost { animation: none !important; opacity: .55; }
        .acs-g-now { display: none; }
    }
</style>

<div class="acs">
    {{-- 1. Anee, and her morning --}}
    <section class="stm-scene" data-title="Anee Auto">
        <div class="stm-wrap acs-hero">
            <div>
                <span class="acs-me" data-in><img src="{{ $asFace('wave') }}" alt=""></span>
                <span class="stm-kick" data-in style="--d: 60ms">Anee Auto</span>
                <h2 class="stm-h" data-in style="--d: 120ms">Anee plans your season. <em>You just follow.</em></h2>
                <p class="stm-p" data-in style="--d: 200ms">Tell her about your farm once. She writes every task of the season for each lot, tells you and your crew what to do each morning, and moves the work when the rain or the crew changes.</p>
                <div class="acs-chips" data-in style="--d: 280ms"><span>📅 The whole season, written</span><span>🌦️ Moved around the rain</span><span>👷 Your crew told each morning</span></div>
                <button type="button" class="stm-next" data-stm-next data-in style="--d: 360ms">See how she does it
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-6-6l6 6 6-6"/></svg></button>
            </div>
            <div class="acs-phone" aria-hidden="true" data-in style="--d: 200ms">
                <div class="acs-screen">
                    <div class="acs-top"><img src="{{ $asFace('salute') }}" alt=""><div>Anee<small>Your season, today</small></div></div>
                    <div class="acs-b" style="--k: 0"><img src="{{ $asFace('wave') }}" alt=""><div>Magandang umaga! Rain after lunch, so we fertilize Lot 1 this morning.</div></div>
                    <div class="acs-b" style="--k: 1"><img src="{{ $asFace('salute') }}" alt=""><div><b>Today</b>
                        <div class="acs-t">🧪 Second fertilizer, Lot 1<em>Now</em></div>
                        <div class="acs-t">💧 Keep 3 cm of water, Lot 2</div></div></div>
                    <div class="acs-b is-me" style="--k: 2"><div>No workers tomorrow 🙅</div></div>
                    <div class="acs-b" style="--k: 3"><img src="{{ $asFace('thumbsup') }}" alt=""><div>Moved to Thursday, the next dry morning. 👍</div></div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Three steps --}}
    <section class="stm-scene" data-title="How it starts">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Three steps, then she takes over</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">You answer once. She does the planning every day after.</h2>
            <div class="acs-steps">
                <div class="acs-card acs-step" data-in style="--d: 160ms">
                    <span class="acs-n">1</span>
                    <div class="acs-art" aria-hidden="true"><div class="acs-lot">
                        <svg viewBox="0 0 200 116"><path pathLength="1" d="M28 18 L150 8 L182 70 L120 106 L22 92 Z"/></svg>
                        {{-- Each fact on a corner of the lot, the farm in the middle (2026-10-28, the owner). The corners are the path's points. --}}
                        <span style="left: 14%; top: 15.5%; --k: 0">🟤 Soil</span><span style="left: 75%; top: 6.9%; --k: 1">🌾 Variety</span><span style="left: 91%; top: 60.3%; --k: 2">💧 Water</span><span style="left: 60%; top: 91.4%; --k: 3">👷 Crew</span><span style="left: 11%; top: 79.3%; --k: 4">🌦️ Weather</span><span class="is-farm" style="left: 50%; top: 50%; --k: 5">🏡 Your farm</span>
                    </div></div>
                    <b>Tell her once</b>
                    <p>Your lots, the crop and variety, the soil, the water, your fertilizer and your workers. The weather she reads herself.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 260ms">
                    <span class="acs-n">2</span>
                    <div class="acs-art" aria-hidden="true"><div class="acs-cal">
                        @php $asCal = [0 => '#f5c518', 3 => '#a8cc7e', 5 => '#7cc4f5', 8 => '#a8cc7e', 10 => '#f5c518', 12 => '#7cc4f5', 15 => '#a8cc7e', 17 => '#e98b5d', 19 => '#7cc4f5', 22 => '#f5c518', 24 => '#a8cc7e', 26 => '#7cc4f5']; $asK = 0; @endphp
                        @for ($c = 0; $c < 28; $c++)
                            @if (isset($asCal[$c]))<i class="on" style="--c: {{ $asCal[$c] }}; --k: {{ $asK++ }}"></i>@else<i></i>@endif
                        @endfor
                    </div></div>
                    <b>She writes the season</b>
                    <p>Every task on its day for each lot: land prep, planting, each fertilizer, the water, the checks and the harvest.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 360ms">
                    <span class="acs-n">3</span>
                    <div class="acs-art" aria-hidden="true"><div class="acs-note"><small><img src="{{ $asFace('salute') }}" alt="">Anee · 5:30 AM</small><b>Today: 2 tasks on 2 lots</b><div>Lot 1: second fertilizer before the rain. Lot 2: check for stem borers.</div></div></div>
                    <b>Every morning, today's work</b>
                    <p>In the app and by email, for you and for each worker. Tell her what happened and she adjusts.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. The work moved around the rain --}}
    <section class="stm-scene" data-title="Around the rain">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>She keeps it on track</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Rain coming on a fertilizer day? She moves the work, not you.</h2>
            <div class="acs-card acs-rain" data-in style="--d: 160ms" role="img" aria-label="An example ten day forecast. The fertilizer planned on Thursday, with 26 mm of rain, moves to Wednesday morning, which is dry.">
                <div class="acs-task is-ghost"><span>Planned</span></div>
                <div class="acs-task"><span>🧪 Fertilizer</span></div>
                <span class="acs-rain-u">🌧️ Rain, mm</span>
                <div class="acs-rain-g">
                    @foreach ($asRain as $i => $mm)
                        @php $h = $asRainMax ? round($mm / $asRainMax * 100) : 0; @endphp
                        <div><em style="--h: {{ max(2, $h) }}%; --d: {{ 400 + $i * 70 }}ms">{{ $mm ?: '' }}</em><i style="height: {{ max(2, $h) }}%; --d: {{ 300 + $i * 70 }}ms"></i></div>
                    @endforeach
                </div>
                <div class="acs-rain-x">@foreach ($asDays as $i => $d)<span class="{{ $asRain[$i] >= 10 ? 'is-wet' : '' }}">{{ $asRain[$i] >= 10 ? '🌧️' : '☀️' }}<br>{{ $d }}</span>@endforeach</div>
            </div>
            <div class="acs-say" data-in style="--d: 260ms"><img src="{{ $asFace('thinking') }}" alt=""><p><b>Fertilizer on Thursday would wash away in 26 mm of rain.</b> Wednesday morning is dry, and the crop is at the same stage, so she moves it there, tells the crew, and the days after it follow. The same when a worker cannot come.</p></div>
            <p class="stm-fine" data-in style="--d: 320ms">An example forecast. Every morning she reads the real one for your farm.</p>
        </div>
    </section>

    {{-- 4. What she reads each morning --}}
    <section class="stm-scene" data-title="What she reads">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Every morning, before you wake up</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">She weighs everything a good farm technician would, for every lot.</h2>
            <div class="acs-orbit">
                <div class="acs-ins">
                    @foreach ($asInputs as $i => [$emo, $t, $l])
                        <div class="acs-in" data-in style="--d: {{ 140 + $i * 70 }}ms"><span>{{ $emo }}</span><div><b>{{ $t }}</b><small>{{ $l }}</small></div></div>
                    @endforeach
                </div>
                <div class="acs-mid" data-in style="--d: 600ms" aria-hidden="true"><img src="{{ $asFace('thinking') }}" alt=""></div>
                <div class="acs-out" data-in style="--d: 760ms">
                    <small><img src="{{ $asFace('salute') }}" alt="" style="width:1.1rem;height:1.1rem">This morning's plan</small>
                    <b>3 tasks, in the right order</b>
                    <div class="acs-t">🧪 Lot 1: fertilize before 10 AM<em>Rain later</em></div>
                    <div class="acs-t">🔍 Lot 2: check for stem borers</div>
                    <div class="acs-t">🛒 Buy 2 bags of urea for Friday</div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. The season at a glance --}}
    <section class="stm-scene" data-title="The season">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Your season at a glance</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Every task on its day, timed to the crop's stage.</h2>
            <div class="acs-card acs-gantt" data-in style="--d: 160ms" role="img" aria-label="An example rice season of 110 days: the growth stages, the fertilizer at planting, early tillering, panicle and booting, the checks every two weeks, the water and the harvest on day 110.">
                <div class="acs-g-body">
                    <div class="acs-g-row"><span><i>🌱</i><b>Stages</b></span><div class="acs-g-track">
                        @foreach ($asStages as $i => [$n, $a, $b])
                            <span class="acs-g-st" style="--a: {{ $a / 110 }}; --w: {{ ($b - $a) / 110 }}; --c: {{ ['#d6ebbf', '#b5d98f', '#8fc96a', '#f0cf5c', '#e0a94a'][$i] }}; --d: {{ 300 + $i * 160 }}ms">{{ $n }}</span>
                        @endforeach
                    </div></div>
                    @foreach ($asWork as $r => [$emo, $label, $marks])
                        <div class="acs-g-row"><span><i>{{ $emo }}</i><b>{{ $label }}</b></span><div class="acs-g-track">
                            @foreach ($marks as $j => [$day, $tip])
                                <span class="acs-g-dot" style="--a: {{ min(1, $day / 110) }}; --c: {{ ['#f5c518', '#e98b5d', '#c084fc', '#7cc4f5', '#a8cc7e'][$r] }}; --d: {{ 1100 + $r * 180 + $j * 90 }}ms" @if ($tip !== '') data-tip="{{ $tip }}" @endif></span>
                            @endforeach
                        </div></div>
                    @endforeach
                    <span class="acs-g-now" aria-hidden="true"></span>
                </div>
                <div class="acs-g-row"><span></span><div class="acs-g-axis">@foreach ([0, 30, 60, 90, 110] as $d)<span style="left: {{ $d / 110 * 100 }}%">Day {{ $d }}</span>@endforeach</div></div>
            </div>
            <p class="stm-fine" data-in style="--d: 260ms">An example rice season. Yours is written for your crop, your variety, your lots and your weather, and changes as the season does.</p>
        </div>
    </section>

    {{-- 6. Why it is worth it --}}
    <section class="stm-scene" data-title="Worth it">
        <div class="stm-wrap" data-in style="container-type: inline-size">
            @include('auto.partials.worth')
        </div>
    </section>

    {{-- 7. Start --}}
    <section class="stm-scene" data-title="Start">
        <div class="stm-wrap">
            <div class="acs-start" data-in>
                <img src="{{ $asFace('salute') }}" alt="">
                <div>
                    <span class="stm-kick">Ready when you are</span>
                    <p class="acs-price">{{ number_format($asPrice) }} credits a lot<small>For the whole season, from the plan to the harvest. Anee says the exact price before you start.</small></p>
                </div>
                <button type="button" class="acs-go" data-acs-start>Start Anee Auto
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></button>
            </div>
        </div>
    </section>
</div>
