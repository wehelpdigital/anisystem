@extends('layouts.public')

{{-- Try and Ask Anee (AskAneeController). The farm first (name, email, size,
     crop, place), then one question in a search bar. Anee reads it, a short
     wait plays, and the answer goes to the email as a page of its own. --}}
@include('public.partials.site-css')
@include('public.site.css')

@section('title_full', 'Ask Anee a Farming Question for Free | anee.io')
@section('meta_description', 'Ask Anee, the anee.io AI farm technician, one farming question for free. Palay, mais, gulay, pests or fertilizer: tell her about your farm and get a full answer.')

@push('head')
    <link rel="canonical" href="{{ url('/ask-anee') }}">
    <meta property="og:title" content="Ask Anee a farming question, free">
    <meta property="og:description" content="Ask Anee, the anee.io AI farm technician, one farming question and get a full answer for your farm.">
    <meta property="og:url" content="{{ url('/ask-anee') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone.jpg') }}">
    @if ($siteKey)
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ $siteKey }}" async defer></script>
    @endif
@endpush

@section('content')
<style>
    /* Everything here wears the house easing and stands still for reduced motion. */
    .ak-hero { position: relative; overflow: hidden; background: radial-gradient(60rem 30rem at 85% -10%, rgb(245 197 24 / .16), transparent 60%),
        radial-gradient(50rem 26rem at 0% 0%, rgb(168 204 126 / .25), transparent 60%), linear-gradient(180deg, #f3f8ec 0%, #ffffff 100%); border-bottom: 1px solid #e3eed6; }
    .ak-top { text-align: center; }
    .ak-face { position: relative; display: inline-block; }
    .ak-face img { width: 5.2rem; height: 5.2rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 4px #fff, 0 18px 40px -18px rgb(40 70 15 / .6); }
    .ak-face::after { content: ''; position: absolute; inset: -6px; border-radius: 999px; box-shadow: 0 0 0 0 rgb(107 159 61 / .35); animation: akHalo 2.6s ease-in-out infinite; }
    @keyframes akHalo { 50% { box-shadow: 0 0 0 12px rgb(107 159 61 / 0); } }
    .ak-kicker { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1rem; padding: .35rem .75rem; border-radius: 999px; background: #fff;
        border: 1px solid #d7e8c3; font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #3d6823; }
    .ak-h1 { margin-top: .8rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; line-height: 1.05;
        font-size: clamp(2.2rem, 6vw, 3.5rem); letter-spacing: -.02em; }
    .ak-h1 span { color: #4a7c2a; }
    .ak-lead { margin: .9rem auto 0; font-size: 1.05rem; line-height: 1.6; color: #4b5563; max-width: 34rem; }

    /* The two steps, said above the panel. */
    .ak-steps { display: flex; justify-content: center; align-items: center; gap: .6rem; margin: 1.6rem 0 1rem; }
    .ak-steps span { display: inline-flex; align-items: center; gap: .45rem; font-size: .84rem; font-weight: 800; color: #9ca3af; transition: color .28s cubic-bezier(.22,1,.36,1); }
    .ak-steps span i { display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; font-style: normal; background: #e5e7eb; color: #6b7280;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ak-steps span.is-on { color: #2f5219; } .ak-steps span.is-on i { background: #4a7c2a; color: #fff; }
    .ak-steps span.is-done i { background: #cfe3b8; color: #2f5219; }
    .ak-steps b { width: 2.2rem; height: 2px; background: #dbe8cc; border-radius: 2px; }

    .ak-stage { max-width: 46rem; margin: 0 auto; }
    .ak-step { animation: akIn .36s cubic-bezier(.22,1,.36,1) both; }
    .ak-step[hidden] { display: none; }
    @keyframes akIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

    /* ---- step 1: the farm ---- */
    .ak-card { border-radius: 1.5rem; background: #fff; border: 1px solid #e2ecd6; padding: 1.4rem; box-shadow: 0 30px 70px -45px rgb(20 40 10 / .5); text-align: left; }
    @media (min-width: 640px) { .ak-card { padding: 1.8rem; } }
    .ak-card h2 { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #14210c; }
    .ak-card .ak-why { margin-top: .25rem; font-size: .88rem; color: #6b7280; }
    .ak-grid { display: grid; gap: .9rem; margin-top: 1.1rem; }
    @media (min-width: 640px) { .ak-grid { grid-template-columns: 1fr 1fr; } .ak-grid .full { grid-column: 1 / -1; } }
    .ak-lbl { display: block; font-size: .78rem; font-weight: 800; color: #374151; margin-bottom: .35rem; }
    .ak-input { width: 100%; border: 1px solid #d6dfcb; border-radius: .85rem; padding: .7rem .85rem; font-size: 1rem; color: #14210c; background: #fff;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ak-input:focus { outline: none; border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .15); }
    .ak-input.is-bad, .ak-tag.is-bad { border-color: #dc2626; box-shadow: 0 0 0 4px rgb(220 38 38 / .1); }
    .ak-size { display: flex; gap: .5rem; }
    .ak-size input { flex: 1; min-width: 0; }
    .ak-seg { display: inline-flex; padding: .2rem; border-radius: .85rem; background: #eef4e6; flex: none; }
    .ak-seg button { padding: .45rem .7rem; border-radius: .65rem; font-size: .8rem; font-weight: 800; color: #4b5563;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ak-seg button.is-on { background: #fff; color: #2f5219; box-shadow: 0 1px 3px rgb(0 0 0 / .08); }
    .ak-tag { display: flex; align-items: center; gap: .55rem; width: 100%; padding: .68rem .8rem; border-radius: .85rem; text-align: left;
        border: 1.5px dashed #b9d39b; background: #f7fbf1; color: #3d6823; font-weight: 700; font-size: .95rem;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ak-tag:hover { background: #eef6e5; }
    .ak-tag.is-set { border-style: solid; border-color: #6b9f3d; background: #eef6e5; color: #1f3a0f; }
    .ak-tag .ic { font-size: 1.1rem; }
    .ak-tag [data-t] { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ak-tag .chev { margin-left: auto; flex: none; width: 1rem; height: 1rem; color: #7ea35a; }
    .ak-two { display: grid; gap: .5rem; grid-template-columns: 1fr 1fr; }
    @media (max-width: 380px) { .ak-two { grid-template-columns: 1fr; } }
    .ak-abroad { margin-top: .45rem; font-size: .76rem; font-weight: 700; color: #4a7c2a; text-decoration: underline; }
    .ak-err { margin-top: .8rem; padding: .6rem .8rem; border-radius: .8rem; background: #fef2f2; font-size: .86rem; font-weight: 700; color: #b91c1c; }
    .ak-go { margin-top: 1.2rem; width: 100%; justify-content: center; }
    .ak-small { margin-top: .7rem; font-size: .74rem; color: #9ca3af; line-height: 1.5; text-align: center; }
    .ak-small a { text-decoration: underline; }
    .grecaptcha-badge { visibility: hidden !important; }

    /* ---- step 2: one question, the way a search engine asks ---- */
    .ak-hello { display: flex; flex-direction: column; align-items: center; gap: .55rem; margin-bottom: 1.1rem; text-align: center; }
    .ak-hello p { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #14210c; }
    .ak-farm { display: inline-flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .35rem; }
    .ak-farm span { padding: .25rem .6rem; border-radius: 999px; background: #fff; border: 1px solid #dbe8cc; font-size: .78rem; font-weight: 700; color: #3d6823; }
    .ak-farm button { font-size: .78rem; font-weight: 800; color: #4a7c2a; text-decoration: underline; }
    .ak-search { display: flex; align-items: flex-end; gap: .5rem; padding: .45rem .45rem .45rem 1.1rem; border-radius: 2rem; background: #fff;
        border: 1.5px solid #dbe5cf; box-shadow: 0 14px 40px -24px rgb(20 40 10 / .45);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ak-search:focus-within { border-color: #6b9f3d; box-shadow: 0 18px 46px -24px rgb(20 40 10 / .55), 0 0 0 4px rgb(107 159 61 / .14); }
    .ak-search > svg { flex: none; width: 1.35rem; height: 1.35rem; margin-bottom: .95rem; color: #6b9f3d; }
    .ak-search textarea { flex: 1; min-width: 0; resize: none; border: 0; background: transparent; padding: .85rem 0; font-size: 1.08rem; line-height: 1.45;
        max-height: 9.5rem; color: #14210c; }
    .ak-search textarea:focus { outline: none; }
    .ak-ask { flex: none; display: inline-flex; align-items: center; gap: .4rem; height: 3.1rem; padding: 0 1.2rem; border-radius: 999px; font-weight: 800;
        color: #3b2f00; background: #f5c518; transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .ak-ask:hover { transform: translateY(-1px); }
    .ak-ask:disabled { opacity: .45; transform: none; }
    .ak-ask svg { width: 1.15rem; height: 1.15rem; }
    @media (max-width: 480px) { .ak-ask span { display: none; } .ak-ask { width: 3.1rem; padding: 0; justify-content: center; } }
    .ak-ask.is-busy svg { animation: akSpin .8s linear infinite; }
    @keyframes akSpin { to { transform: rotate(360deg); } }
    .ak-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: .45rem; margin-top: 1rem; }
    .ak-chips small { width: 100%; text-align: center; font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #9ca3af; }
    .ak-chips button { padding: .45rem .8rem; border-radius: 999px; border: 1px solid #dbe8cc; background: #fff; font-size: .84rem; font-weight: 700; color: #3d6823;
        transition: background .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .ak-chips button:hover { background: #eef6e5; transform: translateY(-1px); }
    .ak-note { margin-top: .9rem; text-align: center; font-size: .8rem; color: #6b7280; }

    /* What Anee says back, under the bar. */
    .ak-said { display: flex; gap: .8rem; align-items: flex-start; margin-top: 1.2rem; padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff;
        border: 1px solid #e3ecd8; box-shadow: 0 10px 26px -20px rgb(20 40 10 / .45); text-align: left; animation: akIn .32s cubic-bezier(.22,1,.36,1) both; }
    .ak-said > img { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; }
    .ak-said .t { font-size: .98rem; line-height: 1.6; color: #1f2a17; }
    .ak-said .t p + p { margin-top: .4rem; }
    .ak-said .t .anee-emo img { width: 1.4em; height: 1.4em; vertical-align: -.3em; }
    .ak-said.is-no { background: #fffbeb; border-color: #fde68a; }
    .ak-dots { display: inline-flex; align-items: center; gap: .25rem; padding: .5rem .1rem; }
    /* While Anee reads, the dots sit level with her face, in the middle of the card. */
    .ak-said.is-wait { align-items: center; }
    .ak-said.is-wait .t { display: flex; align-items: center; min-height: 2.6rem; }
    .ak-said.is-wait .ak-dots { padding: 0 .1rem; }
    .ak-dots i { width: .5rem; height: .5rem; border-radius: 999px; background: #8fb86a; animation: akDot 1.1s ease-in-out infinite; }
    .ak-dots i:nth-child(2) { animation-delay: .15s; } .ak-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes akDot { 0%, 80%, 100% { opacity: .35; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }

    /* ---- the end: sent ---- */
    .ak-sent { text-align: center; }
    .ak-tick { display: grid; place-items: center; width: 4.2rem; height: 4.2rem; margin: 0 auto; border-radius: 999px; background: #eef6e5; color: #3d6823;
        animation: akPop .5s cubic-bezier(.22,1,.36,1) both; }
    .ak-tick svg { width: 2rem; height: 2rem; }
    @keyframes akPop { from { transform: scale(.6); opacity: 0; } to { transform: none; opacity: 1; } }
    .ak-sent h2 { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: #14210c; }
    .ak-sent > p { margin-top: .4rem; color: #4b5563; }
    .ak-sent .ak-q { margin: 1rem auto 0; max-width: 32rem; padding: .8rem 1rem; border-radius: 1rem; background: #f3f8ec; color: #1f3a0f; font-weight: 600; }
    .ak-more { margin-top: 1.4rem; padding: 1.2rem; border-radius: 1.2rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 80%); text-align: left; }
    .ak-more b { display: block; font-family: var(--font-heading); font-size: 1.15rem; color: #fff; }
    .ak-more p { margin-top: .35rem; font-size: .92rem; color: #d7e6c8; }
    .ak-more .btns { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .5rem; }
    .ak-ghost { display: inline-flex; align-items: center; justify-content: center; padding: .6rem 1rem; border-radius: .8rem; font-weight: 700;
        color: #fff; border: 1.5px solid rgb(255 255 255 / .45); background: transparent; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ak-ghost:hover { background: rgb(255 255 255 / .1); }
    /* Start over: the form comes back empty. */
    .ak-again { margin-top: 1.1rem; display: flex; flex-direction: column; align-items: center; gap: .45rem; }
    .ak-reset { display: inline-flex; align-items: center; gap: .45rem; padding: .6rem 1.1rem; border-radius: 999px; font-weight: 800; font-size: .92rem;
        color: #2f5219; background: #fff; border: 1.5px solid #cfe3b8;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .ak-reset:hover { background: #f3f8ec; border-color: #9fc47a; transform: translateY(-1px); }
    .ak-reset svg { width: 1.05rem; height: 1.05rem; transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .ak-reset:hover svg { transform: rotate(-160deg); }
    .ak-again p { font-size: .8rem; color: #6b7280; }

    /* ---- the refusal: why this email cannot ask now ---- */
    .ak-modal { position: fixed; inset: 0; z-index: 70; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .ak-modal { align-items: center; padding: 1rem; } }
    .ak-modal[hidden] { display: none; }
    .ak-modal-bg { position: absolute; inset: 0; background: rgb(14 22 9 / .55); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ak-modal.is-on .ak-modal-bg { opacity: 1; }
    .ak-modal-card { position: relative; width: 100%; max-width: 27rem; padding: 2rem 1.4rem calc(1.4rem + env(safe-area-inset-bottom, 0px)); text-align: center;
        background: #fff; border-radius: 1.6rem 1.6rem 0 0; box-shadow: 0 40px 80px -30px rgb(14 22 9 / .6);
        transform: translateY(100%); transition: transform .32s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 640px) { .ak-modal-card { border-radius: 1.6rem; padding: 2.1rem 1.8rem 1.6rem; opacity: 0; transform: translateY(18px) scale(.96); } }
    .ak-modal.is-on .ak-modal-card { transform: none; opacity: 1; }
    .ak-modal-card::before { content: ''; position: absolute; inset: 0 0 auto; height: 6.5rem; border-radius: inherit;
        background: radial-gradient(18rem 7rem at 50% 0%, rgb(245 197 24 / .2), transparent 70%); pointer-events: none; }
    .ak-modal-face { position: relative; display: inline-block; }
    .ak-modal-face img { width: 4.6rem; height: 4.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 4px #fff, 0 14px 30px -14px rgb(40 70 15 / .55); }
    .ak-modal-face span { position: absolute; right: -.35rem; bottom: -.2rem; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
        font-size: 1.05rem; background: #fff; box-shadow: 0 4px 12px -4px rgb(0 0 0 / .25); }
    .ak-modal.is-on .ak-modal-face span { animation: akPop .5s .12s cubic-bezier(.22,1,.36,1) both; }
    .ak-modal-card h3 { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; line-height: 1.2; color: #14210c; text-wrap: balance; }
    .ak-modal-card > p { margin-top: .5rem; font-size: .96rem; line-height: 1.6; color: #4b5563; }
    .ak-modal-card > p b { color: #1f3a0f; overflow-wrap: anywhere; }
    .ak-when { display: flex; align-items: center; gap: .6rem; margin-top: 1rem; padding: .75rem .9rem; border-radius: 1rem; text-align: left;
        background: #f3f8ec; border: 1px solid #dbe8cc; }
    .ak-when svg { flex: none; width: 1.3rem; height: 1.3rem; color: #4a7c2a; }
    .ak-when small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b8f4a; }
    .ak-when b { display: block; font-size: .95rem; color: #1f3a0f; }
    .ak-modal-btns { display: flex; flex-direction: column; gap: .5rem; margin-top: 1.3rem; }
    .ak-modal-btns .btn { width: 100%; justify-content: center; }
    .ak-modal-ok { padding: .7rem 1rem; border-radius: .85rem; font-weight: 800; color: #4b5563; background: #f3f4f6;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ak-modal-ok:hover { background: #e5e7eb; }
    .ak-modal-ok:focus-visible, .ak-reset:focus-visible { outline: 2px solid #6b9f3d; outline-offset: 2px; }
    .ak-modal-foot { margin-top: .8rem; font-size: .76rem; color: #9ca3af; }

    /* ---- the picker sheet (crop, province, town) ---- */
    .ak-sheet { position: fixed; inset: 0; z-index: 60; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .ak-sheet { align-items: center; } }
    .ak-sheet[hidden] { display: none; }
    .ak-sheet-bg { position: absolute; inset: 0; background: rgb(14 22 9 / .5); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ak-sheet.is-on .ak-sheet-bg { opacity: 1; }
    .ak-panel { position: relative; width: 100%; max-width: 32rem; max-height: 86vh; display: flex; flex-direction: column; background: #fff;
        border-radius: 1.4rem 1.4rem 0 0; transform: translateY(100%); transition: transform .28s cubic-bezier(.22,1,.36,1);
        padding-bottom: env(safe-area-inset-bottom, 0px); }
    @media (min-width: 640px) { .ak-panel { border-radius: 1.4rem; transform: translateY(16px) scale(.98); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); } }
    .ak-sheet.is-on .ak-panel { transform: none; opacity: 1; }
    .ak-panel-h { display: flex; align-items: center; gap: .6rem; padding: 1rem 1rem .6rem; }
    .ak-panel-h b { font-family: var(--font-heading); font-size: 1.1rem; color: #14210c; }
    .ak-x { margin-left: auto; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: #6b7280; background: #f3f4f6; }
    .ak-find { padding: 0 1rem .6rem; }
    .ak-list { overflow-y: auto; padding: 0 1rem 1rem; overscroll-behavior: contain; }
    .ak-grp { margin: .8rem 0 .45rem; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .ak-opts { display: flex; flex-wrap: wrap; gap: .4rem; }
    .ak-opt { padding: .5rem .75rem; border-radius: 999px; border: 1px solid #e0e8d6; background: #fff; font-size: .9rem; font-weight: 600; color: #1f2a17;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ak-opt:hover { background: #f3f8ec; }
    .ak-opt.is-on { background: #3d6823; border-color: #3d6823; color: #fff; }
    .ak-row-opt { display: flex; width: 100%; padding: .75rem .4rem; border-bottom: 1px solid #f1f4ee; text-align: left; font-size: .96rem; color: #1f2a17; }
    .ak-row-opt:hover { background: #f7fbf1; }
    .ak-none { padding: 1rem .2rem; font-size: .9rem; color: #6b7280; }

    /* ---- how it works: four steps on one path ---- */
    .ak-how-wrap { background: linear-gradient(180deg, #f9fbf6 0%, #ffffff 100%); border-top: 1px solid #e4efd4; }
    .ak-how-head { text-align: center; max-width: 36rem; margin: 0 auto; }
    .ak-how-head .ak-how-kick { margin-top: 0; font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .ak-how-head h2 { margin-top: .4rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.7rem, 4vw, 2.4rem); color: #14210c; line-height: 1.15; }
    .ak-how-head p { margin-top: .55rem; color: #6b7280; line-height: 1.6; }
    .ak-how { position: relative; display: grid; gap: 1.6rem; margin-top: 2.6rem; }
    /* A phone walks the steps down a dotted path on the left. */
    .ak-how::before { content: ''; position: absolute; left: 1.95rem; top: 2rem; bottom: 2rem; width: 2px;
        background: repeating-linear-gradient(to bottom, #b9d39b 0 6px, transparent 6px 12px); }
    .ak-st { position: relative; display: grid; grid-template-columns: 4rem minmax(0, 1fr); gap: 1rem; align-items: start;
        opacity: 0; transform: translateY(14px); transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1);
        transition-delay: calc(var(--i) * 110ms); }
    .ak-how.is-in .ak-st { opacity: 1; transform: none; }
    .ak-ic { position: relative; width: 4rem; height: 4rem; border-radius: 1.3rem; display: grid; place-items: center; color: #fff;
        background: linear-gradient(140deg, #6b9f3d, #3d6823); box-shadow: 0 14px 30px -14px rgb(40 70 15 / .6), 0 0 0 6px #f9fbf6;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ak-st:hover .ak-ic { transform: translateY(-3px) rotate(-3deg); }
    .ak-ic svg { width: 1.75rem; height: 1.75rem; }
    .ak-ic em { position: absolute; top: -.5rem; right: -.5rem; width: 1.55rem; height: 1.55rem; border-radius: 999px; display: grid; place-items: center;
        background: #f5c518; color: #3b2f00; font-style: normal; font-size: .76rem; font-weight: 900; box-shadow: 0 0 0 3px #fff; }
    .ak-st.is-gold .ak-ic { background: linear-gradient(140deg, #f7d443, #e0a800); color: #3b2f00; }
    .ak-st.is-gold .ak-ic em { background: #3d6823; color: #fff; }
    .ak-st .txt { padding-top: .3rem; }
    .ak-st b { display: block; font-family: var(--font-heading); font-size: 1.08rem; font-weight: 800; color: #14210c; }
    .ak-st p { margin-top: .3rem; font-size: .93rem; line-height: 1.55; color: #6b7280; }
    /* A wide screen lays them in a row on one dotted line. */
    @media (min-width: 900px) {
        .ak-how { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.5rem; }
        .ak-how::before { left: 12.5%; right: 12.5%; top: 2rem; bottom: auto; width: auto; height: 2px;
            background: repeating-linear-gradient(to right, #b9d39b 0 8px, transparent 8px 16px); }
        .ak-st { grid-template-columns: 1fr; justify-items: center; text-align: center; }
        .ak-st .txt { padding: .2rem .5rem 0; }
    }
    .ak-how-go { margin-top: 2.4rem; text-align: center; }
    @media (prefers-reduced-motion: reduce) {
        .ak-step, .ak-said, .ak-tick { animation: none; }
        .ak-st { opacity: 1; transform: none; transition: none; }
        .ak-st:hover .ak-ic { transform: none; }
        .ak-face::after, .ak-dots i, .ak-ask.is-busy svg { animation: none; }
        .ak-panel, .ak-sheet-bg, .ak-modal-card, .ak-modal-bg, .ak-reset, .ak-reset svg { transition: none; }
        .ak-modal.is-on .ak-modal-face span { animation: none; }
        .ak-reset:hover, .ak-reset:hover svg { transform: none; }
    }
</style>

<section class="ak-hero">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="ak-top">
            <div class="ak-face"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee, the anee.io AI farm technician"></div>
            <div><span class="ak-kicker">Free for everyone</span></div>
            <h1 class="ak-h1">Try and <span>Ask Anee</span></h1>
            <p class="ak-lead">Ask Anee, the anee.io AI farm technician, one farming question. She looks at it together with your farm and sends you a full answer by email.</p>
        </div>

        <div class="ak-steps" aria-hidden="true">
            <span class="is-on" id="akS1"><i>1</i>Your farm</span><b></b><span id="akS2"><i>2</i>Your question</span>
        </div>

        <div class="ak-stage" id="akStage">
            {{-- Step 1: who is asking, and about what farm. --}}
            <form class="ak-step ak-card" id="akFarm" novalidate autocomplete="on">
                <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                <h2>First, tell Anee about you and your farm</h2>
                <p class="ak-why">She uses these to fit the answer to your farm. Your answer is sent to your email.</p>
                <div class="ak-grid">
                    <div>
                        <label class="ak-lbl" for="akName">Your name</label>
                        <input class="ak-input" id="akName" name="name" autocomplete="name" maxlength="120" placeholder="Juan dela Cruz">
                    </div>
                    <div>
                        <label class="ak-lbl" for="akEmail">Email</label>
                        <input class="ak-input" id="akEmail" name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" placeholder="you@example.com">
                    </div>
                    <div>
                        <label class="ak-lbl" for="akSize">Farm size</label>
                        <div class="ak-size">
                            <input type="number" inputmode="decimal" step="any" min="0" class="ak-input" id="akSize" placeholder="1.5">
                            <div class="ak-seg" role="group" aria-label="Unit">
                                <button type="button" class="is-on" data-unit="ha">hectares</button>
                                <button type="button" data-unit="sqm">sq. meters</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="ak-lbl">Main crop</span>
                        <button type="button" class="ak-tag" id="akCrop"><span class="ic">🌱</span><span data-t>Choose a crop</span>
                            <svg class="chev" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                    <div class="full">
                        <span class="ak-lbl">Where is the farm?</span>
                        <div class="ak-two" id="akPlacePH">
                            <button type="button" class="ak-tag" id="akProv"><span class="ic">📍</span><span data-t>Province</span></button>
                            <button type="button" class="ak-tag" id="akTown"><span class="ic">🏘️</span><span data-t>Town</span></button>
                        </div>
                        <div id="akPlaceAbroad" hidden>
                            <select class="ak-input" id="akCountry">
                                @foreach ($countries as $code => $cname)
                                    @if ($code !== 'PH')<option value="{{ $code }}">{{ $cname }}</option>@endif
                                @endforeach
                            </select>
                            <div class="ak-two" style="margin-top:.5rem">
                                <input class="ak-input" id="akProvTxt" placeholder="State or province">
                                <input class="ak-input" id="akTownTxt" placeholder="Town or city">
                            </div>
                        </div>
                        <button type="button" class="ak-abroad" id="akAbroadBtn">Farm outside the Philippines?</button>
                    </div>
                </div>
                <div class="ak-err" id="akFarmErr" hidden></div>
                <button type="submit" class="btn btn-accent btn-lg ak-go" id="akFarmGo">Continue</button>
                <p class="ak-small">We send your answer and a few farming tips. You can unsubscribe anytime. See our <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" target="_blank">privacy policy</a>.<br>
                    Protected by reCAPTCHA. The Google <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms</a> apply.</p>
            </form>

            {{-- Step 2: the question, in one search bar. --}}
            <div class="ak-step" id="akAsk" hidden>
                <div class="ak-hello">
                    <p id="akHi">What would you like to ask?</p>
                    <div class="ak-farm" id="akFarmSum"></div>
                </div>
                <form class="ak-search" id="akSearch" autocomplete="off">
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                    <textarea id="akQ" rows="1" maxlength="700" placeholder="Ask Anee anything about your farm…" aria-label="Your question"></textarea>
                    <button type="submit" class="ak-ask" id="akAskBtn" disabled>
                        <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <span>Ask Anee</span>
                    </button>
                </form>
                {{-- What Anee says back sits right under the bar. --}}
                <div id="akSaid"></div>
                <div class="ak-chips" id="akChips">
                    <small>Try asking</small>
                    <button type="button">Why are my rice leaves turning yellow?</button>
                    <button type="button">Kailan dapat mag abono ng mais?</button>
                    <button type="button">How do I stop fall armyworm?</button>
                    <button type="button">Best fertilizer for eggplant?</button>
                </div>
                <p class="ak-note">One free question a week. Ask in Tagalog or English.</p>
            </div>

            {{-- The end: the answer is on its way. --}}
            <div class="ak-step ak-card ak-sent" id="akSent" hidden>
                <div class="ak-tick"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                <h2>Your answer is on its way!</h2>
                <p>Anee sent it to <b id="akSentTo"></b>. If you do not see it, look in Promotions or Spam.</p>
                <p class="ak-q" id="akSentQ"></p>
                <div class="ak-again">
                    <button type="button" class="ak-reset" id="akReset">
                        <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M4.6 15a8 8 0 101.9-8.3L4 9"/></svg>
                        Start over
                    </button>
                    <p id="akNext">One free question a week.</p>
                </div>
                <div class="ak-more">
                    <b>Want Anee on your farm every day?</b>
                    <p>Plan your whole season, see the growth stage and the weather for every lot, and ask Anee anytime, even with a photo of a sick plant.</p>
                    <div class="btns">
                        <a class="btn btn-accent" href="{{ route('signup') }}?utm_source=ask-anee&utm_medium=page">Try it for free</a>
                        <a class="ak-ghost" href="{{ url('/questions') }}">Read other answers</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ak-how-wrap">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14 sm:py-16">
        <div class="ak-how-head">
            <p class="ak-how-kick">How it works</p>
            <h2>Your answer in four easy steps</h2>
            <p>No account needed. It is free, and it takes about a minute.</p>
        </div>
        <div class="ak-how" id="akHow">
            <div class="ak-st" style="--i: 0">
                <span class="ak-ic"><em>1</em><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg></span>
                <div class="txt"><b>Tell Anee about your farm</b><p>Your name, email, farm size, crop and where the farm is.</p></div>
            </div>
            <div class="ak-st" style="--i: 1">
                <span class="ak-ic"><em>2</em><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg></span>
                <div class="txt"><b>Ask one question</b><p>Anything about your crops, in Tagalog or English.</p></div>
            </div>
            <div class="ak-st" style="--i: 2">
                <span class="ak-ic"><em>3</em><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span>
                <div class="txt"><b>Check your email</b><p>Anee sends you a link to your answer.</p></div>
            </div>
            <div class="ak-st is-gold" style="--i: 3">
                <span class="ak-ic"><em>4</em><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg></span>
                <div class="txt"><b>Read the full answer</b><p>What to do, when, and how much, with the sources Anee used.</p></div>
            </div>
        </div>
        <div class="ak-how-go">
            <a href="#akStage" class="btn btn-accent btn-lg" id="akHowGo">Ask Anee now</a>
        </div>
    </div>
</section>

<section class="anee-band">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
        <p class="text-sm font-bold uppercase tracking-wider text-accent-400">One question is only the start</p>
        <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Have Anee on your farm every day</h2>
        <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
            anee.io plans your whole season day by day, shows the growth stage and the weather for every lot,
            and lets you ask Anee anytime, even with a photo of a sick plant.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ route('signup') }}?utm_source=ask-anee&utm_medium=page" class="btn btn-accent btn-lg">Try it for free</a>
            <a href="{{ route('features') }}" class="btn btn-outline btn-lg !text-white !border-white/40 hover:!bg-white/10">See every feature</a>
        </div>
    </div>
</section>

{{-- The picker: crops, provinces and towns share one sheet. --}}
<div class="ak-sheet" id="akSheet" hidden role="dialog" aria-modal="true" aria-labelledby="akSheetTitle">
    <div class="ak-sheet-bg" data-close></div>
    <div class="ak-panel">
        <div class="ak-panel-h">
            <b id="akSheetTitle">Choose</b>
            <button type="button" class="ak-x" data-close aria-label="Close">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div class="ak-find"><input type="search" class="ak-input" id="akFind" placeholder="Search" autocomplete="off"></div>
        <div class="ak-list" id="akList"></div>
    </div>
</div>

{{-- Why this email cannot ask now (member, this week, this browser). Closing it starts the form over. --}}
<div class="ak-modal" id="akModal" hidden role="alertdialog" aria-modal="true" aria-labelledby="akModalT" aria-describedby="akModalP">
    <div class="ak-modal-bg" data-shut></div>
    <div class="ak-modal-card">
        <div class="ak-modal-face"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt=""><span id="akModalIc" aria-hidden="true">🗓️</span></div>
        <h3 id="akModalT"></h3>
        <p id="akModalP"></p>
        <div class="ak-when" id="akModalWhen" hidden>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
            <div><small>You can ask again on</small><b id="akModalDate"></b></div>
        </div>
        <div class="ak-modal-btns">
            <a class="btn btn-accent btn-lg" id="akModalGo" href="{{ route('signup') }}?utm_source=ask-anee&utm_medium=limit">Make a free account</a>
            <button type="button" class="ak-modal-ok" id="akModalOk" data-shut>OK, got it</button>
        </div>
        <p class="ak-modal-foot">The form will be cleared so you can start fresh.</p>
    </div>
</div>

@include('sm.partials.anee-wait')
@include('partials.anee-emoji')
@endsection

@push('scripts')
<script>
(() => {
    const SITE_KEY = @json($siteKey);
    const URLS = { farm: @json(route('ask.farm')), ask: @json(route('ask.question')), state: @json(url('/ask-anee/question')), send: @json(route('ask.send')) };
    const CROPS = @json($crops);
    const AVATAR = @json(asset('images/anee/avatar-160.jpg'));
    const LOGIN = @json(route('login'));
    const SIGNUP = @json(route('signup') . '?utm_source=ask-anee&utm_medium=limit');
    const $ = (id) => document.getElementById(id);
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const src = new URLSearchParams(location.search).get('utm_source') || '';
    const farmForm = $('akFarm');
    let token = null, email = '', busy = false;

    /* Google's word for each step, or '' when the script is blocked (the server decides). */
    const captcha = (action) => new Promise((resolve) => {
        const g = window.grecaptcha && window.grecaptcha.enterprise;
        if (!SITE_KEY || !g) return resolve('');
        const t = setTimeout(() => resolve(''), 6000);
        g.ready(() => g.execute(SITE_KEY, { action }).then((tk) => { clearTimeout(t); resolve(tk); }, () => { clearTimeout(t); resolve(''); }));
    });
    const post = async (url, body, action) => window.api(url, { method: 'POST', body: Object.assign({ captcha: await captcha(action), website: farmForm.website.value }, body) });

    /* One step on screen at a time, the steps above saying where we are. */
    const show = (id) => {
        ['akFarm', 'akAsk', 'akSent'].forEach((x) => { $(x).hidden = x !== id; });
        $('akS1').className = id === 'akFarm' ? 'is-on' : 'is-done';
        $('akS2').className = id === 'akAsk' ? 'is-on' : (id === 'akSent' ? 'is-done' : '');
        const top = $(id).getBoundingClientRect().top + scrollY - 110;
        if (scrollY > top) window.scrollTo({ top, behavior: reduce() ? 'auto' : 'smooth' });
    };

    /* ---- step 1: the farm ---- */
    const state = { unit: 'ha', crop: null, cropLabel: '', province: '', town: '' };
    let LOC = null;
    const locations = async () => {
        if (LOC) return LOC;
        try { LOC = await (await fetch(@json(asset('data/ph-locations.json')))).json(); } catch (_) { LOC = {}; }
        return LOC;
    };
    const setTag = (btn, text, set) => { btn.querySelector('[data-t]').textContent = text; btn.classList.toggle('is-set', !!set); btn.classList.remove('is-bad'); };
    farmForm.querySelectorAll('[data-unit]').forEach((b) => b.addEventListener('click', () => {
        state.unit = b.dataset.unit;
        farmForm.querySelectorAll('[data-unit]').forEach((x) => x.classList.toggle('is-on', x === b));
    }));
    farmForm.addEventListener('input', (e) => { if (e.target.classList) e.target.classList.remove('is-bad'); $('akFarmErr').hidden = true; });
    $('akCrop').addEventListener('click', () => pickCrop((key, label, icon) => {
        state.crop = key; state.cropLabel = label;
        $('akCrop').querySelector('.ic').textContent = icon || '🌱';
        setTag($('akCrop'), label, true);
    }));
    $('akProv').addEventListener('click', async () => {
        const loc = await locations();
        pickList('Province', Object.keys(loc), (p) => {
            if (p !== state.province) { state.town = ''; setTag($('akTown'), 'Town', false); }
            state.province = p; setTag($('akProv'), p, true);
            setTimeout(() => $('akTown').click(), 320);
        });
    });
    $('akTown').addEventListener('click', async () => {
        if (!state.province) { $('akProv').click(); return; }
        const loc = await locations();
        pickList('Town in ' + state.province, loc[state.province] || [], (t) => { state.town = t; setTag($('akTown'), t, true); }, true);
    });
    $('akAbroadBtn').addEventListener('click', () => {
        const abroad = $('akPlaceAbroad').hidden;
        $('akPlaceAbroad').hidden = !abroad;
        $('akPlacePH').hidden = abroad;
        $('akAbroadBtn').textContent = abroad ? 'The farm is in the Philippines' : 'Farm outside the Philippines?';
    });
    farmForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy) return;
        const err = $('akFarmErr'), go = $('akFarmGo');
        const abroad = !$('akPlaceAbroad').hidden;
        const name = $('akName').value.trim(), mail = $('akEmail').value.trim();
        const size = parseFloat($('akSize').value);
        const province = abroad ? $('akProvTxt').value.trim() : state.province;
        const town = abroad ? $('akTownTxt').value.trim() : state.town;
        const bad = [];
        const flag = (el, ok, what) => { el.classList.toggle('is-bad', !ok); if (!ok) bad.push(what); };
        flag($('akName'), name.length > 1, 'your name');
        flag($('akEmail'), /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail), 'a working email');
        flag($('akSize'), size > 0, 'the farm size');
        flag($('akCrop'), !!state.crop, 'the crop');
        flag(abroad ? $('akProvTxt') : $('akProv'), !!province, 'the province');
        flag(abroad ? $('akTownTxt') : $('akTown'), !!town, 'the town');
        if (bad.length) { err.textContent = 'Please add ' + bad.join(', ') + '.'; err.hidden = false; return; }
        err.hidden = true;
        busy = true; go.disabled = true; go.textContent = 'One moment…';
        try {
            const res = await post(URLS.farm, {
                token, name, email: mail, farmSize: size, farmUnit: state.unit,
                crop: state.crop, cropOther: state.crop === 'other' ? state.cropLabel : '',
                country: abroad ? $('akCountry').value : 'PH', province, town, src,
            }, 'ask_farm');
            token = res.data.token;
            email = mail;
            $('akHi').textContent = `Hi ${res.data.first}! What would you like to ask?`;
            const crop = state.cropLabel.split(' (')[0];
            const sizeWords = (+size.toFixed(2)) + ' ' + (state.unit === 'sqm' ? 'sq. meters' : (size === 1 ? 'hectare' : 'hectares'));
            $('akFarmSum').innerHTML = [sizeWords, crop, town + ', ' + province].map((x) => `<span>${esc(x)}</span>`).join('') + '<button type="button" id="akEdit">Edit</button>';
            $('akEdit').addEventListener('click', () => show('akFarm'));
            show('akAsk');
            if (window.matchMedia('(hover: hover)').matches) setTimeout(() => $('akQ').focus(), 380);
        } catch (ex) {
            if (ex.data && ex.data.refused) { refuse(ex.data); return; }
            err.textContent = ex.message || 'Something went wrong. Please try again.';
            err.hidden = false;
        } finally {
            busy = false; go.disabled = false; go.textContent = 'Continue';
        }
    });

    /* ---- step 2: the question ---- */
    const box = $('akQ'), ask = $('akAskBtn'), said = $('akSaid');
    const grow = () => { box.style.height = 'auto'; box.style.height = Math.min(box.scrollHeight, 152) + 'px'; ask.disabled = box.value.trim().length < 6 || busy; };
    box.addEventListener('input', grow);
    box.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('akSearch').requestSubmit(); } });
    $('akChips').addEventListener('click', (e) => { const b = e.target.closest('button'); if (!b) return; box.value = b.textContent; grow(); box.focus(); });
    const say = (html, no) => {
        const wait = html.includes('ak-dots');
        said.innerHTML = `<div class="ak-said ${no ? 'is-no' : ''} ${wait ? 'is-wait' : ''}"><img src="${AVATAR}" alt=""><div class="t">${html}</div></div>`;
        const r = said.firstElementChild.getBoundingClientRect();
        if (r.bottom > innerHeight - 20) said.firstElementChild.scrollIntoView({ block: 'center', behavior: reduce() ? 'auto' : 'smooth' });
    };
    $('akSearch').addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = box.value.trim();
        if (q.length < 6 || busy) return;
        busy = true; ask.disabled = true; ask.classList.add('is-busy');
        say('<span class="ak-dots"><i></i><i></i><i></i></span>');
        try {
            let d = (await post(URLS.ask, { token, question: q }, 'ask_question')).data;
            // She reads it off the request's clock; her answer is asked after.
            for (let i = 0; d.pending && i < 70; i++) {
                await new Promise((r) => setTimeout(r, 1500));
                d = (await window.api(URLS.state + '/' + d.token, { method: 'GET' })).data;
            }
            if (d.pending) throw new Error('Anee is taking a long time to read that. Please try again.');
            say(d.reply, !d.agri);
            if (d.agri) {
                await new Promise((r) => setTimeout(r, 1600));
                await prepare();
                const sent = (await post(URLS.send, { token }, 'ask_send')).data || {};
                $('akSentTo').textContent = email;
                $('akSentQ').textContent = '“' + q + '”';
                $('akNext').textContent = 'One free question a week.' + (sent.next ? ' Your next one opens on ' + when(sent.next) + '.' : '');
                show('akSent');
            }
        } catch (ex) {
            if (ex.data && ex.data.refused) { said.innerHTML = ''; refuse(ex.data); return; }
            if (ex.data && ex.data.restart) { show('akFarm'); }
            say(esc(ex.message || 'Something went wrong. Please try again.'), true);
        } finally {
            busy = false; ask.classList.remove('is-busy'); grow();
        }
    });

    /* The wait: the answer itself is written when the emailed link is opened. */
    async function prepare() {
        const w = window.aneeWait;
        if (!w) return;
        const crop = state.cropLabel.split(' (')[0] || 'your crop';
        const place = state.province || 'your area';
        w.show({
            title: 'Anee is analyzing your question…',
            sub: 'This takes a few seconds.',
            stay: 'Please stay on this screen for a moment.',
            lines: [
                'Reading your question closely…',
                `Looking at how ${crop} grows, stage by stage…`,
                `Checking the weather pattern in ${place}…`,
                'Weighing the size of your farm…',
                'Matching it with the official recommendations…',
                'Putting your answer together…',
            ],
        });
        const PH = {
            start: { label: 'Reading your question', lo: 3, hi: 25, tau: 3 },
            farm: { label: 'Looking at your farm', lo: 25, hi: 60, tau: 4 },
            write: { label: 'Preparing your answer', lo: 60, hi: 95, tau: 4 },
        };
        const total = 7000 + Math.random() * 7000;
        w.progress({ phases: PH, phase: 'start' });
        await new Promise((r) => setTimeout(r, total * 0.3));
        w.progress({ phase: 'farm' });
        await new Promise((r) => setTimeout(r, total * 0.35));
        w.progress({ phase: 'write' });
        await new Promise((r) => setTimeout(r, total * 0.35));
        await w.done({ title: 'Your answer is ready!', line: 'Sending it to your email now.' });
    }

    /* A date the visitor reads in their own time: "Thursday, October 8 at 3:40 PM". */
    function when(iso, fallback) {
        const d = new Date(iso);
        if (isNaN(d)) return fallback || '';
        return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
            + ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    /* ---- start over: every field empty again, the first step on screen ---- */
    function resetAll() {
        farmForm.reset();
        Object.assign(state, { unit: 'ha', crop: null, cropLabel: '', province: '', town: '' });
        farmForm.querySelectorAll('[data-unit]').forEach((x) => x.classList.toggle('is-on', x.dataset.unit === 'ha'));
        $('akCrop').querySelector('.ic').textContent = '🌱';
        setTag($('akCrop'), 'Choose a crop', false);
        setTag($('akProv'), 'Province', false);
        setTag($('akTown'), 'Town', false);
        $('akPlaceAbroad').hidden = true;
        $('akPlacePH').hidden = false;
        $('akAbroadBtn').textContent = 'Farm outside the Philippines?';
        farmForm.querySelectorAll('.is-bad').forEach((x) => x.classList.remove('is-bad'));
        $('akFarmErr').hidden = true;
        token = null; email = '';
        box.value = ''; said.innerHTML = ''; grow();
        $('akSentTo').textContent = ''; $('akSentQ').textContent = '';
        show('akFarm');
    }
    const focusName = () => { if (window.matchMedia('(hover: hover)').matches) $('akName').focus({ preventScroll: true }); };
    $('akReset').addEventListener('click', () => { resetAll(); setTimeout(focusName, 380); });

    /* ---- the refusal: a member, this email's week, or this browser's week ----
       Said in a modal; closing it, by any way out, starts the form over. */
    const modal = $('akModal');
    const WHY = {
        member: {
            ic: '👋', t: 'You are already a member',
            p: (m) => `<b>${esc(m)}</b> already has an anee.io account. Log in and ask Anee inside the app.`,
            go: ['Log in to anee.io', LOGIN],
        },
        week: {
            ic: '🗓️', t: 'You already asked Anee this week',
            p: (m) => `Each email gets one free question a week, and <b>${esc(m)}</b> has used this week's. Want Anee every day? Start with a free anee.io account.`,
            go: ['Make a free account', SIGNUP],
        },
        browser: {
            ic: '🗓️', t: 'This browser already asked this week',
            p: () => 'One free question a week can be sent from each browser, and this one has used it. Want Anee every day? Start with a free anee.io account.',
            go: ['Make a free account', SIGNUP],
        },
    };
    let shutTimer = null;
    function refuse(d) {
        const w = WHY[d.refused] || WHY.week;
        $('akModalIc').textContent = w.ic;
        $('akModalT').textContent = w.t;
        $('akModalP').innerHTML = w.p(d.email || email);
        $('akModalWhen').hidden = !d.until;
        if (d.until) $('akModalDate').textContent = when(d.until, d.untilText);
        $('akModalGo').textContent = w.go[0];
        $('akModalGo').href = w.go[1];
        clearTimeout(shutTimer);
        modal.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => modal.classList.add('is-on')));
        setTimeout(() => $('akModalOk').focus({ preventScroll: true }), 80);
    }
    function shut() {
        if (modal.hidden || !modal.classList.contains('is-on')) return;
        modal.classList.remove('is-on');
        document.documentElement.style.overflow = '';
        resetAll();
        shutTimer = setTimeout(() => { modal.hidden = true; focusName(); }, reduce() ? 0 : 330);
    }
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-shut]')) shut(); });
    // Leaving for the login or signup page: the form is left empty behind it.
    $('akModalGo').addEventListener('click', () => resetAll());
    document.addEventListener('keydown', (e) => {
        if (modal.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); shut(); return; }
        if (e.key === 'Tab') {
            // Focus stays inside the card while it is open.
            const go = $('akModalGo'), ok = $('akModalOk');
            if (e.shiftKey && document.activeElement === go) { e.preventDefault(); ok.focus(); }
            else if (!e.shiftKey && document.activeElement === ok) { e.preventDefault(); go.focus(); }
            else if (document.activeElement !== go && document.activeElement !== ok) { e.preventDefault(); ok.focus(); }
        }
    });

    /* ---- how it works: the steps rise in turn when they come into view ---- */
    const how = $('akHow');
    if (how && 'IntersectionObserver' in window && !reduce()) {
        const io = new IntersectionObserver((es) => { if (es.some((x) => x.isIntersecting)) { how.classList.add('is-in'); io.disconnect(); } }, { threshold: .25 });
        io.observe(how);
    } else if (how) {
        how.classList.add('is-in');
    }
    $('akHowGo').addEventListener('click', (e) => {
        e.preventDefault();
        const top = $('akStage').getBoundingClientRect().top + scrollY - 120;
        window.scrollTo({ top, behavior: reduce() ? 'auto' : 'smooth' });
        const first = !$('akAsk').hidden ? $('akQ') : (!$('akFarm').hidden ? $('akName') : null);
        if (first && window.matchMedia('(hover: hover)').matches) setTimeout(() => first.focus({ preventScroll: true }), 450);
    });

    /* ---- the sheet ---- */
    const sheet = $('akSheet'), list = $('akList'), find = $('akFind');
    let onFind = null;
    const openSheet = (title) => {
        $('akSheetTitle').textContent = title;
        find.value = '';
        sheet.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(() => sheet.classList.add('is-on'));
        if (window.matchMedia('(hover: hover)').matches) setTimeout(() => find.focus(), 200);
    };
    const closeSheet = () => {
        sheet.classList.remove('is-on');
        document.documentElement.style.overflow = '';
        setTimeout(() => { sheet.hidden = true; }, reduce() ? 0 : 290);
    };
    sheet.addEventListener('click', (e) => { if (e.target.closest('[data-close]')) closeSheet(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !sheet.hidden) closeSheet(); });
    find.addEventListener('input', () => onFind && onFind(find.value.trim().toLowerCase()));
    const CROPS_FLAT = {};
    Object.values(CROPS).forEach((items) => Object.entries(items).forEach(([k, c]) => { CROPS_FLAT[k] = c.label; }));

    function pickCrop(done) {
        openSheet('Choose a crop');
        onFind = (q) => {
            let html = '';
            for (const [group, items] of Object.entries(CROPS)) {
                const hits = Object.entries(items).filter(([, c]) => !q || c.label.toLowerCase().includes(q));
                if (!hits.length) continue;
                html += `<p class="ak-grp">${esc(group)}</p><div class="ak-opts">`
                    + hits.map(([k, c]) => `<button type="button" class="ak-opt ${state.crop === k ? 'is-on' : ''}" data-k="${esc(k)}" data-i="${esc(c.icon)}">${esc(c.icon)} ${esc(c.label)}</button>`).join('')
                    + '</div>';
            }
            html += q ? `<p class="ak-grp">Not in the list</p><div class="ak-opts"><button type="button" class="ak-opt" data-k="other" data-l="${esc(find.value.trim())}">🌱 Use “${esc(find.value.trim())}”</button></div>` : '';
            list.innerHTML = html || '<p class="ak-none">No crop by that name.</p>';
        };
        onFind('');
        list.onclick = (e) => {
            const b = e.target.closest('[data-k]');
            if (!b) return;
            const key = b.dataset.k;
            const label = key === 'other' ? b.dataset.l : CROPS_FLAT[key];
            if (!label) return;
            done(key, label, b.dataset.i);
            closeSheet();
        };
    }

    function pickList(title, items, done, allowOwn) {
        openSheet(title);
        onFind = (q) => {
            const hits = items.filter((x) => !q || x.toLowerCase().includes(q)).slice(0, 400);
            let html = hits.map((x) => `<button type="button" class="ak-row-opt" data-v="${esc(x)}">${esc(x)}</button>`).join('');
            if (allowOwn && q) html += `<button type="button" class="ak-row-opt" data-v="${esc(find.value.trim())}"><b>Use “${esc(find.value.trim())}”</b></button>`;
            list.innerHTML = html || '<p class="ak-none">Nothing by that name.</p>';
        };
        onFind('');
        list.onclick = (e) => {
            const b = e.target.closest('[data-v]');
            if (!b) return;
            done(b.dataset.v);
            closeSheet();
        };
    }
})();
</script>
@endpush
