{{-- Anee Auto, how it works (2026-10-28, the owner: "in the dashboard, we don't want the how it works when it
     expands, its too long ... when that button is clicked, show a modal box, a full screen modal explaining in an
     animated way what is anee auto. make it look great with graphics, graphs, animations. surprise me. it should
     look good, persuasive, but simple enough to easily understand").

     A story in partials/story-modal's shell, fetched the first time the card's How it works opens (route
     auto.how) and drawn once. Ten scenes: what she is, the five steps, when to plant, the short stave (Liebig's barrel), the work moved around a typhoon (an
     animated week), asking her about a photo, what she reads each morning, a season at a glance (a chart), why it is worth it (auto.partials.worth)
     and the start. The charts are examples and say so. The script is in auto.partials.card. --}}
@php
    $asPrice = \App\Support\AiPrices::of('auto');
    $asFace = fn (string $f) => \App\Support\AneeEmoji::url($f);
    // An example week from a stormy today: [day, sky, what it says, kind]. The fertilizer planned today moves to the
    // first day the storm has passed and the field has drained that is still in the crop's window (Saturday).
    $asWeek = [['Wed', '🌀', '120 km/h', 'storm'], ['Thu', '🌀', '95 km/h', 'storm'], ['Fri', '🌧️', '40 mm', 'wet'], ['Sat', '☀️', 'Dry', 'best'],
        ['Sun', '⛅', '6 mm', ''], ['Mon', '☀️', 'Dry', ''], ['Tue', '🌧️', '15 mm', 'wet']];
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
    /* the goal against the budget */
    .acs-goal { position: relative; width: min(100%, 16.5rem); display: grid; gap: .32rem; }
    .acs-goal-b { justify-self: start; margin-bottom: .15rem; padding: .15rem .55rem; border-radius: 999px; font-size: .62rem; font-weight: 800; color: var(--ink); background: rgb(255 255 255 / .08); opacity: 0; }
    .acs-goal-b b { color: var(--gold); }
    .stm-scene.is-in .acs-goal-b { animation: acsPop .45s .3s var(--e) forwards; }
    .acs-goal-r { position: relative; display: grid; grid-template-columns: 4.6rem minmax(0, 1fr) 2.2rem 2.1rem; gap: .35rem; align-items: center; padding: .22rem .4rem; border-radius: .6rem; font-size: .6rem; font-weight: 800; color: var(--mute);
        transition: background-color .5s var(--e), box-shadow .5s var(--e), opacity .5s var(--e); }
    .acs-goal-r span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .acs-goal-r i { height: .45rem; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; }
    .acs-goal-r i u { display: block; height: 100%; width: calc(var(--v) * 100%); border-radius: inherit; background: linear-gradient(90deg, #a8cc7e, #f5c518); transform-origin: left; transform: scaleX(0);
        transition: transform 1s var(--e) calc(.6s + var(--k) * .25s); }
    .stm-scene.is-in .acs-goal-r i u { transform: none; }
    .acs-goal-r b { color: #fff; text-align: right; } .acs-goal-r small { font-size: .58rem; text-align: right; }
    .acs-goal-r em { position: absolute; font-style: normal; font-weight: 900; opacity: 0; transform: scale(.6); }
    .acs-goal-r em.ok { right: -.5rem; top: -.45rem; width: 1.1rem; height: 1.1rem; display: grid; place-items: center; border-radius: 999px; font-size: .6rem; color: #1d330d; background: var(--gold); }
    .acs-goal-r em.no { left: 5.3rem; top: 50%; margin-top: -.4rem; line-height: .8rem; padding: 0 .35rem; border-radius: 999px; font-size: .52rem; color: #fff; background: #c2453d; }
    .stm-scene.is-in .acs-goal-r em { animation: acsPop .4s 2.1s var(--e) forwards; }
    .stm-scene.is-in .acs-goal-r.is-pick { background: rgb(245 197 24 / .12); box-shadow: inset 0 0 0 1.5px rgb(245 197 24 / .6); color: #fff; transition-delay: 2s; }
    .stm-scene.is-in .acs-goal-r.is-over { opacity: .45; transition-delay: 2s; }
    /* the protocol written from what she weighs */
    .acs-proto { position: relative; width: min(100%, 15.6rem); display: grid; grid-template-columns: 4.6rem minmax(0, 1fr); gap: .5rem; align-items: center; }
    .acs-proto-in { display: grid; gap: .22rem; }
    .acs-proto-in span { justify-self: start; padding: .1rem .4rem; border-radius: 999px; white-space: nowrap; font-size: .56rem; font-weight: 800; color: #1d330d; background: var(--leaf); opacity: 0; transform: translateX(-14px); }
    .stm-scene.is-in .acs-proto-in span { animation: acsProtoIn 2.6s var(--e) forwards; animation-delay: calc(.3s + var(--k) * .18s); }
    @keyframes acsProtoIn { 0% { opacity: 0; transform: translateX(-14px); } 20%, 55% { opacity: 1; transform: none; } 80%, 100% { opacity: .35; transform: translateX(10px) scale(.92); } }
    .acs-proto-doc { position: relative; display: grid; gap: .2rem; padding: .45rem .5rem .5rem; border-radius: .6rem; color: #1f2a17; background: #f6f9f1; box-shadow: 0 12px 24px -14px rgb(0 0 0 / .9); opacity: 0; transform: translateY(8px); }
    .stm-scene.is-in .acs-proto-doc { animation: acsPop .5s .9s var(--e) forwards; }
    .acs-proto-doc small { font-size: .58rem; font-weight: 900; color: #4a7c2a; }
    .acs-proto-doc span { display: flex; align-items: center; gap: .25rem; min-width: 0; white-space: nowrap; overflow: hidden; font-size: .55rem; font-weight: 700; color: #374151; clip-path: inset(0 100% 0 0); }
    .stm-scene.is-in .acs-proto-doc span { animation: acsType .6s var(--e) forwards; animation-delay: calc(1.5s + var(--k) * .32s); }
    @keyframes acsType { to { clip-path: inset(0 0 0 0); } }
    .acs-proto-doc span i { flex: none; width: .75rem; height: .75rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .48rem; color: #fff; background: #4a7c2a; }
    /* A month of a season (2026-10-28, the owner: "make it animated with fading in and fading out of fertilizer,
       irrigation, etc. icons randomly"): each day's work shows on its day and fades, the days in season order
       (land prep and planting first, the harvest last), today ringed. */
    .acs-cal { display: grid; gap: .3rem; }
    .acs-wk, .acs-grid { display: grid; grid-template-columns: repeat(7, 1.5rem); gap: .3rem; }
    .acs-wk span { font-size: .56rem; font-weight: 900; text-align: center; color: rgb(185 202 168 / .65); }
    .acs-grid i { position: relative; width: 1.5rem; height: 1.5rem; border-radius: .4rem; background: rgb(255 255 255 / .06); }
    .acs-grid i b { position: absolute; inset: 0; display: grid; place-items: center; border-radius: inherit; font-size: .8rem; line-height: 1;
        background: color-mix(in srgb, var(--c) 32%, transparent); box-shadow: inset 0 0 0 1.5px var(--c); opacity: 0; transform: scale(.6); }
    .stm-scene.is-in .acs-grid i b { animation: acsTask var(--t) ease-in-out var(--w) infinite; }
    @keyframes acsTask { 0% { opacity: 0; transform: scale(.6); } 14%, 58% { opacity: 1; transform: scale(1); } 74%, 100% { opacity: 0; transform: scale(.85); } }
    .acs-grid i.today { box-shadow: 0 0 0 2px var(--gold); }
    .acs-grid i.today::after { content: ''; position: absolute; inset: -5px; border-radius: .6rem; border: 2px solid rgb(245 197 24 / .5); animation: acsRing 2s var(--e) infinite; }
    /* this morning's card sliding in */
    .acs-note { width: 13rem; padding: .6rem .7rem; border-radius: .9rem; background: #f6f9f1; color: #1f2a17; box-shadow: 0 14px 30px -16px rgb(0 0 0 / .9); opacity: 0; transform: translateY(-14px); }
    .stm-scene.is-in .acs-note { animation: acsPop .55s 1s var(--e) forwards; }
    .acs-note small { display: flex; align-items: center; gap: .35rem; font-size: .6rem; font-weight: 800; color: #4a7c2a; }
    .acs-note small img { width: 1rem; height: 1rem; border-radius: 999px; }
    .acs-note b { display: block; margin-top: .2rem; font-size: .76rem; }
    .acs-note div { margin-top: .25rem; font-size: .66rem; line-height: 1.45; color: #4b5563; }

    /* ===== 2026-10-28, the owner's second pass on the story: every step card moves on its own loop. ===== */
    /* The yellow numbers ripple like the shop's Coming soon. */
    .acs-n { box-shadow: 0 0 0 0 rgb(245 197 24 / .55); }
    .stm-scene.is-in .acs-n { animation: acsNRing 2.2s ease-out infinite; }
    @keyframes acsNRing { 0% { box-shadow: 0 0 0 0 rgb(245 197 24 / .6); } 70%, 100% { box-shadow: 0 0 0 12px rgb(245 197 24 / 0); } }
    /* 1. Tell her once: the answers fill in, each ticked, then the promise is stamped. */
    .acs-once { --cy: 8s; position: relative; width: min(100%, 14rem); display: grid; gap: .26rem; }
    .acs-once-r { display: grid; grid-template-columns: 4.3rem minmax(0, 1fr) 1.1rem; gap: .4rem; align-items: center; padding: .28rem .45rem; border-radius: .6rem; font-size: .66rem;
        background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); opacity: 0; }
    .acs-once-r span { font-weight: 800; color: var(--mute); white-space: nowrap; }
    .acs-once-r b { font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; clip-path: inset(0 100% 0 0); }
    .acs-once-r i { width: 1.05rem; height: 1.05rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .58rem; font-weight: 900; color: #1d330d; background: var(--leaf); opacity: 0; }
    .stm-scene.is-in .acs-once-r { animation: acsOnceR var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .55s); }
    .stm-scene.is-in .acs-once-r b { animation: acsOnceT var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .55s); }
    .stm-scene.is-in .acs-once-r i { animation: acsOnceC var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .55s); }
    @keyframes acsOnceR { 0% { opacity: 0; transform: translateY(6px); } 5%, 84% { opacity: 1; transform: none; } 92%, 100% { opacity: 0; } }
    @keyframes acsOnceT { 0%, 5% { clip-path: inset(0 100% 0 0); } 14%, 100% { clip-path: inset(0 0 0 0); } }
    @keyframes acsOnceC { 0%, 14% { opacity: 0; transform: scale(.4); } 18%, 84% { opacity: 1; transform: none; } 92%, 100% { opacity: 0; } }
    .acs-once-stamp { position: absolute; z-index: 2; left: 50%; top: 50%; margin: -.75rem 0 0 -6.2rem; width: 12.4rem; text-align: center; padding: .3rem .65rem; border-radius: 999px; white-space: nowrap; font-size: .64rem; font-weight: 900; color: #1d330d; background: var(--gold);
        box-shadow: 0 0 0 0 rgb(245 197 24 / .5); opacity: 0; transform: scale(.6) rotate(-4deg); }
    .stm-scene.is-in .acs-once-stamp { animation: acsOnceS var(--cy) var(--e) infinite; }
    @keyframes acsOnceS { 0%, 42% { opacity: 0; transform: scale(.6) rotate(-4deg); } 47% { opacity: 1; transform: scale(1.08) rotate(-2deg); box-shadow: 0 0 0 0 rgb(245 197 24 / .6); }
        52%, 84% { opacity: 1; transform: scale(1) rotate(-2deg); box-shadow: 0 0 0 10px rgb(245 197 24 / 0); } 92%, 100% { opacity: 0; transform: scale(1) rotate(-2deg); } }
    /* 2. A goal for any budget: the budget slides, Anee's plan follows it. */
    .acs-bud { --cy: 9s; position: relative; width: min(100%, 16.5rem); display: grid; gap: .55rem; }
    .acs-bud-t { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); padding: .2rem; border-radius: 999px; background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    .acs-bud-t span { position: relative; z-index: 1; padding: .22rem 0; text-align: center; font-size: .6rem; font-weight: 900; color: var(--mute); }
    .acs-bud-t i { position: absolute; top: .2rem; bottom: .2rem; left: .2rem; width: calc((100% - .4rem) / 3); border-radius: 999px; background: var(--gold); box-shadow: 0 6px 14px -6px rgb(245 197 24 / .8); }
    .stm-scene.is-in .acs-bud-t i { animation: acsBudK var(--cy) var(--e) infinite; }
    @keyframes acsBudK { 0%, 28% { transform: translateX(0); } 34%, 61% { transform: translateX(100%); } 67%, 94% { transform: translateX(200%); } 100% { transform: translateX(0); } }
    .stm-scene.is-in .acs-bud-t span:nth-of-type(1) { animation: acsBud1 var(--cy) infinite; }
    .stm-scene.is-in .acs-bud-t span:nth-of-type(2) { animation: acsBud2 var(--cy) infinite; }
    .stm-scene.is-in .acs-bud-t span:nth-of-type(3) { animation: acsBud3 var(--cy) infinite; }
    @keyframes acsBud1 { 0%, 28%, 100% { color: #2b2100; } 32%, 96% { color: var(--mute); } }
    @keyframes acsBud2 { 0%, 31% { color: var(--mute); } 34%, 61% { color: #2b2100; } 64%, 100% { color: var(--mute); } }
    @keyframes acsBud3 { 0%, 64% { color: var(--mute); } 67%, 94% { color: #2b2100; } 97%, 100% { color: var(--mute); } }
    .acs-bud-o { position: relative; display: grid; }
    .acs-bud-o > div { grid-area: 1 / 1; display: grid; gap: .25rem; padding: .45rem .55rem; border-radius: .7rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); opacity: 0; }
    .acs-bud-o b { font-size: .72rem; font-weight: 800; color: #fff; }
    .acs-bud-o small { font-size: .6rem; line-height: 1.35; color: var(--mute); }
    .acs-bud-o u { display: flex; align-items: center; gap: .35rem; text-decoration: none; font-size: .62rem; font-weight: 800; color: var(--gold); }
    .acs-bud-o u i { flex: 1; height: .4rem; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; }
    .acs-bud-o u i::before { content: ''; display: block; height: 100%; width: var(--v); border-radius: inherit; background: linear-gradient(90deg, #a8cc7e, #f5c518); }
    .stm-scene.is-in .acs-bud-o > div:nth-child(1) { animation: acsBudA var(--cy) var(--e) infinite; }
    .stm-scene.is-in .acs-bud-o > div:nth-child(2) { animation: acsBudB var(--cy) var(--e) infinite; }
    .stm-scene.is-in .acs-bud-o > div:nth-child(3) { animation: acsBudC var(--cy) var(--e) infinite; }
    @keyframes acsBudA { 0%, 28% { opacity: 1; transform: none; } 32%, 96% { opacity: 0; transform: translateY(-4px); } 100% { opacity: 1; transform: none; } }
    @keyframes acsBudB { 0%, 31% { opacity: 0; transform: translateY(6px); } 35%, 61% { opacity: 1; transform: none; } 65%, 100% { opacity: 0; transform: translateY(-4px); } }
    @keyframes acsBudC { 0%, 64% { opacity: 0; transform: translateY(6px); } 68%, 94% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; transform: translateY(-4px); } }
    /* 3. The protocol, written again and again: what she weighs flies in, the lines write, the stamp lands. */
    .acs-proto { --cy: 8s; }
    .stm-scene.is-in .acs-proto-in span { animation: acsProtoIn2 var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .15s); }
    @keyframes acsProtoIn2 { 0% { opacity: 0; transform: translateX(-14px); } 8%, 28% { opacity: 1; transform: none; } 38%, 100% { opacity: .3; transform: translateX(12px) scale(.92); } }
    .stm-scene.is-in .acs-proto-doc { animation: acsProtoDoc var(--cy) var(--e) infinite; }
    @keyframes acsProtoDoc { 0%, 18% { opacity: 0; transform: translateY(8px); } 24%, 92% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; transform: translateY(8px); } }
    .stm-scene.is-in .acs-proto-doc span { animation: acsProtoLine var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .35s); }
    @keyframes acsProtoLine { 0%, 28% { clip-path: inset(0 100% 0 0); } 36%, 92% { clip-path: inset(0 0 0 0); } 98%, 100% { clip-path: inset(0 100% 0 0); } }
    .acs-proto-ok { position: absolute; right: -.4rem; bottom: -.5rem; padding: .16rem .5rem; border-radius: 999px; white-space: nowrap; font-size: .56rem; font-weight: 900; color: #1d330d; background: var(--gold);
        opacity: 0; transform: scale(.6) rotate(-6deg); box-shadow: 0 8px 16px -8px rgb(0 0 0 / .8); }
    .stm-scene.is-in .acs-proto-ok { animation: acsProtoOk var(--cy) var(--e) infinite; }
    @keyframes acsProtoOk { 0%, 64% { opacity: 0; transform: scale(.6) rotate(-6deg); } 69% { opacity: 1; transform: scale(1.1) rotate(-4deg); } 73%, 92% { opacity: 1; transform: scale(1) rotate(-4deg); } 98%, 100% { opacity: 0; } }
    /* 5. Every morning: the message arrives at 5:30, its tasks get done one by one. */
    .acs-morn { --cy: 9s; position: relative; width: min(100%, 15.5rem); }
    .acs-morn-n { padding: .55rem .65rem; border-radius: .9rem; background: #f6f9f1; color: #1f2a17; box-shadow: 0 14px 30px -16px rgb(0 0 0 / .9); opacity: 0; transform: translateY(-14px); }
    .stm-scene.is-in .acs-morn-n { animation: acsMornN var(--cy) var(--e) infinite; }
    @keyframes acsMornN { 0% { opacity: 0; transform: translateY(-14px); } 7%, 92% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; transform: translateY(-8px); } }
    .acs-morn-n small { display: flex; align-items: center; gap: .35rem; font-size: .6rem; font-weight: 800; color: #4a7c2a; }
    .acs-morn-n small img { width: 1rem; height: 1rem; border-radius: 999px; }
    .acs-morn-n > b { display: block; margin-top: .15rem; font-size: .74rem; }
    .acs-morn-n ul { margin: .3rem 0 0; padding: 0; list-style: none; display: grid; gap: .2rem; }
    .acs-morn-n li { display: flex; align-items: center; gap: .35rem; font-size: .62rem; font-weight: 700; color: #374151; opacity: 0; }
    .acs-morn-n li i { flex: none; width: .85rem; height: .85rem; border-radius: .25rem; display: grid; place-items: center; font-style: normal; font-size: .5rem; color: transparent; box-shadow: inset 0 0 0 1.5px #9ca3af; }
    .stm-scene.is-in .acs-morn-n li { animation: acsMornL var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .3s); }
    .stm-scene.is-in .acs-morn-n li i { animation: acsMornC var(--cy) var(--e) infinite; animation-delay: calc(var(--k) * .9s); }
    @keyframes acsMornL { 0%, 9% { opacity: 0; transform: translateX(-6px); } 14%, 92% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; } }
    @keyframes acsMornC { 0%, 34% { color: transparent; background: transparent; box-shadow: inset 0 0 0 1.5px #9ca3af; } 38%, 92% { color: #fff; background: #4a7c2a; box-shadow: none; } 98%, 100% { color: transparent; background: transparent; } }
    .acs-morn-done { position: absolute; right: -.3rem; top: -.55rem; padding: .14rem .5rem; border-radius: 999px; white-space: nowrap; font-size: .58rem; font-weight: 900; color: #1d330d; background: var(--gold); opacity: 0; transform: scale(.6); }
    .stm-scene.is-in .acs-morn-done { animation: acsMornD var(--cy) var(--e) infinite; }
    @keyframes acsMornD { 0%, 66% { opacity: 0; transform: scale(.6); } 71%, 92% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; } }
    /* Every morning before you wake up: her check, item by item, then the plan it makes. */
    .acs-chk { --cy: 12s; margin-top: 1.6rem; padding: 1rem; }
    .acs-chk-top { display: flex; align-items: center; gap: .7rem; }
    .acs-chk-top img { flex: none; width: 2.6rem; height: 2.6rem; }
    .acs-chk-top b { display: block; font-size: .92rem; font-weight: 800; color: #fff; }
    .acs-chk-top small { position: relative; display: block; height: 1.1rem; font-size: .72rem; font-weight: 800; color: var(--mute); }
    .acs-chk-top small span { position: absolute; left: 0; top: 0; white-space: nowrap; }
    .acs-chk-top small span + span { opacity: 0; color: var(--gold); }
    .stm-scene.is-in .acs-chk-top small span:first-child { animation: acsChkA var(--cy) infinite; }
    .stm-scene.is-in .acs-chk-top small span + span { animation: acsChkB var(--cy) infinite; }
    @keyframes acsChkA { 0%, 74% { opacity: 1; } 77%, 97% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes acsChkB { 0%, 74% { opacity: 0; } 77%, 96% { opacity: 1; } 100% { opacity: 0; } }
    .acs-chk-bar { position: relative; height: .35rem; margin-top: .7rem; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; }
    .acs-chk-bar i { position: absolute; inset: 0 auto 0 0; width: 100%; border-radius: inherit; background: linear-gradient(90deg, #a8cc7e, #f5c518); transform-origin: left; transform: scaleX(0); }
    .stm-scene.is-in .acs-chk-bar i { animation: acsChkBar var(--cy) linear infinite; }
    @keyframes acsChkBar { 0%, 4% { transform: scaleX(0); } 76%, 96% { transform: scaleX(1); } 100% { transform: scaleX(0); } }
    .acs-chk-list { display: grid; gap: .35rem; margin-top: .8rem; }
    @media (min-width: 760px) { .acs-chk-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .acs-chk-r { display: grid; grid-template-columns: 1.9rem minmax(0, 1fr) 1.2rem; gap: .55rem; align-items: center; padding: .45rem .55rem; border-radius: .8rem; background: rgb(255 255 255 / .05);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); opacity: .4; }
    .acs-chk-r > span { font-size: 1.1rem; text-align: center; }
    .acs-chk-r b { display: block; font-size: .78rem; font-weight: 800; color: #fff; }
    .acs-chk-r small { position: relative; display: block; height: 1.05rem; font-size: .68rem; color: var(--mute); }
    .acs-chk-r small em { position: absolute; left: 0; top: 0; font-style: normal; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .acs-chk-r small em.found { color: var(--gold); font-weight: 800; opacity: 0; }
    .acs-chk-r .st { position: relative; width: 1.2rem; height: 1.2rem; }
    .acs-chk-r .st i, .acs-chk-r .st u { position: absolute; inset: 0; border-radius: 999px; opacity: 0; }
    .acs-chk-r .st i { border: 2px solid rgb(245 197 24 / .35); border-top-color: var(--gold); animation: acsSpin .8s linear infinite; }
    .acs-chk-r .st u { display: grid; place-items: center; text-decoration: none; font-size: .62rem; font-weight: 900; color: #1d330d; background: var(--leaf); }
    .acs-chk-plan { margin-top: .9rem; opacity: 0; transform: translateY(10px); }
    .stm-scene.is-in .acs-chk-plan { animation: acsChkPlan var(--cy) var(--e) infinite; }
    @keyframes acsChkPlan { 0%, 76% { opacity: 0; transform: translateY(10px); } 80%, 96% { opacity: 1; transform: none; } 100% { opacity: 0; } }
    .acs-chk-plan .acs-out { width: 100%; max-width: 26rem; margin: 0 auto; }
    /* The season at a glance: why the day is the day. */
    .acs-g-why { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .9rem; }
    .acs-g-why span { padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: var(--ink); background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    /* Why it is worth it: the same hectare, without Anee and with her. */
    .acs-vs { display: grid; gap: .9rem; margin-bottom: 1.4rem; }
    .acs-vs-h { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #fff; }
    .acs-vs-h small { display: block; margin-top: .15rem; font-family: inherit; font-size: .78rem; font-weight: 700; color: var(--mute); }
    .acs-vs-cols { display: grid; gap: .8rem; }
    @media (min-width: 760px) { .acs-vs-cols { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .acs-vs-c { padding: 1rem; border-radius: 1.2rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); }
    .acs-vs-c.is-with { background: linear-gradient(160deg, rgb(245 197 24 / .14), rgb(168 204 126 / .08)); box-shadow: inset 0 0 0 1.5px rgb(245 197 24 / .5); }
    .acs-vs-c h4 { display: flex; align-items: center; gap: .45rem; font-size: .92rem; font-weight: 800; color: #fff; }
    .acs-vs-c h4 img { width: 1.6rem; height: 1.6rem; }
    .acs-vs-c h4 small { margin-left: auto; padding: .1rem .5rem; border-radius: 999px; font-size: .62rem; font-weight: 900; color: #1d330d; background: var(--gold); white-space: nowrap; }
    .acs-vs-bar { display: flex; height: 1.6rem; margin-top: .8rem; border-radius: .55rem; overflow: hidden; background: rgb(255 255 255 / .06); }
    .acs-vs-bar i { display: grid; place-items: center; flex: none; width: calc(var(--w) * 100%); font-style: normal; font-size: .6rem; font-weight: 900; color: #1d330d; transform-origin: left; transform: scaleX(0);
        transition: transform .7s var(--e) calc(.4s + var(--k) * .45s); }
    .stm-scene.is-in .acs-vs-bar i { transform: none; }
    .acs-vs-bar i.keep { background: #8fc96a; }
    .acs-vs-bar i.lost { color: #fff; background: repeating-linear-gradient(135deg, #c2453d 0 6px, #a3362f 6px 12px); }
    .acs-vs-bar i.gain { background: linear-gradient(90deg, #f5c518, #fde68a); }
    .acs-vs-rows { display: grid; gap: .3rem; margin-top: .7rem; }
    .acs-vs-rows div { display: flex; align-items: center; gap: .5rem; font-size: .76rem; line-height: 1.35; color: var(--ink); opacity: 0; transform: translateX(-6px); transition: opacity .45s var(--e) calc(.6s + var(--k) * .45s), transform .45s var(--e) calc(.6s + var(--k) * .45s); }
    .stm-scene.is-in .acs-vs-rows div { opacity: 1; transform: none; }
    .acs-vs-rows div b { margin-left: auto; white-space: nowrap; font-weight: 900; }
    .acs-vs-c:not(.is-with) .acs-vs-rows b { color: #f19b93; } .acs-vs-c.is-with .acs-vs-rows b { color: var(--gold); }
    .acs-vs-tot { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; margin-top: .8rem; padding-top: .7rem; border-top: 1px solid rgb(255 255 255 / .1); }
    .acs-vs-tot span { font-size: .74rem; font-weight: 800; color: var(--mute); }
    .acs-vs-tot b { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
    .acs-vs-c.is-with .acs-vs-tot b { color: var(--gold); }
    .acs-vs-net { display: flex; align-items: center; gap: .8rem; padding: .9rem 1rem; border-radius: 1.1rem; background: rgb(245 197 24 / .12); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    .acs-vs-net img { flex: none; width: 2.8rem; height: 2.8rem; }
    .acs-vs-net p { font-size: .86rem; line-height: 1.5; color: var(--ink); }
    .acs-vs-net p b { font-family: var(--font-heading); font-size: 1.05rem; color: var(--gold); font-variant-numeric: tabular-nums; }
    /* ---------- 2b. when to plant (2026-10-28, the owner: "add a section about the when to plant analysis ... historical
       data and analysis to provide forecast of the safest less risky as well as its effects in the soil type, irrigation,
       and the forecast of enso, and the pricing forecast as well based in our historical database of prices per crop").
       A year of planting months: the bars the risk of each (twenty years of the town's storms, floods, dry spells and
       heat), the gold line the price at harvest. The pick moves between the safest months and the best paid ones on a
       nine second clock, the two rankings Anee gives. ---------- */
    .acs-wtp { --cy: 9s; margin-top: 1.6rem; padding: 1rem; }
    .acs-wtp-tabs { display: flex; flex-wrap: wrap; gap: .35rem; }
    .acs-wtp-tabs span { padding: .28rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: var(--mute); background: rgb(255 255 255 / .06); }
    .stm-scene.is-in .acs-wtp-tabs span { animation: acsWtpTab var(--cy) infinite; }
    .stm-scene.is-in .acs-wtp-tabs span + span { animation-delay: calc(var(--cy) * -.5); }
    @keyframes acsWtpTab { 0%, 46% { color: #1d330d; background: var(--gold); } 50%, 100% { color: var(--mute); background: rgb(255 255 255 / .06); } }
    .acs-wtp-chart { position: relative; height: 9.5rem; margin-top: .9rem; }
    .acs-wtp-bars { position: absolute; inset: 1.4rem 0 0; display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .3rem; align-items: end; border-bottom: 1px solid rgb(255 255 255 / .12); }
    .acs-wtp-bars i { display: block; height: var(--h); border-radius: .35rem .35rem 0 0; background: var(--c); opacity: .85; transform-origin: bottom; transform: scaleY(0); transition: transform .9s var(--e) calc(.3s + var(--k) * 55ms); }
    .stm-scene.is-in .acs-wtp-bars i { transform: none; }
    .acs-wtp-line { position: absolute; inset: 1.4rem 0 0; width: 100%; height: calc(100% - 1.4rem); overflow: visible; pointer-events: none; }
    /* Drawn by a wipe, left to right: a dash on a stretched line breaks into pieces. */
    .acs-wtp-line { clip-path: inset(0 100% 0 0); }
    .acs-wtp-line polyline { fill: none; stroke: var(--gold); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; vector-effect: non-scaling-stroke; filter: drop-shadow(0 0 4px rgb(245 197 24 / .6)); }
    .stm-scene.is-in .acs-wtp-line { animation: acsWipe 1.8s 1.1s var(--e) forwards; }
    @keyframes acsWipe { to { clip-path: inset(0 0 0 0); } }
    .acs-wtp-pick { position: absolute; top: 0; bottom: 0; left: calc(10 * (100% + .3rem) / 12); width: calc(2 * (100% + .3rem) / 12 - .3rem); border-radius: .7rem; box-shadow: inset 0 0 0 2px var(--gold), 0 0 22px rgb(245 197 24 / .3);
        background: rgb(245 197 24 / .08); opacity: 0; }
    .stm-scene.is-in .acs-wtp-pick { animation: acsWtpPick var(--cy) var(--e) infinite; animation-delay: 1.8s; }
    @keyframes acsWtpPick { 0% { opacity: 0; left: calc(10 * (100% + .3rem) / 12); } 6%, 44% { opacity: 1; left: calc(10 * (100% + .3rem) / 12); } 52%, 94% { opacity: 1; left: 0%; } 100% { opacity: 1; left: calc(10 * (100% + .3rem) / 12); } }
    .acs-wtp-pick b { position: absolute; left: 50%; top: -.1rem; translate: -50% -50%; padding: .1rem .45rem; border-radius: 999px; white-space: nowrap; font-size: .58rem; font-weight: 900; color: #1d330d; background: var(--gold); }
    .acs-wtp-pick b + b { opacity: 0; }
    .stm-scene.is-in .acs-wtp-pick b:first-child { animation: acsWtpA var(--cy) infinite; animation-delay: 1.8s; }
    .stm-scene.is-in .acs-wtp-pick b + b { animation: acsWtpB var(--cy) infinite; animation-delay: 1.8s; }
    @keyframes acsWtpA { 0%, 44% { opacity: 1; } 48%, 96% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes acsWtpB { 0%, 48% { opacity: 0; } 52%, 94% { opacity: 1; } 98%, 100% { opacity: 0; } }
    .acs-wtp-m { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .3rem; margin-top: .3rem; text-align: center; font-size: .6rem; font-weight: 800; color: var(--mute); }
    .acs-wtp-key { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .6rem; font-size: .66rem; font-weight: 700; color: var(--mute); }
    .acs-wtp-key span { display: inline-flex; align-items: center; gap: .35rem; }
    .acs-wtp-key i { width: .8rem; height: .5rem; border-radius: .15rem; background: linear-gradient(90deg, #8fc96a, #f0cf5c, #e98b5d); }
    .acs-wtp-key i.ln { height: 3px; border-radius: 999px; background: var(--gold); }
    .acs-wtp-why { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .9rem; }
    .acs-wtp-why span { padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--ink); background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); opacity: 0; transform: translateY(6px); }
    .stm-scene.is-in .acs-wtp-why span { animation: acsPop .45s var(--e) forwards; animation-delay: calc(2.2s + var(--k) * .18s); }
    /* ---------- 2c. the law of the minimum (2026-10-28, the owner: "add a section about the leibigs on its nutrient
       analysis based on the goal, and needs of crops and make it animated"). Liebig's barrel on a twelve second clock:
       the water (the harvest) rises to the shortest stave and spills there; Anee adds what is short, that stave grows,
       the water rises to the next shortest, until every stave reaches the goal. ---------- */
    .acs-lb { --cy: 12s; margin-top: 1.6rem; padding: 1.1rem 1rem 1rem; }
    .acs-lb-barrel { position: relative; width: min(100%, 19rem); height: 12rem; margin: 1.6rem auto 0; }
    .acs-lb-staves { position: absolute; inset: 0; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .22rem; align-items: end; }
    .acs-lb-staves i { position: relative; height: var(--h); border-radius: .45rem .45rem .25rem .25rem; font-style: normal;
        background: linear-gradient(90deg, #8a5a2b, #b9824a 45%, #9a6633); box-shadow: inset 0 0 0 1px rgb(0 0 0 / .25); }
    .acs-lb-staves i:nth-child(even) { background: linear-gradient(90deg, #7c4f25, #a87540 45%, #8a5a2b); }
    /* The nutrients' names under the barrel, where the water never covers them. */
    .acs-lb-names { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .22rem; width: min(100%, 19rem); margin: .4rem auto 0; text-align: center; font-size: .74rem; font-weight: 900; color: #f3e4c8; }
    .acs-lb-names b.is-short { color: var(--gold); }
    .stm-scene.is-in .acs-lb-staves i.zn { animation: acsLbZn var(--cy) var(--e) infinite; }
    .stm-scene.is-in .acs-lb-staves i.s { animation: acsLbS var(--cy) var(--e) infinite; }
    @keyframes acsLbZn { 0%, 28% { height: 52%; } 35%, 94% { height: 96%; } 100% { height: 52%; } }
    @keyframes acsLbS { 0%, 60% { height: 70%; } 67%, 94% { height: 96%; } 100% { height: 70%; } }
    .acs-lb-hoop { position: absolute; left: -.2rem; right: -.2rem; height: .32rem; border-radius: 999px; background: #3b2a1a; box-shadow: 0 1px 0 rgb(255 255 255 / .12); }
    .acs-lb-water { position: absolute; left: .15rem; right: .15rem; bottom: 0; height: 0; border-radius: 0 0 .25rem .25rem; pointer-events: none;
        background: linear-gradient(180deg, rgb(160 214 255 / .75), rgb(59 130 196 / .7)); box-shadow: inset 0 3px 0 rgb(255 255 255 / .35); }
    .stm-scene.is-in .acs-lb-water { animation: acsLbWater var(--cy) var(--e) infinite; }
    @keyframes acsLbWater { 0% { height: 0; opacity: 1; } 8%, 36% { height: 50%; } 44%, 68% { height: 68%; } 76%, 94% { height: 92%; opacity: 1; } 99% { height: 92%; opacity: 0; } 100% { height: 0; opacity: 0; } }
    /* The spill over the shortest stave. */
    .acs-lb-spill { position: absolute; width: .3rem; margin-left: -.15rem; border-radius: 999px; background: linear-gradient(180deg, #a0d6ff, rgb(59 130 196 / .2)); opacity: 0; transform-origin: top; }
    .acs-lb-spill.zn { left: calc(4.5 * 100% / 7); top: 48%; height: 50%; }
    .acs-lb-spill.s { left: calc(3.5 * 100% / 7); top: 30%; height: 68%; }
    .stm-scene.is-in .acs-lb-spill.zn { animation: acsLbSpillZn var(--cy) infinite; }
    .stm-scene.is-in .acs-lb-spill.s { animation: acsLbSpillS var(--cy) infinite; }
    @keyframes acsLbSpillZn { 0%, 8% { opacity: 0; transform: scaleY(0); } 11%, 27% { opacity: 1; transform: scaleY(1); } 30%, 100% { opacity: 0; transform: scaleY(1); } }
    @keyframes acsLbSpillS { 0%, 44% { opacity: 0; transform: scaleY(0); } 47%, 59% { opacity: 1; transform: scaleY(1); } 62%, 100% { opacity: 0; transform: scaleY(1); } }
    /* The goal line, and what Anee adds where. */
    .acs-lb-goal { position: absolute; left: -.6rem; right: -.6rem; bottom: 92%; border-top: 2px dashed var(--gold); }
    .acs-lb-goal span { position: absolute; right: 0; bottom: .25rem; padding: .08rem .45rem; border-radius: 999px; font-size: .6rem; font-weight: 900; color: #1d330d; background: var(--gold); }
    .acs-lb-add { position: absolute; bottom: calc(96% + 1.6rem); translate: -50% 0; padding: .14rem .5rem; border-radius: 999px; white-space: nowrap; font-size: .6rem; font-weight: 800; color: #1d330d; background: var(--leaf);
        box-shadow: 0 8px 16px -8px rgb(0 0 0 / .8); opacity: 0; }
    .acs-lb-add.zn { left: calc(4.5 * 100% / 7); } .acs-lb-add.s { left: calc(3.5 * 100% / 7); }
    .stm-scene.is-in .acs-lb-add.zn { animation: acsLbAddZn var(--cy) var(--e) infinite; }
    .stm-scene.is-in .acs-lb-add.s { animation: acsLbAddS var(--cy) var(--e) infinite; }
    @keyframes acsLbAddZn { 0%, 26% { opacity: 0; transform: translateY(6px); } 29%, 40% { opacity: 1; transform: none; } 44%, 100% { opacity: 0; } }
    @keyframes acsLbAddS { 0%, 58% { opacity: 0; transform: translateY(6px); } 61%, 72% { opacity: 1; transform: none; } 76%, 100% { opacity: 0; } }
    /* What the harvest is held to, a line a step. */
    .acs-lb-say { position: relative; min-height: 2.6rem; margin-top: .9rem; text-align: center; }
    .acs-lb-say span { position: absolute; left: 0; right: 0; top: 0; font-size: .86rem; font-weight: 800; color: #fff; opacity: 0; }
    .acs-lb-say span small { display: block; font-size: .72rem; font-weight: 700; color: var(--mute); }
    .acs-lb-say span.c { color: var(--gold); }
    .stm-scene.is-in .acs-lb-say span.a { animation: acsLbA var(--cy) infinite; }
    .stm-scene.is-in .acs-lb-say span.b { animation: acsLbB var(--cy) infinite; }
    .stm-scene.is-in .acs-lb-say span.c { animation: acsLbC var(--cy) infinite; }
    @keyframes acsLbA { 0%, 9% { opacity: 0; } 12%, 34% { opacity: 1; } 38%, 100% { opacity: 0; } }
    @keyframes acsLbB { 0%, 45% { opacity: 0; } 48%, 66% { opacity: 1; } 70%, 100% { opacity: 0; } }
    @keyframes acsLbC { 0%, 76% { opacity: 0; transform: scale(.94); } 80%, 94% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; } }
    .acs-lb-why { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem; margin-top: .6rem; }
    .acs-lb-why span { padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--ink); background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    /* ---------- 3. a typhoon, and the best day (2026-10-28, the owner: "can't fertilizer today because of the typhoon?
       anee adjusts it to the best timing possible ... let anee think for you then change animation to present it good").
       One ten second loop: the storm comes, the fertilizer cannot go today, Anee weighs it, the task flies to
       Saturday, the best day, and she says so. Every part runs on the same clock (--cy), its moment in its keyframes. ---------- */
    .acs-ty { --cy: 10s; position: relative; margin-top: 1.6rem; padding: 1rem .8rem 1rem; overflow: hidden; }
    .acs-ty-days { --g: .35rem; --cw: calc((100% - 6 * var(--g)) / 7); position: relative; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: var(--g); padding-top: 3.3rem; }
    .acs-ty-d { position: relative; display: grid; justify-items: center; gap: .1rem; padding: .5rem .15rem .45rem; border-radius: .85rem; text-align: center; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .acs-ty-d b { font-size: .66rem; font-weight: 900; color: var(--mute); }
    .acs-ty-d i { display: inline-block; font-style: normal; font-size: 1.15rem; line-height: 1.25; }
    .acs-ty-d small { max-width: 100%; overflow: hidden; text-overflow: ellipsis; font-size: .55rem; font-weight: 800; color: var(--mute); white-space: nowrap; }
    .acs-ty-d u { position: absolute; top: -.55rem; left: 50%; translate: -50% 0; padding: .05rem .4rem; border-radius: 999px; text-decoration: none; font-size: .5rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #1d330d; background: var(--leaf); }
    .acs-ty-d.is-storm { background: rgb(90 120 170 / .24); box-shadow: inset 0 0 0 1px rgb(150 185 235 / .38); }
    .acs-ty-d.is-storm small, .acs-ty-d.is-wet small { color: #a9c8f2; }
    .stm-scene.is-in .acs-ty-d.is-storm i { animation: acsSpin 2s linear infinite; }
    .stm-scene.is-in .acs-ty-d.is-best { animation: acsTyBest var(--cy) var(--e) infinite; }
    @keyframes acsSpin { to { transform: rotate(-360deg); } }
    @keyframes acsTyBest { 0%, 58% { background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); } 64%, 91% { background: rgb(245 197 24 / .14); box-shadow: inset 0 0 0 2px var(--gold), 0 0 20px rgb(245 197 24 / .35); } 98%, 100% { background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); } }
    /* Wind across the storm days. */
    .acs-ty-wind { position: absolute; left: 0; width: calc(2 * var(--cw) + var(--g)); top: 3.3rem; bottom: 0; pointer-events: none; overflow: hidden; border-radius: .85rem; }
    .acs-ty-wind i { position: absolute; left: -40%; height: 2px; width: 40%; border-radius: 999px; background: linear-gradient(90deg, transparent, rgb(190 215 250 / .7), transparent); }
    .acs-ty-wind i:nth-child(1) { top: 30%; } .acs-ty-wind i:nth-child(2) { top: 55%; width: 30%; } .acs-ty-wind i:nth-child(3) { top: 78%; width: 35%; }
    .stm-scene.is-in .acs-ty-wind i { animation: acsWind 1.1s linear infinite; } .stm-scene.is-in .acs-ty-wind i:nth-child(2) { animation-delay: .4s; } .stm-scene.is-in .acs-ty-wind i:nth-child(3) { animation-delay: .75s; }
    @keyframes acsWind { to { left: 110%; } }
    /* The crop's window under the days. */
    .acs-ty-win { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .35rem; margin-top: .45rem; }
    .acs-ty-win span { grid-column: 1 / 6; display: grid; place-items: center; height: 1.35rem; border-radius: 999px; font-size: .58rem; font-weight: 800; color: var(--leaf); background: rgb(168 204 126 / .12); box-shadow: inset 0 0 0 1px rgb(168 204 126 / .4); white-space: nowrap; overflow: hidden; }
    /* The task, flying from today to the best day. */
    .acs-ty-chip { position: absolute; top: .95rem; left: 0; z-index: 2; opacity: 0; }
    .stm-scene.is-in .acs-ty-chip { animation: acsTyFly var(--cy) var(--e) infinite; }
    .acs-ty-chip > span { display: inline-flex; align-items: center; gap: .25rem; padding: .3rem .6rem; border-radius: 999px; white-space: nowrap; font-size: .7rem; font-weight: 800; color: #1d330d; background: var(--gold); box-shadow: 0 10px 22px -10px rgb(245 197 24 / .9); }
    .stm-scene.is-in .acs-ty-chip > span { animation: acsTyShake var(--cy) linear infinite; }
    .acs-ty-chip em { position: absolute; left: 50%; bottom: calc(100% + .3rem); translate: -50% 0; padding: .1rem .45rem; border-radius: 999px; font-style: normal; white-space: nowrap; font-size: .58rem; font-weight: 900; opacity: 0; }
    .acs-ty-chip em.no { color: #fff; background: #dc4c4c; }
    .acs-ty-chip em.ok { color: #1d330d; background: var(--leaf); }
    .stm-scene.is-in .acs-ty-chip em.no { animation: acsTyNo var(--cy) infinite; }
    .stm-scene.is-in .acs-ty-chip em.ok { animation: acsTyOk var(--cy) infinite; }
    @keyframes acsTyFly {
        0% { opacity: 0; left: 0; translate: 0 0; top: .95rem; } 6%, 46% { opacity: 1; left: 0; translate: 0 0; top: .95rem; }
        53% { top: -.15rem; } 60% { left: calc(3 * (var(--cw) + var(--g)) + var(--cw) / 2); translate: -50% 0; top: .95rem; }
        92% { opacity: 1; left: calc(3 * (var(--cw) + var(--g)) + var(--cw) / 2); translate: -50% 0; top: .95rem; } 98%, 100% { opacity: 0; left: calc(3 * (var(--cw) + var(--g)) + var(--cw) / 2); translate: -50% 0; top: .95rem; }
    }
    @keyframes acsTyShake { 0%, 13% { transform: none; background: var(--gold); } 15% { transform: translateX(-4px) rotate(-3deg); } 17% { transform: translateX(4px) rotate(3deg); } 19% { transform: translateX(-3px); } 21% { transform: translateX(3px); }
        23%, 46% { transform: none; background: #f3c9a6; } 60%, 100% { transform: none; background: var(--gold); } }
    @keyframes acsTyNo { 0%, 13% { opacity: 0; } 16%, 46% { opacity: 1; } 50%, 100% { opacity: 0; } }
    @keyframes acsTyOk { 0%, 60% { opacity: 0; transform: scale(.6); } 64%, 92% { opacity: 1; transform: none; } 97%, 100% { opacity: 0; } }
    /* Anee weighs it, then says where it went. */
    /* Its own clock: it sits beside the card, not inside it, and must keep the card's time. */
    .acs-ty-anee { --cy: 10s; display: flex; gap: .7rem; align-items: flex-start; margin-top: 1rem; padding: .8rem .9rem; border-radius: 1rem; background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .acs-ty-face { position: relative; flex: none; width: 2.6rem; height: 2.6rem; }
    .acs-ty-face img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; }
    .acs-ty-face img.done { opacity: 0; }
    .stm-scene.is-in .acs-ty-face img.think { animation: acsTyA var(--cy) infinite; }
    .stm-scene.is-in .acs-ty-face img.done { animation: acsTyB var(--cy) infinite; }
    .acs-ty-say { position: relative; flex: 1; min-width: 0; display: grid; }
    .acs-ty-say > div { grid-area: 1 / 1; }
    .acs-ty-say b { display: block; font-size: .88rem; line-height: 1.4; color: #fff; }
    .acs-ty-say p { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--ink); }
    .acs-ty-say .done { opacity: 0; }
    .stm-scene.is-in .acs-ty-say .think { animation: acsTyA var(--cy) infinite; }
    .stm-scene.is-in .acs-ty-say .done { animation: acsTyB var(--cy) infinite; }
    .acs-ty-why { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .acs-ty-why span { padding: .15rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; color: var(--ink); background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); opacity: 0; }
    .stm-scene.is-in .acs-ty-why span { animation: acsTyWhy var(--cy) infinite; animation-delay: calc(var(--k) * .45s); }
    .acs-ty-dots i { display: inline-block; width: .28rem; height: .28rem; margin-left: .15rem; border-radius: 50%; background: currentColor; vertical-align: .12rem; animation: acsDot 1.1s ease-in-out infinite; }
    .acs-ty-dots i:nth-child(2) { animation-delay: .15s; } .acs-ty-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes acsDot { 0%, 80%, 100% { opacity: .25; transform: none; } 40% { opacity: 1; transform: translateY(-3px); } }
    @keyframes acsTyA { 0%, 54% { opacity: 1; } 59%, 97% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes acsTyB { 0%, 57% { opacity: 0; transform: translateY(6px); } 62%, 93% { opacity: 1; transform: none; } 98%, 100% { opacity: 0; } }
    @keyframes acsTyWhy { 0%, 22% { opacity: 0; transform: translateY(6px) scale(.9); } 27%, 52% { opacity: 1; transform: none; } 57%, 100% { opacity: 0; } }
    /* ---------- 3b. ask her anything (2026-10-28, the owner: "add the part where you can ask anee anything about your
       crop ... show animation about showing an image then anee thinks and says what's the problem ... do not show a
       screenshot ... in a good step by step animation way"). One eleven second loop, three steps lit in turn: the
       photo (drawn, a rice leaf with blast spots) comes with its question, Anee scans it and weighs what she sees,
       then her answer. ---------- */
    .acs-ask { --cy: 11s; margin-top: 1.6rem; padding: 1rem; }
    .acs-ask-steps { display: flex; flex-wrap: wrap; gap: .35rem; }
    .acs-ask-steps span { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .65rem .25rem .3rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: var(--mute); background: rgb(255 255 255 / .06); }
    .acs-ask-steps span i { width: 1.25rem; height: 1.25rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .64rem; font-weight: 900; color: var(--mute); background: rgb(255 255 255 / .1); }
    .stm-scene.is-in .acs-ask-steps span { animation: acsAskStep var(--cy) infinite; }
    .stm-scene.is-in .acs-ask-steps span i { animation: acsAskStepI var(--cy) infinite; }
    .acs-ask-steps span:nth-child(2), .acs-ask-steps span:nth-child(2) i { animation-delay: calc(var(--cy) * -.75) !important; }
    .acs-ask-steps span:nth-child(3), .acs-ask-steps span:nth-child(3) i { animation-delay: calc(var(--cy) * -.45) !important; }
    @keyframes acsAskStep { 0%, 24% { color: #fff; background: rgb(245 197 24 / .16); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .55); } 28%, 100% { color: var(--mute); background: rgb(255 255 255 / .06); box-shadow: none; } }
    @keyframes acsAskStepI { 0%, 24% { color: #1d330d; background: var(--gold); } 28%, 100% { color: var(--mute); background: rgb(255 255 255 / .1); } }
    .acs-ask { contain: layout paint; }
    .acs-ask-body { display: grid; gap: 1rem; margin-top: 1.7rem; align-items: center; }
    @media (min-width: 760px) { .acs-ask-body { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1.4rem; } }
    /* The photo, a print in a white frame, with its question. */
    .acs-ask-pic { position: relative; justify-self: center; width: min(100%, 22rem); opacity: 0; }
    .stm-scene.is-in .acs-ask-pic { animation: acsAskPic var(--cy) var(--e) infinite; }
    @keyframes acsAskPic { 0% { opacity: 0; transform: translateY(16px) rotate(-7deg); } 6%, 93% { opacity: 1; transform: rotate(-2deg); } 99%, 100% { opacity: 0; transform: rotate(-2deg); } }
    .acs-ask-frame { position: relative; padding: .4rem .4rem 1.3rem; border-radius: .7rem; background: #f7f8f3; box-shadow: 0 18px 36px -18px rgb(0 0 0 / .95); }
    .acs-ask-frame > img { display: block; width: 100%; height: auto; aspect-ratio: 640 / 426; object-fit: cover; border-radius: .4rem; }
    .acs-ask-focus { position: absolute; left: 30%; top: 30%; width: 42%; height: 34%; border-radius: .4rem; opacity: 0;
        background: linear-gradient(var(--gold), var(--gold)) top left / 14px 3px no-repeat, linear-gradient(var(--gold), var(--gold)) top left / 3px 14px no-repeat,
            linear-gradient(var(--gold), var(--gold)) top right / 14px 3px no-repeat, linear-gradient(var(--gold), var(--gold)) top right / 3px 14px no-repeat,
            linear-gradient(var(--gold), var(--gold)) bottom left / 14px 3px no-repeat, linear-gradient(var(--gold), var(--gold)) bottom left / 3px 14px no-repeat,
            linear-gradient(var(--gold), var(--gold)) bottom right / 14px 3px no-repeat, linear-gradient(var(--gold), var(--gold)) bottom right / 3px 14px no-repeat; }
    .stm-scene.is-in .acs-ask-focus { animation: acsAskFocus var(--cy) var(--e) infinite; }
    @keyframes acsAskFocus { 0%, 36% { opacity: 0; transform: scale(1.5); } 41%, 54% { opacity: 1; transform: scale(1); } 58%, 100% { opacity: 0; transform: scale(1); } }
    .acs-ask-frame small { position: absolute; left: .6rem; bottom: .3rem; font-size: .6rem; font-weight: 800; color: #6b7280; }
    .acs-ask-flash { position: absolute; inset: 0; border-radius: .7rem; background: #fff; opacity: 0; pointer-events: none; }
    .stm-scene.is-in .acs-ask-flash { animation: acsAskFlash var(--cy) infinite; }
    @keyframes acsAskFlash { 0%, 5% { opacity: 0; } 7% { opacity: .9; } 13%, 100% { opacity: 0; } }
    .acs-ask-scan { position: absolute; left: .4rem; right: .4rem; top: .4rem; height: 3px; will-change: transform; border-radius: 999px; background: var(--gold); box-shadow: 0 0 14px 4px rgb(245 197 24 / .55); opacity: 0; }
    .stm-scene.is-in .acs-ask-scan { animation: acsAskScan var(--cy) linear infinite; }
    @keyframes acsAskScan { 0%, 26% { opacity: 0; transform: translateY(0); } 28% { opacity: 1; transform: translateY(0); } 40% { transform: translateY(var(--sh, 12rem)); } 52% { opacity: 1; transform: translateY(0); } 54%, 100% { opacity: 0; transform: translateY(0); } }

    .acs-ask-q { position: absolute; right: -.4rem; top: -.9rem; max-width: 70%; padding: .4rem .65rem; border-radius: .9rem .9rem .2rem .9rem; font-size: .72rem; font-weight: 700; line-height: 1.35; color: #1f2a17; background: #e3f1d2;
        box-shadow: 0 10px 20px -10px rgb(0 0 0 / .8); opacity: 0; }
    .stm-scene.is-in .acs-ask-q { animation: acsAskQ var(--cy) var(--e) infinite; }
    @keyframes acsAskQ { 0%, 9% { opacity: 0; transform: translateY(8px) scale(.9); } 13%, 93% { opacity: 1; transform: none; } 99%, 100% { opacity: 0; } }
    /* Anee: waiting, looking, answering. */
    .acs-ask-anee { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem .9rem; border-radius: 1rem; background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .acs-ask-face { position: relative; flex: none; width: 2.6rem; height: 2.6rem; }
    .acs-ask-face img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; opacity: 0; }
    .acs-ask-face img.p1 { opacity: 1; }
    .acs-ask-say { position: relative; flex: 1; min-width: 0; display: grid; }
    .acs-ask-say > div { grid-area: 1 / 1; opacity: 0; }
    .acs-ask-say b { display: block; font-size: .9rem; line-height: 1.4; color: #fff; }
    .acs-ask-say p { margin-top: .3rem; font-size: .8rem; line-height: 1.5; color: var(--ink); }
    .acs-ask-say ul { margin: .35rem 0 0; padding: 0; list-style: none; display: grid; gap: .2rem; font-size: .78rem; line-height: 1.45; color: var(--ink); }
    .acs-ask-sure { display: inline-block; margin-left: .3rem; padding: .05rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 900; color: #1d330d; background: var(--leaf); vertical-align: .1rem; }
    .stm-scene.is-in .acs-ask .p1 { animation: acsAskP1 var(--cy) infinite; }
    .stm-scene.is-in .acs-ask .p2 { animation: acsAskP2 var(--cy) infinite; }
    .stm-scene.is-in .acs-ask .p3 { animation: acsAskP3 var(--cy) infinite; }
    @keyframes acsAskP1 { 0%, 23% { opacity: 1; transform: none; } 27%, 100% { opacity: 0; transform: translateY(-4px); } }
    @keyframes acsAskP2 { 0%, 25% { opacity: 0; transform: translateY(6px); } 29%, 54% { opacity: 1; transform: none; } 58%, 100% { opacity: 0; } }
    @keyframes acsAskP3 { 0%, 56% { opacity: 0; transform: translateY(8px); } 61%, 93% { opacity: 1; transform: none; } 99%, 100% { opacity: 0; } }
    .acs-ask-why { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .acs-ask-why span { padding: .15rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; color: var(--ink); background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); opacity: 0; }
    .stm-scene.is-in .acs-ask-why span { animation: acsAskWhy var(--cy) infinite; animation-delay: calc(var(--k) * .5s); }
    @keyframes acsAskWhy { 0%, 30% { opacity: 0; transform: translateY(6px) scale(.9); } 34%, 54% { opacity: 1; transform: none; } 58%, 100% { opacity: 0; } }
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
    /* On a phone (2026-10-28, the owner: "the graph part looks bad and overlaps each other elements in mobile"): the
       season down the screen, a stage a block, its work as chips, a light running down the rail. */
    .acs-tl { display: none; position: relative; margin-top: 1.4rem; padding: 1rem 1rem 1rem 1.9rem; }
    @media (max-width: 639.98px) { .acs-tl { display: block; } .acs-gantt { display: none; } }
    .acs-tl::before { content: ''; position: absolute; left: 1.05rem; top: 1.3rem; bottom: 1.3rem; width: 2px; border-radius: 999px; background: rgb(255 255 255 / .08); }
    .acs-tl-run { position: absolute; left: calc(1.05rem - 4px); top: 1.3rem; width: 10px; height: 10px; border-radius: 999px; background: var(--gold); box-shadow: 0 0 12px 3px rgb(245 197 24 / .6); opacity: 0; }
    .stm-scene.is-in .acs-tl-run { animation: acsTlRun 4.2s .5s var(--e) forwards; }
    @keyframes acsTlRun { 0% { opacity: 0; top: 1.3rem; } 8% { opacity: 1; } 92% { opacity: 1; top: calc(100% - 1.6rem); } 100% { opacity: 0; top: calc(100% - 1.6rem); } }
    .acs-tl-st { position: relative; padding: .1rem 0 .85rem; opacity: 0; transform: translateX(10px); transition: opacity .5s var(--e), transform .5s var(--e); transition-delay: calc(.5s + var(--k) * .7s); }
    .stm-scene.is-in .acs-tl-st { opacity: 1; transform: none; }
    .acs-tl-st:last-of-type { padding-bottom: 0; }
    .acs-tl-st::before { content: ''; position: absolute; left: calc(-1.9rem + 1.05rem - 5px); top: .25rem; width: 12px; height: 12px; border-radius: 999px; background: var(--c); box-shadow: 0 0 0 3px rgb(11 20 7 / .95); }
    .acs-tl-h { display: flex; align-items: baseline; gap: .5rem; }
    .acs-tl-h b { font-size: .92rem; font-weight: 800; color: #fff; }
    .acs-tl-h small { font-size: .7rem; font-weight: 800; color: var(--c); }
    .acs-tl-tasks { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .acs-tl-tasks span { padding: .18rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--ink); background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1);
        opacity: 0; transform: scale(.85); transition: opacity .4s var(--e), transform .4s var(--e); transition-delay: calc(.8s + var(--k) * .7s + var(--j) * .12s); }
    .stm-scene.is-in .acs-tl-tasks span { opacity: 1; transform: none; }
    .acs-g-track { margin: 0 .45rem; }
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
        .acs-grid i b { opacity: 1; transform: none; animation: none !important; }
        .acs-goal *, .acs-goal-b, .acs-proto * { animation: none !important; transition: none !important; }
        .acs-goal-b, .acs-goal-r em, .acs-proto-in span, .acs-proto-doc { opacity: 1; transform: none; }
        .acs-goal-r i u { transform: none; } .acs-proto-doc span { clip-path: none; }
        .acs-grid i.today::after { animation: none !important; display: none; }
        .acs-g-st, .acs-g-dot { transition: none !important; }
        .acs-ty *, .acs-ty-anee * { animation: none !important; }
        .acs-ty-chip { opacity: 1; left: calc(3 * (var(--cw) + var(--g)) + var(--cw) / 2); translate: -50% 0; }
        .acs-ty-chip em.ok, .acs-ty-say .done, .acs-ty-face img.done { opacity: 1; transform: none; }
        .acs-ty-say .think, .acs-ty-face img.think, .acs-ty-wind { display: none; }
        .acs-ty-d.is-best { background: rgb(245 197 24 / .14); box-shadow: inset 0 0 0 2px var(--gold); }
        .acs-ask *, .acs-ask-anee * { animation: none !important; }
        .acs-n, .acs-once *, .acs-bud *, .acs-proto *, .acs-morn *, .acs-chk * { animation: none !important; }
        .acs-once-r, .acs-once-r i, .acs-once-stamp, .acs-proto-doc, .acs-proto-ok, .acs-morn-n, .acs-morn-n li, .acs-morn-done, .acs-chk-r, .acs-chk-plan, .acs-chk-r small em.found, .acs-chk-r .st u { opacity: 1; transform: none; }
        .acs-once-r b, .acs-proto-doc span { clip-path: none; } .acs-chk-r small em:not(.found), .acs-chk-r .st i { display: none; } .acs-chk-bar i { transform: none; }
        .acs-bud-o > div:not(:nth-child(2)) { display: none; } .acs-bud-o > div:nth-child(2) { opacity: 1; } .acs-bud-t i { transform: translateX(100%); }
        .acs-vs-bar i, .acs-vs-rows div { transform: none; opacity: 1; transition: none; }
        .acs-wtp *, .acs-wtp-why span { animation: none !important; transition: none !important; }
        .acs-lb * { animation: none !important; }
        .acs-lb-staves i.zn, .acs-lb-staves i.s { height: 96%; } .acs-lb-water { height: 92%; } .acs-lb-say span.c { opacity: 1; }
        .acs-wtp-bars i { transform: none; } .acs-wtp-line { clip-path: none; } .acs-wtp-pick, .acs-wtp-why span { opacity: 1; transform: none; }
        .acs-wtp-tabs span:first-child { color: #1d330d; background: var(--gold); }
        .acs-ask-pic, .acs-ask-q, .acs-ask .p3, .acs-ask-face img.p3 { opacity: 1; transform: none; }
        .acs-ask-pic { transform: rotate(-2deg); }
        .acs-ask-scan, .acs-ask-flash { display: none; }
        .acs-g-now, .acs-tl-run { display: none; }
        .acs-tl-st, .acs-tl-tasks span { opacity: 1; transform: none; transition: none !important; }
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
                <p class="stm-p" data-in style="--d: 200ms">Tell her about your farm once. She writes every task of the season for each lot, tells you and your crew what to do each morning, and moves the work when the rain or the crew changes. <b class="text-white">You do not have to think about what to do: just follow Anee.</b></p>
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

    {{-- 2. Five steps (2026-10-28, the owner: "add a section about the realistic goals based on your budget and creating your
         complete protocol based on all the factors (i think this should be in the part about the three steps then she takes over)") --}}
    <section class="stm-scene" data-title="How it starts">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Five steps, then she takes over</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">You answer once. She does the planning every day after.</h2>
            <div class="acs-steps">
                <div class="acs-card acs-step" data-in style="--d: 160ms">
                    <span class="acs-n">1</span>
                    {{-- The answers fill in and are ticked, then the promise is stamped (2026-10-28, again the owner). --}}
                    <div class="acs-art" aria-hidden="true"><div class="acs-once">
                        @foreach ([['🌾 Crop', 'Palay, inbred'], ['🟤 Soil', 'Clay loam'], ['💧 Water', 'Irrigated'], ['👷 Crew', '3 workers']] as $k => [$q, $ans])
                            <div class="acs-once-r" style="--k: {{ $k }}"><span>{{ $q }}</span><b>{{ $ans }}</b><i>✓</i></div>
                        @endforeach
                        <span class="acs-once-stamp">✓ Told once, kept all season</span>
                    </div></div>
                    <b>Tell her once, she remembers</b>
                    <p>A few taps about your lots, crop, soil, water, budget and workers. She keeps it and reads it every day after, so you never repeat yourself. The weather she reads herself.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 220ms">
                    <span class="acs-n">2</span>
                    {{-- No amount, only how much there is to spend: the budget slides, and Anee's plan follows it (2026-10-28, the owner). --}}
                    <div class="acs-art" aria-hidden="true"><div class="acs-bud">
                        <div class="acs-bud-t"><i></i><span>Low budget</span><span>Middle</span><span>Full budget</span></div>
                        <div class="acs-bud-o">
                            <div><b>🌱 Keep it lean</b><small>Basal and one top dress, sprays only if needed</small><u><i style="--v: 55%"></i>about 4.8 t</u></div>
                            <div><b>🌾 Realistic</b><small>Three splits, weekly scouting, sprays when needed</small><u><i style="--v: 76%"></i>about 6.5 t</u></div>
                            <div><b>🚀 High potential</b><small>Four splits, micronutrients, full protection</small><u><i style="--v: 96%"></i>about 8 t</u></div>
                        </div>
                    </div></div>
                    <b>Pick a goal you can afford</b>
                    <p>Low budget, middle or full: say what you can spend. Anee adapts and builds the best protocol for it, with the yield it can reach and the fertilizer it takes.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 280ms">
                    <span class="acs-n">3</span>
                    {{-- What she weighs flies into one plan, then its lines are written. --}}
                    <div class="acs-art" aria-hidden="true"><div class="acs-proto">
                        <div class="acs-proto-in">@foreach (['🟤 Soil', '🌦️ Weather', '🌾 Variety', '💰 Budget', '👷 Crew'] as $k => $f)<span style="--k: {{ $k }}">{{ $f }}</span>@endforeach</div>
                        <div class="acs-proto-doc">
                            <small>📋 Your protocol</small>
                            @foreach (['🧪 Day 0 · 4 bags complete', '🧪 Day 14 · 1.5 bags urea', '🛡️ Day 30 · spray if needed', '🧺 Day 110 · harvest'] as $k => $l)<span style="--k: {{ $k }}"><i>✓</i>{{ $l }}</span>@endforeach
                            <em class="acs-proto-ok">✓ Ready for you</em>
                        </div>
                    </div></div>
                    <b>She builds your whole protocol</b>
                    <p>From your soil, the weather of your place, your variety, your budget and your crew: every fertilizer, spray and watering for the season, for you to approve.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 340ms">
                    <span class="acs-n">4</span>
                    <div class="acs-art" aria-hidden="true"><div class="acs-cal">
                        @php
                            // The work of a season on its days: [icon, colour] by kind, then the days that carry work.
                            $asTaskKinds = ['prep' => ['🚜', '#e0a458'], 'plant' => ['🌱', '#a8cc7e'], 'fert' => ['🧪', '#f5c518'], 'water' => ['💧', '#7cc4f5'],
                                'spray' => ['🛡️', '#e98b5d'], 'scout' => ['👀', '#c4b5fd'], 'harvest' => ['🧺', '#f0b44a']];
                            $asMonth = [0 => 'prep', 2 => 'prep', 4 => 'plant', 6 => 'water', 8 => 'fert', 10 => 'scout', 12 => 'water', 13 => 'spray', 15 => 'fert',
                                17 => 'water', 18 => 'scout', 20 => 'fert', 21 => 'water', 23 => 'spray', 24 => 'scout', 26 => 'water', 27 => 'harvest'];
                        @endphp
                        <div class="acs-wk">@foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $d)<span>{{ $d }}</span>@endforeach</div>
                        <div class="acs-grid">
                            @for ($c = 0; $c < 28; $c++)
                                @if (isset($asMonth[$c]))
                                    @php [$asIcon, $asColor] = $asTaskKinds[$asMonth[$c]]; @endphp
                                    {{-- Each its own pace and start, so the month never blinks together. --}}
                                    <i class="{{ $c === 9 ? 'today' : '' }}" style="--c: {{ $asColor }}; --t: {{ 4.2 + (($c * 37) % 23) / 10 }}s; --w: -{{ (($c * 53) % 50) / 10 }}s"><b>{{ $asIcon }}</b></i>
                                @else
                                    <i class="{{ $c === 9 ? 'today' : '' }}"></i>
                                @endif
                            @endfor
                        </div>
                    </div></div>
                    <b>She writes the season</b>
                    <p>Every task on its day for each lot: land prep, planting, each fertilizer, the water, the checks and the harvest.</p>
                </div>
                <div class="acs-card acs-step" data-in style="--d: 400ms">
                    <span class="acs-n">5</span>
                    {{-- The morning message arrives, its work gets done one by one (2026-10-28, again the owner). --}}
                    <div class="acs-art" aria-hidden="true"><div class="acs-morn">
                        <div class="acs-morn-n">
                            <small><img src="{{ $asFace('salute') }}" alt="">Anee · 5:30 AM</small>
                            <b>Today: 3 tasks on 2 lots</b>
                            <ul>@foreach (['🧪 Lot 1: fertilize before the rain', '🔍 Lot 2: check for stem borers', '💧 Lot 1: let the water in'] as $k => $t)<li style="--k: {{ $k }}"><i>✓</i>{{ $t }}</li>@endforeach</ul>
                        </div>
                        <span class="acs-morn-done">🎉 All done today</span>
                    </div></div>
                    <b>Every morning, today's work</b>
                    <p>In the app and by email, for you and for each worker. Tell her what happened and she adjusts.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 2b. When to plant: the safest months and the best paid ones --}}
    <section class="stm-scene" data-title="When to plant">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Before the season</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">The safest time to plant, read from twenty years of your town's weather.</h2>
            <p class="stm-p" data-in style="--d: 140ms">Anee keeps the history of your place: its storms, floods, dry spells and heat, month by month. She weighs it with the ENSO outlook, your soil and your water, and the price at harvest from our record of prices for each crop. Then she shows you two answers: the safest months, and the ones that pay best.</p>
            @php
                // An example year of planting months for rice: [month, risk 0 to 100, price at harvest 0 to 100].
                $asYear = [['J', 34, 82], ['F', 38, 86], ['M', 46, 74], ['A', 55, 60], ['M', 62, 50], ['J', 66, 40], ['J', 78, 34], ['A', 86, 42], ['S', 82, 52], ['O', 70, 58], ['N', 24, 64], ['D', 20, 70]];
                $asRiskColor = fn ($r) => $r < 40 ? '#8fc96a' : ($r < 65 ? '#f0cf5c' : '#e98b5d');
            @endphp
            <div class="acs-card acs-wtp" data-in style="--d: 200ms" role="img" aria-label="An example year for rice. The risk of planting is lowest in November and December, when storms and floods are fewest. The price at harvest is best for planting in January and February.">
                <div class="acs-wtp-tabs" aria-hidden="true"><span>🛡️ Safest</span><span>💰 Best price at harvest</span></div>
                <div class="acs-wtp-chart" aria-hidden="true">
                    <div class="acs-wtp-bars">@foreach ($asYear as $k => [$m, $r, $pr])<i style="--h: {{ $r }}%; --c: {{ $asRiskColor($r) }}; --k: {{ $k }}"></i>@endforeach</div>
                    <svg class="acs-wtp-line" viewBox="0 0 120 100" preserveAspectRatio="none"><polyline points="{{ collect($asYear)->map(fn ($y, $k) => (($k + .5) * 10) . ',' . (100 - $y[2]))->implode(' ') }}"/></svg>
                    <span class="acs-wtp-pick"><b>🛡️ Safest</b><b>💰 Pays best</b></span>
                </div>
                <div class="acs-wtp-m" aria-hidden="true">@foreach ($asYear as [$m])<span>{{ $m }}</span>@endforeach</div>
                <div class="acs-wtp-key" aria-hidden="true"><span><i></i>Risk of the month: storms, floods, dry spells, heat</span><span><i class="ln"></i>Price at harvest</span></div>
                <div class="acs-wtp-why">
                    @foreach (['📜 20 years of your town', '🌀 Storms and floods by month', '🌊 The ENSO outlook', '🟤 Your soil', '💧 Your water', '💰 Prices at harvest for your crop'] as $k => $w)<span style="--k: {{ $k }}">{{ $w }}</span>@endforeach
                </div>
            </div>
            <p class="stm-fine" data-in style="--d: 280ms">An example year for rice. Yours is read for your crop, your lots and your town, with the harvest day each planting day gives.</p>
        </div>
    </section>

    {{-- 2c. The law of the minimum: Liebig's barrel --}}
    <section class="stm-scene" data-title="The short stave">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>The law of the minimum</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Your harvest is held back by the one nutrient that runs out first.</h2>
            <p class="stm-p" data-in style="--d: 140ms">Like a barrel that holds water only up to its shortest stave. Anee reads what your goal takes, what your crop needs at each stage and what your soil already gives, finds the short stave, and plans what fills it. Nothing wasted on what is already enough.</p>
            <div class="acs-card acs-lb" data-in style="--d: 200ms" role="img" aria-label="An example barrel of seven nutrients. Zinc is the shortest stave, so the harvest stops there; with zinc added, sulfur is next; with both added, every nutrient reaches the goal of 6.5 tons.">
                <div class="acs-lb-barrel" aria-hidden="true">
                    <div class="acs-lb-staves">
                        @foreach ([['N', 97, ''], ['P', 95, ''], ['K', 98, ''], ['S', 70, 's'], ['Zn', 52, 'zn'], ['Mg', 96, ''], ['B', 97, '']] as [$el, $h, $cls])
                            <i class="{{ $cls }}" style="--h: {{ $h }}%"></i>
                        @endforeach
                    </div>
                    <span class="acs-lb-hoop" style="bottom: 22%"></span><span class="acs-lb-hoop" style="bottom: 66%"></span>
                    <span class="acs-lb-water"></span>
                    <span class="acs-lb-spill zn"></span><span class="acs-lb-spill s"></span>
                    <span class="acs-lb-goal"><span>🎯 Your goal, 6.5 t</span></span>
                    <span class="acs-lb-add zn">🧪 Zinc added</span><span class="acs-lb-add s">🧪 Sulfur added</span>
                </div>
                <div class="acs-lb-names" aria-hidden="true">@foreach (['N', 'P', 'K', 'S', 'Zn', 'Mg', 'B'] as $el)<b class="{{ in_array($el, ['S', 'Zn'], true) ? 'is-short' : '' }}">{{ $el }}</b>@endforeach</div>
                <div class="acs-lb-say" aria-hidden="true">
                    <span class="a">Zinc runs out first<small>The harvest stops at 4.9 t, whatever else you add</small></span>
                    <span class="b">Now sulfur is the short stave<small>Held at 5.8 t until it is filled too</small></span>
                    <span class="c">✓ Every nutrient reaches your goal<small>6.5 t, with nothing spent where the soil already had enough</small></span>
                </div>
                <div class="acs-lb-why">@foreach (['🎯 Your goal', '🌾 What the crop takes, stage by stage', '🟤 What your soil already gives', '🧪 Only what is short'] as $w)<span>{{ $w }}</span>@endforeach</div>
            </div>
            <p class="stm-fine" data-in style="--d: 280ms">An example. Your barrel is read from your soil, your crop, your variety and your goal.</p>
        </div>
    </section>

    {{-- 3. The work moved around a typhoon --}}
    <section class="stm-scene" data-title="Around the storm">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>She keeps it on track</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Can't fertilize today because of a typhoon? Anee finds the best day for it.</h2>
            <p class="stm-p" data-in style="--d: 140ms">Let Anee think for you. She reads the storm, how soon the field drains, the crop's stage and who can work, then moves the task. The days after it follow.</p>
            <div class="acs-card acs-ty" data-in style="--d: 200ms" role="img" aria-label="An example week. Today, Wednesday, and Thursday bring a typhoon and Friday 40 mm of rain. The fertilizer planned for today moves to Saturday, the first dry day after the storm, still at the right stage.">
                <div class="acs-ty-days">
                    <span class="acs-ty-wind"><i></i><i></i><i></i></span>
                    <span class="acs-ty-chip"><span>🧪 Fertilizer</span><em class="no">✕ Typhoon today</em><em class="ok">✓ Best day</em></span>
                    @foreach ($asWeek as $i => [$d, $sky, $says, $kind])
                        <div class="acs-ty-d{{ $kind === 'storm' ? ' is-storm' : ($kind === 'wet' ? ' is-wet' : ($kind === 'best' ? ' is-best' : '')) }}">
                            @if ($i === 0)<u>Today</u>@endif
                            <b>{{ $d }}</b><i>{{ $sky }}</i><small>{{ $says }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="acs-ty-win"><span>🌾 The right stage for it</span></div>
            </div>
            <div class="acs-ty-anee" data-in style="--d: 280ms">
                <span class="acs-ty-face"><img class="think" src="{{ $asFace('thinking') }}" alt=""><img class="done" src="{{ $asFace('thumbsup') }}" alt=""></span>
                <div class="acs-ty-say">
                    <div class="think">
                        <b>Anee is thinking<span class="acs-ty-dots" aria-hidden="true"><i></i><i></i><i></i></span></b>
                        <div class="acs-ty-why">
                            <span style="--k: 0">🌀 Typhoon today and tomorrow</span><span style="--k: 1">💧 The field drains by Saturday</span>
                            <span style="--k: 2">🌾 Still the right stage</span><span style="--k: 3">👷 Juan and Pedro are free</span>
                        </div>
                    </div>
                    <div class="done">
                        <b>Moved to Saturday morning.</b>
                        <p>The first dry day after the storm, while the crop still takes it best. The crew is told, and the days after it follow.</p>
                    </div>
                </div>
            </div>
            <p class="stm-fine" data-in style="--d: 340ms">An example week. Every morning she reads the real forecast for your farm. The same when a worker cannot come.</p>
        </div>
    </section>

    {{-- 3b. Ask her anything: a photo, Anee looks, the answer --}}
    <section class="stm-scene" data-title="Ask her anything">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Ask her anything</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Something looks wrong? Show Anee a photo and ask.</h2>
            <p class="stm-p" data-in style="--d: 140ms">A spot on a leaf, a bug you do not know, when to water. She knows your lot, its stage and its weather, so the answer fits your field.</p>
            <div class="acs-card acs-ask" data-in style="--d: 200ms">
                <div class="acs-ask-steps" aria-hidden="true"><span><i>1</i>Show her</span><span><i>2</i>She looks</span><span><i>3</i>She answers</span></div>
                <div class="acs-ask-body">
                    <div class="acs-ask-pic" role="img" aria-label="An example: a photo of a rice leaf with a spindle shaped spot, gray in the middle with a brown border.">
                        <div class="acs-ask-frame">
                            {{-- A real photo from the disease finder (2026-10-28, the owner: "use a real photo in the sample"): rice blast, public domain. --}}
                            <img src="{{ asset('images/site/diseases/rice-blast.webp') }}" alt="" width="640" height="426" loading="lazy" decoding="async">
                            <span class="acs-ask-focus"></span>
                            <small>📷 From Lot 1 · photo: public domain</small>
                            <span class="acs-ask-scan"></span>
                            <span class="acs-ask-flash"></span>
                        </div>
                        <span class="acs-ask-q">What is this spot on my palay?</span>
                    </div>
                    <div class="acs-ask-anee">
                        <span class="acs-ask-face"><img class="p1" src="{{ $asFace('wave') }}" alt=""><img class="p2" src="{{ $asFace('thinking') }}" alt=""><img class="p3" src="{{ $asFace('thumbsup') }}" alt=""></span>
                        <div class="acs-ask-say" aria-live="off">
                            <div class="p1"><b>Send a photo and a few words.</b><p>Or a short video, or a voice note. Close to the leaf, in good light.</p></div>
                            <div class="p2">
                                <b>Anee is looking<span class="acs-ty-dots" aria-hidden="true"><i></i><i></i><i></i></span></b>
                                <div class="acs-ask-why">
                                    <span style="--k: 0">🔍 Spindle shaped spot</span><span style="--k: 1">⚪ Gray centers, brown edges</span>
                                    <span style="--k: 2">🌫️ Humid nights this week</span><span style="--k: 3">🌾 Lot 1 is tillering</span>
                                </div>
                            </div>
                            <div class="p3">
                                <b>🍄 Rice blast, most likely<span class="acs-ask-sure">High</span></b>
                                <ul><li>✋ Hold the next nitrogen for now.</li><li>🧴 If the spots spread, spray tricyclazole, by its label.</li><li>👀 I added a field walk on Friday to check it.</li></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="stm-fine" data-in style="--d: 280ms">An example. Usage limits apply: each lot comes with its questions and photos for the season.</p>
        </div>
    </section>

    {{-- 4. What she reads each morning --}}
    <section class="stm-scene" data-title="What she reads">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Every morning, before you wake up</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">She weighs everything a good farm technician would, for every lot.</h2>
            <p class="stm-p" data-in style="--d: 140ms">While you sleep, Anee goes through your farm the way a good technician would: the weather, every lot's stage, your crew, the shed, your reports, the pests near you and the prices. Then she writes today's plan.</p>
            @php
                // What she checks, and what she finds this morning (an example).
                $asChecks = [['🌦️', 'The forecast', 'Rain, wind and heat for your farm', 'Rain after 2 PM'], ['🌱', 'Each lot\'s stage', 'Counted from the day it was planted', 'Lot 1 is tillering'],
                    ['👷', 'Your crew', 'Who works when, half days too', 'Juan is off today'], ['📦', 'The shed', 'What is in stock, what to buy', '2 bags of urea left'],
                    ['📸', 'Your reports', 'Photos, notes and voice notes', 'Your photo: no pests'], ['🐛', 'Pests near you', 'What the season and the area bring', 'Stem borers nearby'],
                    ['💰', 'Prices', 'When it pays to sell', 'Palay price steady']];
            @endphp
            <style>
                @foreach ($asChecks as $k => $c)
                    @php $t0 = 6 + $k * 10; $t1 = $t0 + 7; @endphp
                    .stm-scene.is-in .acs-chk-r.r{{ $k }} { animation: acsChkR{{ $k }} var(--cy) infinite; }
                    .stm-scene.is-in .acs-chk-r.r{{ $k }} .st i { animation: acsSpin .8s linear infinite, acsChkS{{ $k }} var(--cy) infinite; }
                    .stm-scene.is-in .acs-chk-r.r{{ $k }} .st u, .stm-scene.is-in .acs-chk-r.r{{ $k }} small em.found { animation: acsChkO{{ $k }} var(--cy) infinite; }
                    .stm-scene.is-in .acs-chk-r.r{{ $k }} small em:not(.found) { animation: acsChkL{{ $k }} var(--cy) infinite; }
                    @keyframes acsChkR{{ $k }} { 0%, {{ $t0 - 1 }}% { opacity: .4; } {{ $t0 }}%, 96% { opacity: 1; } 100% { opacity: .4; } }
                    @keyframes acsChkS{{ $k }} { 0%, {{ $t0 - 1 }}% { opacity: 0; } {{ $t0 }}%, {{ $t1 }}% { opacity: 1; } {{ $t1 + 1 }}%, 100% { opacity: 0; } }
                    @keyframes acsChkO{{ $k }} { 0%, {{ $t1 }}% { opacity: 0; transform: scale(.6); } {{ $t1 + 2 }}%, 96% { opacity: 1; transform: none; } 100% { opacity: 0; } }
                    @keyframes acsChkL{{ $k }} { 0%, {{ $t1 }}% { opacity: 1; } {{ $t1 + 2 }}%, 96% { opacity: 0; } 100% { opacity: 1; } }
                @endforeach
            </style>
            <div class="acs-card acs-chk" data-in style="--d: 200ms" role="img" aria-label="An example morning: Anee checks the forecast, each lot's stage, the crew, the shed, the reports, the pests near the farm and the prices, then writes three tasks for today.">
                <div class="acs-chk-top"><img src="{{ $asFace('thinking') }}" alt=""><div><b>Anee's morning check</b><small><span>Checking your farm, from 4:00 AM</span><span>✓ Done at 5:30 AM</span></small></div></div>
                <div class="acs-chk-bar"><i></i></div>
                <div class="acs-chk-list">
                    @foreach ($asChecks as $k => [$emo, $t, $l, $f])
                        <div class="acs-chk-r r{{ $k }}"><span>{{ $emo }}</span><div><b>{{ $t }}</b><small><em>{{ $l }}</em><em class="found">{{ $f }}</em></small></div><span class="st"><i></i><u>✓</u></span></div>
                    @endforeach
                </div>
                <div class="acs-chk-plan">
                    <div class="acs-out">
                        <small><img src="{{ $asFace('salute') }}" alt="" style="width:1.1rem;height:1.1rem">This morning's plan</small>
                        <b>3 tasks, in the right order</b>
                        <div class="acs-t">🧪 Lot 1: fertilize before 10 AM<em>Rain after 2 PM</em></div>
                        <div class="acs-t">🔍 Lot 2: check for stem borers<em>Seen nearby</em></div>
                        <div class="acs-t">🛒 Buy 2 bags of urea for Friday</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. The season at a glance --}}
    <section class="stm-scene" data-title="The season">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Your season at a glance</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Every task on its day: at the crop's stage, and when it works best for the least money.</h2>
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
            @php
                // The same season by stage, for the phone: each stage's work, one chip a kind (its days together).
                $asTl = [];
                $asSay = ['Fertilizer' => null, 'Scouting' => 'Walk the field', 'Sprays when needed' => 'Spray if needed', 'Water' => null, 'Harvest' => 'Harvest'];
                foreach ($asStages as $si => [$sn, $sa, $sb]) {
                    $chips = [];
                    foreach ($asWork as [$emo, $label, $marks]) {
                        $in = array_values(array_filter($marks, fn ($m) => $m[0] >= $sa && ($m[0] < $sb || ($si === count($asStages) - 1 && $m[0] <= $sb))));
                        if (! $in) {
                            continue;
                        }
                        $named = array_key_exists($label, $asSay) ? $asSay[$label] : $label;
                        if ($named === null) {
                            foreach ($in as [$day, $tip]) {
                                $chips[] = $emo . ' Day ' . $day . ' · ' . ($tip !== '' ? $tip : $label);
                            }
                        } else {
                            $chips[] = $emo . ' ' . $named . ' · day' . (count($in) > 1 ? 's ' : ' ') . implode(' and ', array_column($in, 0));
                        }
                    }
                    $asTl[] = [$sn, $sa, $sb, $chips];
                }
            @endphp
            <div class="acs-card acs-tl" data-in style="--d: 160ms" role="img" aria-label="An example rice season of 110 days, stage by stage, with the work of each stage.">
                <span class="acs-tl-run" aria-hidden="true"></span>
                @foreach ($asTl as $k => [$sn, $sa, $sb, $chips])
                    <div class="acs-tl-st" style="--k: {{ $k }}; --c: {{ ['#d6ebbf', '#b5d98f', '#8fc96a', '#f0cf5c', '#e0a94a'][$k] }}">
                        <div class="acs-tl-h"><b>{{ $sn }}</b><small>Day {{ $sa }} to {{ $sb }}</small></div>
                        <div class="acs-tl-tasks">@foreach ($chips as $j => $c)<span style="--j: {{ $j }}">{{ $c }}</span>@endforeach</div>
                    </div>
                @endforeach
            </div>
            <div class="acs-g-why" data-in style="--d: 220ms"><span>🎯 Fertilizer when the crop takes it up best</span><span>🌧️ Never just before rain that washes it away</span><span>🧪 Two sprays in one trip when they mix</span><span>💰 Only what the crop is short of</span></div>
            <p class="stm-fine" data-in style="--d: 260ms">An example rice season. Yours is written for your crop, your variety, your lots and your weather, and changes as the season does.</p>
        </div>
    </section>

    {{-- 6. Why it is worth it --}}
    <section class="stm-scene" data-title="Worth it">
        <div class="stm-wrap" data-in style="container-type: inline-size">
            {{-- The same hectare, without Anee and with her (2026-10-28, the owner: "create an animated comparison ... spending 1500
                 per lot for anee to manage your crop ... gives you this increase in potential yield, protection from disaster, etc.
                 and potential added income ... what you do now without anee ... all the lost yield and disasters and lost income"). --}}
            <div class="acs-vs">
                <p class="stm-kick">Worth it?</p>
                <h3 class="acs-vs-h">The same hectare of palay, two ways<small>An example, at ₱20 a kilo. A field that could give about 5.5 tons.</small></h3>
                <div class="acs-vs-cols">
                    <div class="acs-vs-c">
                        <h4>😟 What you do now, without Anee</h4>
                        <div class="acs-vs-bar"><i class="keep" style="--w: .673; --k: 0">3.7 t</i><i class="lost" style="--w: .109; --k: 1"></i><i class="lost" style="--w: .127; --k: 2"></i><i class="lost" style="--w: .091; --k: 3"></i></div>
                        <div class="acs-vs-rows">
                            <div style="--k: 1">⏰ Fertilizer late or at the wrong stage<b>−0.6 t</b></div>
                            <div style="--k: 2">🐛 A pest seen too late<b>−0.7 t</b></div>
                            <div style="--k: 3">🌀 A typhoon week with no plan<b>−0.5 t</b></div>
                            <div style="--k: 4">🌧️ Sprays and fertilizer washed away<b>−₱2,500</b></div>
                        </div>
                        <div class="acs-vs-tot"><span>Harvest worth</span><b>₱<span data-count="71500">0</span></b></div>
                    </div>
                    <div class="acs-vs-c is-with">
                        <h4><img src="{{ $asFace('thumbsup') }}" alt="">With Anee Auto<small>{{ number_format($asPrice) }} credits a lot</small></h4>
                        <div class="acs-vs-bar"><i class="keep" style="--w: .673; --k: 0">3.7 t</i><i class="gain" style="--w: .109; --k: 1"></i><i class="gain" style="--w: .127; --k: 2"></i><i class="gain" style="--w: .091; --k: 3"></i></div>
                        <div class="acs-vs-rows">
                            <div style="--k: 1">🎯 Fertilizer on time, by stage<b>+0.6 t</b></div>
                            <div style="--k: 2">🔍 Weekly scouting, an early spray<b>+0.7 t</b></div>
                            <div style="--k: 3">🌀 Work moved around the storm<b>+0.5 t</b></div>
                            <div style="--k: 4">💰 Nothing sprayed into the rain<b>+₱2,500</b></div>
                        </div>
                        <div class="acs-vs-tot"><span>Harvest worth</span><b>₱<span data-count="110000">0</span></b></div>
                    </div>
                </div>
                <div class="acs-vs-net"><img src="{{ $asFace('cheer') }}" alt=""><p>That is about <b>₱<span data-count="38500">0</span></b> more from one hectare, for {{ number_format($asPrice) }} credits. <span class="text-xs opacity-80">An example, not a promise: your season depends on your field, the weather and the work done.</span></p></div>
            </div>
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
