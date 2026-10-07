@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@php
    $ph = \App\Support\Region::ph();
    $pay = \App\Support\Region::payMethod();
    $mail = 'support@anee.io';
    $signup = route('signup');
    $ask = route('ask.page');
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
@endphp

@section('title', 'Contact anee.io: Help With Your Farm App')
@section('meta_description', 'Get in touch with the anee.io team. Questions about plans, ' . $pay . ' payments, or using the app? Email support@anee.io and a real person replies, usually within a business day.')

{{-- CONTACT (rebuilt 2026-10-06 in the homepage's look). Still no form, on
     purpose: a contact form swallows a message, the sender cannot see what
     they sent or attach the photo of the leaf, and has nothing to follow up
     on. An email address gives them all three. Around it: the fastest door
     for each kind of question (Anee for a crop question, the app's own Help
     and Support for members, the tutorial for how things work), quick
     answers to what people ask most, and a way to start. --}}
@section('content')

    {{-- ================= HERO ================= --}}
    <section class="ct2-hero">
        <img src="{{ asset('images/site/photos/inspect.jpg') }}" alt="A farmer in her rice field, checking her season on anee.io" class="ct2-hero-bg" loading="eager" fetchpriority="high">
        <div class="ct2-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 ct2-hero-in text-center on-dark" style="z-index:1">
            <span class="ct2-chip animate-fade-up">Contact us</span>
            <h1 class="ct2-h1 animate-fade-up" style="animation-delay:.06s">
                We Are Here
                <span class="hp-mark"><span class="hp-shimmer">to Help.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M5 15 C 55 7, 105 19, 160 12 S 255 6, 295 13"/></svg></span>
            </h1>
            <p class="ct2-lede animate-fade-up" style="animation-delay:.12s">
                Questions about plans, {{ $pay }} payments or setting up your season? Write to us. A real person reads
                every message and answers, usually within a business day.
            </p>
        </div>
    </section>

    {{-- ================= THE ADDRESS ================= --}}
    <section class="ct2-mail-wrap">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="ct2-mail reveal">
                <span class="ct2-mail-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                </span>
                <p class="ct2-mail-lead">Email us at</p>
                <a class="ct2-mail-addr" href="mailto:{{ $mail }}">{{ $mail }}</a>
                <p class="ct2-mail-sub">For plans, payments, your account, or anything that is not working.</p>
                <div class="hp-cta-row" style="margin-top: 1.4rem">
                    <a href="mailto:{{ $mail }}" class="btn btn-accent btn-lg hp-go">Open your email app {!! $arrow !!}</a>
                    <button type="button" class="hp-alt" id="ctCopyMail" data-mail="{{ $mail }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M5 15V6a2 2 0 012-2h9"/></svg>
                        <span>Copy the address</span>
                    </button>
                </div>
                <p class="ct2-mail-tip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    A photo of the leaf, the label or the screen helps us more than a long description.
                </p>
            </div>

            <div class="ct2-facts">
                <div class="ct2-fact reveal" style="--reveal-delay:.06s">
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                    <div><b>When we answer</b><small>Monday to Saturday, usually the same day.</small></div>
                </div>
                <div class="ct2-fact reveal" style="--reveal-delay:.12s">
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <div><b>Where we are</b><small>{{ $ph ? 'The Philippines. Built here, for farms here.' : 'Built in the Philippines, for farms everywhere.' }}</small></div>
                </div>
                <div class="ct2-fact reveal" style="--reveal-delay:.18s">
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 015-3.9m6-4.1a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                    <div><b>Who answers</b><small>Our own team, the people who build and use anee.io.</small></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= THE FASTEST DOOR ================= --}}
    <section class="hp-sec bg-brand-mesh bg-drift">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Get an answer faster</p>
                <h2 class="hp-h2">Pick the Quickest Way <em>for Your Question.</em></h2>
                <p class="hp-p">Some questions do not need to wait for an email.</p>
            </div>
            <div class="ct2-doors">
                <a href="{{ $ask }}" class="ct2-door is-anee reveal">
                    <span class="ct2-door-ico"><img src="{{ $faceLg }}" alt=""></span>
                    <p class="ct2-door-k">A question about your crop</p>
                    <h3>Ask Anee, free.</h3>
                    <p>Pests, fertilizer, a leaf that looks wrong. Anee answers in minutes{{ $ph ? ', in Filipino or English' : '' }}. No account needed, one free question a week.</p>
                    <span class="ct2-door-go">Ask Anee now {!! $arrow !!}</span>
                </a>
                <a href="{{ route('support.index') }}" class="ct2-door reveal" style="--reveal-delay:.08s">
                    <span class="ct2-door-ico"><img src="{{ asset('images/icons/technician-support.png') }}" alt=""></span>
                    <p class="ct2-door-k">Already a member</p>
                    <h3>Help and Support in the app.</h3>
                    <p>Log in and open Help and Support to send us a help request. Your account details come with it, so we can help faster.</p>
                    <span class="ct2-door-go">Open Help and Support {!! $arrow !!}</span>
                </a>
                <a href="{{ route('tutorial') }}" class="ct2-door reveal" style="--reveal-delay:.16s">
                    <span class="ct2-door-ico"><img src="{{ asset('images/icons/checklist.png') }}" alt=""></span>
                    <p class="ct2-door-k">How something works</p>
                    <h3>Read the tutorial.</h3>
                    <p>Plans, payments and setting up your first season, step by step. It answers most of the questions we get.</p>
                    <span class="ct2-door-go">Read the tutorial {!! $arrow !!}</span>
                </a>
                <a href="{{ route('how') }}" class="ct2-door reveal" style="--reveal-delay:.24s">
                    <span class="ct2-door-ico"><img src="{{ asset('images/icons/calendar.png') }}" alt=""></span>
                    <p class="ct2-door-k">What the app can do</p>
                    <h3>See how it works.</h3>
                    <p>Every tool in the order you use it in a season, each with a short video of the real app on a phone.</p>
                    <span class="ct2-door-go">See how it works {!! $arrow !!}</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ================= QUICK ANSWERS ================= --}}
    @php
        $quick = [
            ['Is anee.io free?', 'Yes. The Libre plan is free forever, with one active season. You can upgrade inside the app when your farm needs more.'],
            ['How do I pay for a plan?', 'Choose a plan inside the app and pay with ' . $pay . '. Send the receipt from the same screen, and your plan turns on once our team checks the payment.'],
            ['Can I ask Anee without an account?', 'Yes. Anyone can ask Anee one free question a week on the Try and Ask Anee page. To use her inside your season, she comes with the Libre + Anee plan and up.'],
            ['I forgot my password. What do I do?', 'Use "Forgot password" on the login page and we will email you a link to set a new one. If the email does not come, write to us.'],
            ['Can my workers use anee.io?', 'Yes. Every morning they can get the day\'s plan by email, and on the Farm Owner plan each worker can have their own login.'],
        ];
    @endphp
    <section class="hp-sec bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Quick answers</p>
                <h2 class="hp-h2">Here Is What People <em>Ask Us Most.</em></h2>
            </div>
            <div class="mt-10 space-y-3" x-data="{ open: 0 }">
                @foreach ($quick as $i => [$q, $a])
                    <div class="ct2-q reveal" :class="{ 'is-open': open === {{ $i }} }" style="--reveal-delay: {{ $i * 0.05 }}s">
                        <button type="button" class="ct2-q-btn" @click="open = open === {{ $i }} ? -1 : {{ $i }}" :aria-expanded="open === {{ $i }}">
                            <span>{{ $q }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <div class="ct2-q-body" :class="{ 'is-open': open === {{ $i }} }">
                            <div><p>{{ $a }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="mailto:{{ $mail }}" class="btn btn-accent btn-lg hp-go">Still have a question? Email us {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Or <a href="{{ route('password.request') }}">reset your password</a> if you cannot log in.</p>
            </div>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="ct2-final">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="ct2-final-face">
            <h2 class="hp-h2">Not a Member Yet? <em>Start Free Today.</em></h2>
            <p class="hp-p">Set up your first season in minutes. If you get stuck, we are one email away.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const b = document.getElementById('ctCopyMail');
            if (!b) return;
            const label = b.querySelector('span');
            b.addEventListener('click', async () => {
                const said = label.textContent;
                try {
                    await navigator.clipboard.writeText(b.dataset.mail);
                    label.textContent = 'Copied';
                    b.classList.add('is-done');
                } catch (_) {
                    // A browser that refuses the clipboard still shows the
                    // address in full above; nothing is lost but the shortcut.
                    label.textContent = b.dataset.mail;
                }
                setTimeout(() => { label.textContent = said; b.classList.remove('is-done'); }, 1800);
            });
        })();
    </script>

@endsection

@push('head')
<style>
    /* ===================== CONTACT ===================== */
    .ct2-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
    .ct2-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; object-position: 50% 35%;
        animation: ct2Ken 24s ease-in-out infinite alternate; }
    @keyframes ct2Ken { from { transform: scale(1.05); } to { transform: scale(1.12) translate3d(-1.5%, -1%, 0); } }
    .ct2-hero-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(10 18 6 / .7), rgb(16 28 10 / .88)); }
    .ct2-hero-in { padding-top: 5.5rem; padding-bottom: 9rem; }
    @media (min-width: 1024px) { .ct2-hero-in { padding-top: 7rem; padding-bottom: 10rem; } }
    .ct2-chip { display: inline-flex; align-items: center; padding: .4rem 1rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase; color: #f4d778; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .22); backdrop-filter: blur(8px); }
    .ct2-h1 { margin-top: 1.4rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.04;
        font-size: clamp(2.5rem, 6.6vw, 4.4rem); text-wrap: balance; }
    .ct2-lede { margin: 1.6rem auto 0; max-width: 38rem; font-size: clamp(1.02rem, 1.7vw, 1.18rem); line-height: 1.7; color: #dde6d4; text-wrap: pretty; }

    /* The address card rides up over the hero's lower edge. */
    .ct2-mail-wrap { position: relative; z-index: 2; margin-top: -6rem; padding-bottom: 4.5rem; }
    .ct2-mail { text-align: center; padding: 2.2rem 1.4rem 1.8rem; border-radius: 1.8rem; background: #fff;
        box-shadow: 0 40px 80px -40px rgb(20 33 12 / .55), 0 0 0 1px rgb(20 33 12 / .06); }
    @media (min-width: 640px) { .ct2-mail { padding: 2.6rem 2.4rem 2rem; } }
    .ct2-mail-ico { width: 4rem; height: 4rem; margin: 0 auto; border-radius: 1.3rem; display: grid; place-items: center; color: var(--hp-ink);
        background: var(--hp-sun); box-shadow: 0 16px 30px -16px rgb(245 197 24 / .9); animation: ct2Bob 5s ease-in-out infinite; }
    .ct2-mail-ico svg { width: 2rem; height: 2rem; }
    @keyframes ct2Bob { 0%, 100% { translate: 0 0; } 50% { translate: 0 -6px; } }
    .ct2-mail-lead { margin-top: 1.2rem; font-size: .8rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .ct2-mail-addr { display: inline-block; margin-top: .4rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.01em;
        font-size: clamp(1.7rem, 5.5vw, 2.6rem); color: var(--hp-ink); text-decoration: none; word-break: break-word;
        background-image: linear-gradient(var(--hp-sun), var(--hp-sun)); background-repeat: no-repeat; background-position: 0 92%; background-size: 0% .22em;
        transition: background-size .5s var(--hp-ease), color .28s var(--hp-ease); }
    .ct2-mail-addr:hover { background-size: 100% .22em; color: var(--hp-deep); }
    .ct2-mail-sub { margin-top: .6rem; color: #4b5563; font-size: 1rem; line-height: 1.6; }
    .hp-alt.is-done { border-color: var(--hp-green); color: var(--hp-green); }
    .ct2-mail-tip { margin: 1.5rem auto 0; max-width: 32rem; display: flex; align-items: flex-start; gap: .6rem; text-align: left; padding: .85rem 1rem;
        border-radius: 1rem; background: #f6faf1; border: 1px solid #dcead0; font-size: .9rem; line-height: 1.55; color: #4b5563; }
    .ct2-mail-tip svg { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .1rem; color: var(--hp-green); }
    .ct2-facts { margin-top: 1.2rem; display: grid; gap: .8rem; }
    @media (min-width: 720px) { .ct2-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ct2-fact { display: flex; align-items: flex-start; gap: .8rem; padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e5ebdf;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ct2-fact:hover { transform: translateY(-3px); box-shadow: 0 18px 34px -26px rgb(20 33 12 / .55); }
    .ct2-fact > span { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .85rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5; }
    .ct2-fact > span svg { width: 1.2rem; height: 1.2rem; }
    .ct2-fact b { display: block; font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); }
    .ct2-fact small { display: block; margin-top: .2rem; font-size: .88rem; color: #6b7280; line-height: 1.45; }

    .ct2-doors { margin-top: 3rem; display: grid; gap: 1.1rem; }
    @media (min-width: 720px) { .ct2-doors { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.3rem; } }
    .ct2-door { display: flex; flex-direction: column; padding: 1.5rem; border-radius: 1.5rem; background: #fff; border: 1px solid #e1ead6; text-decoration: none;
        box-shadow: 0 24px 50px -44px rgb(20 33 12 / .6);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .ct2-door:hover { transform: translateY(-4px); border-color: #b9d69a; box-shadow: 0 28px 54px -34px rgb(20 33 12 / .6); }
    .ct2-door.is-anee { color: #fff; border: 0; background: radial-gradient(120% 120% at 0% 0%, #5c9434 0%, #3d6823 45%, #24420f 100%);
        box-shadow: 0 30px 60px -30px rgb(36 66 15 / .85), 0 0 0 3px rgb(245 197 24 / .55); }
    .ct2-door-ico { width: 3.4rem; height: 3.4rem; border-radius: 1.1rem; display: grid; place-items: center; overflow: hidden; background: #f3f8ec;
        box-shadow: inset 0 0 0 1px #dcead0; }
    .ct2-door-ico img { width: 2.1rem; height: 2.1rem; object-fit: contain; }
    .is-anee .ct2-door-ico { border-radius: 999px; background: #fff; box-shadow: 0 0 0 3px var(--hp-sun); }
    .is-anee .ct2-door-ico img { width: 100%; height: 100%; object-fit: cover; }
    .ct2-door-k { margin-top: 1rem; font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .is-anee .ct2-door-k { color: var(--hp-sun); }
    .ct2-door h3 { margin-top: .35rem; font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: var(--hp-ink); }
    .is-anee h3 { color: #fff; }
    .ct2-door > p { margin-top: .5rem; font-size: .95rem; line-height: 1.6; color: #4b5563; flex: 1 1 auto; }
    .is-anee > p { color: #e4f0d6; }
    .ct2-door-go { margin-top: 1.1rem; display: inline-flex; align-items: center; gap: .35rem; font-weight: 800; color: var(--hp-green); }
    .is-anee .ct2-door-go { color: var(--hp-sun); }
    .ct2-door-go svg { width: 1.05rem; height: 1.05rem; transition: transform .28s var(--hp-ease); }
    .ct2-door:hover .ct2-door-go svg { transform: translateX(4px); }

    .ct2-q { border-radius: 1.1rem; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ct2-q.is-open { border-color: #b9d69a; box-shadow: 0 16px 34px -26px rgb(47 82 25 / .55); }
    .ct2-q-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; text-align: left;
        font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); background: none; border: 0; cursor: pointer; }
    .ct2-q-btn svg { flex: none; width: 1.25rem; height: 1.25rem; color: var(--hp-green); transition: transform .28s var(--hp-ease); }
    .ct2-q.is-open .ct2-q-btn svg { transform: rotate(45deg); }
    .ct2-q-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--hp-ease); }
    .ct2-q-body.is-open { grid-template-rows: 1fr; }
    .ct2-q-body > div { overflow: hidden; }
    .ct2-q-body p { padding: 0 1.25rem 1.2rem; color: #4b5563; line-height: 1.65; }

    .ct2-final { position: relative; isolation: isolate; overflow: hidden;
        background: radial-gradient(90% 120% at 85% 10%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .ct2-final-face { width: 5rem; height: 5rem; margin: 0 auto 1rem; border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 4px var(--hp-sun), 0 0 0 12px rgb(245 197 24 / .18); animation: ct2Bob 5s ease-in-out infinite; }

    @media (prefers-reduced-motion: reduce) {
        .ct2-hero-bg, .ct2-mail-ico, .ct2-final-face { animation: none !important; }
        .ct2-mail-addr, .ct2-fact, .ct2-door, .ct2-door-go svg, .ct2-q, .ct2-q-btn svg, .ct2-q-body { transition: none !important; }
    }
</style>
@endpush
