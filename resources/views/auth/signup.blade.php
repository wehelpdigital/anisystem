@extends('layouts.public')

@section('title', 'Create an Account')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" class="h-9 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Create your free account</h1>
            <p class="text-sm text-gray-500 mt-1">The Libre plan is free forever — no card, no trial clock.</p>
        </div>

        <div class="card card-body">
            @include('auth.partials.google-button')

            <form method="POST" action="{{ route('signup.attempt') }}" class="space-y-4" novalidate>
                @csrf
                @if ($plan)
                    <input type="hidden" name="plan" value="{{ $plan }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="form-label">First name</label>
                        <input id="firstName" name="firstName" type="text" value="{{ old('firstName') }}"
                            class="form-input" placeholder="{{ \App\Support\Region::t('firstNameExample') }}" required autofocus autocomplete="given-name">
                        @error('firstName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="form-label">Last name</label>
                        <input id="lastName" name="lastName" type="text" value="{{ old('lastName') }}"
                            class="form-input" placeholder="{{ \App\Support\Region::t('lastNameExample') }}" required autocomplete="family-name">
                        @error('lastName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Where the farm is. Pre-picked from where the visitor is
                     reading from; the phone rule below follows it. --}}
                @php $suCountry = old('country') ?: \App\Support\Region::code(); $suPhone = \App\Support\Region::phone($suCountry); @endphp
                <div>
                    <label class="form-label">Country</label>
                    @include('partials.country-pick', ['id' => 'signupCountry', 'name' => 'country', 'value' => $suCountry])
                    <p class="form-hint">Sets the language, the currency and the local advice Anee gives.</p>
                    @error('country') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="form-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}"
                        class="form-input" placeholder="{{ $suPhone['placeholder'] ?? '' }}" required autocomplete="tel">
                    <p class="form-hint" id="phoneHint">{{ $suPhone['hint'] ?? '' }}</p>
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="form-input" placeholder="you@example.com" required autocomplete="email">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password"
                        class="form-input" placeholder="At least 8 characters" required autocomplete="new-password">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="form-input" placeholder="Repeat your password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full">Create Free Account</button>
                <p class="text-center text-xs text-gray-400">We'll email you a confirmation link before your first login.</p>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline">Log in</a>
        </p>
    </div>
</div>
<script>
    // The phone rule follows the country the moment it changes.
    document.getElementById('signupCountry')?.addEventListener('country:change', (e) => {
        const r = e.detail && e.detail.rules;
        if (!r) return;
        const p = document.getElementById('phone'), h = document.getElementById('phoneHint');
        if (p) p.placeholder = r.phone.placeholder || '';
        if (h) h.textContent = r.phone.hint || '';
    });
</script>
@endsection
