@extends('layouts.public')

{{-- Try and Ask Anee (AskAneeController): one free farming question, a few
     farm details, a short wait, an email, and the answer arrives as a page
     of its own. The whole exchange happens in one chat card. --}}
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
        linear-gradient(160deg, #f3f8ec 0%, #eef6e5 55%, #ffffff 100%); border-bottom: 1px solid #e3eed6; }
    .ak-wrap { display: grid; gap: 2rem; align-items: start; }
    @media (min-width: 1024px) { .ak-wrap { grid-template-columns: minmax(0, 1fr) minmax(0, 34rem); gap: 3.5rem; align-items: center; } }
    .ak-kicker { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .75rem; border-radius: 999px; background: #fff;
        border: 1px solid #d7e8c3; font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #3d6823; }
    .ak-kicker i { width: .5rem; height: .5rem; border-radius: 999px; background: #6b9f3d; box-shadow: 0 0 0 0 rgb(107 159 61 / .5); animation: akPulse 2s ease-in-out infinite; }
    @keyframes akPulse { 50% { box-shadow: 0 0 0 6px rgb(107 159 61 / 0); } }
    .ak-h1 { margin-top: 1rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; line-height: 1.05;
        font-size: clamp(2.2rem, 6vw, 3.6rem); letter-spacing: -.02em; }
    .ak-h1 span { color: #4a7c2a; }
    .ak-lead { margin-top: 1rem; font-size: 1.08rem; line-height: 1.65; color: #4b5563; max-width: 36rem; }
    .ak-points { margin-top: 1.4rem; display: grid; gap: .6rem; }
    .ak-points li { display: flex; gap: .6rem; align-items: flex-start; color: #374151; font-size: .96rem; }
    .ak-points svg { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .1rem; color: #4a7c2a; }

    /* ---- the chat card ---- */
    .ak-card { position: relative; border-radius: 1.6rem; background: #fff; border: 1px solid #e2ecd6;
        box-shadow: 0 30px 70px -40px rgb(20 40 10 / .45), 0 2px 0 rgb(255 255 255) inset; overflow: hidden; }
    .ak-head { display: flex; align-items: center; gap: .75rem; padding: .95rem 1.1rem; color: #fff;
        background: linear-gradient(135deg, #3d6823, #24400f 80%); }
    .ak-head img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .ak-head b { display: block; font-family: var(--font-heading); font-size: 1.02rem; }
    .ak-head small { display: flex; align-items: center; gap: .35rem; font-size: .75rem; color: #cfe3b8; }
    .ak-head small::before { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: #9be15d; }
    .ak-free { margin-left: auto; font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase;
        background: #f5c518; color: #3b2f00; padding: .3rem .55rem; border-radius: 999px; }
    .ak-thread { display: flex; flex-direction: column; gap: .8rem; padding: 1.1rem; min-height: 14rem;
        background: linear-gradient(180deg, #fbfdf8, #f6faf1); scroll-behavior: smooth; }
    /* A phone scrolls the page, never a box inside it; a wide screen keeps the chat in its card. */
    @media (min-width: 1024px) { .ak-thread { max-height: min(34rem, 62vh); overflow-y: auto; overscroll-behavior: contain; } }
    .ak-row { display: flex; gap: .55rem; align-items: flex-end; animation: akIn .32s cubic-bezier(.22,1,.36,1) both; }
    .ak-row.is-me { justify-content: flex-end; }
    @keyframes akIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .ak-row > img { flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; object-fit: cover; }
    .ak-b { max-width: 88%; padding: .7rem .9rem; border-radius: 1.1rem 1.1rem 1.1rem .35rem; background: #fff; border: 1px solid #e3ecd8;
        color: #1f2a17; font-size: .95rem; line-height: 1.55; box-shadow: 0 6px 18px -14px rgb(20 40 10 / .35); }
    .ak-b p + p { margin-top: .45rem; }
    .ak-row.is-me .ak-b { border-radius: 1.1rem 1.1rem .35rem 1.1rem; background: #3d6823; border-color: #3d6823; color: #fff; }
    .ak-b .anee-emo img { width: 1.35em; height: 1.35em; vertical-align: -.3em; }
    .ak-dots { display: inline-flex; gap: .25rem; padding: .3rem .1rem; }
    .ak-dots i { width: .45rem; height: .45rem; border-radius: 999px; background: #8fb86a; animation: akDot 1.1s ease-in-out infinite; }
    .ak-dots i:nth-child(2) { animation-delay: .15s; } .ak-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes akDot { 0%, 80%, 100% { opacity: .35; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }

    /* A form inside the thread: the farm, then the email. */
    .ak-form { width: 100%; max-width: 100%; padding: 1rem; border-radius: 1.1rem; background: #fff; border: 1px solid #dbe8cc;
        box-shadow: 0 10px 26px -20px rgb(20 40 10 / .5); animation: akIn .32s cubic-bezier(.22,1,.36,1) both; }
    .ak-form h4 { font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: 1rem; }
    .ak-form .ak-why { margin-top: .2rem; font-size: .8rem; color: #6b7280; }
    .ak-field { margin-top: .85rem; }
    .ak-field > label, .ak-lbl { display: block; font-size: .78rem; font-weight: 800; color: #374151; margin-bottom: .35rem; }
    .ak-size { display: flex; gap: .5rem; }
    .ak-size input { flex: 1; min-width: 0; }
    .ak-seg { display: inline-flex; padding: .2rem; border-radius: .8rem; background: #eef4e6; flex: none; }
    .ak-seg button { padding: .45rem .7rem; border-radius: .6rem; font-size: .8rem; font-weight: 800; color: #4b5563;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ak-seg button.is-on { background: #fff; color: #2f5219; box-shadow: 0 1px 3px rgb(0 0 0 / .08); }
    .ak-input { width: 100%; border: 1px solid #d6dfcb; border-radius: .8rem; padding: .65rem .8rem; font-size: 1rem; color: #14210c; background: #fff;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ak-input:focus { outline: none; border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .15); }
    .ak-input.is-bad { border-color: #dc2626; box-shadow: 0 0 0 4px rgb(220 38 38 / .1); }
    .ak-tag { display: flex; align-items: center; gap: .55rem; width: 100%; padding: .6rem .75rem; border-radius: .8rem; text-align: left;
        border: 1.5px dashed #b9d39b; background: #f7fbf1; color: #3d6823; font-weight: 700; font-size: .95rem;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ak-tag:hover { background: #eef6e5; }
    .ak-tag.is-set { border-style: solid; border-color: #6b9f3d; background: #eef6e5; color: #1f3a0f; }
    .ak-tag.is-bad { border-color: #dc2626; }
    .ak-tag .ic { font-size: 1.1rem; }
    .ak-tag .chev { margin-left: auto; width: 1rem; height: 1rem; color: #7ea35a; }
    .ak-two { display: grid; gap: .5rem; grid-template-columns: 1fr 1fr; }
    @media (max-width: 380px) { .ak-two { grid-template-columns: 1fr; } }
    .ak-err { margin-top: .5rem; font-size: .8rem; font-weight: 700; color: #b91c1c; }
    .ak-go { margin-top: 1rem; width: 100%; justify-content: center; }
    .ak-abroad { margin-top: .45rem; font-size: .76rem; font-weight: 700; color: #4a7c2a; text-decoration: underline; }
    .ak-small { margin-top: .6rem; font-size: .72rem; color: #9ca3af; line-height: 1.45; }
    .ak-small a { text-decoration: underline; }

    /* The composer. */
    .ak-compose { padding: .8rem .9rem .9rem; border-top: 1px solid #edf2e6; background: #fff; }
    .ak-box { display: flex; align-items: flex-end; gap: .5rem; padding: .35rem .35rem .35rem .8rem; border-radius: 1.1rem;
        border: 1.5px solid #d6e4c6; background: #fbfdf8; transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ak-box:focus-within { border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .14); }
    .ak-box textarea { flex: 1; min-width: 0; resize: none; border: 0; background: transparent; padding: .5rem 0; font-size: 1rem;
        line-height: 1.45; max-height: 9rem; color: #14210c; }
    .ak-box textarea:focus { outline: none; }
    .ak-send { flex: none; width: 2.75rem; height: 2.75rem; border-radius: .85rem; display: grid; place-items: center; color: #3b2f00; background: #f5c518;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .ak-send:hover { transform: translateY(-1px); }
    .ak-send:disabled { opacity: .45; transform: none; }
    .ak-send svg { width: 1.2rem; height: 1.2rem; }
    .ak-chips { display: flex; gap: .4rem; overflow-x: auto; padding: .6rem 0 .1rem; scrollbar-width: none; }
    .ak-chips::-webkit-scrollbar { display: none; }
    .ak-chips button { flex: none; padding: .4rem .7rem; border-radius: 999px; border: 1px solid #dbe8cc; background: #fff; font-size: .8rem; font-weight: 700; color: #3d6823;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ak-chips button:hover { background: #eef6e5; }
    .ak-compose.is-off { display: none; }
    .ak-done { padding: 1rem; border-radius: 1.1rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 80%); animation: akIn .32s cubic-bezier(.22,1,.36,1) both; }
    .ak-done b { display: block; font-family: var(--font-heading); font-size: 1.1rem; color: #fff; }
    .ak-done p { margin-top: .35rem; font-size: .9rem; color: #d7e6c8; }
    .ak-done .btns { margin-top: .9rem; display: flex; flex-wrap: wrap; gap: .5rem; }
    .grecaptcha-badge { visibility: hidden !important; }
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

    .ak-steps { display: grid; gap: 1rem; }
    @media (min-width: 768px) { .ak-steps { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ak-step { padding: 1.2rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e5ecdc; }
    .ak-step span { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .8rem; background: #eef6e5; color: #3d6823; font-weight: 900; }
    .ak-step b { display: block; margin-top: .7rem; font-family: var(--font-heading); color: #14210c; }
    .ak-step p { margin-top: .3rem; font-size: .9rem; color: #6b7280; line-height: 1.5; }
    @media (prefers-reduced-motion: reduce) {
        .ak-row, .ak-form, .ak-done { animation: none; }
        .ak-kicker i, .ak-dots i { animation: none; }
        .ak-panel, .ak-sheet-bg { transition: none; }
    }
</style>

<section class="ak-hero">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14 lg:py-16">
        <div class="ak-wrap">
            <div>
                <span class="ak-kicker"><i></i>Free for everyone</span>
                <h1 class="ak-h1">Try and <span>Ask Anee</span></h1>
                <p class="ak-lead">Ask Anee, the anee.io AI farm technician, one farming question. Tell her about your farm and she prepares a full answer for you, sent to your email.</p>
                <ul class="ak-points">
                    <li><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Palay, mais, gulay, fruit trees and more</li>
                    <li><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Pests, diseases, fertilizer, planting and harvest</li>
                    <li><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Ask in Tagalog or English</li>
                </ul>
            </div>

            <div class="ak-card" id="akCard">
                <div class="ak-head">
                    <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee, the anee.io AI farm technician">
                    <div>
                        <b>Anee</b>
                        <small>AI farm technician</small>
                    </div>
                    <span class="ak-free">1 free question</span>
                </div>
                <div class="ak-thread" id="akThread" aria-live="polite">
                    <div class="ak-row">
                        <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">
                        <div class="ak-b">{!! \App\Support\AneeEmoji::render('<p>Kumusta! I am Anee, your farm technician. Ask me one farming question: a pest, a sick plant, fertilizer, when to plant, anything about your crops. :anee-wave:</p>') !!}</div>
                    </div>
                </div>
                <form class="ak-compose" id="akCompose" autocomplete="off">
                    <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <div class="ak-box">
                        <textarea id="akQ" rows="1" maxlength="700" placeholder="Type your farming question…" aria-label="Your question"></textarea>
                        <button type="submit" class="ak-send" id="akSend" aria-label="Ask Anee" disabled>
                            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                    </div>
                    <div class="ak-chips" id="akChips">
                        <button type="button">Why are my rice leaves turning yellow?</button>
                        <button type="button">Kailan dapat mag abono ng mais?</button>
                        <button type="button">How do I stop fall armyworm?</button>
                        <button type="button">Best fertilizer for eggplant?</button>
                    </div>
                    <p class="ak-small">Protected by reCAPTCHA. The Google <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms</a> apply.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-14">
        <h2 class="font-heading text-2xl sm:text-3xl font-bold text-ink">How it works</h2>
        <div class="ak-steps mt-6">
            <div class="ak-step"><span>1</span><b>Ask one question</b><p>Anything about your crops, in Tagalog or English.</p></div>
            <div class="ak-step"><span>2</span><b>Tell Anee about your farm</b><p>The size, the crop and the place. The answer fits your farm.</p></div>
            <div class="ak-step"><span>3</span><b>Get it by email</b><p>Anee sends a link to your full answer.</p></div>
            <div class="ak-step"><span>4</span><b>Read the full answer</b><p>What to do, when, and how much, with sources.</p></div>
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
    const URLS = { ask: @json(route('ask.question')), state: @json(url('/ask-anee/question')), details: @json(route('ask.details')), email: @json(route('ask.email')) };
    const CROPS = @json($crops);
    const AVATAR = @json(asset('images/anee/avatar-160.jpg'));
    const FACE = (n) => @json(asset('images/anee/emoji')) + '/' + n + '.png';
    const $ = (id) => document.getElementById(id);
    const thread = $('akThread'), compose = $('akCompose'), box = $('akQ'), send = $('akSend');
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const src = new URLSearchParams(location.search).get('utm_source') || '';
    let token = null, busy = false;

    /* Google's word for each step, or '' when the script is blocked (the server decides). */
    const captcha = (action) => new Promise((resolve) => {
        const g = window.grecaptcha && window.grecaptcha.enterprise;
        if (!SITE_KEY || !g) return resolve('');
        const t = setTimeout(() => resolve(''), 6000);
        g.ready(() => g.execute(SITE_KEY, { action }).then((tk) => { clearTimeout(t); resolve(tk); }, () => { clearTimeout(t); resolve(''); }));
    });
    const post = async (url, body, action) => window.api(url, { method: 'POST', body: Object.assign({ captcha: await captcha(action), website: compose.website.value }, body) });

    const wide = () => window.matchMedia('(min-width: 1024px)').matches;
    /* The newest thing in view: inside the card on a wide screen, on the page on a phone. */
    const down = () => requestAnimationFrame(() => {
        if (wide()) { thread.scrollTop = thread.scrollHeight; return; }
        const last = thread.lastElementChild;
        if (last) last.scrollIntoView({ block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' });
    });
    const row = (me, html) => {
        const r = document.createElement('div');
        r.className = 'ak-row' + (me ? ' is-me' : '');
        r.innerHTML = (me ? '' : `<img src="${AVATAR}" alt="">`) + `<div class="ak-b">${html}</div>`;
        thread.appendChild(r);
        down();
        return r;
    };
    const typing = () => row(false, '<span class="ak-dots"><i></i><i></i><i></i></span>');
    const face = (n) => `<span class="anee-emo"><img src="${FACE(n)}" alt=""></span>`;
    const block = (el) => { thread.appendChild(el); down(); return el; };

    /* ---- the composer ---- */
    const grow = () => { box.style.height = 'auto'; box.style.height = Math.min(box.scrollHeight, 144) + 'px'; send.disabled = box.value.trim().length < 6 || busy; };
    box.addEventListener('input', grow);
    box.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(hover: hover)').matches) { e.preventDefault(); compose.requestSubmit(); } });
    $('akChips').addEventListener('click', (e) => { const b = e.target.closest('button'); if (!b) return; box.value = b.textContent; grow(); box.focus(); });

    compose.addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = box.value.trim();
        if (q.length < 6 || busy) return;
        busy = true; send.disabled = true;
        row(true, esc(q).replace(/\n/g, '<br>'));
        box.value = ''; grow();
        const t = typing();
        try {
            let d = (await post(URLS.ask, { question: q, src }, 'ask_question')).data;
            // She reads it off the request's clock; her answer is asked after.
            for (let i = 0; d.pending && i < 70; i++) {
                await new Promise((r) => setTimeout(r, 1500));
                d = (await window.api(URLS.state + '/' + d.token, { method: 'GET' })).data;
            }
            if (d.pending) throw new Error('Anee is taking long to read that. Please try again.');
            t.querySelector('.ak-b').innerHTML = d.reply;
            if (d.agri) {
                token = d.token;
                compose.classList.add('is-off');
                farmForm(d.crop, d.cropLabel);
            }
        } catch (err) {
            t.querySelector('.ak-b').innerHTML = `<p>${face('oops')} ${esc(err.message || 'Something went wrong. Please try again.')}</p>`;
        } finally {
            busy = false; grow(); down();
        }
    });

    /* ---- step 2: the farm ---- */
    const state = { unit: 'ha', crop: null, cropLabel: '', country: 'PH', province: '', town: '' };
    let LOC = null;
    const locations = async () => {
        if (LOC) return LOC;
        try { LOC = await (await fetch(@json(asset('data/ph-locations.json')))).json(); } catch (_) { LOC = {}; }
        return LOC;
    };
    function farmForm(crop, cropLabel) {
        if (crop) { state.crop = crop; state.cropLabel = cropLabel; }
        const f = document.createElement('div');
        f.className = 'ak-form';
        f.innerHTML = `
            <h4>Tell me about your farm</h4>
            <p class="ak-why">I need these to analyze your answer properly.</p>
            <div class="ak-field">
                <label for="akSize">Farm size</label>
                <div class="ak-size">
                    <input type="number" inputmode="decimal" step="any" min="0" class="ak-input" id="akSize" placeholder="e.g. 1.5">
                    <div class="ak-seg" role="group" aria-label="Unit">
                        <button type="button" class="is-on" data-unit="ha">hectares</button>
                        <button type="button" data-unit="sqm">sq. meters</button>
                    </div>
                </div>
            </div>
            <div class="ak-field">
                <span class="ak-lbl">Crop</span>
                <button type="button" class="ak-tag" id="akCrop"><span class="ic">🌱</span><span data-t>Choose a crop</span>
                    <svg class="chev" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
            </div>
            <div class="ak-field">
                <span class="ak-lbl">Where is the farm?</span>
                <div class="ak-two" id="akPlacePH">
                    <button type="button" class="ak-tag" id="akProv"><span class="ic">📍</span><span data-t>Province</span></button>
                    <button type="button" class="ak-tag" id="akTown"><span class="ic">🏘️</span><span data-t>Town</span></button>
                </div>
                <div id="akPlaceAbroad" hidden>
                    <select class="ak-input" id="akCountry">${@json($countries) ? Object.entries(@json($countries)).map(([k, v]) => `<option value="${esc(k)}">${esc(v)}</option>`).join('') : ''}</select>
                    <div class="ak-two" style="margin-top:.5rem">
                        <input class="ak-input" id="akProvTxt" placeholder="State or province">
                        <input class="ak-input" id="akTownTxt" placeholder="Town or city">
                    </div>
                </div>
                <button type="button" class="ak-abroad" id="akAbroadBtn">Farm outside the Philippines?</button>
            </div>
            <p class="ak-err" id="akFarmErr" hidden></p>
            <button type="button" class="btn btn-accent ak-go" id="akFarmGo">Continue</button>`;
        block(f);
        const setTag = (btn, text, set) => { btn.querySelector('[data-t]').textContent = text; btn.classList.toggle('is-set', !!set); btn.classList.remove('is-bad'); };
        if (state.crop) setTag($('akCrop'), state.cropLabel, true);
        f.querySelectorAll('[data-unit]').forEach((b) => b.addEventListener('click', () => {
            state.unit = b.dataset.unit;
            f.querySelectorAll('[data-unit]').forEach((x) => x.classList.toggle('is-on', x === b));
        }));
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
            if (abroad && $('akCountry').value === 'PH') { const o = [...$('akCountry').options].find((x) => x.value !== 'PH'); if (o) $('akCountry').value = o.value; }
        });
        $('akFarmGo').addEventListener('click', () => submitFarm(f));
        down();
    }

    async function submitFarm(f) {
        const err = $('akFarmErr');
        const abroad = !$('akPlaceAbroad').hidden;
        const size = parseFloat($('akSize').value);
        const province = abroad ? $('akProvTxt').value.trim() : state.province;
        const town = abroad ? $('akTownTxt').value.trim() : state.town;
        const bad = [];
        $('akSize').classList.toggle('is-bad', !(size > 0)); if (!(size > 0)) bad.push('the farm size');
        $('akCrop').classList.toggle('is-bad', !state.crop); if (!state.crop) bad.push('the crop');
        if (!province) { bad.push('the province'); (abroad ? $('akProvTxt') : $('akProv')).classList.add('is-bad'); }
        if (!town) { bad.push('the town'); (abroad ? $('akTownTxt') : $('akTown')).classList.add('is-bad'); }
        if (bad.length) { err.textContent = 'Please add ' + bad.join(', ') + '.'; err.hidden = false; return; }
        err.hidden = true;
        const go = $('akFarmGo');
        go.disabled = true; go.textContent = 'One moment…';
        try {
            const res = await post(URLS.details, {
                token, farmSize: size, farmUnit: state.unit,
                crop: state.crop === 'other' ? 'other' : state.crop, cropOther: state.crop === 'other' ? state.cropLabel : '',
                country: abroad ? $('akCountry').value : 'PH', province, town,
            }, 'ask_details');
            f.querySelectorAll('input, select, button').forEach((x) => { x.disabled = true; });
            go.textContent = 'Sent to Anee';
            await prepare(res.data);
            emailForm();
        } catch (e) {
            err.textContent = e.message || 'Something went wrong. Please try again.';
            err.hidden = false;
            go.disabled = false; go.textContent = 'Continue';
        }
    }

    /* ---- step 3: the wait. The answer is written when the emailed link is opened. ---- */
    async function prepare(d) {
        const w = window.aneeWait;
        if (!w) return;
        const crop = d.crop || 'your crop';
        const place = d.place || 'your area';
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
        await w.done({ title: 'Your answer is ready!', line: 'Tell me where to send it.' });
    }

    /* ---- step 4: the email ---- */
    function emailForm() {
        row(false, `<p>Your answer is ready! ${face('cheer')} Where should I send it?</p>`);
        const f = document.createElement('form');
        f.className = 'ak-form';
        f.noValidate = true;
        f.innerHTML = `
            <h4>Send my answer to</h4>
            <div class="ak-field">
                <input type="email" inputmode="email" autocomplete="email" class="ak-input" id="akEmail" placeholder="you@example.com" aria-label="Your email">
            </div>
            <p class="ak-err" id="akEmailErr" hidden></p>
            <button type="submit" class="btn btn-accent ak-go" id="akEmailGo">Send my answer</button>
            <p class="ak-small">We send your answer and a few farming tips. You can unsubscribe anytime. See our <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" target="_blank">privacy policy</a>.</p>`;
        block(f);
        const input = $('akEmail'), err = $('akEmailErr'), go = $('akEmailGo');
        const valid = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
        input.addEventListener('input', () => { input.classList.remove('is-bad'); err.hidden = true; });
        setTimeout(() => { if (window.matchMedia('(hover: hover)').matches) input.focus(); }, 350);
        f.addEventListener('submit', async (e) => {
            e.preventDefault();
            const v = input.value.trim();
            if (!valid(v)) { input.classList.add('is-bad'); err.textContent = 'That email does not look right. Please check it.'; err.hidden = false; return; }
            go.disabled = true; go.textContent = 'Sending…';
            try {
                await post(URLS.email, { token, email: v }, 'ask_email');
                f.querySelectorAll('input, button').forEach((x) => { x.disabled = true; });
                go.textContent = 'Sent';
                row(false, `<p>Sent! ${face('happy')} Check your inbox at <b>${esc(v)}</b>. If you do not see it, look in Promotions or Spam.</p>`);
                const done = document.createElement('div');
                done.className = 'ak-done';
                done.innerHTML = `<b>Want me on your farm every day?</b>
                    <p>Plan your whole season, see the growth stage and the weather for every lot, and ask me anytime.</p>
                    <div class="btns">
                        <a class="btn btn-accent" href="{{ route('signup') }}?utm_source=ask-anee&utm_medium=chat">Try it for free</a>
                        <a class="ak-ghost" href="{{ url('/questions') }}">Read other answers</a>
                    </div>`;
                block(done);
            } catch (ex) {
                err.textContent = ex.message || 'The email could not be sent. Please try again.';
                err.hidden = false;
                go.disabled = false; go.textContent = 'Send my answer';
            }
        });
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

    function pickCrop(done) {
        openSheet('Choose a crop');
        onFind = (q) => {
            let html = '';
            for (const [group, items] of Object.entries(CROPS)) {
                const hits = Object.entries(items).filter(([k, c]) => !q || c.label.toLowerCase().includes(q));
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
    const CROPS_FLAT = {};
    Object.values(CROPS).forEach((items) => Object.entries(items).forEach(([k, c]) => { CROPS_FLAT[k] = c.label; }));

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
