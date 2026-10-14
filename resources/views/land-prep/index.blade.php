@extends('layouts.app')
@section('title', 'Land Prep Planner')
@section('page-title', 'Land Prep Planner')
@section('page-subtitle', 'Every lot ready on planting day')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- LAND PREP PLANNER, by Anee (2026-10-14).

     A wizard, one question a step (crop, how it goes in, variety, planting
     day, lots, crew, soil and water, field history, place), then Anee's
     plan in a full screen view: the whole farm (a timeline across the lots,
     the crew calendar, her reading of the weather and ENSO, the seed
     preparation with its hours) and each lot step by step, every step
     tagged with where it comes from. The arithmetic is the server's
     (App\Support\LandPrepPlanner); the page draws it. Saved plans live on
     "Your plans", renamable, deletable, and any plan can be sent to the
     Protocol Builder. --}}

@section('content')
<style>
    .lp { --lp-e: cubic-bezier(.22,1,.36,1); --lp-g: #4a7c2a; --lp-gd: #2d5016; --lp-gl: #e4efd4; --lp-gx: #f4f9ee; max-width: 40rem; margin: 0 auto; }
    .lp-tabs { display: flex; gap: .35rem; padding: .3rem; margin-bottom: .9rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lp-tab { flex: 1 1 0; min-height: 2.6rem; border-radius: .75rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-500); transition: background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-tab.is-on { background: var(--lp-g); color: #fff; }
    .lp-tab small { font-weight: 800; font-size: .7rem; margin-left: .25rem; padding: .05rem .4rem; border-radius: 999px; background: rgb(0 0 0 / .08); }
    .lp-tab.is-on small { background: rgb(255 255 255 / .22); }

    /* The price card. */
    .lp-quote { display: flex; align-items: center; gap: .7rem; padding: .75rem .9rem; margin-bottom: .9rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; font-size: .82rem; line-height: 1.5; color: #3d5226; }
    .lp-quote img { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .lp-quote b { color: var(--lp-gd); }
    .lp-quote.is-off { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }

    /* The wizard. */
    .lp-wiz { position: relative; padding: 1.1rem 1.1rem 1rem; border-radius: 1.3rem; background: var(--color-white); border: 1px solid var(--color-gray-200); box-shadow: 0 18px 40px -32px rgb(20 33 12 / .45); }
    .lp-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .2rem; }
    .lp-head small { font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--lp-g); }
    .lp-head span { margin-left: auto; font-size: .72rem; font-weight: 800; color: var(--color-gray-400); }
    .lp-bar { height: .32rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; margin-bottom: 1rem; }
    .lp-bar i { display: block; height: 100%; width: 10%; border-radius: 999px; background: linear-gradient(90deg, #86b556, var(--lp-g)); transition: width .45s var(--lp-e); }
    .lp-step { display: none; }
    .lp-step.is-on { display: block; animation: lpIn .34s var(--lp-e) both; }
    .lp-step.is-back { animation-name: lpBack; }
    @keyframes lpIn { from { opacity: 0; transform: translateX(22px); } }
    @keyframes lpBack { from { opacity: 0; transform: translateX(-22px); } }
    .lp-q { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900); }
    .lp-sub { margin: .3rem 0 1rem; font-size: .84rem; line-height: 1.5; color: var(--color-gray-500); }
    .lp-nav { display: flex; gap: .6rem; margin-top: 1.2rem; }
    .lp-nav .btn { flex: 1 1 0; min-height: 3rem; font-size: .95rem; }

    .lp-choices { display: grid; gap: .55rem; }
    @media (min-width: 560px) { .lp-choices.is-2 { grid-template-columns: 1fr 1fr; } }
    .lp-choice { width: 100%; display: flex; align-items: center; gap: .75rem; padding: .8rem .85rem; border-radius: 1rem; text-align: left; background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), transform .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-choice:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .lp-choice.is-on { border-color: var(--lp-g); background: var(--lp-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .lp-choice .ce { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.35rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lp-choice.is-on .ce { background: #fff; border-color: #d2e5bd; }
    .lp-choice .ct { min-width: 0; flex: 1; }
    .lp-choice .ct b { display: block; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .lp-choice .ct small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.4; color: var(--color-gray-500); }
    .lp-choice .ct em { display: block; margin-top: .3rem; font-style: normal; font-size: .7rem; font-weight: 700; color: var(--lp-g); }
    .lp-choice .ck { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; opacity: 0; transform: scale(.5); transition: opacity .28s var(--lp-e), transform .28s var(--lp-e); }
    .lp-choice .ck svg { width: .8rem; height: .8rem; }
    .lp-choice.is-on .ck { opacity: 1; transform: none; }

    /* A tag that opens a sheet (variety, date, province, town). */
    .lp-tag { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .6rem .7rem .6rem .6rem; border-radius: 1rem; text-align: left; background: #f6f8f3; border: 1px solid #dfe8d4; color: #14210c;
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-tag:hover:not(:disabled) { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
    .lp-tag:disabled { opacity: .55; cursor: default; }
    .lp-tag .ti { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.3rem; background: #fff; border: 1px solid #e5ebdf; }
    .lp-tag .ti svg { width: 1.3rem; height: 1.3rem; color: var(--lp-g); }
    .lp-tag .tt { min-width: 0; flex: 1; }
    .lp-tag .tt b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-tag .tt small { display: block; font-size: .76rem; color: #6b7280; line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-tag .tc { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); }
    .lp-tag .tc svg { width: .8rem; height: .8rem; }
    .lp-tag.is-none .tt b { color: var(--color-gray-500); font-weight: 700; }
    .lp-tag.is-bump .ti { animation: lpBump .42s var(--lp-e); }
    @keyframes lpBump { 40% { transform: scale(1.14) rotate(-6deg); } }
    .lp-tag.is-big { padding: .8rem .85rem; }
    .lp-tag.is-big .ti { width: 3.4rem; height: 3.4rem; border-radius: 1rem; flex-direction: column; gap: 0; background: #fff; overflow: hidden; }
    .lp-tag.is-big .ti i { display: block; width: 100%; padding: .12rem 0; font-style: normal; font-size: .58rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; text-align: center; color: #fff; background: var(--lp-g); }
    .lp-tag.is-big .ti em { display: block; font-style: normal; font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; line-height: 1.5; color: var(--color-gray-900); }
    .lp-tag.is-big .tt b { font-size: 1.1rem; }

    .lp-label { display: block; margin: 1rem 0 .4rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .lp-label small { font-weight: 600; color: var(--color-gray-400); }
    .lp-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lp-chipb { min-height: 2.35rem; padding: .35rem .8rem; border-radius: 999px; font-size: .82rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-chipb:hover { border-color: #a8cc7e; }
    .lp-chipb.is-on { color: #fff; background: var(--lp-g); border-color: var(--lp-g); }
    .lp-chipb small { display: block; font-size: .66rem; font-weight: 600; opacity: .75; line-height: 1.2; }
    .lp-hint { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #3d5226; background: var(--lp-gx); border: 1px solid #e1edd3; }
    .lp-hint.is-warn { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .lp-hint b { color: inherit; }

    /* The seed card. */
    .lp-seed { margin-top: .9rem; padding: .85rem .9rem; border-radius: 1rem; background: #fff; border: 1px solid var(--color-gray-200); }
    .lp-seed-h { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .lp-seed-h b { font-size: .9rem; color: var(--color-gray-900); }
    .lp-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .02em; }
    .lp-badge.is-co { background: var(--lp-gl); color: var(--lp-gd); }
    .lp-badge.is-gen { background: #fef3c7; color: #92400e; }
    .lp-badge.is-none { background: var(--color-gray-100); color: var(--color-gray-600); }
    .lp-hours { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .7rem; }
    .lp-hour { padding: .55rem .4rem; border-radius: .85rem; text-align: center; background: var(--color-gray-50); }
    .lp-hour b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.2; }
    .lp-hour small { display: block; font-size: .66rem; font-weight: 700; color: var(--color-gray-500); line-height: 1.3; }
    .lp-seed p { margin-top: .6rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }

    /* Lots. */
    .lp-lots { display: grid; gap: .6rem; }
    .lp-lot { position: relative; padding: .75rem; border-radius: 1rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); animation: lpPop .3s var(--lp-e); }
    .lp-lot.is-gone { opacity: 0; transform: scale(.97); transition: opacity .22s var(--lp-e), transform .22s var(--lp-e); }
    @keyframes lpPop { from { opacity: 0; transform: translateY(8px); } }
    .lp-lot-row { display: grid; grid-template-columns: minmax(0, 1fr) 6.5rem; gap: .5rem; }
    .lp-lot label { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); margin-bottom: .2rem; }
    .lp-lot .form-input { min-height: 2.75rem; font-size: 1rem; }
    .lp-ha { position: relative; }
    .lp-ha span { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%); font-size: .8rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .lp-ha .form-input { padding-right: 2rem; }
    .lp-lot-x { position: absolute; top: .4rem; right: .4rem; width: 1.9rem; height: 1.9rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-400); }
    .lp-lot-x:hover { color: #b91c1c; background: #fee2e2; }
    .lp-lot-x svg { width: .9rem; height: .9rem; }
    .lp-lot-top { display: flex; align-items: center; gap: .4rem; margin-bottom: .5rem; padding-right: 2rem; }
    .lp-lot-top i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .lp-lot-top b { font-size: .78rem; font-weight: 800; color: var(--color-gray-600); }
    .lp-lot-top em { font-style: normal; font-size: .7rem; color: var(--color-gray-400); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-own { margin-top: .5rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .lp-own button { min-height: 2.2rem; padding: .3rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: var(--lp-gd); background: #fff; border: 1px solid #d2e5bd; transition: background-color .28s var(--lp-e); }
    .lp-own button:hover { background: var(--lp-gl); }
    .lp-own button.is-x { color: var(--color-gray-500); border-color: var(--color-gray-200); }
    .lp-lot-acts { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .7rem; }
    .lp-lot-acts .btn { min-height: 2.8rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; white-space: nowrap; font-size: .86rem; padding-left: .6rem; padding-right: .6rem; }
    .lp-lot-acts .btn svg { flex: none; width: 1.05rem; height: 1.05rem; }
    .lp-total { margin-top: .6rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-500); text-align: right; }

    /* Stepper. */
    .lp-stepper { display: flex; align-items: center; gap: .6rem; }
    .lp-stepper button { width: 3rem; height: 3rem; border-radius: 1rem; font-size: 1.4rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); transition: transform .28s var(--lp-e), background-color .28s var(--lp-e); }
    .lp-stepper button:active { transform: scale(.94); }
    .lp-stepper input { width: 5rem; min-height: 3rem; text-align: center; font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; border-radius: 1rem; }
    .lp-stepper span { font-size: .84rem; font-weight: 700; color: var(--color-gray-500); }

    /* Question groups (soil, history). */
    .lp-qg { padding: .8rem 0; border-top: 1px solid var(--color-gray-100); }
    .lp-qg:first-of-type { border-top: 0; padding-top: 0; }
    .lp-qg > b { display: flex; align-items: center; gap: .4rem; margin-bottom: .5rem; font-size: .86rem; font-weight: 800; color: var(--color-gray-800); }
    .lp-qg > b i { width: 1.05rem; height: 1.05rem; flex: none; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .6rem; color: #fff; background: var(--lp-g); transform: scale(0); transition: transform .28s var(--lp-e); }
    .lp-qg.is-done > b i { transform: none; }
    .lp-count { font-size: .76rem; font-weight: 700; color: var(--color-gray-500); margin: -.4rem 0 .6rem; }

    /* Review. */
    .lp-rev { display: grid; gap: .1rem; border-radius: 1rem; overflow: hidden; border: 1px solid var(--color-gray-200); }
    .lp-rev button { display: flex; align-items: center; gap: .7rem; padding: .7rem .8rem; text-align: left; background: #fff; transition: background-color .28s var(--lp-e); }
    .lp-rev button + button { border-top: 1px solid var(--color-gray-100); }
    .lp-rev button:hover { background: var(--lp-gx); }
    .lp-rev .re { flex: none; font-size: 1.15rem; width: 1.6rem; text-align: center; }
    .lp-rev .rt { min-width: 0; flex: 1; }
    .lp-rev .rt small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .lp-rev .rt b { display: block; font-size: .88rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.35; }
    .lp-rev .rc { flex: none; font-size: .72rem; font-weight: 800; color: var(--lp-g); }
    .lp-run { margin-top: 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .55rem; min-height: 3.3rem; padding: .85rem 1rem; border-radius: 1.1rem; color: #fff; font-weight: 800; font-size: 1rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a); background-size: 260% 100%; animation: lpTide 5.5s ease-in-out infinite alternate; box-shadow: 0 12px 26px -14px rgb(61 104 35 / .7); }
    .lp-run:disabled { opacity: .55; animation: none; }
    .lp-run img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .6); }
    @keyframes lpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .lp-fine { margin-top: .5rem; text-align: center; font-size: .74rem; color: var(--color-gray-400); }
    /* A run that failed says why, and stays said (2026-10-15). */
    .lp-err { margin: .2rem 0 .8rem; padding: .8rem .95rem; border-radius: 1rem; background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; animation: lpIn .34s cubic-bezier(.22,1,.36,1) both; }
    .lp-err b { display: block; font-size: .9rem; font-weight: 800; }
    .lp-err p { margin-top: .2rem; font-size: .84rem; line-height: 1.45; }
    html.dark .lp-err { background: #2a1414; border-color: #5b2121; color: #fecaca; }
    @media (prefers-reduced-motion: reduce) { .lp-err { animation: none; } }

    /* Your plans. */
    .lp-find { display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; margin-bottom: .8rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lp-find:focus-within { border-color: #86b556; }
    .lp-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .lp-find input { flex: 1; min-width: 0; min-height: 2.8rem; border: 0; background: transparent; font-size: 1rem; outline: none; box-shadow: none; }
    .lp-cards { display: grid; gap: .7rem; }
    @media (min-width: 640px) { .lp-cards { grid-template-columns: 1fr 1fr; } }
    .lp-card { position: relative; display: flex; flex-direction: column; gap: .55rem; width: 100%; padding: .9rem; border-radius: 1.2rem; text-align: left; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: lpPop .34s var(--lp-e) both; transition: border-color .28s var(--lp-e), box-shadow .28s var(--lp-e), transform .28s var(--lp-e), opacity .28s var(--lp-e); }
    .lp-card:hover { border-color: #a8cc7e; box-shadow: 0 16px 30px -24px rgb(20 33 12 / .6); transform: translateY(-2px); }
    .lp-card.is-gone { opacity: 0; transform: scale(.96); }
    .lp-card-top { display: flex; align-items: flex-start; gap: .7rem; padding-right: 2rem; }
    .lp-card-ico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.4rem; background: var(--lp-gx); border: 1px solid #e1edd3; }
    .lp-card-t { min-width: 0; }
    .lp-card-t b { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .92rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-card-t small { display: block; margin-top: .15rem; font-size: .74rem; color: var(--color-gray-500); }
    .lp-card-meta { display: flex; flex-wrap: wrap; gap: .35rem; }
    .lp-card-meta span { display: inline-flex; align-items: center; gap: .25rem; padding: .18rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lp-card-pen { position: absolute; top: .6rem; right: .6rem; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-400); transition: background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-card-pen:hover { color: var(--lp-gd); background: var(--lp-gl); }
    .lp-card-pen svg { width: .95rem; height: .95rem; }
    .lp-empty { padding: 2.2rem 1rem; text-align: center; border-radius: 1.2rem; background: var(--color-white); border: 1px dashed var(--color-gray-300); }
    .lp-empty b { display: block; font-size: 1rem; color: var(--color-gray-900); }
    .lp-empty p { margin-top: .3rem; font-size: .84rem; color: var(--color-gray-500); }
    .lp-more { display: block; margin: .9rem auto 0; }

    /* ---- the sheets ---- */
    .lp-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .lp-sheet::backdrop { background: rgb(10 18 6 / .5); animation: lpFade .28s var(--lp-e, ease); }
    .lp-sheet.is-closing::backdrop { animation: lpFade .22s ease reverse forwards; }
    .lp-sbox { position: absolute; left: 0; right: 0; bottom: 0; max-height: 90vh; max-height: 90dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0; background: #fff;
        box-shadow: 0 -20px 50px -20px rgb(10 18 6 / .5); animation: lpUp .34s cubic-bezier(.22,1,.36,1); }
    .lp-sheet.is-closing .lp-sbox { animation: lpUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
    @media (min-width: 640px) {
        .lp-sbox { left: 50%; top: 50%; bottom: auto; right: auto; width: min(32rem, calc(100vw - 2rem)); max-height: min(46rem, 88vh); border-radius: 1.4rem; transform: translate(-50%, -50%); animation-name: lpPopC; }
        .lp-sheet.is-closing .lp-sbox { animation-name: lpPopC; }
        .lp-sheet.is-small .lp-sbox { width: min(25rem, calc(100vw - 2rem)); }
    }
    .lp-sheet.is-tall .lp-sbox { height: 86vh; height: 86dvh; }
    @media (min-width: 640px) { .lp-sheet.is-tall .lp-sbox { height: min(44rem, 88vh); } }
    @keyframes lpFade { from { opacity: 0; } }
    @keyframes lpUp { from { transform: translateY(100%); } }
    @keyframes lpPopC { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
    .lp-sbox:focus { outline: none; }
    .lp-grip { width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; flex: none; }
    @media (min-width: 640px) { .lp-grip { display: none; } }
    .lp-shead { flex: none; display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .55rem 1.15rem; }
    .lp-shead h2 { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .lp-shead h2 small { display: block; font-family: inherit; font-size: .76rem; font-weight: 600; color: var(--color-gray-500); }
    .lp-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef; transition: background-color .28s var(--lp-e, ease); }
    .lp-x:hover { background: #e7ecdf; }
    .lp-x svg { width: 1.1rem; height: 1.1rem; }
    .lp-sfind { flex: none; margin: 0 1rem .6rem; display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; }
    .lp-sfind:focus-within { border-color: #86b556; background: #fff; }
    .lp-sfind svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .lp-sfind input { flex: 1; min-width: 0; min-height: 2.75rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
    .lp-slist { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 0 .6rem 1rem; }
    .lp-sgh { position: sticky; top: 0; z-index: 1; display: block; padding: .6rem .55rem .4rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; background: #fff; }
    .lp-row { width: 100%; display: flex; align-items: center; gap: .6rem; padding: .55rem .55rem; border-radius: .9rem; text-align: left; color: #14210c; transition: background-color .28s var(--lp-e, ease); }
    .lp-row:hover { background: #f4f8ef; }
    .lp-row[aria-selected="true"] { background: #eef5e6; }
    .lp-row .rt { min-width: 0; flex: 1; }
    .lp-row .rt b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; }
    .lp-row .rt small { display: block; font-size: .74rem; color: #6b7280; line-height: 1.35; }
    .lp-row .rn { flex: none; font-size: .7rem; font-weight: 800; padding: .18rem .5rem; border-radius: 999px; color: #3d6823; background: #eef5e6; white-space: nowrap; }
    .lp-row .rn.is-hy { color: #92400e; background: #fef3c7; }
    .lp-row .ri { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.1rem; background: #f6f8f3; }
    .lp-row.is-typed .rt b { color: var(--lp-gd, #2d5016); }
    .lp-snone { padding: 1.4rem 1rem; text-align: center; font-size: .9rem; color: #6b7280; }
    .lp-sfoot { flex: none; display: flex; align-items: center; gap: .8rem; padding: .8rem 1rem calc(.9rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-gray-100); }
    @media (min-width: 640px) { .lp-sfoot { padding-bottom: .9rem; } }
    .lp-sfoot .btn { flex: none; min-height: 2.9rem; padding-left: 1.1rem; padding-right: 1.1rem; }
    .lp-pickline { flex: 1; min-width: 0; }
    .lp-pickline b { display: block; font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .lp-pickline span { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .lp-check { flex: none; width: 1.5rem; height: 1.5rem; border-radius: .45rem; display: grid; place-items: center; border: 2px solid var(--color-gray-300); color: #fff; transition: background-color .28s ease, border-color .28s ease; }
    .lp-check svg { width: .85rem; height: .85rem; opacity: 0; }
    .lp-row[aria-checked="true"] .lp-check { background: #4a7c2a; border-color: #4a7c2a; }
    .lp-row[aria-checked="true"] .lp-check svg { opacity: 1; }
    .lp-sbody { padding: 0 1.15rem 1rem; overflow-y: auto; }

    /* The date sheet. */
    .lp-quick { flex: none; display: flex; gap: .4rem; overflow-x: auto; padding: 0 1rem .75rem; scrollbar-width: none; }
    .lp-quick::-webkit-scrollbar { display: none; }
    .lp-qc { flex: none; min-height: 2.3rem; padding: .35rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        transition: background-color .28s ease, color .28s ease, border-color .28s ease; }
    .lp-qc.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    .lp-cal { padding: 0 1rem; overflow: hidden; }
    .lp-mon { display: flex; align-items: center; justify-content: space-between; padding: 0 0 .5rem; }
    .lp-mon b { font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: var(--color-gray-900); }
    .lp-mon button { width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); transition: background-color .28s ease, opacity .28s ease; }
    .lp-mon button:hover:not(:disabled) { background: #e4efd4; }
    .lp-mon button:disabled { opacity: .3; cursor: default; }
    .lp-mon svg { width: 1.1rem; height: 1.1rem; }
    .lp-wk { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); padding-bottom: .3rem; text-align: center; font-size: .7rem; font-weight: 800; color: var(--color-gray-400); }
    .lp-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .2rem; padding-bottom: .4rem; }
    .lp-days.is-l { animation: lpSlideL .3s cubic-bezier(.22,1,.36,1); }
    .lp-days.is-r { animation: lpSlideR .3s cubic-bezier(.22,1,.36,1); }
    @keyframes lpSlideL { from { opacity: 0; transform: translateX(-26px); } }
    @keyframes lpSlideR { from { opacity: 0; transform: translateX(26px); } }
    .lp-d { position: relative; width: 100%; aspect-ratio: 1; max-height: 3.1rem; border-radius: 999px; display: grid; place-items: center; font-size: 1rem; font-weight: 700; color: var(--color-gray-800); font-variant-numeric: tabular-nums;
        transition: background-color .28s ease, color .28s ease, transform .28s ease; }
    .lp-d:hover:not(:disabled):not(.is-sel) { background: #eef5e6; }
    .lp-d.is-today { box-shadow: inset 0 0 0 2px #86b556; }
    .lp-d.is-ready::after { content: ''; position: absolute; bottom: .25rem; width: .3rem; height: .3rem; border-radius: 999px; background: #86b556; }
    .lp-d.is-sel { background: #4a7c2a; color: #fff; font-weight: 900; animation: lpSel .34s cubic-bezier(.22,1,.36,1); }
    .lp-d.is-sel::after { background: #fff; }
    @keyframes lpSel { 40% { transform: scale(1.16); } }
    .lp-d:disabled { color: var(--color-gray-300); cursor: default; }
    .lp-legend { display: flex; align-items: center; gap: .4rem; padding: 0 1rem .5rem; font-size: .72rem; color: var(--color-gray-500); }
    .lp-legend i { width: .4rem; height: .4rem; border-radius: 999px; background: #86b556; }

    /* The port sheet. */
    .lp-pmode { display: grid; gap: .5rem; padding: 0 1.15rem; }
    .lp-pmode .lp-choice { padding: .7rem .8rem; }
    .lp-plist { margin-top: .6rem; display: grid; gap: .1rem; max-height: 16rem; overflow-y: auto; border-radius: .9rem; border: 1px solid var(--color-gray-200); }
    .lp-done { padding: 1.4rem 1.15rem; text-align: center; }
    .lp-done b { display: block; font-family: var(--font-heading); font-size: 1.15rem; color: var(--color-gray-900); }
    .lp-done p { margin-top: .3rem; font-size: .86rem; color: var(--color-gray-500); }
    .lp-done .ok { width: 3.2rem; height: 3.2rem; margin: 0 auto .7rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #4a7c2a; animation: lpSel .5s cubic-bezier(.22,1,.36,1); }
    .lp-done .ok svg { width: 1.6rem; height: 1.6rem; }
    html.lp-lock { overflow: hidden; }

    /* ---- the plan, full screen ---- */
    .lp-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; opacity: 0; transform: translateY(14px);
        transition: opacity .3s cubic-bezier(.22,1,.36,1), transform .3s cubic-bezier(.22,1,.36,1); }
    .lp-view.is-on { opacity: 1; transform: none; }
    .lp-vbar { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: .5rem; padding: .6rem .8rem; padding-top: max(.6rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid var(--color-gray-200); }
    .lp-vname { flex: 1; min-width: 0; display: flex; align-items: center; gap: .35rem; padding: .35rem .5rem; border-radius: .7rem; text-align: left; transition: background-color .28s ease; }
    .lp-vname:hover { background: var(--color-gray-100); }
    .lp-vname b { min-width: 0; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-vname svg { flex: none; width: .9rem; height: .9rem; color: var(--color-gray-400); }
    .lp-vsend { flex: none; display: none; }
    @media (min-width: 720px) { .lp-vsend { display: inline-flex; align-items: center; gap: .35rem; } }
    .lp-vbody { max-width: 62rem; margin: 0 auto; padding: 1rem 1rem calc(2.5rem + env(safe-area-inset-bottom)); display: grid; gap: .9rem; }
    .lp-vbody > * { min-width: 0; }
    .lp-hero { position: relative; overflow: hidden; padding: 1.1rem 1.15rem; border-radius: 1.3rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #6b9f3d 0%, #3d6823 45%, #24410f 100%); }
    .lp-hero::after { content: ''; position: absolute; right: -2rem; bottom: -2.5rem; width: 10rem; height: 10rem; border-radius: 999px; background: rgb(255 255 255 / .06); }
    .lp-hero-top { display: flex; align-items: center; gap: .8rem; }
    .lp-hero-ico { flex: none; width: 3.2rem; height: 3.2rem; border-radius: 1rem; display: grid; place-items: center; font-size: 1.7rem; background: rgb(255 255 255 / .14); }
    .lp-hero small { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; opacity: .8; }
    .lp-hero h2 { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; line-height: 1.2; }
    .lp-hero-chips { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .8rem; }
    .lp-hero-chips span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: rgb(255 255 255 / .16); }
    .lp-hero-chips span.is-ok { background: #d9f99d; color: #1a2e05; }
    .lp-hero-chips span.is-warn { background: #fde68a; color: #78350f; }
    .lp-seg { position: sticky; top: 3.4rem; z-index: 4; display: flex; padding: .25rem; border-radius: 1rem; background: rgb(255 255 255 / .9); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid var(--color-gray-200); }
    .lp-seg button { position: relative; z-index: 1; flex: 1 1 0; min-height: 2.6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-500); transition: color .28s ease; }
    .lp-seg button.is-on { color: #fff; }
    .lp-seg i { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .25rem); border-radius: .8rem; background: var(--lp-g, #4a7c2a); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .lp-seg.is-lot i { transform: translateX(100%); }
    .lp-pane { display: grid; gap: .9rem; min-width: 0; }
    .lp-pane > * { min-width: 0; }
    .lp-pane.is-in { animation: lpIn .34s cubic-bezier(.22,1,.36,1) both; }
    .lp-pane[hidden] { display: none; }
    .lp-c { min-width: 0; padding: 1rem 1.05rem; border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lp-c > h3 { display: flex; align-items: center; gap: .45rem; margin-bottom: .7rem; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-c > h3 img { width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; }
    .lp-c > h3 em { margin-left: auto; font-style: normal; font-size: .7rem; font-weight: 800; padding: .15rem .5rem; border-radius: 999px; color: var(--color-gray-600); background: var(--color-gray-100); }
    .lp-p { font-size: .9rem; line-height: 1.6; color: var(--color-gray-700); }
    .lp-an { display: grid; gap: .55rem; margin-top: .8rem; }
    @media (min-width: 720px) { .lp-an { grid-template-columns: 1fr 1fr; } }
    .lp-an div { display: flex; gap: .6rem; padding: .7rem .75rem; border-radius: .95rem; background: var(--color-gray-50); }
    .lp-an div > span:first-child { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; background: #fff; }
    .lp-an div > span + span { flex: 1; min-width: 0; }
    .lp-an b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .lp-an p { margin-top: .1rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .lp-warnc { border-color: #fde68a; background: #fffdf5; }
    .lp-warnc li { display: flex; gap: .5rem; padding: .45rem 0; font-size: .84rem; line-height: 1.5; color: #78350f; border-top: 1px solid #fef3c7; }
    .lp-warnc li:first-child { border-top: 0; }
    .lp-warnc li i { flex: none; width: .55rem; height: .55rem; margin-top: .45rem; border-radius: 999px; background: var(--lc); }
    .lp-warnc .lp-p { color: #78350f; margin-top: .4rem; }

    /* Timeline across the lots. */
    .lp-gantt { position: relative; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin; border-radius: .9rem; border: 1px solid var(--color-gray-100); background: linear-gradient(90deg, #fff 0 7rem, transparent 7rem); }
    .lp-g-in { position: relative; }
    .lp-g-head { position: relative; height: 2rem; margin-left: 7rem; border-bottom: 1px solid var(--color-gray-100); }
    .lp-g-head span { position: absolute; top: .45rem; font-size: .64rem; font-weight: 800; color: var(--color-gray-500); white-space: nowrap; padding-left: .25rem; border-left: 1px solid var(--color-gray-200); height: 1.55rem; }
    .lp-g-row { position: relative; display: flex; border-bottom: 1px solid var(--color-gray-100); }
    .lp-g-row:last-child { border-bottom: 0; }
    .lp-g-lab { position: sticky; left: 0; z-index: 2; flex: none; width: 7rem; padding: .5rem .6rem; background: #fff; border-right: 1px solid var(--color-gray-100); }
    .lp-g-lab b { display: flex; align-items: center; gap: .3rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-g-lab b i { flex: none; width: .55rem; height: .55rem; border-radius: 999px; background: var(--lc); }
    .lp-g-lab small { display: block; font-size: .64rem; color: var(--color-gray-500); white-space: nowrap; }
    .lp-g-track { position: relative; flex: none; }
    .lp-g-bar { position: absolute; height: 1.15rem; border-radius: .45rem; display: flex; align-items: center; padding: 0 .3rem; font-size: .6rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden;
        transform-origin: left; transform: scaleX(0); transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .lp-view.is-drawn .lp-g-bar { transform: none; }
    .lp-g-bar.is-machine { background: #4a7c2a; }
    .lp-g-bar.is-manual { background: #d97706; }
    .lp-g-bar.is-seed { background: #0284c7; }
    .lp-g-bar.is-late { box-shadow: inset 0 0 0 2px #ef4444; }
    .lp-g-bar { cursor: pointer; }
    .lp-g-plant { position: absolute; top: .2rem; bottom: .2rem; width: 2px; background: #7c3aed; }
    .lp-g-plant::before { content: '🌱'; position: absolute; top: -.2rem; left: -.45rem; font-size: .8rem; }
    .lp-g-today { position: absolute; top: 0; bottom: 0; width: 2px; background: #ef4444; opacity: .55; z-index: 1; }
    .lp-g-weekend { position: absolute; top: 0; bottom: 0; background: rgb(0 0 0 / .025); }
    .lp-glegend { display: flex; flex-wrap: wrap; gap: .4rem .9rem; margin-top: .6rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-500); }
    .lp-glegend span { display: inline-flex; align-items: center; gap: .3rem; }
    .lp-glegend i { width: 1rem; height: .5rem; border-radius: .25rem; }

    /* Crew calendar. */
    .lp-week { margin-top: .7rem; }
    .lp-week:first-child { margin-top: 0; }
    .lp-week > b { display: block; margin-bottom: .35rem; font-size: .68rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-400); }
    .lp-day { display: flex; gap: .6rem; padding: .45rem 0; border-top: 1px solid var(--color-gray-100); }
    .lp-dbox { flex: none; width: 2.7rem; text-align: center; border-radius: .7rem; padding: .2rem 0; background: var(--color-gray-50); }
    .lp-dbox small { display: block; font-size: .58rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .lp-dbox b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.2; }
    .lp-day.is-plant .lp-dbox { background: #ede9fe; }
    .lp-dchips { display: flex; flex-wrap: wrap; align-content: center; gap: .3rem; min-width: 0; }
    .lp-cc { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lp-cc i { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: var(--lc); }
    .lp-cc em { font-style: normal; font-weight: 800; color: var(--color-gray-500); }
    .lp-cc.is-plant { background: #f5f3ff; border-color: #ddd6fe; color: #5b21b6; }
    .lp-showall { display: block; margin: .7rem auto 0; font-size: .8rem; font-weight: 800; color: var(--lp-g, #4a7c2a); }

    /* Seed card in the plan. */
    .lp-tl { margin-top: .7rem; display: grid; gap: .1rem; }
    .lp-ev { display: flex; gap: .6rem; align-items: flex-start; padding: .45rem 0; border-top: 1px solid var(--color-gray-100); }
    .lp-ev:first-child { border-top: 0; }
    .lp-ev > span { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1rem; background: #e0f2fe; }
    .lp-ev b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .lp-ev small { display: block; font-size: .74rem; color: var(--color-gray-500); line-height: 1.45; }
    .lp-ev em { font-style: normal; font-weight: 800; color: #0369a1; }
    .lp-steps-ol { margin-top: .6rem; display: grid; gap: .3rem; padding-left: 1.1rem; list-style: decimal; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); }
    details.lp-more-d summary { cursor: pointer; margin-top: .6rem; font-size: .8rem; font-weight: 800; color: var(--lp-g, #4a7c2a); list-style: none; }
    details.lp-more-d summary::-webkit-details-marker { display: none; }

    /* Weather card. */
    .lp-fc { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .3rem; scrollbar-width: none; }
    .lp-fc::-webkit-scrollbar { display: none; }
    .lp-fd { flex: none; width: 3.6rem; padding: .45rem .2rem; border-radius: .9rem; text-align: center; background: var(--color-gray-50); }
    .lp-fd small { display: block; font-size: .6rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; }
    .lp-fd span { display: block; font-size: 1.2rem; line-height: 1.5; }
    .lp-fd b { display: block; font-size: .74rem; color: var(--color-gray-900); }
    .lp-fd i { display: block; font-style: normal; font-size: .64rem; font-weight: 800; color: #0369a1; }
    .lp-wx { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .7rem; }
    .lp-wx div { padding: .6rem .5rem; border-radius: .9rem; text-align: center; background: #f0f9ff; }
    .lp-wx b { display: block; font-family: var(--font-heading); font-size: 1.1rem; color: #0c4a6e; }
    .lp-wx small { display: block; font-size: .66rem; font-weight: 700; color: #0369a1; line-height: 1.3; }

    .lp-risk { display: flex; gap: .6rem; padding: .55rem 0; border-top: 1px solid var(--color-gray-100); }
    .lp-risk:first-of-type { border-top: 0; }
    .lp-risk > span { flex: none; margin-top: .15rem; padding: .12rem .45rem; height: fit-content; border-radius: 999px; font-size: .62rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
    .lp-risk > span.is-low { background: #dcfce7; color: #166534; }
    .lp-risk > span.is-medium { background: #fef3c7; color: #92400e; }
    .lp-risk > span.is-high { background: #fee2e2; color: #991b1b; }
    .lp-risk b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .lp-risk p { font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .lp-risk small { display: block; font-size: .72rem; font-weight: 700; color: var(--color-gray-400); margin-top: .1rem; }
    .lp-rules li { display: flex; gap: .5rem; padding: .4rem 0; font-size: .84rem; line-height: 1.5; color: var(--color-gray-700); border-top: 1px solid var(--color-gray-100); }
    .lp-rules li:first-child { border-top: 0; }
    .lp-rules li span { flex: none; }
    .lp-fineprint { font-size: .74rem; line-height: 1.55; color: var(--color-gray-500); }

    /* Per lot. */
    .lp-lotpick { display: flex; gap: .4rem; overflow-x: auto; padding: .1rem .1rem .3rem; scrollbar-width: none; }
    .lp-lotpick::-webkit-scrollbar { display: none; }
    .lp-lotpick button { flex: none; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.5rem; padding: .35rem .85rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s ease, color .28s ease, border-color .28s ease; }
    .lp-lotpick button i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc); }
    .lp-lotpick button.is-on { color: #fff; background: #1f2937; border-color: #1f2937; }
    .lp-lothead { display: flex; align-items: center; gap: .8rem; }
    .lp-lothead > i { flex: none; width: 3rem; height: 3rem; border-radius: 1rem; display: grid; place-items: center; font-style: normal; font-size: 1.4rem; background: color-mix(in srgb, var(--lc) 16%, #fff); }
    .lp-lothead b { display: block; font-family: var(--font-heading); font-size: 1.1rem; color: var(--color-gray-900); }
    .lp-lothead small { display: block; font-size: .8rem; color: var(--color-gray-500); }
    .lp-moved { margin-top: .6rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .8rem; line-height: 1.45; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; }
    .lp-list { display: grid; gap: .55rem; }
    .lp-s { display: flex; gap: .7rem; padding: .8rem .85rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: lpPop .34s cubic-bezier(.22,1,.36,1) both; }
    .lp-s.is-anee { border-color: #fcd34d; background: linear-gradient(180deg, #fffdf5, #fff); }
    .lp-s.is-past { opacity: .62; }
    .lp-cal1 { flex: none; width: 3.1rem; overflow: hidden; border-radius: .85rem; text-align: center; background: var(--color-white); border: 1px solid var(--color-gray-200); height: fit-content; }
    .lp-cal1 i { display: block; padding: .12rem; font-style: normal; font-size: .56rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #fff; background: #4a7c2a; }
    .lp-s.is-anee .lp-cal1 i { background: #d97706; }
    .lp-cal1 b { display: block; padding: .1rem 0 .2rem; font-family: var(--font-heading); font-size: 1.2rem; color: var(--color-gray-900); }
    .lp-cal1 small { display: block; margin-top: -.25rem; padding-bottom: .2rem; font-size: .58rem; font-weight: 800; color: var(--color-gray-400); }
    .lp-sb { min-width: 0; flex: 1; }
    .lp-sb > b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
    .lp-tg { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; }
    .lp-tg.is-philrice { background: #e4efd4; color: #2d5016; }
    .lp-tg.is-irri { background: #e0f2fe; color: #075985; }
    .lp-tg.is-extension { background: #f1f5f9; color: #334155; }
    .lp-tg.is-anee { background: #fef3c7; color: #92400e; }
    .lp-tg.is-reason { background: var(--color-gray-100); color: var(--color-gray-600); }
    .lp-tg.is-late { background: #fee2e2; color: #991b1b; }
    .lp-tg.is-past { background: var(--color-gray-100); color: var(--color-gray-500); }
    .lp-when { margin-top: .35rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); line-height: 1.45; }
    .lp-how { margin-top: .35rem; font-size: .82rem; line-height: 1.55; color: var(--color-gray-700); }
    .lp-why { margin-top: .4rem; padding: .45rem .6rem; border-radius: .7rem; font-size: .78rem; line-height: 1.5; color: #78350f; background: #fffbeb; }
    .lp-why b { color: #92400e; }
    .lp-note { margin-top: .4rem; display: flex; gap: .45rem; padding: .45rem .6rem; border-radius: .7rem; font-size: .78rem; line-height: 1.5; color: #374151; background: var(--color-gray-50); }
    .lp-note img { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; object-fit: cover; }
    .lp-ts { display: flex; gap: .6rem; align-items: center; padding: .55rem .8rem; border-radius: 1rem; background: #f0f9ff; border: 1px solid #e0f2fe; animation: lpPop .34s cubic-bezier(.22,1,.36,1) both; }
    .lp-ts > span { flex: none; font-size: 1.1rem; }
    .lp-ts b { display: block; font-size: .84rem; color: #0c4a6e; }
    .lp-ts small { display: block; font-size: .74rem; line-height: 1.4; color: #0369a1; }
    .lp-acts { display: grid; gap: .5rem; }
    @media (min-width: 560px) { .lp-acts { grid-template-columns: 2fr 1fr 1fr; } }
    .lp-acts .btn { min-height: 3rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; }
    .lp-acts .btn svg { width: 1rem; height: 1rem; }
    .lp-acts .lp-del:hover { color: #b91c1c; }

    @media (max-width: 400px) {
        .lp-wiz { padding: .95rem .85rem .9rem; }
        .lp-q { font-size: 1.1rem; }
        .lp-hours { gap: .35rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lp-step.is-on, .lp-lot, .lp-card, .lp-s, .lp-ts, .lp-pane.is-in, .lp-run, .lp-tag.is-bump .ti, .lp-days.is-l, .lp-days.is-r, .lp-d.is-sel, .lp-done .ok { animation: none !important; }
        .lp-sbox, .lp-sheet::backdrop, .lp-sheet.is-closing .lp-sbox, .lp-sheet.is-closing::backdrop { animation: none !important; }
        .lp-bar i, .lp-choice, .lp-tag, .lp-chipb, .lp-view, .lp-g-bar, .lp-seg i, .lp-choice .ck, .lp-qg > b i, .lp-card { transition: none !important; }
        .lp-g-bar { transform: none; }
    }

    /* Night. */
    html.dark .lp-tabs, html.dark .lp-wiz, html.dark .lp-c, html.dark .lp-card, html.dark .lp-s, html.dark .lp-find, html.dark .lp-empty { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-q, html.dark .lp-choice .ct b, html.dark .lp-c > h3, html.dark .lp-card-t b, html.dark .lp-sb > b, html.dark .lp-rev .rt b, html.dark .lp-lothead b, html.dark .lp-empty b { color: #e8efe1; }
    html.dark .lp-choice, html.dark .lp-chipb, html.dark .lp-rev button, html.dark .lp-lotpick button { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-choice.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .lp-choice .ce, html.dark .lp-hour, html.dark .lp-an div, html.dark .lp-dbox, html.dark .lp-cc, html.dark .lp-fd, html.dark .lp-note, html.dark .lp-lot, html.dark .lp-card-meta span { background: #1c2416; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-chipb.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .lp-tag { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .lp-tag .ti, html.dark .lp-an div > span:first-child { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-tag .tc { background: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-quote b { color: #e8efe1; }
    html.dark .lp-hint { background: #1a2414; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-hint.is-warn { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    html.dark .lp-seed { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-seed-h b, html.dark .lp-hour b, html.dark .lp-an b, html.dark .lp-dbox b, html.dark .lp-ev b, html.dark .lp-risk b, html.dark .lp-fd b { color: #e8efe1; }
    html.dark .lp-p, html.dark .lp-how, html.dark .lp-rules li, html.dark .lp-an p, html.dark .lp-seed p, html.dark .lp-risk p { color: #b7c2ad; }
    html.dark .lp-view { background: #0d110a; }
    html.dark .lp-vbar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .lp-vname b { color: #e8efe1; }
    html.dark .lp-seg { background: rgb(21 27 18 / .9); border-color: #2b3a1c; }
    html.dark .lp-gantt { background: linear-gradient(90deg, #151b12 0 7rem, transparent 7rem); border-color: #2b3a1c; }
    html.dark .lp-g-lab { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-g-lab b { color: #e8efe1; }
    html.dark .lp-g-row, html.dark .lp-g-head, html.dark .lp-day, html.dark .lp-ev, html.dark .lp-risk, html.dark .lp-rules li, html.dark .lp-qg { border-color: #222b1a; }
    html.dark .lp-cal1 { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-cal1 b { color: #e8efe1; }
    html.dark .lp-s.is-anee { background: linear-gradient(180deg, #2a210a, #151b12); border-color: #4d3a0d; }
    html.dark .lp-why { background: #2a210a; color: #fcd34d; }
    html.dark .lp-ts { background: #0c2231; border-color: #12364d; }
    html.dark .lp-ts b { color: #bae6fd; }
    html.dark .lp-wx div { background: #0c2231; }
    html.dark .lp-wx b { color: #bae6fd; }
    html.dark .lp-warnc { background: #2a210a; border-color: #4d3a0d; }
    html.dark .lp-warnc li, html.dark .lp-warnc .lp-p { color: #fcd34d; border-color: #3a2c0a; }
    html.dark .lp-sbox { background: #151b12; }
    html.dark .lp-shead h2, html.dark .lp-row, html.dark .lp-mon b, html.dark .lp-pickline b, html.dark .lp-done b { color: #e8efe1; }
    html.dark .lp-sgh { background: #151b12; color: #a5c97e; }
    html.dark .lp-x { background: #1c2616; color: #d5e3c5; }
    html.dark .lp-sfind { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lp-sfind input, html.dark .lp-find input { color: #e8efe1; }
    html.dark .lp-row:hover, html.dark .lp-row[aria-selected="true"] { background: #1c2616; }
    html.dark .lp-row .ri, html.dark .lp-mon button, html.dark .lp-qc { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-d { color: #d5e3c5; }
    html.dark .lp-d:hover:not(:disabled):not(.is-sel) { background: #22301a; }
    html.dark .lp-d:disabled { color: #3d4a33; }
    html.dark .lp-own button { background: #1c2616; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-own button.is-x { color: #93a684; }
    html.dark .lp-row .rn { background: #22301a; color: #cfe6b8; }
    html.dark .lp-row .rn.is-hy { background: #3a2a0a; color: #fcd34d; }
    html.dark .lp-badge.is-co, html.dark .lp-tg.is-philrice { background: #22301a; color: #cfe6b8; }
    html.dark .lp-badge.is-gen, html.dark .lp-tg.is-anee { background: #3a2a0a; color: #fcd34d; }
    html.dark .lp-tg.is-irri { background: #0c2231; color: #bae6fd; }
    html.dark .lp-tg.is-extension, html.dark .lp-tg.is-reason, html.dark .lp-badge.is-none { background: #1c2416; color: #b7c2ad; }
    html.dark .lp-stepper button { background: #22301a; color: #cfe6b8; }
    html.dark .lp-rev button:hover { background: #1c2616; }
    html.dark .lp-lothead > i { background: color-mix(in srgb, var(--lc) 22%, #151b12); }
    html.dark .lp-quote.is-off { background: #2a1a0a; border-color: #4d2f0d; color: #fdba74; }
    html.dark .lp-hour, html.dark .lp-wx div { color: #d5e3c5; }
</style>

@php
    $lpCrops = collect($crops)->map(fn ($c) => [$c[0], $c[1], $c[2], $c[3], null])->all();
    $lpCropJs = collect($crops)->map(fn ($c) => ['local' => $c[0], 'en' => $c[1], 'icon' => $c[2]])->all();
@endphp

<div class="lp" id="lpPage">
    <div class="lp-tabs" role="tablist">
        <button type="button" class="lp-tab is-on" id="lpTabPlan" role="tab" aria-selected="true">New plan</button>
        <button type="button" class="lp-tab" id="lpTabList" role="tab" aria-selected="false">Your plans</button>
    </div>

    <section id="lpPlanPane">
        <div class="lp-quote" id="lpQuote" hidden></div>

        <div class="lp-wiz" id="lpWiz">
            <div class="lp-head"><small id="lpStepName">Crop</small><span id="lpStepNo">Step 1 of 10</span></div>
            <div class="lp-bar" aria-hidden="true"><i id="lpBar"></i></div>

            {{-- 1. The crop --}}
            <section class="lp-step is-on" data-step="0">
                <p class="lp-q">Which crop are you preparing for?</p>
                <p class="lp-sub">Tap to change it. Every step is dated back from its planting day.</p>
                @include('public.site.partials.crop-pick', ['id' => 'lpCropSheet', 'crops' => $lpCrops, 'current' => 'rice', 'title' => 'Which crop?', 'noun' => null])
            </section>

            {{-- 2. How it goes in --}}
            <section class="lp-step" data-step="1">
                <p class="lp-q" id="lpMethodQ">How will you plant it?</p>
                <p class="lp-sub" id="lpMethodSub">Each way has its own schedule.</p>
                <div class="lp-choices" id="lpMethods"></div>
            </section>

            {{-- 3. The variety, and how its seed is prepared --}}
            <section class="lp-step" data-step="2">
                <p class="lp-q">Which variety?</p>
                <p class="lp-sub">Its seed preparation depends on it. Not decided yet? That is fine.</p>
                <button type="button" class="lp-tag is-none" id="lpVarTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true">🌾</span>
                    <span class="tt"><b id="lpVarName">I don't know yet</b><small id="lpVarLine">Tap to pick from the list or type one</small></span>
                    <span class="tc">Change<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                </button>
                <div id="lpSeedType" hidden>
                    <span class="lp-label">Is the seed inbred or hybrid?</span>
                    <div class="lp-chips" id="lpSeedTypes">
                        <button type="button" class="lp-chipb" data-type="inbred">Inbred</button>
                        <button type="button" class="lp-chipb" data-type="hybrid">Hybrid</button>
                        <button type="button" class="lp-chipb" data-type="">Not sure</button>
                    </div>
                </div>
                <div class="lp-seed" id="lpSeed" hidden></div>
            </section>

            {{-- 4. The planting day --}}
            <section class="lp-step" data-step="3">
                <p class="lp-q" id="lpDateQ">When will you plant?</p>
                <p class="lp-sub">Everything is dated back from this day. Lots can have their own day on the next step.</p>
                <button type="button" class="lp-tag is-big is-none" id="lpDateTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true"><i id="lpDateMon">Day</i><em id="lpDateDay">?</em></span>
                    <span class="tt"><b id="lpDateText">Pick the day</b><small id="lpDateLine">Opens a calendar</small></span>
                    <span class="tc">Change<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                </button>
                <div class="lp-hint" id="lpDateHint" hidden></div>
            </section>

            {{-- 5. The lots --}}
            <section class="lp-step" data-step="4">
                <p class="lp-q">Which lots are you preparing?</p>
                <p class="lp-sub">Name each one and give its size. Anee plans every lot and shares the crew between them.</p>
                <div class="lp-lots" id="lpLots"></div>
                <div class="lp-lot-acts">
                    <button type="button" class="btn btn-white" id="lpLotAdd"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Add a lot</button>
                    <button type="button" class="btn btn-white" id="lpLotSeason"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>From your seasons</button>
                </div>
                <p class="lp-total" id="lpLotTotal"></p>
            </section>

            {{-- 6. The crew --}}
            <section class="lp-step" data-step="5">
                <p class="lp-q">Who will do the work?</p>
                <p class="lp-sub">Anee spreads the plowing, harrowing and hand work across your lots so nobody is in two places at once.</p>
                <span class="lp-label">Workers for land preparation</span>
                <div class="lp-stepper">
                    <button type="button" data-step-w="-1" aria-label="One less">−</button>
                    <input type="text" inputmode="numeric" id="lpWorkers" class="form-input" value="3" aria-label="Workers">
                    <button type="button" data-step-w="1" aria-label="One more">+</button>
                    <span>people</span>
                </div>
                <span class="lp-label">What pulls the plow?</span>
                <div class="lp-choices is-2" id="lpEquip"></div>
                <div id="lpMachWrap">
                    <span class="lp-label" id="lpMachLabel">How many machines?</span>
                    <div class="lp-chips" id="lpMach"></div>
                </div>
                <span class="lp-label">Work days a week</span>
                <div class="lp-chips" id="lpDpw">
                    <button type="button" class="lp-chipb" data-dpw="5">5 days<small>Weekends off</small></button>
                    <button type="button" class="lp-chipb is-on" data-dpw="6">6 days<small>Sundays off</small></button>
                    <button type="button" class="lp-chipb" data-dpw="7">Every day</button>
                </div>
                <span class="lp-label">If the crew cannot finish a lot in time</span>
                <div class="lp-choices" id="lpStagger">
                    <button type="button" class="lp-choice is-on" data-stagger="0"><span class="ce">📅</span><span class="ct"><b>Keep my planting days</b><small>Anee shows what runs late and how to fix it</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <button type="button" class="lp-choice" data-stagger="1"><span class="ce">🔀</span><span class="ct"><b>Let Anee stagger the lots</b><small>She moves a planting day later only as much as the crew needs</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                </div>
            </section>

            {{-- 7. The soil and the water --}}
            <section class="lp-step" data-step="6">
                <p class="lp-q">Tell Anee about the soil and water</p>
                <p class="lp-sub">By what you see and feel. Skip what you do not know.</p>
                <p class="lp-count" id="lpSoilCount"></p>
                <div id="lpSoil"></div>
                <span class="lp-label">Known pH <small>(from a soil test, optional)</small></span>
                <input type="text" inputmode="decimal" id="lpPh" class="form-input" maxlength="4" placeholder="e.g. 5.4" style="max-width:8rem">
            </section>

            {{-- 8. The field's history --}}
            <section class="lp-step" data-step="7">
                <p class="lp-q">What happened on this field before?</p>
                <p class="lp-sub">The last crop, floods, weeds and pests change what the land needs.</p>
                <p class="lp-count" id="lpHistCount"></p>
                <div id="lpHist"></div>
            </section>

            {{-- 9. The place --}}
            <section class="lp-step" data-step="8">
                <p class="lp-q">Where are the lots?</p>
                <p class="lp-sub">Anee reads the forecast, the ENSO outlook and ten years of the area's weather there.</p>
                <div id="lpPlacePh">
                    <span class="lp-label">Province</span>
                    <button type="button" class="lp-tag is-none" id="lpProv" aria-haspopup="dialog"><span class="ti" aria-hidden="true">🗺️</span><span class="tt"><b>Choose the province</b></span><span class="tc">Pick<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span></button>
                    <span class="lp-label">Town or city</span>
                    <button type="button" class="lp-tag is-none" id="lpTown" aria-haspopup="dialog" disabled><span class="ti" aria-hidden="true">📍</span><span class="tt"><b>Pick the province first</b></span><span class="tc">Pick<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span></button>
                </div>
                <div id="lpPlaceAny" hidden>
                    <span class="lp-label">Town and region</span>
                    <input type="text" id="lpPlaceText" class="form-input" maxlength="160" placeholder="{{ \App\Support\Region::get('exampleLocation') }}">
                </div>
                <span class="lp-label">Anything else Anee should know? <small>(optional)</small></span>
                <textarea id="lpNotes" class="form-textarea" rows="2" maxlength="500" placeholder="e.g. the canal water comes late every year, Lot B is the low side"></textarea>
            </section>

            {{-- 10. Review and run --}}
            <section class="lp-step" data-step="9">
                <p class="lp-q">Ready for Anee</p>
                <p class="lp-sub">Tap a row to change it.</p>
                <div class="lp-rev" id="lpReview"></div>
                <div class="lp-err" id="lpErr" role="alert" hidden><b>The plan did not finish</b><p id="lpErrText"></p></div>
                <button type="button" class="lp-run" id="lpRun"><span id="lpRunFace"></span><span id="lpRunSays">Make my plan</span></button>
                <p class="lp-fine" id="lpRunFine"></p>
            </section>

            <div class="lp-nav">
                <button type="button" class="btn btn-white" id="lpBack" disabled>Back</button>
                <button type="button" class="btn btn-primary" id="lpNext">Next</button>
            </div>
            @include('sm.partials.anee-wait')
        </div>
    </section>

    <section id="lpListPane" hidden>
        <label class="lp-find">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search your plans</span>
            <input type="search" id="lpFind" placeholder="Search your plans" autocomplete="off">
        </label>
        <div class="lp-cards" id="lpCards"></div>
        <div class="lp-empty" id="lpEmpty" hidden><b>No plans yet</b><p>Every plan Anee makes is saved here.</p></div>
        <button type="button" class="btn btn-white lp-more" id="lpMore" hidden>Show more</button>
    </section>
</div>

{{-- The plan, full screen. --}}
<div class="lp-view" id="lpView" hidden role="dialog" aria-modal="true" aria-labelledby="lpViewTitle" tabindex="-1">
    <div class="lp-vbar">
        <button type="button" class="lp-x" id="lpViewX" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        <button type="button" class="lp-vname" id="lpViewName" aria-label="Rename this plan"><b id="lpViewTitle">Land prep plan</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg></button>
        <button type="button" class="btn btn-primary lp-vsend" id="lpViewSend"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="width:1rem;height:1rem" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>Send to Protocol Builder</button>
    </div>
    <div class="lp-vbody" id="lpViewBody"></div>
</div>

{{-- The variety. --}}
<dialog class="lp-sheet is-tall" id="lpVarSheet" aria-labelledby="lpVarTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpVarTitle">Which variety?</h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="lp-sfind">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search a variety</span>
            <input type="search" id="lpVarQ" placeholder="Search, like Rc 222, SL-8H or Mestiso 20" autocomplete="off" maxlength="60">
        </label>
        <div class="lp-slist" id="lpVarList" role="listbox" aria-label="Varieties"></div>
    </div>
</dialog>

{{-- The planting day. --}}
<dialog class="lp-sheet is-small" id="lpDateSheet" aria-labelledby="lpDateTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpDateTitle">Planting day</h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lp-quick" id="lpQuick"></div>
        <div class="lp-cal">
            <div class="lp-mon">
                <button type="button" id="lpPrevM" aria-label="Earlier month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                <b id="lpMonName" aria-live="polite"></b>
                <button type="button" id="lpNextM" aria-label="Later month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
            </div>
            <div class="lp-wk" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
            <div class="lp-days" id="lpDays"></div>
        </div>
        <p class="lp-legend" id="lpDateLegend"><i></i>Days with time for every step from today</p>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b id="lpPickDate">Tap a day</b><span id="lpPickLine">Days before today are off</span></div>
            <button type="button" class="btn btn-primary" id="lpDateUse" disabled>Use this day</button>
        </div>
    </div>
</dialog>

{{-- A list to pick from: province, town. --}}
<dialog class="lp-sheet is-tall" id="lpPickSheet" aria-labelledby="lpPickTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpPickTitle">Choose</h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="lp-sfind">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search</span>
            <input type="search" id="lpPickQ" placeholder="Search" autocomplete="off">
        </label>
        <div class="lp-slist" id="lpPickList" role="listbox"></div>
    </div>
</dialog>

{{-- Lots from the farmer's seasons. --}}
<dialog class="lp-sheet is-tall" id="lpSeasonSheet" aria-labelledby="lpSeasonTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpSeasonTitle">Lots from your seasons<small>Tick the lots to prepare</small></h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lp-slist" id="lpSeasonList"></div>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b id="lpSeasonCount">None ticked</b><span>Their names, sizes and places come along</span></div>
            <button type="button" class="btn btn-primary" id="lpSeasonUse" disabled>Add them</button>
        </div>
    </div>
</dialog>

{{-- Rename. --}}
<dialog class="lp-sheet is-small" id="lpRenSheet" aria-labelledby="lpRenTitle">
    <form class="lp-sbox" tabindex="-1" id="lpRenForm">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpRenTitle">Rename this plan</h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lp-sbody">
            <label class="sr-only" for="lpRenInput">Name</label>
            <input type="text" id="lpRenInput" class="form-input" maxlength="191" autocomplete="off">
        </div>
        <div class="lp-sfoot"><span class="lp-pickline"></span><button type="button" class="btn btn-white" data-close>Cancel</button><button type="submit" class="btn btn-primary" id="lpRenSave">Save</button></div>
    </form>
</dialog>

{{-- Send to the Protocol Builder. --}}
<dialog class="lp-sheet" id="lpPortSheet" aria-labelledby="lpPortTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpPortTitle">Send to Protocol Builder<small>Every step lands on the protocol's own day count</small></h2><button type="button" class="lp-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div id="lpPortForm" class="lp-slist" style="padding:0 0 1rem">
            <div class="lp-pmode">
                <button type="button" class="lp-choice is-on" data-pmode="new"><span class="ce">✨</span><span class="ct"><b>A new protocol</b><small>Counted the way this crop is planted</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                <div id="lpPortNew">
                    <label class="lp-label" for="lpPortTitleIn">Its name</label>
                    <input type="text" id="lpPortTitleIn" class="form-input" maxlength="190">
                </div>
                <button type="button" class="lp-choice" data-pmode="existing"><span class="ce">📋</span><span class="ct"><b>Add to one of mine</b><small>Into the version in use, and undoable there</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                <div id="lpPortOld" hidden><div class="lp-plist" id="lpPortList" role="listbox"></div></div>
            </div>
        </div>
        <div class="lp-done" id="lpPortDone" hidden>
            <div class="ok"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
            <b id="lpPortDoneT">Sent</b><p id="lpPortDoneP"></p>
        </div>
        <div class="lp-sfoot">
            <span class="lp-pickline"></span>
            <button type="button" class="btn btn-white" data-close id="lpPortClose">Cancel</button>
            <button type="button" class="btn btn-primary" id="lpPortGo">Send</button>
            <a class="btn btn-primary" id="lpPortOpen" href="#" hidden>Open it</a>
        </div>
    </div>
</dialog>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const U = {
        options: @json(route('landprep.options')), varieties: @json(route('landprep.varieties')), seed: @json(route('landprep.seed')),
        seasons: @json(route('landprep.seasons')), protocols: @json(route('landprep.protocols')), generate: @json(route('landprep.generate')),
        list: @json(route('landprep.list')), meta: @json(route('landprep.meta')), port: @json(route('landprep.port')),
        job: (id) => @json(url('/app/land-prep/job')) + '/' + id, one: (id) => @json(url('/app/land-prep/one')) + '/' + id, del: (id) => @json(url('/app/land-prep')) + '/' + id,
        ph: @json(asset('data/ph-locations.json')),
    };
    const TODAY = @json($today);
    const CROPS = @json($lpCropJs);
    const STEP_NAMES = ['Crop', 'How it goes in', 'Variety', 'Planting day', 'Lots', 'Crew', 'Soil and water', 'Field history', 'Place', 'Review'];
    const LOT_COLORS = ['#4a7c2a', '#d97706', '#0284c7', '#7c3aed', '#db2777', '#0f766e', '#b45309', '#4f46e5', '#65a30d', '#dc2626', '#0891b2', '#9333ea'];
    const lc = (i) => LOT_COLORS[i % LOT_COLORS.length];
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';

    /* ---- dates, as Y-m-d strings and whole days ---- */
    const dn = (s) => { const [y, m, d] = String(s).split('-').map(Number); return Date.UTC(y, m - 1, d) / 864e5; };
    const ds = (n) => new Date(n * 864e5).toISOString().slice(0, 10);
    const fmt = (s, o) => new Date(String(s).slice(0, 10) + 'T00:00:00Z').toLocaleDateString('en-US', Object.assign({ timeZone: 'UTC' }, o));
    const day = (s) => fmt(s, { weekday: 'short', month: 'short', day: 'numeric' });
    const time = (s) => { const [d, t] = String(s).split(' '); const [h, m] = (t || '0:0').split(':').map(Number); return new Date(Date.UTC(2000, 0, 1, h, m)).toLocaleTimeString('en-US', { timeZone: 'UTC', hour: 'numeric', minute: '2-digit' }); };
    const inDays = (s) => { const n = dn(s) - dn(TODAY); return n === 0 ? 'today' : (n === 1 ? 'tomorrow' : 'in ' + n + ' days'); };
    const num = (v) => { const n = parseFloat(String(v).replace(',', '.')); return Number.isFinite(n) ? n : 0; };
    const ha = (n) => (Math.round(n * 100) / 100).toLocaleString('en-US') + ' ha';

    let OPT = null;
    const S = {
        crop: 'rice', method: null, variety: '', varRec: null, seedType: '', planting: null,
        lots: [], crew: { workers: 3, equipment: 'hand_tractor', machines: 1, daysPerWeek: 6, stagger: false },
        soil: {}, history: {}, loc: { province: '', town: '', place: '', lat: null, lng: null }, notes: '',
    };
    let uid = 0;
    const newLot = (o) => Object.assign({ uid: ++uid, name: 'Lot ' + (S.lots.length + 1), area: '', planting: null, sourceLotId: null }, o || {});
    S.lots.push(newLot({ name: 'Lot 1' }));
    let step = 0;
    const LAST = 9;

    async function boot() {
        try {
            OPT = (await window.api(U.options)).data;
        } catch (err) { window.toast?.(err.message, 'error'); return; }
        paintQuote();
        paintMethods();
        paintEquip();
        paintQuestions();
        paintLots();
        $('lpPlacePh').hidden = !OPT.ph;
        $('lpPlaceAny').hidden = !!OPT.ph;
        show(0);
        const open = new URLSearchParams(location.search).get('open');
        if (open) openSaved(open);
    }

    function paintQuote() {
        const q = $('lpQuote');
        if (!OPT.canUse) {
            q.className = 'lp-quote is-off';
            q.innerHTML = '<span>' + esc(OPT.whyNot || 'The planner is not available right now.') + '</span>';
        } else {
            q.className = 'lp-quote';
            q.innerHTML = (OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '')
                + '<span>Anee plans every step of your land preparation, lot by lot, from PhilRice and extension practice, your soil, the weather and your crew. One plan costs <b>' + OPT.quote + ' credits</b>. You have '
                + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : '<b>' + esc(OPT.balance) + '</b>') + '. Nothing is charged until the plan is ready.</span>';
        }
        q.hidden = false;
    }

    /* ---------------- the walk ---------------- */
    function show(n, back) {
        step = Math.max(0, Math.min(LAST, n));
        document.querySelectorAll('.lp-step').forEach((s) => {
            const on = Number(s.dataset.step) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!back);
        });
        $('lpStepName').textContent = STEP_NAMES[step];
        $('lpStepNo').textContent = 'Step ' + (step + 1) + ' of ' + (LAST + 1);
        $('lpBar').style.width = ((step + 1) / (LAST + 1) * 100) + '%';
        $('lpBack').disabled = step === 0;
        $('lpNext').hidden = step === LAST;
        if (step === 2) paintVarTag(true);
        if (step === 3) paintDate();
        if (step === LAST) review();
        const top = $('lpWiz').getBoundingClientRect().top;
        if (top < 0) $('lpWiz').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
    }
    function ready() {
        switch (step) {
            case 1: return !!S.method || (window.toast?.('Pick how it goes in.', 'error'), false);
            case 3: return !!S.planting || (window.toast?.('Pick the planting day.', 'error'), false);
            case 4: {
                readLots();
                const bad = S.lots.find((l) => !l.name.trim() || !(num(l.area) > 0));
                if (bad) { window.toast?.('Give ' + (bad.name.trim() || 'every lot') + ' a name and a size in hectares.', 'error'); return false; }
                return true;
            }
            case 5: return S.crew.workers >= 1 || (window.toast?.('Say how many workers there are.', 'error'), false);
            case 8:
                S.notes = $('lpNotes').value.trim();
                if (OPT.ph) return (!!S.loc.province && !!S.loc.town) || (window.toast?.('Pick the province and town, so Anee can read the weather there.', 'error'), false);
                S.loc.place = $('lpPlaceText').value.trim();
                return !!S.loc.place || (window.toast?.('Say where the lots are.', 'error'), false);
            default: return true;
        }
    }
    $('lpNext').addEventListener('click', () => { if (ready()) show(step + 1); });
    $('lpBack').addEventListener('click', () => show(step - 1, true));

    /* ---------------- 1 and 2: crop and method ---------------- */
    document.addEventListener('croppick', (e) => {
        if (!e.target.closest('#lpWiz')) return;
        S.crop = e.detail.crop;
        S.method = null; S.variety = ''; S.varRec = null; S.seedType = '';
        paintMethods(); paintQuestions();
        setTimeout(() => show(1), 220);
    });
    const dayWordOf = () => (['tpr_manual', 'tpr_mechanical', 'transplant'].includes(S.method) ? 'transplant' : (['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'].includes(S.method) ? 'sow' : 'plant'));
    function paintMethods() {
        if (!OPT) return;
        const ms = OPT.methods[S.crop] || {};
        const keys = Object.keys(ms);
        if (!S.method || !ms[S.method]) S.method = keys.length === 1 ? keys[0] : null;
        const rice = S.crop === 'rice';
        $('lpMethodQ').textContent = keys.length === 1 ? 'How it goes in' : 'How will you plant it?';
        $('lpMethodSub').textContent = keys.length === 1 ? (CROPS[S.crop] ? CROPS[S.crop].en : 'This crop') + ' goes in one way. The plan follows it.' : 'Each way has its own schedule.';
        $('lpMethods').innerHTML = keys.map((k) => {
            const m = ms[k], f = rice ? (OPT.riceFacts[k] || {}) : {};
            return '<button type="button" class="lp-choice' + (S.method === k ? ' is-on' : '') + '" data-method="' + esc(k) + '"><span class="ce">' + esc(m[2]) + '</span><span class="ct"><b>' + esc(m[0]) + '</b><small>' + esc(m[1]) + '</small>'
                + (f.rate ? '<em>' + esc(f.rate.split('.')[0]) + '</em>' : '') + '</span><span class="ck">' + CHECK + '</span></button>';
        }).join('');
    }
    $('lpMethods').addEventListener('click', (e) => {
        const b = e.target.closest('[data-method]');
        if (!b) return;
        S.method = b.dataset.method;
        $('lpMethods').querySelectorAll('.lp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        loadSeed();
        setTimeout(() => show(2), 200);
    });

    /* ---------------- 3: variety and seed ---------------- */
    const VAR = {};
    async function varieties() {
        if (VAR[S.crop]) return VAR[S.crop];
        try { VAR[S.crop] = (await window.api(U.varieties + '?crop=' + encodeURIComponent(S.crop))).data.rows || []; }
        catch (_) { VAR[S.crop] = []; }
        return VAR[S.crop];
    }
    function paintVarTag(fetchSeed) {
        const t = $('lpVarTag');
        const r = S.varRec;
        t.classList.toggle('is-none', !S.variety);
        t.querySelector('.ti').textContent = CROPS[S.crop] ? CROPS[S.crop].icon : '🌱';
        $('lpVarName').textContent = S.variety || "I don't know yet";
        $('lpVarLine').textContent = r ? [r.l, r.h ? 'hybrid' : (S.crop === 'rice' ? 'inbred' : r.k), r.d ? r.d + ' ' + (r.u || 'days') : '', r.b].filter(Boolean).join(' · ')
            : (S.variety ? 'Not on our list, kept as you typed it' : 'Tap to pick from the list or type one');
        const askType = S.crop === 'rice' && S.method !== 'dsr_dry' && !(r && 'h' in r) && !(r && r.n);
        $('lpSeedType').hidden = !askType;
        $('lpSeedTypes').querySelectorAll('[data-type]').forEach((b) => b.classList.toggle('is-on', b.dataset.type === S.seedType));
        if (fetchSeed) loadSeed();
    }
    let seedAsk = 0;
    async function loadSeed() {
        if (!S.method) return;
        const ask = ++seedAsk;
        const type = S.varRec && S.varRec.n ? (S.varRec.h ? 'hybrid' : 'inbred') : S.seedType;
        try {
            const r = await window.api(U.seed + '?' + new URLSearchParams({ crop: S.crop, method: S.method, variety: S.variety, type }).toString());
            if (ask === seedAsk) paintSeed(r.data.prep);
        } catch (_) { /* the card waits */ }
    }
    function paintSeed(p) {
        const box = $('lpSeed');
        if (!p) { box.hidden = true; return; }
        if (!p.found || !p.applies || !p.soakHours) {
            box.innerHTML = '<div class="lp-seed-h"><b>Seed preparation</b><span class="lp-badge is-none">' + (p.applies === false && p.found ? 'Not needed' : 'Not researched') + '</span></div><p>' + esc(p.say) + '</p>';
            box.hidden = false;
            return;
        }
        const co = p.confidence === 'company';
        box.innerHTML = '<div class="lp-seed-h"><b>' + (p.brand ? esc(p.brand) : (p.type === 'inbred' ? 'Inbred seed' : 'Hybrid seed')) + '</b><span class="lp-badge ' + (co ? 'is-co' : 'is-gen') + '">' + (co ? (p.source === 'brand' ? 'Seed company' : 'PhilRice') : 'General practice') + '</span></div>'
            + '<div class="lp-hours"><div class="lp-hour"><b>' + p.soakHours + ' h</b><small>soak' + (p.soakRange[0] !== p.soakRange[1] ? ' (' + p.soakRange[0] + ' to ' + p.soakRange[1] + ')' : '') + '</small></div>'
            + '<div class="lp-hour"><b>' + (p.refreshEveryHours ? 'every ' + p.refreshEveryHours + ' h' : 'none given') + '</b><small>change the water</small></div>'
            + '<div class="lp-hour"><b>' + p.incubateRange[0] + ' to ' + p.incubateRange[1] + ' h</b><small>incubate</small></div></div>'
            + '<p>' + esc(p.say) + (p.refreshNote ? ' ' + esc(p.refreshNote) : '') + (p.floatRule ? ' ' + esc(p.floatRule) : '') + ' The plan dates every soak, water change and sowing for each lot.</p>';
        box.hidden = false;
    }
    $('lpSeedTypes').addEventListener('click', (e) => {
        const b = e.target.closest('[data-type]');
        if (!b) return;
        S.seedType = b.dataset.type;
        paintVarTag(true);
    });
    const norm = (s) => String(s || '').toLowerCase().replace(/[\s\-._]+/g, '');
    let varRows = [];
    $('lpVarTag').addEventListener('click', async () => {
        $('lpVarQ').value = '';
        $('lpVarList').innerHTML = '<p class="lp-snone">Loading the varieties…</p>';
        sheetOpen($('lpVarSheet'), $('lpVarQ'));
        varRows = await varieties();
        paintVarList();
    });
    function paintVarList() {
        const q = $('lpVarQ').value.trim();
        const nq = norm(q);
        const hit = (r) => !nq || norm(r.n).includes(nq) || norm(r.l).includes(nq) || norm(r.t).includes(nq);
        const rows = varRows.filter(hit);
        const exact = nq && varRows.some((r) => norm(r.n) === nq || norm(r.l) === nq || String(r.t || '').split(',').some((t) => norm(t) === nq));
        const row = (r) => '<button type="button" class="lp-row" role="option" data-var="' + esc(r.n) + '" aria-selected="' + (S.variety === r.n) + '"><span class="rt"><b>' + esc(r.n) + (r.l ? ' · ' + esc(r.l) : '') + '</b><small>'
            + esc([r.t, r.d ? r.d + ' ' + (r.u || 'days') : '', r.k].filter(Boolean).join(' · ')) + '</small></span>'
            + (r.h ? '<span class="rn is-hy">' + esc(r.b ? r.b : 'Hybrid') + '</span>' : (S.crop === 'rice' ? '<span class="rn">Inbred</span>' : '')) + '</button>';
        let h = '<button type="button" class="lp-row" data-var="" aria-selected="' + (!S.variety) + '"><span class="ri">🤔</span><span class="rt"><b>I don\'t know yet</b><small>The plan uses the general practice and says what to check</small></span></button>';
        if (q && !exact) h += '<button type="button" class="lp-row is-typed" data-typed="' + esc(q) + '"><span class="ri">✍️</span><span class="rt"><b>Use “' + esc(q) + '”</b><small>Not on the list? Keep the name you know</small></span></button>';
        const pop = !q ? rows.filter((r) => r.p) : [];
        if (pop.length) h += '<b class="lp-sgh">Popular with farmers</b>' + pop.slice(0, 20).map(row).join('');
        if (rows.length) h += '<b class="lp-sgh">' + (q ? rows.length + ' found' : 'All ' + rows.length + ' varieties') + '</b>' + rows.slice(0, 80).map(row).join('') + (rows.length > 80 ? '<p class="lp-snone">Keep typing to narrow the list.</p>' : '');
        else if (!varRows.length) h += '<p class="lp-snone">No varieties on record for this crop. Type the name you know.</p>';
        else if (q) h += '<p class="lp-snone">No variety by that name on our list.</p>';
        $('lpVarList').innerHTML = h;
    }
    $('lpVarQ').addEventListener('input', paintVarList);
    $('lpVarList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-var],[data-typed]');
        if (!b) return;
        if ('typed' in b.dataset) { S.variety = b.dataset.typed; S.varRec = null; }
        else { S.variety = b.dataset.var; S.varRec = varRows.find((r) => r.n === S.variety) || null; }
        if (S.varRec && S.varRec.n) S.seedType = S.crop === 'rice' ? (S.varRec.h ? 'hybrid' : 'inbred') : '';
        sheetClose($('lpVarSheet'));
        const t = $('lpVarTag');
        t.classList.remove('is-bump'); void t.offsetWidth; t.classList.add('is-bump');
        paintVarTag(true);
    });

    /* ---------------- 4: the planting day (and a lot's own) ---------------- */
    const leadDays = () => (OPT && OPT.lead[S.crop + ':' + S.method]) || 30;
    const MAX_DATE = ds(dn(TODAY) + 400);
    function paintDate() {
        $('lpDateQ').textContent = 'When will you ' + dayWordOf() + '?';
        const t = $('lpDateTag');
        t.classList.toggle('is-none', !S.planting);
        $('lpDateMon').textContent = S.planting ? fmt(S.planting, { month: 'short' }) : 'Day';
        $('lpDateDay').textContent = S.planting ? fmt(S.planting, { day: 'numeric' }) : '?';
        $('lpDateText').textContent = S.planting ? fmt(S.planting, { weekday: 'short', month: 'short', day: 'numeric' }) : 'Pick the day';
        $('lpDateLine').textContent = S.planting ? fmt(S.planting, { year: 'numeric' }) + ', ' + inDays(S.planting) : 'Opens a calendar';
        const lead = leadDays();
        const earliest = ds(dn(TODAY) + lead);
        const h = $('lpDateHint');
        if (!S.method) { h.hidden = true; return; }
        if (S.planting && S.planting < earliest) {
            h.className = 'lp-hint is-warn';
            h.innerHTML = 'The full preparation starts about <b>' + lead + ' days</b> before. With this day, the early steps are already late, so Anee squeezes what she can. To follow every step, plant on or after <b>' + esc(day(earliest)) + '</b>.';
        } else {
            h.className = 'lp-hint';
            h.innerHTML = 'The full preparation starts about <b>' + lead + ' days</b> before the ' + dayWordOf() + ' day. To follow every step from today, plant on or after <b>' + esc(day(earliest)) + '</b>.';
        }
        h.hidden = false;
    }
    const DS = { month: TODAY.slice(0, 7), sel: null, target: null };
    const QUICK = [['In 2 weeks', 14], ['In 3 weeks', 21], ['In 4 weeks', 28], ['In 5 weeks', 35], ['In 6 weeks', 42], ['In 8 weeks', 56], ['In 10 weeks', 70], ['In 3 months', 91]];
    function openDate(target) {
        DS.target = target || null;
        const cur = target ? (S.lots.find((l) => l.uid === target) || {}).planting || S.planting : S.planting;
        DS.sel = cur;
        DS.month = (cur || ds(dn(TODAY) + leadDays())).slice(0, 7);
        $('lpDateTitle').textContent = target ? 'Own planting day for ' + ((S.lots.find((l) => l.uid === target) || {}).name || 'this lot') : 'The ' + dayWordOf() + ' day';
        $('lpQuick').innerHTML = QUICK.map(([t, n]) => '<button type="button" class="lp-qc" data-q="' + ds(dn(TODAY) + n) + '">' + t + '</button>').join('');
        paintMonth(0); paintPick();
        sheetOpen($('lpDateSheet'));
        $('lpQuick').querySelector('.is-on')?.scrollIntoView({ inline: 'center', block: 'nearest' });
    }
    function paintMonth(dir) {
        const [y, m] = DS.month.split('-').map(Number);
        const first = Date.UTC(y, m - 1, 1) / 864e5;
        const count = new Date(Date.UTC(y, m, 0)).getUTCDate();
        const lead = new Date(first * 864e5).getUTCDay();
        const ok = ds(dn(TODAY) + leadDays());
        $('lpMonName').textContent = new Date(first * 864e5).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', year: 'numeric' });
        let h = '';
        for (let i = 0; i < lead; i++) h += '<span aria-hidden="true"></span>';
        for (let d = 1; d <= count; d++) {
            const s = ds(first + d - 1), off = s < TODAY || s > MAX_DATE;
            h += '<button type="button" class="lp-d' + (s === TODAY ? ' is-today' : '') + (s === DS.sel ? ' is-sel' : '') + (!off && s >= ok ? ' is-ready' : '') + '" data-d="' + s + '"' + (off ? ' disabled' : '') + ' aria-pressed="' + (s === DS.sel) + '" aria-label="' + esc(fmt(s, { weekday: 'long', month: 'long', day: 'numeric' })) + '">' + d + '</button>';
        }
        for (let i = lead + count; i < 42; i++) h += '<span class="lp-d" aria-hidden="true"></span>';
        const g = $('lpDays');
        g.innerHTML = h;
        if (dir && !reduce()) { g.classList.remove('is-l', 'is-r'); void g.offsetWidth; g.classList.add(dir < 0 ? 'is-l' : 'is-r'); }
        $('lpPrevM').disabled = DS.month <= TODAY.slice(0, 7);
        $('lpNextM').disabled = DS.month >= MAX_DATE.slice(0, 7);
    }
    function paintPick() {
        $('lpQuick').querySelectorAll('.lp-qc').forEach((c) => c.classList.toggle('is-on', c.dataset.q === DS.sel));
        if (!DS.sel) { $('lpPickDate').textContent = 'Tap a day'; $('lpPickLine').textContent = 'Days before today are off'; $('lpDateUse').disabled = true; return; }
        $('lpPickDate').textContent = fmt(DS.sel, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        const early = DS.sel < ds(dn(TODAY) + leadDays());
        $('lpPickLine').textContent = inDays(DS.sel) + (early ? ', the early steps run late' : ', time for every step');
        $('lpDateUse').disabled = false;
    }
    function pickDay(s) {
        DS.sel = s;
        if (s.slice(0, 7) !== DS.month) { const dir = s.slice(0, 7) < DS.month ? -1 : 1; DS.month = s.slice(0, 7); paintMonth(dir); }
        else { $('lpDays').querySelectorAll('.lp-d').forEach((b) => { const on = b.dataset.d === s; b.classList.toggle('is-sel', on); if (b.dataset.d) b.setAttribute('aria-pressed', String(on)); }); }
        paintPick();
    }
    $('lpDays').addEventListener('click', (e) => { const b = e.target.closest('[data-d]'); if (b && !b.disabled) pickDay(b.dataset.d); });
    $('lpQuick').addEventListener('click', (e) => { const b = e.target.closest('[data-q]'); if (b) pickDay(b.dataset.q); });
    const shiftMonth = (k) => {
        const [y, m] = DS.month.split('-').map(Number);
        const next = new Date(Date.UTC(y, m - 1 + k, 1)).toISOString().slice(0, 7);
        if (next < TODAY.slice(0, 7) || next > MAX_DATE.slice(0, 7)) return;
        DS.month = next; paintMonth(k);
    };
    $('lpPrevM').addEventListener('click', () => shiftMonth(-1));
    $('lpNextM').addEventListener('click', () => shiftMonth(1));
    $('lpDateUse').addEventListener('click', () => {
        if (!DS.sel) return;
        if (DS.target) {
            const l = S.lots.find((x) => x.uid === DS.target);
            if (l) l.planting = DS.sel;
            paintLots();
        } else {
            S.planting = DS.sel;
            paintDate();
            const t = $('lpDateTag'); t.classList.remove('is-bump'); void t.offsetWidth; t.classList.add('is-bump');
        }
        sheetClose($('lpDateSheet'));
    });
    $('lpDateTag').addEventListener('click', () => openDate(null));

    /* ---------------- 5: lots ---------------- */
    function readLots() {
        $('lpLots').querySelectorAll('.lp-lot').forEach((el) => {
            const l = S.lots.find((x) => String(x.uid) === el.dataset.uid);
            if (!l) return;
            l.name = el.querySelector('[data-f="name"]').value;
            l.area = el.querySelector('[data-f="area"]').value;
        });
    }
    function paintLots() {
        $('lpLots').innerHTML = S.lots.map((l, i) => '<div class="lp-lot" data-uid="' + l.uid + '" style="--lc:' + lc(i) + '">'
            + '<div class="lp-lot-top"><i></i><b>Lot ' + (i + 1) + '</b>' + (l.from ? '<em>from ' + esc(l.from) + '</em>' : '') + '</div>'
            + (S.lots.length > 1 ? '<button type="button" class="lp-lot-x" data-lot-x="' + l.uid + '" aria-label="Remove this lot"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>' : '')
            + '<div class="lp-lot-row"><div><label>Name</label><input type="text" class="form-input" data-f="name" maxlength="60" value="' + esc(l.name) + '"></div>'
            + '<div><label>Size</label><div class="lp-ha"><input type="text" inputmode="decimal" class="form-input" data-f="area" maxlength="7" placeholder="1" value="' + esc(l.area) + '"><span>ha</span></div></div></div>'
            + '<div class="lp-own">' + (l.planting
                ? '<button type="button" data-lot-date="' + l.uid + '">📅 ' + esc(day(l.planting)) + '</button><button type="button" class="is-x" data-lot-same="' + l.uid + '">Same day as the others</button>'
                : '<button type="button" data-lot-date="' + l.uid + '">📅 Its own planting day</button>') + '</div></div>').join('');
        total();
    }
    function total() {
        readLots();
        const a = S.lots.reduce((s, l) => s + num(l.area), 0);
        $('lpLotTotal').textContent = S.lots.length + (S.lots.length === 1 ? ' lot' : ' lots') + (a > 0 ? ' · ' + ha(a) : '');
    }
    $('lpLots').addEventListener('input', total);
    $('lpLots').addEventListener('click', (e) => {
        const x = e.target.closest('[data-lot-x]');
        if (x) {
            readLots();
            const el = x.closest('.lp-lot');
            el.classList.add('is-gone');
            setTimeout(() => { S.lots = S.lots.filter((l) => String(l.uid) !== x.dataset.lotX); paintLots(); }, reduce() ? 0 : 220);
            return;
        }
        const d = e.target.closest('[data-lot-date]');
        if (d) { readLots(); openDate(Number(d.dataset.lotDate)); return; }
        const s = e.target.closest('[data-lot-same]');
        if (s) { readLots(); const l = S.lots.find((x2) => String(x2.uid) === s.dataset.lotSame); if (l) l.planting = null; paintLots(); }
    });
    $('lpLotAdd').addEventListener('click', () => {
        readLots();
        if (S.lots.length >= 12) { window.toast?.('Up to 12 lots in one plan.', 'error'); return; }
        S.lots.push(newLot({ name: 'Lot ' + (S.lots.length + 1) }));
        paintLots();
        const last = $('lpLots').lastElementChild;
        last?.querySelector('[data-f="name"]')?.focus({ preventScroll: true });
        last?.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'nearest' });
    });
    let SEASONS = null;
    const picked = new Set();
    $('lpLotSeason').addEventListener('click', async () => {
        picked.clear();
        paintSeasonCount();
        $('lpSeasonList').innerHTML = '<p class="lp-snone">Loading your seasons…</p>';
        sheetOpen($('lpSeasonSheet'));
        try { SEASONS = SEASONS || (await window.api(U.seasons)).data.seasons || []; }
        catch (err) { $('lpSeasonList').innerHTML = '<p class="lp-snone">' + esc(err.message) + '</p>'; return; }
        $('lpSeasonList').innerHTML = SEASONS.length ? SEASONS.map((s) => '<b class="lp-sgh">' + esc(s.title) + '</b>' + s.lots.map((l) => '<button type="button" class="lp-row" role="checkbox" aria-checked="false" data-slot="' + l.id + '">'
            + '<span class="lp-check">' + CHECK + '</span><span class="rt"><b>' + esc(l.name) + '</b><small>' + esc([l.size, l.cropLabel, l.planting ? (l.planting >= TODAY ? 'plants ' : 'planted ') + day(l.planting) : '', l.town].filter(Boolean).join(' · ')) + '</small></span></button>').join('')).join('')
            : '<p class="lp-snone">No lots in your seasons yet. Add them here by hand.</p>';
    });
    const paintSeasonCount = () => { $('lpSeasonCount').textContent = picked.size ? picked.size + (picked.size === 1 ? ' lot ticked' : ' lots ticked') : 'None ticked'; $('lpSeasonUse').disabled = !picked.size; };
    $('lpSeasonList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-slot]');
        if (!b) return;
        const id = Number(b.dataset.slot);
        if (picked.has(id)) picked.delete(id); else picked.add(id);
        b.setAttribute('aria-checked', String(picked.has(id)));
        paintSeasonCount();
    });
    $('lpSeasonUse').addEventListener('click', () => {
        readLots();
        const all = (SEASONS || []).flatMap((s) => s.lots.map((l) => Object.assign({ season: s.title }, l)));
        // A first lot still blank gives way to the season's.
        if (S.lots.length === 1 && !num(S.lots[0].area) && /^Lot 1$/.test(S.lots[0].name)) S.lots = [];
        all.filter((l) => picked.has(l.id)).forEach((l) => {
            if (S.lots.length >= 12 || S.lots.some((x) => x.sourceLotId === l.id)) return;
            S.lots.push(newLot({ name: l.name, area: l.ha > 0 ? String(l.ha) : '', planting: l.planting && l.planting >= TODAY ? l.planting : null, sourceLotId: l.id, from: l.season }));
            if (!S.loc.province && l.province) { S.loc.province = l.province; S.loc.town = l.town || ''; }
            if (S.loc.lat === null && l.lat !== null && l.lng !== null) { S.loc.lat = l.lat; S.loc.lng = l.lng; }
        });
        if (!S.lots.length) S.lots.push(newLot({ name: 'Lot 1' }));
        paintLots(); paintPlace();
        sheetClose($('lpSeasonSheet'));
    });

    /* ---------------- 6: crew ---------------- */
    const setWorkers = (n) => { S.crew.workers = Math.max(1, Math.min(200, Math.round(n) || 1)); $('lpWorkers').value = S.crew.workers; };
    document.querySelectorAll('[data-step-w]').forEach((b) => b.addEventListener('click', () => setWorkers(S.crew.workers + Number(b.dataset.stepW))));
    $('lpWorkers').addEventListener('change', () => setWorkers(num($('lpWorkers').value)));
    function paintEquip() {
        $('lpEquip').innerHTML = Object.entries(OPT.equipment).map(([k, e]) => '<button type="button" class="lp-choice' + (S.crew.equipment === k ? ' is-on' : '') + '" data-eq="' + k + '"><span class="ce">' + esc(e[2]) + '</span><span class="ct"><b>' + esc(e[0]) + '</b><small>' + esc(e[1]) + '</small></span><span class="ck">' + CHECK + '</span></button>').join('');
        paintMach();
    }
    function paintMach() {
        const eq = S.crew.equipment;
        $('lpMachWrap').hidden = eq === 'hand';
        $('lpMachLabel').textContent = eq === 'carabao' ? 'How many carabao teams?' : (eq === 'hired' ? 'How many machines can the operator bring?' : 'How many machines?');
        $('lpMach').innerHTML = [1, 2, 3, 4].map((n) => '<button type="button" class="lp-chipb' + (S.crew.machines === n ? ' is-on' : '') + '" data-mach="' + n + '">' + n + '</button>').join('');
    }
    $('lpEquip').addEventListener('click', (e) => { const b = e.target.closest('[data-eq]'); if (!b) return; S.crew.equipment = b.dataset.eq; $('lpEquip').querySelectorAll('.lp-choice').forEach((c) => c.classList.toggle('is-on', c === b)); paintMach(); });
    $('lpMach').addEventListener('click', (e) => { const b = e.target.closest('[data-mach]'); if (!b) return; S.crew.machines = Number(b.dataset.mach); paintMach(); });
    $('lpDpw').addEventListener('click', (e) => { const b = e.target.closest('[data-dpw]'); if (!b) return; S.crew.daysPerWeek = Number(b.dataset.dpw); $('lpDpw').querySelectorAll('[data-dpw]').forEach((c) => c.classList.toggle('is-on', c === b)); });
    $('lpStagger').addEventListener('click', (e) => { const b = e.target.closest('[data-stagger]'); if (!b) return; S.crew.stagger = b.dataset.stagger === '1'; $('lpStagger').querySelectorAll('.lp-choice').forEach((c) => c.classList.toggle('is-on', c === b)); });

    /* ---------------- 7 and 8: soil and history ---------------- */
    function paintQuestions() {
        if (!OPT) return;
        const rice = S.crop === 'rice';
        const group = (table, state, host) => {
            $(host).innerHTML = Object.entries(table).filter(([, q]) => !(q[2] === 'rice' && !rice) && !(q[2] === 'other' && rice)).map(([k, q]) => '<div class="lp-qg' + (state[k] ? ' is-done' : '') + '" data-q="' + k + '"><b><i>✓</i>' + esc(q[0]) + '</b><div class="lp-chips">'
                + Object.entries(q[1]).map(([v, o]) => '<button type="button" class="lp-chipb' + (state[k] === v ? ' is-on' : '') + '" data-v="' + esc(v) + '">' + esc(o[0]) + (o[1] ? '<small>' + esc(o[1]) + '</small>' : '') + '</button>').join('') + '</div></div>').join('');
        };
        Object.keys(S.history).forEach((k) => { if (OPT.history[k] && OPT.history[k][2] === 'rice' && !rice) delete S.history[k]; });
        group(OPT.soil, S.soil, 'lpSoil');
        group(OPT.history, S.history, 'lpHist');
        counts();
    }
    const counts = () => {
        const n1 = $('lpSoil').querySelectorAll('.lp-qg').length, a1 = $('lpSoil').querySelectorAll('.lp-qg.is-done').length;
        const n2 = $('lpHist').querySelectorAll('.lp-qg').length, a2 = $('lpHist').querySelectorAll('.lp-qg.is-done').length;
        $('lpSoilCount').textContent = a1 + ' of ' + n1 + ' answered';
        $('lpHistCount').textContent = a2 + ' of ' + n2 + ' answered';
    };
    const qWire = (host, state) => $(host).addEventListener('click', (e) => {
        const b = e.target.closest('[data-v]');
        if (!b) return;
        const g = b.closest('[data-q]'), k = g.dataset.q;
        state[k] = state[k] === b.dataset.v ? undefined : b.dataset.v;
        if (!state[k]) delete state[k];
        g.querySelectorAll('[data-v]').forEach((c) => c.classList.toggle('is-on', c.dataset.v === state[k]));
        g.classList.toggle('is-done', !!state[k]);
        counts();
    });
    qWire('lpSoil', S.soil);
    qWire('lpHist', S.history);

    /* ---------------- 9: place ---------------- */
    let PHL = null;
    const loadPh = async () => PHL ??= await (await fetch(U.ph)).json();
    const townName = (t) => String(t || '').replace(/\s*\(Capital\)\s*$/i, '');
    function paintPlace() {
        const p = $('lpProv'), t = $('lpTown');
        p.classList.toggle('is-none', !S.loc.province);
        p.querySelector('.tt b').textContent = S.loc.province || 'Choose the province';
        t.disabled = !S.loc.province;
        t.classList.toggle('is-none', !S.loc.town);
        t.querySelector('.tt b').textContent = S.loc.town ? townName(S.loc.town) : (S.loc.province ? 'Choose the town' : 'Pick the province first');
    }
    let PICK = null;
    function pick(title, rows, cur, done) {
        PICK = { rows, done };
        $('lpPickTitle').textContent = title;
        $('lpPickQ').value = '';
        paintPickList(cur);
        sheetOpen($('lpPickSheet'), $('lpPickQ'));
        $('lpPickList').querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'center' });
    }
    function paintPickList(cur) {
        const q = $('lpPickQ').value.trim().toLowerCase();
        const rows = PICK.rows.filter((r) => !q || r.label.toLowerCase().includes(q));
        $('lpPickList').innerHTML = rows.length ? rows.map((r) => '<button type="button" class="lp-row" role="option" data-pick="' + esc(r.key) + '" aria-selected="' + (r.key === cur) + '"><span class="rt"><b>' + esc(r.label) + '</b>' + (r.hint ? '<small>' + esc(r.hint) + '</small>' : '') + '</span></button>').join('') : '<p class="lp-snone">Nothing by that name.</p>';
    }
    $('lpPickQ').addEventListener('input', () => { if (PICK) paintPickList(null); });
    $('lpPickList').addEventListener('click', (e) => { const b = e.target.closest('[data-pick]'); if (!b || !PICK) return; sheetClose($('lpPickSheet')); PICK.done(b.dataset.pick); });
    $('lpProv').addEventListener('click', async () => {
        try { await loadPh(); } catch (_) { window.toast?.('The list of provinces could not load.', 'error'); return; }
        pick('Choose the province', Object.keys(PHL).sort((a, b) => a.localeCompare(b)).map((p) => ({ key: p, label: p, hint: PHL[p].length + ' towns and cities' })), S.loc.province, (k) => {
            if (k !== S.loc.province) { S.loc.town = ''; S.loc.lat = null; S.loc.lng = null; }
            S.loc.province = k; paintPlace();
            if (!S.loc.town) setTimeout(() => $('lpTown').click(), 300);
        });
    });
    $('lpTown').addEventListener('click', async () => {
        if (!S.loc.province) return;
        try { await loadPh(); } catch (_) { return; }
        pick('Choose the town or city', (PHL[S.loc.province] || []).map((t) => ({ key: t, label: townName(t), hint: /\(Capital\)/i.test(t) ? 'The capital' : '' })), S.loc.town, (k) => { S.loc.town = k; S.loc.lat = null; S.loc.lng = null; paintPlace(); });
    });

    /* ---------------- 10: review and run ---------------- */
    const labelOf = (table, k, v) => (table[k] && table[k][1][v] ? table[k][1][v][0] : v);
    function review() {
        readLots();
        S.notes = $('lpNotes').value.trim();
        const m = (OPT.methods[S.crop] || {})[S.method] || [];
        const a = S.lots.reduce((s, l) => s + num(l.area), 0);
        const own = S.lots.filter((l) => l.planting).length;
        const eq = OPT.equipment[S.crew.equipment] || [];
        const sAns = Object.keys(S.soil).length + ($('lpPh').value.trim() ? 1 : 0), hAns = Object.keys(S.history).length;
        const rows = [
            [0, CROPS[S.crop] ? CROPS[S.crop].icon : '🌱', 'Crop', CROPS[S.crop] ? CROPS[S.crop].en : S.crop],
            [1, m[2] || '🌱', 'How it goes in', m[0] || 'Not chosen'],
            [2, '🏷️', 'Variety', (S.variety || "Don't know yet") + (S.seedType && !(S.varRec && S.varRec.n) ? ', ' + S.seedType : '')],
            [3, '📅', 'Planting day', S.planting ? day(S.planting) + (own ? ', ' + own + ' with their own day' : '') : 'Not chosen'],
            [4, '🟩', 'Lots', S.lots.length + (S.lots.length === 1 ? ' lot' : ' lots') + (a ? ', ' + ha(a) : '')],
            [5, eq[2] || '👷', 'Crew', S.crew.workers + (S.crew.workers === 1 ? ' worker' : ' workers') + ', ' + (eq[0] || '').toLowerCase() + (S.crew.equipment !== 'hand' && S.crew.machines > 1 ? ' x' + S.crew.machines : '') + ', ' + S.crew.daysPerWeek + ' days a week' + (S.crew.stagger ? ', staggering allowed' : '')],
            [6, '🟫', 'Soil and water', sAns ? sAns + ' answered' + (S.soil.texture ? ', ' + labelOf(OPT.soil, 'texture', S.soil.texture).toLowerCase() : '') : 'Skipped'],
            [7, '📜', 'Field history', hAns ? hAns + ' answered' + (S.history.prevCrop ? ', after ' + labelOf(OPT.history, 'prevCrop', S.history.prevCrop).toLowerCase() : '') : 'Skipped'],
            [8, '📍', 'Place', OPT.ph ? (S.loc.town ? townName(S.loc.town) + ', ' + S.loc.province : 'Not chosen') : (S.loc.place || 'Not given')],
        ];
        $('lpReview').innerHTML = rows.map(([n, e, k, v]) => '<button type="button" data-jump="' + n + '"><span class="re">' + esc(e) + '</span><span class="rt"><small>' + esc(k) + '</small><b>' + esc(v) + '</b></span><span class="rc">Change</span></button>').join('');
        $('lpRunFace').innerHTML = OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '';
        $('lpRunSays').textContent = OPT.canUse ? 'Make my plan · ' + OPT.quote + ' credits' : 'Make my plan';
        $('lpRun').disabled = !OPT.canUse;
        const short = OPT.canUse && !OPT.unlimited && Number(OPT.balance) < Number(OPT.quote);
        $('lpRunFine').innerHTML = !OPT.canUse ? esc(OPT.whyNot || '')
            : (short ? 'You have ' + esc(Math.floor(Number(OPT.balance))) + ' credits and this plan needs ' + esc(OPT.quote) + '.' + (OPT.creditsUrl ? ' <a href="' + esc(OPT.creditsUrl) + '" style="font-weight:800;text-decoration:underline">Get credits</a>' : '')
            : 'Usually one to three minutes. Stay on this page while Anee works.');
    }
    $('lpReview').addEventListener('click', (e) => { const b = e.target.closest('[data-jump]'); if (b) show(Number(b.dataset.jump), true); });

    $('lpRun').addEventListener('click', async () => {
        readLots();
        const body = {
            crop: S.crop, method: S.method, variety: S.variety, seedType: S.seedType || null, planting: S.planting,
            lots: S.lots.map((l) => ({ name: l.name.trim(), area: num(l.area), planting: l.planting, sourceLotId: l.sourceLotId })),
            crew: S.crew, soil: Object.assign({}, S.soil, $('lpPh').value.trim() ? { ph: $('lpPh').value.trim() } : {}), history: S.history,
            location: { province: S.loc.province, town: townName(S.loc.town), place: S.loc.place, lat: S.loc.lat, lng: S.loc.lng }, notes: S.notes,
        };
        window.aneeWait.show({ title: 'Anee is planning your land preparation…', lines: ['Laying out the PhilRice steps for ' + (CROPS[S.crop] ? CROPS[S.crop].en.toLowerCase() : 'your crop') + '…', 'Sharing the crew between your lots…', 'Reading the forecast and ten years of weather…', 'Checking the ENSO outlook for water…', 'Timing the seed soak to the hour…'], sub: 'Usually one to three minutes. Stay on this page while Anee works.' });
        $('lpErr').hidden = true;
        let landed = false;
        try {
            const res = await window.api(U.generate, { method: 'POST', body });
            let data = res.data;
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases: window.aneeWait.phases.plain });
            landed = true;
            if (data.balance != null) OPT.balance = data.balance;
            try { openView(data.item, 'fresh'); } catch (drawErr) { console.error(drawErr); window.toast?.('Your plan is saved in Your plans, but it could not be shown here.', 'error'); }
            await window.aneeWait.done({ title: 'Your plan is ready', line: (data.item && data.item.credits ? data.item.credits + ' credits used. ' : '') + 'Saved in Your plans.' });
            paintQuote();
            LIST.stale = true;
        } catch (err) {
            // Said on the page, not in a toast that is gone before it is read.
            $('lpErrText').textContent = (err && err.message) || 'Something went wrong. Nothing was charged. Please try again.';
            $('lpErr').hidden = false;
            $('lpRunSays').textContent = OPT && OPT.canUse ? 'Try again · ' + OPT.quote + ' credits' : 'Try again';
        } finally {
            if (!landed) window.aneeWait.fail();
            if (!landed) requestAnimationFrame(() => $('lpErr').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' }));
        }
    });

    /* ---------------- the sheets ---------------- */
    const sheetLock = () => document.documentElement.classList.toggle('lp-lock', !!document.querySelector('.lp-sheet[open]') || !$('lpView').hidden);
    function sheetOpen(dlg, focus) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        sheetLock();
        if (window.registerOverlay) window.registerOverlay('lp:' + dlg.id, () => sheetClose(dlg));
        if (focus && matchMedia('(pointer: fine)').matches) focus.focus(); else dlg.querySelector('.lp-sbox').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('lp:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); sheetLock(); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    document.querySelectorAll('.lp-sheet').forEach((dlg) => {
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    });

    /* ================= the plan, drawn ================= */
    const TAG = { philrice: 'PhilRice practice', irri: 'IRRI practice', extension: 'Extension practice', anee: "Anee's suggestion" };
    const REASON = { soil: 'For your soil', history: "For the field's history", weather: 'For the weather', enso: 'For the ENSO outlook', crew: 'For your crew', seed: 'For your seed', water: 'For your water', method: 'For how you plant' };
    const TOPIC = { weather: '🌧️', enso: '🌊', soil: '🟫', history: '📜', crew: '👷', seed: '🌾', water: '💧' };
    const SHORT = { plow: 'Plow', first_plow: 'First plow', crack_plow: 'Dry tillage', harrow1: 'Harrow 1', harrow2: 'Harrow 2', harrow: 'Harrow', harrow_level: 'Harrow, level', level: 'Level', extra_level: 'Extra leveling', rough_level: 'Rough level',
        dikes: 'Dikes', straw: 'Straw', canals: 'Canals', manure: 'Manure', lime: 'Lime', gypsum: 'Gypsum', seedbed_site: 'Seedbed ground', seedbed_beds: 'Seedbed plots', sow_seedbed: 'Sow seedbed', nursery_fill: 'Fill trays',
        sow_nursery: 'Sow nursery', backup_seedbed: 'Backup seedbed', clear: 'Clear', clear_land: 'Clear land', stones: 'Stones', contour: 'Contour', beds: 'Beds', mulch: 'Mulch', furrow: 'Furrows', ridges: 'Ridges', drainage: 'Drainage',
        holes: 'Holes', fill: 'Fill holes', windbreak: 'Windbreak', material: 'Planting material', snail_screens: 'Snail screens', trellis: 'Trellis', stale_kill: 'Weed kill', clean_field: 'Clean field', drain_canal: 'Drain canal',
        deep_till: 'Hardpan', subsoil: 'Subsoil', nursery: 'Nursery', sucker_prep: 'Suckers', harden: 'Harden plantlets', transplant: 'Transplant', pull: 'Pull seedlings', sow: 'Sow', plant: 'Plant' };
    let VIEW = null;   // { item, mode, lot, tab }
    const stepOf = (r, key) => (r.steps || []).find((s) => s.key === key) || { key, what: SHORT[key] || key, how: '' };
    const short = (r, key) => SHORT[key] || String(stepOf(r, key).what || key).split(' ').slice(0, 3).join(' ');

    function openView(item, mode) {
        VIEW = { item, mode, lot: 0, tab: 'farm' };
        $('lpViewTitle').textContent = item.title || 'Land prep plan';
        drawPlan();
        const v = $('lpView');
        v.hidden = false;
        v.scrollTop = 0;
        sheetLock();
        if (window.registerOverlay) window.registerOverlay('lp:view', closeView);
        requestAnimationFrame(() => requestAnimationFrame(() => { v.classList.add('is-on'); setTimeout(() => v.classList.add('is-drawn'), 120); }));
    }
    function closeView() {
        const v = $('lpView');
        if (v.hidden) return;
        if (window.unregisterOverlay) window.unregisterOverlay('lp:view');
        v.classList.remove('is-on', 'is-drawn');
        const fresh = VIEW && VIEW.mode === 'fresh';
        setTimeout(() => { v.hidden = true; $('lpViewBody').innerHTML = ''; sheetLock(); if (fresh) tab('list'); }, reduce() ? 0 : 300);
        if (new URLSearchParams(location.search).has('open')) history.replaceState(null, '', location.pathname);
    }
    $('lpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !$('lpView').hidden && !document.querySelector('.lp-sheet[open]')) closeView(); });

    function drawPlan() {
        const it = VIEW.item, r = it.report || {};
        const lots = r.lots || [];
        const area = lots.reduce((s, l) => s + Number(l.area || 0), 0);
        const major = (r.conflicts || []).filter((c) => c.major !== false);
        const moved = lots.filter((l) => l.shift > 0);
        const dates = lots.map((l) => l.planting).sort();
        const a = r.anee || {};
        $('lpViewBody').innerHTML = ''
            + '<div class="lp-hero"><div class="lp-hero-top"><span class="lp-hero-ico">' + esc((r.crop || {}).icon || '🌱') + '</span><div><small>Land preparation plan</small><h2>' + esc((r.crop || {}).label || '') + ', ' + esc(((r.method || {}).label || '').toLowerCase()) + '</h2></div></div>'
            + '<div class="lp-hero-chips"><span>📅 ' + esc(dates[0] ? day(dates[0]) : '') + (dates.length > 1 && dates[dates.length - 1] !== dates[0] ? ' to ' + esc(day(dates[dates.length - 1])) : '') + '</span>'
            + '<span>🟩 ' + lots.length + (lots.length === 1 ? ' lot' : ' lots') + ', ' + ha(area) + '</span>'
            + '<span>👷 ' + esc(((r.crew || {}).workers || '') + ' workers, ' + String((r.crew || {}).equipmentLabel || '').toLowerCase()) + '</span>'
            + (major.length ? '<span class="is-warn">⚠ ' + major.length + (major.length === 1 ? ' step runs late' : ' steps run late') + '</span>' : '<span class="is-ok">✓ Every lot ready on time</span>')
            + (moved.length ? '<span>🔀 ' + moved.length + (moved.length === 1 ? ' lot staggered' : ' lots staggered') + '</span>' : '')
            + (r.variety ? '<span>🏷️ ' + esc(r.variety) + '</span>' : '') + (it.credits ? '<span>' + esc(it.credits) + ' credits</span>' : '') + '</div></div>'
            + '<div class="lp-seg" id="lpSeg"><i></i><button type="button" class="' + (VIEW.tab === 'farm' ? 'is-on' : '') + '" data-tab="farm">Whole farm</button><button type="button" class="' + (VIEW.tab === 'lot' ? 'is-on' : '') + '" data-tab="lot">Per lot</button></div>'
            + '<div class="lp-pane" id="lpFarm"' + (VIEW.tab === 'farm' ? '' : ' hidden') + '>' + farmHtml(r, a) + '</div>'
            + '<div class="lp-pane" id="lpLot"' + (VIEW.tab === 'lot' ? '' : ' hidden') + '>' + lotHtml(r, VIEW.lot) + '</div>'
            + '<div class="lp-acts"><button type="button" class="btn btn-primary" data-act="send"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>Send to Protocol Builder</button>'
            + '<button type="button" class="btn btn-white" data-act="rename">Rename</button><button type="button" class="btn btn-white lp-del" data-act="delete">Delete</button></div>'
            + (VIEW.mode === 'fresh' ? '<button type="button" class="btn btn-white" data-act="again">Plan another field</button>' : '');
        $('lpSeg').classList.toggle('is-lot', VIEW.tab === 'lot');
        fitGantt();
    }

    function farmHtml(r, a) {
        const lots = r.lots || [];
        const face = OPT && OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '🤖';
        let h = '<div class="lp-c"><h3>' + face + "Anee's reading" + (a.confidence ? '<em>Confidence: ' + esc(a.confidence) + '</em>' : '') + '</h3><p class="lp-p">' + esc(a.summary || '') + '</p>'
            + ((a.analysis || []).length ? '<div class="lp-an">' + a.analysis.map((x) => '<div><span>' + (TOPIC[x.topic] || '•') + '</span><span><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p></span></div>').join('') + '</div>' : '') + '</div>';
        // What runs late, and what to do.
        const late = (r.conflicts || []).filter((c) => c.major !== false);
        const moved = lots.map((l, i) => [l, i]).filter(([l]) => l.shift > 0);
        if (late.length || moved.length) {
            h += '<div class="lp-c lp-warnc"><h3>⚠ The crew and your dates</h3><ul>'
                + moved.map(([l, i]) => '<li style="--lc:' + lc(i) + '"><i></i><span>Anee moved <b>' + esc(l.name) + '</b> from ' + esc(day(l.asked)) + ' to <b>' + esc(day(l.planting)) + '</b>, ' + l.shift + (l.shift === 1 ? ' day' : ' days') + ' later, so the crew can finish it.</span></li>').join('')
                + late.map((c) => '<li style="--lc:' + lc(c.lot) + '"><i></i><span><b>' + esc((lots[c.lot] || {}).name || '') + ':</b> ' + esc(stepOf(r, c.key).what) + ' ' + (c.why === 'past' ? 'should have started already, its window has passed' : 'runs ' + (c.late ? c.late + (c.late === 1 ? ' day' : ' days') : 'well') + ' late') + '.</span></li>').join('')
                + '</ul>' + (a.crew ? '<p class="lp-p">' + esc(a.crew) + '</p>' : '') + '</div>';
        } else if (a.crew) {
            h += '<div class="lp-c"><h3>👷 Your crew</h3><p class="lp-p">' + esc(a.crew) + '</p></div>';
        }
        h += '<div class="lp-c"><h3>📊 Across your lots</h3>' + ganttHtml(r) + '<div class="lp-glegend"><span><i style="background:#4a7c2a"></i>Machine work</span><span><i style="background:#d97706"></i>Hand work</span><span><i style="background:#0284c7"></i>Seedbed</span><span>🌱 Planting day</span><span><i style="background:#ef4444;width:2px;height:.8rem"></i>Today</span></div></div>';
        h += crewHtml(r);
        h += seedHtml(r, null);
        h += weatherHtml(r, a);
        if ((a.risks || []).length) {
            h += '<div class="lp-c"><h3>👀 What to watch</h3>' + a.risks.map((x) => '<div class="lp-risk"><span class="is-' + esc(x.level) + '">' + esc(x.level) + '</span><div><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p>' + (x.when ? '<small>' + esc(x.when) + '</small>' : '') + '</div></div>').join('') + '</div>';
        }
        if ((r.rules || []).length) {
            h += '<div class="lp-c"><h3>📋 Good to know for this field</h3><ul class="lp-rules">' + r.rules.map((n) => '<li><span>' + (n.level === 'warn' ? '⚠️' : '💡') + '</span><span>' + esc(n.text) + '</span></li>').join('') + '</ul></div>';
        }
        h += '<div class="lp-c"><h3>🧭 Where this comes from</h3><p class="lp-fineprint">' + ((r.crop || {}).key === 'rice'
            ? 'The base steps follow PhilRice practice, the PalayCheck System of PhilRice (2022 revised edition), and IRRI practice for dry seeded rice, in our own words.'
            : 'The base steps follow DA and university extension practice, in our own words.')
            + " Steps marked Anee's suggestion are added for this field, each with its reason. The crew times are typical work rates, so treat them as a guide and adjust to your own pace."
            + ((a.dataGaps || []).length ? ' What would sharpen this plan: ' + esc(a.dataGaps.join(' ')) : '') + '</p></div>';
        return h;
    }

    /* The timeline: one row per lot, its crew work as bars, lanes so none overlap. */
    function ganttHtml(r) {
        const lots = r.lots || [];
        let lo = null, hi = null;
        (r.perLot || []).forEach((pl) => (pl.steps || []).forEach((s) => { if (s.start) { lo = lo === null || s.start < lo ? s.start : lo; hi = hi === null || s.end > hi ? s.end : hi; } }));
        lots.forEach((l) => { hi = hi === null || l.planting > hi ? l.planting : hi; lo = lo === null || l.planting < lo ? l.planting : lo; });
        if (!lo) return '<p class="lp-p">No crew work to lay out.</p>';
        lo = ds(Math.min(dn(lo), dn(TODAY))) ;
        const n0 = dn(lo), N = dn(hi) - n0 + 3;
        let head = '';
        for (let d = 0; d < N; d++) { const s = ds(n0 + d); if (new Date(s + 'T00:00:00Z').getUTCDay() === 1 || d === 0) head += '<span style="left:calc(var(--dw) * ' + d + ')">' + esc(fmt(s, { month: 'short', day: 'numeric' })) + '</span>'; }
        let weekend = '';
        for (let d = 0; d < N; d++) if (new Date(ds(n0 + d) + 'T00:00:00Z').getUTCDay() === 0) weekend += '<i class="lp-g-weekend" style="left:calc(var(--dw) * ' + d + ');width:var(--dw)"></i>';
        const rows = (r.perLot || []).map((pl) => {
            const lot = lots[pl.lot] || {};
            const bars = (pl.steps || []).filter((s) => s.start).sort((x, y) => (x.start < y.start ? -1 : 1));
            const lanes = [];
            const html = bars.map((s) => {
                const a = dn(s.start) - n0, b = dn(s.end) - n0;
                let lane = lanes.findIndex((end) => end < a);
                if (lane < 0) { lane = lanes.length; lanes.push(b); } else lanes[lane] = b;
                const kind = ['seedbed_site', 'seedbed_beds', 'sow_seedbed', 'nursery_fill', 'sow_nursery', 'backup_seedbed', 'nursery'].includes(s.key) ? 'seed' : (s.crew === 'machine' ? 'machine' : 'manual');
                return '<span class="lp-g-bar is-' + kind + (s.late ? ' is-late' : '') + '" data-tip="' + esc((lots[pl.lot] || {}).name + ': ' + stepOf(r, s.key).what + ', ' + day(s.start) + (s.end !== s.start ? ' to ' + day(s.end) : '')) + '" style="left:calc(var(--dw) * ' + a + ');width:calc(var(--dw) * ' + (b - a + 1) + ' - 2px);top:calc(.45rem + ' + lane + ' * 1.35rem);transition-delay:' + (60 + a * 6) + 'ms" title="' + esc(stepOf(r, s.key).what + ': ' + day(s.start) + (s.end !== s.start ? ' to ' + day(s.end) : '')) + '">' + ((b - a + 1) >= 3 ? esc(short(r, s.key)) : '') + '</span>';
            }).join('');
            const h = Math.max(1, lanes.length) * 1.35 + .9;
            return '<div class="lp-g-row" style="--lc:' + lc(pl.lot) + '"><div class="lp-g-lab"><b><i></i>' + esc(lot.name || '') + '</b><small>🌱 ' + esc(fmt(lot.planting, { month: 'short', day: 'numeric' })) + '</small></div>'
                + '<div class="lp-g-track" style="width:calc(var(--dw) * ' + N + ');height:' + h + 'rem">' + weekend + html + '<i class="lp-g-plant" style="left:calc(var(--dw) * ' + (dn(lot.planting) - n0) + ' + var(--dw) / 2)"></i></div></div>';
        }).join('');
        const t = dn(TODAY) - n0;
        return '<div class="lp-gantt" id="lpGantt" style="--dw:18px"><div class="lp-g-in" style="width:calc(7rem + var(--dw) * ' + N + ')" data-days="' + N + '"><div class="lp-g-head">' + head + '</div>' + rows
            + (t >= 0 && t < N ? '<i class="lp-g-today" style="left:calc(7rem + var(--dw) * ' + t + ' + var(--dw) / 2)"></i>' : '') + '</div></div>';
    }
    function fitGantt() {
        const g = $('lpGantt');
        if (!g) return;
        const n = Number(g.querySelector('.lp-g-in').dataset.days) || 30;
        const w = g.clientWidth - 112;
        g.style.setProperty('--dw', Math.max(16, Math.min(34, Math.floor(w / n))) + 'px');
    }
    window.addEventListener('resize', () => { if (!$('lpView').hidden) fitGantt(); });
    /* Slides under a mouse too. */
    document.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse' || e.button !== 0) return;
        const box = e.target.closest('.lp-gantt');
        if (!box || box.scrollWidth <= box.clientWidth) return;
        const x0 = e.clientX, l0 = box.scrollLeft;
        const move = (ev) => { box.scrollLeft = l0 - (ev.clientX - x0); };
        const up = () => { document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', up); };
        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
    });

    /* The crew calendar: day by day, week by week. */
    function crewHtml(r) {
        const lots = r.lots || [];
        const days = {};
        Object.entries(r.calendar || {}).forEach(([d, items]) => { days[d] = (days[d] || []).concat(items.map((x) => Object.assign({ crew: true }, x))); });
        (r.plantDays || []).forEach((p) => { days[p.date] = (days[p.date] || []).concat([Object.assign({ plant: true }, p)]); });
        const keys = Object.keys(days).sort();
        if (!keys.length) return '';
        const busiest = Math.max(...Object.values(r.calendar || {}).map((items) => items.reduce((s, x) => s + (x.people || 0), 0)), 0);
        let h = '<div class="lp-c"><h3>👷 Crew calendar<em>' + keys.length + ' days</em></h3><p class="lp-p" style="font-size:.8rem;margin:-.3rem 0 .7rem">Who does what each day. ' + (busiest ? 'The busiest day needs ' + busiest + ' of your ' + ((r.crew || {}).workers || '') + ' workers.' : '') + ' Planting days show the extra hands they take.</p><div id="lpCrewDays">';
        let week = null, shown = 0;
        keys.forEach((d) => {
            const monday = ds(dn(d) - ((new Date(d + 'T00:00:00Z').getUTCDay() + 6) % 7));
            const hide = shown >= 12;
            if (monday !== week) { week = monday; h += '<div class="lp-week"' + (hide ? ' data-more hidden' : '') + '><b>Week of ' + esc(fmt(monday, { month: 'short', day: 'numeric' })) + '</b>'; }
            const items = days[d];
            const plant = items.some((x) => x.plant);
            h += '<div class="lp-day' + (plant ? ' is-plant' : '') + '"' + (hide ? ' data-more hidden' : '') + '><span class="lp-dbox"><small>' + esc(fmt(d, { weekday: 'short' })) + '</small><b>' + esc(fmt(d, { day: 'numeric' })) + '</b></span><span class="lp-dchips">'
                + items.map((x) => x.plant
                    ? '<span class="lp-cc is-plant" style="--lc:' + lc(x.lot) + '"><i></i>' + esc((lots[x.lot] || {}).name || '') + ' · ' + esc(short(r, x.key)) + ' <em>about ' + x.people + ' ' + esc(x.words) + '</em></span>'
                    : '<span class="lp-cc" style="--lc:' + lc(x.lot) + '"><i></i>' + esc((lots[x.lot] || {}).name || '') + ' · ' + esc(short(r, x.key)) + ' <em>' + (x.machine ? '🚜' : '') + (x.people ? ' 👷' + x.people : '') + '</em></span>').join('')
                + '</span></div>';
            shown++;
            const nx = keys[keys.indexOf(d) + 1];
            if (!nx || ds(dn(nx) - ((new Date(nx + 'T00:00:00Z').getUTCDay() + 6) % 7)) !== week) h += '</div>';
        });
        h += '</div>' + (keys.length > 12 ? '<button type="button" class="lp-showall" data-act="crewall">Show all ' + keys.length + ' days</button>' : '') + '</div>';
        return h;
    }

    /* The seed: hours, amounts, and each lot's timed reminders. */
    function seedHtml(r, onlyLot) {
        const p = r.seed || {};
        if (!p.found || !p.applies || !p.soakHours) {
            return onlyLot === null ? '<div class="lp-c"><h3>🌾 Seed preparation</h3><p class="lp-p">' + esc(p.say || '') + '</p></div>' : '';
        }
        const lots = r.lots || [];
        const co = p.confidence === 'company';
        let h = '<div class="lp-c"><h3>🌾 Seed preparation<em>' + (co ? (p.source === 'brand' ? 'Seed company' : 'PhilRice') : 'General practice') + '</em></h3>'
            + '<div class="lp-hours"><div class="lp-hour"><b>' + p.soakHours + ' h</b><small>soak</small></div><div class="lp-hour"><b>' + (p.refreshEveryHours ? 'every ' + p.refreshEveryHours + ' h' : 'none given') + '</b><small>change the water</small></div><div class="lp-hour"><b>' + p.incubateRange[0] + ' to ' + p.incubateRange[1] + ' h</b><small>incubate</small></div></div>'
            + '<p class="lp-p" style="font-size:.84rem;margin-top:.7rem">' + esc(p.say) + (p.refreshNote ? ' ' + esc(p.refreshNote) : '') + (p.floatRule ? ' ' + esc(p.floatRule) : '') + '</p>';
        if (onlyLot === null && (p.amounts || []).length) {
            h += '<p class="lp-p" style="font-size:.84rem;margin-top:.4rem"><b>Seed to buy:</b> ' + p.amounts.map((x) => esc((lots[x.lot] || {}).name || '') + ' ' + (x.lo === x.hi ? x.lo : x.lo + ' to ' + x.hi) + ' kg').join(', ') + '.</p>';
        } else if (onlyLot === null && p.rateNote) {
            h += '<p class="lp-p" style="font-size:.84rem;margin-top:.4rem">' + esc(p.rateNote) + '</p>';
        }
        if (onlyLot === null) {
            h += (p.timeline || []).map((t) => '<div class="lp-tl"><b class="lp-sgh" style="position:static;padding-left:0;--lc:' + lc(t.lot) + '">' + esc((lots[t.lot] || {}).name || '') + ', sowing ' + esc(day(t.sow)) + '</b>' + eventsHtml(t.events) + '</div>').join('');
            if ((p.steps || []).length) h += '<details class="lp-more-d"><summary>How to do it, step by step</summary><ol class="lp-steps-ol">' + p.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol></details>';
        }
        return h + '</div>';
    }
    function eventsHtml(events) {
        const out = [];
        (events || []).forEach((e) => {
            const last = out[out.length - 1];
            if (e.kind === 'water' && last && last.kind === 'water') { last.times.push(e.at); return; }
            out.push(Object.assign({ times: [e.at] }, e));
        });
        const ico = { soak: '💧', water: '🔁', incubate: '🧺', sow: '🌱' };
        return out.map((e) => '<div class="lp-ev"><span>' + (ico[e.kind] || '⏰') + '</span><div><b>' + esc(e.what) + '</b><small><em>' + esc(e.times.map((t, i) => (i === 0 || t.slice(0, 10) !== e.times[i - 1].slice(0, 10) ? day(t) + ', ' : '') + time(t)).join(', ')) + '</em>. ' + esc(e.how) + '</small></div></div>').join('');
    }

    function weatherHtml(r, a) {
        const w = r.weather || {};
        const fc = w.forecast || [];
        if (!w.place && !w.enso && !a.weather) return '';
        let h = '<div class="lp-c"><h3>🌦️ Weather and water' + (w.enso ? '<em>' + esc(w.enso) + '</em>' : '') + '</h3>';
        if (a.weather) h += '<p class="lp-p" style="margin-bottom:.7rem">' + esc(a.weather) + '</p>';
        if (fc.length) h += '<div class="lp-fc">' + fc.map((d) => '<div class="lp-fd"><small>' + esc(fmt(d.date, { weekday: 'short' })) + ' ' + esc(fmt(d.date, { day: 'numeric' })) + '</small><span>' + esc(d.emoji || '') + '</span><b>' + (d.max != null ? d.max + '°' : '') + '</b><i>' + (d.pop != null ? d.pop + '%' : '') + '</i></div>').join('') + '</div>';
        if (w.history || w.plowRain != null) {
            h += '<div class="lp-wx">' + (w.history ? '<div><b>' + w.history.rain + ' mm</b><small>rain over the prep, a ' + w.history.years + ' year average</small></div><div><b>' + w.history.dry + ' to ' + w.history.wet + '</b><small>mm, driest to wettest year</small></div>' : '')
                + (w.plowRain != null ? '<div><b>' + w.plowRain + ' mm</b><small>in the 3 plowing weeks</small></div>' : '') + '</div>';
        }
        if (w.place) h += '<p class="lp-fineprint" style="margin-top:.6rem">At ' + esc(w.place.label) + '. Forecast and history from Open-Meteo, ENSO from NOAA.</p>';
        return h + '</div>';
    }

    /* One lot, step by step. */
    function lotHtml(r, i) {
        const lots = r.lots || [];
        const lot = lots[i] || {};
        const pl = (r.perLot || [])[i] || { steps: [] };
        const cal = r.calendar || {};
        const people = {};
        Object.values(cal).forEach((items) => items.forEach((x) => { if (x.lot === i) { const k = x.key; people[k] = Math.max(people[k] || 0, x.people || 0); } }));
        const plant = (r.plantDays || []).filter((p) => p.lot === i);
        const word = r.dayWord || 'planting';
        let h = '<div class="lp-lotpick" id="lpLotPick">' + lots.map((l, j) => '<button type="button" class="' + (j === i ? 'is-on' : '') + '" data-lot="' + j + '" style="--lc:' + lc(j) + '"><i></i>' + esc(l.name) + '</button>').join('') + '</div>';
        const amt = ((r.seed || {}).amounts || []).find((x) => x.lot === i);
        h += '<div class="lp-c"><div class="lp-lothead" style="--lc:' + lc(i) + '"><i>' + esc((r.crop || {}).icon || '🌱') + '</i><div><b>' + esc(lot.name || '') + '</b><small>' + ha(Number(lot.area || 0)) + ' · ' + esc(word) + ' ' + esc(lot.planting ? fmt(lot.planting, { weekday: 'long', month: 'long', day: 'numeric' }) : '') + (amt ? ' · seed ' + (amt.lo === amt.hi ? amt.lo : amt.lo + ' to ' + amt.hi) + ' kg' : '') + '</small></div></div>'
            + (lot.shift ? '<p class="lp-moved">Anee moved this lot ' + lot.shift + (lot.shift === 1 ? ' day' : ' days') + ' later than the ' + esc(day(lot.asked)) + ' you asked, so the crew can finish it in time.</p>' : '') + '</div>';
        // The steps and the seed reminders, in date order.
        const items = (pl.steps || []).map((s) => ({ at: s.start || s.winFrom, s }));
        const tl = ((r.seed || {}).timeline || []).find((t) => t.lot === i);
        const evs = [];
        ((tl && tl.events) || []).forEach((e) => { const last = evs[evs.length - 1]; if (e.kind === 'water' && last && last.e.kind === 'water') { last.times.push(e.at); return; } evs.push({ e, times: [e.at] }); });
        evs.forEach((x) => items.push({ at: x.e.at.slice(0, 10), ev: x }));
        items.sort((x, y) => (x.at < y.at ? -1 : (x.at > y.at ? 1 : (x.ev ? -1 : 1))));
        h += '<div class="lp-list">' + items.map((x, k) => (x.ev ? evHtml(x.ev) : stepHtml(r, x.s, people[x.s.key], plant, word))).join('') + '</div>';
        return h;
    }
    function evHtml(x) {
        const ico = { soak: '💧', water: '🔁', incubate: '🧺', sow: '🌱' };
        return '<div class="lp-ts"><span>' + (ico[x.e.kind] || '⏰') + '</span><div><b>' + esc(x.e.what) + '</b><small>' + esc(x.times.map((t, i) => (i === 0 || t.slice(0, 10) !== x.times[i - 1].slice(0, 10) ? day(t) + ', ' : '') + time(t)).join(', ')) + '</small></div></div>';
    }
    function stepHtml(r, row, ppl, plant, word) {
        const s = stepOf(r, row.key);
        const anee = s.tag === 'anee';
        const at = row.start || row.winFrom;
        let when;
        if (row.start) {
            const eq = (r.crew || {}).equipmentLabel || 'machine';
            when = 'Crew: ' + day(row.start) + (row.end !== row.start ? ' to ' + day(row.end) : '') + (row.crew === 'machine' ? ', ' + String(eq).toLowerCase() : (ppl ? ', ' + ppl + (ppl === 1 ? ' person' : ' people') : ''));
        } else if (row.crew && !row.start) {
            when = 'The crew could not fit this in.';
        } else if (row.planting) {
            const pd = plant.find((p) => p.key === row.key);
            when = (row.winFrom === row.winTo ? day(row.winFrom) : day(row.winFrom) + ' or ' + day(row.winTo)) + (pd ? ', about ' + pd.people + ' ' + pd.words : '');
        } else {
            when = row.winFrom === row.winTo ? 'On ' + day(row.winFrom) : 'Any day from ' + day(row.winFrom) + ' to ' + day(row.winTo);
        }
        const tags = '<span class="lp-tg is-' + esc(s.tag || 'extension') + '">' + (anee ? '✦ ' : '') + esc(TAG[s.tag] || '') + '</span>'
            + (s.reason && REASON[s.reason] ? '<span class="lp-tg is-reason">' + esc(REASON[s.reason]) + '</span>' : '')
            + (s.lotsWords ? '<span class="lp-tg is-reason">' + esc(s.lotsWords) + '</span>' : '')
            + (row.late ? '<span class="lp-tg is-late">' + row.late + (row.late === 1 ? ' day late' : ' days late') + '</span>' : '')
            + (row.past ? '<span class="lp-tg is-past">Window passed</span>' : '');
        return '<div class="lp-s' + (anee ? ' is-anee' : '') + (row.past ? ' is-past' : '') + '"><span class="lp-cal1"><i>' + esc(fmt(at, { month: 'short' })) + '</i><b>' + esc(fmt(at, { day: 'numeric' })) + '</b><small>' + esc(fmt(at, { weekday: 'short' })) + '</small></span>'
            + '<div class="lp-sb"><b>' + esc(s.what) + '</b><div class="lp-tags">' + tags + '</div><p class="lp-when">' + esc(when) + '</p>'
            + (s.how ? '<p class="lp-how">' + esc(s.how) + (s.rule ? ' ' + esc(s.rule) : '') + '</p>' : '')
            + (s.why ? '<p class="lp-why"><b>Why:</b> ' + esc(s.why) + '</p>' : '')
            + (s.note ? '<p class="lp-note">' + (OPT && OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '') + '<span>' + esc(s.note) + '</span></p>' : '') + '</div></div>';
    }

    $('lpViewBody').addEventListener('click', async (e) => {
        const t = e.target.closest('[data-tab]');
        if (t && VIEW) {
            if (VIEW.tab === t.dataset.tab) return;
            VIEW.tab = t.dataset.tab;
            $('lpSeg').classList.toggle('is-lot', VIEW.tab === 'lot');
            $('lpSeg').querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('is-on', b === t));
            const on = VIEW.tab === 'farm' ? $('lpFarm') : $('lpLot'), off = VIEW.tab === 'farm' ? $('lpLot') : $('lpFarm');
            off.hidden = true; on.hidden = false;
            on.classList.remove('is-in'); void on.offsetWidth; on.classList.add('is-in');
            if (VIEW.tab === 'farm') fitGantt();
            const segTop = $('lpSeg').getBoundingClientRect().top;
            if (segTop < 0 || segTop > 120) $('lpSeg').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
            return;
        }
        const tip = e.target.closest('[data-tip]');
        if (tip) { window.toast?.(tip.dataset.tip, 'info'); return; }
        const l = e.target.closest('[data-lot]');
        if (l && VIEW) {
            VIEW.lot = Number(l.dataset.lot);
            const pane = $('lpLot');
            pane.innerHTML = lotHtml(VIEW.item.report || {}, VIEW.lot);
            pane.classList.remove('is-in'); void pane.offsetWidth; pane.classList.add('is-in');
            pane.querySelector('#lpLotPick .is-on')?.scrollIntoView({ inline: 'center', block: 'nearest' });
            return;
        }
        const a = e.target.closest('[data-act]');
        if (!a || !VIEW) return;
        const act = a.dataset.act;
        if (act === 'crewall') { $('lpCrewDays').querySelectorAll('[data-more]').forEach((x) => { x.hidden = false; }); a.remove(); return; }
        if (act === 'send') { openPort(); return; }
        if (act === 'rename') { openRename(VIEW.item); return; }
        if (act === 'again') { closeView(); setTimeout(() => { tab('plan'); show(0); }, 320); return; }
        if (act === 'delete') {
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this plan?', message: 'Only the plan is deleted. The credits are not refunded.', confirmText: 'Delete' }) : confirm('Delete this plan?');
            if (!ok) return;
            try {
                const res = await window.api(U.del(VIEW.item.id), { method: 'DELETE' });
                window.toast?.(res.message);
                LIST.stale = true;
                VIEW.mode = 'saved';
                closeView();
                if (!$('lpListPane').hidden) loadList(false);
            } catch (err) { window.toast?.(err.message, 'error'); }
        }
    });
    $('lpViewSend').addEventListener('click', () => openPort());
    $('lpViewName').addEventListener('click', () => VIEW && openRename(VIEW.item));

    /* ---------------- rename ---------------- */
    let REN = null;
    function openRename(item) {
        REN = item;
        $('lpRenInput').value = item.title || '';
        sheetOpen($('lpRenSheet'), $('lpRenInput'));
    }
    $('lpRenForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!REN) return;
        const title = $('lpRenInput').value.trim();
        if (!title) return;
        $('lpRenSave').disabled = true;
        try {
            await window.api(U.meta, { method: 'POST', body: { id: REN.id, title } });
            REN.title = title;
            if (VIEW && VIEW.item.id === REN.id) $('lpViewTitle').textContent = title;
            const card = document.querySelector('.lp-card[data-id="' + REN.id + '"] .lp-card-t b');
            if (card) card.textContent = title;
            (LIST.rows || []).forEach((r) => { if (r.id === REN.id) r.title = title; });
            sheetClose($('lpRenSheet'));
            window.toast?.('Renamed.');
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { $('lpRenSave').disabled = false; }
    });

    /* ---------------- send to the Protocol Builder ---------------- */
    const PORT = { mode: 'new', pid: null, rows: null };
    async function openPort() {
        if (!VIEW) return;
        PORT.mode = 'new'; PORT.pid = null;
        $('lpPortForm').hidden = false; $('lpPortDone').hidden = true; $('lpPortGo').hidden = false; $('lpPortOpen').hidden = true; $('lpPortClose').textContent = 'Cancel';
        $('lpPortTitleIn').value = 'Land prep: ' + (VIEW.item.title || '');
        paintPortMode();
        sheetOpen($('lpPortSheet'));
        try { PORT.rows = (await window.api(U.protocols)).data.rows || []; } catch (_) { PORT.rows = []; }
        paintPortList();
    }
    function paintPortMode() {
        $('lpPortForm').querySelectorAll('[data-pmode]').forEach((b) => b.classList.toggle('is-on', b.dataset.pmode === PORT.mode));
        $('lpPortNew').hidden = PORT.mode !== 'new';
        $('lpPortOld').hidden = PORT.mode !== 'existing';
    }
    function paintPortList() {
        const crop = ((VIEW && VIEW.item.report && VIEW.item.report.crop) || {}).key;
        const rows = (PORT.rows || []).slice().sort((a, b) => ((b.crop === crop) - (a.crop === crop)));
        $('lpPortList').innerHTML = rows.length ? rows.map((p) => '<button type="button" class="lp-row" role="option" data-pid="' + p.id + '" aria-selected="' + (PORT.pid === p.id) + '"><span class="ri">' + esc(p.cropIcon || '🌱') + '</span><span class="rt"><b>' + esc(p.title) + '</b><small>' + esc([p.cropLabel, p.dayType === 'TREE' ? 'DOS' : p.dayType, 'updated ' + p.updated].filter(Boolean).join(' · ')) + '</small></span></button>').join('')
            : '<p class="lp-snone">You have no protocols yet. Make a new one.</p>';
    }
    $('lpPortForm').addEventListener('click', (e) => {
        const m = e.target.closest('[data-pmode]');
        if (m) { PORT.mode = m.dataset.pmode; paintPortMode(); return; }
        const p = e.target.closest('[data-pid]');
        if (p) { PORT.pid = Number(p.dataset.pid); $('lpPortList').querySelectorAll('[data-pid]').forEach((b) => b.setAttribute('aria-selected', String(b === p))); }
    });
    $('lpPortGo').addEventListener('click', async () => {
        if (!VIEW) return;
        if (PORT.mode === 'existing' && !PORT.pid) { window.toast?.('Pick the protocol to add the steps to.', 'error'); return; }
        $('lpPortGo').disabled = true;
        try {
            const res = await window.api(U.port, { method: 'POST', body: { id: VIEW.item.id, mode: PORT.mode, protocolId: PORT.pid, title: $('lpPortTitleIn').value.trim() } });
            $('lpPortForm').hidden = true; $('lpPortDone').hidden = false;
            $('lpPortDoneT').textContent = res.data.added ? 'Sent' : 'Already there';
            $('lpPortDoneP').textContent = res.message;
            $('lpPortGo').hidden = true;
            $('lpPortOpen').href = res.data.url; $('lpPortOpen').hidden = false;
            $('lpPortClose').textContent = 'Close';
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { $('lpPortGo').disabled = false; }
    });

    /* ---------------- your plans ---------------- */
    const LIST = { page: 1, q: '', rows: [], hasMore: false, stale: true, busy: false };
    async function loadList(more) {
        if (LIST.busy) return;
        LIST.busy = true;
        try {
            const page = more ? LIST.page + 1 : 1;
            const res = await window.api(U.list + '?page=' + page + '&q=' + encodeURIComponent(LIST.q) + '&_=' + Date.now());
            const rows = res.data.rows || [];
            LIST.page = page; LIST.hasMore = !!res.data.hasMore; LIST.rows = more ? LIST.rows.concat(rows) : rows; LIST.stale = false;
            const html = rows.map((r, k) => '<div class="lp-card" role="button" tabindex="0" data-id="' + r.id + '" style="animation-delay:' + (k * 40) + 'ms">'
                + '<div class="lp-card-top"><span class="lp-card-ico">' + esc(r.icon) + '</span><span class="lp-card-t"><b>' + esc(r.title) + '</b><small>' + esc(r.method) + '</small></span></div>'
                + '<div class="lp-card-meta">' + (r.planting ? '<span>📅 ' + esc(r.planting) + '</span>' : '') + '<span>🟩 ' + r.lots + (r.lots === 1 ? ' lot' : ' lots') + ', ' + ha(Number(r.area || 0)) + '</span><span>Made ' + esc(r.at) + '</span></div>'
                + '<button type="button" class="lp-card-pen" data-pen="' + r.id + '" aria-label="Rename ' + esc(r.title) + '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg></button></div>').join('');
            if (more) $('lpCards').insertAdjacentHTML('beforeend', html); else $('lpCards').innerHTML = html;
            $('lpEmpty').hidden = LIST.rows.length > 0;
            $('lpEmpty').querySelector('b').textContent = LIST.q ? 'No plans match' : 'No plans yet';
            $('lpMore').hidden = !LIST.hasMore;
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { LIST.busy = false; }
    }
    $('lpMore').addEventListener('click', () => loadList(true));
    let findT = null;
    $('lpFind').addEventListener('input', () => { clearTimeout(findT); findT = setTimeout(() => { LIST.q = $('lpFind').value.trim(); loadList(false); }, 280); });
    async function openSaved(id) {
        try { const res = await window.api(U.one(id)); openView(res.data.item, 'saved'); }
        catch (err) { console.error(err); window.toast?.(err.message, 'error'); }
    }
    $('lpCards').addEventListener('click', (e) => {
        const pen = e.target.closest('[data-pen]');
        if (pen) { e.stopPropagation(); const r = LIST.rows.find((x) => String(x.id) === pen.dataset.pen); if (r) openRename(r); return; }
        const c = e.target.closest('.lp-card');
        if (c) openSaved(c.dataset.id);
    });
    $('lpCards').addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.classList.contains('lp-card')) openSaved(e.target.dataset.id); });

    function tab(which) {
        $('lpPlanPane').hidden = which !== 'plan';
        $('lpListPane').hidden = which !== 'list';
        $('lpTabPlan').classList.toggle('is-on', which === 'plan');
        $('lpTabList').classList.toggle('is-on', which === 'list');
        $('lpTabPlan').setAttribute('aria-selected', String(which === 'plan'));
        $('lpTabList').setAttribute('aria-selected', String(which === 'list'));
        if (which === 'list' && LIST.stale) loadList(false);
    }
    $('lpTabPlan').addEventListener('click', () => tab('plan'));
    $('lpTabList').addEventListener('click', () => tab('list'));

    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
