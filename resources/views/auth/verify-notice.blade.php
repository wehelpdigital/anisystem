@extends('layouts.public')

@section('title', 'Confirm Your Email')

{{-- Straight from the signup form: the ads count it as their conversion. --}}
@push('head')
    @include('partials.ad-tags', ['signedUp' => (bool) session('signup.fresh')])
    <style>
        .vn-card { animation: vnIn .28s cubic-bezier(.22,1,.36,1) both; }
        @keyframes vnIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .vn-mail { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; }
        .vn-mail svg { width: 1.15rem; height: 1.15rem; }
        .vn-wait { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1rem; font-size: .8rem; font-weight: 600; color: #6b7280; }
        .vn-wait i { width: .5rem; height: .5rem; border-radius: 999px; background: #4a7c2a; animation: vnBeat 1.6s ease-in-out infinite; }
        @keyframes vnBeat { 0%, 100% { opacity: .25; transform: scale(.8); } 50% { opacity: 1; transform: none; } }
        .vn-tips { margin-top: 1.1rem; text-align: left; font-size: .82rem; line-height: 1.5; color: #4b5563; }
        .vn-tips summary { cursor: pointer; font-weight: 700; color: #2d5016; list-style: none; text-align: center; }
        .vn-tips summary::-webkit-details-marker { display: none; }
        .vn-tips ul { margin-top: .6rem; padding: .7rem .9rem .7rem 1.9rem; border-radius: .9rem; background: #f9fafb; list-style: disc; }
        .vn-tips li + li { margin-top: .3rem; }
        html.dark .vn-tips { color: #d1d5db; } html.dark .vn-tips summary { color: #a8cc7e; } html.dark .vn-tips ul { background: #111827; }
        .vn-done[hidden], .vn-waiting[hidden] { display: none; }
        .vn-lock { display: flex; gap: .6rem; align-items: flex-start; margin-top: 1rem; padding: .75rem .85rem; border-radius: .9rem; text-align: left;
            background: #fff8e1; border: 1px solid #f3dd9a; color: #5b4a12; font-size: .84rem; line-height: 1.5; }
        .vn-lock svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .1rem; color: #b8860b; }
        .vn-gets { margin-top: .9rem; text-align: left; font-size: .84rem; line-height: 1.5; color: #374151; }
        .vn-gets b { display: block; margin-bottom: .35rem; font-size: .74rem; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
        .vn-gets li { display: flex; gap: .5rem; align-items: flex-start; }
        .vn-gets li + li { margin-top: .35rem; }
        .vn-gets li svg { flex: none; width: 1rem; height: 1rem; margin-top: .2rem; color: #4a7c2a; }
        html.dark .vn-lock { background: #2e2a12; border-color: #5b4e17; color: #f3e6b5; }
        html.dark .vn-gets { color: #d1d5db; } html.dark .vn-gets b { color: #9ca3af; }
        @media (prefers-reduced-motion: reduce) { .vn-card { animation: none; } .vn-wait i { animation: none; opacity: 1; } }
    </style>
@endpush

@section('content')
@php
    // The address's own mail app, one tap away: the link waits in there.
    $vnHost = $email ? strtolower((string) substr(strrchr($email, '@'), 1)) : '';
    $vnApp = match (true) {
        in_array($vnHost, ['gmail.com', 'googlemail.com'], true) => ['Open Gmail', 'https://mail.google.com/mail/u/0/#search/from%3Aanee.io'],
        (bool) preg_match('/^(yahoo\.|ymail\.com$|rocketmail\.com$)/', $vnHost) => ['Open Yahoo Mail', 'https://mail.yahoo.com/'],
        (bool) preg_match('/^(outlook\.|hotmail\.|live\.|msn\.com$)/', $vnHost) => ['Open Outlook', 'https://outlook.live.com/mail/0/'],
        in_array($vnHost, ['icloud.com', 'me.com', 'mac.com'], true) => ['Open iCloud Mail', 'https://www.icloud.com/mail'],
        default => null,
    };
    // Just sent (the signup, or a resend): the button rests a minute, as the server does.
    $vnJustSent = (bool) session('signup.fresh') || (bool) session('success');
    $vnLogin = route('login') . ($reading ? '?read=' . urlencode((string) parse_url($reading['url'], PHP_URL_PATH)) : '');
    // From a members' guide (2026-11-19, the owner: "persuade to verify email first"): the way back to the guide opens
    // only once the email is confirmed (the account cannot log in before), so the page says so and what confirming
    // opens. A guide behind Libre + Anee opens only its start on the free plan: said, as the welcome page says it.
    $vnSection = $reading ? (explode('/', trim((string) parse_url($reading['url'], PHP_URL_PATH), '/'))[0] ?? '') : '';
    $vnPlus = $reading && \App\Support\ContentGate::needsPlus($vnSection);
    $vnPlusName = \App\Support\Tier::planName(\App\Support\ContentGate::PLUS_PLAN);
@endphp
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div id="vnSteps">@include('auth.partials.signup-steps', ['at' => 2, 'reading' => $reading])</div>

        <div class="card card-body text-center vn-card vn-waiting" id="vnWaiting">
            <div class="mx-auto w-16 h-16 rounded-full bg-brand-50 ring-1 ring-brand-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ $reading ? 'Confirm your email to keep reading' : (($existing ?? false) ? 'Confirm your email to continue' : 'Check your inbox') }}</h1>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                We sent a confirmation link
                @if ($email) to <span class="font-semibold text-gray-900" style="overflow-wrap:anywhere">{{ $email }}</span>@endif.
                {{ ($existing ?? false) ? 'Every account confirms its email before it goes on. Tap the link and you are back in.' : 'Tap it to open your free account.' }}
            </p>
            @if ($reading)
                <div class="vn-lock">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>
                    <span>
                        @if ($vnPlus)
                            <b>{{ $reading['title'] }}</b> waits for you behind this link. Confirm your email and we take you straight back. Your free account opens the start of this guide, and the whole guide comes with {{ $vnPlusName }}.
                        @else
                            The rest of <b>{{ $reading['title'] }}</b> opens the moment you confirm your email. Until then it stays locked, so tap the link now and we take you straight back to it.
                        @endif
                    </span>
                </div>
                <div class="vn-gets">
                    <b>Confirming opens</b>
                    <ul>
                    @if ($vnPlus)
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Your way back to {{ $reading['title'] }}, and every pest, disease, weed and variety guide in full when you add Anee</span></li>
                    @else
                        <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>The whole of {{ $reading['title'] }}, and every land preparation, how to plant and nutrient guide</span></li>
                    @endif
                    <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Your free farm calendar, with every task dated from your planting day</span></li>
                    <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Growth stages, notes and photos for your season, free</span></li>
                    </ul>
                </div>
            @endif

            @if ($vnApp)
                <a href="{{ $vnApp[1] }}" target="_blank" rel="noopener" class="btn btn-accent w-full mt-5 vn-mail">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $vnApp[0] }}
                </a>
            @endif

            @if ($email)
                <p class="vn-wait" aria-live="polite"><i aria-hidden="true"></i><span>Waiting for you to tap the link</span></p>
            @endif

            <details class="vn-tips">
                <summary>No email after a minute?</summary>
                <ul>
                    <li>Look in the Spam, Promotions or Updates folder. Mark it "Not spam" so our next emails reach you.</li>
                    <li>Search your mail for <b>anee.io</b>.</li>
                    @if ($existing ?? false)
                        <li>Check that the address above is right. If it is not, tell us below and we fix it.</li>
                    @else
                        <li>Check that the address above is right. If it is not, sign up again with the right one.</li>
                    @endif
                    <li>Still nothing? Send a new link below.</li>
                </ul>
            </details>

            <form method="POST" action="{{ route('verify.resend') }}" class="mt-5 space-y-3" id="vnResend">
                @csrf
                @unless ($email)
                    <input name="email" type="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                @endunless
                <button type="submit" class="btn btn-outline w-full" id="vnResendBtn" @if ($vnJustSent && $email) data-rest="60" @endif>Send the link again</button>
            </form>
        </div>

        {{-- Tapped in another browser (the mail app's own): this one says so and offers the way in. --}}
        <div class="card card-body text-center vn-card vn-done" id="vnDone" hidden>
            <div class="mx-auto w-16 h-16 rounded-full bg-brand-50 ring-1 ring-brand-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold text-gray-900">Your email is confirmed</h2>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed" id="vnDoneSays">You opened the link in another app. Log in here to keep going{{ $reading ? ' with ' . $reading['title'] : '' }}.</p>
            <a href="{{ $vnLogin }}" class="btn btn-accent w-full mt-5" id="vnDoneGo">Log in</a>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6" id="vnWrong">
            Wrong address?
            @if ($existing ?? false)
                {{-- An account that already has its seasons: a new signup would start an empty one. --}}
                <a href="{{ route('contact') }}" class="inline-block py-2 font-bold text-brand-700 hover:underline">Tell us, and we fix it</a>
            @else
            <a href="{{ route('signup') }}{{ $reading ? '?read=' . urlencode((string) parse_url($reading['url'], PHP_URL_PATH)) : '' }}" class="inline-block py-2 font-bold text-brand-700 hover:underline">Sign up again</a>
            @endif
        </p>
    </div>
</div>
<script>
    (() => {
        // ---- the resend button rests a minute after a link went out ----
        const btn = document.getElementById('vnResendBtn');
        let left = Number(btn?.dataset.rest || 0);
        const rest = () => {
            if (!btn) return;
            if (left <= 0) { btn.disabled = false; btn.textContent = 'Send the link again'; return; }
            btn.disabled = true;
            btn.textContent = 'Send the link again in ' + left + ' s';
            left--;
            setTimeout(rest, 1000);
        };
        rest();

        // ---- confirmed somewhere else? ask now and then, while the page is in view ----
        @if ($email)
        const statusUrl = @json(route('verify.status'));
        const started = Date.now();
        let timer = null, done = false;
        const showDone = (signedIn) => {
            done = true;
            // Opened in another tab of this same browser: this one is signed in too.
            if (signedIn) {
                document.getElementById('vnDoneSays').textContent = 'You are in. Your free account is open.';
                const go = document.getElementById('vnDoneGo');
                go.href = @json(route('signup.welcome'));
                go.textContent = 'Keep going';
            }
            document.getElementById('vnWaiting').hidden = true;
            document.getElementById('vnWrong').hidden = true;
            document.getElementById('vnDone').hidden = false;
            const steps = document.querySelectorAll('#vnSteps .su-step');
            if (steps[1]) {
                steps[1].classList.remove('is-now'); steps[1].classList.add('is-done'); steps[1].removeAttribute('aria-current');
                const dot = steps[1].querySelector('.su-dot');
                if (dot) dot.innerHTML = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
            }
            if (steps[2]) { steps[2].classList.add('is-now'); steps[2].setAttribute('aria-current', 'step'); }
        };
        const ask = async () => {
            clearTimeout(timer);
            if (done || document.hidden) return;
            // Twenty minutes is long enough: after that a reload asks again.
            if (Date.now() - started > 20 * 60 * 1000) return;
            try {
                const r = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                // Sent on to the dashboard: this browser is signed in now.
                if (r.redirected) { showDone(true); return; }
                if (r.ok && (await r.json()).confirmed) { showDone(false); return; }
            } catch (e) {}
            timer = setTimeout(ask, Date.now() - started < 60 * 1000 ? 8000 : 20000);
        };
        timer = setTimeout(ask, 8000);
        // Back from the mail app: ask at once.
        document.addEventListener('visibilitychange', () => { if (!document.hidden) ask(); });
        @endif
    })();
</script>
@endsection
