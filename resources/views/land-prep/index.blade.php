@extends('layouts.app')
@section('title', 'Land Prep Planner')
@section('page-title', 'Land Prep Planner')
@section('page-subtitle', 'Every lot ready on planting day')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- LAND PREP PLANNER, by Anee (2026-10-14, round two 2026-10-15).

     A wizard, one question a screen: the crop, how it goes in, the variety
     (a new one can be added, and Anee looks it up), the seed (rice), the
     planting day (a warning when the time is short, never a refusal), the
     lots (each a card, its size in the unit the farmer knows), the machines
     (photos, owned and hired counts), the workers (each with their own
     days, and the crew Anee recommends), then for EVERY lot its soil,
     water, acidity, last season, weeds and troubles (the shared field
     profile, partials/field-profile, with "Same as Lot 1"), then the place.
     Anee's plan opens full screen: the whole farm (a timeline of every lot,
     the crew and its jobs, the seed with its hours, the weather, what to
     watch) and each lot step by step, every step tagged with where it comes
     from. The arithmetic is the server's (App\Support\LandPrepPlanner); the
     page draws it. Saved plans live on "Your plans", renamable, deletable,
     and any plan can be sent to the Protocol Builder. --}}

@section('content')
<style>
    /* The colours live on every root the page draws into: the page, its sheets and the full screen plan. */
    .lp, .lp-sheet, .lp-view { --lp-e: cubic-bezier(.22,1,.36,1); --lp-g: #4a7c2a; --lp-gd: #2d5016; --lp-gl: #e4efd4; --lp-gx: #f4f9ee; }
    html.dark .lp-sheet, html.dark .lp-view { --lp-gx: #1a2414; --lp-gl: #2b3a1c; --lp-gd: #cfe6b8; }
    .lp { max-width: 40rem; margin: 0 auto; min-width: 0; }
    .lp-tabs { display: flex; gap: .35rem; padding: .3rem; margin-bottom: .9rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lp-tab { flex: 1 1 0; min-height: 2.6rem; border-radius: .75rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-500); transition: background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-tab.is-on { background: var(--lp-g); color: #fff; }

    /* The price card. */
    .lp-quote { display: flex; align-items: center; gap: .7rem; padding: .75rem .9rem; margin-bottom: .9rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; font-size: .82rem; line-height: 1.5; color: #3d5226; }
    .lp-quote img { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .lp-quote b { color: var(--lp-gd); }
    .lp-quote.is-off { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }

    /* The wizard. */
    .lp-wiz { position: relative; padding: 1.1rem 1.1rem 1rem; border-radius: 1.3rem; background: var(--color-white); border: 1px solid var(--color-gray-200); box-shadow: 0 18px 40px -32px rgb(20 33 12 / .45); min-width: 0; }
    .lp-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .2rem; min-width: 0; }
    .lp-head small { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--lp-g); }
    .lp-head span { flex: none; margin-left: auto; font-size: .72rem; font-weight: 800; color: var(--color-gray-400); }
    .lp-bar { height: .32rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; margin-bottom: 1rem; }
    .lp-bar i { display: block; height: 100%; width: 10%; border-radius: 999px; background: linear-gradient(90deg, #86b556, var(--lp-g)); transition: width .45s var(--lp-e); }
    .lp-step { display: none; min-width: 0; }
    /* Steps fade, never slide (2026-10-16, the owner). */
    .lp-step.is-on { display: block; animation: lpIn .26s ease .1s both; }
    .lp-step.is-back { animation-name: lpBack; }
    .lp-step.is-still { animation: none; }
    @keyframes lpIn { from { opacity: 0; } }
    @keyframes lpBack { from { opacity: 0; } }
    @keyframes lpPop { from { opacity: 0; transform: translateY(8px); } }
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

    /* A tag that opens a sheet (variety, date, unit, province, town). */
    .lp-tag { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .6rem .7rem .6rem .6rem; border-radius: 1rem; text-align: left; background: #f6f8f3; border: 1px solid #dfe8d4; color: #14210c;
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-tag:hover:not(:disabled) { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
    .lp-tag:disabled { opacity: .55; cursor: default; }
    .lp-tag .ti { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.3rem; background: #fff; border: 1px solid #e5ebdf; }
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

    .lp-label { display: block; margin: 1rem 0 .4rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    /* What a lot grows (2026-10-17, the owner): the lots before it, each a tap that takes the same. */
    .lp-sub-s { margin: -.15rem 0 .55rem; font-size: .78rem; }
    .lp-samepick { display: grid; gap: .45rem; margin: 0 0 .2rem; animation: lpIn .26s ease both; }
    .lp-spick { width: 100%; display: flex; align-items: center; gap: .65rem; padding: .6rem .75rem; border-radius: .95rem; text-align: left; background: var(--color-white); border: 1.5px dashed #c9dcb3;
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-spick:hover { border-color: #a8cc7e; }
    .lp-spick > i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .lp-spick > span:not(.ck) { min-width: 0; flex: 1; }
    .lp-spick b { display: block; font-size: .86rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-spick small { display: block; margin-top: .05rem; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .lp-spick .ck { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; opacity: 0; transform: scale(.5); transition: opacity .28s var(--lp-e), transform .28s var(--lp-e); }
    .lp-spick .ck svg { width: .75rem; height: .75rem; }
    .lp-spick.is-on { border-style: solid; border-color: var(--lp-g); background: var(--lp-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .lp-spick.is-on .ck { opacity: 1; transform: none; }
    html.dark .lp-spick { background: #151b12; border-color: #3d5a26; }
    html.dark .lp-spick b { color: #e8efe1; }
    html.dark .lp-spick small { color: #a9b89c; }
    html.dark .lp-spick.is-on { background: #1c2a14; border-color: #6b9f3d; }
    @media (prefers-reduced-motion: reduce) { .lp-samepick { animation: none; } .lp-spick, .lp-spick .ck { transition: none; } }
    .lp-label small { font-weight: 600; color: var(--color-gray-400); }
    .lp-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lp-chipb { min-height: 2.5rem; padding: .35rem .9rem; border-radius: 999px; font-size: .84rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-chipb:hover { border-color: #a8cc7e; }
    .lp-chipb.is-on { color: #fff; background: var(--lp-g); border-color: var(--lp-g); }
    .lp-hint { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #3d5226; background: var(--lp-gx); border: 1px solid #e1edd3; }
    .lp-hint.is-warn { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .lp-hint b { color: inherit; }
    .lp-ferr { margin-top: .45rem; font-size: .78rem; font-weight: 700; line-height: 1.4; color: #b91c1c; animation: lpPop .28s var(--lp-e) both; }
    .is-shake { animation: lpShake .42s var(--lp-e) both !important; }
    @keyframes lpShake { 20% { transform: translateX(-6px); } 40% { transform: translateX(5px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(2px); } }

    /* The seed card: its hours as cards, rows on a phone, side by side when there is room. */
    .lp-seed { margin-top: .9rem; padding: .85rem .9rem; border-radius: 1rem; background: #fff; border: 1px solid var(--color-gray-200); container-type: inline-size; min-width: 0; animation: lpPop .3s var(--lp-e) both; }
    .lp-seed-h { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .lp-seed-h b { font-size: .92rem; color: var(--color-gray-900); }
    .lp-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .14rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .02em; }
    .lp-badge.is-co { background: var(--lp-gl); color: var(--lp-gd); }
    .lp-badge.is-gen { background: #fef3c7; color: #92400e; }
    .lp-badge.is-none { background: var(--color-gray-100); color: var(--color-gray-600); }
    .lp-badge.is-anee { background: #ede9fe; color: #5b21b6; }
    .lp-hours { display: grid; gap: .5rem; margin-top: .75rem; }
    .lp-hour { display: flex; align-items: center; gap: .75rem; padding: .65rem .75rem; border-radius: .95rem; background: #f8faf6; border: 1px solid #edf1e8; min-width: 0; }
    .lp-hour > span { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.15rem; background: #fff; border: 1px solid #e3eadb; }
    .lp-hour > div { min-width: 0; }
    .lp-hour b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; line-height: 1.2; color: var(--color-gray-900); }
    .lp-hour em { display: block; font-style: normal; font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .lp-hour small { display: block; font-size: .72rem; line-height: 1.35; color: var(--color-gray-500); }
    @container (min-width: 33rem) {
        .lp-hours { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lp-hour { flex-direction: column; align-items: flex-start; gap: .45rem; }
    }
    .lp-seed p { margin-top: .65rem; font-size: .8rem; line-height: 1.55; color: var(--color-gray-600); }
    .lp-research { margin-top: .7rem; display: flex; gap: .6rem; padding: .65rem .75rem; border-radius: .9rem; background: #f5f3ff; border: 1px solid #e4dcfd; font-size: .8rem; line-height: 1.5; color: #4c1d95; }
    .lp-research > span:first-child { flex: none; font-size: 1.1rem; }
    .lp-research.is-miss { background: #fffbeb; border-color: #fde68a; color: #78350f; }

    /* Lots: each a card, each field on its own row on a phone. */
    .lp-lots { display: grid; gap: .7rem; }
    .lp-lot { position: relative; padding: .85rem .85rem .9rem; border-radius: 1.1rem; background: #fbfcfa; border: 1px solid var(--color-gray-200); border-left: 4px solid var(--lc); animation: lpPop .3s var(--lp-e) both; min-width: 0;
        transition: opacity .22s var(--lp-e), transform .22s var(--lp-e); }
    .lp-lot.is-gone { opacity: 0; transform: scale(.97); }
    .lp-lot-top { display: flex; align-items: center; gap: .45rem; margin-bottom: .6rem; padding-right: 2.2rem; min-width: 0; }
    .lp-lot-top i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .lp-lot-top b { flex: none; font-size: .86rem; font-weight: 900; color: var(--color-gray-800); }
    .lp-lot-top em { min-width: 0; font-style: normal; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-lot-x { position: absolute; top: .55rem; right: .55rem; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-400); transition: background-color .28s var(--lp-e), color .28s var(--lp-e); }
    .lp-lot-x:hover { color: #b91c1c; background: #fee2e2; }
    .lp-lot-x svg { width: .95rem; height: .95rem; }
    .lp-field { margin-top: .7rem; min-width: 0; }
    .lp-field:first-of-type { margin-top: 0; }
    .lp-field > label, .lp-field > span.lp-fl { display: block; margin-bottom: .3rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .lp-field > label small, .lp-field > span.lp-fl small { font-weight: 600; color: var(--color-gray-400); }
    .lp-field .form-input { width: 100%; min-height: 2.9rem; font-size: 1rem; transition: border-color .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-field.is-bad .form-input, .lp-field.is-bad .lp-unit { border-color: #ef4444; box-shadow: 0 0 0 3px rgb(239 68 68 / .14); }
    .lp-size { display: flex; gap: .5rem; min-width: 0; }
    .lp-size .form-input { flex: 1; min-width: 0; font-weight: 700; }
    .lp-unit { flex: none; display: inline-flex; align-items: center; gap: .35rem; min-height: 2.9rem; padding: 0 .75rem 0 .85rem; border-radius: .75rem; font-size: .9rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); border: 1.5px solid #cfe3b8;
        transition: background-color .28s var(--lp-e), border-color .28s var(--lp-e); }
    .lp-unit:hover { background: #d9e9c4; }
    .lp-unit svg { width: .8rem; height: .8rem; }
    .lp-size-eq { margin-top: .3rem; font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }
    .lp-own { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .lp-own button { min-height: 2.5rem; padding: .3rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--lp-gd); background: #fff; border: 1px solid #d2e5bd; transition: background-color .28s var(--lp-e); }
    .lp-own button:hover { background: var(--lp-gl); }
    .lp-own button.is-x { color: var(--color-gray-500); border-color: var(--color-gray-200); }
    .lp-lot-acts { display: grid; grid-template-columns: 1fr; gap: .5rem; margin-top: .8rem; }
    .lp-lot-acts .btn { min-height: 2.9rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; white-space: nowrap; font-size: .86rem; padding-left: .5rem; padding-right: .5rem; }
    .lp-lot-acts .btn svg { flex: none; width: 1.05rem; height: 1.05rem; }
    @media (max-width: 370px) { .lp-lot-acts { grid-template-columns: 1fr; } }
    .lp-total { margin-top: .6rem; font-size: .8rem; font-weight: 700; color: var(--color-gray-500); text-align: right; }
    #lpLotPlace[hidden] { display: none; }

    /* A lot from the farm's lots (2026-10-16): its name, place and answers come with it. */
    .lp-flot { display: flex; align-items: flex-start; gap: .65rem; min-width: 0; padding: .6rem .65rem; margin-bottom: .1rem; border-radius: .9rem; background: #fff; border: 1px solid #e3eadb; }
    .lp-flot > span:first-child { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.2rem; background: var(--lp-gx); border: 1px solid #e1edd3; }
    .lp-flot > span:first-child img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .lp-flot > div { flex: 1; min-width: 0; }
    .lp-flot b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .lp-flot small { display: block; margin-top: .05rem; font-size: .76rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .lp-flot small.is-none { color: #b45309; font-weight: 700; }
    .lp-flchips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .lp-flchips span { padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; line-height: 1.35; color: #3d5226; background: #eef5e6; }
    .lp-flchips span.is-ask { color: #92400e; background: #fef3c7; }
    .lp-flchips span.is-old { color: #92400e; background: #fef3c7; }
    .lp-fledit { flex: none; align-self: center; min-height: 2.4rem; padding: .3rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); transition: background-color .28s var(--lp-e); }
    .lp-fledit:hover { background: #d9e9c4; }
    .lp-flold { display: grid; gap: .5rem; margin-top: .55rem; padding: .65rem .75rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; animation: lpPop .3s var(--lp-e) both; }
    .lp-flold > div { display: flex; flex-wrap: wrap; gap: .45rem; }
    .lp-flold button { min-height: 2.4rem; padding: .3rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 800; border: 1.5px solid #f59e0b; color: #92400e; background: #fff; transition: background-color .28s var(--lp-e); }
    .lp-flold button.is-main { color: #fff; background: #d97706; border-color: #d97706; }
    .lp-lotsnone { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-radius: 1.1rem; background: var(--color-gray-50); border: 1px dashed var(--color-gray-300); animation: lpPop .3s var(--lp-e) both; }
    .lp-lotsnone > span { flex: none; font-size: 1.6rem; }
    .lp-lotsnone b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-lotsnone small { display: block; margin-top: .15rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .lp-farmlist { display: grid; align-content: start; gap: .55rem; padding-top: .2rem; }
    .lp-farmlist .fl-card[disabled] { cursor: default; opacity: .7; }
    .lp-farmlist .fl-card[disabled]:hover { transform: none; box-shadow: none; }
    .lp-farmlist .fl-chip.is-in { color: #fff; background: #4a7c2a; }
    .lp-notes-rev { margin-top: .4rem; }
    html.dark .lp-flot { background: #12170f; border-color: #2b3a1c; }
    html.dark .lp-flot > span:first-child { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lp-flot b, html.dark .lp-lotsnone b { color: #e8efe1; }
    html.dark .lp-flchips span { color: #cfe6b8; background: #22301a; }
    html.dark .lp-flchips span.is-ask, html.dark .lp-flchips span.is-old { color: #fcd34d; background: #3a2a0c; }
    html.dark .lp-fledit { background: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-flold { color: #fde68a; background: #2a2010; border-color: #5c4413; }
    html.dark .lp-flold button { background: #1a1408; color: #fcd34d; }
    html.dark .lp-lotsnone { background: #12170f; border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) {
        .lp-flold, .lp-lotsnone { animation: none !important; }
        .lp-fledit, .lp-flold button { transition: none !important; }
    }

    /* The machines: photo circles, owned and hired counters. */
    .lp-eqs { display: grid; gap: .6rem; }
    .lp-eq { display: flex; align-items: center; gap: .75rem; padding: .7rem .75rem; border-radius: 1.05rem; background: #fff; border: 1.5px solid var(--color-gray-200); animation: lpPop .3s var(--lp-e) both; min-width: 0; flex-wrap: wrap; }
    .lp-ph { flex: none; width: 3.4rem; height: 3.4rem; border-radius: 999px; overflow: hidden; display: grid; place-items: center; font-size: 1.5rem; background: #f3f6ef; box-shadow: 0 0 0 2px #fff, 0 0 0 3.5px #dbe8cc; }
    .lp-ph img { width: 100%; height: 100%; object-fit: cover; }
    .lp-eq .et { flex: 1; min-width: 8rem; }
    .lp-eq .et b { display: block; font-size: .94rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900); }
    .lp-eq .et small { display: block; font-size: .76rem; line-height: 1.35; color: var(--color-gray-500); }
    .lp-loc { display: inline-block; margin-left: .3rem; padding: .02rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; vertical-align: .1rem; color: var(--lp-gd); background: var(--lp-gl); }
    .lp-cts { display: flex; gap: .5rem; width: 100%; padding-left: 4.15rem; }
    .lp-ct { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: .3rem; min-height: 2.6rem; padding: .2rem .25rem .2rem .7rem; border-radius: .85rem; background: #f6f8f3; border: 1px solid #e6ece0; min-width: 0; }
    .lp-ct > span { font-size: .78rem; font-weight: 800; color: var(--color-gray-600); }
    .lp-ct > div { display: flex; align-items: center; gap: .15rem; }
    .lp-ct button { width: 2.1rem; height: 2.1rem; border-radius: .65rem; font-size: 1.1rem; font-weight: 800; color: var(--lp-gd); background: #fff; border: 1px solid #dbe6cf; transition: transform .2s var(--lp-e), background-color .28s var(--lp-e); }
    .lp-ct button:active { transform: scale(.92); }
    .lp-ct button:disabled { opacity: .35; }
    .lp-ct output { min-width: 1.6rem; text-align: center; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-ct.is-hire { background: #fff8eb; border-color: #fde9c0; }
    .lp-ct.is-hire > span { color: #92400e; }
    @media (max-width: 400px) { .lp-cts { padding-left: 0; } }
    .lp-eqadd { margin-top: .7rem; }

    /* Workers. */
    .lp-rec { display: flex; gap: .7rem; padding: .8rem .85rem; margin-bottom: 1rem; border-radius: 1.05rem; background: linear-gradient(120deg, #f3f8ec, #e8f1dc); border: 1px solid #d3e6bd; min-width: 0; animation: lpPop .3s var(--lp-e) both; }
    .lp-rec img, .lp-rec > span { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; object-fit: cover; display: grid; place-items: center; font-size: 1.2rem; background: #fff; box-shadow: 0 0 0 2px #fff; }
    .lp-rec > div { flex: 1; min-width: 0; }
    .lp-rec b { display: block; font-size: .95rem; font-weight: 800; color: #1f3510; }
    .lp-rec p { margin-top: .2rem; font-size: .78rem; line-height: 1.5; color: #3d5226; }
    .lp-rec .btn { margin-top: .55rem; min-height: 2.5rem; padding: .3rem .95rem; font-size: .84rem; }
    .lp-rec.is-wait b { color: #6b7280; font-weight: 700; }
    .lp-stepper { display: flex; align-items: center; gap: .6rem; }
    .lp-stepper button { width: 3rem; height: 3rem; border-radius: 1rem; font-size: 1.4rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); transition: transform .28s var(--lp-e), background-color .28s var(--lp-e); }
    .lp-stepper button:active { transform: scale(.94); }
    .lp-stepper output { min-width: 3.2rem; text-align: center; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-stepper span { font-size: .86rem; font-weight: 700; color: var(--color-gray-500); }
    .lp-wks { display: grid; gap: .55rem; margin-top: .8rem; }
    .lp-wk { padding: .65rem .7rem; border-radius: 1rem; background: #fbfcfa; border: 1px solid var(--color-gray-200); animation: lpPop .28s var(--lp-e) both; min-width: 0; transition: opacity .2s var(--lp-e), transform .2s var(--lp-e); }
    .lp-wk.is-gone { opacity: 0; transform: scale(.97); }
    .lp-wk-top { display: flex; align-items: center; gap: .55rem; min-width: 0; }
    .lp-wk-top > i { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .78rem; font-weight: 900; color: #fff; background: #6b9f3d; }
    .lp-wk-top input { flex: 1; min-width: 0; min-height: 2.5rem; padding: .35rem .65rem; border-radius: .7rem; border: 1.5px solid #e1e7da; background: #fff; font-size: .95rem; font-weight: 700; color: #14210c; transition: border-color .28s var(--lp-e); }
    .lp-wk-top input:focus { outline: none; border-color: #86b556; }
    .lp-wk-top small { flex: none; font-size: .74rem; font-weight: 800; color: var(--color-gray-500); }
    .lp-d7 { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; margin-top: .5rem; }
    .lp-d7 button { min-height: 2.35rem; border-radius: .7rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-500); background: #fff; border: 1.5px solid #e3e8dc; transition: background-color .28s var(--lp-e), color .28s var(--lp-e), border-color .28s var(--lp-e), transform .2s var(--lp-e); }
    .lp-d7 button:active { transform: scale(.94); }
    .lp-d7 button.is-on { color: #fff; background: var(--lp-g); border-color: var(--lp-g); }
    .lp-same { display: flex; justify-content: flex-end; margin-top: .55rem; }
    .lp-same button { min-height: 2.4rem; padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); transition: background-color .28s var(--lp-e); }
    .lp-same button:hover { background: #d9e9c4; }
    .lp-same { gap: .45rem; flex-wrap: wrap; }

    /* A lot's own crop, and its sheet (2026-10-16, the owner). */
    .lp-lcbody { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 0 1.15rem 1rem; }
    .lp-lcfind { margin: 0 0 .5rem !important; }
    .lp-lcvars { display: grid; gap: .1rem; max-height: 15rem; overflow-y: auto; overscroll-behavior: contain; padding: .1rem; border-radius: .9rem; border: 1px solid var(--color-gray-200); }
    .lp-lcvars:empty { display: none; }
    .lp-lot-crop { display: inline-flex; align-items: center; gap: .4rem; }
    html.dark .lp-lcvars { border-color: #2b3a1c; }

    /* Pay, the roster and who is hired (2026-10-16, the owner). */
    .lp-roster { margin-bottom: .8rem; }
    .lp-wk.is-roster { border-color: #cfe3b8; background: #f7fbf2; }
    .lp-wk-from { display: inline-flex; align-items: center; gap: .25rem; margin: .4rem 0 0 2.55rem; padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; color: var(--lp-gd); background: var(--lp-gl); }
    .lp-pay { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-top: .5rem; min-width: 0; }
    .lp-pay > span { flex: none; min-width: 2.2rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-600); }
    .lp-money { flex: 1 1 7rem; min-width: 6rem; display: flex; align-items: center; gap: .3rem; min-height: 2.5rem; padding: 0 .6rem; border-radius: .7rem; border: 1.5px solid #e1e7da; background: #fff; transition: border-color .28s var(--lp-e); }
    .lp-money:focus-within { border-color: #86b556; }
    .lp-money em { font-style: normal; font-weight: 800; color: var(--color-gray-500); }
    .lp-money input { flex: 1; min-width: 0; border: 0; background: transparent; padding: 0; font-size: .95rem; font-weight: 700; color: #14210c; outline: none; box-shadow: none; }
    .lp-unit2 { flex: none; display: inline-flex; gap: .15rem; padding: .2rem; border-radius: .8rem; background: #f1f5ec; max-width: 100%; flex-wrap: wrap; }
    .lp-unit2 button { min-height: 2.1rem; padding: 0 .65rem; border-radius: .6rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-500); transition: background-color .28s var(--lp-e), color .28s var(--lp-e), box-shadow .28s var(--lp-e); }
    .lp-unit2 button.is-on { color: var(--lp-gd); background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .1); }
    .lp-savenew { display: flex; align-items: flex-start; gap: .6rem; margin-top: .7rem; padding: .65rem .75rem; border-radius: .9rem; background: var(--lp-gx); border: 1px solid #e1edd3; cursor: pointer; animation: lpPop .28s var(--lp-e) both; }
    .lp-savenew input { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; accent-color: #4a7c2a; }
    .lp-savenew b { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-savenew small { display: block; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); }
    .lp-hire { width: 100%; display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .32s var(--lp-e), opacity .28s var(--lp-e); }
    .lp-hire.is-closed { grid-template-rows: 0fr; opacity: 0; }
    .lp-hire > div { min-height: 0; overflow: hidden; }
    .lp-hire-in { display: grid; gap: .45rem; margin: .55rem 0 0 4.15rem; padding: .7rem .75rem; border-radius: .9rem; background: #fffaf0; border: 1px solid #fde9c0; min-width: 0; }
    @media (max-width: 400px) { .lp-hire-in { margin-left: 0; } }
    .lp-hire-h { font-size: .76rem; font-weight: 800; color: #92400e; }
    .lp-hire-h small { font-weight: 700; color: #b45309; }
    .lp-hpick { display: flex; align-items: center; gap: .5rem; min-height: 2.6rem; padding: .3rem .75rem; border-radius: .8rem; font-size: .84rem; font-weight: 800; text-align: left; color: #92400e; background: #fff; border: 1.5px dashed #f5c97a; transition: background-color .28s var(--lp-e), border-color .28s var(--lp-e); }
    .lp-hpick:hover { background: #fff7e6; border-color: #e9a93f; }
    .lp-hchip { display: flex; align-items: center; gap: .55rem; padding: .35rem .4rem .35rem .45rem; border-radius: .85rem; background: #fff; border: 1px solid #fde1a8; min-width: 0; animation: lpPop .26s var(--lp-e) both; }
    .lp-hchip > i { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-weight: 900; color: #fff; background: #d97706; }
    .lp-hchip > span { flex: 1; min-width: 0; }
    .lp-hchip b { display: block; font-size: .86rem; font-weight: 800; color: #78350f; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-hchip small { display: block; font-size: .72rem; color: #a16207; }
    .lp-hchip button { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: #92400e; transition: background-color .28s var(--lp-e); }
    .lp-hchip button:hover { background: #fef3c7; }
    .lp-hchip button svg { width: 1rem; height: 1rem; }
    .lp-hire .lp-unit2 { background: #fdf0d5; }
    .lp-hire .lp-money { border-color: #f6dca6; }
    .lp-hire-note { font-size: .7rem; line-height: 1.4; color: #a16207; }
    /* The days of many lots (2026-10-16, the owner): the first lot's and Anee times the others, or each lot's own. */
    #lpDateMode { margin-bottom: 1rem; }
    .lp-dfor { display: flex; align-items: center; gap: .45rem; margin: 0 0 .45rem; font-size: .86rem; font-weight: 900; color: var(--color-gray-800); min-width: 0; }
    .lp-dfor i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .lp-dfor small { font-weight: 700; color: var(--color-gray-500); }
    .lp-dlots { display: grid; gap: .55rem; margin-top: .8rem; }
    .lp-dlot { padding: .7rem .75rem; border-radius: 1rem; background: #fbfcfa; border: 1px solid var(--color-gray-200); border-left: 4px solid var(--lc); animation: lpPop .3s var(--lp-e) both; min-width: 0; }
    .lp-dlot-h { display: flex; align-items: baseline; gap: .45rem; margin-bottom: .45rem; min-width: 0; }
    .lp-dlot-h i { flex: none; align-self: center; width: .65rem; height: .65rem; border-radius: 999px; background: var(--lc); }
    .lp-dlot-h b { min-width: 0; font-size: .86rem; font-weight: 900; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-dlot-h small { flex: none; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .lp-dlot-t { margin-top: .45rem; font-size: .76rem; font-weight: 700; line-height: 1.4; color: #92400e; }
    .lp-dauto { display: flex; gap: .65rem; margin-top: .8rem; padding: .75rem .8rem; border-radius: 1rem; background: #f5f3ff; border: 1px solid #e4dcfd; animation: lpPop .3s var(--lp-e) both; min-width: 0; }
    .lp-dauto > span:first-child { flex: none; font-size: 1.15rem; }
    .lp-dauto > span + span { flex: 1; min-width: 0; }
    .lp-dauto b { display: block; font-size: .86rem; font-weight: 800; color: #4c1d95; }
    .lp-dauto small { display: block; margin-top: .15rem; font-size: .76rem; line-height: 1.45; color: #5b21b6; }
    .lp-dclose { margin-top: .7rem; }
    /* Will every lot be ready on its day? (the Workers step and the review) */
    .lp-cast { margin-top: 1rem; transition: opacity .2s ease; }
    .lp-cast:empty { margin: 0; }
    .lp-cast.is-busy { opacity: .55; }
    .lp-cast-c { padding: .8rem .85rem; border-radius: 1.05rem; background: #f8faf6; border: 1px solid #dfe8d4; min-width: 0; animation: lpPop .3s var(--lp-e) both; }
    .lp-cast-c.is-warn { background: #fffdf5; border-color: #fde68a; }
    .lp-cast-h { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .lp-cast-h > span { flex: none; font-size: 1.1rem; }
    .lp-cast-h b { min-width: 0; font-size: .92rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-cast-c > p { margin-top: .2rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .lp-cast-rows { display: grid; gap: .35rem; margin-top: .6rem; }
    .lp-cast-row { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: start; gap: .5rem; font-size: .78rem; animation: lpPop .3s var(--lp-e) both; }
    .lp-cast-row > b { display: flex; align-items: center; gap: .35rem; padding-top: .42rem; font-weight: 800; color: var(--color-gray-700); white-space: nowrap; }
    .lp-cast-row > b i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc); }
    .lp-cast-row > span { min-width: 0; padding: .4rem .55rem; border-radius: .6rem; font-weight: 700; line-height: 1.4; overflow-wrap: anywhere; }
    .lp-cast-row > span small { display: block; margin-top: .12rem; font-size: .72rem; font-weight: 600; }
    .lp-cast-row .is-ok { color: #2d5016; background: #e9f3dd; }
    .lp-cast-row .is-late { color: #991b1b; background: #fde8e8; }
    .lp-cast-row .is-moved { color: #5b21b6; background: #efe9fd; }
    .lp-cast-do { display: grid; gap: .4rem; margin-top: .7rem; }
    .lp-cast-do p { font-size: .78rem; font-weight: 700; line-height: 1.45; color: var(--color-gray-700); }
    .lp-cast-do .btn { justify-self: start; min-height: 2.5rem; padding: .3rem .95rem; font-size: .84rem; }
    html.dark .lp-wk.is-roster { background: #18210f; border-color: #2f4220; }
    html.dark .lp-money { background: #12170f; border-color: #2b3a1c; }
    html.dark .lp-money input { color: #e8efe1; }
    html.dark .lp-unit2 { background: #1c2416; }
    html.dark .lp-unit2 button.is-on { color: #e8efe1; background: #2b3a1c; }
    html.dark .lp-savenew { background: #18210f; border-color: #2b3a1c; }
    html.dark .lp-savenew b { color: #e8efe1; }
    html.dark .lp-hire-in { background: #2a2010; border-color: #5c4413; }
    html.dark .lp-hire-h, html.dark .lp-hire-h small, html.dark .lp-hire-note { color: #fcd34d; }
    html.dark .lp-hpick, html.dark .lp-hchip { color: #fcd34d; background: #1a1408; border-color: #5c4413; }
    html.dark .lp-hchip b { color: #fde68a; }
    html.dark .lp-hire .lp-unit2 { background: #3a2a0c; }
    html.dark .lp-hire .lp-money { border-color: #5c4413; }
    html.dark .lp-dfor, html.dark .lp-dlot-h b { color: #e8efe1; }
    html.dark .lp-dlot { background: #12170f; border-color: #2b3a1c; border-left-color: var(--lc); }
    html.dark .lp-dlot-t { color: #fcd34d; }
    html.dark .lp-dauto { background: #1f1836; border-color: #3b2e66; }
    html.dark .lp-dauto b { color: #e4dcfd; }
    html.dark .lp-dauto small { color: #c9bdf5; }
    html.dark .lp-cast-c { background: #12170f; border-color: #2b3a1c; }
    html.dark .lp-cast-c.is-warn { background: #2a210a; border-color: #4d3a0d; }
    html.dark .lp-cast-h b, html.dark .lp-cast-row > b, html.dark .lp-cast-do p { color: #e8efe1; }
    html.dark .lp-cast-c > p { color: #b7c2ad; }
    html.dark .lp-cast-row .is-ok { color: #cfe6b8; background: #22301a; }
    html.dark .lp-cast-row .is-late { color: #fca5a5; background: #3a1414; }
    html.dark .lp-cast-row .is-moved { color: #d8ccfb; background: #2a1f45; }
    @media (prefers-reduced-motion: reduce) { .lp-hire, .lp-hchip, .lp-savenew, .lp-dlot, .lp-dauto, .lp-cast, .lp-cast-c, .lp-cast-row { transition: none !important; animation: none !important; } }

    /* The lot pages host the shared field profile. */
    .lp-fqhost { min-height: 10rem; }

    /* Review. */
    .lp-rev { display: grid; gap: .1rem; border-radius: 1rem; overflow: hidden; border: 1px solid var(--color-gray-200); }
    .lp-rev button { display: flex; align-items: center; gap: .7rem; padding: .7rem .8rem; text-align: left; background: #fff; min-width: 0; transition: background-color .28s var(--lp-e); }
    .lp-rev button + button { border-top: 1px solid var(--color-gray-100); }
    .lp-rev button:hover { background: var(--lp-gx); }
    .lp-rev .re { flex: none; font-size: 1.15rem; width: 1.6rem; text-align: center; }
    .lp-rev .re i { display: inline-block; width: .8rem; height: .8rem; border-radius: 999px; background: var(--lc); }
    .lp-rev .rt { min-width: 0; flex: 1; }
    .lp-rev .rt small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .lp-rev .rt b { display: block; font-size: .88rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.35; overflow-wrap: anywhere; }
    .lp-rev .rc { flex: none; font-size: .72rem; font-weight: 800; color: var(--lp-g); }
    .lp-twarn { margin-top: .8rem; display: flex; gap: .65rem; padding: .75rem .8rem; border-radius: 1rem; background: #fffbeb; border: 1px solid #fde68a; color: #78350f; font-size: .8rem; line-height: 1.5; animation: lpPop .3s var(--lp-e) both; }
    .lp-twarn > span:first-child { flex: none; font-size: 1.15rem; }
    .lp-twarn > span + span, .lp-research > span + span, .lp-book > span + span, .lp-tw-note > span + span { flex: 1; min-width: 0; }
    .lp-twarn b { color: #92400e; }
    .lp-twarn button { margin-top: .3rem; font-size: .78rem; font-weight: 800; color: #92400e; text-decoration: underline; }
    .lp-run { margin-top: 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .55rem; min-height: 3.3rem; padding: .85rem 1rem; border-radius: 1.1rem; color: #fff; font-weight: 800; font-size: 1rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a); background-size: 260% 100%; animation: lpTide 5.5s ease-in-out infinite alternate; box-shadow: 0 12px 26px -14px rgb(61 104 35 / .7); }
    .lp-run:disabled { opacity: .55; animation: none; }
    .lp-run img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .6); }
    @keyframes lpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .lp-fine { margin-top: .5rem; text-align: center; font-size: .74rem; color: var(--color-gray-400); }
    .lp-err { margin: .2rem 0 .8rem; padding: .8rem .95rem; border-radius: 1rem; background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; animation: lpIn .34s var(--lp-e) both; }
    .lp-err b { display: block; font-size: .9rem; font-weight: 800; }
    .lp-err p { margin-top: .2rem; font-size: .84rem; line-height: 1.45; }

    /* Your plans. */
    .lp-find { display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; margin-bottom: .8rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lp-find:focus-within { border-color: #86b556; }
    .lp-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .lp-find input { flex: 1; min-width: 0; min-height: 2.8rem; border: 0; background: transparent; font-size: 1rem; outline: none; box-shadow: none; }
    .lp-cards { display: grid; gap: .7rem; }
    @media (min-width: 640px) { .lp-cards { grid-template-columns: 1fr 1fr; } }
    .lp-card { position: relative; display: flex; flex-direction: column; gap: .55rem; width: 100%; padding: .9rem; border-radius: 1.2rem; text-align: left; background: var(--color-white); border: 1px solid var(--color-gray-200); min-width: 0;
        animation: lpPop .34s var(--lp-e) both; transition: border-color .28s var(--lp-e), box-shadow .28s var(--lp-e), transform .28s var(--lp-e), opacity .28s var(--lp-e); cursor: pointer; }
    .lp-card:hover { border-color: #a8cc7e; box-shadow: 0 16px 30px -24px rgb(20 33 12 / .6); transform: translateY(-2px); }
    .lp-card-top { display: flex; align-items: flex-start; gap: .7rem; padding-right: 2rem; }
    .lp-card-ico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.4rem; background: var(--lp-gx); border: 1px solid #e1edd3; }
    .lp-card-t { min-width: 0; }
    .lp-card-t b { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .92rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-card-t small { display: block; margin-top: .15rem; font-size: .74rem; color: var(--color-gray-500); }
    .lp-card-meta { display: flex; flex-wrap: wrap; gap: .35rem; }
    .lp-card-meta span { display: inline-flex; align-items: center; gap: .25rem; padding: .18rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lp-card-pen { position: absolute; top: .6rem; right: .6rem; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-400); transition: background-color .28s var(--lp-e), color .28s var(--lp-e); }
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
        .lp-sheet.is-small .lp-sbox { width: min(26rem, calc(100vw - 2rem)); }
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
    .lp-shead h2 { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; line-height: 1.3; color: #14210c; }
    .lp-shead h2 small { display: block; margin-top: .1rem; font-family: inherit; font-size: .76rem; font-weight: 600; line-height: 1.4; color: var(--color-gray-500); }
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
    .lp-row .rt b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
    .lp-row .rt small { display: block; font-size: .74rem; color: #6b7280; line-height: 1.35; overflow-wrap: anywhere; }
    .lp-row .rn { flex: none; font-size: .7rem; font-weight: 800; padding: .18rem .5rem; border-radius: 999px; color: #3d6823; background: #eef5e6; white-space: nowrap; }
    .lp-row .rn.is-hy { color: #92400e; background: #fef3c7; }
    .lp-row .ri { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.1rem; background: #f6f8f3; }
    .lp-row.is-add .rt b { color: var(--lp-gd, #2d5016); }
    .lp-row.is-add .ri { background: var(--lp-gl, #e4efd4); color: var(--lp-gd, #2d5016); }
    .lp-row.is-add .ri svg { width: 1.1rem; height: 1.1rem; }
    .lp-snone { padding: 1.4rem 1rem; text-align: center; font-size: .9rem; color: #6b7280; }
    .lp-sfoot { flex: none; display: flex; align-items: center; gap: .7rem; padding: .8rem 1rem calc(.9rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-gray-100); }
    @media (min-width: 640px) { .lp-sfoot { padding-bottom: .9rem; } }
    .lp-sfoot .btn { flex: none; min-height: 2.9rem; padding-left: 1.1rem; padding-right: 1.1rem; }
    .lp-sfoot .lp-wide { flex: 1; }
    .lp-pickline { flex: 1; min-width: 0; }
    .lp-pickline b { display: block; font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .lp-pickline span { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .lp-check { flex: none; width: 1.5rem; height: 1.5rem; border-radius: .45rem; display: grid; place-items: center; border: 2px solid var(--color-gray-300); color: #fff; transition: background-color .28s ease, border-color .28s ease; }
    .lp-check svg { width: .85rem; height: .85rem; opacity: 0; }
    .lp-row[aria-checked="true"] .lp-check, .lp-eqrow [aria-checked="true"] .lp-check { background: #4a7c2a; border-color: #4a7c2a; }
    .lp-row[aria-checked="true"] .lp-check svg, .lp-eqrow [aria-checked="true"] .lp-check svg { opacity: 1; }
    .lp-sbody { padding: 0 1.15rem 1rem; overflow-y: auto; min-height: 0; }
    .lp-credit { padding: .3rem 1.15rem .2rem; }
    .lp-credit button { font-size: .76rem; font-weight: 700; color: var(--color-gray-500); text-decoration: underline; text-underline-offset: 2px; }

    /* The add variety panel. */
    .lp-addv { padding: .2rem 1.15rem 1rem; overflow-y: auto; animation: lpIn .3s var(--lp-e) both; }
    .lp-addv .form-input { width: 100%; min-height: 3rem; font-size: 1.05rem; font-weight: 700; }
    .lp-addv .lp-research { margin-top: 1rem; }

    /* The equipment sheet rows. */
    .lp-eqrow { border-radius: 1rem; transition: background-color .28s var(--lp-e); }
    .lp-eqrow.is-on { background: #f3f8ec; }
    .lp-eqrow > button { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .55rem .55rem; text-align: left; color: #14210c; }
    .lp-eqrow .lp-ph { width: 3rem; height: 3rem; }
    .lp-eqrow .et { flex: 1; min-width: 0; }
    .lp-eqrow .et b { display: block; font-size: .92rem; font-weight: 800; line-height: 1.3; }
    .lp-eqrow .et small { display: block; font-size: .75rem; color: #6b7280; line-height: 1.35; }
    .lp-eqrow .lp-cts { display: grid; grid-template-rows: 0fr; padding: 0 .55rem 0 4.25rem; transition: grid-template-rows .28s var(--lp-e), padding .28s var(--lp-e); }
    .lp-eqrow .lp-cts > div { overflow: hidden; display: flex; gap: .5rem; min-height: 0; }
    .lp-eqrow.is-on .lp-cts { grid-template-rows: 1fr; padding-bottom: .65rem; }
    @media (max-width: 400px) { .lp-eqrow .lp-cts { padding-left: .55rem; } }
    .lp-creds { display: grid; gap: .6rem; }
    .lp-cred { display: flex; gap: .65rem; align-items: flex-start; font-size: .78rem; line-height: 1.45; color: #374151; }
    .lp-cred .lp-ph { width: 2.6rem; height: 2.6rem; }
    .lp-cred a { color: var(--lp-gd, #2d5016); font-weight: 700; text-decoration: underline; overflow-wrap: anywhere; }
    html.dark .lp-cred a { color: #a5c97e; }
    .lp-own .lp-same-day { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.5rem; padding: .3rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px dashed var(--color-gray-300); }
    html.dark .lp-own .lp-same-day { background: #12170f; border-color: #3d4a33; color: #b7c2ad; }
    .lp-cred div { min-width: 0; }

    /* The date sheet. */
    .lp-cal { padding: 0 1rem; overflow: hidden; }
    .lp-mon { display: flex; align-items: center; justify-content: space-between; padding: 0 0 .5rem; }
    .lp-mon b { font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: var(--color-gray-900); }
    .lp-mon button { width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); transition: background-color .28s ease, opacity .28s ease; }
    .lp-mon button:hover:not(:disabled) { background: #e4efd4; }
    .lp-mon button:disabled { opacity: .3; cursor: default; }
    .lp-mon svg { width: 1.1rem; height: 1.1rem; }
    .lp-wkd { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); padding-bottom: .3rem; text-align: center; font-size: .7rem; font-weight: 800; color: var(--color-gray-400); }
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

    /* The time warning. */
    .lp-tw { padding: .8rem .85rem; margin-bottom: .7rem; border-radius: 1rem; background: #fffdf7; border: 1px solid #fde9b5; }
    .lp-tw-lot { display: flex; align-items: center; gap: .4rem; margin-bottom: .55rem; font-size: .84rem; font-weight: 900; color: #1f2937; }
    .lp-tw-lot i { width: .65rem; height: .65rem; border-radius: 999px; background: var(--lc); }
    .lp-tw-stats { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .lp-tw-stats div { padding: .55rem .6rem; border-radius: .85rem; background: #fff; border: 1px solid #f3e3b7; }
    .lp-tw-stats b { display: block; font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; line-height: 1.1; color: #92400e; }
    .lp-tw-stats .is-left b { color: #b91c1c; }
    .lp-tw-stats small { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 700; line-height: 1.35; color: #78350f; }
    .lp-tw-bar { position: relative; height: .5rem; margin: .7rem 0 .2rem; border-radius: 999px; background: #fde68a; overflow: hidden; }
    .lp-tw-bar i { position: absolute; right: 0; top: 0; bottom: 0; border-radius: 999px; background: #d97706; transform-origin: right; animation: lpGrow .6s var(--lp-e) both; }
    @keyframes lpGrow { from { transform: scaleX(0); } }
    .lp-tw-cap { display: flex; justify-content: space-between; font-size: .66rem; font-weight: 800; color: #92400e; }
    .lp-tw-h { margin: .7rem 0 .35rem; font-size: .72rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #92400e; }
    .lp-tw-list { display: grid; gap: .3rem; }
    .lp-tw-list li { display: flex; align-items: center; gap: .5rem; font-size: .82rem; line-height: 1.35; color: #374151; min-width: 0; }
    .lp-tw-list li span { flex: none; padding: .1rem .45rem; border-radius: 999px; font-size: .64rem; font-weight: 900; text-transform: uppercase; letter-spacing: .03em; }
    .lp-tw-list li span.is-moved { background: #fee2e2; color: #991b1b; }
    .lp-tw-list li span.is-short { background: #fef3c7; color: #92400e; }
    .lp-tw-list li em { font-style: normal; min-width: 0; overflow-wrap: anywhere; }
    .lp-tw-note { display: flex; gap: .55rem; margin-top: .2rem; padding: .7rem .75rem; border-radius: .95rem; background: var(--lp-gx); border: 1px solid #e1edd3; font-size: .8rem; line-height: 1.5; color: #3d5226; }
    .lp-tw-note > span:first-child { flex: none; }

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

    /* The season sheet's summary (2026-10-15). */
    .lp-ssum { display: grid; gap: .45rem; margin-top: .9rem; }
    .lp-ssum > div { display: flex; align-items: flex-start; gap: .65rem; padding: .6rem .7rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lp-ssum > div > span { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; background: #fff; border: 1px solid #e5ebdf; }
    .lp-ssum p { min-width: 0; display: grid; gap: .05rem; }
    .lp-ssum b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-ssum small { font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .lp-sprev { margin-top: .7rem; padding: .6rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; color: #6b4a00; background: #fff8e6; border: 1px solid #f5d98a; }
    .lp-sprev a { font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }
    html.dark .lp-ssum > div { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-ssum > div > span { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lp-ssum b { color: #e8efe1; }
    html.dark .lp-sprev { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }

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
    .lp-vbody { max-width: 62rem; margin: 0 auto; padding: 1rem 1rem calc(2.5rem + env(safe-area-inset-bottom)); display: grid; gap: .9rem; min-width: 0; }
    .lp-vbody > * { min-width: 0; }
    .lp-hero { position: relative; overflow: hidden; padding: 1.1rem 1.15rem; border-radius: 1.3rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #6b9f3d 0%, #3d6823 45%, #24410f 100%); }
    .lp-hero::after { content: ''; position: absolute; right: -2rem; bottom: -2.5rem; width: 10rem; height: 10rem; border-radius: 999px; background: rgb(255 255 255 / .06); }
    .lp-hero-top { display: flex; align-items: center; gap: .8rem; min-width: 0; }
    .lp-hero-ico { flex: none; width: 3.2rem; height: 3.2rem; border-radius: 1rem; display: grid; place-items: center; font-size: 1.7rem; background: rgb(255 255 255 / .14); }
    .lp-hero small { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; opacity: .8; }
    .lp-hero h2 { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
    .lp-hero-chips { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .8rem; }
    .lp-hero-chips span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: rgb(255 255 255 / .16); max-width: 100%; }
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
    .lp-c { min-width: 0; padding: 1rem 1.05rem; border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); overflow-wrap: anywhere; }
    .lp-c > h3 { display: flex; align-items: center; gap: .45rem; margin-bottom: .7rem; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--color-gray-900); min-width: 0; }
    .lp-c > h3 img { flex: none; width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; }
    .lp-c > h3 em { flex: none; margin-left: auto; font-style: normal; font-size: .7rem; font-weight: 800; padding: .15rem .5rem; border-radius: 999px; color: var(--color-gray-600); background: var(--color-gray-100); }
    .lp-p { font-size: .9rem; line-height: 1.6; color: var(--color-gray-700); }
    .lp-an { display: grid; gap: .55rem; margin-top: .8rem; }
    @media (min-width: 720px) { .lp-an { grid-template-columns: 1fr 1fr; } }
    .lp-an div { display: flex; gap: .6rem; padding: .7rem .75rem; border-radius: .95rem; background: var(--color-gray-50); min-width: 0; }
    .lp-an div > span:first-child { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; background: #fff; }
    .lp-an div > span + span { flex: 1; min-width: 0; }
    .lp-an b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .lp-an p { margin-top: .1rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .lp-warnc { border-color: #fde68a; background: #fffdf5; }
    .lp-warnc li { display: flex; gap: .5rem; padding: .45rem 0; font-size: .84rem; line-height: 1.5; color: #78350f; border-top: 1px solid #fef3c7; min-width: 0; }
    .lp-warnc li:first-child { border-top: 0; }
    .lp-warnc li > i { flex: none; width: .55rem; height: .55rem; margin-top: .45rem; border-radius: 999px; background: var(--lc); }
    .lp-warnc li > span { min-width: 0; flex: 1; }
    .lp-warnc .lp-p { color: #78350f; margin-top: .4rem; }
    .lp-short { border-color: #fde68a; background: linear-gradient(180deg, #fffbeb, #fff); }
    .lp-short-lots { display: grid; gap: .45rem; margin-bottom: .7rem; }
    .lp-short-lot { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: .85rem; background: #fff; border: 1px solid #fde9b5; min-width: 0; }
    .lp-short-lot > i { flex: none; width: .65rem; height: .65rem; border-radius: 999px; background: var(--lc); }
    .lp-short-lot > span { flex: 1; min-width: 0; font-size: .82rem; line-height: 1.4; color: #78350f; }
    .lp-short-lot b { color: #92400e; }

    /* Across your lots: one block a lot, lanes for the land work, the seedbed and the planting day, on one date axis. */
    .lp-tl2 { min-width: 0; }
    .lp-tax { position: relative; height: 1.5rem; margin-left: var(--lw); border-bottom: 1px solid var(--color-gray-200); }
    .lp-tax span { position: absolute; top: .15rem; padding-left: .3rem; border-left: 1.5px solid var(--color-gray-300); font-size: .66rem; font-weight: 800; line-height: 1.2rem; color: var(--color-gray-500); white-space: nowrap; }
    .lp-tl2 { --lw: 4.9rem; }
    @media (min-width: 720px) { .lp-tl2 { --lw: 6.5rem; } }
    .lp-tlot { padding: .7rem 0 .55rem; border-bottom: 1px dashed var(--color-gray-200); }
    .lp-tlot:last-of-type { border-bottom: 0; }
    .lp-tlh { display: flex; align-items: center; gap: .45rem; margin-bottom: .45rem; min-width: 0; }
    .lp-tlh > i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .lp-tlh b { flex: none; font-size: .86rem; font-weight: 900; color: var(--color-gray-900); }
    .lp-tlh small { min-width: 0; font-size: .74rem; font-weight: 700; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-lane { display: grid; grid-template-columns: var(--lw) minmax(0, 1fr); align-items: center; min-height: 1.6rem; }
    .lp-lane + .lp-lane { margin-top: .25rem; }
    .lp-ln { font-size: .68rem; font-weight: 800; line-height: 1.2; color: var(--color-gray-500); padding-right: .4rem; }
    .lp-trk { position: relative; min-width: 0; height: 1.5rem; border-radius: .5rem; background: repeating-linear-gradient(90deg, transparent 0 calc(var(--wk) - 1px), rgb(0 0 0 / .045) calc(var(--wk) - 1px) var(--wk)), #f7f9f5; }
    .lp-bar2 { position: absolute; height: 1.15rem; min-width: 7px; display: flex; align-items: center; padding: 0 .3rem; border-radius: .4rem; font-size: .6rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden;
        transform-origin: left; transform: scaleX(0); transition: transform .6s cubic-bezier(.22,1,.36,1), box-shadow .2s ease, filter .2s ease; cursor: pointer; }
    .lp-view.is-drawn .lp-bar2 { transform: none; }
    .lp-bar2:hover, .lp-bar2:focus-visible { filter: brightness(1.1); box-shadow: 0 0 0 2px #fff, 0 0 0 4px currentColor; outline: none; }
    .lp-bar2.is-machine { background: #4a7c2a; }
    .lp-bar2.is-manual { background: #d97706; }
    .lp-bar2.is-seed { background: #0284c7; }
    .lp-bar2.is-grow { background: repeating-linear-gradient(135deg, #bae6fd 0 6px, #e0f2fe 6px 12px); color: #075985; cursor: default; }
    .lp-bar2.is-late { box-shadow: inset 0 0 0 2px #ef4444; }
    .lp-pdot { position: absolute; top: 50%; display: inline-flex; align-items: center; gap: .3rem; transform: translate(-.55rem, -50%); white-space: nowrap; font-size: .7rem; font-weight: 800; color: #5b21b6; }
    .lp-pdot i { width: 1.1rem; height: 1.1rem; border-radius: .3rem; transform: rotate(45deg); background: #7c3aed; box-shadow: 0 0 0 2px #fff; }
    .lp-pdot.is-end { transform: translate(calc(-100% + .55rem), -50%); flex-direction: row-reverse; }
    .lp-tnow { position: absolute; top: -.2rem; bottom: -.2rem; width: 2px; background: #ef4444; z-index: 2; pointer-events: none; }
    .lp-glegend { display: flex; flex-wrap: wrap; gap: .45rem .9rem; margin-top: .75rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-500); }
    .lp-glegend span { display: inline-flex; align-items: center; gap: .3rem; }
    .lp-glegend i { width: 1rem; height: .55rem; border-radius: .25rem; }
    .lp-tl-tip { margin-top: .55rem; font-size: .74rem; color: var(--color-gray-500); }

    /* Your crew: who, the bookings, the jobs (each opens its sheet), day by day. */
    .lp-crew-top { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .75rem; }
    .lp-crew-chip { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.4rem; padding: .25rem .7rem .25rem .3rem; border-radius: 999px; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .78rem; font-weight: 700; color: var(--color-gray-700); max-width: 100%; }
    .lp-crew-chip .lp-ph { width: 1.9rem; height: 1.9rem; font-size: 1rem; box-shadow: none; }
    .lp-crew-chip em { font-style: normal; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* What it costs (2026-10-16). */
    .lp-cost-big { display: flex; align-items: baseline; flex-wrap: wrap; gap: .2rem .55rem; padding: .7rem .8rem; margin-bottom: .6rem; border-radius: 1rem; background: linear-gradient(120deg, #f3f8ec, #e8f1dc); }
    .lp-cost-big b { font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: #1f3510; }
    .lp-cost-big span { font-size: .8rem; font-weight: 700; color: #3d5226; }
    .lp-cost-rows { display: grid; margin: .3rem 0 .6rem; }
    .lp-cost-rows > div { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; border-top: 1px solid var(--color-gray-100); min-width: 0; animation: lpPop .3s var(--lp-e) both; }
    .lp-cost-rows > div:first-child { border-top: 0; }
    .lp-cost-rows > div > span { flex: 1; min-width: 0; }
    .lp-cost-rows b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-cost-rows small { display: block; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); }
    .lp-cost-rows em { flex: none; font-style: normal; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .lp-cost-rows em.is-none { font-size: .74rem; font-weight: 700; color: var(--color-gray-400); }
    .lp-cost-rows b > i { display: inline-block; width: .6rem; height: .6rem; margin-right: .35rem; border-radius: 999px; background: var(--lc); }
    html.dark .lp-cost-big { background: linear-gradient(120deg, #1c2a12, #22301a); }
    html.dark .lp-cost-big b { color: #e8efe1; }
    html.dark .lp-cost-big span { color: #cfe6b8; }
    .lp-book { display: flex; gap: .6rem; padding: .6rem .7rem; margin-bottom: .55rem; border-radius: .9rem; background: #fff8eb; border: 1px solid #fde9c0; font-size: .8rem; line-height: 1.45; color: #78350f; min-width: 0; }
    .lp-book > span:first-child { flex: none; font-size: 1.05rem; }
    .lp-book b { color: #92400e; }
    .lp-jobs { display: grid; gap: .45rem; margin-top: .3rem; }
    .lp-job { width: 100%; display: flex; align-items: center; gap: .65rem; padding: .65rem .7rem; border-radius: 1rem; text-align: left; background: #fbfcfa; border: 1px solid var(--color-gray-200); min-width: 0;
        transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e), transform .28s var(--lp-e); animation: lpPop .34s var(--lp-e) both; }
    .lp-job:hover { border-color: #a8cc7e; background: #fff; transform: translateY(-1px); }
    .lp-job > span:first-child { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.15rem; background: #fff; border: 1px solid #e5ebdf; }
    .lp-job .jt { flex: 1; min-width: 0; }
    .lp-job .jt b { display: block; font-size: .9rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-job .jt small { display: block; margin-top: .1rem; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); }
    .lp-job .jd { display: inline-flex; gap: .2rem; margin-right: .3rem; vertical-align: -.05rem; }
    .lp-job .jd i { width: .55rem; height: .55rem; border-radius: 999px; background: var(--lc); }
    .lp-job > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .lp-job.is-late { border-color: #fecaca; }
    .lp-more2 { display: flex; align-items: center; justify-content: center; gap: .35rem; width: 100%; margin-top: .7rem; min-height: 2.6rem; border-radius: .9rem; font-size: .84rem; font-weight: 800; color: var(--lp-g, #4a7c2a); background: var(--lp-gx); border: 1px solid #e1edd3; transition: background-color .28s var(--lp-e); }
    .lp-more2:hover { background: var(--lp-gl); }
    .lp-more2 svg { width: .9rem; height: .9rem; transition: transform .28s var(--lp-e); }
    .lp-more2[aria-expanded="true"] svg { transform: rotate(180deg); }
    .lp-cal2 { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .34s var(--lp-e); }
    .lp-cal2 > div { overflow: hidden; min-height: 0; }
    .lp-cal2.is-open { grid-template-rows: 1fr; }
    .lp-week { margin-top: .8rem; }
    .lp-week > b { display: block; margin-bottom: .35rem; font-size: .68rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-400); }
    .lp-day { display: flex; gap: .6rem; padding: .45rem 0; border-top: 1px solid var(--color-gray-100); min-width: 0; }
    .lp-dbox { flex: none; width: 2.7rem; text-align: center; border-radius: .7rem; padding: .2rem 0; background: var(--color-gray-50); height: fit-content; }
    .lp-dbox small { display: block; font-size: .58rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .lp-dbox b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.2; }
    .lp-day.is-plant .lp-dbox { background: #ede9fe; }
    .lp-dchips { display: flex; flex-wrap: wrap; align-content: center; gap: .3rem; min-width: 0; }
    .lp-cc { display: inline-flex; align-items: center; gap: .3rem; min-height: 1.9rem; padding: .22rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); max-width: 100%; text-align: left; transition: border-color .28s var(--lp-e), background-color .28s var(--lp-e); }
    button.lp-cc:hover { border-color: #a8cc7e; background: #fff; }
    .lp-cc i { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: var(--lc); }
    .lp-cc em { font-style: normal; font-weight: 800; color: var(--color-gray-500); }
    .lp-cc.is-plant { background: #f5f3ff; border-color: #ddd6fe; color: #5b21b6; }

    /* The seed card in the plan. */
    .lp-tl { margin-top: .7rem; display: grid; gap: .1rem; }
    .lp-ev { display: flex; gap: .6rem; align-items: flex-start; padding: .45rem 0; border-top: 1px solid var(--color-gray-100); min-width: 0; }
    .lp-ev:first-child { border-top: 0; }
    .lp-ev > span { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1rem; background: #e0f2fe; }
    .lp-ev > div { min-width: 0; }
    .lp-ev b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .lp-ev small { display: block; font-size: .74rem; color: var(--color-gray-500); line-height: 1.45; }
    .lp-ev em { font-style: normal; font-weight: 800; color: #0369a1; }
    .lp-steps-ol { margin-top: .6rem; display: grid; gap: .3rem; padding-left: 1.1rem; list-style: decimal; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); }
    details.lp-more-d summary { cursor: pointer; margin-top: .6rem; font-size: .8rem; font-weight: 800; color: var(--lp-g, #4a7c2a); list-style: none; }
    details.lp-more-d summary::-webkit-details-marker { display: none; }
    .lp-tlh2 { display: flex; align-items: center; gap: .4rem; margin-top: .8rem; font-size: .74rem; font-weight: 900; color: #0c4a6e; }
    .lp-tlh2 i { width: .55rem; height: .55rem; border-radius: 999px; background: var(--lc); }

    /* The weather: the forecast slides sideways, and says so. */
    .lp-scrollx { position: relative; min-width: 0; }
    .lp-fc { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .3rem; scrollbar-width: none; scroll-behavior: smooth; }
    .lp-fc::-webkit-scrollbar { display: none; }
    .lp-scrollx::after { content: ''; position: absolute; top: 0; right: 0; bottom: .3rem; width: 3rem; pointer-events: none; background: linear-gradient(90deg, rgb(255 255 255 / 0), #fff); opacity: 1; transition: opacity .28s var(--lp-e); }
    .lp-scrollx.is-end::after, .lp-scrollx.is-fit::after { opacity: 0; }
    .lp-nudge { position: absolute; right: .3rem; top: 50%; z-index: 1; display: inline-flex; align-items: center; gap: .2rem; padding: .3rem .55rem .3rem .65rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #fff; background: rgb(12 74 110 / .82);
        transform: translateY(-50%); transition: opacity .3s var(--lp-e), transform .3s var(--lp-e); animation: lpNudge 1.6s var(--lp-e) .6s 3; pointer-events: auto; }
    .lp-nudge svg { width: .85rem; height: .85rem; }
    .lp-scrollx.is-moved .lp-nudge, .lp-scrollx.is-fit .lp-nudge, .lp-scrollx.is-end .lp-nudge { opacity: 0; transform: translate(.6rem, -50%); pointer-events: none; }
    @keyframes lpNudge { 0%, 100% { transform: translate(0, -50%); } 35% { transform: translate(-.55rem, -50%); } 60% { transform: translate(.1rem, -50%); } }
    .lp-fd { flex: none; width: 3.6rem; padding: .45rem .2rem; border-radius: .9rem; text-align: center; background: var(--color-gray-50); }
    .lp-fd small { display: block; font-size: .6rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; }
    .lp-fd span { display: block; font-size: 1.2rem; line-height: 1.5; }
    .lp-fd b { display: block; font-size: .74rem; color: var(--color-gray-900); }
    .lp-fd i { display: block; font-style: normal; font-size: .64rem; font-weight: 800; color: #0369a1; }
    .lp-wx { display: grid; grid-template-columns: repeat(auto-fit, minmax(6.5rem, 1fr)); gap: .45rem; margin-top: .7rem; }
    .lp-wx div { padding: .6rem .5rem; border-radius: .9rem; text-align: center; background: #f0f9ff; min-width: 0; }
    .lp-wx b { display: block; font-family: var(--font-heading); font-size: 1.1rem; color: #0c4a6e; }
    .lp-wx small { display: block; font-size: .66rem; font-weight: 700; color: #0369a1; line-height: 1.3; }

    .lp-risk { display: flex; gap: .6rem; padding: .55rem 0; border-top: 1px solid var(--color-gray-100); min-width: 0; }
    .lp-risk:first-of-type { border-top: 0; }
    .lp-risk > span { flex: none; margin-top: .15rem; padding: .12rem .45rem; height: fit-content; border-radius: 999px; font-size: .62rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
    .lp-risk > span.is-low { background: #dcfce7; color: #166534; }
    .lp-risk > span.is-medium { background: #fef3c7; color: #92400e; }
    .lp-risk > span.is-high { background: #fee2e2; color: #991b1b; }
    .lp-risk > div { min-width: 0; flex: 1; }
    .lp-risk b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .lp-risk p { font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .lp-risk small { display: block; font-size: .72rem; font-weight: 700; color: var(--color-gray-400); margin-top: .1rem; }
    /* Good to know: nothing wider than the screen. */
    .lp-rules { display: grid; min-width: 0; }
    .lp-rules li { display: flex; gap: .55rem; padding: .5rem 0; font-size: .84rem; line-height: 1.5; color: var(--color-gray-700); border-top: 1px solid var(--color-gray-100); min-width: 0; }
    .lp-rules li:first-child { border-top: 0; }
    .lp-rules li > span:first-child { flex: none; }
    .lp-rules li > div { flex: 1; min-width: 0; overflow-wrap: anywhere; word-break: break-word; }
    .lp-rules li > div > span { display: inline-flex; flex-wrap: wrap; gap: .25rem; margin-top: .25rem; max-width: 100%; }
    .lp-lotchip { display: inline-flex; align-items: center; gap: .25rem; max-width: 100%; padding: .05rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lp-lotchip i { flex: none; width: .45rem; height: .45rem; border-radius: 999px; background: var(--lc); }
    .lp-fineprint { font-size: .74rem; line-height: 1.55; color: var(--color-gray-500); }

    /* Per lot. */
    .lp-lotpick { display: flex; gap: .4rem; overflow-x: auto; padding: .1rem .1rem .3rem; scrollbar-width: none; }
    .lp-lotpick::-webkit-scrollbar { display: none; }
    .lp-lotpick button { flex: none; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.5rem; padding: .35rem .85rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s ease, color .28s ease, border-color .28s ease; }
    .lp-lotpick button i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc); }
    .lp-lotpick button.is-on { color: #fff; background: #1f2937; border-color: #1f2937; }
    .lp-lothead { display: flex; align-items: center; gap: .8rem; min-width: 0; }
    .lp-lothead > i { flex: none; width: 3rem; height: 3rem; border-radius: 1rem; display: grid; place-items: center; font-style: normal; font-size: 1.4rem; background: color-mix(in srgb, var(--lc) 16%, #fff); }
    .lp-lothead > div { min-width: 0; }
    .lp-lothead b { display: block; font-family: var(--font-heading); font-size: 1.1rem; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .lp-lothead small { display: block; font-size: .8rem; line-height: 1.45; color: var(--color-gray-500); }
    .lp-moved { margin-top: .6rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .8rem; line-height: 1.45; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; }
    .lp-ans { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .lp-ans span { display: inline-flex; gap: .3rem; max-width: 100%; padding: .2rem .55rem; border-radius: .6rem; font-size: .72rem; line-height: 1.35; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); overflow-wrap: anywhere; }
    .lp-ans span b { flex: none; font-weight: 800; color: var(--color-gray-500); }
    .lp-copytag { display: inline-flex; align-items: center; gap: .3rem; margin-top: .6rem; padding: .2rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; }
    .lp-list { display: grid; gap: .55rem; }
    .lp-s { display: flex; gap: .7rem; padding: .8rem .85rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: lpPop .34s cubic-bezier(.22,1,.36,1) both; min-width: 0; cursor: pointer; text-align: left; width: 100%; transition: border-color .28s var(--lp-e); }
    .lp-s:hover { border-color: #c5dcaa; }
    .lp-s.is-anee { border-color: #fcd34d; background: linear-gradient(180deg, #fffdf5, #fff); }
    .lp-s.is-squeezed { border-left: 4px solid #f59e0b; }
    .lp-cal1 { flex: none; width: 3.1rem; overflow: hidden; border-radius: .85rem; text-align: center; background: var(--color-white); border: 1px solid var(--color-gray-200); height: fit-content; }
    .lp-cal1 i { display: block; padding: .12rem; font-style: normal; font-size: .56rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #fff; background: #4a7c2a; }
    .lp-s.is-anee .lp-cal1 i { background: #d97706; }
    .lp-cal1 b { display: block; padding: .1rem 0 .2rem; font-family: var(--font-heading); font-size: 1.2rem; color: var(--color-gray-900); }
    .lp-cal1 small { display: block; margin-top: -.25rem; padding-bottom: .2rem; font-size: .58rem; font-weight: 800; color: var(--color-gray-400); }
    .lp-sb { min-width: 0; flex: 1; }
    .lp-sb > b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .lp-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
    .lp-tg { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; max-width: 100%; }
    .lp-tg.is-philrice { background: #e4efd4; color: #2d5016; }
    .lp-tg.is-irri { background: #e0f2fe; color: #075985; }
    .lp-tg.is-extension { background: #f1f5f9; color: #334155; }
    .lp-tg.is-anee { background: #fef3c7; color: #92400e; }
    .lp-tg.is-reason { background: var(--color-gray-100); color: var(--color-gray-600); }
    .lp-tg.is-late { background: #fee2e2; color: #991b1b; }
    .lp-tg.is-squeezed { background: #ffedd5; color: #9a3412; }
    .lp-when { margin-top: .35rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); line-height: 1.45; }
    .lp-how { margin-top: .35rem; font-size: .82rem; line-height: 1.55; color: var(--color-gray-700); }
    .lp-why { margin-top: .4rem; padding: .45rem .6rem; border-radius: .7rem; font-size: .78rem; line-height: 1.5; color: #78350f; background: #fffbeb; }
    .lp-why b { color: #92400e; }
    .lp-sq { margin-top: .4rem; padding: .45rem .6rem; border-radius: .7rem; font-size: .78rem; line-height: 1.5; color: #9a3412; background: #fff7ed; }
    .lp-note { margin-top: .4rem; display: flex; gap: .45rem; padding: .45rem .6rem; border-radius: .7rem; font-size: .78rem; line-height: 1.5; color: #374151; background: var(--color-gray-50); }
    .lp-note img { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; object-fit: cover; }
    .lp-ts { display: flex; gap: .6rem; align-items: center; padding: .55rem .8rem; border-radius: 1rem; background: #f0f9ff; border: 1px solid #e0f2fe; animation: lpPop .34s cubic-bezier(.22,1,.36,1) both; min-width: 0; }
    .lp-ts > span { flex: none; font-size: 1.1rem; }
    .lp-ts > div { min-width: 0; }
    .lp-ts b { display: block; font-size: .84rem; color: #0c4a6e; }
    .lp-ts small { display: block; font-size: .74rem; line-height: 1.4; color: #0369a1; }
    .lp-acts { display: grid; gap: .5rem; }
    @media (min-width: 560px) { .lp-acts { grid-template-columns: 2fr 1fr 1fr; } }
    .lp-acts .btn { min-height: 3rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; }
    .lp-acts .btn svg { width: 1rem; height: 1rem; }
    .lp-acts .lp-del:hover { color: #b91c1c; }

    /* The step sheet. */
    .lp-stepbody { display: grid; gap: .8rem; }
    .lp-sk { font-size: .7rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-400); margin-bottom: .3rem; }
    .lp-swhen { display: grid; gap: .35rem; }
    .lp-swhen div { display: flex; align-items: center; gap: .5rem; padding: .5rem .6rem; border-radius: .8rem; background: var(--color-gray-50); font-size: .84rem; line-height: 1.4; color: var(--color-gray-800); min-width: 0; }
    .lp-swhen div i { flex: none; width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc); }
    .lp-swhen div span { min-width: 0; flex: 1; }
    .lp-swhen div b { font-weight: 800; }
    .lp-sstats { display: grid; grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr)); gap: .45rem; }
    .lp-sstats div { display: flex; align-items: center; gap: .55rem; padding: .6rem .65rem; border-radius: .9rem; background: #f8faf6; border: 1px solid #edf1e8; min-width: 0; }
    .lp-sstats .lp-ph { width: 2.2rem; height: 2.2rem; font-size: 1.1rem; box-shadow: none; }
    .lp-sstats b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .lp-sstats small { display: block; font-size: .72rem; line-height: 1.35; color: var(--color-gray-500); }
    .lp-stips { display: grid; gap: .35rem; }
    .lp-stips li { display: flex; gap: .45rem; font-size: .84rem; line-height: 1.5; color: var(--color-gray-700); }
    .lp-stips li::before { content: ''; flex: none; width: .4rem; height: .4rem; margin-top: .55rem; border-radius: 999px; background: #86b556; }

    @media (max-width: 400px) {
        .lp-wiz { padding: .95rem .85rem .9rem; }
        .lp-q { font-size: 1.1rem; }
        .lp-vbody { padding-left: .75rem; padding-right: .75rem; }
        .lp-c { padding: .9rem .85rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lp-step.is-on, .lp-lot, .lp-card, .lp-s, .lp-ts, .lp-pane.is-in, .lp-run, .lp-tag.is-bump .ti, .lp-days.is-l, .lp-days.is-r, .lp-d.is-sel, .lp-done .ok, .lp-eq, .lp-wk, .lp-rec, .lp-seed, .lp-ferr, .lp-twarn, .lp-job, .lp-tw-bar i, .lp-addv, .is-shake, .lp-nudge, .lp-err { animation: none !important; }
        .lp-sbox, .lp-sheet::backdrop, .lp-sheet.is-closing .lp-sbox, .lp-sheet.is-closing::backdrop { animation: none !important; }
        .lp-bar i, .lp-choice, .lp-tag, .lp-chipb, .lp-view, .lp-bar2, .lp-seg i, .lp-choice .ck, .lp-card, .lp-lot, .lp-wk, .lp-eqrow .lp-cts, .lp-cal2, .lp-more2 svg, .lp-d7 button, .lp-nudge, .lp-scrollx::after { transition: none !important; }
        .lp-bar2 { transform: none; }
    }

    /* Night. */
    html.dark .lp-tabs, html.dark .lp-wiz, html.dark .lp-c, html.dark .lp-card, html.dark .lp-s, html.dark .lp-find, html.dark .lp-empty, html.dark .lp-eq { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-q, html.dark .lp-choice .ct b, html.dark .lp-c > h3, html.dark .lp-card-t b, html.dark .lp-sb > b, html.dark .lp-rev .rt b, html.dark .lp-lothead b, html.dark .lp-empty b, html.dark .lp-eq .et b, html.dark .lp-job .jt b, html.dark .lp-tlh b, html.dark .lp-stepper output, html.dark .lp-ct output { color: #e8efe1; }
    html.dark .lp-choice, html.dark .lp-chipb, html.dark .lp-rev button, html.dark .lp-lotpick button { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-choice.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .lp-choice .ce, html.dark .lp-hour, html.dark .lp-an div, html.dark .lp-dbox, html.dark .lp-cc, html.dark .lp-fd, html.dark .lp-note, html.dark .lp-card-meta span, html.dark .lp-ans span, html.dark .lp-swhen div, html.dark .lp-crew-chip { background: #1c2416; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-lot, html.dark .lp-wk, html.dark .lp-job { background: #12170f; border-color: #2b3a1c; }
    html.dark .lp-lot { border-left-color: var(--lc); }
    html.dark .lp-lot-top b, html.dark .lp-field > label, html.dark .lp-field > span.lp-fl, html.dark .lp-label { color: #d5e3c5; }
    html.dark .lp-chipb.is-on, html.dark .lp-d7 button.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .lp-tag { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .lp-tag .ti, html.dark .lp-an div > span:first-child, html.dark .lp-hour > span, html.dark .lp-job > span:first-child { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-tag .tc, html.dark .lp-unit, html.dark .lp-same button, html.dark .lp-loc { background: #2b3a1c; border-color: #3d5228; color: #d5e3c5; }
    html.dark .lp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-quote b { color: #e8efe1; }
    html.dark .lp-hint, html.dark .lp-tw-note { background: #1a2414; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-hint.is-warn, html.dark .lp-twarn { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    html.dark .lp-twarn b, html.dark .lp-twarn button { color: #fde68a; }
    html.dark .lp-seed { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-seed-h b, html.dark .lp-hour b, html.dark .lp-an b, html.dark .lp-dbox b, html.dark .lp-ev b, html.dark .lp-risk b, html.dark .lp-fd b, html.dark .lp-sstats b { color: #e8efe1; }
    html.dark .lp-hour em { color: #d5e3c5; }
    html.dark .lp-p, html.dark .lp-how, html.dark .lp-rules li, html.dark .lp-an p, html.dark .lp-seed p, html.dark .lp-risk p, html.dark .lp-stips li { color: #b7c2ad; }
    html.dark .lp-research { background: #1e1736; border-color: #3b2d6b; color: #ddd6fe; }
    html.dark .lp-research.is-miss { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    html.dark .lp-badge.is-anee { background: #2e1f5a; color: #ddd6fe; }
    html.dark .lp-ph { background: #1c2616; box-shadow: 0 0 0 2px #151b12, 0 0 0 3.5px #2b3a1c; }
    html.dark .lp-ct { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lp-ct > span { color: #b7c2ad; }
    html.dark .lp-ct button { background: #151b12; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .lp-ct.is-hire { background: #2a210a; border-color: #4d3a0d; }
    html.dark .lp-ct.is-hire > span { color: #fcd34d; }
    html.dark .lp-rec { background: linear-gradient(120deg, #1a2414, #1f2c17); border-color: #2b3a1c; }
    html.dark .lp-rec b { color: #e8efe1; }
    html.dark .lp-rec p { color: #b7c2ad; }
    html.dark .lp-wk-top input { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .lp-d7 button { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .lp-view { background: #0d110a; }
    html.dark .lp-vbar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .lp-vname b { color: #e8efe1; }
    html.dark .lp-seg { background: rgb(21 27 18 / .9); border-color: #2b3a1c; }
    html.dark .lp-tax { border-color: #2b3a1c; }
    html.dark .lp-tax span { border-color: #3d4a33; color: #93a684; }
    html.dark .lp-trk { background: repeating-linear-gradient(90deg, transparent 0 calc(var(--wk) - 1px), rgb(255 255 255 / .05) calc(var(--wk) - 1px) var(--wk)), #1a2114; }
    html.dark .lp-tlot { border-color: #2b3a1c; }
    html.dark .lp-pdot { color: #c4b5fd; }
    html.dark .lp-pdot i { box-shadow: 0 0 0 2px #151b12; }
    html.dark .lp-bar2.is-grow { background: repeating-linear-gradient(135deg, #0c4a6e 0 6px, #0c3550 6px 12px); color: #bae6fd; }
    html.dark .lp-day, html.dark .lp-ev, html.dark .lp-risk, html.dark .lp-rules li, html.dark .lp-rev button + button { border-color: #222b1a; }
    html.dark .lp-cal1 { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-cal1 b { color: #e8efe1; }
    html.dark .lp-s.is-anee { background: linear-gradient(180deg, #2a210a, #151b12); border-color: #4d3a0d; }
    html.dark .lp-why, html.dark .lp-book { background: #2a210a; color: #fcd34d; border-color: #4d3a0d; }
    html.dark .lp-book b, html.dark .lp-why b { color: #fde68a; }
    html.dark .lp-sq { background: #2c1a0b; color: #fdba74; }
    html.dark .lp-ts { background: #0c2231; border-color: #12364d; }
    html.dark .lp-ts b, html.dark .lp-tlh2 { color: #bae6fd; }
    html.dark .lp-wx div { background: #0c2231; }
    html.dark .lp-wx b { color: #bae6fd; }
    html.dark .lp-scrollx::after { background: linear-gradient(90deg, rgb(21 27 18 / 0), #151b12); }
    html.dark .lp-warnc, html.dark .lp-short { background: #2a210a; border-color: #4d3a0d; }
    html.dark .lp-warnc li, html.dark .lp-warnc .lp-p { color: #fcd34d; border-color: #3a2c0a; }
    html.dark .lp-short-lot { background: #1f1907; border-color: #4d3a0d; }
    html.dark .lp-short-lot > span, html.dark .lp-short-lot b { color: #fcd34d; }
    html.dark .lp-sbox { background: #151b12; }
    html.dark .lp-shead h2, html.dark .lp-row, html.dark .lp-mon b, html.dark .lp-pickline b, html.dark .lp-done b, html.dark .lp-eqrow > button, html.dark .lp-cred { color: #e8efe1; }
    html.dark .lp-eqrow.is-on { background: #1c2616; }
    html.dark .lp-sgh { background: #151b12; color: #a5c97e; }
    html.dark .lp-x { background: #1c2616; color: #d5e3c5; }
    html.dark .lp-sfind { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lp-sfind input, html.dark .lp-find input { color: #e8efe1; }
    html.dark .lp-row:hover, html.dark .lp-row[aria-selected="true"] { background: #1c2616; }
    html.dark .lp-row .ri, html.dark .lp-mon button { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
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
    html.dark .lp-tg.is-squeezed { background: #3a1f0a; color: #fdba74; }
    html.dark .lp-tg.is-extension, html.dark .lp-tg.is-reason, html.dark .lp-badge.is-none, html.dark .lp-lotchip { background: #1c2416; color: #b7c2ad; }
    html.dark .lp-stepper button { background: #22301a; color: #cfe6b8; }
    html.dark .lp-rev button:hover, html.dark .lp-job:hover { background: #1c2616; }
    html.dark .lp-lothead > i { background: color-mix(in srgb, var(--lc) 22%, #151b12); }
    html.dark .lp-quote.is-off { background: #2a1a0a; border-color: #4d2f0d; color: #fdba74; }
    html.dark .lp-tw { background: #1f1907; border-color: #4d3a0d; }
    html.dark .lp-tw-lot { color: #e8efe1; }
    html.dark .lp-tw-stats div { background: #151b12; border-color: #4d3a0d; }
    html.dark .lp-tw-stats b, html.dark .lp-tw-h, html.dark .lp-tw-cap { color: #fcd34d; }
    html.dark .lp-tw-stats .is-left b { color: #fca5a5; }
    html.dark .lp-tw-stats small { color: #e5c07b; }
    html.dark .lp-tw-bar { background: #4d3a0d; }
    html.dark .lp-tw-list li { color: #d5e3c5; }
    html.dark .lp-copytag { background: #0f2a20; border-color: #1f5c43; color: #6ee7b7; }
    html.dark .lp-moved { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    html.dark .lp-more2 { background: #1a2414; border-color: #2b3a1c; color: #a5c97e; }
    html.dark .lp-sstats div { background: #1c2416; border-color: #2b3a1c; }
</style>

@php
    $lpCrops = collect($crops)->map(fn ($c) => [$c[0], $c[1], $c[2], $c[3], null])->all();
    $lpCropJs = collect($crops)->map(fn ($c) => ['local' => $c[0], 'en' => $c[1], 'icon' => $c[2]])->all();
    $xIcon = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    $chev = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
@endphp
@include('partials.field-profile')
@include('partials.farm-lots')

<div class="lp" id="lpPage">
    <div class="lp-tabs" role="tablist">
        <button type="button" class="lp-tab is-on" id="lpTabPlan" role="tab" aria-selected="true">New plan</button>
        <button type="button" class="lp-tab" id="lpTabList" role="tab" aria-selected="false">Your plans</button>
    </div>

    <section id="lpPlanPane">
        <div class="lp-quote" id="lpQuote" hidden></div>

        <div class="lp-wiz" id="lpWiz">
            <div class="lp-head"><small id="lpStepName">Lots</small><span id="lpStepNo">Step 1</span></div>
            <div class="lp-bar" aria-hidden="true"><i id="lpBar"></i></div>

            {{-- What each lot will grow (2026-10-17, the owner: the plan starts from the lots, and
                 each lot picks what to plant): its crop, how it goes in and its variety, one page a
                 lot, the lots after the first can take the same as one before in a tap. --}}
            <section class="lp-step" data-screen="plant">
                <p class="lp-q" id="lpPlantQ">What will you plant?</p>
                <p class="lp-sub" id="lpPlantSub">Every step is dated back from its planting day.</p>
                <div class="lp-samepick" id="lpSamePick" hidden></div>
                <span class="lp-label">Crop</span>
                <div id="lpPlantCrop">
                    @include('public.site.partials.crop-pick', ['id' => 'lpCropSheet', 'crops' => $lpCrops, 'current' => 'rice', 'title' => 'Which crop?', 'noun' => null])
                </div>
                <span class="lp-label" id="lpMethodQ" data-tl="planting-method">How will you plant it?</span>
                <p class="lp-sub lp-sub-s" id="lpMethodSub">Each way has its own schedule.</p>
                <div class="lp-choices" id="lpMethods"></div>
                <p class="lp-ferr" id="lpMethodErr" hidden>Pick how it goes in.</p>
                <span class="lp-label">Which variety?{{ \App\Support\Tagalog::flag('variety') }}</span>
                <p class="lp-sub lp-sub-s">Its seed preparation depends on it. Not on our list? Add it, and Anee looks it up.</p>
                <button type="button" class="lp-tag is-none" id="lpVarTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true">🌾</span>
                    <span class="tt"><b id="lpVarName">I don't know yet</b><small id="lpVarLine">Tap to pick from the list or add one</small></span>
                    <span class="tc">Change{!! $chev !!}</span>
                </button>
                <div id="lpSeedType" hidden>
                    <span class="lp-label">Is the seed inbred or hybrid?</span>
                    <div class="lp-chips" id="lpSeedTypes">
                        <button type="button" class="lp-chipb" data-type="inbred">Inbred{{ \App\Support\Tagalog::flag('inbred') }}</button>
                        <button type="button" class="lp-chipb" data-type="hybrid">Hybrid{{ \App\Support\Tagalog::flag('hybrid') }}</button>
                        <button type="button" class="lp-chipb" data-type="">Not sure</button>
                    </div>
                </div>
                <div class="lp-seed" id="lpSeed" hidden></div>
            </section>

            {{-- Where the seed is from (rice) --}}
            <section class="lp-step" data-screen="seed">
                <p class="lp-q" id="lpSeedQ">Where is your seed from?</p>
                <p class="lp-sub" id="lpSeedWhy" data-tl="germination-test"></p>
                <div class="lp-choices" id="lpSeedSrc"></div>
            </section>

            {{-- The planting day --}}
            <section class="lp-step" data-screen="date">
                <p class="lp-q" id="lpDateQ" data-tl="planting-day">When will you plant?</p>
                <p class="lp-sub" id="lpDateSub">Everything is dated back from this day.</p>
                {{-- Many lots (2026-10-16, the owner): only the first lot's day and Anee times the
                     others, or a day for each lot with warnings where the crew or the days fall short. --}}
                <div class="lp-choices" id="lpDateMode" hidden>
                    <button type="button" class="lp-choice is-on" data-dmode="first" aria-pressed="true"><span class="ce">📅</span><span class="ct"><b>Only the first lot's day</b><small>You pick the day of the first lot. Anee times the others: each goes in as soon as your crew can have it ready.</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <button type="button" class="lp-choice" data-dmode="each" aria-pressed="false"><span class="ce">🗓️</span><span class="ct"><b>A day for each lot</b><small>Every lot keeps the day you pick. Anee warns you where your workers or the days look too few.</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                </div>
                <p class="lp-dfor" id="lpDateFor" hidden></p>
                <button type="button" class="lp-tag is-big is-none" id="lpDateTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true"><i id="lpDateMon">Day</i><em id="lpDateDay">?</em></span>
                    <span class="tt"><b id="lpDateText">Pick the day</b><small id="lpDateLine">Opens a calendar</small></span>
                    <span class="tc">Change{!! $chev !!}</span>
                </button>
                <p class="lp-ferr" id="lpDateErr" hidden>Pick the day first.</p>
                <div class="lp-hint" id="lpDateHint" hidden></div>
                <div id="lpDateLots" aria-live="polite"></div>
            </section>

            {{-- The lots: the plan starts here (2026-10-17, the owner) --}}
            <section class="lp-step is-on" data-screen="lots">
                <p class="lp-q">Which lots are you preparing?{{ \App\Support\Tagalog::flag('lot') }}</p>
                <p class="lp-sub">One card a lot. Next, you pick what each lot will grow. Anee plans every lot and shares the crew between them. Your lots bring their place, size and answers, so only what they lack is asked.</p>
                <div class="lp-lots" id="lpLots"></div>
                {{-- The farm's lots are the source (2026-10-16, the owner): pick them
                     from Global Lots (partials/farm-lots). "From your seasons" is gone
                     (the owner, the same day): the lots live in Global Lots. --}}
                <div class="lp-lot-acts">
                    <button type="button" class="btn btn-white" id="lpLotAdd"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Add from your lots</button>
                </div>
                <p class="lp-total" id="lpLotTotal"></p>
                <div class="lp-hint" id="lpLotPlace" hidden></div>
            </section>

            {{-- The machines --}}
            <section class="lp-step" data-screen="equip">
                <p class="lp-q">What will plow and harrow?{{ \App\Support\Tagalog::flag('harrowing') }}</p>
                <p class="lp-sub">Pick every machine or animal you will use, and say how many you own and how many you hire.</p>
                <div id="lpEquipBox"></div>
            </section>

            {{-- The workers --}}
            <section class="lp-step" data-screen="workers">
                <p class="lp-q">Who will do the work?{{ \App\Support\Tagalog::flag('crew') }}</p>
                <p class="lp-sub">Each worker keeps their own days and pay. Anee gives each day only the hands that work it, and adds up what the work costs.</p>
                <div id="lpRec"></div>
                {{-- The farm's own workers (Global Workers): picked, they bring their days off and pay (2026-10-16, the owner). --}}
                <button type="button" class="lp-tag lp-roster" id="lpRosterOpen" hidden><span class="ti" aria-hidden="true">👥</span><span class="tt"><b>Pick from My workers</b><small id="lpRosterLine">Your workers from every season</small></span><span class="tc">Pick{!! $chev !!}</span></button>
                <div class="lp-stepper">
                    <button type="button" data-wstep="-1" aria-label="One worker less">−</button>
                    <output id="lpWCount">2</output>
                    <button type="button" data-wstep="1" aria-label="One worker more">+</button>
                    <span id="lpWWord">workers</span>
                </div>
                <div class="lp-wks" id="lpWorkers"></div>
                <div class="lp-same" id="lpSameDays"><button type="button" data-same="days">Same days for all</button><button type="button" data-same="pay">Same pay for all</button></div>
                <label class="lp-savenew" id="lpSaveNew" hidden><input type="checkbox" id="lpSaveNewOn" checked><span><b>Add the new names to My workers</b><small>Next season you pick them instead of typing them again.</small></span></label>
                <p class="lp-ferr" id="lpWErr" hidden>Give at least one worker a work day.</p>
                {{-- Will every lot be ready on its day with this crew? The days themselves are set on the
                     planting day step (2026-10-16, the owner), so this only warns. --}}
                <div class="lp-cast" id="lpCast" aria-live="polite"></div>
            </section>

            {{-- Every lot's soil, water and history: the shared field profile --}}
            <section class="lp-step" data-screen="lotq">
                <div class="lp-fqhost" id="lpFq"></div>
            </section>

            {{-- The place --}}
            <section class="lp-step" data-screen="place">
                <p class="lp-q">Where are the lots?</p>
                <p class="lp-sub">Anee reads the forecast, the ENSO outlook{{ \App\Support\Tagalog::flag('enso-outlook') }} and twenty years of the area's weather there.</p>
                <div id="lpPlacePh">
                    <span class="lp-label">Province</span>
                    <button type="button" class="lp-tag is-none" id="lpProv" aria-haspopup="dialog"><span class="ti" aria-hidden="true">🗺️</span><span class="tt"><b>Choose the province</b></span><span class="tc">Pick{!! $chev !!}</span></button>
                    <span class="lp-label">Town or city</span>
                    <button type="button" class="lp-tag is-none" id="lpTown" aria-haspopup="dialog" disabled><span class="ti" aria-hidden="true">📍</span><span class="tt"><b>Pick the province first</b></span><span class="tc">Pick{!! $chev !!}</span></button>
                </div>
                <div id="lpPlaceAny" hidden>
                    <label class="lp-label" for="lpPlaceText">Town and region</label>
                    <input type="text" id="lpPlaceText" class="form-input" maxlength="160">
                </div>
                <p class="lp-ferr" id="lpPlaceErr" hidden></p>
                {{-- The notes live here, and move to the review when the lots' own place skips this screen. --}}
                <div id="lpNotesHome"><div id="lpNotesBox">
                    <label class="lp-label" for="lpNotes">Anything else Anee should know? <small>(optional)</small></label>
                    <textarea id="lpNotes" class="form-textarea" rows="2" maxlength="500"></textarea>
                </div></div>
            </section>

            {{-- Review and run --}}
            <section class="lp-step" data-screen="review">
                <p class="lp-q">Ready for Anee</p>
                <p class="lp-sub">Tap a row to change it.</p>
                <div class="lp-rev" id="lpReview"></div>
                <div id="lpNotesSlot"></div>
                <div id="lpTimeCard"></div>
                <div class="lp-cast" id="lpCastRev" aria-live="polite"></div>
                <div class="lp-err" id="lpErr" role="alert" hidden><b>The plan did not finish</b><p id="lpErrText"></p></div>
                <button type="button" class="lp-run" id="lpRun"><span id="lpRunFace"></span><span id="lpRunSays">Make my plan</span></button>
                <p class="lp-fine" id="lpRunFine"></p>
            </section>

            <div class="lp-nav">
                <button type="button" class="btn btn-white" id="lpBack">Back</button>
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
        <button type="button" class="lp-x" id="lpViewX" aria-label="Close">{!! $xIcon !!}</button>
        <button type="button" class="lp-vname" id="lpViewName" aria-label="Rename this plan"><b id="lpViewTitle">Land prep plan</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg></button>
        <button type="button" class="btn btn-white lp-vsend" id="lpViewSeason"><svg fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" style="width:1rem;height:1rem" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 13v5M9.5 15.5h5"/></svg>Make a cropping schedule</button>
        <button type="button" class="btn btn-primary lp-vsend" id="lpViewSend"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="width:1rem;height:1rem" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>Send to Protocol Builder</button>
    </div>
    <div class="lp-vbody" id="lpViewBody"></div>
</div>

{{-- The variety: the list, and a panel to add one that is not on it. --}}
<dialog class="lp-sheet is-tall" id="lpVarSheet" aria-labelledby="lpVarTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpVarTitle">Which variety?</h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div id="lpVarPick" style="display:contents">
            <label class="lp-sfind">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                <span class="sr-only">Search a variety</span>
                <input type="search" id="lpVarQ" placeholder="Search, like Rc 222 or SL-8H" autocomplete="off" maxlength="60">
            </label>
            <div class="lp-slist" id="lpVarList" role="listbox" aria-label="Varieties"></div>
        </div>
        <div class="lp-addv" id="lpVarAdd" hidden>
            <label class="lp-label" for="lpVarNew" style="margin-top:.2rem">Name of the variety</label>
            <input type="text" id="lpVarNew" class="form-input" maxlength="80" autocomplete="off">
            <p class="lp-ferr" id="lpVarNewErr" hidden>Type the name first.</p>
            <div id="lpVarNewType">
                <span class="lp-label">Is it hybrid or inbred?</span>
                <div class="lp-chips" id="lpVarNewTypes">
                    <button type="button" class="lp-chipb" data-ntype="hybrid">Hybrid{{ \App\Support\Tagalog::flag('hybrid') }}</button>
                    <button type="button" class="lp-chipb" data-ntype="inbred">Inbred{{ \App\Support\Tagalog::flag('inbred') }}</button>
                    <button type="button" class="lp-chipb is-on" data-ntype="">Not sure</button>
                </div>
            </div>
            <div class="lp-research"><span>🔎</span><span>When you make the plan, Anee looks this variety up on the web first: its days to harvest, whether it is a hybrid, and the seed company's own soaking, water change and sprouting hours. If she cannot find it, the plan says so and you follow the seed bag.</span></div>
        </div>
        <div class="lp-sfoot" id="lpVarAddFoot" hidden>
            <button type="button" class="btn btn-white" id="lpVarAddBack">Back to the list</button>
            <button type="button" class="btn btn-primary lp-wide" id="lpVarAddUse">Use this variety</button>
        </div>
    </div>
</dialog>

{{-- The planting day. --}}
<dialog class="lp-sheet is-small" id="lpDateSheet" aria-labelledby="lpDateTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpDateTitle">Planting day</h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-cal">
            <div class="lp-mon">
                <button type="button" id="lpPrevM" aria-label="Earlier month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                <b id="lpMonName" aria-live="polite"></b>
                <button type="button" id="lpNextM" aria-label="Later month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
            </div>
            <div class="lp-wkd" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
            <div class="lp-days" id="lpDays"></div>
        </div>
        <p class="lp-legend" id="lpDateLegend"><i></i>Days with time for every step from today</p>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b id="lpPickDate">Tap a day</b><span id="lpPickLine">Days before today are off</span></div>
            <button type="button" class="btn btn-primary" id="lpDateUse" disabled>Use this day</button>
        </div>
    </div>
</dialog>

{{-- Too little time: a warning, never a stop. --}}
<dialog class="lp-sheet is-small" id="lpTimeSheet" aria-labelledby="lpTimeTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpTimeTitle">Little time for the full preparation<small>Anee squeezes the plan to fit the days left{{ \App\Support\Tagalog::flag('short-on-time') }}</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-sbody" id="lpTimeBody"></div>
        <div class="lp-sfoot"><button type="button" class="btn btn-primary lp-wide" id="lpTimeGo">Continue, I understand</button></div>
    </div>
</dialog>

{{-- The size's unit. --}}
<dialog class="lp-sheet is-small" id="lpUnitSheet" aria-labelledby="lpUnitTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpUnitTitle">Size in<small>Type the number in the unit you know</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-slist" id="lpUnitList" role="listbox"></div>
        <p class="lp-fineprint" style="padding:0 1.15rem 1.1rem">Local units like the cavan or the balita differ from place to place, so change them to one of these first. One hectare is 10,000 square meters, about 100 by 100 meters.</p>
    </div>
</dialog>

{{-- The machines and animals, with photos. --}}
<dialog class="lp-sheet is-tall" id="lpEqSheet" aria-labelledby="lpEqTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpEqTitle">Machines and animals<small>Tick each one, then set how many you own and hire</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-slist" id="lpEqList"></div>
        <div class="lp-credit"><button type="button" id="lpCredOpen">Photo credits</button></div>
        <div class="lp-sfoot"><span class="lp-pickline"><b id="lpEqCount">None picked</b><span>A hired{{ \App\Support\Tagalog::flag('hired') }} one comes with its driver</span></span><button type="button" class="btn btn-primary" data-close>Done</button></div>
    </div>
</dialog>

{{-- The photos' authors and licences. --}}
<dialog class="lp-sheet is-small" id="lpCredSheet" aria-labelledby="lpCredTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpCredTitle">Photo credits<small>Free licences, from Wikimedia Commons</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-sbody"><div class="lp-creds" id="lpCredList"></div></div>
    </div>
</dialog>

{{-- My workers (Global Workers): tick who works on this plan. --}}
<dialog class="lp-sheet is-tall" id="lpRosterSheet" aria-labelledby="lpRosterTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpRosterTitle">My workers<small>Tick who will work. They bring their days off and their pay.</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <label class="lp-sfind">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search your workers</span>
            <input type="search" id="lpRosterQ" placeholder="Search your workers" autocomplete="off" maxlength="60">
        </label>
        <div class="lp-slist" id="lpRosterList" role="listbox" aria-multiselectable="true" aria-label="Your workers"></div>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b id="lpRosterCount">None ticked</b><span><a href="{{ route('workers.global') }}" target="_blank" rel="noopener" class="underline">Manage your workers</a></span></div>
            <button type="button" class="btn btn-primary" id="lpRosterUse">Use them</button>
        </div>
    </div>
</dialog>

{{-- Who is hired for a machine: one from the Contact List, or nobody. --}}
<dialog class="lp-sheet is-tall" id="lpConSheet" aria-labelledby="lpConTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpConTitle">Who do you hire?<small id="lpConSub">From your Contact List</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <label class="lp-sfind">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search your contacts</span>
            <input type="search" id="lpConQ" placeholder="Search a name, a number or a town" autocomplete="off" maxlength="60">
        </label>
        <div class="lp-slist" id="lpConList" role="listbox" aria-label="Your contacts"></div>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b>Not in your contacts?</b><span>Skip this, or add them in <a href="{{ route('contacts.page') }}" target="_blank" rel="noopener" class="underline">Contact List</a></span></div>
            <button type="button" class="btn btn-white" data-close>Skip</button>
        </div>
    </div>
</dialog>

{{-- One step or crew job, explained. --}}
<dialog class="lp-sheet is-tall" id="lpStepSheet" aria-labelledby="lpStepTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpStepTitle">Step<small id="lpStepSub"></small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-sbody"><div class="lp-stepbody" id="lpStepBody"></div></div>
    </div>
</dialog>

{{-- A list to pick from: province, town. --}}
<dialog class="lp-sheet is-tall" id="lpPickSheet" aria-labelledby="lpPickTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpPickTitle">Choose</h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <label class="lp-sfind">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search</span>
            <input type="search" id="lpPickQ" placeholder="Search" autocomplete="off">
        </label>
        <div class="lp-slist" id="lpPickList" role="listbox"></div>
    </div>
</dialog>


{{-- The farm's lots (Global Lots): tick the lots to prepare, or add a new one. --}}
<dialog class="lp-sheet is-tall" id="lpFarmSheet" aria-labelledby="lpFarmTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpFarmTitle">Your lots<small>Tick the lots to prepare. Their place, size and answers come along.</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="lp-slist lp-farmlist" id="lpFarmList"></div>
        <div class="lp-sfoot">
            <div class="lp-pickline"><b id="lpFarmCount">None ticked</b><span>A lot already in the plan is ticked</span></div>
            <button type="button" class="btn btn-primary" id="lpFarmUse" disabled>Add them</button>
        </div>
    </div>
</dialog>

{{-- Rename. --}}
<dialog class="lp-sheet is-small" id="lpRenSheet" aria-labelledby="lpRenTitle">
    <form class="lp-sbox" tabindex="-1" id="lpRenForm">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpRenTitle">Rename this plan</h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
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
        <div class="lp-shead"><h2 id="lpPortTitle">Send to Protocol Builder<small>Every step lands on the protocol's own day count</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
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

{{-- Make a cropping schedule from the plan (2026-10-15, the owner). The
     only way in is a new season made out of it. --}}
<dialog class="lp-sheet" id="lpMakeSheet" aria-labelledby="lpMakeTitle">
    <div class="lp-sbox" tabindex="-1">
        <div class="lp-grip" aria-hidden="true"></div>
        <div class="lp-shead"><h2 id="lpMakeTitle">Make a cropping schedule<small>The plan's lots, dates and steps on a season's board</small></h2><button type="button" class="lp-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div id="lpMakeForm" class="lp-slist" style="padding:0 0 1rem">
            <div class="lp-pmode">
                <p class="lp-label" style="margin:.1rem 0 0">Which cropping schedule?</p>
                <button type="button" class="lp-choice is-on" aria-pressed="true"><span class="ce">✨</span><span class="ct"><b>A new cropping schedule</b><small>Made out of this plan</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                <div>
                    <label class="lp-label" for="lpMakeName">Its name</label>
                    <input type="text" id="lpMakeName" class="form-input" maxlength="255">
                </div>
            </div>
            <div class="lp-pmode"><div class="lp-ssum" id="lpMakeSum"></div>
            <p class="lp-sprev" id="lpMakePrev" hidden></p></div>
        </div>
        <div class="lp-done" id="lpMakeDone" hidden>
            <div class="ok"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
            <b>The season is made</b><p id="lpMakeDoneP"></p>
        </div>
        <div class="lp-sfoot">
            <span class="lp-pickline"></span>
            <button type="button" class="btn btn-white" data-close id="lpMakeClose">Cancel</button>
            <button type="button" class="btn btn-primary" id="lpMakeGo">Make the season</button>
            <a class="btn btn-primary" id="lpMakeOpen" href="#" hidden>Open the season</a>
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
        check: @json(route('landprep.check')), list: @json(route('landprep.list')), meta: @json(route('landprep.meta')), port: @json(route('landprep.port')),
        job: (id) => @json(url('/app/land-prep/job')) + '/' + id, one: (id) => @json(url('/app/land-prep/one')) + '/' + id, del: (id) => @json(url('/app/land-prep')) + '/' + id,
        ph: @json(asset('data/ph-locations.json')),
        lots: @json(route('farmlots.index')), lotIcon: @json(asset('images/treasure-map.png')),
        people: @json(route('landprep.people')),
    };
    const TODAY = @json($today);
    const CROPS = @json($lpCropJs);
    const LOT_COLORS = ['#4a7c2a', '#d97706', '#0284c7', '#7c3aed', '#db2777', '#0f766e', '#b45309', '#4f46e5', '#65a30d', '#dc2626', '#0891b2', '#9333ea'];
    const lc = (i) => LOT_COLORS[i % LOT_COLORS.length];
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const CHEV = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
    const XSM = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    /* Tagalog help flags (partials/tagalog-flags): tlf is a flag for a spot inside a line,
       tlAt the data-tl attribute for a whole label. Methods and machines by key. */
    const tlf = (key) => (key && window.aneeTl ? window.aneeTl.flag(key) : '');
    const tlAt = (key) => (key ? ' data-tl="' + esc(key) + '"' : '');
    const METH_TL = { tpr_manual: 'transplanting', transplant: 'transplanting', tpr_mechanical: 'mechanical-transplanting', dsr_wet_broadcast: 'wet-seeding', dsr_wet_drum: 'drum-seeder',
        dsr_dry: 'dry-seeding', direct: 'direct-seeding', setts: 'cane-setts', suckers: 'suckers', tissue: 'tissue-culture', grafted: 'grafted-seedlings' };
    const EQ_TL = { hand_tractor: 'hand-tractor', tractor_rotavator: 'rotavating', tractor_disc: 'disc-plow', carabao_plow: 'plowing', carabao_harrow: 'harrowing',
        pagulong: 'puddling', leveling_board: 'leveling', drum_seeder: 'drum-seeder', transplanter: 'mechanical-transplanting' };

    /* ---- dates, as Y-m-d strings and whole days ---- */
    const dn = (s) => { const [y, m, d] = String(s).split('-').map(Number); return Date.UTC(y, m - 1, d) / 864e5; };
    const ds = (n) => new Date(n * 864e5).toISOString().slice(0, 10);
    const fmt = (s, o) => new Date(String(s).slice(0, 10) + 'T00:00:00Z').toLocaleDateString('en-US', Object.assign({ timeZone: 'UTC' }, o));
    const day = (s) => fmt(s, { weekday: 'short', month: 'short', day: 'numeric' });
    const time = (s) => { const [, t] = String(s).split(' '); const [h, m] = (t || '0:0').split(':').map(Number); return new Date(Date.UTC(2000, 0, 1, h, m)).toLocaleTimeString('en-US', { timeZone: 'UTC', hour: 'numeric', minute: '2-digit' }); };
    const inDays = (s) => { const n = dn(s) - dn(TODAY); return n === 0 ? 'today' : (n === 1 ? 'tomorrow' : 'in ' + n + ' days'); };
    const num = (v) => { const n = parseFloat(String(v ?? '').replace(/,/g, '').trim()); return Number.isFinite(n) ? n : 0; };
    const numOk = (v) => /^\s*\d+([.,]\d+)?\s*$/.test(String(v ?? '').replace(/,(?=\d{3}\b)/g, ''));
    const ha = (n) => (Math.round(n * 100) / 100).toLocaleString('en-US') + ' ha';
    const UNITS = { ha: ['hectares', 'ha', 1], sqm: ['square meters', 'm²', 0.0001], acre: ['acres', 'acres', 0.40468564] };
    const sizeWords = (v, u) => num(v).toLocaleString('en-US', { maximumFractionDigits: 3 }) + ' ' + (UNITS[u] || UNITS.ha)[1];
    const toHa = (v, u) => num(v) * (UNITS[u] || UNITS.ha)[2];
    const shake = (el) => { if (!el || reduce()) return; el.classList.remove('is-shake'); void el.offsetWidth; el.classList.add('is-shake'); setTimeout(() => el.classList.remove('is-shake'), 450); };
    const bump = (el) => { if (!el) return; el.classList.remove('is-bump'); void el.offsetWidth; el.classList.add('is-bump'); };

    let OPT = null;
    let FQ = null;
    let uid = 0;
    const S = {
        crop: 'rice', method: null, variety: '', varRec: null, custom: false, seedType: '', seedSource: '', planting: null,
        lots: [], fleet: {}, handOnly: false, workers: [], dateMode: 'first',
        loc: { province: '', town: '', place: '', lat: null, lng: null }, notes: '',
    };
    /* A lot of the plan. One from the farm's lots (2026-10-16, the owner) keeps
       farmLotId, its row (lot), its place, and `base`: its answers when it was
       picked or last edited, which decide the pages still to ask. */
    const newLot = (o) => Object.assign({ uid: ++uid, name: '', area: '', unit: 'ha', planting: null, sourceLotId: null, from: null, profile: {}, copiedFrom: null, edited: false, savedFrom: null,
        farmLotId: null, lot: null, base: null, crop: null, province: '', town: '', where: '', place: '', stale: false, updated: '', areaFromLot: false, lc: null, pick: null }, o || {});
    /* What each lot grows (2026-10-17, the owner: the plan starts from the lots,
       and each lot picks what to plant). Every lot keeps its own pick (crop, how it
       goes in, variety); the "What to plant" page edits one at a time through S's
       crop fields, the working copy (PICK_AT: the uid of the lot loaded there).
       Off that page S holds the first lot's pick, so S.crop is the plan's crop,
       and a lot growing anything else carries it as lc, as the server reads it. */
    const PICK_KEYS = ['crop', 'method', 'variety', 'varRec', 'custom', 'seedType'];
    let PICK_AT = null;
    const pickFromS = () => { const p = {}; PICK_KEYS.forEach((k) => { p[k] = S[k]; }); return clone(p); };
    const samePick = (a, b) => !!a && !!b && a.crop === b.crop && (a.method || null) === (b.method || null) && (a.variety || '') === (b.variety || '') && !!a.custom === !!b.custom && (a.seedType || '') === (b.seedType || '');
    // A lot not picked yet: its usual crop from the farm's lots, else what the lot before it grows, else rice.
    function defaultPick(l) {
        const i = S.lots.indexOf(l);
        if (l.crop && CROPS[l.crop]) return { crop: l.crop, method: null, variety: '', varRec: null, custom: false, seedType: '' };
        const before = i > 0 ? S.lots[i - 1] : null;
        if (before) return clone(pickOf(before));
        return { crop: 'rice', method: null, variety: '', varRec: null, custom: false, seedType: '' };
    }
    // A lot not opened yet follows its default (so it takes what the lot before it ends up growing).
    const pickOf = (l) => (l.uid === PICK_AT ? pickFromS() : (l.pick || defaultPick(l)));
    // The page leaves a lot: its pick is kept; another lot's is loaded into S.
    function savePick() { const l = S.lots.find((x) => x.uid === PICK_AT); if (l) l.pick = pickFromS(); }
    function loadPick(l) {
        if (!l || l.uid === PICK_AT) return;
        savePick();
        const p = clone(pickOf(l));
        PICK_KEYS.forEach((k) => { S[k] = p[k] === undefined ? (k === 'custom' ? false : (k === 'varRec' || k === 'method' ? null : '')) : p[k]; });
        PICK_AT = l.uid;
    }
    // The lots after the first that grow something else carry it (lc), relative to the first lot.
    function refreshLc() {
        const p0 = S.lots[0] ? pickOf(S.lots[0]) : null;
        S.lots.forEach((l, i) => {
            const p = pickOf(l);
            l.lc = i === 0 || samePick(p, p0) ? null : { crop: p.crop, method: p.method, variety: p.variety, custom: !!p.custom, seedType: p.seedType };
        });
    }
    // A lot's crop, way of planting and variety.
    const lcOf = (l) => (l ? pickOf(l) : pickFromS());
    const dayWordFor = (m) => (['tpr_manual', 'tpr_mechanical', 'transplant'].includes(m) ? 'transplant' : (['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'].includes(m) ? 'sow' : 'plant'));
    const cropLine = (c) => (CROPS[c.crop] ? CROPS[c.crop].icon + ' ' + CROPS[c.crop].en : c.crop) + (OPT && OPT.methods[c.crop] && OPT.methods[c.crop][c.method] ? ', ' + OPT.methods[c.crop][c.method][0].toLowerCase() : '') + (c.variety ? ' · ' + c.variety : '');
    const anyCustom = () => !!S.custom || S.lots.some((l) => !!pickOf(l).custom);
    // A worker's pay (a day, or half a day) and the roster person they are, when picked from My workers.
    const newWorker = (o) => Object.assign({ uid: ++uid, name: '', days: [1, 2, 3, 4, 5, 6], pay: '', payUnit: 'day', globalId: null }, o || {});
    const CUR = @json(\App\Support\Region::symbol());
    const money = (v) => CUR + Math.round(num(v)).toLocaleString('en-US');
    // The people the wizard can pick: the farm's workers and the Contact List (LandPrepController::people).
    let PEOPLE = null;
    const loadPeople = async () => {
        if (PEOPLE) return PEOPLE;
        try { PEOPLE = (await window.api(U.people)).data; } catch (_) { PEOPLE = { roster: [], contacts: [], canSave: false }; }
        return PEOPLE;
    };
    S.saveNew = true;
    const clone = (o) => JSON.parse(JSON.stringify(o || {}));
    // The plan starts with no lot: they come from the farm's lots, or from a season.
    S.workers.push(newWorker(), newWorker());

    /* ---- the farm's lots in the plan ---- */
    const isRiceCrop = () => String(S.crop || '').startsWith('rice');
    const answeredVal = (x) => x !== undefined && x !== null && x !== '' && !(Array.isArray(x) && !x.length);
    // A page of the field questions the answers already cover (any of its questions for this crop).
    const pageDone = (v, p, crop) => {
        const FP = window.aneeFieldProfile;
        if (!FP || !v) return false;
        const riceHere = String(crop || S.crop || '').startsWith('rice');
        return Object.entries(FP.questions).some(([k, q]) => q.page === p && !(q.only === 'rice' && !riceHere) && answeredVal(v[k])) || (p === 'acid' && v.phFrom != null);
    };
    // The pages a lot is asked: all of them, or for a farm lot only the ones it has not answered.
    const askedPages = (l) => {
        const crop = lcOf(l).crop;
        const pages = window.aneeFieldProfile ? window.aneeFieldProfile.pagesFor(crop) : [];
        return l.farmLotId ? pages.filter((p) => !pageDone(l.base, p, crop)) : pages;
    };
    // The place the plan reads the weather at, when the lots have one: the first farm lot with a place.
    function lotPlace() {
        const all = S.lots.filter((l) => l.farmLotId && l.town && l.province);
        if (!all.length) return null;
        const f = all[0], key = (l) => (l.town + '|' + l.province).toLowerCase();
        return { province: f.province, town: f.town, where: f.where || f.town + ', ' + f.province, name: f.name, mixed: all.some((l) => key(l) !== key(f)) };
    }
    // A farm lot's size, in the unit the lot was given in (acres and square meters stay so).
    const lotSize = (a, u) => { a = Number(a) || 0; return !a ? { area: '', unit: 'ha' } : (u === 'acre' ? { area: String(Math.round(a / 0.40468564 * 100) / 100), unit: 'acre' }
        : (u === 'sqm' || a < 0.1) ? { area: String(Math.round(a * 10000)), unit: 'sqm' } : { area: String(Math.round(a * 10000) / 10000), unit: 'ha' }); };
    const farmBits = (r) => ({ farmLotId: r.id, lot: r, name: r.name || '', crop: r.crop || null, base: clone(r.answers), province: r.province || '', town: r.town || '', where: r.where || '',
        place: r.place || '', stale: !!r.stale, updated: r.updated || '' });
    const fromFarm = (r) => newLot(Object.assign(farmBits(r), lotSize(r.areaHa, r.areaUnit), { areaFromLot: Number(r.areaHa) > 0, profile: clone(r.answers), savedFrom: r.count ? r.name : null }));
    // A lot saved or checked again. `keep`: the answers did not change there ("Still correct"), so this plan's own stay.
    function refreshFarm(l, r, keep) {
        Object.assign(l, farmBits(r), keep ? {} : { profile: clone(r.answers), savedFrom: r.count ? r.name : null, copiedFrom: null, edited: false });
        // The size follows the lot, unless one was typed here.
        if (Number(r.areaHa) > 0 && (l.areaFromLot || !String(l.area).trim())) Object.assign(l, lotSize(r.areaHa, r.areaUnit), { areaFromLot: true });
    }
    // The lot's sheet (partials/farm-lots). The answers given on this plan's pages ride along, so the lot keeps them too.
    async function editFarm(l) {
        const L = window.aneeLots;
        if (!L || !l || !l.farmLotId) return null;
        const typed = numOk(l.area) && num(l.area) > 0 ? Math.round(toHa(l.area, l.unit) * 10000) / 10000 : null;
        const row = await L.edit(Object.assign({}, l.lot || {}, { id: l.farmLotId, name: l.name, answers: clone(l.profile), areaHa: (l.lot && l.lot.areaHa) || typed }), { crop: S.crop });
        if (!row) return null;
        refreshFarm(l, row);
        return row;
    }

    /* ---------------- the walk: a flow of screens, the lot pages once for every lot ---------------- */
    let FLOW = ['lots'];
    let pos = 0;
    const rice = () => S.crop === 'rice';
    function flow() {
        // The lots first, then what each one grows (2026-10-17, the owner), then their days
        // (2026-10-16: a day for the first lot or for each).
        const f = ['lots'];
        S.lots.forEach((l, i) => f.push('plant:' + i));
        // Where the seed is from: once, when a lot sows rice seed it prepares.
        if (S.lots.some((l) => { const p = lcOf(l); return p.crop === 'rice' && p.method !== 'dsr_dry'; })) f.push('seed');
        f.push('date', 'equip', 'workers');
        // A lot from the farm's lots is asked only the pages it has not answered yet.
        S.lots.forEach((l, i) => askedPages(l).forEach((p) => f.push('lot:' + i + ':' + p)));
        // The place is asked only when no lot of the farm's brings one.
        if (!lotPlace()) f.push('place');
        f.push('review');
        return f;
    }
    const NAMES = { plant: 'What to plant', seed: 'Seed', date: 'Planting day', lots: 'Lots', equip: 'Machines', workers: 'Workers', place: 'Place', review: 'Review' };
    const screenOf = (id) => (String(id).startsWith('lot:') ? 'lotq' : (String(id).startsWith('plant:') ? 'plant' : id));
    const lotPage = (id) => { const [, i, p] = String(id).split(':'); return [Number(i), p]; };
    const lotWord = (i) => { const l = S.lots[i] || {}, nm = String(l.name || '').trim(); return S.lots.length > 1 ? 'Lot ' + (i + 1) + (nm ? ', ' + nm : '') : (nm || 'Your lot'); };
    function show(n, back) {
        const was = FLOW[pos];
        // The lot on the "What to plant" page keeps what was picked, before the walk is counted again.
        savePick();
        FLOW = flow();
        pos = Math.max(0, Math.min(FLOW.length - 1, n));
        const id = FLOW[pos], sc = screenOf(id);
        // That page edits its own lot; everywhere else S holds the first lot's pick.
        const pi = sc === 'plant' ? Number(String(id).split(':')[1]) : 0;
        if (S.lots[pi]) loadPick(S.lots[pi]);
        refreshLc();
        const same = screenOf(was) === sc && sc === 'lotq';
        // One lot's "What to plant" to the next: the same page, faded out and in again like any step.
        if (was !== undefined && was !== id && sc === 'plant' && screenOf(was) === 'plant') {
            const pg = document.querySelector('.lp-step[data-screen="plant"]');
            window.aneeStepOut?.(pg);
            pg.classList.remove('is-on'); void pg.offsetWidth;
        }
        if (was !== undefined && screenOf(was) !== sc) window.aneeStepOut?.(document.querySelector('.lp-step.is-on'));
        // The notes ride on the place screen, or on the review when the lots' own place skips it.
        const notesAt = sc === 'review' && !FLOW.includes('place') ? $('lpNotesSlot') : $('lpNotesHome');
        if ($('lpNotesBox').parentNode !== notesAt) notesAt.appendChild($('lpNotesBox'));
        document.querySelectorAll('.lp-step').forEach((s) => {
            const on = s.dataset.screen === sc;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!back);
            s.classList.toggle('is-still', on && same);
        });
        if (sc === 'lotq') {
            const [i, p] = lotPage(id);
            const l = S.lots[i] || {};
            $('lpStepName').textContent = (S.lots.length > 1 ? 'Lot ' + (i + 1) + (l.name ? ', ' + l.name : '') : (l.farmLotId && l.name ? l.name : 'Your field')) + ' · ' + window.aneeFieldProfile.groups[p][0];
            FQ.setCrop(lcOf(l).crop);   // its own crop's questions
            FQ.show(i, p, back);
        } else if (sc === 'plant') {
            $('lpStepName').textContent = (S.lots.length > 1 ? lotWord(pi) + ' · ' : '') + NAMES.plant;
        } else {
            $('lpStepName').textContent = NAMES[sc] || '';
        }
        paintCount();
        $('lpBack').disabled = false;   // on the first page it goes back where they came from
        $('lpNext').hidden = sc === 'review';
        $('lpNext').textContent = sc === 'lotq' && FLOW[pos + 1] && !String(FLOW[pos + 1]).startsWith('lot:') ? 'Next' : 'Next';
        if (sc === 'plant') paintPlant(pi);
        if (sc === 'seed') paintSeedSrc();
        if (sc === 'date') paintDate();
        if (sc === 'lots') paintLots();
        if (sc === 'equip') { paintEquip(); loadPeople(); }
        if (sc === 'workers') { paintWorkers(); recommend(); castSoon(true); loadPeople().then(paintRosterBtn); }
        if (sc === 'place') paintPlace();
        if (sc === 'review') review();
        const top = $('lpWiz').getBoundingClientRect().top;
        if (top < 0) $('lpWiz').scrollIntoView({ behavior: 'auto', block: 'start' });
    }
    // The step count, again when the lots change it (each lot brings its own pages).
    function paintCount() {
        if (screenOf(FLOW[pos]) === 'lots') FLOW = flow();
        $('lpStepNo').textContent = 'Step ' + (pos + 1) + ' of ' + FLOW.length;
        $('lpBar').style.width = ((pos + 1) / FLOW.length * 100) + '%';
    }
    const goTo = (id, back) => { FLOW = flow(); const n = FLOW.indexOf(id); if (n >= 0) show(n, back); };
    let busy = false;
    async function next() {
        if (busy) return;
        busy = true;
        $('lpNext').disabled = true;
        try { if (await ready()) show(pos + 1); }
        finally { busy = false; $('lpNext').disabled = false; }
    }
    $('lpNext').addEventListener('click', next);
    $('lpBack').addEventListener('click', () => (pos === 0 ? window.aneeLeave() : show(pos - 1, true)));

    async function ready() {
        const sc = screenOf(FLOW[pos]);
        if (sc === 'plant') {
            if (S.method) { $('lpMethodErr').hidden = true; return true; }
            $('lpMethodErr').hidden = false; shake($('lpMethods'));
            $('lpMethods').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' });
            return false;
        }
        if (sc === 'date') {
            if (!S.planting) { $('lpDateErr').hidden = false; shake($('lpDateTag')); return false; }
            const t = await timeCheck(false);
            if (t && t.some((x) => x.short)) return await timeWarn(t, 'date');
            return true;
        }
        if (sc === 'lots') return checkLots();
        if (sc === 'equip') {
            if (S.handOnly || Object.keys(fleetNow()).length) return true;
            const e = $('lpEqErr'); if (e) { e.hidden = false; shake($('lpEqOpen')); }
            return false;
        }
        if (sc === 'workers') { const ok = S.workers.some((w) => w.days.length); $('lpWErr').hidden = ok; if (!ok) shake($('lpWorkers')); return ok; }
        if (sc === 'place') {
            S.notes = $('lpNotes').value.trim();
            const err = $('lpPlaceErr');
            if (OPT.ph) {
                if (S.loc.province && S.loc.town) { err.hidden = true; return true; }
                err.textContent = S.loc.province ? 'Pick the town or city too, so Anee can read the weather there.' : 'Pick the province and the town, so Anee can read the weather there.';
                err.hidden = false; shake(S.loc.province ? $('lpTown') : $('lpProv'));
                return false;
            }
            S.loc.place = $('lpPlaceText').value.trim();
            if (S.loc.place) { err.hidden = true; return true; }
            err.textContent = 'Say where the lots are.'; err.hidden = false; shake($('lpPlaceText'));
            return false;
        }
        return true;
    }

    async function boot() {
        try { OPT = (await window.api(U.options)).data; } catch (err) { window.toast?.(err.message, 'error'); return; }
        paintQuote();
        paintMethods();
        FQ = window.aneeFieldProfile.mount($('lpFq'), {
            crop: S.crop, lots: fqLots(), saved: true,
            onChange: (i, value, meta) => { const l = S.lots[i]; if (!l) return; l.profile = value; l.copiedFrom = meta.copiedFrom; l.edited = meta.edited; l.savedFrom = meta.from; },
            // Past this lot's pages: the next lot's first asked page, the place, or the review.
            onSkip: (i) => { FLOW = flow(); const nx = FLOW.slice(pos + 1).find((id) => !String(id).startsWith('lot:' + i + ':')); if (nx) goTo(nx); },
        });
        $('lpPlacePh').hidden = !OPT.ph;
        $('lpPlaceAny').hidden = !!OPT.ph;
        show(0);
        const open = new URLSearchParams(location.search).get('open');
        if (open) openSaved(open);
    }
    const fqLots = () => S.lots.map((l, i) => ({ name: l.name.trim() || ('Lot ' + (i + 1)), value: l.profile, copiedFrom: l.copiedFrom, edited: l.edited, from: l.savedFrom }));
    const syncFq = () => { if (FQ) { FQ.setCrop(S.crop); FQ.setLots(fqLots()); } };

    /* This plan's price by its size (App\Support\AiPricing, 2026-10-15): the lots, and the
       variety lookup when the variety is not on our list. Sent back on the run as `quote`, so
       the price on the button is the price charged; a 409 hands back a newer one. */
    const QUOTE_NOW = {};
    const quoteKey = () => Math.min(12, Math.max(1, (S.lots || []).length)) + ':' + (anyCustom() ? 1 : 0);
    function planQuote() {
        const n = Math.min(12, Math.max(1, (S.lots || []).length)), k = quoteKey();
        if (QUOTE_NOW[k]) return QUOTE_NOW[k];
        const row = OPT.quoteFor && OPT.quoteFor[n];
        return row ? Number(row[anyCustom() ? 1 : 0]) : Number(OPT.quote);
    }
    function paintQuote() {
        const q = $('lpQuote');
        if (!OPT.canUse) {
            q.className = 'lp-quote is-off';
            q.innerHTML = '<span>' + esc(OPT.whyNot || 'The planner is not available right now.') + '</span>';
        } else {
            q.className = 'lp-quote';
            q.innerHTML = (OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '')
                + '<span>Anee plans every step of your land preparation, lot by lot, from PhilRice and extension practice, your soil, the weather and your crew. A plan costs <b>' + OPT.quote + ' credits' + tlf('credits') + ' a lot</b>, more when Anee has to look up your variety. You have '
                + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : '<b>' + esc(OPT.balance) + '</b>') + '. Nothing is charged until the plan is ready.</span>';
        }
        q.hidden = false;
    }

    /* ---------------- what a lot grows: crop, method, variety (one page a lot) ---------------- */
    let PLANT_AT = 0;    // the lot whose page is open
    // The page for a lot: its question, the "same as" taps for the lots before it, and its crop, way and variety.
    function paintPlant(i) {
        PLANT_AT = i;
        const l = S.lots[i] || {}, nm = String(l.name || '').trim(), many = S.lots.length > 1;
        $('lpPlantQ').textContent = 'What will you plant on ' + (nm || (many ? 'Lot ' + (i + 1) : 'this lot')) + '?';
        $('lpPlantSub').textContent = i > 0 ? 'Pick what it grows, or tap the same as a lot before it.'
            : (many ? 'Each lot picks its own, one page a lot. Every step is dated back from its planting day.' : 'Every step is dated back from its planting day.');
        window.cropPickSet?.(document.querySelector('#lpPlantCrop .cp-tag'), S.crop);
        $('lpMethodErr').hidden = true;
        paintMethods();
        paintVarTag(true);
    }
    // The lots before this one, each a tap that takes the same crop, way and variety (a pick shared by several shows once).
    function paintSame() {
        const box = $('lpSamePick'), i = PLANT_AT;
        if (!i || !S.lots[i]) { box.hidden = true; box.innerHTML = ''; return; }
        const cur = pickFromS(), seen = [];
        const html = S.lots.slice(0, i).map((l, j) => {
            const p = pickOf(l);
            if (seen.some((q) => samePick(q, p))) return '';
            seen.push(p);
            const on = samePick(cur, p);
            return '<button type="button" class="lp-spick' + (on ? ' is-on' : '') + '" data-spick="' + j + '" aria-pressed="' + on + '" style="--lc:' + lc(j) + '"><i></i><span><b>Same as ' + esc(lotWord(j)) + '</b><small>' + esc(cropLine(p)) + '</small></span><span class="ck">' + CHECK + '</span></button>';
        }).join('');
        box.innerHTML = html;
        box.hidden = !html;
    }
    $('lpSamePick').addEventListener('click', (e) => {
        const b = e.target.closest('[data-spick]');
        const src = b && S.lots[Number(b.dataset.spick)];
        if (!src) return;
        const p = clone(pickOf(src));
        PICK_KEYS.forEach((k) => { S[k] = p[k] === undefined ? S[k] : p[k]; });
        paintPlant(PLANT_AT);
        syncFq(); paintQuote();
    });
    document.addEventListener('croppick', (e) => {
        if (!e.target.closest('#lpWiz')) return;
        S.crop = e.detail.crop;
        S.method = null; S.variety = ''; S.varRec = null; S.seedType = ''; S.custom = false;
        paintMethods(); paintVarTag(true); syncFq(); paintQuote();
    });
    const dayWordOf = () => (['tpr_manual', 'tpr_mechanical', 'transplant'].includes(S.method) ? 'transplant' : (['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'].includes(S.method) ? 'sow' : 'plant'));
    function paintMethods() {
        if (!OPT) return;
        const ms = OPT.methods[S.crop] || {};
        const keys = Object.keys(ms);
        if (!S.method || !ms[S.method]) S.method = keys.length === 1 ? keys[0] : null;
        $('lpMethodQ').textContent = keys.length === 1 ? 'How it goes in' : 'How will you plant it?';
        $('lpMethodSub').textContent = keys.length === 1 ? (CROPS[S.crop] ? CROPS[S.crop].en : 'This crop') + ' goes in one way. The plan follows it.' : 'Each way has its own schedule.';
        $('lpMethods').innerHTML = keys.map((k, i) => {
            const m = ms[k], f = rice() ? (OPT.riceFacts[k] || {}) : {};
            return '<button type="button" class="lp-choice' + (S.method === k ? ' is-on' : '') + '" data-method="' + esc(k) + '"><span class="ce">' + esc(m[2]) + '</span><span class="ct"><b' + tlAt(METH_TL[k]) + '>' + esc(m[0]) + '</b><small>' + esc(m[1]) + '</small>'
                + (f.rate ? '<em' + (i === 0 ? tlAt('seeding-rate') : '') + '>' + esc(f.rate.split('.')[0]) + '</em>' : '') + '</span><span class="ck">' + CHECK + '</span></button>';
        }).join('');
    }
    $('lpMethods').addEventListener('click', (e) => {
        const b = e.target.closest('[data-method]');
        if (!b) return;
        S.method = b.dataset.method;
        $('lpMethods').querySelectorAll('.lp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        $('lpMethodErr').hidden = true;
        // The way decides whether the seed's type is asked (dry seeded rice is not soaked).
        paintVarTag(true);
    });

    /* ---------------- the variety and its seed ---------------- */
    const VAR = {};
    async function varieties(crop) {
        const c = crop || S.crop;
        if (VAR[c]) return VAR[c];
        try { VAR[c] = (await window.api(U.varieties + '?crop=' + encodeURIComponent(c))).data.rows || []; }
        catch (_) { VAR[c] = []; }
        return VAR[c];
    }
    function paintVarTag(fetchSeed) {
        const t = $('lpVarTag');
        const r = S.varRec;
        t.classList.toggle('is-none', !S.variety);
        t.querySelector('.ti').textContent = CROPS[S.crop] ? CROPS[S.crop].icon : '🌱';
        $('lpVarName').textContent = S.variety || "I don't know yet";
        $('lpVarLine').textContent = r ? [r.l, r.h ? 'hybrid' : (rice() ? 'inbred' : r.k), r.d ? r.d + ' ' + (r.u || 'days') : '', r.b].filter(Boolean).join(' · ')
            : (S.variety ? 'New variety, Anee looks it up' : 'Tap to pick from the list or add one');
        const askType = rice() && S.method !== 'dsr_dry' && !(r && r.n) && !S.custom;
        $('lpSeedType').hidden = !askType;
        $('lpSeedTypes').querySelectorAll('[data-type]').forEach((b) => b.classList.toggle('is-on', b.dataset.type === S.seedType));
        if (fetchSeed) loadSeed();
        paintSame();
    }
    let seedAsk = 0;
    async function loadSeed() {
        if (!S.method) return;
        const ask = ++seedAsk;
        const type = S.varRec && S.varRec.n ? (S.varRec.h ? 'hybrid' : 'inbred') : S.seedType;
        try {
            const r = await window.api(U.seed + '?' + new URLSearchParams({ crop: S.crop, method: S.method, variety: S.custom ? '' : S.variety, type }).toString());
            if (ask === seedAsk) paintSeed(r.data.prep);
        } catch (_) { /* the card waits */ }
    }
    function hoursHtml(p) {
        const card = (ico, val, label, line, tl) => '<div class="lp-hour"><span>' + ico + '</span><div><b>' + esc(val) + '</b><em' + tlAt(tl) + '>' + esc(label) + '</em><small>' + esc(line) + '</small></div></div>';
        return '<div class="lp-hours">'
            + card('💧', p.soakHours + ' hours', 'Soak', p.soakRange[0] !== p.soakRange[1] ? p.soakRange[0] + ' to ' + p.soakRange[1] + ' hours is fine' : 'In clean water', 'seed-soaking')
            + card('🔁', 'Every ' + (p.refreshEveryHours || 6) + ' h', 'Change the water', p.refreshEveryHours ? 'While it soaks in still water' : 'PhilRice advice, none given')
            + card('🧺', p.incubateRange[0] + ' to ' + p.incubateRange[1] + ' h', 'Incubate', 'Until a small white root shows', 'seed-incubation')
            + '</div>';
    }
    window.lpHoursHtml = hoursHtml;
    function paintSeed(p) {
        const box = $('lpSeed');
        if (!p) { box.hidden = true; return; }
        const research = S.custom && S.variety
            ? '<div class="lp-research"><span>🔎</span><span><b>Anee looks up ' + esc(S.variety) + '.</b> When you make the plan she searches the web for its days to harvest, its type and the seed company\'s own soaking and sprouting hours. ' + (p.found && p.applies && p.soakHours ? 'Until then, these are the general hours for ' + esc(p.type || 'this') + ' seed.' : 'Until then, follow the seed bag.') + '</span></div>' : '';
        if (!p.found || !p.applies || !p.soakHours) {
            box.innerHTML = '<div class="lp-seed-h"><b data-tl="seed-prep">Seed preparation</b><span class="lp-badge ' + (S.custom ? 'is-anee' : 'is-none') + '">' + (S.custom ? 'Anee looks it up' : (p.applies === false && p.found ? 'Not needed' : 'Not researched')) + '</span></div>'
                + research + (S.custom ? '' : '<p>' + esc(p.say) + '</p>');
            box.hidden = false;
            return;
        }
        const co = p.confidence === 'company';
        box.innerHTML = '<div class="lp-seed-h"><b' + (p.brand && !S.custom ? '' : tlAt(p.type === 'inbred' ? 'inbred' : 'hybrid')) + '>' + (p.brand && !S.custom ? esc(p.brand) : (p.type === 'inbred' ? 'Inbred seed' : 'Hybrid seed')) + '</b><span class="lp-badge ' + (S.custom ? 'is-anee' : (co ? 'is-co' : 'is-gen')) + '">' + (S.custom ? 'Anee looks it up' : (co ? (p.source === 'brand' ? 'Seed company' : 'PhilRice') : 'General practice')) + '</span></div>'
            + hoursHtml(p) + research
            + (S.custom ? '' : '<p>' + esc(p.say) + (p.refreshNote ? ' ' + esc(p.refreshNote) : '') + (p.floatRule ? ' ' + esc(p.floatRule) : '') + ' The plan dates every soak, water change and sowing for each lot.</p>');
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
    const varMode = (add) => {
        $('lpVarPick').style.display = add ? 'none' : 'contents';
        $('lpVarAdd').hidden = !add; $('lpVarAddFoot').hidden = !add;
        $('lpVarTitle').textContent = add ? 'Add a variety' : 'Which variety?';
    };
    $('lpVarTag').addEventListener('click', async () => {
        $('lpVarQ').value = '';
        varMode(false);
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
            + (r.h ? '<span class="rn is-hy">' + esc(r.b ? r.b : 'Hybrid') + '</span>' : (rice() ? '<span class="rn">Inbred</span>' : '')) + '</button>';
        let h = '<button type="button" class="lp-row is-add" data-addv="' + esc(exact ? '' : q) + '"><span class="ri">' + PLUS + '</span><span class="rt"><b>' + (q && !exact ? 'Add “' + esc(q) + '” as a new variety' : 'Add a variety not on the list') + '</b><small>Anee looks it up on the web when she plans</small></span></button>';
        h += '<button type="button" class="lp-row" data-var="" aria-selected="' + (!S.variety) + '"><span class="ri">🤔</span><span class="rt"><b>I don\'t know yet</b><small>The plan uses the general practice and says what to check</small></span></button>';
        const pop = !q ? rows.filter((r) => r.p) : [];
        if (pop.length) h += '<b class="lp-sgh">Popular with farmers</b>' + pop.slice(0, 20).map(row).join('');
        if (rows.length) h += '<b class="lp-sgh">' + (q ? rows.length + ' found' : 'All ' + rows.length + ' varieties') + '</b>' + rows.slice(0, 80).map(row).join('') + (rows.length > 80 ? '<p class="lp-snone">Keep typing to narrow the list.</p>' : '');
        else if (!varRows.length) h += '<p class="lp-snone">No varieties on record for this crop. Add the one you plant.</p>';
        else if (q) h += '<p class="lp-snone">No variety by that name on our list. Add it above.</p>';
        $('lpVarList').innerHTML = h;
    }
    $('lpVarQ').addEventListener('input', paintVarList);
    let newType = '';
    $('lpVarList').addEventListener('click', (e) => {
        const add = e.target.closest('[data-addv]');
        if (add) {
            varMode(true);
            $('lpVarNew').value = add.dataset.addv || (S.custom ? S.variety : '');
            newType = S.custom ? S.seedType : '';
            $('lpVarNewType').hidden = !rice() || S.method === 'dsr_dry';
            $('lpVarNewTypes').querySelectorAll('[data-ntype]').forEach((b) => b.classList.toggle('is-on', b.dataset.ntype === newType));
            $('lpVarNewErr').hidden = true;
            if (matchMedia('(pointer: fine)').matches) $('lpVarNew').focus();
            return;
        }
        const b = e.target.closest('[data-var]');
        if (!b) return;
        S.variety = b.dataset.var; S.varRec = varRows.find((r) => r.n === S.variety) || null; S.custom = false;
        if (S.varRec && S.varRec.n) S.seedType = rice() ? (S.varRec.h ? 'hybrid' : 'inbred') : '';
        sheetClose($('lpVarSheet'));
        bump($('lpVarTag'));
        paintVarTag(true);
    });
    $('lpVarNewTypes').addEventListener('click', (e) => { const b = e.target.closest('[data-ntype]'); if (!b) return; newType = b.dataset.ntype; $('lpVarNewTypes').querySelectorAll('[data-ntype]').forEach((x) => x.classList.toggle('is-on', x === b)); });
    $('lpVarAddBack').addEventListener('click', () => { varMode(false); paintVarList(); });
    $('lpVarAddUse').addEventListener('click', () => {
        const name = $('lpVarNew').value.trim().replace(/\s+/g, ' ');
        if (!name) { $('lpVarNewErr').hidden = false; shake($('lpVarNew')); return; }
        const known = varRows.find((r) => norm(r.n) === norm(name) || norm(r.l) === norm(name) || String(r.t || '').split(',').some((t) => norm(t) === norm(name)));
        if (known) { S.variety = known.n; S.varRec = known; S.custom = false; S.seedType = rice() ? (known.h ? 'hybrid' : 'inbred') : ''; }
        else { S.variety = name; S.varRec = null; S.custom = true; S.seedType = newType; }
        sheetClose($('lpVarSheet'));
        bump($('lpVarTag'));
        paintVarTag(true);
        if (known) window.toast?.(known.x ? 'Anee looked up ' + known.n + ' before, so she uses what she found.' : known.n + ' is on our list, so Anee uses its record.');
    });
    $('lpVarNew').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); $('lpVarAddUse').click(); } });

    /* ---------------- where the seed is from (rice) ---------------- */
    function paintSeedSrc() {
        const q = OPT.seedQ || { q: '', why: '', o: {} };
        $('lpSeedQ').textContent = q.q;
        $('lpSeedWhy').textContent = q.why;
        const ico = { certified: '🏷️', own: '🌾', old: '📦' };
        $('lpSeedSrc').innerHTML = Object.entries(q.o).map(([k, o]) => '<button type="button" class="lp-choice' + (S.seedSource === k ? ' is-on' : '') + '" data-src="' + esc(k) + '"><span class="ce">' + (ico[k] || '🌱') + '</span><span class="ct"><b' + tlAt({ certified: 'certified-seed', own: 'farmer-saved-seed' }[k]) + '>' + esc(o[0]) + '</b><small>' + esc(o[1]) + '</small></span><span class="ck">' + CHECK + '</span></button>').join('');
    }
    $('lpSeedSrc').addEventListener('click', (e) => {
        const b = e.target.closest('[data-src]');
        if (!b) return;
        S.seedSource = S.seedSource === b.dataset.src ? '' : b.dataset.src;
        $('lpSeedSrc').querySelectorAll('[data-src]').forEach((c) => c.classList.toggle('is-on', c.dataset.src === S.seedSource));
    });

    /* ---------------- the planting day: one lot's, the first lot's, or each lot's own ----------------
       Many lots (2026-10-16, the owner): "first", the farmer picks the first
       lot's day and Anee times the others by what the crew can do (the plan
       staggers them and the first lot keeps its day); "each", every lot keeps
       the day the farmer picks, and the Workers step and the review warn
       where the crew or the days fall short. The first lot's day is
       S.planting; a later lot's own day is its planting. */
    const leadOf = (c) => (OPT && OPT.lead[c.crop + ':' + c.method]) || 30;
    const MAX_DATE = ds(dn(TODAY) + 400);
    const capW = (w) => String(w).replace(/^\w/, (c) => c.toUpperCase());
    const lotLabel = (l, i) => 'Lot ' + (i + 1) + (l && String(l.name || '').trim() ? ', ' + String(l.name).trim() : '');
    const eachDay = () => S.lots.length > 1 && S.dateMode === 'each';
    // The day a lot sends (null: the first lot's), and the day it has.
    const lotDay = (l, i) => (i > 0 && eachDay() ? l.planting || null : null);
    const dayOf = (l, i) => lotDay(l, i) || S.planting;
    const daysN = (n) => n + (n === 1 ? ' day' : ' days');
    // Content that changes fades out and in (nothing snaps).
    function fadeSwap(el, html) {
        if (el._html === html) return;
        const had = el._html;
        el._html = html;
        if (reduce() || !had) { el.innerHTML = html; return; }
        el.style.transition = 'opacity .16s ease';
        el.style.opacity = '0';
        setTimeout(() => { if (el._html === html) el.innerHTML = html; el.style.opacity = '1'; }, 160);
    }
    function paintDate(swap) {
        const many = S.lots.length > 1, each = eachDay();
        // The lots changed and a lot with a day of its own is now the first: its day is the first lot's.
        if (S.lots[0] && S.lots[0].planting) { if (each || !S.planting) S.planting = S.lots[0].planting; S.lots[0].planting = null; }
        const c0 = lcOf(S.lots[0]), w0 = dayWordFor(c0.method);
        const words = new Set(S.lots.map((l) => dayWordFor(lcOf(l).method)));
        $('lpDateQ').textContent = !many ? 'When will you ' + w0 + '?' : 'When will you ' + (words.size === 1 ? w0 : 'plant') + ' your lots?';
        $('lpDateSub').textContent = !many ? 'Everything is dated back from this day.'
            : (each ? 'Pick a day for every lot. Each lot\'s work is dated back from its own day.' : 'Pick the day of the first lot. Everything is dated back from it.');
        const mode = $('lpDateMode');
        mode.hidden = !many;
        mode.querySelectorAll('[data-dmode]').forEach((b) => { const on = b.dataset.dmode === S.dateMode; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
        const fl = $('lpDateFor');
        fl.hidden = !many;
        if (many) fl.innerHTML = '<i style="--lc:' + lc(0) + '"></i>' + esc(lotLabel(S.lots[0], 0)) + ' <small>' + esc(capW(w0)) + 'ing day</small>';
        const t = $('lpDateTag');
        t.classList.toggle('is-none', !S.planting);
        $('lpDateMon').textContent = S.planting ? fmt(S.planting, { month: 'short' }) : 'Day';
        $('lpDateDay').textContent = S.planting ? fmt(S.planting, { day: 'numeric' }) : '?';
        $('lpDateText').textContent = S.planting ? fmt(S.planting, { weekday: 'short', month: 'short', day: 'numeric' }) : 'Pick the day';
        $('lpDateLine').textContent = S.planting ? fmt(S.planting, { year: 'numeric' }) + ', ' + inDays(S.planting) : 'Opens a calendar';
        const lead = leadOf(c0);
        const earliest = ds(dn(TODAY) + lead);
        const h = $('lpDateHint');
        if (!S.method) h.hidden = true;
        else {
            if (S.planting && S.planting < earliest) {
                h.className = 'lp-hint is-warn';
                h.innerHTML = 'The full preparation starts about <b>' + lead + ' days</b> before. This day leaves <b>' + (dn(S.planting) - dn(TODAY)) + '</b>, so Anee squeezes the plan to fit. For every step at its own pace, ' + w0 + ' on or after <b>' + esc(day(earliest)) + '</b>.';
            } else {
                h.className = 'lp-hint';
                h.innerHTML = 'The full preparation starts about <b>' + lead + ' days</b> before the ' + w0 + ' day. To follow every step from today, ' + w0 + ' on or after <b>' + esc(day(earliest)) + '</b>.';
            }
            h.hidden = false;
        }
        paintDateLots(swap);
    }
    // Under the first lot's day: Anee timing the others, or every other lot with its own day.
    function paintDateLots(swap) {
        const many = S.lots.length > 1, each = eachDay();
        let html = '';
        if (many && !each) {
            const rest = S.lots.slice(1);
            html = '<div class="lp-dauto"><span aria-hidden="true">🔀</span><span><b>Anee times ' + esc(rest.length === 1 ? lotLabel(rest[0], 1) : 'the other ' + rest.length + ' lots') + '</b><small>'
                + (rest.length > 1 ? esc(rest.map((l, k) => lotLabel(l, k + 1)).join(' · ')) + '. ' : '')
                + 'Each goes in as soon as your crew can have it ready' + (S.planting ? ', from ' + esc(day(S.planting)) + ' on' : '') + '. You see their days when you add your workers.</small></span></div>';
        } else if (each) {
            html = '<div class="lp-dlots">' + S.lots.slice(1).map((l, k) => {
                const i = k + 1, c = lcOf(l), d = dayOf(l, i), lead = leadOf(c), left = d ? dn(d) - dn(TODAY) : null;
                return '<div class="lp-dlot" style="--lc:' + lc(i) + ';animation-delay:' + Math.min(k, 8) * 40 + 'ms"><div class="lp-dlot-h"><i></i><b>' + esc(lotLabel(l, i)) + '</b><small>' + esc(capW(dayWordFor(c.method))) + 'ing day</small></div>'
                    + '<div class="lp-own"><button type="button" data-lot-date="' + l.uid + '">📅 ' + (d ? esc(day(d)) : 'Pick the day') + '</button>'
                    + (l.planting ? '<button type="button" class="is-x" data-lot-same="' + l.uid + '">Use Lot 1\'s day</button>' : (d ? '<span class="lp-same-day">Same as Lot 1</span>' : '')) + '</div>'
                    + (d && left < lead ? '<p class="lp-dlot-t">⏳ ' + daysN(Math.max(0, left)) + ' left, and the full preparation needs about ' + lead + '. Anee squeezes it to fit.</p>' : '')
                    + '</div>';
            }).join('') + '</div>' + closeHtml();
        }
        const box = $('lpDateLots');
        if (swap) fadeSwap(box, html); else { box._html = html; box.innerHTML = html; }
    }
    // Lots that go in close together are prepared at the same time: said now, the crew is checked on the Workers step.
    function closeHtml() {
        if (!S.planting) return '';
        const at = S.lots.map((l, i) => ({ i, d: dn(dayOf(l, i)), ha: numOk(l.area) ? toHa(l.area, l.unit) : 0 })).sort((a, b) => a.d - b.d || a.i - b.i);
        const groups = [];
        at.forEach((x) => { const g = groups[groups.length - 1]; if (g && x.d - g[g.length - 1].d <= 3) g.push(x); else groups.push([x]); });
        const close = groups.filter((g) => g.length > 1);
        if (!close.length) return '';
        const names = (g) => { const n = g.map((x) => x.i).sort((a, b) => a - b).map((i) => 'Lot ' + (i + 1)); return n.length === 2 ? n.join(' and ') : n.slice(0, -1).join(', ') + ' and ' + n[n.length - 1]; };
        return '<div class="lp-hint is-warn lp-dclose">' + close.map((g) => '<b>' + esc(names(g)) + '</b> ' + (g.every((x) => x.d === g[0].d) ? 'go in on the same day' : 'go in within ' + daysN(g[g.length - 1].d - g[0].d) + ' of each other')
            + (g.some((x) => x.ha > 0) ? ', ' + esc(ha(g.reduce((s2, x) => s2 + x.ha, 0))) + ' in all' : '') + '.').join(' ')
            + ' Your crew prepares them at the same time. When you add your workers, Anee checks they are enough for these days.</div>';
    }
    $('lpDateMode').addEventListener('click', (e) => {
        const b = e.target.closest('[data-dmode]');
        if (!b || b.dataset.dmode === S.dateMode) return;
        S.dateMode = b.dataset.dmode;
        paintDate(true);
    });
    $('lpDateLots').addEventListener('click', (e) => {
        const d = e.target.closest('[data-lot-date]');
        if (d) { openDate(Number(d.dataset.lotDate)); return; }
        const s2 = e.target.closest('[data-lot-same]');
        if (s2) { const l = S.lots.find((x) => String(x.uid) === s2.dataset.lotSame); if (l) { l.planting = null; paintDateLots(true); } }
    });
    const DS = { month: TODAY.slice(0, 7), sel: null, target: null, lead: 30 };
    function openDate(target) {
        DS.target = target || null;
        const lot = target ? S.lots.find((l) => l.uid === target) : null;
        const i = lot ? S.lots.indexOf(lot) : 0;
        const c = lcOf(lot || S.lots[0]);
        const cur = lot ? lot.planting || S.planting : S.planting;
        DS.lead = leadOf(c);
        DS.sel = cur;
        DS.month = (cur || ds(dn(TODAY) + DS.lead)).slice(0, 7);
        const w = dayWordFor(c.method);
        $('lpDateTitle').textContent = S.lots.length > 1 ? capW(w) + 'ing day of ' + lotLabel(S.lots[i], i) : 'The ' + w + 'ing day';
        paintMonth(0); paintPick();
        sheetOpen($('lpDateSheet'));
    }
    function paintMonth(dir) {
        const [y, m] = DS.month.split('-').map(Number);
        const first = Date.UTC(y, m - 1, 1) / 864e5;
        const count = new Date(Date.UTC(y, m, 0)).getUTCDate();
        const lead = new Date(first * 864e5).getUTCDay();
        const ok = ds(dn(TODAY) + DS.lead);
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
        if (!DS.sel) { $('lpPickDate').textContent = 'Tap a day'; $('lpPickLine').textContent = 'Days before today are off'; $('lpDateUse').disabled = true; return; }
        $('lpPickDate').textContent = fmt(DS.sel, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        const early = DS.sel < ds(dn(TODAY) + DS.lead);
        $('lpPickLine').textContent = inDays(DS.sel) + (early ? ', the plan is squeezed' : ', time for every step');
        $('lpDateUse').disabled = false;
    }
    function pickDay(s) {
        DS.sel = s;
        $('lpDays').querySelectorAll('.lp-d').forEach((b) => { const on = b.dataset.d === s; b.classList.toggle('is-sel', on); if (b.dataset.d) b.setAttribute('aria-pressed', String(on)); });
        paintPick();
    }
    $('lpDays').addEventListener('click', (e) => { const b = e.target.closest('[data-d]'); if (b && !b.disabled) pickDay(b.dataset.d); });
    const shiftMonth = (k) => {
        const [y, m] = DS.month.split('-').map(Number);
        const nx = new Date(Date.UTC(y, m - 1 + k, 1)).toISOString().slice(0, 7);
        if (nx < TODAY.slice(0, 7) || nx > MAX_DATE.slice(0, 7)) return;
        DS.month = nx; paintMonth(k);
    };
    $('lpPrevM').addEventListener('click', () => shiftMonth(-1));
    $('lpNextM').addEventListener('click', () => shiftMonth(1));
    $('lpDateUse').addEventListener('click', () => {
        if (!DS.sel) return;
        if (DS.target) {
            const l = S.lots.find((x) => x.uid === DS.target);
            if (l) l.planting = DS.sel;
            paintDateLots(true);
        } else {
            S.planting = DS.sel;
            $('lpDateErr').hidden = true;
            paintDate(true);
            bump($('lpDateTag'));
        }
        sheetClose($('lpDateSheet'));
    });
    $('lpDateTag').addEventListener('click', () => openDate(null));

    /* ---------------- the time check: a warning, never a stop ---------------- */
    const lotCropBody = (l) => (l.lc ? { crop: l.lc.crop, method: l.lc.method, variety: l.lc.variety || null, seedType: l.lc.seedType || null } : {});
    function checkBody(withAnswers) {
        return {
            crop: S.crop, method: S.method, variety: S.variety || null, planting: S.planting, dateMode: S.lots.length > 1 ? S.dateMode : null,
            lots: S.lots.map((l, i) => Object.assign({ name: l.name.trim() || 'Lot ' + (i + 1), area: numOk(l.area) ? num(l.area) : null, unit: l.unit, planting: lotDay(l, i) },
                lotCropBody(l), withAnswers ? { profile: l.profile } : {})),
            crew: { workers: S.workers.map((w) => ({ name: w.name, days: w.days })), fleet: fleetList().map(({ type, own, hired }) => ({ type, own, hired })) },
        };
    }
    async function checkNow(withAnswers, extra) {
        try {
            const d = (await window.api(U.check, { method: 'POST', body: Object.assign(checkBody(withAnswers), extra || {}) })).data;
            // The server's price for this very plan (it knows which varieties need a lookup).
            if (withAnswers && Number(d.quote) > 0) QUOTE_NOW[quoteKey()] = Number(d.quote);
            return d;
        } catch (_) { return null; }
    }
    async function timeCheck(withAnswers) { const d = await checkNow(withAnswers); return d ? d.time || [] : null; }
    const ACK = { date: null, review: null };
    function timeHtml(t) {
        const short = t.filter((x) => x.short);
        const many = S.lots.length > 1;
        const word = dayWordOf() + 'ing';
        let tlSq = false, tlRest = false;    // one flag a term in the sheet
        return short.map((x) => {
            const pct = Math.max(4, Math.min(100, Math.round((x.need - Math.max(0, x.left)) / Math.max(1, x.need) * 100)));
            const sqAt = x.steps.length && !tlSq ? (tlSq = true, tlAt('squeezed-steps')) : '';
            const restFlag = x.rest && !tlRest ? (tlRest = true, tlf('fallow')) : '';
            return '<div class="lp-tw">' + (many ? '<b class="lp-tw-lot" style="--lc:' + lc(x.lot) + '"><i></i>' + esc(x.name) + '</b>' : '')
                + '<div class="lp-tw-stats"><div><b>' + x.need + '</b><small>days the full preparation needs</small></div><div class="is-left"><b>' + Math.max(0, x.left) + '</b><small>days left to ' + esc(day(x.planting)) + '</small></div></div>'
                + '<div class="lp-tw-bar" aria-hidden="true"><i style="width:' + pct + '%"></i></div><div class="lp-tw-cap"><span>Today</span><span>' + (x.need - Math.max(0, x.left)) + ' days short</span></div>'
                + (x.steps.length ? '<p class="lp-tw-h"' + sqAt + '>Squeezed to fit</p><ul class="lp-tw-list">' + x.steps.slice(0, 8).map((s) => '<li><span class="' + (s.state === 'missed' ? 'is-moved' : 'is-short') + '">' + (s.state === 'missed' ? 'Moved up' : 'Shortened') + '</span><em>' + esc(s.what) + '</em></li>').join('')
                    + (x.steps.length > 8 ? '<li><em>and ' + (x.steps.length - 8) + ' more</em></li>' : '') + '</ul>' : '')
                + (x.rest ? '<p class="lp-tw-h" style="text-transform:none;letter-spacing:0;font-weight:700">The field also needs ' + x.rest + ' days of rest' + restFlag + ' without rice before sowing, as PhilRice advises.</p>' : '')
                + (x.restNotYet ? '<p class="lp-tw-h" style="text-transform:none;letter-spacing:0;font-weight:700">The last crop is not harvested yet: count 30 days without rice from the harvest.</p>' : '')
                + '</div>';
        }).join('') + '<div class="lp-tw-note"><span>🔎</span><span>Anee keeps the plowing, every harrowing and the leveling in order and shortens the waiting between them. Before you go on, walk the field and check it carefully: the dikes, the water and how hard the soil is. For every step at its own pace, ' + esc(dayWordOf()) + ' on or after ' + esc(day(short.map((x) => x.earliest).sort().pop() || TODAY)) + '.</span></div>';
    }
    function timeWarn(t, where) {
        const sig = JSON.stringify(t.map((x) => [x.lot, x.left, x.need]));
        if (ACK[where] === sig) return Promise.resolve(true);
        $('lpTimeBody').innerHTML = timeHtml(t);
        $('lpTimeTitle').firstChild.textContent = 'Little time for the full preparation';
        return new Promise((resolve) => {
            let go = false;
            const dlg = $('lpTimeSheet');
            $('lpTimeGo').onclick = () => { go = true; ACK[where] = sig; sheetClose(dlg); };
            dlg.addEventListener('lpclosed', () => resolve(go), { once: true });
            sheetOpen(dlg);
        });
    }

    /* ---------------- lots: one card each ---------------- */
    // A farm lot's head: its name, place and answers (they come with it), and its sheet one tap away.
    function farmHead(l) {
        const FP = window.aneeFieldProfile, n = FP ? FP.count(l.profile) : 0, ask = askedPages(l).length;
        const C = window.aneeLots && window.aneeLots.crops ? window.aneeLots.crops[l.crop] : null;
        return '<div class="lp-flot"><span aria-hidden="true">' + (C && C[2] ? esc(C[2]) : '<img src="' + esc(U.lotIcon) + '" alt="">') + '</span><div><b>' + esc(l.name) + '</b>'
            + (l.place ? '<small>' + esc(l.place) + '</small>' : '<small class="is-none">No place yet</small>')
            + '<span class="lp-flchips">' + (n ? '<span>' + n + (n === 1 ? ' answer' : ' answers') + '</span>' : '')
            + (ask ? '<span class="is-ask">' + (n ? ask + (ask === 1 ? ' page' : ' pages') + ' of questions next' : 'Its questions come next') + '</span>' : '')
            + (l.stale ? '<span class="is-old">Check it</span>' : '') + '</span></div>'
            + '<button type="button" class="lp-fledit" data-lot-edit="' + l.uid + '" aria-label="Edit ' + esc(l.name) + '">Edit</button></div>'
            + '<button type="button" class="lp-flprev" data-lot-prev="' + l.uid + '" aria-expanded="false">See its answers</button><div class="lp-flprevhost" data-prevhost="' + l.uid + '"></div>'
            + (l.stale ? '<div class="lp-flold"><span><b>' + esc(l.name) + '</b> was last checked on ' + esc(l.updated) + ', over six months ago. Are its answers still right?</span>'
                + '<div><button type="button" class="is-main" data-lot-edit="' + l.uid + '">Check them</button><button type="button" data-lot-fine="' + l.uid + '">Still correct</button></div></div>' : '');
    }
    // Where the plan reads the weather, when the lots bring their place.
    function paintLotPlace() {
        const h = $('lpLotPlace'), lp = lotPlace();
        if (!lp) { h.hidden = true; return; }
        h.className = 'lp-hint' + (lp.mixed ? ' is-warn' : '');
        h.innerHTML = lp.mixed
            ? 'Your lots are in different towns. The plan reads the weather at the place of <b>' + esc(lp.name) + '</b>, the first one: ' + esc(lp.where) + '.'
            : 'The place comes from your lots: <b>' + esc(lp.where) + '</b>. Anee reads the weather there.';
        h.hidden = false;
    }
    function paintLots() {
        if (!S.lots.length) {
            $('lpLots').innerHTML = '<div class="lp-lotsnone"><span aria-hidden="true">🗺️</span><div><b>No lots in this plan yet</b><small>Add them from your lots: each brings its place, size and answers. Or tick the lots of one of your seasons.</small></div></div>';
            total(); paintLotPlace(); paintCount();
            return;
        }
        const haAt = S.lots.findIndex((x) => x.unit === 'ha');     // the hectare flag, on the first lot in hectares
        $('lpLots').innerHTML = S.lots.map((l, i) => '<div class="lp-lot" data-uid="' + l.uid + '" style="--lc:' + lc(i) + ';animation-delay:' + (i * 40) + 'ms">'
            + '<div class="lp-lot-top"><i></i><b>Lot ' + (i + 1) + '</b>' + (l.from ? '<em>from ' + esc(l.from) + '</em>' : (l.farmLotId ? '<em>from your lots</em>' : '')) + '</div>'
            + '<button type="button" class="lp-lot-x" data-lot-x="' + l.uid + '" aria-label="Remove lot ' + (i + 1) + '">' + XSM + '</button>'
            + (l.farmLotId ? farmHead(l)
                : '<div class="lp-field" data-f="name"><label for="lpLn' + l.uid + '">Name of the lot <small>(optional)</small></label><input type="text" id="lpLn' + l.uid + '" class="form-input" data-in="name" maxlength="60" value="' + esc(l.name) + '" autocomplete="off"></div>')
            + '<div class="lp-field" data-f="area"><label for="lpLa' + l.uid + '">Size</label><div class="lp-size"><input type="text" id="lpLa' + l.uid + '" class="form-input" data-in="area" inputmode="decimal" maxlength="10" value="' + esc(l.area) + '" autocomplete="off">'
            + '<button type="button" class="lp-unit" data-unit="' + l.uid + '" aria-haspopup="dialog" aria-label="Unit: ' + esc(UNITS[l.unit][0]) + '">' + esc(UNITS[l.unit][1]) + (i === haAt ? tlf('hectare') : '') + CHEV + '</button></div>'
            + '<p class="lp-size-eq" data-eq>' + eqWords(l) + '</p><p class="lp-ferr" data-err hidden></p></div>'
            + '</div>').join('');
        total();
        paintLotPlace();
        paintCount();
    }
    const eqWords = (l) => (l.unit !== 'ha' && num(l.area) > 0 && toHa(l.area, l.unit) >= 0.01 ? '= ' + ha(toHa(l.area, l.unit)) : '');

    function total() {
        const a = S.lots.reduce((s, l) => s + toHa(l.area, l.unit), 0);
        $('lpLotTotal').textContent = S.lots.length + (S.lots.length === 1 ? ' lot' : ' lots') + (a > 0 ? ' · ' + ha(a) + ' in all' : '');
    }
    function lotErr(l, msg) {
        const card = $('lpLots').querySelector('.lp-lot[data-uid="' + l.uid + '"]');
        const f = card && card.querySelector('[data-f="area"]');
        if (!f) return;
        f.classList.toggle('is-bad', !!msg);
        const e = f.querySelector('[data-err]');
        e.hidden = !msg; e.textContent = msg || '';
    }
    function checkLots() {
        if (!S.lots.length) {
            window.toast?.('Add a lot first, from your lots.', 'error');
            shake($('lpLotAdd').parentNode);
            return false;
        }
        let first = null;
        S.lots.forEach((l, i) => {
            let msg = '';
            if (String(l.area).trim() === '') msg = 'Type the size of ' + (l.name.trim() || 'Lot ' + (i + 1)) + '.';
            else if (!numOk(l.area) || !(num(l.area) > 0)) msg = 'Type the size as a number, like 1.5 or 2500.';
            else if (toHa(l.area, l.unit) < 0.001 || toHa(l.area, l.unit) > 500) msg = 'A lot must be from 10 square meters to 500 hectares.';
            lotErr(l, msg);
            if (msg && !first) first = l;
        });
        if (!first) return true;
        const f = $('lpLots').querySelector('.lp-lot[data-uid="' + first.uid + '"] [data-f="area"]');
        f.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' });
        setTimeout(() => { shake(f.querySelector('.lp-size')); f.querySelector('input').focus({ preventScroll: true }); }, reduce() ? 0 : 280);
        return false;
    }
    $('lpLots').addEventListener('input', (e) => {
        const inp = e.target.closest('[data-in]');
        if (!inp) return;
        const card = inp.closest('.lp-lot'), l = S.lots.find((x) => String(x.uid) === card.dataset.uid);
        if (!l) return;
        l[inp.dataset.in] = inp.value;
        if (inp.dataset.in === 'area') {
            l.areaFromLot = false;
            if (card.querySelector('[data-f="area"]').classList.contains('is-bad') && numOk(l.area)) lotErr(l, '');
            card.querySelector('[data-eq]').textContent = eqWords(l);
        }
        total();
        syncFq();
    });
    let UNIT_FOR = null;
    $('lpLots').addEventListener('click', async (e) => {
        // A farm lot: its sheet ("Edit", "Check them"), or "Still correct" (the six months start again).
        const ed = e.target.closest('[data-lot-edit]');
        if (ed) {
            const l = S.lots.find((y) => String(y.uid) === ed.dataset.lotEdit);
            if (l && await editFarm(l)) { paintLots(); syncFq(); }
            return;
        }
        // The lot's answers as cards to slide through (partials/farm-lots), under its card.
        const pv = e.target.closest('[data-lot-prev]');
        if (pv) {
            const l = S.lots.find((y) => String(y.uid) === pv.dataset.lotPrev);
            if (!l || !window.aneeLots || !window.aneeLots.togglePreview) return;
            const lot = Object.assign({}, l.lot || {}, { id: l.farmLotId, name: l.name, answers: clone(l.profile), count: window.aneeFieldProfile ? window.aneeFieldProfile.count(l.profile) : 0 });
            const open = window.aneeLots.togglePreview($('lpLots').querySelector('[data-prevhost="' + l.uid + '"]'), lot, { crop: S.crop, onEdit: async () => { await editFarm(l); paintLots(); } });
            pv.textContent = open ? 'Hide its answers' : 'See its answers';
            pv.setAttribute('aria-expanded', String(open));
            return;
        }
        const fine = e.target.closest('[data-lot-fine]');
        if (fine) {
            const l = S.lots.find((y) => String(y.uid) === fine.dataset.lotFine);
            if (!l || !window.aneeLots) return;
            fine.disabled = true;
            try { refreshFarm(l, await window.aneeLots.confirm(Object.assign({}, l.lot, { id: l.farmLotId })), true); paintLots(); }
            catch (err) { fine.disabled = false; window.toast?.(err.message || 'That did not save. Please try again.', 'error'); }
            return;
        }
        const x = e.target.closest('[data-lot-x]');
        if (x) {
            const el = x.closest('.lp-lot');
            el.classList.add('is-gone');
            setTimeout(() => {
                const gone = S.lots.findIndex((l) => String(l.uid) === x.dataset.lotX);
                S.lots = S.lots.filter((l) => String(l.uid) !== x.dataset.lotX);
                S.lots.forEach((l) => { if (l.copiedFrom === gone) l.copiedFrom = null; else if (l.copiedFrom !== null && l.copiedFrom > gone) l.copiedFrom--; });
                paintLots(); syncFq();
            }, reduce() ? 0 : 220);
            return;
        }
        const u = e.target.closest('[data-unit]');
        if (u) {
            UNIT_FOR = Number(u.dataset.unit);
            const l = S.lots.find((y) => y.uid === UNIT_FOR);
            $('lpUnitList').innerHTML = Object.entries(UNITS).map(([k, w]) => '<button type="button" class="lp-row" role="option" data-u="' + k + '" aria-selected="' + (l && l.unit === k) + '"><span class="ri">' + ({ ha: '🟩', sqm: '📐', acre: '🗺️' }[k]) + '</span><span class="rt"><b>' + esc(w[0].replace(/^\w/, (c) => c.toUpperCase())) + '</b><small>'
                + ({ ha: '10,000 square meters, about 100 by 100 meters', sqm: 'The length times the width, in meters', acre: 'About 4,047 square meters' }[k]) + '</small></span>' + (l && l.unit === k ? '<span class="rn">In use</span>' : '') + '</button>').join('');
            sheetOpen($('lpUnitSheet'));
            return;
        }
    });
    $('lpUnitList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-u]');
        if (!b) return;
        const l = S.lots.find((y) => y.uid === UNIT_FOR);
        if (l) l.unit = b.dataset.u;
        sheetClose($('lpUnitSheet'));
        paintLots();
        const card = $('lpLots').querySelector('.lp-lot[data-uid="' + UNIT_FOR + '"] .lp-unit');
        if (card) { card.classList.remove('is-bump'); bump(card); }
    });
    /* "Add from your lots" (2026-10-16, the owner): the farm's lots, ticked one
       or several (partials/farm-lots draws each card), or a new one made in the
       lot sheet, which asks its place, size and questions once for every analysis. */
    const FARM = { picked: new Set(), rows: [] };
    const farmCount = () => {
        const n = FARM.picked.size;
        $('lpFarmCount').textContent = n ? n + (n === 1 ? ' lot ticked' : ' lots ticked') : 'None ticked';
        $('lpFarmUse').disabled = !n;
    };
    async function paintFarm(fresh) {
        const L = window.aneeLots;
        FARM.rows = await L.rows(fresh);
        const inPlan = new Set(S.lots.map((l) => l.farmLotId).filter(Boolean));
        const card = (r, i) => {
            // A tick list here (the kit draws a radio card); a lot already in the plan cannot be added twice.
            let h = L.card(r, inPlan.has(r.id) || FARM.picked.has(r.id), i).replace('role="radio"', 'role="checkbox"');
            if (inPlan.has(r.id)) h = h.replace('<button type="button" class="fl-card', '<button type="button" disabled class="fl-card').replace('<span class="fl-chips">', '<span class="fl-chips"><span class="fl-chip is-in">In the plan</span>');
            return h;
        };
        $('lpFarmList').innerHTML = (FARM.rows.length ? FARM.rows.map(card).join('')
            : '<div class="fl-empty">No lots yet. Add your first one: where it is, its size, and its soil and water. You answer it once, and every analysis reads it.</div>')
            + '<button type="button" class="fl-add" data-farm-new>' + PLUS + (FARM.rows.length ? 'Add a new lot' : 'Add a lot') + '</button>'
            + '<p class="fl-more"><span>Your lots are kept in Global Lots.</span><a href="' + esc(U.lots) + '" target="_blank" rel="noopener">Open Global Lots</a></p>';
    }
    $('lpLotAdd').addEventListener('click', async () => {
        if (S.lots.length >= 12) { window.toast?.('Up to 12 lots in one plan.', 'error'); return; }
        if (!window.aneeLots) { window.toast?.('Your lots could not load. Please try again.', 'error'); return; }
        FARM.picked.clear();
        farmCount();
        $('lpFarmList').innerHTML = '<p class="lp-snone">Loading your lots…</p>';
        sheetOpen($('lpFarmSheet'));
        try { await paintFarm(false); } catch (_) { $('lpFarmList').innerHTML = '<p class="lp-snone">Your lots could not load. Please try again.</p>'; }
    });
    $('lpFarmList').addEventListener('click', async (e) => {
        const L = window.aneeLots;
        if (!L) return;
        if (e.target.closest('[data-farm-new]')) {
            const row = await L.edit(null, { crop: S.crop });
            if (row) { FARM.picked.add(row.id); await paintFarm(true); farmCount(); }
            return;
        }
        const c = e.target.closest('[data-fl-lot]');
        if (!c || c.disabled) return;
        const id = Number(c.getAttribute('data-fl-lot'));
        const r = FARM.rows.find((x) => x.id === id);
        if (!r) return;
        if (!r.where && !FARM.picked.has(id)) {
            // A lot saved before the lots had a place: say where it is first.
            const row = await L.edit(r, { crop: S.crop, reason: 'place', ask: false });
            if (row) { FARM.picked.add(row.id); await paintFarm(true); farmCount(); }
            return;
        }
        if (FARM.picked.has(id)) FARM.picked.delete(id); else FARM.picked.add(id);
        const on = FARM.picked.has(id);
        c.classList.toggle('is-on', on);
        c.setAttribute('aria-checked', String(on));
        farmCount();
    });
    $('lpFarmUse').addEventListener('click', () => {
        let full = false;
        // In the order they were ticked: the first one's place is the plan's.
        [...FARM.picked].map((id) => FARM.rows.find((r) => r.id === id)).filter(Boolean).forEach((r) => {
            if (S.lots.some((x) => x.farmLotId === r.id)) return;
            if (S.lots.length >= 12) { full = true; return; }
            S.lots.push(fromFarm(r));
        });
        if (full) window.toast?.('Up to 12 lots in one plan.', 'error');
        paintLots(); syncFq();
        sheetClose($('lpFarmSheet'));
    });
    /* ---------------- the machines ---------------- */
    // A machine any lot can use: the plan's crop, or a lot's own.
    const eqFor = () => {
        const cs = [lcOf(null)].concat(S.lots.filter((l) => l.lc).map((l) => l.lc));
        return Object.fromEntries(Object.entries(OPT.equipment).filter(([, e]) => e.for === 'all'
            || (e.for === 'rice_wet' && cs.some((c) => String(c.crop).startsWith('rice') && c.method !== 'dsr_dry'))
            || (Array.isArray(e.for) && cs.some((c) => e.for.includes(c.method)))));
    };
    const fleetNow = () => { const ok = eqFor(); return Object.fromEntries(Object.entries(S.fleet).filter(([k, f]) => ok[k] && (f.own + f.hired) > 0)); };
    const fleetList = () => Object.entries(fleetNow()).map(([type, f]) => ({ type, own: f.own, hired: f.hired,
        cost: f.hired && num(f.cost) > 0 ? num(f.cost) : null, costUnit: f.costUnit || 'ha', hire: f.hired && f.hire ? f.hire : null }));
    const newFleet = (o) => Object.assign({ own: 0, hired: 0, cost: '', costUnit: 'ha', hire: null }, o || {});
    const HUNITS = [['ha', 'a hectare'], ['day', 'a day'], ['job', 'whole job']];
    // Under a machine with a hired one: who is hired (Contact List) and the service price. Both optional.
    const hireHtml = (k, f, closed) => '<div class="lp-hire' + (closed ? ' is-closed' : '') + '" data-hire="' + k + '"><div><div class="lp-hire-in">'
        + '<span class="lp-hire-h">Who do you hire? <small>(optional)</small></span>'
        + (f.hire ? '<div class="lp-hchip"><i>' + esc((f.hire.name || '?').trim().charAt(0).toUpperCase()) + '</i><span><b>' + esc(f.hire.name) + '</b><small>' + esc(f.hire.phone || 'No number saved') + '</small></span><button type="button" data-hclear="' + k + '" aria-label="Remove ' + esc(f.hire.name) + '">' + XSM + '</button></div>'
            : '<button type="button" class="lp-hpick" data-hpick="' + k + '"><span aria-hidden="true">📇</span><span>Pick from your contacts</span></button>')
        + '<span class="lp-hire-h">Their price <small>(optional)</small></span>'
        + '<div class="lp-pay"><label class="lp-money"><em>' + esc(CUR) + '</em><input type="text" inputmode="decimal" data-hcost="' + k + '" value="' + esc(f.cost || '') + '" placeholder="0" aria-label="Price of the hired ' + esc(String((OPT.equipment[k] || {}).name || '').toLowerCase()) + '"></label>'
        + '<div class="lp-unit2" role="group" aria-label="The price is for">' + HUNITS.map(([u, w]) => '<button type="button" data-hunit="' + k + ':' + u + '" class="' + ((f.costUnit || 'ha') === u ? 'is-on' : '') + '" aria-pressed="' + ((f.costUnit || 'ha') === u) + '">' + w + '</button>').join('') + '</div></div>'
        + '<small class="lp-hire-note">' + ((f.costUnit || 'ha') === 'ha' ? 'A hectare counts every pass: plowing once and harrowing twice is three hectares on one hectare.' : (f.costUnit === 'day' ? 'Every day the machine is on your lots counts.' : 'One price for all the work it does on your lots.')) + '</small>'
        + '</div></div></div>';
    const photo = (e, alt) => '<span class="lp-ph">' + (e.photo ? '<img src="' + esc(e.photo) + '" alt="' + esc(alt || '') + '" loading="lazy">' : '🚜') + '</span>';
    const counters = (k, f) => '<div class="lp-ct"><span>Own</span><div><button type="button" data-ct="' + k + ':own:-1" aria-label="One less owned"' + (f.own < 1 ? ' disabled' : '') + '>−</button><output>' + f.own + '</output><button type="button" data-ct="' + k + ':own:1" aria-label="One more owned">+</button></div></div>'
        + '<div class="lp-ct is-hire"><span>Hired</span><div><button type="button" data-ct="' + k + ':hired:-1" aria-label="One less hired"' + (f.hired < 1 ? ' disabled' : '') + '>−</button><output>' + f.hired + '</output><button type="button" data-ct="' + k + ':hired:1" aria-label="One more hired">+</button></div></div>';
    function paintEquip() {
        const items = eqFor(), fleet = fleetNow();
        const keys = Object.keys(fleet);
        let h = '';
        if (keys.length) {
            h += '<div class="lp-eqs">' + keys.map((k, i) => '<div class="lp-eq" data-eqc="' + k + '" style="animation-delay:' + (i * 40) + 'ms">' + photo(items[k], items[k].name) + '<span class="et"><b>' + esc(items[k].name) + tlf(EQ_TL[k]) + (items[k].local ? '<span class="lp-loc">' + esc(items[k].local) + '</span>' : '') + '</b><small>' + esc(items[k].line) + '</small></span>'
                + '<div class="lp-cts">' + counters(k, fleet[k]) + '</div>' + (fleet[k].hired > 0 ? hireHtml(k, fleet[k], !!fleet[k]._opening) : '') + '</div>').join('') + '</div>';
            h += '<button type="button" class="lp-tag lp-eqadd" id="lpEqOpen"><span class="ti">➕</span><span class="tt"><b>Change the machines</b><small>' + keys.length + ' picked</small></span><span class="tc">Open' + CHEV + '</span></button>';
        } else {
            h += '<button type="button" class="lp-tag' + (S.handOnly ? '' : ' is-none') + '" id="lpEqOpen"><span class="ti">🚜</span><span class="tt"><b>Choose the machines</b><small>Hand tractor, four wheel tractor, carabao and more</small></span><span class="tc">Open' + CHEV + '</span></button>';
        }
        h += '<span class="lp-label">Or</span><button type="button" class="lp-choice' + (S.handOnly && !keys.length ? ' is-on' : '') + '" id="lpHandOnly"><span class="ce">🧑‍🌾</span><span class="ct"><b>By hand only</b><small>Hoes and shovels, no machine and no animal</small></span><span class="ck">' + CHECK + '</span></button>';
        h += '<p class="lp-ferr" id="lpEqErr" hidden>Pick the machines, or choose By hand only.</p>';
        $('lpEquipBox').innerHTML = h;
        // A hired machine just added: its panel opens.
        keys.forEach((k) => { if (fleet[k]._opening) { delete fleet[k]._opening; const p = $('lpEquipBox').querySelector('[data-hire="' + k + '"]'); if (p) requestAnimationFrame(() => requestAnimationFrame(() => p.classList.remove('is-closed'))); } });
    }
    function paintEqSheet() {
        const items = eqFor();
        $('lpEqList').innerHTML = Object.entries(items).map(([k, e]) => {
            const f = S.fleet[k] && (S.fleet[k].own + S.fleet[k].hired) > 0 ? S.fleet[k] : null;
            return '<div class="lp-eqrow' + (f ? ' is-on' : '') + '" data-eqk="' + k + '"><button type="button" role="checkbox" aria-checked="' + !!f + '" data-eqt="' + k + '">' + photo(e, e.name) + '<span class="et"><b>' + esc(e.name) + tlf(EQ_TL[k]) + (e.local ? '<span class="lp-loc">' + esc(e.local) + '</span>' : '') + '</b><small>' + esc(e.line) + (e.plants ? '. For the planting day' : '') + '</small></span><span class="lp-check">' + CHECK + '</span></button>'
                + '<div class="lp-cts"><div>' + counters(k, f || { own: 0, hired: 0 }) + '</div></div></div>';
        }).join('');
        const n = Object.keys(fleetNow()).length;
        $('lpEqCount').textContent = n ? n + (n === 1 ? ' picked' : ' picked') : 'None picked';
    }
    function setCount(k, which, d) {
        const f = S.fleet[k] || (S.fleet[k] = newFleet());
        const had = f.hired;
        f[which] = Math.max(0, Math.min(20, f[which] + d));
        if (which === 'hired' && had === 0 && f.hired > 0 && !reduce()) f._opening = true;
        if (f.own + f.hired === 0) delete S.fleet[k];
        S.handOnly = false;
    }
    $('lpEquipBox').addEventListener('click', (e) => {
        if (e.target.closest('#lpEqOpen')) { paintEqSheet(); sheetOpen($('lpEqSheet')); return; }
        if (e.target.closest('#lpHandOnly')) { S.handOnly = !S.handOnly; if (S.handOnly) S.fleet = {}; paintEquip(); return; }
        const c = e.target.closest('[data-ct]');
        if (c) {
            const [k, which, d] = c.dataset.ct.split(':');
            const hadHire = (S.fleet[k] || {}).hired > 0;
            setCount(k, which, Number(d));
            if (!S.fleet[k]) { const card = c.closest('.lp-eq'); if (card && !reduce()) { card.style.transition = 'opacity .22s, transform .22s'; card.style.opacity = '0'; card.style.transform = 'scale(.97)'; setTimeout(paintEquip, 220); return; } }
            // The last hired one gone: its panel folds away first.
            const panel = $('lpEquipBox').querySelector('[data-hire="' + k + '"]');
            if (hadHire && S.fleet[k].hired === 0 && panel && !reduce()) { panel.classList.add('is-closed'); c.closest('.lp-cts').innerHTML = counters(k, S.fleet[k]); setTimeout(paintEquip, 300); return; }
            if (panel && S.fleet[k].hired > 0) { c.closest('.lp-cts').innerHTML = counters(k, S.fleet[k]); return; }   // the panel stays as it is
            paintEquip();
            return;
        }
        const hp = e.target.closest('[data-hpick]');
        if (hp) { openContacts(hp.dataset.hpick); return; }
        const hc = e.target.closest('[data-hclear]');
        if (hc) { const f = S.fleet[hc.dataset.hclear]; if (f) f.hire = null; paintEquip(); return; }
        const hu = e.target.closest('[data-hunit]');
        if (hu) {
            const [k, u] = hu.dataset.hunit.split(':');
            const f = S.fleet[k];
            if (!f) return;
            f.costUnit = u;
            hu.parentNode.querySelectorAll('button').forEach((b) => { const on = b === hu; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
            const note = hu.closest('.lp-hire-in').querySelector('.lp-hire-note');
            if (note) note.textContent = u === 'ha' ? 'A hectare counts every pass: plowing once and harrowing twice is three hectares on one hectare.' : (u === 'day' ? 'Every day the machine is on your lots counts.' : 'One price for all the work it does on your lots.');
        }
    });
    $('lpEquipBox').addEventListener('input', (e) => { const i = e.target.closest('[data-hcost]'); if (!i) return; const f = S.fleet[i.dataset.hcost]; if (f) f.cost = i.value.replace(/[^\d.,]/g, ''); });

    /* Who is hired: one of the member's contacts. */
    let CON_FOR = null;
    function paintContacts() {
        const q = $('lpConQ').value.trim().toLowerCase();
        const all = (PEOPLE && PEOPLE.contacts) || [];
        // The people who rent out machines first (their tags or role say so), then by name.
        const MACH = /tractor|rent|plow|araro|harrow|suyod|kuliglig|carabao|kalabaw|machine|makina|service|operator|transplanter|seeder|custom/i;
        const hit = (c) => (MACH.test([c.company, (c.tags || []).join(' ')].join(' ')) ? 0 : 1);
        const rows = all.filter((c) => !q || [c.name, c.phone, c.company, c.town, (c.tags || []).join(' ')].join(' ').toLowerCase().includes(q))
            .sort((a, b) => hit(a) - hit(b) || String(a.name).localeCompare(String(b.name)));
        const cur = (S.fleet[CON_FOR] || {}).hire;
        $('lpConList').innerHTML = rows.length ? rows.slice(0, 200).map((c) => '<button type="button" class="lp-row" role="option" data-con="' + c.id + '" aria-selected="' + !!(cur && cur.contactId === c.id) + '"><span class="ri">' + esc((c.name || '?').trim().charAt(0).toUpperCase()) + '</span><span class="rt"><b>' + esc(c.name) + '</b><small>'
            + esc([c.phone, c.company, c.town].filter(Boolean).join(' · ') || 'No number saved') + '</small></span>' + ((c.tags || []).length ? '<span class="rn">' + esc(c.tags[0]) + '</span>' : '') + '</button>').join('')
            : '<p class="lp-snone">' + (all.length ? 'Nobody by that name.' : 'Your Contact List is empty. You can skip this, and add the people you hire there later.') + '</p>';
    }
    async function openContacts(k) {
        CON_FOR = k;
        await loadPeople();
        $('lpConTitle').firstChild.textContent = 'Who do you hire?';
        $('lpConSub').textContent = 'For the ' + String((OPT.equipment[k] || {}).name || 'machine').toLowerCase() + ', from your Contact List';
        $('lpConQ').value = '';
        paintContacts();
        sheetOpen($('lpConSheet'), $('lpConQ'));
    }
    $('lpConQ').addEventListener('input', paintContacts);
    $('lpConList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-con]');
        if (!b || !S.fleet[CON_FOR]) return;
        const c = ((PEOPLE && PEOPLE.contacts) || []).find((x) => String(x.id) === b.dataset.con);
        if (!c) return;
        S.fleet[CON_FOR].hire = { contactId: c.id, name: c.name, phone: c.phone || '' };
        sheetClose($('lpConSheet'));
        paintEquip();
    });
    $('lpEqList').addEventListener('click', (e) => {
        const t = e.target.closest('[data-eqt]');
        if (t) {
            const k = t.dataset.eqt;
            if (S.fleet[k]) delete S.fleet[k]; else S.fleet[k] = newFleet({ own: 1 });
            S.handOnly = false;
            const row = t.closest('.lp-eqrow'), on = !!S.fleet[k];
            row.classList.toggle('is-on', on);
            t.setAttribute('aria-checked', String(on));
            row.querySelector('.lp-cts > div').innerHTML = counters(k, S.fleet[k] || { own: 0, hired: 0 });
        }
        const c = e.target.closest('[data-ct]');
        if (c) {
            const [k, which, d] = c.dataset.ct.split(':');
            setCount(k, which, Number(d));
            const row = c.closest('.lp-eqrow'), on = !!S.fleet[k];
            row.classList.toggle('is-on', on);
            row.querySelector('[data-eqt]').setAttribute('aria-checked', String(on));
            row.querySelector('.lp-cts > div').innerHTML = counters(k, S.fleet[k] || { own: 0, hired: 0 });
        }
        const n = Object.keys(fleetNow()).length;
        $('lpEqCount').textContent = n ? n + ' picked' : 'None picked';
    });
    $('lpEqSheet').addEventListener('lpclosed', () => paintEquip());
    $('lpCredOpen').addEventListener('click', () => {
        $('lpCredList').innerHTML = Object.entries(OPT.equipment).filter(([, e]) => e.credit).map(([, e]) => '<div class="lp-cred">' + photo(e, '') + '<div><b>' + esc(e.name) + '</b><br>' + esc(e.credit.author) + ', ' + esc(e.credit.license)
            + (e.credit.what ? '. ' + esc(e.credit.what) : '') + ' <a href="' + esc(e.credit.page) + '" target="_blank" rel="noopener">File page</a></div></div>').join('');
        sheetOpen($('lpCredSheet'));
    });

    /* ---------------- the workers ---------------- */
    const DAYN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    const daysWord = (d) => (d.length === 7 ? 'every day' : d.length === 0 ? 'no days' : d.length + (d.length === 1 ? ' day' : ' days'));
    function paintWorkers() {
        $('lpWCount').textContent = S.workers.length;
        $('lpWWord').textContent = S.workers.length === 1 ? 'worker' : 'workers';
        $('lpWorkers').innerHTML = S.workers.map((w, i) => '<div class="lp-wk' + (w.globalId ? ' is-roster' : '') + '" data-w="' + w.uid + '" style="animation-delay:' + Math.min(i, 8) * 30 + 'ms"><div class="lp-wk-top"><i>' + (i + 1) + '</i><input type="text" data-wname maxlength="40" value="' + esc(w.name) + '" placeholder="Name (optional)" aria-label="Name of worker ' + (i + 1) + '"><small data-wdays>' + daysWord(w.days) + '</small></div>'
            + (w.globalId ? '<span class="lp-wk-from">👥 From My workers</span>' : '')
            + '<div class="lp-d7" role="group" aria-label="Work days of worker ' + (i + 1) + '">' + DAYN.map((n, d) => '<button type="button" data-wd="' + d + '" class="' + (w.days.includes(d) ? 'is-on' : '') + '" aria-pressed="' + w.days.includes(d) + '">' + n + '</button>').join('') + '</div>'
            + '<div class="lp-pay"><span>Pay</span><label class="lp-money"><em>' + esc(CUR) + '</em><input type="text" inputmode="decimal" data-wpay value="' + esc(w.pay || '') + '" placeholder="0" aria-label="Pay of worker ' + (i + 1) + '"></label>'
            + '<div class="lp-unit2" role="group" aria-label="The pay is for">' + [['day', 'a day'], ['half', 'half a day']].map(([u, t]) => '<button type="button" data-wunit="' + u + '" class="' + (w.payUnit === u ? 'is-on' : '') + '" aria-pressed="' + (w.payUnit === u) + '">' + t + '</button>').join('') + '</div></div></div>').join('');
        $('lpSameDays').hidden = S.workers.length < 2;
        paintSaveNew();
        castSoon();
    }
    // New names may join the roster (the Workers module's plan gate decides, LandPrepController::people).
    const paintSaveNew = () => {
        const box = $('lpSaveNew');
        const any = !!(PEOPLE && PEOPLE.canSave) && S.workers.some((w) => !w.globalId && w.name.trim());
        if (box.hidden === !any) return;
        box.hidden = !any;
    };
    function paintRosterBtn() {
        const r = (PEOPLE && PEOPLE.roster) || [];
        const b = $('lpRosterOpen');
        b.hidden = !r.length;
        const inPlan = S.workers.filter((w) => w.globalId).length;
        $('lpRosterLine').textContent = r.length + (r.length === 1 ? ' worker' : ' workers') + ' on your list' + (inPlan ? ', ' + inPlan + ' in this plan' : '') + '. They bring their days off and pay.';
        paintSaveNew();
    }
    $('lpSaveNewOn').addEventListener('change', (e) => { S.saveNew = e.target.checked; });

    /* My workers: tick who works; picked, a worker brings their days off and pay. */
    let RPICK = new Set();
    function paintRoster() {
        const q = $('lpRosterQ').value.trim().toLowerCase();
        const rows = ((PEOPLE && PEOPLE.roster) || []).filter((g) => !q || (g.name + ' ' + g.phone).toLowerCase().includes(q));
        const DN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $('lpRosterList').innerHTML = rows.length ? rows.map((g) => '<button type="button" class="lp-row" role="option" data-gw="' + g.id + '" aria-checked="' + RPICK.has(g.id) + '" aria-selected="' + RPICK.has(g.id) + '"><span class="ri">' + esc((g.name || '?').trim().charAt(0).toUpperCase()) + '</span><span class="rt"><b>' + esc(g.name) + '</b><small>'
            + esc([g.halfDay > 0 ? money(g.halfDay) + ' a half day' : 'No pay saved', (g.off || []).length ? 'off ' + g.off.map((d) => DN[d]).join(', ') : '', g.phone].filter(Boolean).join(' · ')) + '</small></span><span class="lp-check">' + CHECK + '</span></button>').join('')
            : '<p class="lp-snone">Nobody by that name.</p>';
        $('lpRosterCount').textContent = RPICK.size ? RPICK.size + ' ticked' : 'None ticked';
    }
    $('lpRosterOpen').addEventListener('click', async () => {
        await loadPeople();
        RPICK = new Set(S.workers.filter((w) => w.globalId).map((w) => w.globalId));
        $('lpRosterQ').value = '';
        paintRoster();
        sheetOpen($('lpRosterSheet'), $('lpRosterQ'));
    });
    $('lpRosterQ').addEventListener('input', paintRoster);
    $('lpRosterList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-gw]');
        if (!b) return;
        const id = Number(b.dataset.gw);
        if (RPICK.has(id)) RPICK.delete(id); else RPICK.add(id);
        b.setAttribute('aria-checked', String(RPICK.has(id)));
        b.setAttribute('aria-selected', String(RPICK.has(id)));
        $('lpRosterCount').textContent = RPICK.size ? RPICK.size + ' ticked' : 'None ticked';
    });
    $('lpRosterUse').addEventListener('click', () => {
        const roster = (PEOPLE && PEOPLE.roster) || [];
        // Unticked ones leave, ticked ones fill the empty cards first, then come as new cards.
        S.workers = S.workers.filter((w) => !w.globalId || RPICK.has(w.globalId));
        const have = new Set(S.workers.map((w) => w.globalId).filter(Boolean));
        let added = 0;
        roster.filter((g) => RPICK.has(g.id) && !have.has(g.id)).forEach((g) => {
            const days = [0, 1, 2, 3, 4, 5, 6].filter((d) => !(g.off || []).includes(d));
            const facts = { name: g.name, days: (g.off || []).length ? days : [1, 2, 3, 4, 5, 6], globalId: g.id, pay: g.halfDay > 0 ? String(g.halfDay) : '', payUnit: 'half' };
            const empty = S.workers.find((w) => !w.globalId && !w.name.trim() && !num(w.pay));
            if (empty) Object.assign(empty, facts); else if (S.workers.length < 100) S.workers.push(newWorker(facts));
            added++;
        });
        if (!S.workers.length) S.workers.push(newWorker());
        sheetClose($('lpRosterSheet'));
        paintWorkers();
        paintRosterBtn();
        if (S.workers.some((x) => x.days.length)) $('lpWErr').hidden = true;
        if (added) window.toast?.(added + (added === 1 ? ' worker' : ' workers') + ' from My workers. Check their days and pay below.');
    });
    const setWorkers = (n) => {
        n = Math.max(1, Math.min(100, Math.round(n) || 1));
        while (S.workers.length < n) S.workers.push(newWorker({ days: (S.workers[S.workers.length - 1] || {}).days ? S.workers[S.workers.length - 1].days.slice() : [1, 2, 3, 4, 5, 6] }));
        if (S.workers.length > n) S.workers = S.workers.slice(0, n);
        paintWorkers();
    };
    document.querySelectorAll('[data-wstep]').forEach((b) => b.addEventListener('click', () => setWorkers(S.workers.length + Number(b.dataset.wstep))));
    $('lpWorkers').addEventListener('click', (e) => {
        const b = e.target.closest('[data-wd]');
        if (!b) return;
        const w = S.workers.find((x) => String(x.uid) === b.closest('.lp-wk').dataset.w);
        const d = Number(b.dataset.wd);
        w.days = w.days.includes(d) ? w.days.filter((x) => x !== d) : w.days.concat(d).sort();
        b.classList.toggle('is-on', w.days.includes(d));
        b.setAttribute('aria-pressed', String(w.days.includes(d)));
        b.closest('.lp-wk').querySelector('[data-wdays]').textContent = daysWord(w.days);
        if (S.workers.some((x) => x.days.length)) $('lpWErr').hidden = true;
    });
    $('lpWorkers').addEventListener('input', (e) => {
        const card = e.target.closest('.lp-wk');
        const w = card && S.workers.find((x) => String(x.uid) === card.dataset.w);
        if (!w) return;
        if (e.target.closest('[data-wname]')) { w.name = e.target.value; paintSaveNew(); }
        if (e.target.closest('[data-wpay]')) w.pay = e.target.value.replace(/[^\d.,]/g, '');
    });
    $('lpWorkers').addEventListener('click', (e) => {
        const b = e.target.closest('[data-wunit]');
        if (!b) return;
        const w = S.workers.find((x) => String(x.uid) === b.closest('.lp-wk').dataset.w);
        if (!w) return;
        w.payUnit = b.dataset.wunit;
        b.parentNode.querySelectorAll('button').forEach((x) => { const on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', String(on)); });
    });
    $('lpSameDays').addEventListener('click', (e) => {
        const b = e.target.closest('[data-same]');
        if (!b) return;
        const first = S.workers[0];
        if (b.dataset.same === 'pay') {
            if (!num(first.pay)) { window.toast?.('Type the pay of worker 1 first.', 'error'); return; }
            S.workers.forEach((w) => { w.pay = first.pay; w.payUnit = first.payUnit; });
            paintWorkers();
            window.toast?.('Every worker now gets ' + money(first.pay) + (first.payUnit === 'half' ? ' a half day.' : ' a day.'));
            return;
        }
        const d = first.days.slice(); S.workers.forEach((w) => { w.days = d.slice(); }); paintWorkers(); window.toast?.('Every worker now works ' + (d.length === 7 ? 'every day' : d.map((x) => DAYN[x]).join(', ')) + '.');
    });
    let recAsk = 0;
    async function recommend() {
        const box = $('lpRec');
        const ask = ++recAsk;
        const face = OPT && OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '<span>👷</span>';
        box.innerHTML = '<div class="lp-rec is-wait">' + face + '<div><b>Anee is working out the crew for your lots…</b></div></div>';
        let r = null;
        try { r = (await window.api(U.check, { method: 'POST', body: Object.assign(checkBody(false), { advise: 1 }) })).data.crew; } catch (_) { r = null; }
        if (ask !== recAsk) return;
        if (!r) { box.innerHTML = ''; return; }
        // Anee times the lots after the first: no crew is too small, it only spaces them further apart.
        const timed = S.lots.length > 1 && S.dateMode === 'first';
        if (timed && !r.workers) {
            box.innerHTML = '<div class="lp-rec">' + face + '<div><b>Anee spaces your lots</b><p>With these machines your lots cannot all go in on the first lot\'s day, so each gets its own day, as soon as your crew can have it ready. Below, Anee shows the days for the crew you set.</p></div></div>';
            return;
        }
        box.innerHTML = '<div class="lp-rec">' + face + '<div><b>' + (r.workers ? 'Recommended: about ' + r.workers + (r.workers === 1 ? ' worker' : ' workers') : 'This needs more than a crew') + '</b><p>' + esc(r.reason) + (timed ? ' That gets every lot ready on the first lot\'s day. With fewer, Anee spaces the lots further apart.' : '') + '</p>'
            + (r.workers && r.workers !== S.workers.length ? '<button type="button" class="btn btn-primary" data-userec="' + r.workers + '">Use ' + r.workers + (r.workers === 1 ? ' worker' : ' workers') + '</button>' : '') + '</div></div>';
    }
    $('lpRec').addEventListener('click', (e) => { const b = e.target.closest('[data-userec]'); if (!b) return; setWorkers(Number(b.dataset.userec)); b.remove(); window.toast?.('Set to ' + S.workers.length + ' workers. Give each their own days below.'); });

    /* ---------------- will every lot be ready on its day? (2026-10-16, the owner) ----------------
       The crew calendar without Anee (LandPrepPlanner::forecast), counted
       from today: on the Workers step while the crew is set, and on the
       review with every lot's answers. A warning only: nothing is stopped. */
    let castSig = '', castT = null, castAsk = 0;
    const castKey = (b) => JSON.stringify([b.crop, b.method, b.planting, b.dateMode, b.lots.map((l) => [l.area, l.unit, l.planting, l.crop, l.method]), b.crew.workers.map((w) => w.days), b.crew.fleet]);
    function castSoon(now) { clearTimeout(castT); castT = setTimeout(paintCast, now ? 0 : 450); }
    async function paintCast() {
        const box = $('lpCast');
        if (!S.planting || !S.lots.length || screenOf(FLOW[pos]) !== 'workers') return;
        const body = Object.assign(checkBody(false), { forecast: 1 });
        const key = castKey(body);
        if (key === castSig) return;
        castSig = key;
        const ask = ++castAsk;
        box.classList.add('is-busy');
        let fc = null;
        try { fc = (await window.api(U.check, { method: 'POST', body })).data.forecast || null; } catch (_) { fc = null; }
        if (ask !== castAsk) return;
        box.classList.remove('is-busy');
        if (!fc) castSig = '';
        fadeSwap(box, fc ? castHtml(fc, 'workers') : '');
    }
    function castHtml(fc, where) {
        const many = fc.lots.length > 1, first = fc.mode === 'first', onReview = where === 'review';
        const late = fc.lots.filter((l) => l.late.length), moved = fc.lots.filter((l) => l.shift > 0);
        const short = onReview ? [] : fc.lots.filter((l) => l.short);
        const head = late.length ? (many ? (late.length === 1 ? 'One lot is not ready on its day' : late.length + ' lots are not ready on their days') : 'Not ready on your day')
            : (short.length ? 'Ready, with little time' : (many ? (first && moved.length ? 'Every lot ready, on the days Anee gives them' : 'Every lot ready on its day') : 'Ready on your day'));
        const rows = fc.lots.map((l) => {
            const i = l.lot, cls = l.late.length ? 'is-late' : (l.shift > 0 ? 'is-moved' : 'is-ok');
            const nm = many && l.name && l.name !== 'Lot ' + (i + 1) ? l.name + ' · ' : '';
            const when = day(l.planting) + (first && i > 0 ? (l.shift > 0 ? ', ' + daysN(l.shift) + ' after Lot 1' : ', the same day as Lot 1') : (first && i === 0 ? ', your day' : ''));
            const main = l.late.length ? (l.shift > 0 ? when + ', and still not ready' : 'Not ready by ' + day(l.planting)) : when + ', ready';
            const x = l.late[0];
            const det = (x ? x.what + ' runs ' + (x.days ? daysN(x.days) : 'well') + ' late' + (l.lateCount > 1 ? ', and ' + (l.lateCount - 1) + ' more ' + (l.lateCount === 2 ? 'step' : 'steps') : '') + '.' : '')
                + (l.readyBy ? ' Ready by ' + day(l.readyBy) + ' with this crew.' : '')
                + (l.short && !onReview ? (x ? ' ' : '') + '⏳ ' + daysN(Math.max(0, l.short.left)) + ' left, and the full preparation needs about ' + l.short.needed + ': squeezed to fit.' : '');
            return '<div class="lp-cast-row"><b><i style="--lc:' + lc(i) + '"></i>' + (many ? 'Lot ' + (i + 1) : 'Your lot') + '</b><span class="' + cls + '">' + esc(nm + main) + (det ? '<small>' + esc(det.trim()) + '</small>' : '') + '</span></div>';
        }).join('');
        const tips = [];
        const total = fc.more ? fc.workers + fc.more : 0;
        const use = (n) => (onReview ? '<button type="button" class="btn btn-white" data-jump="workers">Change the workers</button>' : '<button type="button" class="btn btn-primary" data-castuse="' + n + '">Use ' + n + ' workers</button>');
        if (late.length) {
            if (fc.more) tips.push(['With ' + total + ' workers working Monday to Saturday, ' + fc.more + ' more than now, ' + (many ? (first ? 'every lot is ready on the day Anee gives it.' : 'every lot is ready on its day.') : 'it is ready on your day.'), use(total)]);
            // A day falls on a Sunday nobody works (a Monday planting puts the leveling there).
            else if (fc.everyDay != null) tips.push([(fc.everyDay ? 'With ' + (fc.workers + fc.everyDay) + ' workers working every day, Sundays too, ' : 'With your workers working Sundays too, ') + (many ? 'every lot is ready on its day.' : 'it is ready on your day.'), '']);
            else if (fc.machines) tips.push(['More workers do not fix it: the machines hold the work up. Add a machine, or hire one.', '<button type="button" class="btn btn-white" data-jump="equip">Change the machines</button>']);
            else tips.push(['Even 20 more workers do not get it ready in time.', '']);
            const rb = fc.lots.filter((l) => l.readyBy);
            if (rb.length) tips.push(['Or move ' + (many ? rb.map((l) => 'Lot ' + (l.lot + 1) + ' to ' + day(l.readyBy)).join(', ') : 'the day to ' + day(rb[0].readyBy)) + ', ' + (rb.length === 1 ? 'a day' : 'days') + ' this crew can make.', '<button type="button" class="btn btn-white" data-jump="date">Change the days</button>']);
        } else if (first && moved.length && fc.closer) {
            const tc = fc.workers + fc.closer;
            tips.push(['Want them closer together? With ' + tc + ' workers working Monday to Saturday, every lot is ready on ' + day(fc.lots[0].planting) + '.', use(tc)]);
        } else if (first && moved.length) {
            tips.push(['More workers alone do not get every lot ready by ' + day(fc.lots[0].planting) + '. Another machine can bring them closer together.', '<button type="button" class="btn btn-white" data-jump="equip">Change the machines</button>']);
        }
        if (short.length && !late.length) tips.push(['For every step at its own pace, give ' + (many ? short.map((l) => 'Lot ' + (l.lot + 1)).join(', ') : 'your lot') + ' a later day.', '<button type="button" class="btn btn-white" data-jump="date">Change the days</button>']);
        const warn = late.length || short.length;
        return '<div class="lp-cast-c' + (warn ? ' is-warn' : '') + '"><div class="lp-cast-h"><span aria-hidden="true">' + (late.length ? '⚠️' : (short.length ? '⏳' : '✅')) + '</span><b>' + esc(head) + '</b></div>'
            + '<p>' + esc('Counted from today, with your ' + fc.workers + (fc.workers === 1 ? ' worker' : ' workers') + ' and the machines you picked' + (onReview ? ', and every lot\'s answers.' : '.')) + '</p>'
            + '<div class="lp-cast-rows">' + rows + '</div>'
            + (tips.length ? '<div class="lp-cast-do">' + tips.map(([t2, b]) => '<p>' + esc(t2) + '</p>' + b).join('') + '</div>' : '') + '</div>';
    }
    $('lpCast').addEventListener('click', (e) => {
        const u = e.target.closest('[data-castuse]');
        if (u) { setWorkers(Number(u.dataset.castuse)); window.toast?.('Set to ' + S.workers.length + ' workers. Check their days above.'); return; }
        const j = e.target.closest('[data-jump]');
        if (j) goTo(j.dataset.jump, true);
    });
    $('lpCastRev').addEventListener('click', (e) => { const j = e.target.closest('[data-jump]'); if (j) goTo(j.dataset.jump, true); });
    // Any change to the crew asks again, after a pause (the same crew is not asked twice).
    document.querySelector('.lp-step[data-screen="workers"]').addEventListener('click', (e) => { if (!e.target.closest('#lpCast')) castSoon(); });

    /* ---------------- the place ---------------- */
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
            S.loc.province = k; paintPlace(); $('lpPlaceErr').hidden = true;
        });
    });
    $('lpTown').addEventListener('click', async () => {
        if (!S.loc.province) return;
        try { await loadPh(); } catch (_) { return; }
        pick('Choose the town or city', (PHL[S.loc.province] || []).map((t) => ({ key: t, label: townName(t), hint: /\(Capital\)/i.test(t) ? 'The capital' : '' })), S.loc.town, (k) => { S.loc.town = k; S.loc.lat = null; S.loc.lng = null; paintPlace(); $('lpPlaceErr').hidden = true; });
    });

    /* ---------------- review and run ---------------- */
    async function review() {
        S.notes = $('lpNotes').value.trim();
        const m = (OPT.methods[S.crop] || {})[S.method] || [];
        const a = S.lots.reduce((s, l) => s + toHa(l.area, l.unit), 0);
        const items = OPT.equipment, fleet = fleetNow();
        const machines = Object.entries(fleet).map(([k, f]) => [f.own ? f.own + ' ' + items[k].name.toLowerCase() : '', f.hired ? f.hired + ' hired ' + items[k].name.toLowerCase() : ''].filter(Boolean).join(', ')).join(', ');
        const pages = window.aneeFieldProfile.pagesFor(S.crop);
        // The lots first, then what each one grows (2026-10-17, the owner).
        const rows = [['lots', '🟩', 'Lots', S.lots.length + (S.lots.length === 1 ? ' lot' : ' lots') + (a ? ', ' + ha(a) : '')]];
        S.lots.forEach((l, i) => {
            const p = lcOf(l), mm = (OPT.methods[p.crop] || {})[p.method] || [];
            rows.push(['plant:' + i, CROPS[p.crop] ? CROPS[p.crop].icon : '🌱', (S.lots.length > 1 ? lotWord(i) + ' grows' : 'What you plant'),
                [CROPS[p.crop] ? CROPS[p.crop].en : p.crop, mm[0] ? mm[0].toLowerCase() : 'way not chosen', (p.variety || "variety not known yet") + (p.custom ? ', Anee looks it up' : '') + (p.seedType && !(p.varRec && p.varRec.n) ? ', ' + p.seedType : '')].join(', ')]);
        });
        if (FLOW.includes('seed')) rows.push(['seed', '🌾', 'Seed', S.seedSource ? OPT.seedQ.o[S.seedSource][0] : 'Not said']);
        const many = S.lots.length > 1;
        const days = S.lots.map((l, i) => dayOf(l, i)).filter(Boolean).sort();
        const dateWords = !S.planting ? 'Not chosen' : (!many ? day(S.planting)
            : (S.dateMode === 'first' ? 'Lot 1 on ' + day(S.planting) + ', Anee times ' + (S.lots.length === 2 ? 'Lot 2' : 'the other ' + (S.lots.length - 1))
                : (days[0] === days[days.length - 1] ? 'Every lot on ' + day(days[0]) : 'A day for each lot, ' + day(days[0]) + ' to ' + day(days[days.length - 1]))));
        rows.push(['date', '📅', many ? 'Planting days' : 'Planting day', dateWords],
            ['equip', '🚜', 'Machines', machines || (S.handOnly ? 'By hand only' : 'None picked')],
            ['workers', '👷', 'Workers', S.workers.length + (S.workers.length === 1 ? ' worker' : ' workers')
                + (S.workers.some((w) => num(w.pay) > 0) ? ', pay set for ' + S.workers.filter((w) => num(w.pay) > 0).length : '')]);
        S.lots.forEach((l, i) => {
            const n = window.aneeFieldProfile.count(l.profile);
            // A farm lot says where its answers came from, and opens its own sheet.
            if (l.farmLotId) {
                const w = window.aneeFieldProfile.words(l.profile);
                rows.push(['lotedit:' + l.uid, '<i style="--lc:' + lc(i) + '"></i>', S.lots.length > 1 ? 'Lot ' + (i + 1) + ', ' + l.name : l.name,
                    'From ' + l.name + ': ' + (n ? (w.length > 120 ? w.slice(0, 118).replace(/\s+\S*$/, '') + '…' : w) : 'no answers yet'), 'Edit the lot']);
                return;
            }
            const src = l.copiedFrom !== null && S.lots[l.copiedFrom] ? 'same as Lot ' + (l.copiedFrom + 1) + (l.edited ? ', changed' : '') : (l.savedFrom ? 'from your saved ' + l.savedFrom : '');
            rows.push(['lot:' + i + ':' + pages[0], '<i style="--lc:' + lc(i) + '"></i>', S.lots.length > 1 ? 'Lot ' + (i + 1) + (l.name.trim() ? ', ' + l.name.trim() : '') : 'Your field',
                'Soil and history: ' + (n ? n + (n === 1 ? ' answer' : ' answers') : 'skipped') + (src ? ', ' + src : '')]);
        });
        const lp = lotPlace();
        rows.push(lp ? ['lots', '📍', 'Place', (OPT.ph ? townName(lp.town) + ', ' + lp.province : lp.where) + ', the place of ' + lp.name + (lp.mixed ? '. Your lots are in different towns, so the plan reads the weather there' : '')]
            : ['place', '📍', 'Place', OPT.ph ? (S.loc.town ? townName(S.loc.town) + ', ' + S.loc.province : 'Not chosen') : (S.loc.place || 'Not given')]);
        $('lpReview').innerHTML = rows.map(([id, e, k, v, c]) => '<button type="button" data-jump="' + esc(id) + '"><span class="re">' + (String(e).startsWith('<i') ? e : esc(e)) + '</span><span class="rt"><small>' + esc(k) + '</small><b>' + esc(v) + '</b></span><span class="rc">' + esc(c || 'Change') + '</span></button>').join('');
        $('lpRunFace').innerHTML = OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '';
        paintRun();
        // The time, again, with every lot's answers (lime, a salt flush or a rest can add weeks), and the crew against the days.
        $('lpTimeCard').innerHTML = '';
        const cr = $('lpCastRev');
        cr._html = ''; cr.innerHTML = '';
        const d = await checkNow(true, { forecast: 1 });
        const t = d ? d.time || [] : null;
        if (screenOf(FLOW[pos]) === 'review') paintRun();
        if (screenOf(FLOW[pos]) !== 'review' || !t) return;
        if (d.forecast) fadeSwap(cr, castHtml(d.forecast, 'review'));
        const sh = t.filter((x) => x.short);
        if (sh.length) {
            $('lpTimeCard').innerHTML = '<div class="lp-twarn"><span>⏳</span><span><b data-tl="short-on-time">Little time for the full preparation.</b> ' + sh.map((x) => (S.lots.length > 1 ? esc(x.name) + ': ' : '') + Math.max(0, x.left) + ' days left, ' + x.need + ' needed').join('. ') + '. Anee squeezes the plan to fit.<br><button type="button" id="lpTimeMore">See what gets squeezed</button></span></div>';
            $('lpTimeMore').onclick = () => { ACK.review = null; timeWarn(t, 'review'); };
            timeWarn(t, 'review');
        }
    }
    // The run button and its fine print, at this plan's price.
    function paintRun() {
        const price = planQuote();
        $('lpRunSays').textContent = OPT.canUse ? 'Make my plan · ' + price + ' credits' : 'Make my plan';
        $('lpRun').disabled = !OPT.canUse;
        const short = OPT.canUse && !OPT.unlimited && Number(OPT.balance) < price;
        $('lpRunFine').innerHTML = !OPT.canUse ? esc(OPT.whyNot || '')
            : (short ? 'You have ' + esc(Math.floor(Number(OPT.balance))) + ' credits and this plan needs ' + esc(price) + '.' + (OPT.creditsUrl ? ' <a href="' + esc(OPT.creditsUrl) + '" style="font-weight:800;text-decoration:underline">Get credits</a>' : '')
                : 'Usually one to three minutes' + (S.custom ? ', a little longer while Anee looks up the variety. Looking it up on the web is why this plan costs more' : '') + '. Stay on this page while Anee works.');
    }
    $('lpReview').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-jump]');
        if (!b) return;
        const id = b.dataset.jump;
        if (id.startsWith('lotedit:')) {
            // The lot's sheet; saved, the review is read again (its pages may no longer need asking).
            const l = S.lots.find((x) => 'lotedit:' + x.uid === id);
            if (l && await editFarm(l)) { syncFq(); goTo('review'); }
            return;
        }
        goTo(id, true);
    });

    $('lpRun').addEventListener('click', async () => {
        // The notes may sit on this screen (the lots brought the place): read them now.
        S.notes = $('lpNotes').value.trim();
        const lp = lotPlace();
        const body = {
            crop: S.crop, method: S.method, variety: S.variety, seedType: S.seedType || null, seedSource: rice() ? (S.seedSource || null) : null, planting: S.planting,
            dateMode: S.lots.length > 1 ? S.dateMode : null,
            lots: S.lots.map((l, i) => Object.assign({ name: l.name.trim(), area: num(l.area), unit: l.unit, planting: lotDay(l, i), sourceLotId: l.sourceLotId, farmLotId: l.farmLotId, copiedFrom: l.copiedFrom, edited: !!l.edited, profile: l.profile }, lotCropBody(l))),
            crew: { workers: S.workers.map((w) => ({ name: w.name.trim(), days: w.days, pay: num(w.pay) > 0 ? num(w.pay) : null, payUnit: w.payUnit, globalId: w.globalId || null })),
                fleet: fleetList(), stagger: S.lots.length > 1 && S.dateMode === 'first', saveNew: !!(S.saveNew && PEOPLE && PEOPLE.canSave) },
            // The first farm lot's place when the lots have one, else the place screen's.
            location: lp ? { province: lp.province, town: townName(lp.town), place: '', lat: null, lng: null }
                : { province: S.loc.province, town: townName(S.loc.town), place: S.loc.place, lat: S.loc.lat, lng: S.loc.lng }, notes: S.notes,
            quote: planQuote(),
        };
        const phases = anyCustom()
            ? { start: { label: 'Getting started', lo: 2, hi: 6, tau: 10 }, research: { label: 'Looking up ' + (S.custom ? S.variety : 'your varieties'), lo: 6, hi: 34, tau: 70 }, document: { label: 'Writing your plan', lo: 34, hi: 93, tau: 80 }, 'document-json': { label: 'Tidying the plan', lo: 93, hi: 97, tau: 25 } }
            : { start: { label: 'Getting started', lo: 2, hi: 10, tau: 10 }, document: { label: 'Writing your plan', lo: 10, hi: 93, tau: 75 }, 'document-json': { label: 'Tidying the plan', lo: 93, hi: 97, tau: 25 } };
        const lines = ['Laying out the ' + (rice() ? 'PhilRice' : 'extension') + ' steps for ' + (CROPS[S.crop] ? CROPS[S.crop].en.toLowerCase() : 'your crop') + '…', 'Reading every lot\'s soil and history…', 'Sharing the crew and the machines between your lots…', 'Reading the forecast and twenty years of weather…', 'Timing the seed soak to the hour…'];
        if (S.custom) lines.unshift('Looking up ' + S.variety + ' on the web…');
        window.aneeWait.show({ title: 'Anee is planning your land preparation…', lines, sub: 'Usually one to three minutes. Stay on this page while Anee works.' });
        $('lpErr').hidden = true;
        let landed = false;
        try {
            const res = await window.api(U.generate, { method: 'POST', body });
            let data = res.data;
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases });
            landed = true;
            if (data.balance != null) OPT.balance = data.balance;
            try { window.lpOpenView(data.item, 'fresh'); } catch (drawErr) { console.error(drawErr); window.toast?.('Your plan is saved in Your plans, but it could not be shown here.', 'error'); }
            await window.aneeWait.done({ title: 'Your plan is ready', line: (data.item && data.item.credits ? data.item.credits + ' credits used. ' : '') + 'Saved in Your plans.' });
            paintQuote();
            window.lpListStale();
        } catch (err) {
            // The price moved since the page loaded: keep the new one, so the next try carries it.
            if (err && err.status === 409 && err.data && Number(err.data.quote) > 0) QUOTE_NOW[quoteKey()] = Number(err.data.quote);
            $('lpErrText').textContent = (err && err.message) || 'Something went wrong. Nothing was charged. Please try again.';
            $('lpErr').hidden = false;
            $('lpRunSays').textContent = OPT && OPT.canUse ? 'Try again · ' + planQuote() + ' credits' : 'Try again';
        } finally {
            if (!landed) window.aneeWait.fail();
            if (!landed) requestAnimationFrame(() => $('lpErr').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' }));
        }
    });
    /* A plan made leaves a clean form (2026-10-17, the owner): closing it,
       "Plan another field" or deleting it brings the wizard back at the
       lots with every answer as the page first opens it: rice, no lots, no
       day, no machines, two blank workers, no place and no notes. The
       options, the varieties, the farm's lots and people stay loaded. */
    function resetWizard() {
        Object.assign(S, { crop: 'rice', method: null, variety: '', varRec: null, custom: false, seedType: '', seedSource: '', planting: null,
            lots: [], fleet: {}, handOnly: false, workers: [newWorker(), newWorker()], dateMode: 'first',
            loc: { province: '', town: '', place: '', lat: null, lng: null }, notes: '', saveNew: true });
        ACK.date = null;
        ACK.review = null;
        DS.sel = null;
        DS.target = null;
        // Answers still on their way for the last plan are let go.
        seedAsk++; recAsk++; castAsk++;
        clearTimeout(castT);
        castSig = '';
        PICK_AT = null;
        const tag = document.querySelector('#lpPlantCrop .cp-tag');
        if (tag && window.cropPickSet) window.cropPickSet(tag, 'rice');
        $('lpNotes').value = '';
        $('lpPlaceText').value = '';
        $('lpSaveNewOn').checked = true;
        $('lpSeed').hidden = true;
        ['lpRec', 'lpCast', 'lpCastRev', 'lpTimeCard', 'lpReview'].forEach((id) => { const el = $(id); el._html = ''; el.innerHTML = ''; el.classList.remove('is-busy'); });
        ['lpDateErr', 'lpWErr', 'lpPlaceErr', 'lpErr'].forEach((id) => { $(id).hidden = true; });
        if (OPT) { paintMethods(); paintVarTag(false); paintQuote(); }
        syncFq();
        show(0, true);
    }
    window.lpWizardAgain = resetWizard;

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
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); sheetLock(); dlg.dispatchEvent(new Event('lpclosed')); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    document.querySelectorAll('.lp-sheet').forEach((dlg) => {
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    });
    window.lpSheet = { open: sheetOpen, close: sheetClose, lock: sheetLock };
    window.lpBoot = boot;
    window.lpUtil = { $, esc, reduce, dn, ds, fmt, day, time, inDays, ha, lc, CHECK, CHEV, sizeWords, UNITS, TODAY, CROPS, tlf, tlAt, EQ_TL, opt: () => OPT };
})();
</script>

<script>
(() => {
    const { $, esc, reduce, dn, ds, fmt, day, time, ha, lc, CHEV, UNITS, TODAY, tlf, tlAt, EQ_TL } = window.lpUtil;
    const OPT = () => window.lpUtil.opt() || {};
    const U = {
        list: @json(route('landprep.list')), meta: @json(route('landprep.meta')), port: @json(route('landprep.port')), protocols: @json(route('landprep.protocols')),
        season: @json(route('landprep.season')), hub: @json(route('sm.hub', ['id' => '__ID__'])),
        one: (id) => @json(url('/app/land-prep/one')) + '/' + id, del: (id) => @json(url('/app/land-prep')) + '/' + id,
    };
    const { open: sheetOpen, close: sheetClose, lock: sheetLock } = window.lpSheet;
    const TAG = { philrice: 'PhilRice practice', irri: 'IRRI practice', extension: 'Extension practice', anee: "Anee's suggestion" };
    const REASON = { soil: 'For your soil', history: "For the field's history", weather: 'For the weather', enso: 'For the ENSO outlook', crew: 'For your crew', seed: 'For your seed', water: 'For your water', method: 'For how you plant' };
    const TOPIC = { weather: '🌧️', enso: '🌊', soil: '🟫', history: '📜', crew: '👷', seed: '🌾', water: '💧' };
    const SHORT = { plow: 'Plow', first_plow: 'First plow', crack_plow: 'Dry tillage', harrow1: 'Harrow 1', harrow2: 'Harrow 2', harrow: 'Harrow', harrow_level: 'Harrow, level', level: 'Level', extra_level: 'Extra level', rough_level: 'Rough level',
        dikes: 'Dikes', straw: 'Straw', canals: 'Canals', manure: 'Manure', lime: 'Lime', gypsum: 'Gypsum', seedbed_site: 'Seedbed ground', seedbed_beds: 'Seedbed plots', sow_seedbed: 'Sow seedbed', nursery_fill: 'Fill trays',
        sow_nursery: 'Sow nursery', backup_seedbed: 'Backup seedbed', clear: 'Clear', clear_land: 'Clear land', stones: 'Stones', contour: 'Contour', beds: 'Beds', mulch: 'Mulch', furrow: 'Furrows', ridges: 'Ridges', drainage: 'Drainage',
        holes: 'Holes', fill: 'Fill holes', windbreak: 'Windbreak', material: 'Planting material', snail_screens: 'Snail screens', trellis: 'Trellis', stale_kill: 'Weed kill', clean_field: 'Clean field', drain_canal: 'Drain canal',
        deep_till: 'Hardpan', subsoil: 'Subsoil', nursery: 'Nursery', sucker_prep: 'Suckers', harden: 'Harden plantlets', transplant: 'Transplant', pull: 'Pull seedlings', sow: 'Sow', plant: 'Plant', rat_campaign: 'Rat campaign', host_clear: 'Virus break',
        seed_soak: 'Soak', seed_incubate: 'Sprout', sprout_check: 'Check sprouts' };
    const SEEDKEYS = ['seedbed_site', 'seedbed_beds', 'sow_seedbed', 'nursery_fill', 'sow_nursery', 'backup_seedbed', 'nursery', 'nursery_site'];
    const SOAKKEYS = ['seed_soak', 'seed_incubate', 'sprout_check'];
    /* Tagalog help flags: a step's term by its key, a lot answer's by its short name. A list
       flags a term once (tlOnce keeps a Set of the terms already flagged in that list). */
    const STEP_TL = { plow: 'plowing', first_plow: 'plowing', crack_plow: 'plowing', harrow1: 'harrowing', harrow2: 'harrowing', harrow: 'harrowing', harrow_level: 'harrowing',
        level: 'leveling', extra_level: 'leveling', rough_level: 'leveling', dikes: 'dike', straw: 'straw-incorporation', stale_wet: 'stale-seedbed', stale_kill: 'stale-seedbed',
        stale_round: 'stale-seedbed', settle: 'puddling', lime: 'lime', gypsum: 'gypsum', manure: 'organic-fertilizer', seed_soak: 'seed-soaking', seed_incubate: 'seed-incubation',
        seed_test: 'germination-test', soil_test: 'soil-test', seedbed_site: 'seedbed', seedbed_beds: 'seedbed', sow_seedbed: 'seedbed', backup_seedbed: 'backup-seedbed',
        nursery_fill: 'dapog', sow_nursery: 'seedling-tray', rest: 'fallow', transplant: 'transplanting', drum_setup: 'drum-seeder', weeds_pre: 'pre-emergence', weeds_post: 'early-post',
        flush_salt: 'saline', deep_till: 'hardpan', subsoil: 'hardpan', replant: 'replanting', water_replant: 'replanting', seedbed_zinc: 'zinc-sulfate', nursery_zinc: 'zinc-sulfate', book_service: 'hired' };
    const ANS_TL = { Soil: 'soil-texture', 'Organic matter': 'organic-matter', Water: 'water-source', Drainage: 'drainage', Floods: 'flood-prone', Salt: 'saline', Acidity: 'acidic',
        'Known pH': 'ph', 'Hard layer': 'hardpan', 'Last limed': 'lime', Idle: 'fallow', 'Field level': 'leveling' };
    const tlOnce = (seen, key) => (key && !seen.has(key) ? (seen.add(key), tlAt(key)) : '');
    /* Tips for the step sheet, in a farmer's words. */
    const TIPS = {
        plow: ['Plow when the soil is moist, not soaking wet and not bone dry.', 'Overlap each pass a little so no strip is left unturned.', 'Go a little deeper where the last crop left tough roots.'],
        harrow: ['Keep 2 to 3 cm of water in the field so the clods break up.', 'Go along the plowed lines on the first pass and across them on the next.', 'Each pass buries the weeds that sprouted since the last one.'],
        level: ['Level under shallow water so the high and low spots show.', 'Check with a long straight plank: no spot under more than 5 cm of water and no lump above it.'],
        dikes: ['Pack the soil in thin layers and press each one firm.', 'Keep them about 15 cm high and 20 cm wide, too small for rats to burrow in.', 'Patch every crab and rat hole now, not when the water is in.'],
        seedbed: ['Pick a spot near the water and close to the field.', 'Sow evenly, about 1 kg of seed on every 10 square meters.', 'Keep the beds moist, never under deep water.'],
        manure: ['Spread it just before a harrowing so it is mixed in at once.', 'Air dried manure is safer for young roots than fresh.'],
        straw: ['Spread it evenly so it rots at the same pace everywhere.', 'Chop long straw so the plow does not drag it.'],
        hand: ['Start at the far end and work toward the canal.', 'Share the heavy jobs across the crew so nobody is worn out before planting day.'],
        book: ['Agree on the days and the price per hectare before the work starts.', 'Ask the operator to call a day ahead if the plan changes.', 'Have someone at the field when the machine comes.'],
        rats: ['Work with your neighbors on the same days: rats move to the next field.', 'Dig out and flood the burrows on the dikes and the canal banks.'],
        virus: ['Bury or burn the old plants away from the field.', 'Plant together with your neighbors so the insects find no young plants for long.'],
        lime: ['Spread it on a calm day and work it in with the next pass.', 'Lime by the soil test, more is not better.'],
    };
    const tipsFor = (key) => {
        if (/^book_/.test(key)) return TIPS.book;
        if (['plow', 'first_plow', 'crack_plow', 'deep_till', 'subsoil'].includes(key)) return TIPS.plow;
        if (/harrow/.test(key) || key === 'stale_kill') return TIPS.harrow;
        if (['level', 'extra_level', 'rough_level'].includes(key)) return TIPS.level;
        if (SEEDKEYS.includes(key)) return TIPS.seedbed;
        return TIPS[key] || ({ rat_campaign: TIPS.rats, host_clear: TIPS.virus, gypsum: TIPS.lime }[key]) || (['canals', 'drain_canal', 'clean_field', 'clear', 'clear_land', 'stones', 'beds', 'holes', 'fill', 'mulch'].includes(key) ? TIPS.hand : []);
    };
    const eqName = (t) => ((OPT().equipment || {})[t] || {}).name || String(t).replace(/_/g, ' ');
    const eqPhoto = (t) => ((OPT().equipment || {})[t] || {}).photo || null;

    let VIEW = null;   // { item, mode, lot, tab }
    const stepOf = (r, key, lot) => {
        const own = lot != null && r.lotSteps && r.lotSteps[lot] ? r.lotSteps[lot].find((s) => s.key === key) : null;
        return own || (r.steps || []).find((s) => s.key === key) || { key, what: SHORT[key] || key, how: '' };
    };
    const short = (r, key) => SHORT[key] || (String(key).startsWith('book_') ? 'Book it' : String(stepOf(r, key).what || key).split(' ').slice(0, 3).join(' '));
    const lotName = (r, i) => ((r.lots || [])[i] || {}).name || 'Lot ' + (i + 1);
    const sizeOf = (l) => (l.input && l.unit && l.unit !== 'ha' ? Number(l.input).toLocaleString('en-US', { maximumFractionDigits: 3 }) + ' ' + (UNITS[l.unit] || UNITS.ha)[1] + ', ' + ha(Number(l.area || 0)) : ha(Number(l.area || 0)));

    function openView(item, mode) {
        VIEW = { item, mode, lot: 0, tab: 'farm' };
        $('lpViewTitle').textContent = item.title || 'Land prep plan';
        drawPlan();
        const v = $('lpView');
        v.hidden = false;
        v.scrollTop = 0;
        sheetLock();
        if (window.registerOverlay) window.registerOverlay('lp:view', closeView);
        requestAnimationFrame(() => requestAnimationFrame(() => { v.classList.add('is-on'); setTimeout(() => { v.classList.add('is-drawn'); fitTimeline(); }, 120); }));
    }
    window.lpOpenView = openView;
    function closeView() {
        const v = $('lpView');
        if (v.hidden) return;
        if (window.unregisterOverlay) window.unregisterOverlay('lp:view');
        v.classList.remove('is-on', 'is-drawn');
        /* A plan just made closes onto the wizard, started again and empty
           (2026-10-17, the owner: "the wizard should be back to 0 or reset");
           the plan waits in Your plans. 'again' ("Plan another field", or a
           fresh plan deleted) does the same. */
        const mode = VIEW ? VIEW.mode : null;
        setTimeout(() => {
            v.hidden = true; $('lpViewBody').innerHTML = ''; sheetLock();
            if (mode === 'fresh' || mode === 'again') tab('plan');
            if (mode === 'fresh' || mode === 'again') window.lpWizardAgain();
        }, reduce() ? 0 : 300);
        if (new URLSearchParams(location.search).has('open')) history.replaceState(null, '', location.pathname);
    }
    $('lpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !$('lpView').hidden && !document.querySelector('.lp-sheet[open], .fq-sheet[open]')) closeView(); });

    // The plan's crop and way of planting, or every crop when lots grow their own (2026-10-16).
    function heroTitle(r) {
        const names = [];
        (r.lots || []).forEach((l) => { const c = (l.crop || r.crop || {}).label; if (c && !names.includes(c)) names.push(c); });
        if (names.length > 1) return names.map((x) => String(x).replace(/\s*,.*$/, '')).join(', ').replace(/, ([^,]*)$/, ' and $1');
        // Every lot grows one crop of its own: that crop, not the plan's first answer.
        const l0 = (r.lots || [])[0] || {};
        const c = l0.crop || r.crop || {}, m = l0.crop ? (l0.method || {}) : (r.method || {});
        return (c.label || '') + ', ' + (m.label || '').toLowerCase();
    }
    function drawPlan() {
        const it = VIEW.item, r = it.report || {};
        const lots = r.lots || [];
        const area = lots.reduce((s, l) => s + Number(l.area || 0), 0);
        const major = (r.conflicts || []).filter((c) => c.major !== false);
        const moved = lots.filter((l) => l.shift > 0);
        const squeezed = lots.filter((l) => l.squeeze);
        const dates = lots.map((l) => l.planting).sort();
        const crew = r.crew || {};
        const nMach = (crew.fleet || []).reduce((s, f) => s + (f.own || 0) + (f.hired || 0), 0);
        $('lpViewBody').innerHTML = ''
            + '<div class="lp-hero"><div class="lp-hero-top"><span class="lp-hero-ico">' + esc((r.crop || {}).icon || '🌱') + '</span><div style="min-width:0"><small data-tl="land-prep-planner">Land preparation plan</small><h2>' + esc(heroTitle(r)) + '</h2></div></div>'
            + '<div class="lp-hero-chips"><span>📅 ' + esc(dates[0] ? day(dates[0]) : '') + (dates.length > 1 && dates[dates.length - 1] !== dates[0] ? ' to ' + esc(day(dates[dates.length - 1])) : '') + '</span>'
            + '<span>🟩 ' + lots.length + (lots.length === 1 ? ' lot' : ' lots') + ', ' + ha(area) + '</span>'
            + '<span>👷 ' + esc((crew.workers || '') + ((crew.workers === 1) ? ' worker' : ' workers')) + (crew.fleet ? (nMach ? ', ' + nMach + (nMach === 1 ? ' machine' : ' machines') : ', by hand') : (crew.equipmentLabel ? ', ' + esc(String(crew.equipmentLabel).toLowerCase()) : '')) + '</span>'
            + (major.length ? '<span class="is-warn">⚠ ' + major.length + (major.length === 1 ? ' step runs late' : ' steps run late') + '</span>' : '<span class="is-ok">✓ Every lot ready on time</span>')
            + (squeezed.length ? '<span class="is-warn" data-tl="squeezed-steps">⏳ Squeezed to fit the time</span>' : '')
            + (moved.length ? '<span data-tl="stagger">🔀 ' + moved.length + ((crew.dateMode === 'first' ? (moved.length === 1 ? ' lot timed by Anee' : ' lots timed by Anee') : (moved.length === 1 ? ' lot staggered' : ' lots staggered'))) + '</span>' : '')
            + (r.variety ? '<span>🏷️ ' + esc(r.variety) + '</span>' : '') + (it.credits ? '<span>' + esc(it.credits) + ' credits</span>' : '') + '</div></div>'
            + '<div class="lp-seg" id="lpSeg"><i></i><button type="button" class="' + (VIEW.tab === 'farm' ? 'is-on' : '') + '" data-tab="farm">Whole farm</button><button type="button" class="' + (VIEW.tab === 'lot' ? 'is-on' : '') + '" data-tab="lot">Per lot</button></div>'
            + '<div class="lp-pane" id="lpFarm"' + (VIEW.tab === 'farm' ? '' : ' hidden') + '>' + farmHtml(r, r.anee || {}) + '</div>'
            + '<div class="lp-pane" id="lpLot"' + (VIEW.tab === 'lot' ? '' : ' hidden') + '>' + lotHtml(r, VIEW.lot) + '</div>'
            + '<div class="lp-acts"><button type="button" class="btn btn-primary" data-act="season"><svg fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 13v5M9.5 15.5h5"/></svg>Make a cropping schedule</button>'
            + '<button type="button" class="btn btn-white" data-act="send"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>Send to Protocol Builder</button>'
            + '<button type="button" class="btn btn-white" data-act="rename">Rename</button><button type="button" class="btn btn-white lp-del" data-act="delete">Delete</button></div>'
            + (VIEW.mode === 'fresh' ? '<button type="button" class="btn btn-white" data-act="again">Plan another field</button>' : '');
        $('lpSeg').classList.toggle('is-lot', VIEW.tab === 'lot');
        wireScrollx();
        fitTimeline();
    }

    function farmHtml(r, a) {
        const lots = r.lots || [];
        const face = OPT().aneeFace ? '<img src="' + esc(OPT().aneeFace) + '" alt="">' : '🤖';
        let h = '<div class="lp-c"><h3>' + face + "<span>Anee's reading" + tlf('anee-reading') + '</span>' + (a.confidence ? '<em>Confidence: ' + esc(a.confidence) + tlf('confidence') + '</em>' : '') + '</h3><p class="lp-p">' + esc(a.summary || '') + '</p>'
            + ((a.analysis || []).length ? '<div class="lp-an">' + a.analysis.map((x) => '<div><span>' + (TOPIC[x.topic] || '•') + '</span><span><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p></span></div>').join('') + '</div>' : '') + '</div>';
        // Short on time: what Anee squeezed, and what she says comes first.
        const sq = lots.map((l, i) => [l, i]).filter(([l]) => l.squeeze);
        if (sq.length) {
            h += '<div class="lp-c lp-short"><h3><span>⏳ Short on time' + tlf('short-on-time') + '</span></h3><div class="lp-short-lots">' + sq.map(([l, i]) => '<div class="lp-short-lot" style="--lc:' + lc(i) + '"><i></i><span><b>' + esc(l.name) + ':</b> ' + l.squeeze.left + ' days left, the full preparation needs ' + l.squeeze.needed + '. ' + l.squeeze.count + ' steps squeezed to fit'
                + (l.squeeze.age && l.squeeze.ageWas && l.squeeze.age < l.squeeze.ageWas ? ', the seedlings ' + l.squeeze.age + ' days old instead of ' + l.squeeze.ageWas : '') + '.</span></div>').join('') + '</div>'
                + (a.shortTime ? '<p class="lp-p">' + esc(a.shortTime) + '</p>' : '<p class="lp-p">The plowing comes first, every harrowing and the leveling stay, and the waiting between them is shorter. Walk the field and check it carefully before you start.</p>') + '</div>';
        }
        // What runs late, and what to do.
        const late = (r.conflicts || []).filter((c) => c.major !== false);
        const moved = lots.map((l, i) => [l, i]).filter(([l]) => l.shift > 0);
        if (late.length || moved.length) {
            // The farmer set only the first lot's day: the others' are Anee's, said as such (2026-10-16).
            const timed = (r.crew || {}).dateMode === 'first';
            h += '<div class="lp-c lp-warnc"><h3>' + (timed && !late.length ? '🔀 The days Anee gives your lots' : '⚠ The crew and your dates') + '</h3><ul>'
                + moved.map(([l, i]) => '<li style="--lc:' + lc(i) + '"><i></i><span>' + (timed
                    ? 'Anee times <b>' + esc(l.name) + '</b> for <b>' + esc(day(l.planting)) + '</b>, ' + l.shift + (l.shift === 1 ? ' day' : ' days') + ' after the first lot, when the crew can have it ready.'
                    : 'Anee moved <b>' + esc(l.name) + '</b> from ' + esc(day(l.asked)) + ' to <b>' + esc(day(l.planting)) + '</b>, ' + l.shift + (l.shift === 1 ? ' day' : ' days') + ' later, so the crew can finish it.') + '</span></li>').join('')
                + late.map((c) => '<li style="--lc:' + lc(c.lot) + '"><i></i><span><b>' + esc(lotName(r, c.lot)) + ':</b> ' + esc(stepOf(r, c.key, c.lot).what) + ' ' + (c.why === 'past' ? 'should have started already' : 'runs ' + (c.late ? c.late + (c.late === 1 ? ' day' : ' days') : 'well') + ' late') + '.</span></li>').join('')
                + '</ul></div>';
        }
        h += crewHtml(r, a);
        h += costHtml(r);
        h += '<div class="lp-c"><h3><span>📊 Across your lots' + tlf('lots-timeline') + '</span></h3>' + timelineHtml(r) + '</div>';
        h += seedHtml(r);
        h += weatherHtml(r, a);
        if ((a.risks || []).length) {
            h += '<div class="lp-c"><h3>👀 What to watch</h3>' + a.risks.map((x, k) => '<div class="lp-risk"><span class="is-' + esc(x.level) + '"' + (k === 0 ? tlAt('risk-level') : '') + '>' + esc(x.level) + '</span><div><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p>' + (x.when ? '<small>' + esc(x.when) + '</small>' : '') + '</div></div>').join('') + '</div>';
        }
        if ((r.rules || []).length) {
            h += '<div class="lp-c"><h3>📋 Good to know for this field</h3><ul class="lp-rules">' + r.rules.map((n) => '<li><span>' + (n.level === 'warn' ? '⚠️' : '💡') + '</span><div>' + esc(n.text)
                + ((n.lots || []).length && lots.length > 1 ? '<span>' + n.lots.map((i) => '<span class="lp-lotchip" style="--lc:' + lc(i) + '"><i></i>' + esc(lotName(r, i)) + '</span>').join('') + '</span>' : '') + '</div></li>').join('') + '</ul></div>';
        }
        h += '<div class="lp-c"><h3>🧭 Where this comes from</h3><p class="lp-fineprint">' + ((r.crop || {}).key === 'rice'
            ? 'The base steps follow PhilRice practice, the PalayCheck System of PhilRice (2022 revised edition), and IRRI practice for dry seeded rice, in our own words.'
            : 'The base steps follow DA and university extension practice, in our own words.')
            + " Steps marked Anee's suggestion are added for this field, each with its reason. The crew times are typical work rates, so treat them as a guide and adjust to your own pace."
            + ((a.dataGaps || []).length ? ' What would sharpen this plan: ' + esc(a.dataGaps.join(' ')) : '') + '</p></div>';
        return h;
    }

    /* Your crew: who and what, the bookings, the jobs (each opens its sheet), then day by day. */
    function jobsOf(r) {
        const jobs = {};
        (r.perLot || []).forEach((pl) => (pl.steps || []).forEach((s) => {
            if (!s.crew) return;
            const j = jobs[s.key] || (jobs[s.key] = { key: s.key, lots: [], start: null, end: null, machines: [], late: false, byHand: false, crew: s.crew, people: 0, pdays: 0, mdays: 0 });
            if (!j.lots.includes(pl.lot)) j.lots.push(pl.lot);
            if (s.start) { if (!j.start || s.start < j.start) j.start = s.start; if (!j.end || s.end > j.end) j.end = s.end; }
            (s.machines || []).forEach((m) => { if (!j.machines.includes(m)) j.machines.push(m); });
            if (s.late || s.start === null) j.late = true;
            if (s.byHand) j.byHand = true;
        }));
        Object.values(r.calendar || {}).forEach((items) => items.forEach((x) => {
            const j = jobs[x.key];
            if (!j) return;
            const p = x.people || 0;
            if (p > j.people) j.people = p;
            j.pdays += p;
            if (x.machine) j.mdays += x.n || 1;
        }));
        return Object.values(jobs).sort((a, b) => ((a.start || '9') < (b.start || '9') ? -1 : 1));
    }
    function crewHtml(r, a) {
        const crew = r.crew || {}, lots = r.lots || [];
        const jobs = jobsOf(r);
        let h = '<div class="lp-c"><h3><span>👷 Your crew' + tlf('crew') + '</span><em>' + esc(crew.workers || 0) + (crew.workers === 1 ? ' worker' : ' workers') + '</em></h3>';
        // Who: the workers by their days, the machines with their photos.
        const chips = [];
        if (crew.workerList) {
            const byDays = {};
            crew.workerList.forEach((w) => { const k = (w.days || []).join(','); (byDays[k] = byDays[k] || []).push(w); });
            const DN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const dw = (d) => (d.length === 7 ? 'every day' : d.join(',') === '1,2,3,4,5,6' ? 'Mon to Sat' : d.join(',') === '1,2,3,4,5' ? 'Mon to Fri' : d.map((x) => DN[x]).join(', '));
            Object.values(byDays).forEach((ws) => chips.push('<span class="lp-crew-chip"><span class="lp-ph">👷</span><em>' + ws.length + (ws.length === 1 ? ' worker, ' : ' workers, ') + esc(dw(ws[0].days)) + '</em></span>'));
            (crew.fleet || []).forEach((f) => {
                const ph = f.photo || eqPhoto(f.type);
                const bits = [f.own ? f.own + ' own' : '', f.hired ? f.hired + ' hired' : ''].filter(Boolean).join(', ');
                chips.push('<span class="lp-crew-chip"><span class="lp-ph">' + (ph ? '<img src="' + esc(ph) + '" alt="">' : '🚜') + '</span><em>' + esc(f.name || eqName(f.type)) + ', ' + esc(bits) + '</em></span>');
            });
            if (!(crew.fleet || []).length) chips.push('<span class="lp-crew-chip"><span class="lp-ph">🧑‍🌾</span><em>By hand, no machine</em></span>');
        } else {
            chips.push('<span class="lp-crew-chip"><span class="lp-ph">👷</span><em>' + esc(crew.workers || '') + ' workers, ' + esc(crew.daysPerWeek || 6) + ' days a week</em></span>', '<span class="lp-crew-chip"><span class="lp-ph">🚜</span><em>' + esc(crew.equipmentLabel || '') + '</em></span>');
        }
        h += '<div class="lp-crew-top">' + chips.join('') + '</div>';
        (r.bookings || []).forEach((b, bi) => {
            const hire = ((crew.fleet || []).find((f) => f.type === b.type) || {}).hire;
            h += '<div class="lp-book"><span>📞</span><span><b>Book the hired' + (bi === 0 ? tlf('hired') : '') + ' ' + esc(String(b.name).toLowerCase()) + ' by ' + esc(day(b.by)) + '.</b> It is first needed on ' + esc(day(b.use)) + (b.for ? ' for ' + esc(b.for) : '') + (lots.length > 1 ? ' on ' + esc(lotName(r, b.lot)) : '') + '.'
                + (hire ? ' Call <b>' + esc(hire.name) + '</b>' + (hire.phone ? ' at <a href="tel:' + esc(String(hire.phone).replace(/[^\d+]/g, '')) + '" class="underline">' + esc(hire.phone) + '</a>' : '') + '.' : '') + '</span></div>';
        });
        if (a.crew) h += '<p class="lp-p" style="margin:.2rem 0 .6rem">' + esc(a.crew) + '</p>';
        if (jobs.length) {
            const seen = new Set();
            h += '<p class="lp-sk" style="margin-top:.6rem">The jobs, tap one for what to do</p><div class="lp-jobs">' + jobs.map((j, k) => {
                const s = stepOf(r, j.key, j.lots[0]);
                const icon = SEEDKEYS.includes(j.key) ? '🌱' : (j.crew === 'machine' ? '🚜' : '🧑‍🌾');
                const who = j.crew === 'machine' ? (j.machines.length ? j.machines.map(eqName).join(', ').toLowerCase() : 'machine') : (j.byHand ? 'by hand, no machine for it' : (j.people ? 'up to ' + j.people + (j.people === 1 ? ' person' : ' people') + ' a day' : 'hand work'));
                return '<button type="button" class="lp-job' + (j.late ? ' is-late' : '') + '" data-job="' + esc(j.key) + '" style="animation-delay:' + Math.min(k, 10) * 30 + 'ms"><span>' + icon + '</span><span class="jt"><b' + tlOnce(seen, STEP_TL[j.key]) + '>' + esc(s.what) + '</b><small>'
                    + (lots.length > 1 ? '<span class="jd">' + j.lots.map((i) => '<i style="--lc:' + lc(i) + '"></i>').join('') + '</span>' : '')
                    + esc((j.start ? day(j.start) + (j.end && j.end !== j.start ? ' to ' + day(j.end) : '') : 'Not fitted in') + ' · ' + who) + '</small></span>' + '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>';
            }).join('') + '</div>';
        }
        const cal = calendarHtml(r);
        if (cal) h += '<button type="button" class="lp-more2" data-act="crewdays" aria-expanded="false">Day by day' + CHEV + '</button><div class="lp-cal2" id="lpCrewDays"><div>' + cal + '</div></div>';
        return h + '</div>';
    }
    /* What it costs (2026-10-16): from the pay and the prices given in the wizard (LandPrepPlanner::costs). */
    function costHtml(r) {
        const c = r.costs;
        if (!c) return '';
        const cur = c.currency || '₱';
        const m = (v) => cur + Math.round(Number(v || 0)).toLocaleString('en-US');
        const lots = r.lots || [];
        if (!c.priced) {
            return '<div class="lp-c"><h3><span>💰 What it costs</span></h3><p class="lp-p">Give your workers their pay and the hired machines their price in the wizard, and Anee adds up what this preparation costs, lot by lot.</p></div>';
        }
        const row = (title, line, cost, k) => '<div style="animation-delay:' + Math.min(k || 0, 10) * 30 + 'ms"><span><b>' + title + '</b><small>' + line + '</small></span>' + (cost === null || cost === undefined ? '<em class="is-none">No price</em>' : '<em>' + m(cost) + '</em>') + '</div>';
        let h = '<div class="lp-c"><h3><span>💰 What it costs</span><em>' + esc(m(c.total)) + '</em></h3>'
            + '<div class="lp-cost-big"><b>' + esc(m(c.total)) + '</b><span>in all' + (c.perHa ? ', about ' + esc(m(c.perHa)) + ' a hectare' : '') + '</span></div>';
        const ws = (c.workers || []).filter((w) => w.days > 0);
        if (ws.length) {
            h += '<p class="lp-sk">Your workers</p><div class="lp-cost-rows">' + ws.map((w, k) => row(esc(w.name), w.days + (w.days === 1 ? ' day' : ' days')
                + (w.pay ? ' × ' + esc(m(w.payUnit === 'half' ? w.pay * 2 : w.pay)) + ' a day' + (w.payUnit === 'half' ? ' (' + esc(m(w.pay)) + ' a half day)' : '') : ', no pay given'), w.cost, k)).join('') + '</div>';
        }
        if (c.planting) {
            h += '<div class="lp-cost-rows">' + row('Planting day hands', c.planting.people + ' people in all' + (c.planting.rate ? ' × ' + esc(m(c.planting.rate)) + ', the average day pay of your workers' : ', no pay to go by'), c.planting.cost) + '</div>';
        }
        if ((c.hired || []).length) {
            h += '<p class="lp-sk">Hired machines</p><div class="lp-cost-rows">' + c.hired.map((x, k) => {
                const basis = x.unit === 'job' ? 'One price for the whole job' : (x.unit === 'day' ? x.days + (x.days === 1 ? ' day' : ' days') + ' × ' + esc(m(x.amount)) : (x.unit === 'ha' ? x.ha + ' ha of passes × ' + esc(m(x.amount)) : x.days + (x.days === 1 ? ' day' : ' days') + ' on your lots, no price given'));
                return row(esc(x.name) + (x.who ? ', ' + esc(x.who) : ''), basis, x.cost, k);
            }).join('') + '</div>';
        }
        if (lots.length > 1 && (c.perLot || []).length) {
            h += '<p class="lp-sk">Lot by lot</p><div class="lp-cost-rows">' + c.perLot.map((p, k) => row('<i style="--lc:' + lc(p.lot) + '"></i>' + esc(lotName(r, p.lot)),
                [p.labor + p.planting > 0 ? 'Workers ' + esc(m(p.labor + p.planting)) : '', p.hired > 0 ? 'hired ' + esc(m(p.hired)) : ''].filter(Boolean).join(', ') || 'Nothing priced', p.total, k)).join('') + '</div>';
        }
        const miss = [];
        if ((c.missing || {}).pay) miss.push(c.missing.pay + (c.missing.pay === 1 ? ' worker has' : ' workers have') + ' no pay yet, so their days are not in the total.');
        if ((c.missing || {}).hired) miss.push(c.missing.hired + (c.missing.hired === 1 ? ' hired machine has' : ' hired machines have') + ' no price yet.');
        h += '<p class="lp-fineprint">' + esc(miss.concat(['Each day goes to the workers who work that weekday, in the order you listed them.', 'Fuel for your own machines, seed and fertilizer are not counted. The days come from typical work rates, so the real bill can differ.']).join(' ')) + '</p>';
        return h + '</div>';
    }
    function calendarHtml(r) {
        const days = {};
        Object.entries(r.calendar || {}).forEach(([d, items]) => { days[d] = (days[d] || []).concat(items.map((x) => Object.assign({ crew: true }, x))); });
        (r.plantDays || []).forEach((p) => { days[p.date] = (days[p.date] || []).concat([Object.assign({ plant: true }, p)]); });
        const keys = Object.keys(days).sort();
        if (!keys.length) return '';
        let h = '', week = null;
        keys.forEach((d, n) => {
            const monday = ds(dn(d) - ((new Date(d + 'T00:00:00Z').getUTCDay() + 6) % 7));
            if (monday !== week) { if (week) h += '</div>'; week = monday; h += '<div class="lp-week"><b>Week of ' + esc(fmt(monday, { month: 'short', day: 'numeric' })) + '</b>'; }
            const items = days[d];
            h += '<div class="lp-day' + (items.some((x) => x.plant) ? ' is-plant' : '') + '"><span class="lp-dbox"><small>' + esc(fmt(d, { weekday: 'short' })) + '</small><b>' + esc(fmt(d, { day: 'numeric' })) + '</b></span><span class="lp-dchips">'
                + items.map((x) => x.plant
                    ? '<span class="lp-cc is-plant" style="--lc:' + lc(x.lot) + '"><i></i>' + esc(lotName(r, x.lot)) + ' · ' + esc(short(r, x.key)) + ' <em>about ' + x.people + ' ' + esc(x.words) + '</em></span>'
                    : '<button type="button" class="lp-cc" data-step="' + x.lot + '|' + esc(x.key) + '" style="--lc:' + lc(x.lot) + '"><i></i>' + esc(lotName(r, x.lot)) + ' · ' + esc(short(r, x.key)) + ' <em>' + (x.machine ? '🚜' + (x.n > 1 ? x.n : '') : '') + (x.people ? ' 👷' + x.people : '') + '</em></button>').join('')
                + '</span></div>';
            if (n === keys.length - 1) h += '</div>';
        });
        return h;
    }

    /* Across your lots: one shared date axis; for each lot its land work, its seedbed and its planting day. */
    function timelineHtml(r) {
        const lots = r.lots || [], per = r.perLot || [];
        let lo = null, hi = null;
        const see = (a, b) => { if (!a) return; if (lo === null || a < lo) lo = a; if (hi === null || (b || a) > hi) hi = b || a; };
        per.forEach((pl) => (pl.steps || []).forEach((s) => { if (s.start) see(s.start, s.end); else if (SOAKKEYS.includes(s.key)) see(s.winFrom, s.winTo); }));
        lots.forEach((l) => see(l.planting, l.planting));
        if (lo === null) return '<p class="lp-p">No crew work to lay out.</p>';
        if (TODAY < lo && dn(lo) - dn(TODAY) < 21) lo = TODAY;
        const n0 = dn(lo) - 1, N = dn(hi) - n0 + 2;
        const pct = (iso) => ((dn(iso) - n0) / N * 100);
        const w = (a, b) => Math.max(((dn(b) - dn(a) + 1) / N) * 100, 0);
        const stepD = N <= 42 ? 7 : (N <= 90 ? 14 : 28);
        let ticks = '';
        for (let d = dn(lo); d <= dn(hi); d += stepD) { const p = (d - n0) / N * 100; if (p <= 84) ticks += '<span style="left:' + p.toFixed(2) + '%">' + esc(fmt(ds(d), { month: 'short', day: 'numeric' })) + '</span>'; }
        const now = TODAY >= ds(n0) && TODAY <= ds(n0 + N) ? '<i class="lp-tnow" style="left:' + pct(TODAY).toFixed(2) + '%" title="Today"></i>' : '';
        const wk = (7 / N * 100).toFixed(3) + '%';
        const wkStart = ((dn(lo) - n0) / N * 100).toFixed(3) + '%';
        const lane = (label, rows, inner) => '<div class="lp-lane"><span class="lp-ln">' + label + '</span><div class="lp-trk" style="--wk:' + wk + ';background-position:' + wkStart + ' 0;height:' + (Math.max(1, rows) * 1.3 + .2).toFixed(2) + 'rem">' + inner + now + '</div></div>';
        const stack = (items) => { const ends = []; return items.map((x) => { const a = dn(x.a); let row = ends.findIndex((e) => e < a); if (row < 0) { row = ends.length; ends.push(dn(x.b)); } else ends[row] = dn(x.b); return Object.assign({ row }, x); }); };
        const bar = (x, i, cls, delay) => '<span class="lp-bar2 ' + cls + (x.late ? ' is-late' : '') + '" role="button" tabindex="0" data-step="' + i + '|' + esc(x.key) + '" data-l="' + esc(x.label) + '" style="left:' + pct(x.a).toFixed(2) + '%;width:' + w(x.a, x.b).toFixed(2) + '%;top:calc(.1rem + ' + x.row + ' * 1.3rem);transition-delay:' + delay + 'ms" aria-label="' + esc(x.title) + '" title="' + esc(x.title) + '">' + esc(x.label) + '</span>';
        const blocks = per.map((pl) => {
            const i = pl.lot, lot = lots[i] || {};
            const land = [], seed = [];
            let sow = null, pull = null;
            (pl.steps || []).forEach((s) => {
                const what = stepOf(r, s.key, i).what;
                if (['sow_seedbed', 'sow_nursery', 'nursery'].includes(s.key)) sow = s.start || s.winFrom;
                if (s.key === 'pull') pull = s.winFrom;
                if (s.start && s.crew) {
                    const it = { key: s.key, a: s.start, b: s.end, late: !!s.late, label: short(r, s.key), title: what + ': ' + day(s.start) + (s.end !== s.start ? ' to ' + day(s.end) : ''), kind: s.crew };
                    (SEEDKEYS.includes(s.key) ? seed : land).push(it);
                } else if (SOAKKEYS.includes(s.key)) {
                    seed.push({ key: s.key, a: s.winFrom, b: s.winTo, label: short(r, s.key), title: what + ': ' + day(s.winFrom), kind: 'seed' });
                }
            });
            land.sort((x, y) => (x.a < y.a ? -1 : 1)); seed.sort((x, y) => (x.a < y.a ? -1 : 1));
            const L = stack(land), Sd = stack(seed);
            let seedInner = Sd.map((x) => bar(x, i, 'is-seed', 80 + (dn(x.a) - n0) * 5)).join('');
            const grow = sow && (pull || lot.planting) && dn(pull || lot.planting) > dn(sow) + 1 ? { a: ds(dn(sow) + 1), b: pull || lot.planting } : null;
            const growRow = grow ? Sd.reduce((m, x) => Math.max(m, x.row + 1), 0) : 0;
            if (grow) seedInner += '<span class="lp-bar2 is-grow" style="left:' + pct(grow.a).toFixed(2) + '%;width:' + w(grow.a, grow.b).toFixed(2) + '%;top:calc(.1rem + ' + growRow + ' * 1.3rem)" data-l="Seedlings grow" title="Seedlings grow ' + (dn(grow.b) - dn(sow)) + ' days">Seedlings grow</span>';
            const seedRows = Math.max(Sd.reduce((m, x) => Math.max(m, x.row + 1), 0), grow ? growRow + 1 : 0);
            const pp = pct(lot.planting);
            return '<div class="lp-tlot" style="--lc:' + lc(i) + '"><div class="lp-tlh"><i></i><b>' + esc(lot.name || '') + '</b><small>' + esc(ha(Number(lot.area || 0))) + ' · 🌱 ' + esc(day(lot.planting)) + '</small></div>'
                + lane('Land work', Math.max(1, L.reduce((m, x) => Math.max(m, x.row + 1), 0)), L.map((x) => bar(x, i, x.kind === 'machine' ? 'is-machine' : 'is-manual', 60 + (dn(x.a) - n0) * 5)).join(''))
                + (seedRows ? lane(sow ? 'Seedbed' : 'Seed', seedRows, seedInner) : '')
                + lane((r.dayWord || 'Planting').replace(/^\w/, (c) => c.toUpperCase()), 1, '<span class="lp-pdot' + (pp > 70 ? ' is-end' : '') + '" style="left:' + pp.toFixed(2) + '%"><i></i>' + esc(fmt(lot.planting, { month: 'short', day: 'numeric' })) + '</span>')
                + '</div>';
        }).join('');
        return '<div class="lp-tl2"><div class="lp-tax">' + ticks + '</div>' + blocks + '</div>'
            + '<div class="lp-glegend"><span><i style="background:#4a7c2a"></i>Machine work</span><span><i style="background:#d97706"></i>Hand work</span><span><i style="background:#0284c7"></i>Seedbed and seed' + tlf('seedbed') + '</span><span><i style="background:repeating-linear-gradient(135deg,#bae6fd 0 4px,#e0f2fe 4px 8px)"></i>Seedlings grow</span><span><i style="background:#7c3aed;width:.6rem;height:.6rem;border-radius:.15rem;transform:rotate(45deg)"></i>Planting day</span><span><i style="background:#ef4444;width:2px;height:.85rem"></i>Today</span></div>'
            + '<p class="lp-tl-tip">Tap a bar to see the step.</p>';
    }
    function fitTimeline() {
        document.querySelectorAll('#lpViewBody .lp-bar2[data-l]').forEach((b) => {
            b.textContent = b.dataset.l;
            if (b.scrollWidth > b.clientWidth + 1) b.textContent = '';
        });
    }
    let rt = null;
    window.addEventListener('resize', () => { if ($('lpView').hidden) return; clearTimeout(rt); rt = setTimeout(() => { fitTimeline(); document.querySelectorAll('.lp-scrollx').forEach(edgeOf); }, 120); });

    /* The seed: hours, amounts, Anee's research, and each lot's timed reminders. */
    function seedHtml(r) {
        const p = r.seed || {};
        const res = p.research || null;
        const facts = res ? [res.days ? 'about ' + res.days + ' days to harvest' : '', res.type && res.type !== 'open' ? res.type : (res.type === 'open' ? 'open pollinated' : ''), res.company ? 'from ' + res.company : ''].filter(Boolean).join(', ') : '';
        const researchBox = res ? (res.status === 'found'
            ? '<div class="lp-research"><span>🔎</span><span><b>Anee\'s research on ' + esc(r.variety) + ':</b> ' + esc(facts ? facts.replace(/^\w/, (c) => c.toUpperCase()) + '.' : 'Found on the web.') + (p.researchSay ? ' ' + esc(p.researchSay) : '') + '</span></div>'
            : '<div class="lp-research is-miss"><span>🔎</span><span>' + esc(p.researchSay || 'Anee could not find this variety on the web just now. Follow the instructions on the seed bag.') + '</span></div>') : '';
        if (!p.found || !p.applies || !p.soakHours) {
            return '<div class="lp-c"><h3><span>🌾 Seed preparation' + tlf('seed-prep') + '</span></h3>' + (p.say ? '<p class="lp-p">' + esc(p.say) + '</p>' : '') + researchBox + '</div>';
        }
        const lots = r.lots || [];
        const co = p.confidence === 'company', rs = p.confidence === 'research';
        let h = '<div class="lp-c"><h3><span>🌾 Seed preparation' + tlf('seed-prep') + '</span><em>' + (rs ? "Anee's research" : (co ? (p.source === 'brand' ? 'Seed company' : 'PhilRice') : 'General practice')) + '</em></h3>'
            + '<div class="lp-seed" style="margin-top:0;border:0;padding:0;animation:none">' + window.lpHoursHtml(p) + '</div>' + researchBox
            + '<p class="lp-p" style="font-size:.84rem;margin-top:.7rem">' + esc(p.say) + (p.refreshNote ? ' ' + esc(p.refreshNote) : '') + (p.floatRule ? ' ' + esc(p.floatRule) : '') + '</p>';
        if ((p.amounts || []).length) h += '<p class="lp-p" style="font-size:.84rem;margin-top:.4rem"><b>Seed to buy:' + tlf('seeding-rate') + '</b> ' + p.amounts.map((x) => esc(lotName(r, x.lot)) + ' ' + (x.lo === x.hi ? x.lo : x.lo + ' to ' + x.hi) + ' kg').join(', ') + '.</p>';
        else if (p.rateNote) h += '<p class="lp-p" style="font-size:.84rem;margin-top:.4rem">' + esc(p.rateNote) + '</p>';
        h += (p.timeline || []).map((t) => '<p class="lp-tlh2" style="--lc:' + lc(t.lot) + '"><i></i>' + esc(lotName(r, t.lot)) + ', sowing ' + esc(day(t.sow)) + '</p><div class="lp-tl">' + eventsHtml(t.events) + '</div>').join('');
        if ((p.steps || []).length) h += '<details class="lp-more-d"><summary>How to do it, step by step</summary><ol class="lp-steps-ol">' + p.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol></details>';
        return h + '</div>';
    }
    function eventsHtml(events) {
        const out = [];
        (events || []).forEach((e) => { const last = out[out.length - 1]; if (e.kind === 'water' && last && last.kind === 'water') { last.times.push(e.at); return; } out.push(Object.assign({ times: [e.at] }, e)); });
        const ico = { soak: '💧', water: '🔁', incubate: '🧺', sow: '🌱' };
        return out.map((e) => '<div class="lp-ev"><span>' + (ico[e.kind] || '⏰') + '</span><div><b>' + esc(e.what) + '</b><small><em>' + esc(e.times.map((t, i) => (i === 0 || t.slice(0, 10) !== e.times[i - 1].slice(0, 10) ? day(t) + ', ' : '') + time(t)).join(', ')) + '</em>. ' + esc(e.how) + '</small></div></div>').join('');
    }

    function weatherHtml(r, a) {
        const w = r.weather || {};
        const fc = w.forecast || [];
        if (!w.place && !w.enso && !a.weather) return '';
        let h = '<div class="lp-c"><h3>🌦️ Weather and water' + (w.enso ? '<em>' + esc(w.enso) + tlf('enso') + '</em>' : '') + '</h3>';
        if (a.weather) h += '<p class="lp-p" style="margin-bottom:.7rem">' + esc(a.weather) + '</p>';
        if (fc.length) h += '<div class="lp-scrollx"><div class="lp-fc" tabindex="0" aria-label="The forecast, day by day. It slides sideways.">' + fc.map((d) => '<div class="lp-fd"><small>' + esc(fmt(d.date, { weekday: 'short' })) + ' ' + esc(fmt(d.date, { day: 'numeric' })) + '</small><span>' + esc(d.emoji || '') + '</span><b>' + (d.max != null ? d.max + '°' : '') + '</b><i>' + (d.pop != null ? d.pop + '%' : '') + '</i></div>').join('') + '</div>'
            + '<button type="button" class="lp-nudge" data-nudge aria-label="Slide to see more days">Slide<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button></div>';
        if (w.history || w.plowRain != null) {
            h += '<div class="lp-wx">' + (w.history ? '<div><b>' + w.history.rain + ' mm' + tlf('rain-mm') + '</b><small>rain over the prep, a ' + w.history.years + ' year average</small></div><div><b>' + w.history.dry + ' to ' + w.history.wet + '</b><small>mm, driest to wettest year</small></div>' : '')
                + (w.plowRain != null ? '<div><b>' + w.plowRain + ' mm' + (w.history ? '' : tlf('rain-mm')) + '</b><small>in the 3 plowing weeks</small></div>' : '') + '</div>';
        }
        if (w.place) h += '<p class="lp-fineprint" style="margin-top:.6rem">At ' + esc(w.place.label) + '. Forecast and history from Open-Meteo, ENSO from NOAA.</p>';
        return h + '</div>';
    }
    function edgeOf(box) {
        const sc = box.querySelector('.lp-fc');
        if (!sc) return;
        const fit = sc.scrollWidth <= sc.clientWidth + 2;
        box.classList.toggle('is-fit', fit);
        box.classList.toggle('is-end', !fit && sc.scrollLeft + sc.clientWidth >= sc.scrollWidth - 4);
        if (sc.scrollLeft > 8) box.classList.add('is-moved');
    }
    function wireScrollx() {
        document.querySelectorAll('#lpViewBody .lp-scrollx').forEach((box) => {
            const sc = box.querySelector('.lp-fc');
            sc.addEventListener('scroll', () => edgeOf(box), { passive: true });
            requestAnimationFrame(() => edgeOf(box));
        });
    }

    /* One lot, step by step. */
    function lotHtml(r, i) {
        const lots = r.lots || [];
        const lot = lots[i] || {};
        const pl = (r.perLot || [])[i] || { steps: [] };
        const people = {};
        Object.values(r.calendar || {}).forEach((items) => items.forEach((x) => { if (x.lot === i) people[x.key] = Math.max(people[x.key] || 0, x.people || 0); }));
        const plant = (r.plantDays || []).filter((p) => p.lot === i);
        // A lot with its own crop (2026-10-16) wears it: its icon, its words, its planting day word.
        const word = lot.dayWord || r.dayWord || 'planting';
        const lcrop = lot.crop || r.crop || {};
        let h = lots.length > 1 ? '<div class="lp-lotpick" id="lpLotPick">' + lots.map((l, j) => '<button type="button" class="' + (j === i ? 'is-on' : '') + '" data-lot="' + j + '" style="--lc:' + lc(j) + '"><i></i>' + esc(l.name) + '</button>').join('') + '</div>' : '';
        const amt = ((r.seed || {}).amounts || []).find((x) => x.lot === i);
        const own = (r.rules || []).filter((n) => (n.lots || []).includes(i) && lots.length > 1);
        h += '<div class="lp-c"><div class="lp-lothead" style="--lc:' + lc(i) + '"><i>' + esc(lcrop.icon || '🌱') + '</i><div><b>' + esc(lot.name || '') + '</b><small>' + (lot.crop ? esc(String(lot.crop.label).replace(/\s*,.*$/, '') + (lot.method && lot.method.label ? ', ' + String(lot.method.label).toLowerCase() : '') + (lot.variety ? ', ' + lot.variety : '')) + ' · ' : '') + esc(sizeOf(lot)) + ' · ' + esc(word) + ' ' + esc(lot.planting ? fmt(lot.planting, { weekday: 'long', month: 'long', day: 'numeric' }) : '') + (amt ? ' · seed ' + (amt.lo === amt.hi ? amt.lo : amt.lo + ' to ' + amt.hi) + ' kg' : '') + '</small></div></div>'
            + (lot.copiedFrom != null && lots[lot.copiedFrom] ? '<span class="lp-copytag">⧉ ' + (lot.edited ? 'Started from the answers of ' : 'Same answers as ') + esc(lots[lot.copiedFrom].name) + '</span>' : '')
            + (lot.shift ? '<p class="lp-moved">' + ((r.crew || {}).dateMode === 'first'
                ? 'Anee times this lot for ' + esc(day(lot.planting)) + ', ' + lot.shift + (lot.shift === 1 ? ' day' : ' days') + ' after the first lot, when the crew can have it ready.'
                : 'Anee moved this lot ' + lot.shift + (lot.shift === 1 ? ' day' : ' days') + ' later than the ' + esc(day(lot.asked)) + ' you asked, so the crew can finish it in time.') + '</p>' : '')
            + (lot.squeeze ? '<p class="lp-moved">⏳ ' + lot.squeeze.left + ' days left, the full preparation needs ' + lot.squeeze.needed + '. ' + lot.squeeze.count + ' steps were squeezed to fit, each marked below.</p>' : '')
            + (() => { const cc = r.costs, p = cc && cc.priced && (cc.perLot || [])[i]; return p && p.total > 0 ? '<p class="lp-moved">💰 This lot costs about <b>' + esc((cc.currency || '₱') + Math.round(p.total).toLocaleString('en-US')) + '</b>' + (p.hired > 0 && p.labor + p.planting > 0 ? ': workers ' + esc((cc.currency || '₱') + Math.round(p.labor + p.planting).toLocaleString('en-US')) + ', hired ' + esc((cc.currency || '₱') + Math.round(p.hired).toLocaleString('en-US')) : '') + '.</p>' : ''; })()
            + ((lot.answers || []).length ? '<div class="lp-ans">' + lot.answers.map((x) => '<span><b' + tlAt(ANS_TL[x[0]]) + '>' + esc(x[0]) + '</b>' + esc(x[1]) + '</span>').join('') + '</div>' : '')
            + (own.length ? '<ul class="lp-rules" style="margin-top:.6rem">' + own.map((n) => '<li><span>' + (n.level === 'warn' ? '⚠️' : '💡') + '</span><div>' + esc(n.text) + '</div></li>').join('') + '</ul>' : '')
            + '</div>';
        // The steps and the seed reminders, in date order.
        const items = (pl.steps || []).map((s) => ({ at: s.start || s.winFrom, s }));
        const tl = ((r.seed || {}).timeline || []).find((t) => t.lot === i);
        const evs = [];
        ((tl && tl.events) || []).forEach((e) => { const last = evs[evs.length - 1]; if (e.kind === 'water' && last && last.e.kind === 'water') { last.times.push(e.at); return; } evs.push({ e, times: [e.at] }); });
        evs.forEach((x) => items.push({ at: x.e.at.slice(0, 10), ev: x }));
        items.sort((x, y) => (x.at < y.at ? -1 : (x.at > y.at ? 1 : (x.ev ? -1 : 1))));
        const seen = new Set();     // the terms already flagged in this lot's list
        h += '<div class="lp-list">' + items.map((x, k) => (x.ev ? evHtml(x.ev, seen) : stepHtml(r, x.s, people[x.s.key], plant, word, i, k, seen))).join('') + '</div>';
        return h;
    }
    function evHtml(x, seen) {
        const ico = { soak: '💧', water: '🔁', incubate: '🧺', sow: '🌱' };
        return '<div class="lp-ts"><span>' + (ico[x.e.kind] || '⏰') + '</span><div><b' + (seen ? tlOnce(seen, { soak: 'seed-soaking', incubate: 'seed-incubation' }[x.e.kind]) : '') + '>' + esc(x.e.what) + '</b><small>' + esc(x.times.map((t, i) => (i === 0 || t.slice(0, 10) !== x.times[i - 1].slice(0, 10) ? day(t) + ', ' : '') + time(t)).join(', ')) + '</small></div></div>';
    }
    function whenOf(r, row, ppl, plant, lot) {
        if (row.start) {
            const ms = (row.machines || []).map(eqName).join(', ').toLowerCase();
            return 'Crew: ' + day(row.start) + (row.end !== row.start ? ' to ' + day(row.end) : '') + (row.crew === 'machine' ? ', ' + (ms || String((r.crew || {}).equipmentLabel || 'machine').toLowerCase()) : (row.byHand ? ', by hand' : (ppl ? ', ' + ppl + (ppl === 1 ? ' person' : ' people') : '')));
        }
        if (row.crew) return 'The crew could not fit this in.';
        if (row.planting) { const pd = plant.find((p) => p.key === row.key); return (row.winFrom === row.winTo ? day(row.winFrom) : day(row.winFrom) + ' or ' + day(row.winTo)) + (pd ? ', about ' + pd.people + ' ' + pd.words : ''); }
        return row.winFrom === row.winTo ? 'On ' + day(row.winFrom) : 'Any day from ' + day(row.winFrom) + ' to ' + day(row.winTo);
    }
    function stepHtml(r, row, ppl, plant, word, lot, k, seen) {
        const s = stepOf(r, row.key, lot);
        const anee = s.tag === 'anee';
        const at = row.start || row.winFrom;
        const tags = '<span class="lp-tg is-' + esc(s.tag || 'extension') + '">' + (anee ? '✦ ' : '') + esc(TAG[s.tag] || '') + '</span>'
            + (s.reason && REASON[s.reason] ? '<span class="lp-tg is-reason">' + esc(REASON[s.reason]) + '</span>' : '')
            + (s.squeezed ? '<span class="lp-tg is-squeezed">⏳ ' + (s.squeezed.state === 'shortened' ? 'Shortened' : 'Moved up') + '</span>' : '')
            + (row.late ? '<span class="lp-tg is-late">' + row.late + (row.late === 1 ? ' day late' : ' days late') + '</span>' : '');
        return '<button type="button" class="lp-s' + (anee ? ' is-anee' : '') + (s.squeezed ? ' is-squeezed' : '') + '" data-step="' + lot + '|' + esc(row.key) + '" style="animation-delay:' + Math.min(k, 12) * 25 + 'ms"><span class="lp-cal1"><i>' + esc(fmt(at, { month: 'short' })) + '</i><b>' + esc(fmt(at, { day: 'numeric' })) + '</b><small>' + esc(fmt(at, { weekday: 'short' })) + '</small></span>'
            + '<span class="lp-sb"><b' + (seen ? tlOnce(seen, STEP_TL[row.key]) : '') + '>' + esc(s.what) + '</b><span class="lp-tags">' + tags + '</span><span class="lp-when" style="display:block">' + esc(whenOf(r, row, ppl, plant, lot)) + '</span>'
            + (s.how ? '<span class="lp-how" style="display:block">' + esc(s.how) + (s.rule ? ' ' + esc(s.rule) : '') + '</span>' : '')
            + (s.squeezed ? '<span class="lp-sq" style="display:block"><b' + (seen ? tlOnce(seen, 'squeezed-steps') : '') + '>Squeezed:</b> ' + esc(s.squeezed.why) + '</span>' : '')
            + (s.why ? '<span class="lp-why" style="display:block"><b>Why:</b> ' + esc(s.why) + '</span>' : '')
            + (s.note ? '<span class="lp-note">' + (OPT().aneeFace ? '<img src="' + esc(OPT().aneeFace) + '" alt="">' : '') + '<span>' + esc(s.note) + '</span></span>' : '') + '</span></button>';
    }

    /* One step or crew job, explained: what, how, who and with what, the days, the lots, tips. */
    function stepSheet(lot, key) {
        const r = VIEW.item.report || {}, lots = r.lots || [];
        const s = stepOf(r, key, lot);
        $('lpStepTitle').firstChild.textContent = s.what || key;
        $('lpStepSub').textContent = [TAG[s.tag], s.reason && REASON[s.reason], s.lotsWords].filter(Boolean).join(' · ');
        const rows = [];
        (r.perLot || []).forEach((pl) => { const row = (pl.steps || []).find((x) => x.key === key); if (row && (lot === null || pl.lot === lot || lots.length === 1)) rows.push([pl.lot, row]); });
        const allRows = [];
        (r.perLot || []).forEach((pl) => { const row = (pl.steps || []).find((x) => x.key === key); if (row) allRows.push([pl.lot, row]); });
        const plant = r.plantDays || [];
        let people = 0, pdays = 0, mach = 0;
        Object.values(r.calendar || {}).forEach((items) => items.forEach((x) => { if (x.key === key) { people = Math.max(people, x.people || 0); pdays += x.people || 0; if (x.machine) mach += x.n || 1; } }));
        const used = []; allRows.forEach(([, row]) => (row.machines || []).forEach((m) => { if (!used.includes(m)) used.push(m); }));
        const fleet = (r.crew || {}).fleet || [];
        let h = '';
        if (s.squeezed) h += '<div class="lp-sq"><b>Squeezed to fit the time.' + tlf('squeezed-steps') + '</b> ' + esc(s.squeezed.why) + '</div>';
        h += '<div><p class="lp-sk">When' + (allRows.length > 1 ? ', lot by lot' : '') + '</p><div class="lp-swhen">' + allRows.map(([i, row]) => '<div style="--lc:' + lc(i) + '">' + (lots.length > 1 ? '<i></i>' : '') + '<span>' + (lots.length > 1 ? '<b>' + esc(lotName(r, i)) + ':</b> ' : '') + esc(whenOf(r, row, null, plant.filter((p) => p.lot === i), i)) + (row.workDays ? ', ' + row.workDays + (row.workDays === 1 ? ' work day' : ' work days') : '') + (row.late ? ', ' + row.late + ' days late' : '') + '</span></div>').join('') + '</div></div>';
        const anyCrew = allRows.some(([, row]) => row.crew);
        if (anyCrew) {
            const stats = [];
            used.forEach((m) => {
                const f = fleet.find((x) => x.type === m) || {};
                const ph = f.photo || eqPhoto(m);
                stats.push('<div><span class="lp-ph">' + (ph ? '<img src="' + esc(ph) + '" alt="">' : '🚜') + '</span><span><b>' + esc(eqName(m)) + tlf(EQ_TL[m]) + '</b><small>' + (f.hired && !f.own ? 'Hired' + tlf('hired') + ', comes with its driver' : (f.hired ? 'Yours, or the hired one' : 'Yours, one worker drives it')) + '</small></span></div>');
            });
            if (allRows.some(([, row]) => row.byHand)) stats.push('<div><span class="lp-ph">🧑‍🌾</span><span><b>By hand</b><small>None of your machines can do it</small></span></div>');
            if (people) stats.push('<div><span class="lp-ph">👷</span><span><b>Up to ' + people + (people === 1 ? ' person' : ' people') + ' a day</b><small>About ' + pdays + (pdays === 1 ? ' person day' : ' person days') + tlf('person-days') + ' in all</small></span></div>');
            if (mach) stats.push('<div><span class="lp-ph">🚜</span><span><b>' + mach + (mach === 1 ? ' machine day' : ' machine days') + tlf('machine-days') + '</b><small>Across ' + (allRows.length === 1 ? 'the lot' : allRows.length + ' lots') + '</small></span></div>');
            if (stats.length) h += '<div><p class="lp-sk">Who and with what</p><div class="lp-sstats">' + stats.join('') + '</div></div>';
        }
        if (s.how) h += '<div><p class="lp-sk">How to do it</p><p class="lp-p">' + esc(s.how) + (s.rule ? ' ' + esc(s.rule) : '') + '</p></div>';
        if (s.why) h += '<div class="lp-why"><b>Why:</b> ' + esc(s.why) + '</div>';
        if (s.note) h += '<div class="lp-note">' + (OPT().aneeFace ? '<img src="' + esc(OPT().aneeFace) + '" alt="">' : '') + '<span>' + esc(s.note) + '</span></div>';
        const tips = tipsFor(key);
        if (tips.length) h += '<div><p class="lp-sk">Tips</p><ul class="lp-stips">' + tips.map((t) => '<li>' + esc(t) + '</li>').join('') + '</ul></div>';
        $('lpStepBody').innerHTML = h;
        sheetOpen($('lpStepSheet'));
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
            if (VIEW.tab === 'farm') { fitTimeline(); document.querySelectorAll('.lp-scrollx').forEach(edgeOf); }
            const segTop = $('lpSeg').getBoundingClientRect().top;
            if (segTop < 0 || segTop > 120) $('lpSeg').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
            return;
        }
        const nudge = e.target.closest('[data-nudge]');
        if (nudge) { const sc = nudge.parentNode.querySelector('.lp-fc'); sc.scrollBy({ left: sc.clientWidth * .7, behavior: reduce() ? 'auto' : 'smooth' }); return; }
        const job = e.target.closest('[data-job]');
        if (job && VIEW) { stepSheet(null, job.dataset.job); return; }
        const st = e.target.closest('[data-step]');
        if (st && VIEW) { const [i, k] = st.dataset.step.split('|'); stepSheet(Number(i), k); return; }
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
        if (act === 'crewdays') { const box = $('lpCrewDays'), open = !box.classList.contains('is-open'); box.classList.toggle('is-open', open); a.setAttribute('aria-expanded', String(open)); a.firstChild.textContent = open ? 'Hide the days' : 'Day by day'; return; }
        if (act === 'send') { openPort(); return; }
        if (act === 'season') { openSeason(); return; }
        if (act === 'rename') { openRename(VIEW.item); return; }
        if (act === 'again') { VIEW.mode = 'again'; closeView(); return; }
        if (act === 'delete') {
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this plan?', message: 'Only the plan is deleted. The credits are not refunded.', confirmText: 'Delete' }) : confirm('Delete this plan?');
            if (!ok) return;
            try {
                const res = await window.api(U.del(VIEW.item.id), { method: 'DELETE' });
                window.toast?.(res.message);
                LIST.stale = true;
                VIEW.mode = VIEW.mode === 'fresh' ? 'again' : 'saved';
                closeView();
                if (!$('lpListPane').hidden) loadList(false);
            } catch (err) { window.toast?.(err.message, 'error'); }
        }
    });
    $('lpViewBody').addEventListener('keydown', (e) => { if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('.lp-bar2[data-step]')) { e.preventDefault(); e.target.click(); } });
    $('lpViewSend').addEventListener('click', () => openPort());
    $('lpViewSeason').addEventListener('click', () => openSeason());
    $('lpViewName').addEventListener('click', () => VIEW && openRename(VIEW.item));

    /* ---------------- rename ---------------- */
    let REN = null;
    function openRename(item) { REN = item; $('lpRenInput').value = item.title || ''; sheetOpen($('lpRenSheet'), $('lpRenInput')); }
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

    /* ---------------- make a cropping schedule from the plan ---------------- */
    function openSeason() {
        if (!VIEW) return;
        const r = VIEW.item.report || {};
        const lots = r.lots || [], per = r.perLot || [];
        $('lpMakeForm').hidden = false; $('lpMakeDone').hidden = true; $('lpMakeGo').hidden = false; $('lpMakeOpen').hidden = true; $('lpMakeClose').textContent = 'Cancel';
        $('lpMakeName').value = VIEW.item.title || '';
        const steps = per.reduce((n, l) => n + ((l.steps || []).length), 0);
        const starts = per.flatMap((l) => (l.steps || []).map((s) => s.start || s.winFrom)).filter(Boolean).sort();
        const plantings = per.map((l) => l.planting).filter(Boolean).sort();
        const day = (d) => { try { return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }); } catch (_) { return d; } };
        $('lpMakeSum').innerHTML = [
            ['🌾', lots.length + (lots.length === 1 ? ' lot' : ' lots'), lots.map((l) => l.name).join(', ')],
            ['📋', 'About ' + steps + ' activities', 'Each on the day the crew starts it, with the seed soak and the machine bookings'],
            ['📅', starts.length ? day(starts[0]) + ' to ' + day(plantings[plantings.length - 1] || starts[starts.length - 1]) : 'Your dates', (r.dayWord ? 'Counted to the ' + r.dayWord + ' day of each lot' : '')],
        ].map(([i, b, s]) => '<div><span>' + i + '</span><p><b>' + esc(b) + '</b>' + (s ? '<small>' + esc(s) + '</small>' : '') + '</p></div>').join('');
        const prev = r.season;
        $('lpMakePrev').hidden = !prev;
        if (prev) $('lpMakePrev').innerHTML = 'You made <a href="' + esc(U.hub.replace('__ID__', prev.id)) + '">' + esc(prev.title) + '</a> from this plan on ' + esc(prev.at) + '. Making it again adds another season.';
        sheetOpen($('lpMakeSheet'));
    }
    $('lpMakeGo').addEventListener('click', async () => {
        if (!VIEW) return;
        const name = $('lpMakeName').value.trim();
        if (!name) { window.toast?.('Give the season a name.', 'error'); $('lpMakeName').focus(); return; }
        $('lpMakeGo').disabled = true; $('lpMakeGo').textContent = 'Making it…';
        try {
            const res = await window.api(U.season, { method: 'POST', body: { id: VIEW.item.id, title: name } });
            $('lpMakeForm').hidden = true; $('lpMakeDone').hidden = false;
            $('lpMakeDoneP').textContent = res.message;
            $('lpMakeGo').hidden = true;
            $('lpMakeOpen').href = res.data.url; $('lpMakeOpen').hidden = false;
            $('lpMakeClose').textContent = 'Close';
            if (VIEW.item.report) VIEW.item.report.season = res.data.season;
        } catch (err) { if (!err || !err.tierLock) window.toast?.(err.message, 'error'); }
        finally { $('lpMakeGo').disabled = false; $('lpMakeGo').textContent = 'Make the season'; }
    });

    /* ---------------- your plans ---------------- */
    const LIST = { page: 1, q: '', rows: [], hasMore: false, stale: true, busy: false };
    window.lpListStale = () => { LIST.stale = true; };
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
    window.openSaved = openSaved;
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

    const go = () => window.lpBoot();
    if (window.api && window.aneeFieldProfile) go(); else window.addEventListener('load', go, { once: true });
})();
</script>
@endsection
