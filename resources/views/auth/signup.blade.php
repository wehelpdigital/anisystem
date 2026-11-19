@extends('layouts.public')

@section('title', 'Create Your Free anee.io Farm Account')

@push('head')
    @include('partials.ad-tags')
    <style>
        /* The header already wears the logo; on a phone the second one only
           pushed the form down. */
        @media (max-width: 639.98px) { .au-logo { display: none; } }
        /* An error is read on a small screen in the sun: a size up from the
           shared hint size, and the field it belongs to outlined in red. */
        .su-form .form-error { font-size: .82rem; line-height: 1.4; animation: suIn .28s cubic-bezier(.22,1,.36,1) both; }
        .su-form .form-input[aria-invalid="true"] { border-color: #dc2626; }
        .su-form .form-error a { font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
        .su-sum { display: flex; gap: .6rem; align-items: flex-start; margin-bottom: 1.1rem; padding: .75rem .9rem; border-radius: .9rem;
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .86rem; font-weight: 600; line-height: 1.45;
            animation: suIn .28s cubic-bezier(.22,1,.36,1) both; }
        .su-sum svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .1rem; }
        @keyframes suIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
        .su-agree { display: flex; align-items: flex-start; gap: .6rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #374151; background: #f6f9f1; border: 1px solid #dfeccf; cursor: pointer;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .su-agree input { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .1rem; accent-color: #4a7c2a; }
        .su-agree a { font-weight: 800; color: #2d5016; text-decoration: underline; }
        .su-agree:has(input[aria-invalid="true"]) { border-color: #fca5a5; background: #fef2f2; }
        html.dark .su-agree { color: #d6e2cb; background: #142010; border-color: #2b3a1c; } html.dark .su-agree a { color: #a8cc7e; }
        .su-legal { margin-top: .6rem; font-size: .72rem; line-height: 1.45; text-align: center; color: #6b7280; }
        .su-legal a { text-decoration: underline; }

        /* The guide they left for this form, named on it (2026-11-16). */
        .su-read { display: flex; gap: .75rem; align-items: flex-start; margin-bottom: 1rem; padding: .8rem .95rem; border-radius: 1rem;
            background: #f3f8ec; border: 1px solid #dfeccf; animation: suIn .28s cubic-bezier(.22,1,.36,1) both; }
        .su-read-ico { flex: none; width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: grid; place-items: center; background: #fff; border: 1px solid #dfeccf; font-size: 1.15rem; }
        .su-read-k { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #4a7c2a; }
        .su-read-t { margin-top: .1rem; font-size: .92rem; font-weight: 800; line-height: 1.3; color: #1f2937; }
        .su-read-s { margin-top: .2rem; font-size: .8rem; line-height: 1.45; color: #4b5563; }
        html.dark .su-read { background: #142010; border-color: #2b3a1c; }
        html.dark .su-read-ico { background: #0b1407; border-color: #2b3a1c; }
        html.dark .su-read-k { color: #a8cc7e; } html.dark .su-read-t { color: #f3f4f6; } html.dark .su-read-s { color: #c7d2bd; }

        /* What the free account is, in three ticks. */
        .su-ticks { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem .9rem; margin-top: .55rem; font-size: .78rem; font-weight: 700; color: #4b5563; }
        .su-ticks span { display: inline-flex; align-items: center; gap: .3rem; }
        .su-ticks svg { width: .9rem; height: .9rem; color: #4a7c2a; }
        html.dark .su-ticks { color: #c7d2bd; } html.dark .su-ticks svg { color: #a8cc7e; }

        /* One password box with an eye, and a hint that counts down to eight. */
        .su-pw { position: relative; }
        .su-pw .form-input { padding-right: 3rem; }
        .su-eye { position: absolute; top: 50%; right: .3rem; transform: translateY(-50%); width: 2.5rem; height: 2.5rem; border-radius: .7rem;
            display: grid; place-items: center; color: #6b7280; transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .su-eye:hover, .su-eye:focus-visible { color: #2d5016; background: #f3f8ec; }
        .su-eye svg { width: 1.25rem; height: 1.25rem; }
        .su-eye .su-eye-off, .su-eye[aria-pressed="true"] .su-eye-on { display: none; }
        .su-eye[aria-pressed="true"] .su-eye-off { display: block; }
        html.dark .su-eye:hover, html.dark .su-eye:focus-visible { color: #a8cc7e; background: #142010; }
        .su-count { display: inline-flex; align-items: center; gap: .3rem; transition: color .28s cubic-bezier(.22,1,.36,1); }
        .su-count svg { width: .9rem; height: .9rem; display: none; }
        .su-count.is-ok { color: #3d6823; font-weight: 700; } .su-count.is-ok svg { display: inline; }
        html.dark .su-count.is-ok { color: #a8cc7e; }

        /* "Did you mean ...@gmail.com?" */
        .su-mean { margin-top: .4rem; font-size: .82rem; color: #374151; animation: suIn .28s cubic-bezier(.22,1,.36,1) both; }
        .su-mean button { font-weight: 800; color: #2d5016; text-decoration: underline; text-underline-offset: 2px; }
        html.dark .su-mean { color: #d1d5db; } html.dark .su-mean button { color: #a8cc7e; }

        /* The button while the account is being made: once, and it says so. */
        .su-go { position: relative; }
        .su-go .su-spin { display: none; width: 1.1rem; height: 1.1rem; border-radius: 999px; border: 2.5px solid rgb(26 26 26 / .25); border-top-color: #1a1a1a; animation: suSpin .8s linear infinite; }
        .su-go.is-busy .su-spin { display: inline-block; }
        @keyframes suSpin { to { transform: rotate(360deg); } }
        /* "Which best describes you?" (2026-11-19): three big choices, one tap. */
        .su-who { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin: 0; padding: 0; border: 0; }
        .su-who-o { position: relative; display: flex; flex-direction: column; align-items: center; gap: .3rem; padding: .75rem .35rem .65rem;
            border: 1.5px solid #d1d5db; border-radius: .95rem; background: #fff; cursor: pointer; text-align: center;
            font-size: .86rem; font-weight: 800; color: #374151;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .su-who-o input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
        .su-who-i { font-size: 1.35rem; line-height: 1; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .su-who-o:hover { border-color: #9cbf7a; }
        .su-who-o.is-on { border-color: #4a7c2a; background: #f3f8ec; color: #2d5016; box-shadow: 0 0 0 3px rgb(74 124 42 / .16); }
        .su-who-o.is-on .su-who-i { transform: scale(1.12); }
        .su-who-o:has(input:focus-visible) { outline: 2px solid #4a7c2a; outline-offset: 2px; }
        .su-who-wrong .su-who-o { border-color: #f87171; }
        html.dark .su-who-o { background: #111827; border-color: #4b5563; color: #e5e7eb; }
        html.dark .su-who-o.is-on { background: #142010; border-color: #a8cc7e; color: #d9ebc4; box-shadow: 0 0 0 3px rgb(168 204 126 / .18); }
        @media (prefers-reduced-motion: reduce) { .su-who-o, .su-who-i { transition: none; } .su-who-o.is-on .su-who-i { transform: none; } }
        @media (prefers-reduced-motion: reduce) { .su-sum, .su-read, .su-mean, .su-form .form-error { animation: none; } .su-go .su-spin { animation-duration: 2.4s; } }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-5">
            <img src="{{ asset('images/logo.png') }}?v=anee2" alt="anee.io" class="au-logo h-9 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Create your free account</h1>
            <p class="su-ticks">
                <span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>Free forever</span>
                <span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>No card</span>
                <span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>Works on any phone</span>
            </p>
        </div>

        @include('auth.partials.signup-steps', ['at' => 1, 'reading' => $reading])

        {{-- A members' guide sent them here: say which, and that it waits for them (Auth\WelcomeController). --}}
        @if ($reading)
            <div class="su-read" role="note">
                <span class="su-read-ico" aria-hidden="true">📖</span>
                <div class="min-w-0">
                    <p class="su-read-k">You were reading</p>
                    <p class="su-read-t">{{ $reading['title'] }}</p>
                    <p class="su-read-s">Make your free account and confirm your email. We bring you right back to this guide.</p>
                </div>
            </div>
        @endif

        <div class="card card-body">
            @include('auth.partials.google-button')
            @if (filled(config('services.google.client_id')))
                <p class="su-legal">Continuing with Google means you agree to the <a href="{{ route('legal.show', ['slug' => 'eula']) }}" target="_blank" rel="noopener">End User License Agreement</a>, the <a href="{{ route('legal.show', ['slug' => 'terms']) }}" target="_blank" rel="noopener">Terms of Service</a> and the <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" target="_blank" rel="noopener">Privacy Policy</a>.</p>
            @endif

            {{-- After a refused try the page opens at the top, and the field
                 that was wrong may be a screen below: say so here first. --}}
            @if ($errors->any())
                <div class="su-sum" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5v5.5m0 3.5h.01"/></svg>
                    <span>{{ $errors->count() === 1 ? 'One thing needs fixing before we can make your account. It is marked in red below.' : 'A few things need fixing before we can make your account. They are marked in red below.' }}</span>
                </div>
            @endif

            @php
                $suCountry = old('country') ?: \App\Support\Region::code();
                if (! \App\Support\Region::intlOpen()) { $suCountry = \App\Support\Region::HOME; }
                $suPhone = \App\Support\Region::phone($suCountry);
                // The server's own phone rule, for the check as they type (the server still decides).
                $suPhoneRule = preg_replace('#^/(.*)/[a-z]*$#s', '$1', (string) ($suPhone['regex'] ?? ''));
            @endphp
            <form method="POST" action="{{ route('signup.attempt') }}" class="space-y-4 su-form" id="signupForm" novalidate>
                @csrf
                @if ($plan)
                    <input type="hidden" name="plan" value="{{ $plan }}">
                @endif

                {{-- Who they are (2026-11-19, the owner): saved with the account and on the newsletter lists, so the
                     guides and news we send can fit a farmer, a student or anyone else. --}}
                @php $suWho = old('occupation', is_string(request('occupation')) ? request('occupation') : ''); @endphp
                <div data-field>
                    <p class="form-label" id="whoLabel">Which best describes you?</p>
                    <fieldset class="su-who @error('occupation') su-who-wrong @enderror" aria-labelledby="whoLabel" id="suWho">
                        @foreach (['farmer' => '🌾', 'student' => '🎓', 'other' => '🙋'] as $k => $icon)
                            <label class="su-who-o {{ $suWho === $k ? 'is-on' : '' }}">
                                <input type="radio" name="occupation" value="{{ $k }}" id="who_{{ $k }}" @checked($suWho === $k) required
                                    @if ($loop->first) data-need="Please choose the one that fits you best." @endif
                                    @error('occupation') aria-invalid="true" aria-describedby="whoErr" @enderror>
                                <span class="su-who-i" aria-hidden="true">{{ $icon }}</span>
                                <span>{{ \App\Models\User::OCCUPATIONS[$k] }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('occupation') <p class="form-error" id="whoErr" data-server-err>{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div data-field>
                        <label for="firstName" class="form-label">First name</label>
                        <input id="firstName" name="firstName" type="text" value="{{ old('firstName') }}" @error('firstName') aria-invalid="true" aria-describedby="firstNameErr" @enderror
                            class="form-input" placeholder="{{ \App\Support\Region::t('firstNameExample') }}" required data-desktop-focus autocomplete="given-name"
                            data-need="Please enter your first name.">
                        @error('firstName') <p class="form-error" id="firstNameErr" data-server-err>{{ $message }}</p> @enderror
                    </div>
                    <div data-field>
                        <label for="lastName" class="form-label">Last name</label>
                        <input id="lastName" name="lastName" type="text" value="{{ old('lastName') }}" @error('lastName') aria-invalid="true" aria-describedby="lastNameErr" @enderror
                            class="form-input" placeholder="{{ \App\Support\Region::t('lastNameExample') }}" required autocomplete="family-name"
                            data-need="Please enter your last name.">
                        @error('lastName') <p class="form-error" id="lastNameErr" data-server-err>{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Where the farm is. While the international version is closed
                     every account is a Philippine one (RegisterController sets it
                     whatever is sent), so there is nothing to choose and the field
                     stays out of the way; it comes back when other countries open. --}}
                @if (\App\Support\Region::intlOpen())
                    <div>
                        <label class="form-label">Country</label>
                        @include('partials.country-pick', ['id' => 'signupCountry', 'name' => 'country', 'value' => $suCountry])
                        <p class="form-hint">Sets your language, currency and Anee's local advice.</p>
                        @error('country') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <input type="hidden" name="country" value="{{ $suCountry }}">
                @endif

                <div data-field>
                    <label for="phone" class="form-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror
                        class="form-input" placeholder="{{ $suPhone['placeholder'] ?? '' }}" required autocomplete="tel" aria-describedby="phoneHint @error('phone') phoneErr @enderror"
                        data-need="Please enter your mobile number." data-rule="{{ $suPhoneRule }}" data-rule-says="{{ $suPhone['error'] ?? '' }}">
                    <p class="form-hint" id="phoneHint">{{ $suPhone['hint'] ?? '' }}</p>
                    @error('phone') <p class="form-error" id="phoneErr" data-server-err>{{ $message }}</p> @enderror
                </div>

                <div data-field>
                    <label for="email" class="form-label">Email address</label>
                    {{-- The landing page hands its email field over (?email=). --}}
                    <input id="email" name="email" type="email" inputmode="email" value="{{ old('email', is_string(request('email')) ? mb_substr(request('email'), 0, 190) : '') }}" @error('email') aria-invalid="true" aria-describedby="emailErr" @enderror
                        class="form-input" placeholder="you@example.com" required autocomplete="email" autocapitalize="off" spellcheck="false"
                        data-need="Please enter your email address.">
                    <p class="su-mean" id="emailMean" hidden>Did you mean <button type="button" id="emailFix"></button>?</p>
                    @error('email')
                        <p class="form-error" id="emailErr" data-server-err>{{ $message }}
                            {{-- The address is already a member's: the way in is one tap. --}}
                            @if (str_contains(strtolower($message), 'log in'))
                                <a href="{{ route('login') }}{{ $reading ? '?read=' . urlencode(parse_url($reading['url'], PHP_URL_PATH)) : '' }}">Log in</a> or <a href="{{ route('password.request') }}">reset your password</a>.
                            @endif
                        </p>
                    @enderror
                </div>

                <div data-field>
                    <label for="password" class="form-label">Password</label>
                    <div class="su-pw">
                        <input id="password" name="password" type="password" @error('password') aria-invalid="true" @enderror
                            class="form-input" required autocomplete="new-password" minlength="8" aria-describedby="passwordHint @error('password') passwordErr @enderror"
                            data-need="Please choose a password.">
                        {{-- One box, and an eye to see it: a thumb on a small keyboard checks what it typed. --}}
                        <button type="button" class="su-eye" id="suEye" aria-pressed="false" aria-controls="password" aria-label="Show the password">
                            <svg class="su-eye-on" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7S2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="su-eye-off" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 5.1A9.8 9.8 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3.2 4.1M6.6 6.6C3.9 8.3 2.5 12 2.5 12s3.5 7 9.5 7a9.6 9.6 0 004.4-1.1M9.9 9.9a3 3 0 004.2 4.2"/></svg>
                        </button>
                    </div>
                    {{-- Said under the field, where it stays while typing (a
                         placeholder is gone at the first letter). --}}
                    <p class="form-hint" id="passwordHint"><span class="su-count" id="pwCount"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg><span id="pwCountText">At least 8 characters.</span></span></p>
                    @error('password') <p class="form-error" id="passwordErr" data-server-err>{{ $message }}</p> @enderror
                </div>

                {{-- The agreement (2026-10-28, the owner: "in the registration of anee.io, add a eula"): ticked, and kept
                     with its version (App\Support\Legal). --}}
                <div data-field>
                    <label class="su-agree" for="eula">
                        <input type="checkbox" id="eula" name="eula" value="1" @checked(old('eula')) required @error('eula') aria-invalid="true" aria-describedby="eulaErr" @enderror
                            data-need="Please read and tick the agreement to create your account.">
                        <span>I have read and agree to the <a href="{{ route('legal.show', ['slug' => 'eula']) }}" target="_blank" rel="noopener">End User License Agreement</a>, the <a href="{{ route('legal.show', ['slug' => 'terms']) }}" target="_blank" rel="noopener">Terms of Service</a> and the <a href="{{ route('legal.show', ['slug' => 'privacy']) }}" target="_blank" rel="noopener">Privacy Policy</a>. I understand that Anee's advice is a guide and that the decisions on my farm are mine.</span>
                    </label>
                    @error('eula')<p class="form-error" id="eulaErr" data-server-err>{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full su-go" id="suGo">
                    <span class="su-spin" aria-hidden="true"></span>
                    <span id="suGoText">Create my free account</span>
                </button>
                <p class="text-center text-sm text-gray-500">We will email you a link. Your registration is complete once you tap it.</p>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already have an account?
            <a href="{{ route('login') }}{{ $reading ? '?read=' . urlencode(parse_url($reading['url'], PHP_URL_PATH)) : '' }}" class="inline-block py-2 font-bold text-brand-700 hover:underline">Log in</a>
        </p>
    </div>
</div>
<script>
    (() => {
        const form = document.getElementById('signupForm');
        if (!form) return;
        const $ = (id) => document.getElementById(id);
        const phone = $('phone');
        const email = $('email');
        const pw = $('password');

        // The phone rule follows the country the moment it changes.
        $('signupCountry')?.addEventListener('country:change', (e) => {
            const r = e.detail && e.detail.rules;
            if (!r) return;
            const h = $('phoneHint');
            if (phone) { phone.placeholder = r.phone.placeholder || ''; phone.dataset.rule = String(r.phone.regex || '').replace(/^\/(.*)\/[a-z]*$/s, '$1'); phone.dataset.ruleSays = r.phone.error || ''; }
            if (h) h.textContent = r.phone.hint || '';
        });

        // A Philippine number typed the way it is said (+63 917 123 4567,
        // 63917..., 917..., with brackets or dots) is put in the 09 form the
        // server checks for, so a right number is never refused for its shape.
        const isPh = () => (form.querySelector('[name="country"]')?.value || 'PH') === 'PH';
        const tidy = () => {
            if (!phone || !isPh()) return;
            const d = phone.value.replace(/[^\d+]/g, '');
            const m = d.match(/^\+?63(9\d{9})$/) || d.match(/^(9\d{9})$/);
            if (m) phone.value = '0' + m[1];
            else if (/^09\d{9}$/.test(d)) phone.value = d;
        };

        // ---- the checks, as they type (the server still has the last word) ----
        const wrong = (f) => {
            if (f.type === 'checkbox') return f.checked ? '' : f.dataset.need;
            if (f.type === 'radio') return form.querySelector('[name="' + f.name + '"]:checked') ? '' : f.dataset.need;
            const v = f.value.trim();
            if (!v) return f.dataset.need || '';
            if (f === email && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'That email address does not look right. Please check it.';
            if (f === pw && f.value.length < 8) return 'Your password needs at least 8 characters.';
            if (f === phone && f.dataset.rule) {
                try { if (!new RegExp(f.dataset.rule).test(v.replace(/[\s\-().]+/g, ''))) return f.dataset.ruleSays || 'Please check your mobile number.'; } catch (e) {}
            }
            return '';
        };
        const say = (f, msg) => {
            const box = f.closest('[data-field]');
            if (!box) return;
            // The server's word on this field goes once the field changes.
            box.querySelectorAll('[data-server-err]').forEach((p) => p.remove());
            let p = box.querySelector('[data-live-err]');
            if (!msg) {
                p?.remove();
                f.removeAttribute('aria-invalid');
                return;
            }
            if (!p) {
                p = document.createElement('p');
                p.className = 'form-error';
                p.id = f.id + 'Live';
                p.setAttribute('data-live-err', '');
                box.appendChild(p);
                f.setAttribute('aria-describedby', ((f.getAttribute('aria-describedby') || '').replace(/\S+Err\b/g, '').trim() + ' ' + p.id).trim());
            }
            p.textContent = msg;
            f.setAttribute('aria-invalid', 'true');
        };
        const fields = [...form.querySelectorAll('[data-need]')];
        fields.forEach((f) => {
            // Judged when they leave the field, and cleared the moment it is right.
            f.addEventListener('blur', () => { if (f === phone) tidy(); if (f.type !== 'checkbox' && f.type !== 'radio' && (f.value.trim() || f.dataset.touched)) say(f, wrong(f)); f.dataset.touched = '1'; });
            f.addEventListener(f.type === 'checkbox' ? 'change' : 'input', () => {
                if (f.getAttribute('aria-invalid') === 'true') say(f, wrong(f));
            });
        });

        // ---- "Which best describes you?": the chosen card lights, the error goes, Google carries the answer ----
        const who = [...form.querySelectorAll('[name="occupation"]')];
        const google = document.querySelector('[data-google-go]');
        const paintWho = () => {
            const on = who.find((r) => r.checked);
            who.forEach((r) => r.closest('.su-who-o')?.classList.toggle('is-on', r === on));
            if (on) { $('suWho')?.classList.remove('su-who-wrong'); if (who[0].getAttribute('aria-invalid') === 'true') say(who[0], ''); }
            if (google) {
                const u = new URL(google.href, location.href);
                on ? u.searchParams.set('occupation', on.value) : u.searchParams.delete('occupation');
                google.href = u.toString();
            }
        };
        who.forEach((r) => r.addEventListener('change', paintWho));
        paintWho();

        // ---- the password counts down to eight ----
        const count = $('pwCount'), countText = $('pwCountText');
        const paintCount = () => {
            const n = pw.value.length, left = 8 - n;
            count.classList.toggle('is-ok', left <= 0);
            countText.textContent = n === 0 ? 'At least 8 characters.' : left > 0 ? left + ' more character' + (left === 1 ? '' : 's') + ' to go.' : 'Long enough.';
        };
        pw?.addEventListener('input', paintCount);
        if (pw && pw.value) paintCount();
        $('suEye')?.addEventListener('click', (e) => {
            const b = e.currentTarget, on = b.getAttribute('aria-pressed') !== 'true';
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.setAttribute('aria-label', on ? 'Hide the password' : 'Show the password');
            pw.type = on ? 'text' : 'password';
            pw.focus({ preventScroll: true });
        });

        // ---- "Did you mean juan@gmail.com?": a typo in the address loses the link ----
        // Real mail homes, so mail.com is never "corrected" to gmail.com.
        const HOMES = ['gmail.com', 'yahoo.com', 'yahoo.com.ph', 'ymail.com', 'outlook.com', 'outlook.ph', 'hotmail.com', 'live.com', 'icloud.com', 'me.com',
            'rocketmail.com', 'mail.com', 'email.com', 'aol.com', 'gmx.com', 'proton.me', 'protonmail.com', 'zoho.com', 'yandex.com', 'deped.gov.ph', 'up.edu.ph'];
        const far = (a, b) => {
            const d = Array.from({ length: a.length + 1 }, (_, i) => [i]);
            for (let j = 1; j <= b.length; j++) d[0][j] = j;
            for (let i = 1; i <= a.length; i++) for (let j = 1; j <= b.length; j++)
                d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
            return d[a.length][b.length];
        };
        const mean = $('emailMean'), fix = $('emailFix');
        const guess = () => {
            const v = email.value.trim().toLowerCase(), at = v.lastIndexOf('@');
            if (at < 1) return '';
            const host = v.slice(at + 1);
            if (!host || HOMES.includes(host)) return '';
            let best = '', bestD = 3;
            HOMES.forEach((h) => { const d = far(host, h); if (d < bestD) { bestD = d; best = h; } });
            // gmail.co, gmail.con, gmial.com: one or two letters off a mail home.
            return best && bestD <= 2 && host.length >= 5 ? v.slice(0, at + 1) + best : '';
        };
        const offer = () => {
            const g = guess();
            fix.textContent = g;
            mean.hidden = !g;
        };
        email?.addEventListener('blur', offer);
        email?.addEventListener('input', () => { if (!mean.hidden) offer(); });
        fix?.addEventListener('click', () => {
            email.value = fix.textContent;
            mean.hidden = true;
            say(email, wrong(email));
            email.focus({ preventScroll: true });
        });

        // ---- sending: checked first, then once, and the button says so ----
        const go = $('suGo'), goText = $('suGoText');
        let sent = false;
        form.addEventListener('submit', (e) => {
            tidy();
            if (sent) { e.preventDefault(); return; }
            let first = null;
            fields.forEach((f) => { const m = wrong(f); say(f, m); if (m && !first) first = f; });
            $('suWho')?.classList.toggle('su-who-wrong', !who.some((r) => r.checked));
            if (first) {
                e.preventDefault();
                const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
                first.scrollIntoView({ block: 'center', behavior: calm ? 'auto' : 'smooth' });
                first.focus({ preventScroll: true });
                return;
            }
            sent = true;
            go.classList.add('is-busy');
            go.setAttribute('aria-disabled', 'true');
            goText.textContent = 'Making your account…';
        });
        // Back from the next page (the browser's own copy): the button works again.
        window.addEventListener('pageshow', () => { sent = false; go.classList.remove('is-busy'); go.removeAttribute('aria-disabled'); goText.textContent = 'Create my free account'; });

        // After a refused try, the first field to fix gets the caret.
        const bad = form.querySelector('[aria-invalid="true"]');
        if (bad) {
            const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
            requestAnimationFrame(() => {
                bad.scrollIntoView({ block: 'center', behavior: calm ? 'auto' : 'smooth' });
                bad.focus({ preventScroll: true });
            });
        }
    })();
</script>
@endsection
