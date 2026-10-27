@extends('layouts.app')
@section('title', 'Anee Auto')
@section('body-class', 'hide-tabbar')
@section('page-title', 'Anee Auto')
@section('page-subtitle', $schedule->title)
@section('back', route('auto.index'))

{{-- ANEE AUTO, THE SEASON (2026-10-12; cards since 2026-10-27, the owner:
     "don't make it look like a simple chat. so daily thing should appear as
     cards"). Still a conversation with Anee, read top to bottom, but every
     day's thing is a card of its own: her morning (the sky, each lot's day,
     the day's checklist), a task done, a day that could not be and where she
     moved it, a picture from the field and what she sees in it, each lot's
     plan. Plain talk (a question and her answer) stays as speech. The last
     card is today's plan, with Done and Can't today on each task; the
     composer asks or shows her the field. The cards are drawn from
     AneeAutoController::feed (cardOf). --}}
@section('content')
@include('partials.anee-emoji')
<style>
    :root { --ac-ease: cubic-bezier(.22,1,.36,1); }
    .ac { max-width: 46rem; margin: 0 auto; display: flex; flex-direction: column; min-height: calc(100svh - var(--app-head, 4rem) - 1.5rem); }
    @keyframes acIn { from { opacity: 0; transform: translateY(10px); } }
    @keyframes acPop { 0% { transform: scale(.4); opacity: 0; } 60% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(1); } }
    @keyframes acDrift { to { background-position: 120px 110px, -170px 150px; } }
    @keyframes acRise { from { transform: translateY(14px); opacity: .2; } }
    @keyframes acSpin { to { transform: rotate(360deg); } }

    /* ---- the season at a glance ---- */
    .ac-head { position: relative; overflow: hidden; border-radius: 1.4rem; padding: 1rem 1rem .95rem; color: #e8f1de; margin-bottom: .9rem;
        background: radial-gradient(130% 170% at 100% 0%, #5d9431 0%, #24410f 55%, #10200a 100%); box-shadow: 0 22px 44px -32px rgb(16 32 10 / .9); }
    .ac-head::after { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .45;
        background-image: radial-gradient(circle at 15% 30%, rgb(255 255 255 / .5) 0 1px, transparent 1.5px), radial-gradient(circle at 75% 70%, rgb(255 255 255 / .35) 0 1px, transparent 1.5px);
        background-size: 120px 110px, 170px 150px; animation: acDrift 30s linear infinite; }
    .ac-head-row { position: relative; z-index: 1; display: flex; align-items: center; gap: .75rem; }
    .ac-ring { flex: none; position: relative; width: 3.1rem; height: 3.1rem; }
    .ac-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .ac-ring circle { fill: none; stroke-width: 5; }
    .ac-ring .bg { stroke: rgb(255 255 255 / .15); }
    .ac-ring .fg { stroke: #f5c518; stroke-linecap: round; transition: stroke-dashoffset 1s var(--ac-ease); }
    .ac-ring b { position: absolute; inset: 0; display: grid; place-items: center; font-size: .72rem; font-weight: 900; color: #fff; }
    .ac-head h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: #fff; line-height: 1.25; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-head p { font-size: .76rem; color: #cfe3b9; }
    .ac-acts { position: relative; z-index: 1; display: flex; gap: .35rem; margin-top: .75rem; flex-wrap: wrap; }
    .ac-hbtn { display: inline-flex; align-items: center; gap: .3rem; padding: .38rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #e8f1de; background: rgb(255 255 255 / .13);
        transition: background-color .28s var(--ac-ease); }
    .ac-hbtn:hover { background: rgb(255 255 255 / .22); }
    .ac-lots { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(12.5rem, 1fr)); gap: .45rem; margin-top: .8rem; }
    .ac-lot { display: flex; align-items: center; gap: .55rem; padding: .55rem .7rem; border-radius: 1rem; background: rgb(255 255 255 / .1); border: 1px solid rgb(255 255 255 / .14);
        animation: acIn .4s var(--ac-ease) both; animation-delay: calc(var(--i) * 70ms); }
    .ac-lot > span { flex: none; font-size: 1.4rem; }
    .ac-lot > div { flex: 1; min-width: 0; }
    .ac-lot b { display: block; font-size: .8rem; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-lot small { display: block; font-size: .7rem; color: #cfe3b9; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-bar { position: relative; height: .3rem; margin-top: .35rem; border-radius: 999px; background: rgb(255 255 255 / .15); overflow: hidden; }
    .ac-bar i { position: absolute; inset: 0 auto 0 0; width: 0; border-radius: inherit; background: linear-gradient(90deg, #9bd06a, #f5c518); transition: width 1s var(--ac-ease); }
    .ac-week { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; margin-top: .6rem; }
    .ac-week div { text-align: center; padding: .35rem 0 .3rem; border-radius: .8rem; background: rgb(0 0 0 / .14); font-size: .64rem; font-weight: 800; color: #cfe3b9; }
    .ac-week div.is-today { background: rgb(245 197 24 / .25); color: #fff; }
    .ac-week i { display: block; font-style: normal; font-size: 1rem; line-height: 1.35; }
    .ac-week em { display: block; font-style: normal; font-weight: 700; color: #a8d4f5; }
    .ac-week s { display: flex; justify-content: center; gap: 2px; height: 5px; margin-top: .2rem; text-decoration: none; }
    .ac-week s u { width: 5px; height: 5px; border-radius: 999px; background: #f5c518; }

    /* ---- the story ---- */
    .ac-feed { flex: 1; display: flex; flex-direction: column; gap: .75rem; padding: .2rem 0 1rem; }
    .ac-more { align-self: center; font-size: .78rem; font-weight: 800; color: var(--color-brand-700); padding: .35rem .8rem; border-radius: 999px; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .ac-day { display: flex; align-items: center; gap: .6rem; margin: .35rem 0 0; font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-day::before, .ac-day::after { content: ''; flex: 1; border-top: 1px solid var(--color-gray-200); }
    .ac-row { display: flex; gap: .55rem; align-items: flex-end; animation: acIn .38s var(--ac-ease) both; }
    .ac-row.is-me { flex-direction: row-reverse; }
    .ac-av { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; background: #eef6e4; overflow: hidden; box-shadow: 0 0 0 2px #fff, 0 4px 10px -6px rgb(0 0 0 / .4); }
    .ac-av img { width: 100%; height: 100%; object-fit: cover; }
    .ac-sys { align-self: center; display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: var(--color-gray-500);
        background: var(--color-gray-100); animation: acIn .38s var(--ac-ease) both; }

    /* Speech: a question and its answer. */
    .ac-bub { min-width: 0; max-width: 34rem; padding: .65rem .85rem; border-radius: 1.15rem 1.15rem 1.15rem .35rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .88rem; line-height: 1.55; color: var(--color-gray-800); }
    .ac-row.is-me .ac-bub { border-radius: 1.15rem 1.15rem .35rem 1.15rem; background: #e7f2da; border-color: #cfe3b8; color: #1f2a17; }
    .ac-time { display: block; margin-top: .35rem; font-size: .66rem; font-weight: 600; color: var(--color-gray-400); }
    .ac-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr)); gap: .35rem; margin-top: .5rem; }
    .ac-media img, .ac-media video { width: 100%; aspect-ratio: 1; border-radius: .8rem; object-fit: cover; background: var(--color-gray-100); }
    .ac-media.is-one { grid-template-columns: minmax(0, 1fr); }
    .ac-media.is-one img, .ac-media.is-one video { aspect-ratio: 4 / 3; }
    .ac-media audio { width: 100%; grid-column: 1 / -1; }

    /* Cards: one thing that happened, with its own head. */
    .ac-card { --tone-bg: #eef6e4; --tone-ink: #2d5016; position: relative; flex: 1; min-width: 0; max-width: 34rem; border-radius: 1.2rem; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); box-shadow: 0 16px 34px -28px rgb(15 23 42 / .55); }
    .ac-row.is-me .ac-card { max-width: 26rem; }
    .ac-card[data-tone="gold"] { --tone-bg: #fdf3d3; --tone-ink: #6b4e00; } .ac-card[data-tone="rose"] { --tone-bg: #fde9ec; --tone-ink: #8c1d2f; }
    .ac-card[data-tone="sky"] { --tone-bg: #e5f2fc; --tone-ink: #1e4b6b; } .ac-card[data-tone="amber"] { --tone-bg: #fff0dc; --tone-ink: #7a4a00; }
    .ac-card-h { display: flex; align-items: center; gap: .45rem; padding: .5rem .8rem; font-size: .68rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: var(--tone-ink); background: var(--tone-bg); }
    .ac-card-h .ac-card-r { margin-left: auto; font-weight: 700; letter-spacing: 0; text-transform: none; opacity: .75; }
    .ac-card-b { padding: .75rem .85rem .8rem; font-size: .86rem; line-height: 1.55; color: var(--color-gray-700); }
    .ac-card-t { display: flex; align-items: flex-start; gap: .55rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.3; color: var(--color-gray-900); }
    .ac-card-t .ac-te { margin-top: -.1rem; }
    .ac-te { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.2rem; background: var(--tone-bg); }
    .ac-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
    .ac-chip { display: inline-flex; align-items: center; gap: .25rem; max-width: 100%; font-size: .7rem; font-weight: 800; padding: .18rem .55rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-600); }
    .ac-chip.is-crit { color: #8a5a00; background: #fff4d6; } .ac-chip.is-late { color: #9f1d1d; background: #fde8e8; } .ac-chip.is-risk { color: #1e4b6b; background: #e5f2fc; }
    .ac-chip.is-ok { color: #2d5016; background: #e4efd4; }
    .ac-say { margin-top: .5rem; }
    .ac-say p + p { margin-top: .35rem; }
    .ac-sub { margin-top: .6rem; font-size: .66rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-note { margin-top: .5rem; padding: .5rem .65rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .82rem; color: var(--color-gray-700); }
    .ac-card .ac-time { padding: 0 .85rem .55rem; margin-top: -.2rem; text-align: right; }

    /* Her morning: the sun coming up over the day. */
    .ac-morning .ac-sun { position: relative; overflow: hidden; padding: .85rem .9rem .9rem; color: #fff8e6;
        background: linear-gradient(160deg, #f5b13b 0%, #e98a35 38%, #7aa13a 100%); }
    .ac-morning.is-past .ac-sun { background: linear-gradient(160deg, #d9a55a 0%, #b9874d 40%, #6d8a45 100%); }
    .ac-sun svg { position: absolute; right: -.4rem; top: -.2rem; width: 5.6rem; height: 5.6rem; opacity: .9; animation: acRise 1.1s var(--ac-ease) both; }
    .ac-sun svg g { transform-origin: 50% 50%; animation: acSpin 40s linear infinite; }
    .ac-sun small { position: relative; display: block; font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #fff3cf; }
    .ac-sun b { position: relative; display: block; margin-top: .1rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #fff; text-shadow: 0 1px 10px rgb(120 60 0 / .35); }
    .ac-sky { position: relative; display: inline-flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; margin-top: .5rem; padding: .3rem .65rem; border-radius: 999px;
        background: rgb(255 255 255 / .22); font-size: .74rem; font-weight: 800; color: #fff; }
    .ac-mlots { display: grid; gap: .35rem; margin-top: .1rem; }
    .ac-mlot { display: flex; align-items: center; gap: .5rem; font-size: .8rem; }
    .ac-mlot b { color: var(--color-gray-900); }
    .ac-mlot em { font-style: normal; font-weight: 800; color: #2d5016; background: #e4efd4; padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; }
    .ac-check { display: grid; gap: .3rem; margin-top: .3rem; }
    .ac-crow-t { display: flex; align-items: center; gap: .55rem; width: 100%; padding: .45rem .55rem; border-radius: .8rem; text-align: left; background: var(--color-gray-50); border: 1px solid var(--color-gray-100);
        transition: background-color .28s var(--ac-ease), border-color .28s var(--ac-ease); }
    .ac-crow-t:hover { border-color: #cfe3b8; background: #f6fbf0; }
    .ac-crow-t > i { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .7rem; font-weight: 900; border: 2px solid #cbd5c1; color: transparent; }
    .ac-crow-t.is-done > i { background: #4a7c2a; border-color: #4a7c2a; color: #fff; animation: acPop .45s var(--ac-ease) both; }
    .ac-crow-t.is-crit:not(.is-done) > i { border-color: #e8a33a; }
    .ac-crow-t > span { flex: none; font-size: 1rem; }
    .ac-crow-t b { flex: 1; min-width: 0; font-size: .82rem; font-weight: 700; color: var(--color-gray-800); }
    .ac-crow-t.is-done b { color: var(--color-gray-400); text-decoration: line-through; text-decoration-color: #9bbf7a; }
    .ac-crow-t small { flex: none; font-size: .68rem; color: var(--color-gray-400); max-width: 40%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-prog { display: flex; align-items: center; gap: .55rem; margin-top: .6rem; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .ac-prog > span { flex: 1; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ac-prog > span i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #7fb84e, #4a7c2a); transition: width .9s var(--ac-ease); }
    .ac-moves { display: grid; gap: .3rem; margin-top: .3rem; }
    .ac-mv { display: flex; align-items: center; gap: .45rem; font-size: .8rem; color: var(--color-gray-700); }
    .ac-mv b { flex: 1; min-width: 0; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-mv em { flex: none; font-style: normal; font-size: .7rem; font-weight: 800; color: #1e4b6b; background: #e5f2fc; padding: .1rem .5rem; border-radius: 999px; }

    /* Done, from the farmer. */
    .ac-tick { flex: none; width: 2.3rem; height: 2.3rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #4a7c2a; box-shadow: 0 0 0 4px #e4efd4;
        animation: acPop .5s var(--ac-ease) both; }
    .ac-tick svg { width: 1.15rem; height: 1.15rem; }
    /* A day that could not be, and where it went. */
    .ac-why-big { display: flex; align-items: center; gap: .6rem; }
    .ac-why-big > span { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.4rem; background: var(--tone-bg); }
    .ac-why-big b { display: block; font-family: var(--font-heading); font-size: .98rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .ac-why-big small { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .ac-hop { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: .5rem; margin-top: .65rem; }
    .ac-hop div { padding: .45rem .5rem; border-radius: .85rem; text-align: center; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .ac-hop div.is-to { background: #e5f2fc; border-color: #bcdaf2; }
    .ac-hop small { display: block; font-size: .62rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-hop b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .ac-hop div:first-child b { color: var(--color-gray-400); text-decoration: line-through; }
    .ac-hop svg { width: 1.3rem; height: 1.3rem; color: #3b82c4; }
    /* What Anee sees in a picture. */
    .ac-sev { display: inline-flex; align-items: center; gap: .3rem; }
    .ac-dots { display: inline-flex; gap: 2px; }
    .ac-dots i { width: 6px; height: 6px; border-radius: 999px; background: currentColor; opacity: .25; }
    .ac-dots i.on { opacity: 1; }
    .ac-do { display: grid; gap: .3rem; margin-top: .3rem; }
    .ac-do li { display: flex; gap: .45rem; align-items: flex-start; font-size: .84rem; }
    .ac-do li::before { content: '✓'; flex: none; width: 1.15rem; height: 1.15rem; margin-top: .1rem; border-radius: 999px; display: grid; place-items: center; font-size: .66rem; font-weight: 900; color: #fff; background: #4a7c2a; }
    .ac-watch { margin-top: .55rem; padding: .5rem .65rem; border-radius: .8rem; background: #fff6dd; border: 1px solid #f3d98f; font-size: .82rem; color: #6b4a00; }
    .ac-added { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .ac-added span { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #2d5016; background: #e4efd4; }

    /* ---- today: the last card, the one to act on ---- */
    .ac-today { scroll-margin-top: calc(var(--app-head, 4rem) + .5rem); }
    .ac-today .ac-card { max-width: none; border-color: #b7d597; box-shadow: 0 24px 48px -32px rgb(45 80 22 / .7); }
    .ac-today-h { position: relative; overflow: hidden; display: flex; align-items: center; gap: .75rem; padding: .85rem .9rem; color: #fff;
        background: linear-gradient(135deg, #4a7c2a 0%, #2d5016 70%); }
    .ac-today-h::after { content: ''; position: absolute; right: -2.5rem; top: -2.5rem; width: 8rem; height: 8rem; border-radius: 999px; background: rgb(245 197 24 / .18); }
    .ac-today-h > div { position: relative; flex: 1; min-width: 0; }
    .ac-today-h small { display: block; font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #d9ecc4; }
    .ac-today-h b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; }
    .ac-today-h .ac-sky { margin-top: .35rem; background: rgb(255 255 255 / .16); }
    .ac-today-h .ac-ring { position: relative; z-index: 1; }
    .ac-tasks { display: grid; grid-template-columns: minmax(0, 1fr); gap: .5rem; }
    .ac-task { position: relative; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); overflow: hidden; animation: acIn .36s var(--ac-ease) both; animation-delay: calc(var(--i) * 55ms);
        transition: border-color .28s var(--ac-ease), box-shadow .28s var(--ac-ease), opacity .4s var(--ac-ease), transform .4s var(--ac-ease); }
    .ac-task::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--tone-line, #9bc56f); }
    .ac-task.is-open { border-color: #b7d597; box-shadow: 0 14px 30px -24px rgb(15 23 42 / .5); }
    .ac-task.is-leaving { opacity: 0; transform: translateX(30px) scale(.97); }
    .ac-task[data-tone="gold"] { --tone-bg: #fdf3d3; --tone-line: #e2b33c; } .ac-task[data-tone="rose"] { --tone-bg: #fde9ec; --tone-line: #e0788a; }
    .ac-task[data-tone="sky"] { --tone-bg: #e5f2fc; --tone-line: #6aa9da; } .ac-task[data-tone="amber"] { --tone-bg: #fff0dc; --tone-line: #e3a14c; }
    .ac-task.is-late::before { background: #d9534f; }
    .ac-tt { display: flex; gap: .6rem; align-items: center; width: 100%; padding: .7rem .75rem .7rem .9rem; text-align: left; }
    .ac-tm { flex: 1; min-width: 0; }
    .ac-tm b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .ac-tm small { display: flex; flex-wrap: wrap; gap: .25rem .45rem; margin-top: .2rem; font-size: .72rem; color: var(--color-gray-500); }
    .ac-chev { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); transition: transform .28s var(--ac-ease); }
    .ac-task.is-open .ac-chev { transform: rotate(180deg); }
    .ac-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .34s var(--ac-ease); }
    .ac-task.is-open .ac-fold { grid-template-rows: 1fr; }
    .ac-fold > div { overflow: hidden; }
    .ac-fin { padding: 0 .85rem .85rem .95rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .ac-fin h5 { margin: .65rem 0 .2rem; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-fin ol { margin-left: 1.1rem; list-style: decimal; } .ac-fin ul { margin-left: 1rem; list-style: disc; }
    .ac-why { padding: .55rem .7rem; border-radius: .8rem; background: #f3f8ec; border: 1px solid #d6e6c2; color: #2d4a17; }
    .ac-prod { padding: .5rem .65rem; border-radius: .8rem; border: 1px solid var(--color-gray-200); margin-top: .35rem; }
    .ac-prod b { color: var(--color-gray-900); } .ac-prod em { font-style: normal; font-size: .7rem; font-weight: 800; color: #6b4e00; background: #fdf3d3; padding: .05rem .4rem; border-radius: 999px; margin-left: .3rem; }
    /* The shops by their own logos (2026-10-15, the owner): a white tile, the
       logo centred, the shop named for screen readers and on hover. */
    .shop-logo { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 2.25rem; height: 2.25rem; border-radius: .7rem;
        background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 4px 10px -8px rgb(17 24 39 / .45); text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .shop-logo img { width: 1.35rem; height: 1.35rem; object-fit: contain; display: block; }
    .shop-logo:hover { transform: translateY(-1px); box-shadow: 0 8px 16px -10px rgb(17 24 39 / .5); }
    .shop-logo.is-sp:hover { border-color: #f6a58d; }
    .shop-logo.is-lz:hover { border-color: #f59ac8; }
    .shop-logo:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .shop-logo { transition: none; } }
    .ac-shop { display: flex; gap: .35rem; margin-top: .45rem; }
    html.dark .shop-logo { border-color: transparent; box-shadow: 0 0 0 1px rgb(255 255 255 / .08); }
    .ac-safe { padding: .55rem .7rem; border-radius: .8rem; background: #fff6dd; border: 1px solid #f3d98f; color: #6b4a00; }
    .ac-tbtns { display: flex; gap: .45rem; margin-top: .8rem; }
    .ac-tbtns .btn { flex: 1; }
    .ac-done-btn { background: #4a7c2a !important; color: #fff !important; }
    .ac-none { display: flex; align-items: center; gap: .6rem; padding: .7rem .8rem; border-radius: 1rem; background: #f3f8ec; font-size: .86rem; color: #2d4a17; }
    .ac-none span { font-size: 1.5rem; }
    .ac-soon { display: grid; gap: .45rem; margin-top: .35rem; }
    .ac-sday { display: grid; grid-template-columns: 3.1rem minmax(0, 1fr); gap: .55rem; align-items: start; }
    .ac-sday > span { padding: .35rem 0; border-radius: .8rem; text-align: center; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .ac-sday > span small { display: block; font-size: .6rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-sday > span b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.1; }
    .ac-sday > div { display: grid; gap: .25rem; }
    .ac-srow { min-width: 0; display: flex; gap: .5rem; align-items: center; padding: .4rem .55rem; border-radius: .75rem; text-align: left; width: 100%; background: var(--color-gray-50);
        transition: background-color .28s var(--ac-ease); }
    .ac-srow:hover { background: #eef6e4; }
    .ac-srow b { flex: 1; min-width: 0; font-size: .82rem; font-weight: 700; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-srow em { flex: none; font-style: normal; font-size: .68rem; color: var(--color-gray-400); max-width: 40%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-ready { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .ac-ready span { display: inline-flex; align-items: center; gap: .3rem; font-size: .74rem; font-weight: 700; padding: .25rem .6rem; border-radius: 999px; background: #fff0dc; color: #7a4a00; }
    .ac-typing { display: inline-flex; gap: .25rem; padding: .2rem 0; }
    .ac-typing i { width: .45rem; height: .45rem; border-radius: 999px; background: #8fbf5f; animation: acDot 1.1s ease-in-out infinite; }
    .ac-typing i:nth-child(2) { animation-delay: .15s; } .ac-typing i:nth-child(3) { animation-delay: .3s; }
    @keyframes acDot { 50% { transform: translateY(-4px); opacity: .5; } }

    /* Back to today, when the story is scrolled up. */
    .ac-jump { position: fixed; z-index: 25; right: 1rem; bottom: calc(9.4rem + env(safe-area-inset-bottom)); display: inline-flex; align-items: center; gap: .35rem;
        padding: .45rem .9rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: #1f2937; background: #f5c518; box-shadow: 0 12px 26px -14px rgb(0 0 0 / .6);
        opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .28s var(--ac-ease), transform .28s var(--ac-ease); }
    .ac-jump.is-on { opacity: 1; transform: none; pointer-events: auto; }

    /* ---- the composer ---- */
    .ac-comp { position: sticky; bottom: 0; z-index: 20; margin: 0 -1rem; padding: .55rem 1rem calc(.6rem + env(safe-area-inset-bottom)); background: rgb(249 250 251 / .95);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-top: 1px solid var(--color-gray-200); }
    .ac-quick { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; margin-bottom: .45rem; }
    .ac-quick::-webkit-scrollbar { display: none; }
    .ac-quick[hidden] { display: none; }
    .ac-q { flex: none; padding: .32rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: #2d5016; background: #eef6e4; border: 1px solid #d6e6c2;
        transition: background-color .28s var(--ac-ease), transform .2s var(--ac-ease); }
    .ac-q:hover { background: #e2efd2; } .ac-q:active { transform: scale(.96); }
    .ac-modes { display: flex; gap: .35rem; margin-bottom: .45rem; }
    .ac-mode { padding: .3rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); border: 1px solid var(--color-gray-200); transition: background-color .28s var(--ac-ease), color .28s var(--ac-ease); }
    .ac-mode[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .ac-crow { display: flex; gap: .4rem; align-items: flex-end; }
    .ac-crow textarea { flex: 1; min-height: 2.75rem; max-height: 8rem; resize: none; }
    .ac-ib { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); transition: background-color .28s var(--ac-ease); }
    .ac-ib.is-rec { background: #fde8e8; border-color: #f5c2c2; animation: acRec 1s ease-in-out infinite; }
    @keyframes acRec { 50% { box-shadow: 0 0 0 6px rgb(220 38 38 / .15); } }
    .ac-send { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--color-brand-600); }
    .ac-send:disabled { opacity: .5; }
    .ac-att { display: flex; gap: .35rem; flex-wrap: wrap; margin-bottom: .4rem; }
    .ac-att:empty { display: none; }
    .ac-att span { position: relative; display: inline-flex; align-items: center; gap: .3rem; padding: .25rem; border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .74rem; font-weight: 700; animation: acIn .25s var(--ac-ease) both; }
    .ac-att img { width: 3rem; height: 3rem; border-radius: .5rem; object-fit: cover; }
    .ac-att button { width: 1.3rem; height: 1.3rem; border-radius: 999px; background: var(--color-gray-100); font-size: .7rem; }
    .ac-lotpick { font-size: .74rem; padding: .3rem .55rem; border-radius: 999px; border: 1px solid var(--color-gray-200); background: var(--color-white); }
    @media (max-width: 639.98px) {
        .ac-row:not(.is-me) > .ac-av { display: none; }
        .ac-card, .ac-row.is-me .ac-card, .ac-bub { max-width: 100%; }
        .ac-row.is-me { padding-left: 1.6rem; }
        .ac-ib, .ac-send { width: 2.45rem; height: 2.45rem; font-size: 1rem; }
        .ac-crow { gap: .3rem; }
        .ac-crow textarea { font-size: .9rem; min-height: 2.45rem; padding-top: .55rem; padding-bottom: .55rem; }
        .ac-lots { grid-template-columns: minmax(0, 1fr); }
    }

    .ac-modal { position: fixed; inset: 0; z-index: 230; display: flex; align-items: flex-end; justify-content: center; padding: 0; background: rgb(12 20 8 / .55); opacity: 0; transition: opacity .28s var(--ac-ease); }
    @media (min-width: 640px) { .ac-modal { align-items: center; padding: 1rem; } }
    .ac-modal.is-on { opacity: 1; }
    .ac-modal[hidden] { display: none; }
    .ac-modal > div { width: 100%; max-width: 28rem; max-height: 92vh; overflow-y: auto; border-radius: 1.4rem 1.4rem 0 0; background: var(--color-white); padding: 1.1rem 1.15rem calc(1.1rem + env(safe-area-inset-bottom)); transform: translateY(30px); transition: transform .34s var(--ac-ease); }
    @media (min-width: 640px) { .ac-modal > div { border-radius: 1.3rem; } }
    .ac-modal.is-on > div { transform: none; }
    .ac-modal h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .ac-modal p.ac-msub { margin: .3rem 0 .8rem; font-size: .84rem; color: var(--color-gray-500); }
    .ac-reasons { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    .ac-reason { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .65rem .3rem; border-radius: .95rem; font-size: .72rem; font-weight: 800; text-align: center; color: var(--color-gray-700);
        border: 1px solid var(--color-gray-200); background: var(--color-white); transition: background-color .28s var(--ac-ease), border-color .28s var(--ac-ease), transform .2s var(--ac-ease); }
    .ac-reason span { font-size: 1.4rem; }
    .ac-reason:active { transform: scale(.96); }
    .ac-reason[aria-pressed="true"] { background: #f3f8ec; border-color: var(--color-brand-600); color: #2d5016; }
    .ac-lab { display: block; margin: .8rem 0 .3rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .ac-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .ac-pill { padding: .35rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100); transition: background-color .28s var(--ac-ease), color .28s var(--ac-ease); }
    .ac-pill[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); }
    .ac-macts { display: flex; gap: .5rem; margin-top: 1rem; } .ac-macts .btn { flex: 1; }
    .ac-log-day { margin-top: 1rem; } .ac-log-day h4 { font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-log-row { display: flex; gap: .6rem; padding: .45rem 0; border-bottom: 1px dashed var(--color-gray-200); font-size: .82rem; color: var(--color-gray-700); }
    .ac-log-row i { flex: none; width: 3.6rem; font-style: normal; font-size: .7rem; color: var(--color-gray-400); }
    .ac-log-row b { color: var(--color-gray-900); }

    html.dark .ac-bub, html.dark .ac-card, html.dark .ac-task, html.dark .ac-more, html.dark .ac-mode:not([aria-pressed="true"]), html.dark .ac-ib, html.dark .ac-modal > div, html.dark .ac-reason,
    html.dark .ac-att span, html.dark .ac-lotpick { background: #161c12; border-color: #2b3a1c; color: #dbe5d0; }
    html.dark .ac-row.is-me .ac-bub, html.dark .ac-row.is-me .ac-card { background: #1d2817; border-color: #3f5a2a; color: #e8efe1; }
    html.dark .ac-card { --tone-bg: #22301a; --tone-ink: #c5e09f; }
    html.dark .ac-card[data-tone="gold"] { --tone-bg: #2f2810; --tone-ink: #f0d27a; } html.dark .ac-card[data-tone="rose"] { --tone-bg: #321a1f; --tone-ink: #f3a7b4; }
    html.dark .ac-card[data-tone="sky"] { --tone-bg: #14273a; --tone-ink: #9ccbf0; } html.dark .ac-card[data-tone="amber"] { --tone-bg: #31230f; --tone-ink: #f2c27c; }
    html.dark .ac-card-b, html.dark .ac-fin { color: #c9d4bf; }
    html.dark .ac-tm b, html.dark .ac-card-t, html.dark .ac-srow b, html.dark .ac-modal h3, html.dark .ac-prod b, html.dark .ac-log-row b, html.dark .ac-crow-t b, html.dark .ac-mlot b,
    html.dark .ac-why-big b, html.dark .ac-hop b, html.dark .ac-sday > span b { color: #eef3e8; }
    html.dark .ac-crow-t, html.dark .ac-srow, html.dark .ac-sday > span, html.dark .ac-hop div, html.dark .ac-note, html.dark .ac-chip { background: #1a2114; border-color: #2b3a1c; color: #c9d4bf; }
    html.dark .ac-hop div.is-to { background: #14273a; border-color: #24435f; }
    html.dark .ac-why, html.dark .ac-none { background: #17220f; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .ac-safe, html.dark .ac-watch { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    html.dark .ac-comp { background: rgb(16 20 13 / .95); border-color: #2b3a1c; }
    html.dark .ac-reason[aria-pressed="true"] { background: #22301a; color: #c5e09f; }
    html.dark .ac-task[data-tone] { --tone-bg: #22301a; }
    html.dark .ac-q { background: #1d2817; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .ac-sys { background: #1a2114; color: #9fb08f; }
    html.dark .ac-day::before, html.dark .ac-day::after { border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) {
        .ac-head::after, .ac-lot, .ac-row, .ac-sys, .ac-task, .ac-typing i, .ac-ib.is-rec, .ac-att span, .ac-tick, .ac-crow-t > i, .ac-sun svg, .ac-sun svg g { animation: none !important; }
        .ac-fold, .ac-chev, .ac-task, .ac-modal, .ac-modal > div, .ac-bar i, .ac-prog > span i, .ac-ring .fg, .ac-jump { transition: none !important; }
    }
    html.sm-still .ac-head::after, html.sm-still .ac-sun svg g { animation: none !important; }
</style>

<div class="ac">
    <div class="ac-head">
        <div class="ac-head-row">
            <span class="ac-ring" id="acRing" aria-hidden="true"><svg viewBox="0 0 40 40"><circle class="bg" cx="20" cy="20" r="16"/><circle class="fg" cx="20" cy="20" r="16" stroke-dasharray="100.5" stroke-dashoffset="100.5"/></svg><b>0%</b></span>
            <div class="min-w-0"><h2 id="acTitle">{{ $schedule->title }}</h2><p id="acSay" data-tl="anee-auto">Anee plans, you follow.</p></div>
        </div>
        <div class="ac-lots" id="acLots"></div>
        <div class="ac-week" id="acWeek"></div>
        <div class="ac-acts">
            <button type="button" class="ac-hbtn" id="acTodayBtn">📋 Today</button>
            <button type="button" class="ac-hbtn" id="acLogBtn">📖 Season log</button>
            <button type="button" class="ac-hbtn" id="acSafeBtn">🛡️ Safety</button>
            <a class="ac-hbtn" href="{{ route('sm.hub', ['id' => $schedule->id]) }}">🗂️ The season</a>
        </div>
    </div>

    <div class="ac-feed" id="acFeed" aria-live="polite"><p class="ac-sys">Loading your season…</p></div>
    <button type="button" class="ac-jump" id="acJump" aria-label="Back to today's plan">📋 Today ↓</button>

    <div class="ac-comp">
        {{-- Questions farmers ask most, one tap to put in the box (sent only when they press send). --}}
        <div class="ac-quick" id="acQuick">
            <button type="button" class="ac-q" data-q="What is coming up this week, and what should I prepare?">🗓️ This week</button>
            <button type="button" class="ac-q" data-q="Is it a good day to spray tomorrow?">🌧️ Spray tomorrow?</button>
            <button type="button" class="ac-q" data-q="How is my crop doing for its stage?">🌾 How is my crop?</button>
            <button type="button" class="ac-q" data-q="When is the next fertilizer, and how much?">🧪 Next fertilizer</button>
            <button type="button" class="ac-q" data-q="What pests and diseases should I watch for now?">🐛 What to watch</button>
        </div>
        <div class="ac-modes" role="tablist">
            <button type="button" class="ac-mode" data-mode="ask" aria-pressed="true">💬 Ask Anee</button>
            <button type="button" class="ac-mode" data-mode="observe" aria-pressed="false">👀 Show Anee the field</button>
            <select id="acLotPick" class="ac-lotpick ml-auto" aria-label="Which lot" hidden></select>
        </div>
        <div class="ac-att" id="acAtt"></div>
        <div class="ac-crow">
            <button type="button" class="ac-ib" id="acPhoto" title="Take or add a photo" aria-label="Photo">📷</button>
            <button type="button" class="ac-ib" id="acVideo" title="Record or add a short video" aria-label="Video">🎥</button>
            <button type="button" class="ac-ib" id="acVoice" title="Record a voice note" aria-label="Voice">🎙️</button>
            <textarea id="acText" class="form-textarea" rows="1" maxlength="2000" placeholder="Ask Anee about your season"></textarea>
            <button type="button" class="ac-send" id="acSend" aria-label="Send"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg></button>
        </div>
        <input type="file" id="acPhotoIn" accept="image/*" capture="environment" multiple hidden>
        <input type="file" id="acVideoIn" accept="video/*" capture="environment" hidden>
    </div>
</div>

<div class="ac-modal" id="acModal" hidden><div id="acModalBox"></div></div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const SHOP_LOGO = { sp: @json(asset('images/shops/shopee.webp')), lz: @json(asset('images/shops/lazada.webp')) };
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const U = {
        feed: @json(route('auto.feed', $auto->id)), log: @json(route('auto.log', $auto->id)), observe: @json(route('auto.observe', $auto->id)), ask: @json(route('auto.ask', $auto->id)),
        done: (t) => @json(url('/app/auto/' . $auto->id . '/task')) + '/' + t + '/done', cant: (t) => @json(url('/app/auto/' . $auto->id . '/task')) + '/' + t + '/cant',
    };
    const FACE = @json(\App\Support\AneeEmoji::url('happy'));
    const SHOP_NOTE = 'These links only show who may be selling it. Anee does not recommend a shop or a brand: read the label and buy only FPA registered products.';
    let F = null, EV = [], open = new Set();

    /* ---- Tagalog help flags (2026-10-15): written straight after a label's
       last word, on the app's own labels only, never inside Anee's words. ---- */
    const tl = (k) => (k && window.aneeTl ? window.aneeTl.flag(k) : '');
    let tlSeen = new Set();     // one flag per term per drawing of today's plan
    const tlOnce = (k) => { if (!k || tlSeen.has(k)) return ''; tlSeen.add(k); return tl(k); };
    // "DAT 21", "12 days to DAT 0": the day count's flag right after its number.
    const dayHtml = (s) => esc(s).replace(/\b(DAT|DAS|DAP|DOS) (-?\d+)/, (m, c) => m + tl(c.toLowerCase()));
    // A stage's own entry where the glossary has one, the general one otherwise.
    const STAGE_TL = [[/panicle init/, 'panicle-initiation'], [/active tiller/, 'active-tillering'], [/max(imum)? tiller/, 'max-tillering'], [/tiller/, 'tillering'],
        [/booting/, 'booting'], [/heading/, 'heading'], [/flower|anthesis/, 'flowering'], [/grain fill/, 'grain-filling'], [/milk/, 'milking'], [/dough/, 'dough'],
        [/ripen/, 'ripening-stage'], [/matur/, 'maturity'], [/seedling/, 'seedling-stage'], [/vegetative/, 'vegetative-stage'], [/reproductive/, 'reproductive-stage'], [/harvest/, 'harvest']];
    const stageTl = (s) => {
        const x = String(s || '').toLowerCase();
        const hit = STAGE_TL.find(([re]) => re.test(x));
        return tl(hit && window.aneeTl && window.aneeTl.has(hit[1]) ? hit[1] : 'growth-stage');
    };
    const groupTl = (g) => { const m = String(g || '').match(/\b(IRAC|FRAC|HRAC)\b/i); return m ? tl(m[1].toLowerCase()) : ''; };

    const chev = '<svg class="ac-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-5-5m5 5l-5 5"/></svg>';
    const sunArt = '<svg viewBox="0 0 64 64" aria-hidden="true"><g><path d="M32 4v8M32 52v8M4 32h8M52 32h8M12 12l6 6M46 46l6 6M52 12l-6 6M18 46l-6 6" stroke="#fff3cf" stroke-width="3" stroke-linecap="round"/></g><circle cx="32" cy="32" r="11" fill="#fff3cf"/></svg>';
    const avatar = '<span class="ac-av"><img src="' + FACE + '" alt=""></span>';
    const skyLine = (s) => s ? '<span class="ac-sky">' + esc(s.emoji || '⛅') + ' ' + esc(s.pop) + '% rain' + (s.mm ? ' · ' + esc(s.mm) + ' mm' : '') + (s.wind ? ' · 💨 ' + esc(s.wind) + ' km/h' : '') + (s.tmax ? ' · 🌡️ ' + esc(s.tmax) + '°' : '') + '</span>' : '';

    const taskCard = (t, i) => {
        const flags = (t.critical ? '<span class="ac-chip is-crit">Cannot wait</span>' : '') + (t.late ? '<span class="ac-chip is-late">Late</span>' : '')
            + (t.note && t.note.risk ? '<span class="ac-chip is-risk">' + ({ weather: 'Weather risk', workers: 'Short of hands', both: 'Weather and hands' })[t.note.risk] + '</span>' : '');
        const sky = t.note && t.note.sky ? t.note.sky.emoji + ' ' + t.note.sky.pop + '%' : null;
        const meta = [t.lot, t.time ? (t.time === 'morning' ? 'Morning' : 'Afternoon') : null, t.workers && t.workers.length ? '👥 ' + t.workers.join(', ') : null, sky].filter(Boolean);
        // The sky's rain chance is last when it is there; the first card of the plan carries its Tagalog flag.
        const metaHtml = meta.map(esc);
        if (sky) metaHtml[metaHtml.length - 1] += tlOnce('rain-chance');
        const d = t.detail || {};
        let rateTl = tl('label-rate');      // on the first product's rate in this task only
        const prods = (d.products || []).map((p, k) => {
            const shop = (t.shop || [])[k];
            const rateFlag = p.rate ? rateTl : '';
            if (p.rate) rateTl = '';
            return '<div class="ac-prod"><b>' + esc(p.name) + '</b>' + (p.group ? '<em>' + esc(p.group) + groupTl(p.group) + '</em>' : '') + (p.rate ? '<div class="text-xs mt-1">Rate' + rateFlag + ': ' + esc(p.rate) + '</div>' : '') + (p.amount ? '<div class="text-xs">For this lot: ' + esc(p.amount) + '</div>' : '')
                + (shop ? '<div class="ac-shop">'
                    + '<a class="shop-logo is-sp" href="' + esc(shop.shopee) + '" target="_blank" rel="noopener nofollow" title="Search Shopee for ' + esc(p.name) + '" aria-label="Search Shopee for ' + esc(p.name) + '"><img src="' + SHOP_LOGO.sp + '" alt="" width="48" height="48"></a>'
                    + '<a class="shop-logo is-lz" href="' + esc(shop.lazada) + '" target="_blank" rel="noopener nofollow" title="Search Lazada for ' + esc(p.name) + '" aria-label="Search Lazada for ' + esc(p.name) + '"><img src="' + SHOP_LOGO.lz + '" alt="" width="48" height="48"></a></div>' : '') + '</div>';
        }).join('');
        return '<div class="ac-task' + (t.late ? ' is-late' : '') + (open.has(t.id) ? ' is-open' : '') + '" data-task="' + t.id + '" data-tone="' + esc(t.tone) + '" style="--i:' + i + '">'
            // The words in a span of their own: the small is a flex row, and a loose flag would stand apart as an item.
            + '<button type="button" class="ac-tt" data-toggle><span class="ac-te">' + t.emoji + '</span><span class="ac-tm"><b>' + esc(t.title) + '</b><small>' + (metaHtml.length ? '<span>' + metaHtml.join(' · ') + '</span>' : '') + flags + '</small></span>' + chev + '</button>'
            + '<div class="ac-fold"><div><div class="ac-fin">'
            + (t.summary ? '<p>' + esc(t.summary) + '</p>' : '')
            + (t.why ? '<h5>Why</h5><div class="ac-why">' + esc(t.why) + '</div>' : '')
            + ((d.steps || []).length ? '<h5>How</h5><ol>' + d.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol>' : '')
            + (prods ? '<h5>What to use</h5>' + prods + ((t.shop || []).length ? '<p class="text-xs text-gray-400 mt-1">' + esc(SHOP_NOTE).replace('FPA registered', 'FPA registered' + tl('fpa-registration')) + '</p>' : '') : '')
            + ((d.safety || []).length ? '<h5>Safety</h5><div class="ac-safe"><ul>' + d.safety.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul></div>' : '')
            + ((d.bring || []).length ? '<h5>Have ready</h5><ul>' + d.bring.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul>' : '')
            + (t.moved ? '<p class="text-xs text-gray-400 mt-2">Moved ' + t.moved + ' time' + (t.moved > 1 ? 's' : '') + ' so far.</p>' : '')
            + (t.status === 'planned' ? '<div class="ac-tbtns"><button type="button" class="btn ac-done-btn" data-done>✅ Done</button><button type="button" class="btn btn-white" data-cant>🙅 Can\'t today</button></div>' : '')
            + '</div></div></div></div>';
    };

    /* ---- today's plan: the last card, the one to act on ---- */
    const ring = (pct) => '<span class="ac-ring" aria-hidden="true"><svg viewBox="0 0 40 40"><circle class="bg" cx="20" cy="20" r="16"/><circle class="fg" cx="20" cy="20" r="16" stroke-dasharray="100.5" stroke-dashoffset="' + (100.5 * (1 - pct / 100)).toFixed(1) + '"/></svg><b>' + Math.round(pct) + '%</b></span>';
    const todayBlock = () => {
        if (!F) return '';
        tlSeen = new Set();
        if (F.status !== 'active') return '<div class="ac-row ac-today" id="acToday">' + avatar + '<div class="ac-card" data-tone="gold"><div class="ac-card-h">🎉 This season is finished</div><div class="ac-card-b">The log keeps every task, observation and change for the post harvest report.</div></div></div>';
        const done = (F.doneToday || []).length, all = F.today.length + done;
        const sky = F.week && F.week[0] ? { emoji: F.week[0].emoji, pop: F.week[0].pop, tmax: F.week[0].tmax } : null;
        // The coming days, each day's tasks beside its date.
        const byDay = {};
        F.soon.forEach((t) => { (byDay[t.date] = byDay[t.date] || []).push(t); });
        const soon = Object.keys(byDay).sort().map((d) => {
            const dt = new Date(d + 'T00:00:00');
            return '<div class="ac-sday"><span><small>' + dt.toLocaleDateString(undefined, { weekday: 'short' }) + '</small><b>' + dt.getDate() + '</b></span><div>'
                + byDay[d].map((t) => '<button type="button" class="ac-srow" data-peek="' + t.id + '"><span>' + t.emoji + '</span><b>' + esc(t.title) + '</b><em>' + esc(t.lot || '') + '</em></button>').join('') + '</div></div>';
        }).join('');
        return '<div class="ac-row ac-today" id="acToday">' + avatar + '<div class="ac-card">'
            + '<div class="ac-today-h"><div><small>Today\'s plan</small><b>' + new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }) + '</b>' + skyLine(sky) + '</div>' + (all ? ring(done / all * 100) : '') + '</div>'
            + '<div class="ac-card-b">'
            + (F.today.length ? '<div class="ac-tasks">' + F.today.map(taskCard).join('') + '</div>'
                : '<div class="ac-none"><span>' + (done ? '🎉' : '🌤️') + '</span><span>' + (done ? 'All done for today. Salamat! Rest the back, and tell me if you see anything in the field.' : 'Nothing to do today. Walk the field, and tell me if you see anything.') + '</span></div>')
            + ((F.doneToday || []).length ? '<p class="ac-sub">Done today</p><div class="ac-check">' + F.doneToday.map((t) => '<div class="ac-crow-t is-done"><i>✓</i><span>' + t.emoji + '</span><b>' + esc(t.title) + '</b><small>' + esc(t.lot || '') + '</small></div>').join('') + '</div>' : '')
            + (soon ? '<p class="ac-sub">Coming up</p><div class="ac-soon">' + soon + '</div>' : '')
            + (F.ready.length ? '<p class="ac-sub">🛒 Get these ready</p><div class="ac-ready">' + F.ready.map((r) => '<span>' + esc(r) + '</span>').join('') + '</div>' : '')
            + '</div></div></div>';
    };

    /* ---- the story: each day's thing as a card ---- */
    const card = (tone, head, right, body, time) => '<div class="ac-card" data-tone="' + tone + '"><div class="ac-card-h"><span>' + head + '</span>' + (right ? '<span class="ac-card-r">' + right + '</span>' : '') + '</div><div class="ac-card-b">' + body + '</div>' + (time ? '<span class="ac-time">' + esc(time) + '</span>' : '') + '</div>';
    const anee = (inner, e) => '<div class="ac-row" data-ev="' + e.id + '">' + avatar + inner + '</div>';
    const mine = (inner, e) => '<div class="ac-row is-me" data-ev="' + e.id + '">' + inner + '</div>';
    const mediaHtml = (list) => list && list.length ? '<div class="ac-media' + (list.length === 1 ? ' is-one' : '') + '">' + list.map((m) => m.type === 'video' ? '<video src="' + esc(m.url) + '" controls playsinline preload="metadata"></video>'
        : m.type === 'audio' ? '<audio src="' + esc(m.url) + '" controls preload="none"></audio>' : '<img src="' + esc(m.url) + '" alt="" loading="lazy">').join('') + '</div>' : '';
    const chips = (list) => list.filter(Boolean).length ? '<div class="ac-chips">' + list.filter(Boolean).join('') + '</div>' : '';
    const chip = (s, cls) => s ? '<span class="ac-chip' + (cls ? ' ' + cls : '') + '">' + s + '</span>' : '';
    const moveRows = (list) => (list || []).length ? '<div class="ac-moves">' + list.map((m) => '<div class="ac-mv"><span>↪️</span><b>' + esc(m.title) + '</b><em>' + esc(m.to || '') + '</em></div>').join('') + '</div>' : '';
    const sevWord = { high: ['Serious', 3, 'rose'], medium: ['Moderate', 2, 'amber'], low: ['Light', 1, 'leaf'], none: ['Nothing wrong', 0, 'leaf'] };
    const confWord = { high: 'Sure', medium: 'Fairly sure', low: 'Not sure yet' };

    const evRow = (e) => {
        const c = e.card || {}, d = e.data || {};
        if (e.kind === 'email') return '<p class="ac-sys">✉️ The morning plan went out to ' + esc(d.sent) + ' ' + (d.sent === 1 ? 'person' : 'people') + ' · ' + esc(e.time) + '</p>';
        switch (c.type) {
            case 'morning': {
                const tasks = c.tasks || [], done = tasks.filter((t) => t.done).length;
                const lots = (c.lots || []).map((l) => '<div class="ac-mlot"><span>' + esc(l.icon) + '</span><b>' + esc(l.name) + '</b>' + (l.day !== null && l.day !== undefined ? '<em>' + (l.day >= 0 ? esc(l.counter) + ' ' + l.day : 'in ' + (-l.day) + ' days') + '</em>' : '') + (l.stage ? '<span class="text-xs text-gray-500">' + esc(l.stage) + '</span>' : '') + '</div>').join('');
                // A morning kept before its card had the sky and the lots says them in its own words.
                const words = !c.sky && !c.lots ? '<div class="ac-say">' + e.html.split('<br />').filter((x) => !/^\s*Today: /.test(x)).join('<br />') + '</div>' : '';
                return anee('<div class="ac-card ac-morning' + (c.isToday ? '' : ' is-past') + '"><div class="ac-sun">' + sunArt + '<small>' + (c.isToday ? 'Good morning · today' : 'Good morning') + '</small><b>' + esc(c.date) + '</b>' + skyLine(c.sky) + '</div>'
                    + '<div class="ac-card-b">' + (lots ? '<div class="ac-mlots">' + lots + '</div>' : '') + words
                    // This morning's tasks are on today's plan just below: here, only how far the day has come.
                    + (c.isToday && tasks.length ? '<div class="ac-prog"><span><i style="width:' + (done / tasks.length * 100) + '%"></i></span>' + done + ' of ' + tasks.length + ' done</div>'
                        + '<button type="button" class="btn btn-white w-full mt-2" data-today>📋 Open today\'s plan</button>'
                    : tasks.length ? '<p class="ac-sub">' + tasks.length + ' task' + (tasks.length > 1 ? 's' : '') + (c.late ? ', ' + c.late + ' late' : '') + '</p><div class="ac-check">'
                        + tasks.map((t) => '<button type="button" class="ac-crow-t' + (t.done ? ' is-done' : '') + (t.critical ? ' is-crit' : '') + '" data-goto="' + t.id + '"><i>✓</i><span>' + t.emoji + '</span><b>' + esc(t.title) + '</b><small>' + esc(t.done ? 'Done' + (t.doneDate && t.doneDate !== 'Today' ? ' ' + t.doneDate : '') : (t.lot || '')) + '</small></button>').join('') + '</div>'
                        + '<div class="ac-prog"><span><i style="width:' + (done / tasks.length * 100) + '%"></i></span>' + done + ' of ' + tasks.length + ' done</div>'
                        : '<div class="ac-none mt-2"><span>🌤️</span><span>Nothing planned that day. A good day to walk the field.</span></div>')
                    + ((c.moved || []).length ? '<p class="ac-sub">Moved for the weather or the workers</p>' + moveRows(c.moved) : (c.movedCount ? '<p class="ac-sub">' + c.movedCount + ' task' + (c.movedCount > 1 ? 's' : '') + ' moved</p>' : ''))
                    + '</div><span class="ac-time">' + esc(e.time) + '</span></div>', e);
            }
            case 'done': {
                const t = c.task || { title: d.task || 'Task', emoji: '✅' };
                return mine(card('leaf', '✅ Done', esc(t.doneDate || ''),
                    '<div class="ac-card-t"><span class="ac-tick">' + tick + '</span><span>' + esc(t.title) + '</span></div>'
                    + chips([chip(t.emoji + ' ' + esc(t.kind || '')), chip(esc(t.lot || '')), (c.workers || []).length ? chip('👥 ' + esc(c.workers.join(', '))) : '', t.critical ? chip('Cannot wait', 'is-crit') : ''])
                    + (c.used ? '<div class="ac-note">🧪 Used: ' + esc(c.used) + '</div>' : '') + (c.note ? '<div class="ac-note">📝 ' + esc(c.note) + '</div>' : '') + mediaHtml(e.media), e.time), e);
            }
            case 'cant': {
                const t = c.task || { title: '' };
                return mine(card('rose', '🙅 Can\'t today', esc(c.from || ''),
                    '<div class="ac-why-big"><span>' + esc(c.reason ? c.reason.emoji : '✍️') + '</span><div><b>' + esc(t.title) + '</b><small>' + esc(c.reason ? c.reason.label : '') + '</small></div></div>'
                    + (t.lot ? chips([chip(esc(t.lot))]) : ''), e.time), e);
            }
            case 'moved': {
                const t = c.task || { title: '' };
                return anee(card('sky', '📅 Moved', '',
                    '<div class="ac-card-t"><span class="ac-te">' + (t.emoji || '📅') + '</span><span>' + esc(t.title) + (t.lot ? '<small class="block text-xs font-bold text-gray-500 mt-0.5">' + esc(t.lot) + '</small>' : '') + '</span></div>'
                    + '<div class="ac-hop"><div><small>Was</small><b>' + esc(c.from || 'Before') + '</b></div>' + arrow + '<div class="is-to"><small>Now</small><b>' + esc(c.to || 'Later') + '</b></div></div>'
                    + ((c.others || []).length ? '<p class="ac-sub">I also moved</p>' + moveRows(c.others) : '')
                    + (t.critical ? '<div class="ac-watch">This one matters for the yield, so I kept it close.</div>' : ''), e.time), e);
            }
            case 'seen':
                return mine(card('leaf', '📷 From the field', esc(c.lot ? c.lot.name : 'The whole farm'),
                    (e.html ? '<div>' + e.html + '</div>' : '') + mediaHtml(e.media), e.time), e);
            case 'diagnosis': {
                const s = sevWord[d.severity] || sevWord.low;
                const words = e.html.replace(/<br \/>\s*I added to your calendar:[^<]*/, '');
                return anee(card(s[2], '🔎 What Anee sees', esc(c.lot ? c.lot.name : ''),
                    '<div class="ac-card-t">' + esc(d.what || 'From your picture') + '</div>'
                    + chips([chip('<span class="ac-sev">' + esc(s[0]) + ' <span class="ac-dots">' + [1, 2, 3].map((k) => '<i class="' + (k <= s[1] ? 'on' : '') + '"></i>').join('') + '</span></span>'), d.confidence ? chip(esc(confWord[d.confidence] || d.confidence)) : ''])
                    + '<div class="ac-say">' + words + '</div>'
                    + (d.reading ? '<div class="ac-note">' + esc(d.reading) + '</div>' : '')
                    + ((d.actions || []).length ? '<p class="ac-sub">Do this</p><ul class="ac-do">' + d.actions.map((a) => '<li>' + esc(a) + '</li>').join('') + '</ul>' : '')
                    + (d.watch ? '<div class="ac-watch">👀 Watch for: ' + esc(d.watch) + '</div>' : '')
                    + ((d.added || []).length ? '<p class="ac-sub">Added to your calendar</p><div class="ac-added">' + d.added.map((a) => '<span>🗓️ ' + esc(a) + '</span>').join('') + '</div>' : ''), e.time), e);
            }
            case 'praise':
                return anee('<div class="ac-bub">' + e.html + ((c.moved || []).length ? moveRows(c.moved) : '') + '<span class="ac-time">' + esc(e.time) + '</span></div>', e);
            case 'lotplan':
                return anee(card('gold', '🗺️ The plan', esc(c.lot ? c.lot.name : ''), '<div class="ac-card-t"><span class="ac-te">' + esc(c.lot ? c.lot.icon : '🌱') + '</span><span>' + esc(c.lot ? c.lot.name : 'Your lot') + '</span></div><div class="ac-say">' + e.html + '</div>'
                    + (c.tasks ? chips([chip('🗓️ ' + c.tasks + ' tasks on the calendar', 'is-ok')]) : ''), e.time), e);
            case 'reading':
                return anee(card('leaf', '📖 Anee read your farm', '', '<div class="ac-card-t">' + e.html + '</div>', e.time), e);
            case 'setup':
                return anee(card('gold', '🎉 Anee Auto is yours', '', '<div class="ac-say">' + e.html + '</div>', e.time), e);
            case 'welcome':
                return anee(card('leaf', '✅ Your season is planned', '', '<div class="ac-say">' + e.html + '</div>', e.time), e);
            case 'finished':
                return anee(card('gold', '🎉 Season finished', '', '<div class="ac-say">' + e.html + '</div>', e.time), e);
        }
        // Plain talk: a question and its answer.
        const me = e.role === 'farmer';
        const bub = '<div class="ac-bub">' + e.html + mediaHtml(e.media) + '<span class="ac-time">' + esc(e.time) + '</span></div>';
        return me ? mine(bub, e) : anee(bub, e);
    };
    const paint = (keepScroll = false) => {
        if (!F) return;
        let lastDay = null;
        const rows = EV.map((e) => { const head = e.day !== lastDay ? '<p class="ac-day">' + esc(lastDay = e.day) + '</p>' : ''; return head + evRow(e); }).join('');
        $('acFeed').innerHTML = (F.hasMore ? '<button type="button" class="ac-more" id="acMore">Show earlier days</button>' : '') + rows + (lastDay !== 'Today' ? '<p class="ac-day">Today</p>' : '') + todayBlock();
        if (!keepScroll) requestAnimationFrame(() => { const t = $('acToday'); if (t) t.scrollIntoView({ block: 'start', behavior: still() ? 'auto' : 'smooth' }); });
        watchToday();
    };
    const paintHead = () => {
        $('acTitle').textContent = F.title;
        // Each lot's day count and stage carry their Tagalog flags: the lots are read one by one.
        const lotLine = (l) => {
            const bits = [];
            if (l.say) bits.push(dayHtml(l.say));
            if (l.stage) bits.push(esc(l.stage) + stageTl(l.stage));
            return bits.length ? bits.join(' · ') : esc(l.crop || '');
        };
        $('acLots').innerHTML = F.lots.map((l, i) => '<div class="ac-lot" style="--i:' + i + '"><span>' + l.icon + '</span><div><b>' + esc(l.name) + '</b><small>' + lotLine(l) + '</small>'
            + (l.pct !== null && l.pct !== undefined ? '<div class="ac-bar" title="' + l.pct + '% of the way to harvest"><i data-w="' + l.pct + '"></i></div><small>' + (l.harvest ? '🧺 Harvest ' + esc(l.harvest) + (l.toHarvest > 0 ? ', in ' + l.toHarvest + ' days' : '') : '') + '</small>' : (l.harvest ? '<small>🧺 ' + esc(l.harvest) + '</small>' : '')) + '</div></div>').join('');
        // The week's sky, with a dot for each task planned that day.
        const count = {};
        F.today.forEach(() => { count[0] = (count[0] || 0) + 1; });
        F.soon.forEach((t) => { const k = F.week.findIndex((w) => w.date === t.date); if (k >= 0) count[k] = (count[k] || 0) + 1; });
        $('acWeek').innerHTML = F.week.map((d, i) => '<div class="' + (i === 0 ? 'is-today' : '') + '" title="' + (count[i] || 0) + ' task' + ((count[i] || 0) === 1 ? '' : 's') + '">' + esc(d.dow) + '<i>' + d.emoji + '</i><em>' + d.pop + '%</em><s>' + '<u></u>'.repeat(Math.min(4, count[i] || 0)) + '</s></div>').join('');
        const all = F.doneCount + F.left, pct = all ? F.doneCount / all * 100 : 0;
        const r = $('acRing');
        r.querySelector('.fg').setAttribute('stroke-dashoffset', (100.5 * (1 - pct / 100)).toFixed(1));
        r.querySelector('b').textContent = Math.round(pct) + '%';
        r.title = F.doneCount + ' of ' + all + ' tasks done';
        $('acSay').textContent = F.doneCount + ' of ' + all + ' tasks done · ' + (F.today.length ? F.today.length + ' today' : 'nothing due today');
        requestAnimationFrame(() => document.querySelectorAll('.ac-bar i[data-w]').forEach((b) => { b.style.width = b.dataset.w + '%'; }));
        const pick = $('acLotPick');
        pick.innerHTML = '<option value="">The whole farm</option>' + F.lots.map((l) => '<option value="' + l.id + '">' + esc(l.name) + '</option>').join('');
    };
    const load = async (keepScroll = false) => {
        try {
            const r = await window.api(U.feed);
            F = r.data; EV = F.events;
            paintHead(); paint(keepScroll);
        } catch (err) { $('acFeed').innerHTML = '<p class="ac-sys">' + esc(err.message) + '</p>'; }
    };

    /* Back to today: shown while today's card is out of sight. */
    let io = null;
    const watchToday = () => {
        const t = $('acToday');
        if (!t || !('IntersectionObserver' in window)) return;
        io && io.disconnect();
        io = new IntersectionObserver((es) => es.forEach((x) => $('acJump').classList.toggle('is-on', !x.isIntersecting && x.boundingClientRect.top > 0)), { threshold: 0.05 });
        io.observe(t);
    };
    const goToday = (taskId) => {
        const t = $('acToday'); if (!t) return;
        t.scrollIntoView({ block: 'start', behavior: still() ? 'auto' : 'smooth' });
        if (taskId) { const c = t.querySelector('[data-task="' + taskId + '"]'); if (c && !c.classList.contains('is-open')) { c.classList.add('is-open'); open.add(Number(taskId)); } }
    };
    $('acJump').addEventListener('click', () => goToday());
    $('acTodayBtn').addEventListener('click', () => goToday());

    $('acFeed').addEventListener('click', async (e) => {
        if (e.target.id === 'acMore') {
            try { const r = await window.api(U.feed + '?before=' + (EV[0] ? EV[0].id : 0)); EV = r.data.events.concat(EV); F.hasMore = r.data.hasMore; paint(true); } catch (_) {}
            return;
        }
        if (e.target.closest('[data-today]')) return goToday();
        const go = e.target.closest('[data-goto]');
        if (go) {
            const id = Number(go.dataset.goto);
            if (F.today.some((x) => x.id === id)) return goToday(id);
            const t = F.soon.find((x) => x.id === id);
            if (t) return peekTask(t);
            return;
        }
        const card = e.target.closest('[data-task]');
        const peek = e.target.closest('[data-peek]');
        if (peek) { const t = F.soon.find((x) => String(x.id) === peek.dataset.peek); if (t) peekTask(t); return; }
        if (!card) return;
        const id = Number(card.dataset.task);
        const t = F.today.find((x) => x.id === id) || F.soon.find((x) => x.id === id);
        if (e.target.closest('[data-toggle]')) { card.classList.toggle('is-open'); card.classList.contains('is-open') ? open.add(id) : open.delete(id); return; }
        if (e.target.closest('[data-done]')) return doneModal(t, card);
        if (e.target.closest('[data-cant]')) return cantModal(t, card);
    });

    /* ---- modals ---- */
    const modal = (html, mount) => { const m = $('acModal'); $('acModalBox').innerHTML = html; m.hidden = false; void m.offsetWidth; m.classList.add('is-on'); $('acModalBox').querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', closeModal)); mount && mount($('acModalBox')); };
    const closeModal = () => { const m = $('acModal'); m.classList.remove('is-on'); setTimeout(() => { m.hidden = true; }, 300); };
    $('acModal').addEventListener('click', (e) => { if (e.target === $('acModal')) closeModal(); });
    const peekTask = (t) => {
        tlSeen = new Set();     // the peek is a block of its own: its Tagalog flags show again
        modal('<div class="ac-task is-open" data-tone="' + esc(t.tone) + '" style="border:0">' + taskCard(t, 0).replace(/^<div[^>]*>/, '').replace(/<div class="ac-tbtns">[\s\S]*?<\/div>/, '') + '<p class="text-xs text-gray-500 mt-2 px-1">Planned for ' + esc(t.when) + '. Anee moves it if the weather or the workers change.</p><div class="ac-macts"><button type="button" class="btn btn-white" data-close>Close</button></div>');
    };
    const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    const doneModal = (t, card) => modal('<h3>✅ ' + esc(t.title) + '</h3><p class="ac-msub">Anee keeps it in the season log. If it went in on another day, say which: the days after it count from there.</p>'
        + '<label class="ac-lab" for="acDDate">Done on</label><input type="date" id="acDDate" class="form-input" max="' + today() + '" value="' + today() + '">'
        + '<label class="ac-lab" for="acDUsed">What you used <small class="font-normal text-gray-400">(optional)</small></label><input type="text" id="acDUsed" class="form-input w-full" maxlength="400" placeholder="Like 2 bags of urea, or the product and how much">'
        + (F.people.length ? '<span class="ac-lab">Who did it</span><div class="ac-pills" id="acDWho">' + F.people.map((p) => '<button type="button" class="ac-pill" data-w="' + p.id + '" aria-pressed="' + (t.workers || []).includes(p.name) + '">' + esc(p.name) + '</button>').join('') + '</div>' : '')
        + '<label class="ac-lab" for="acDNote">Notes <small class="font-normal text-gray-400">(optional)</small></label><textarea id="acDNote" class="form-textarea w-full" rows="2" maxlength="2000" placeholder="Anything worth remembering"></textarea>'
        + '<label class="ac-lab">A photo <small class="font-normal text-gray-400">(optional)</small></label><input type="file" id="acDPhoto" accept="image/*" capture="environment" class="text-sm">'
        + '<div class="ac-macts"><button type="button" class="btn btn-white" data-close>Cancel</button><button type="button" class="btn btn-primary" id="acDOk">Mark done</button></div>', (box) => {
        box.querySelector('#acDWho')?.addEventListener('click', (e) => { const b = e.target.closest('[data-w]'); if (b) b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true')); });
        box.querySelector('#acDOk').addEventListener('click', async (e) => {
            const fd = new FormData();
            fd.append('date', box.querySelector('#acDDate').value || today());
            fd.append('used', box.querySelector('#acDUsed').value); fd.append('note', box.querySelector('#acDNote').value);
            box.querySelectorAll('#acDWho [aria-pressed="true"]').forEach((b) => fd.append('workers[]', b.dataset.w));
            const ph = box.querySelector('#acDPhoto').files[0]; if (ph) fd.append('photo', ph);
            e.target.disabled = true; e.target.textContent = 'Saving…';
            try { await window.api(U.done(t.id), { method: 'POST', body: fd }); closeModal(); card.classList.add('is-leaving'); setTimeout(() => load(true).then(scrollEnd), 380); }
            catch (err) { window.toast?.(err.message, 'error'); e.target.disabled = false; e.target.textContent = 'Mark done'; }
        });
    });
    const cantModal = (t, card) => modal('<h3>🙅 Can\'t do it today?</h3><p class="ac-msub">' + esc(t.title) + '. Say why and Anee moves it to the best day near it' + (t.critical ? ': this one matters for the yield, so she keeps it close.' : '.') + '</p>'
        + '<div class="ac-reasons" id="acCWhy">' + F.cant.map((c) => '<button type="button" class="ac-reason" data-r="' + c.key + '" aria-pressed="false"><span>' + c.emoji + '</span>' + esc(c.label) + '</button>').join('') + '</div>'
        + '<span class="ac-lab">Not before</span><div class="ac-pills" id="acCDays">' + [[0, 'Anee decides'], [1, 'Tomorrow'], [2, 'In 2 days'], [3, 'In 3 days'], [7, 'Next week']].map(([d, l]) => '<button type="button" class="ac-pill" data-d="' + d + '" aria-pressed="' + (d === 0) + '">' + l + '</button>').join('') + '</div>'
        + '<label class="ac-lab" for="acCNote">Anything to add <small class="font-normal text-gray-400">(optional)</small></label><textarea id="acCNote" class="form-textarea w-full" rows="2" maxlength="1000"></textarea>'
        + '<div class="ac-macts"><button type="button" class="btn btn-white" data-close>Cancel</button><button type="button" class="btn btn-primary" id="acCOk" disabled>Move it</button></div>', (box) => {
        box.querySelector('#acCWhy').addEventListener('click', (e) => { const b = e.target.closest('[data-r]'); if (!b) return; box.querySelectorAll('[data-r]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); box.querySelector('#acCOk').disabled = false; });
        box.querySelector('#acCDays').addEventListener('click', (e) => { const b = e.target.closest('[data-d]'); if (!b) return; box.querySelectorAll('[data-d]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); });
        box.querySelector('#acCOk').addEventListener('click', async (e) => {
            const r = box.querySelector('[data-r][aria-pressed="true"]'), d = Number(box.querySelector('[data-d][aria-pressed="true"]')?.dataset.d || 0);
            e.target.disabled = true; e.target.textContent = 'Moving…';
            try { const res = await window.api(U.cant(t.id), { method: 'POST', body: { reason: r.dataset.r, note: box.querySelector('#acCNote').value, days: d || null } }); closeModal(); card.classList.add('is-leaving'); window.toast?.(res.message, 'success'); setTimeout(() => load(true).then(scrollEnd), 380); }
            catch (err) { window.toast?.(err.message, 'error'); e.target.disabled = false; e.target.textContent = 'Move it'; }
        });
    });
    // After something new: the newest story card, just above today's plan, comes into view.
    const scrollEnd = () => requestAnimationFrame(() => { const t = $('acToday'); let last = t ? t.previousElementSibling : null; if (last && last.classList.contains('ac-day')) last = last.previousElementSibling; (last || t)?.scrollIntoView({ block: 'center', behavior: still() ? 'auto' : 'smooth' }); });

    $('acSafeBtn').addEventListener('click', () => modal('<h3>🛡️ Safety and mixing</h3><p class="ac-msub">Anee checks every spray against these. Read the product label too: it is the law.</p>'
        + ((F.plan.safety || []).length ? '<div class="ac-safe"><ul>' + F.plan.safety.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul></div>' : '<p class="text-sm text-gray-500">No safety notes yet.</p>')
        + ((F.plan.compatibility || []).length ? '<span class="ac-lab">Do not mix</span><ul class="text-sm text-gray-700 list-disc ml-5">' + F.plan.compatibility.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul>' : '')
        + '<div class="ac-macts"><button type="button" class="btn btn-primary" data-close>Got it</button></div>'));
    $('acLogBtn').addEventListener('click', async () => {
        modal('<h3>📖 Season log</h3><p class="ac-msub">Loading…</p>');
        try {
            const r = await window.api(U.log), L = r.data;
            $('acModalBox').innerHTML = '<h3>📖 Season log</h3><p class="ac-msub">' + L.counts.done + ' tasks done, ' + L.counts.moved + ' moved, ' + L.counts.observations + ' observations. Kept for the post harvest report.</p>'
                + L.days.map((d) => '<div class="ac-log-day"><h4>' + esc(d.label) + '</h4>' + d.rows.map((x) => '<div class="ac-log-row"><i>' + esc(x.at) + '</i><span>' + (x.role === 'farmer' ? '<b>You</b> ' : '<b>Anee</b> ') + (x.lot ? '(' + esc(x.lot) + ') ' : '') + esc(x.text) + '</span></div>').join('') + '</div>').join('')
                + '<div class="ac-macts"><button type="button" class="btn btn-primary" data-close>Close</button></div>';
            $('acModalBox').querySelector('[data-close]').addEventListener('click', closeModal);
        } catch (err) { window.toast?.(err.message, 'error'); }
    });

    /* ---- the composer ---- */
    let mode = 'ask', photos = [], video = null, frames = [], voice = null, rec = null, recChunks = [], recStream = null, recT0 = 0;
    const setMode = (m) => {
        mode = m;
        document.querySelectorAll('.ac-mode').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.mode === m)));
        $('acText').placeholder = m === 'ask' ? 'Ask Anee about your season' : 'What do you see? Like: yellow leaves on the lower part, patches near the canal';
        $('acLotPick').hidden = m !== 'observe' || !(F && F.lots.length > 1);
        $('acQuick').hidden = m !== 'ask';
    };
    document.querySelector('.ac-modes').addEventListener('click', (e) => { const b = e.target.closest('[data-mode]'); if (b) setMode(b.dataset.mode); });
    // A ready question goes in the box; the farmer sends it (or changes it first).
    $('acQuick').addEventListener('click', (e) => {
        const b = e.target.closest('[data-q]'); if (!b) return;
        const t = $('acText'); t.value = b.dataset.q; t.dispatchEvent(new Event('input')); t.focus();
    });
    const paintAtt = () => {
        $('acAtt').innerHTML = photos.map((p, i) => '<span><img src="' + p.url + '" alt=""><button type="button" data-rm="p' + i + '">✕</button></span>').join('')
            + (video ? '<span>' + (frames[0] ? '<img src="' + frames[0] + '" alt="">' : '') + '🎥 Video<button type="button" data-rm="v">✕</button></span>' : '')
            + (voice ? '<span>🎙️ ' + voice.secs + 's voice note<button type="button" data-rm="a">✕</button></span>' : '');
        if (photos.length || video || voice) setMode('observe');
    };
    $('acAtt').addEventListener('click', (e) => { const b = e.target.closest('[data-rm]'); if (!b) return; const k = b.dataset.rm; if (k === 'v') { video = null; frames = []; } else if (k === 'a') voice = null; else photos.splice(Number(k.slice(1)), 1); paintAtt(); });
    $('acPhoto').addEventListener('click', () => $('acPhotoIn').click());
    $('acVideo').addEventListener('click', () => $('acVideoIn').click());
    $('acPhotoIn').addEventListener('change', () => { [...$('acPhotoIn').files].slice(0, 3 - photos.length).forEach((f) => photos.push({ file: f, url: URL.createObjectURL(f) })); $('acPhotoIn').value = ''; paintAtt(); });
    // Three frames from the clip, for Anee's eyes; the clip itself is kept in the log.
    const grabFrames = (file) => new Promise((resolve) => {
        const v = document.createElement('video'); v.muted = true; v.playsInline = true; v.preload = 'auto'; v.src = URL.createObjectURL(file);
        const out = []; let idx = 0, at = [];
        const snap = () => { const c = document.createElement('canvas'); const w = Math.min(960, v.videoWidth || 640); c.width = w; c.height = Math.round(w * (v.videoHeight || 360) / (v.videoWidth || 640)); c.getContext('2d').drawImage(v, 0, 0, c.width, c.height); out.push(c.toDataURL('image/jpeg', .8)); };
        v.addEventListener('loadedmetadata', () => { const d = v.duration || 1; at = [d * .15, d * .5, d * .85]; v.currentTime = at[0]; });
        v.addEventListener('seeked', () => { try { snap(); } catch (_) {} idx++; if (idx < at.length) v.currentTime = at[idx]; else resolve(out); });
        v.addEventListener('error', () => resolve(out));
        setTimeout(() => resolve(out), 9000);
    });
    $('acVideoIn').addEventListener('change', async () => {
        const f = $('acVideoIn').files[0]; $('acVideoIn').value = ''; if (!f) return;
        if (f.size > 60 * 1024 * 1024) return window.toast?.('That video is over 60 MB. Record a shorter one (10 to 20 seconds is plenty).', 'error');
        video = f; frames = []; paintAtt(); frames = await grabFrames(f); paintAtt();
    });
    $('acVoice').addEventListener('click', async () => {
        if (rec && rec.state === 'recording') { rec.stop(); return; }
        if (!navigator.mediaDevices || !window.MediaRecorder) return window.toast?.('This browser cannot record audio.', 'error');
        try { recStream = await navigator.mediaDevices.getUserMedia({ audio: true }); } catch (_) { return window.toast?.('Microphone blocked. Allow it for this site.', 'error'); }
        const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'].find((t) => MediaRecorder.isTypeSupported(t)) || '';
        rec = new MediaRecorder(recStream, mime ? { mimeType: mime } : undefined); recChunks = [];
        rec.ondataavailable = (e) => { if (e.data && e.data.size) recChunks.push(e.data); };
        rec.onstop = () => { recStream.getTracks().forEach((t) => t.stop()); $('acVoice').classList.remove('is-rec'); $('acVoice').textContent = '🎙️';
            const type = rec.mimeType || 'audio/webm'; voice = { blob: new Blob(recChunks, { type }), ext: type.includes('mp4') ? 'm4a' : 'webm', secs: Math.round((Date.now() - recT0) / 1000) }; paintAtt(); };
        rec.start(250); recT0 = Date.now(); $('acVoice').classList.add('is-rec'); $('acVoice').textContent = '⏹';
        setTimeout(() => { if (rec && rec.state === 'recording') rec.stop(); }, 180000);
    });
    $('acText').addEventListener('input', () => { const t = $('acText'); t.style.height = 'auto'; t.style.height = Math.min(128, t.scrollHeight) + 'px'; });
    const typing = () => { $('acToday')?.insertAdjacentHTML('beforebegin', '<div class="ac-row" id="acTyping">' + avatar + '<div class="ac-bub"><span class="ac-typing"><i></i><i></i><i></i></span></div></div>'); scrollEnd(); };
    $('acSend').addEventListener('click', async () => {
        const text = $('acText').value.trim();
        if (mode === 'ask' && !text) return;
        if (mode === 'observe' && !text && !photos.length && !video && !voice) return window.toast?.('Add a photo, a video, a voice note or a few words.', 'error');
        $('acSend').disabled = true;
        const said = mode === 'ask'
            ? '<div class="ac-row is-me"><div class="ac-bub">' + esc(text) + '</div></div>'
            : '<div class="ac-row is-me">' + card('leaf', '📷 From the field', '', (text ? '<div>' + esc(text) + '</div>' : (voice ? '🎙️ Voice note' : '')) + (photos.length ? '<div class="ac-media' + (photos.length === 1 ? ' is-one' : '') + '">' + photos.map((p) => '<img src="' + p.url + '" alt="">').join('') + '</div>' : ''), '') + '</div>';
        $('acToday') ? $('acToday').insertAdjacentHTML('beforebegin', said) : $('acFeed').insertAdjacentHTML('beforeend', said);
        typing();
        try {
            if (mode === 'ask') await window.api(U.ask, { method: 'POST', body: { text } });
            else {
                const fd = new FormData();
                fd.append('text', text); if ($('acLotPick').value) fd.append('lotId', $('acLotPick').value);
                photos.forEach((p) => fd.append('photos[]', p.file));
                frames.forEach((f) => fd.append('frames[]', f));
                if (video) fd.append('video', video);
                if (voice) fd.append('audio', voice.blob, 'voice.' + voice.ext);
                await window.api(U.observe, { method: 'POST', body: fd });
            }
            $('acText').value = ''; $('acText').style.height = ''; photos = []; video = null; frames = []; voice = null; paintAtt(); setMode('ask');
        } catch (err) { window.toast?.(err.message, 'error'); }
        $('acSend').disabled = false;
        await load(true); scrollEnd();
    });
    $('acText').addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && !matchMedia('(hover: none)').matches) { e.preventDefault(); $('acSend').click(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && F) load(true); });

    // app.js (window.api) may still be on its way when this runs.
    if (window.api) load(); else window.addEventListener('load', () => load(), { once: true });
})();
</script>
@endpush
