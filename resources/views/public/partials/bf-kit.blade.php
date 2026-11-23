{{-- The landing pages' kit (2026-11-24): the bf- styles and the phone compare switch, shared by the farm management
     app, software and farm management pages. Included once per page. --}}
@push('head')
<style>
    /* ===================== THE FARM MANAGEMENT APP FOR THE PHILIPPINES (2026-11-18) ===================== */
    .bf-more { display: inline-flex; align-items: center; gap: .35rem; margin-top: auto; padding-top: .7rem; font-size: .88rem; font-weight: 800; color: var(--hp-green); text-decoration: none; }
    .bf-more svg { width: 1rem; height: 1rem; flex: none; transition: transform .28s var(--hp-ease); }
    .bf-more:hover svg { transform: translateX(4px); }
    .bf-steps-sec .hp-alt svg { width: 1.15rem; height: 1.15rem; flex: none; transition: transform .28s var(--hp-ease); }
    .bf-steps-sec .hp-alt:hover svg { transform: translateX(4px); }

    /* ---- hero: planting day, shaded deep green, the phone with the season on it ---- */
    .bf-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; padding: 2.25rem 0 2.5rem; }
    @media (min-width: 1024px) { .bf-hero { padding: 3.25rem 0 3rem; } }
    .bf-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.06); animation: bfDrift 28s ease-in-out infinite alternate; }
    @keyframes bfDrift { to { transform: scale(1.12) translate(-1.5%, -1%); } }
    .bf-hero-shade { position: absolute; inset: 0; z-index: -1;
        background: radial-gradient(55rem 28rem at 85% 15%, rgb(245 197 24 / .16), transparent 60%),
            linear-gradient(105deg, rgb(10 20 6 / .95) 0%, rgb(16 32 9 / .9) 48%, rgb(24 46 12 / .72) 100%); }
    .bf-hero-grid { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 1024px) { .bf-hero-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 3rem; } }
    .bf-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 700; color: rgb(255 255 255 / .7); }
    .bf-crumbs a { color: rgb(255 255 255 / .85); text-decoration: none; } .bf-crumbs a:hover { color: #fff; }
    .bf-crumbs svg { width: .8rem; height: .8rem; }
    .bf-badge { display: inline-flex; align-items: center; gap: .55rem; margin-top: 1rem; padding: .4rem .85rem .4rem .45rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: #fff; background: rgb(255 255 255 / .1); border: 1px solid rgb(255 255 255 / .22); backdrop-filter: blur(8px); }
    .bf-badge i { display: grid; place-items: center; width: 1.9rem; height: 1.3rem; border-radius: .3rem; overflow: hidden; box-shadow: 0 0 0 1px rgb(255 255 255 / .4); }
    .bf-badge svg { width: 100%; height: 100%; display: block; }
    .bf-h1 { margin-top: 1rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(2rem, 5.2vw, 3.45rem); line-height: 1.07; letter-spacing: -.02em; text-wrap: balance; }
    .bf-lede { margin-top: 1.1rem; max-width: 38rem; font-size: clamp(1.02rem, 1.6vw, 1.15rem); line-height: 1.7; color: #d7e4cb; }
    .bf-lede b { color: #fff; }
    .bf-ctas { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.6rem; }
    .bf-ticks { display: flex; flex-wrap: wrap; gap: .5rem 1.2rem; margin-top: 1.3rem; font-size: .88rem; font-weight: 700; color: #e6efdc; }
    .bf-ticks li { display: inline-flex; align-items: center; gap: .4rem; }
    .bf-ticks svg { width: 1rem; height: 1rem; color: var(--hp-sun); }
    .bf-hero-art { position: relative; display: grid; place-items: center; padding: .5rem 0 1rem; }
    .bf-phone { position: relative; width: min(17rem, 62vw); margin: 0; padding: .55rem; border-radius: 2.4rem; background: #0c1208;
        box-shadow: 0 0 0 1.5px rgb(255 255 255 / .18), 0 40px 80px -30px rgb(0 0 0 / .9), 0 0 120px -20px rgb(245 197 24 / .25);
        animation: bfFloat 7s ease-in-out infinite; }
    @keyframes bfFloat { 50% { transform: translateY(-10px); } }
    .bf-phone img { display: block; width: 100%; height: auto; border-radius: 1.9rem; }
    .bf-notch { position: absolute; top: 1rem; left: 50%; width: 4.2rem; height: 1.1rem; margin-left: -2.1rem; border-radius: 999px; background: #0c1208; z-index: 1; }
    .bf-float { position: absolute; z-index: 2; display: inline-flex; align-items: center; gap: .45rem; max-width: 15rem; padding: .5rem .8rem .5rem .5rem; border-radius: 999px;
        font-size: .78rem; font-weight: 800; color: #14210c; background: #fff; box-shadow: 0 16px 34px -14px rgb(0 0 0 / .7);
        opacity: 0; animation: bfPop .6s var(--hp-ease) forwards, bfBob 6s ease-in-out infinite; animation-delay: var(--d), calc(var(--d) + .6s); }
    .bf-float i { flex: none; display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; font-style: normal; font-size: .85rem; font-weight: 900; color: #2f5219; background: #eaf4df; }
    .bf-float-face { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px var(--hp-sun); }
    .bf-float.is-a { top: 9%; left: -2%; } .bf-float.is-b { top: 30%; right: -4%; } .bf-float.is-c { bottom: 32%; left: -6%; } .bf-float.is-d { bottom: 9%; right: -2%; }
    @media (max-width: 1023.98px) { .bf-float.is-a { left: 0; } .bf-float.is-b { right: 0; } .bf-float.is-c { left: 0; } .bf-float.is-d { right: 0; } }
    @media (max-width: 479.98px) { .bf-float { font-size: .7rem; max-width: 11.5rem; padding: .4rem .65rem .4rem .4rem; } .bf-float i, .bf-float-face { width: 1.35rem; height: 1.35rem; } }
    @keyframes bfPop { from { opacity: 0; transform: translateY(10px) scale(.9); } to { opacity: 1; transform: none; } }
    @keyframes bfBob { 50% { transform: translateY(-5px); } }
    .bf-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: 2.2rem; }
    @media (min-width: 640px) { .bf-facts { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    .bf-facts div { display: flex; flex-direction: column-reverse; padding: .85rem 1rem; border-radius: 1.1rem; background: rgb(255 255 255 / .08); border: 1px solid rgb(255 255 255 / .14); backdrop-filter: blur(8px); }
    .bf-facts div:last-child { grid-column: 1 / -1; } @media (min-width: 640px) { .bf-facts div:last-child { grid-column: auto; } }
    .bf-facts dd { font-family: var(--font-heading); font-size: clamp(1.5rem, 3.4vw, 2rem); font-weight: 800; line-height: 1; color: var(--hp-sun); }
    .bf-facts dt { margin-top: .35rem; font-size: .8rem; font-weight: 700; line-height: 1.35; color: #d7e4cb; }

    /* ---- the short answer, the way an assistant would quote it ---- */
    .bf-answer-sec { padding-block: 3.5rem; }
    .bf-answer { position: relative; padding: 1.6rem 1.5rem 1.5rem; border-radius: 1.6rem; background: linear-gradient(160deg, #fbfdf7, #f1f8e8); border: 1px solid #d9eac6;
        box-shadow: 0 30px 60px -45px rgb(47 82 25 / .8); }
    .bf-answer::before { content: ''; position: absolute; left: 1.5rem; right: 1.5rem; top: 0; height: 4px; border-radius: 0 0 4px 4px; background: linear-gradient(90deg, var(--hp-green), var(--hp-sun)); }
    .bf-answer-k { display: inline-flex; align-items: center; gap: .5rem; font-size: .76rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .bf-answer-k img { width: 1.6rem; height: 1.6rem; border-radius: 999px; box-shadow: 0 0 0 2px var(--hp-sun); }
    .bf-answer-q { margin-top: .6rem; font-family: var(--font-heading); font-size: clamp(1.3rem, 2.8vw, 1.75rem); font-weight: 800; line-height: 1.2; color: var(--hp-ink); }
    .bf-answer-a { margin-top: .8rem; font-size: 1.04rem; line-height: 1.75; color: #374151; }
    .bf-best { margin-top: 1.2rem; padding-top: 1rem; border-top: 1px dashed #cfe3b8; }
    .bf-best p { font-size: .76rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
    .bf-best ul { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .55rem; }
    .bf-best li { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem .35rem .5rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #2f5219; background: #fff; border: 1px solid #cfe3b8; }
    .bf-best svg { width: .95rem; height: .95rem; color: var(--hp-green); }

    /* ---- what to look for ---- */
    .bf-must-sec { background: linear-gradient(180deg, #f6faf1, #fff); }
    .bf-must { display: grid; gap: 1rem; margin-top: 2.75rem; counter-reset: none; }
    @media (min-width: 640px) { .bf-must { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .bf-must { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .bf-mcard { position: relative; display: flex; flex-direction: column; padding: 1.3rem 1.2rem 1.15rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e5eedc;
        box-shadow: 0 20px 40px -36px rgb(20 33 12 / .6); transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .bf-mcard:hover { transform: translateY(-3px); border-color: #cfe3b8; box-shadow: 0 26px 46px -32px rgb(20 33 12 / .55); }
    .bf-mnum { position: absolute; top: 1rem; right: 1.1rem; font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: #e3eed6; }
    .bf-mico { display: grid; place-items: center; width: 2.8rem; height: 2.8rem; border-radius: .95rem; color: #2f5219; background: linear-gradient(140deg, #eaf4df, #d7ebc2); }
    .bf-mico svg { width: 1.4rem; height: 1.4rem; }
    .bf-mcard h3 { margin-top: .85rem; font-family: var(--font-heading); font-size: 1.08rem; font-weight: 800; line-height: 1.3; color: var(--hp-ink); padding-right: 1.8rem; }
    .bf-why { margin-top: .45rem; font-size: .88rem; line-height: 1.6; color: #6b7280; }
    .bf-how { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .88rem; line-height: 1.55; color: #24420f; background: #f3f9ec; }
    .bf-how b { display: flex; align-items: center; gap: .3rem; margin-bottom: .15rem; font-size: .74rem; letter-spacing: .06em; text-transform: uppercase; color: var(--hp-green); }
    .bf-how svg { width: .9rem; height: .9rem; }

    /* ---- side by side ---- */
    .bf-pick { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .4rem; margin-top: 2.2rem; }
    .bf-pick span { margin-right: .2rem; font-size: .82rem; font-weight: 800; color: #6b7280; }
    .bf-pick button { padding: .5rem .95rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #2f5219; background: #fff; border: 1.5px solid #cfe3b8;
        transition: background-color .28s var(--hp-ease), color .28s var(--hp-ease), border-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .bf-pick button:hover { transform: translateY(-1px); border-color: var(--hp-green); }
    .bf-pick button[aria-pressed="true"] { color: #fff; background: var(--hp-green); border-color: var(--hp-green); }
    @media (min-width: 900px) { .bf-pick { display: none; } }
    .bf-tbl-wrap { margin-top: 1.2rem; border-radius: 1.4rem; border: 1px solid #e5eedc; overflow: clip; background: #fff; box-shadow: 0 30px 60px -48px rgb(20 33 12 / .7); }
    @media (min-width: 900px) { .bf-tbl-wrap { margin-top: 2.5rem; } }
    .bf-tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .bf-tbl th, .bf-tbl td { padding: .85rem .8rem; text-align: left; vertical-align: top; border-bottom: 1px solid #eef3e8; }
    .bf-tbl tbody tr:last-child th, .bf-tbl tbody tr:last-child td { border-bottom: 0; }
    /* The column names stay in sight under the site's bar while the rows scroll by (the wrapper clips, it does not scroll). */
    .bf-tbl thead th { position: sticky; top: 4rem; z-index: 1; font-size: .78rem; font-weight: 800; color: #4b5563; background: #f8fbf4; box-shadow: inset 0 -1px 0 #e5eedc; }
    @media (min-width: 1024px) { .bf-tbl thead th { top: 5rem; } }
    .bf-tbl thead th:first-child { width: 26%; }
    .bf-tbl tbody th { font-size: .9rem; font-weight: 800; line-height: 1.35; color: var(--hp-ink); }
    .bf-tbl td { font-size: .84rem; line-height: 1.4; color: #4b5563; transition: opacity .28s var(--hp-ease); }
    .bf-tbl .is-anee { background: linear-gradient(180deg, #f3faea, #eaf5dd); color: #24420f; }
    .bf-tbl thead .is-anee { color: #fff; background: linear-gradient(135deg, #3d6823, #24420f); }
    .bf-tbl thead .is-anee img { display: inline-block; width: 1.5rem; height: 1.5rem; margin-right: .4rem; vertical-align: middle; border-radius: 999px; box-shadow: 0 0 0 2px var(--hp-sun); }
    .bf-tbl td.is-anee .bf-w { font-weight: 800; }
    .bf-m { display: inline-grid; place-items: center; width: 1.45rem; height: 1.45rem; margin-bottom: .3rem; border-radius: 999px; }
    .bf-m svg { width: .85rem; height: .85rem; }
    .bf-m.is-y { color: #fff; background: #3f8f3a; } .bf-m.is-p { color: #2b2100; background: #e3ac2f; } .bf-m.is-n { color: #fff; background: #c9472e; }
    .bf-w { display: block; }
    .bf-key { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem 1.3rem; margin-top: 1rem; font-size: .8rem; font-weight: 700; color: #6b7280; }
    .bf-key span { display: inline-flex; align-items: center; gap: .4rem; }
    .bf-key .bf-m { margin: 0; width: 1.2rem; height: 1.2rem; } .bf-key .bf-m svg { width: .7rem; height: .7rem; }
    /* A phone: anee.io beside one other way at a time, picked above the table. */
    @media (max-width: 899.98px) {
        .bf-tbl [data-c]:not([data-c="anee"]) { display: none; }
        .bf-tbl[data-vs="note"] [data-c="note"], .bf-tbl[data-vs="sheet"] [data-c="sheet"], .bf-tbl[data-vs="abroad"] [data-c="abroad"], .bf-tbl[data-vs="chat"] [data-c="chat"],
        .bf-tbl[data-vs="acct"] [data-c="acct"], .bf-tbl[data-vs="store"] [data-c="store"] { display: table-cell; }
        .bf-tbl thead th:first-child { width: 34%; }
        .bf-tbl th, .bf-tbl td { padding: .75rem .6rem; }
        .bf-tbl tbody th { font-size: .82rem; }
        .bf-tbl td { font-size: .8rem; }
        .bf-tbl.is-swap td[data-c]:not([data-c="anee"]) { animation: bfSwap .32s var(--hp-ease) both; }
    }
    @keyframes bfSwap { from { opacity: 0; transform: translateY(4px); } }

    /* ---- the season in one app ---- */
    .bf-steps-sec { background: radial-gradient(60rem 30rem at 15% 0%, rgb(245 197 24 / .1), transparent 60%), linear-gradient(160deg, #1d3310, #0f1d08); color: #fff; }
    .bf-steps { display: grid; gap: .9rem; margin-top: 2.75rem; }
    @media (min-width: 640px) { .bf-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .bf-steps { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .bf-step { position: relative; padding: 1.2rem 1.15rem 1.1rem; border-radius: 1.3rem; background: rgb(255 255 255 / .06); border: 1px solid rgb(255 255 255 / .12);
        transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .bf-step:hover { background: rgb(255 255 255 / .1); transform: translateY(-3px); }
    .bf-snum { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: 999px; font-weight: 900; color: #14210c; background: var(--hp-sun); box-shadow: 0 0 0 5px rgb(245 197 24 / .18); }
    .bf-step h3 { margin-top: .8rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #fff; }
    .bf-step > p { margin-top: .35rem; font-size: .86rem; line-height: 1.55; color: #c9d8bb; }
    .bf-step ul { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .75rem; }
    .bf-step li { padding: .22rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: #e6efdc; background: rgb(255 255 255 / .09); }
    .bf-step li.is-more { color: var(--hp-sun); background: transparent; box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }

    /* ---- the farms ---- */
    .bf-farms { display: grid; gap: 1rem; margin-top: 2.75rem; }
    @media (min-width: 640px) { .bf-farms { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .bf-farms { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .bf-farm { display: flex; flex-direction: column; padding: 1.3rem 1.25rem 1.15rem; border-radius: 1.3rem; background: #fbfdf8; border: 1px solid #e5eedc;
        transition: transform .28s var(--hp-ease), border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .bf-farm:hover { transform: translateY(-3px); border-color: #cfe3b8; box-shadow: 0 26px 46px -34px rgb(20 33 12 / .55); }
    .bf-farm-em { display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 1rem; font-size: 1.6rem; background: #fff; box-shadow: 0 10px 24px -14px rgb(20 33 12 / .5); }
    .bf-farm h3 { margin-top: .85rem; font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--hp-ink); }
    .bf-farm p { margin-top: .4rem; font-size: .9rem; line-height: 1.6; color: #4b5563; }

    /* ---- Anee ---- */
    .bf-anee-sec { background: linear-gradient(180deg, #fffaf0, #fff); }
    .bf-anee { display: grid; gap: 2.2rem; align-items: center; }
    @media (min-width: 1024px) { .bf-anee { grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); gap: 3.5rem; } }
    .bf-anee-art { justify-self: center; width: min(26rem, 90%); }
    .bf-anee-art img { display: block; width: 100%; height: auto; border-radius: 2rem; box-shadow: 0 40px 80px -40px rgb(20 33 12 / .7); transform: rotate(-2deg); }
    .bf-anee-list { display: grid; gap: .7rem; margin-top: 1.4rem; }
    .bf-anee-list li { position: relative; padding-left: 1.9rem; font-size: 1rem; line-height: 1.6; color: #4b5563; }
    .bf-anee-list li::before { content: ''; position: absolute; left: 0; top: .3rem; width: 1.15rem; height: 1.15rem; border-radius: 999px; background: var(--hp-sun);
        box-shadow: 0 0 0 4px rgb(245 197 24 / .22); }
    .bf-anee-list b { color: var(--hp-ink); }

    /* ---- plans ---- */
    .bf-plans { display: grid; gap: 1rem; margin-top: 2.75rem; }
    @media (min-width: 640px) { .bf-plans { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .bf-plans { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .bf-plan { position: relative; padding: 1.35rem 1.25rem 1.25rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e5eedc; transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .bf-plan:hover { transform: translateY(-3px); box-shadow: 0 26px 46px -34px rgb(20 33 12 / .55); }
    .bf-plan.is-top { border: 2px solid var(--hp-sun); box-shadow: 0 30px 60px -40px rgb(245 197 24 / .9); }
    .bf-plan-tag { position: absolute; top: -.75rem; left: 1.2rem; padding: .25rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 900; color: #2b2100; background: var(--hp-sun); }
    .bf-plan h3 { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--hp-ink); }
    .bf-price { display: flex; align-items: baseline; gap: .4rem; margin-top: .4rem; }
    .bf-price b { font-family: var(--font-heading); font-size: 2rem; font-weight: 800; color: var(--hp-deep); }
    .bf-price span { font-size: .84rem; font-weight: 700; color: #6b7280; }
    .bf-plan ul { display: grid; gap: .45rem; margin-top: .9rem; }
    .bf-plan li { display: flex; gap: .45rem; font-size: .86rem; line-height: 1.45; color: #4b5563; }
    .bf-plan li svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: var(--hp-green); }

    /* ---- FAQ ---- */
    .bf-faq-sec { background: #f6faf1; }
    .bf-faq { display: grid; gap: .65rem; margin-top: 2.4rem; }
    .bf-qa { border-radius: 1.1rem; background: #fff; border: 1px solid #e5eedc; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .bf-qa[open] { border-color: #cfe3b8; box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); }
    .bf-qa summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; cursor: pointer; list-style: none; }
    .bf-qa summary::-webkit-details-marker { display: none; }
    .bf-qa h3 { font-size: 1rem; font-weight: 800; line-height: 1.4; color: var(--hp-ink); }
    .bf-qa-plus { position: relative; flex: none; width: 1.8rem; height: 1.8rem; border-radius: 999px; background: #eaf4df; transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .bf-qa-plus::before, .bf-qa-plus::after { content: ''; position: absolute; left: 50%; top: 50%; width: .8rem; height: 2px; margin: -1px 0 0 -.4rem; border-radius: 2px; background: var(--hp-deep); }
    .bf-qa-plus::after { transform: rotate(90deg); transition: transform .28s var(--hp-ease); }
    .bf-qa[open] .bf-qa-plus { background: var(--hp-sun); }
    .bf-qa[open] .bf-qa-plus::after { transform: rotate(0deg); }
    .bf-qa-a { padding: 0 1.15rem 1.1rem; }
    .bf-qa-a p { font-size: .95rem; line-height: 1.7; color: #4b5563; }

    /* ---- last call ---- */
    .bf-final { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
    .bf-final-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .bf-final-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(12 22 7 / .82), rgb(12 22 7 / .9)); }
    .bf-final-face { width: 4.5rem; height: 4.5rem; margin: 0 auto 1rem; border-radius: 999px; box-shadow: 0 0 0 4px var(--hp-sun), 0 20px 40px -14px rgb(0 0 0 / .7); }

    @media (prefers-reduced-motion: reduce) {
        .bf-hero-bg, .bf-phone, .bf-float { animation: none !important; }
        .bf-float { opacity: 1; }
        .bf-mcard, .bf-step, .bf-farm, .bf-plan, .bf-pick button, .bf-qa-plus, .bf-qa-plus::after { transition: none !important; }
        .bf-tbl.is-swap td { animation: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    // A phone: anee.io beside one other way at a time.
    const tbl = document.getElementById('bfTbl');
    const pick = document.querySelector('.bf-pick');
    if (!tbl || !pick) return;
    pick.addEventListener('click', (e) => {
        const b = e.target.closest('[data-vs]');
        if (!b || b.getAttribute('aria-pressed') === 'true') return;
        pick.querySelectorAll('[data-vs]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        tbl.dataset.vs = b.dataset.vs;
        tbl.classList.remove('is-swap');
        void tbl.offsetWidth;
        tbl.classList.add('is-swap');
    });
})();
</script>
@endpush
