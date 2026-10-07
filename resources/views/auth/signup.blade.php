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
        .su-form .form-error { font-size: .82rem; line-height: 1.4; }
        .su-form .form-input[aria-invalid="true"] { border-color: #dc2626; }
        .su-form .form-error a { font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
        .su-sum { display: flex; gap: .6rem; align-items: flex-start; margin-bottom: 1.1rem; padding: .75rem .9rem; border-radius: .9rem;
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .86rem; font-weight: 600; line-height: 1.45;
            animation: suIn .28s cubic-bezier(.22,1,.36,1) both; }
        .su-sum svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .1rem; }
        @keyframes suIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
        .su-show { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .88rem; color: #374151; cursor: pointer; user-select: none; }
        .su-show input { width: 1.15rem; height: 1.15rem; }
        @media (prefers-reduced-motion: reduce) { .su-sum { animation: none; } }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" class="au-logo h-9 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Create your free account</h1>
            <p class="text-sm text-gray-500 mt-1">The Libre plan is free forever. No card, no trial.</p>
        </div>

        <div class="card card-body">
            @include('auth.partials.google-button')

            {{-- After a refused try the page opens at the top, and the field
                 that was wrong may be a screen below: say so here first. --}}
            @if ($errors->any())
                <div class="su-sum" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5v5.5m0 3.5h.01"/></svg>
                    <span>{{ $errors->count() === 1 ? 'One thing needs fixing before we can make your account. It is marked in red below.' : 'A few things need fixing before we can make your account. They are marked in red below.' }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('signup.attempt') }}" class="space-y-4 su-form" id="signupForm" novalidate>
                @csrf
                @if ($plan)
                    <input type="hidden" name="plan" value="{{ $plan }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="form-label">First name</label>
                        <input id="firstName" name="firstName" type="text" value="{{ old('firstName') }}" @error('firstName') aria-invalid="true" aria-describedby="firstNameErr" @enderror
                            class="form-input" placeholder="{{ \App\Support\Region::t('firstNameExample') }}" required data-desktop-focus autocomplete="given-name">
                        @error('firstName') <p class="form-error" id="firstNameErr">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="form-label">Last name</label>
                        <input id="lastName" name="lastName" type="text" value="{{ old('lastName') }}" @error('lastName') aria-invalid="true" aria-describedby="lastNameErr" @enderror
                            class="form-input" placeholder="{{ \App\Support\Region::t('lastNameExample') }}" required autocomplete="family-name">
                        @error('lastName') <p class="form-error" id="lastNameErr">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Where the farm is. While the international version is closed
                     every account is a Philippine one (RegisterController sets it
                     whatever is sent), so there is nothing to choose and the field
                     stays out of the way; it comes back when other countries open. --}}
                @php $suCountry = old('country') ?: \App\Support\Region::code(); $suPhone = \App\Support\Region::phone($suCountry); @endphp
                @if (\App\Support\Region::intlOpen())
                    <div>
                        <label class="form-label">Country</label>
                        @include('partials.country-pick', ['id' => 'signupCountry', 'name' => 'country', 'value' => $suCountry])
                        <p class="form-hint">Sets your language, currency and Anee's local advice.</p>
                        @error('country') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    @php $suCountry = \App\Support\Region::HOME; $suPhone = \App\Support\Region::phone($suCountry); @endphp
                    <input type="hidden" name="country" value="{{ $suCountry }}">
                @endif

                <div>
                    <label for="phone" class="form-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror
                        class="form-input" placeholder="{{ $suPhone['placeholder'] ?? '' }}" required autocomplete="tel" aria-describedby="phoneHint @error('phone') phoneErr @enderror">
                    <p class="form-hint" id="phoneHint">{{ $suPhone['hint'] ?? '' }}</p>
                    @error('phone') <p class="form-error" id="phoneErr">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="form-label">Email address</label>
                    {{-- The landing page hands its email field over (?email=). --}}
                    <input id="email" name="email" type="email" inputmode="email" value="{{ old('email', is_string(request('email')) ? mb_substr(request('email'), 0, 190) : '') }}" @error('email') aria-invalid="true" aria-describedby="emailErr" @enderror
                        class="form-input" placeholder="you@example.com" required autocomplete="email" autocapitalize="off" spellcheck="false">
                    @error('email')
                        <p class="form-error" id="emailErr">{{ $message }}
                            {{-- The address is already a member's: the way in is one tap. --}}
                            @if (str_contains(strtolower($message), 'log in'))
                                <a href="{{ route('login') }}">Log in</a> or <a href="{{ route('password.request') }}">reset your password</a>.
                            @endif
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" @error('password') aria-invalid="true" @enderror
                        class="form-input" required autocomplete="new-password" minlength="8" aria-describedby="passwordHint @error('password') passwordErr @enderror">
                    {{-- Said under the field, where it stays while typing (a
                         placeholder is gone at the first letter). --}}
                    <p class="form-hint" id="passwordHint">At least 8 characters.</p>
                    @error('password') <p class="form-error" id="passwordErr">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Type the password again</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="form-input" required autocomplete="new-password">
                    <label class="su-show mt-1">
                        <input type="checkbox" id="suShow" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Show the password
                    </label>
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full">Create my free account</button>
                <p class="text-center text-sm text-gray-500">We will email you a link. Tap it to open your account.</p>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="inline-block py-2 font-bold text-brand-700 hover:underline">Log in</a>
        </p>
    </div>
</div>
<script>
    (() => {
        const form = document.getElementById('signupForm');
        const phone = document.getElementById('phone');

        // The phone rule follows the country the moment it changes.
        document.getElementById('signupCountry')?.addEventListener('country:change', (e) => {
            const r = e.detail && e.detail.rules;
            if (!r) return;
            const h = document.getElementById('phoneHint');
            if (phone) phone.placeholder = r.phone.placeholder || '';
            if (h) h.textContent = r.phone.hint || '';
        });

        // A Philippine number typed the way it is said (+63 917 123 4567,
        // 63917..., 917..., with brackets or dots) is put in the 09 form the
        // server checks for, so a right number is never refused for its shape.
        const isPh = () => (form?.querySelector('[name="country"]')?.value || 'PH') === 'PH';
        const tidy = () => {
            if (!phone || !isPh()) return;
            const d = phone.value.replace(/[^\d+]/g, '');
            const m = d.match(/^\+?63(9\d{9})$/) || d.match(/^(9\d{9})$/);
            if (m) phone.value = '0' + m[1];
            else if (/^09\d{9}$/.test(d)) phone.value = d;
        };
        phone?.addEventListener('blur', tidy);
        form?.addEventListener('submit', tidy);

        // Both password boxes show their letters together, for a thumb on a
        // small keyboard.
        document.getElementById('suShow')?.addEventListener('change', (e) => {
            ['password', 'password_confirmation'].forEach((id) => {
                const f = document.getElementById(id);
                if (f) f.type = e.target.checked ? 'text' : 'password';
            });
        });

        // After a refused try, the first field to fix gets the caret.
        const bad = form?.querySelector('[aria-invalid="true"]');
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
