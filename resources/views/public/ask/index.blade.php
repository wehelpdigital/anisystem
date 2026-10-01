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
    .ak-dots { display: inline-flex; gap: .25rem; padding: .5rem .1rem; }
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

    .ak-how { display: grid; gap: 1rem; }
    @media (min-width: 768px) { .ak-how { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ak-how > div { padding: 1.2rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e5ecdc; }
    .ak-how span { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .8rem; background: #eef6e5; color: #3d6823; font-weight: 900; }
    .ak-how b { display: block; margin-top: .7rem; font-family: var(--font-heading); color: #14210c; }
    .ak-how p { margin-top: .3rem; font-size: .9rem; color: #6b7280; line-height: 1.5; }
    @media (prefers-reduced-motion: reduce) {
        .ak-step, .ak-said, .ak-tick { animation: none; }
        .ak-face::after, .ak-dots i, .ak-ask.is-busy svg { animation: none; }
        .ak-panel, .ak-sheet-bg { transition: none; }
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

        <div class="ak-stage">
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
                <p class="ak-note">One free question. Ask in Tagalog or English.</p>
            </div>

            {{-- The end: the answer is on its way. --}}
            <div class="ak-step ak-card ak-sent" id="akSent" hidden>
                <div class="ak-tick"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                <h2>Your answer is on its way!</h2>
                <p>Anee sent it to <b id="akSentTo"></b>. If you do not see it, look in Promotions or Spam.</p>
                <p class="ak-q" id="akSentQ"></p>
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

<section class="bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-14">
        <h2 class="font-heading text-2xl sm:text-3xl font-bold text-ink">How it works</h2>
        <div class="ak-how mt-6">
            <div><span>1</span><b>Tell Anee about your farm</b><p>Your name, email, farm size, crop and place.</p></div>
            <div><span>2</span><b>Ask one question</b><p>Anything about your crops, in Tagalog or English.</p></div>
            <div><span>3</span><b>Get it by email</b><p>Anee sends a link to your full answer.</p></div>
            <div><span>4</span><b>Read the full answer</b><p>What to do, when, and how much, with sources.</p></div>
        </div>
    </div>
</section>

@if ($recent->count())
<section class="bg-[#f9fbf6] border-t border-[#e4efd4]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
            <h2 class="font-heading text-2xl font-bold text-ink">Questions farmers asked Anee</h2>
            <a href="{{ url('/questions') }}" class="text-sm font-extrabold text-brand-700 hover:text-brand-800">See all questions ›</a>
        </div>
        <div class="sp-grid">
            @foreach ($recent as $p)
                @include('public.site.tile', ['p' => $p])
            @endforeach
        </div>
    </div>
</section>
@endif

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
        said.innerHTML = `<div class="ak-said ${no ? 'is-no' : ''}"><img src="${AVATAR}" alt=""><div class="t">${html}</div></div>`;
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
            if (d.pending) throw new Error('Anee is taking long to read that. Please try again.');
            say(d.reply, !d.agri);
            if (d.agri) {
                await new Promise((r) => setTimeout(r, 1600));
                await prepare();
                await post(URLS.send, { token }, 'ask_send');
                $('akSentTo').textContent = email;
                $('akSentQ').textContent = '“' + q + '”';
                show('akSent');
            }
        } catch (ex) {
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
