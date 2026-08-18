@extends('layouts.public')

@section('title', 'Log In')

{{-- The only public page that wears the saved theme. Declared at the top level
     rather than inside @section('content') because the layout tests this flag
     in <head>, long before the content section is yielded. --}}
@section('honours-theme-cookie', true)

@push('head')
<style>
    /* The spinner grows out of the label instead of popping in, so the text
       sliding left reads as one motion. The negative end-margin cancels .btn's
       own gap while the spinner is collapsed, leaving the idle button pixel
       identical to before. */
    #loginSubmit .login-spin {
        flex: none;
        width: 0;
        height: 1.25rem;
        opacity: 0;
        margin-inline-end: -0.5rem;
        transition: width .28s cubic-bezier(.22,1,.36,1),
                    opacity .28s cubic-bezier(.22,1,.36,1),
                    margin-inline-end .28s cubic-bezier(.22,1,.36,1);
    }
    #loginSubmit.is-busy .login-spin {
        width: 1.25rem;
        opacity: 1;
        margin-inline-end: 0;
        animation: loginSpin .9s linear infinite;
    }
    @keyframes loginSpin { to { transform: rotate(360deg); } }
    /* Busy is not the same as unavailable, so the button keeps its full weight
       while it works — .btn's disabled fade would leave the spinner at roughly
       a quarter alpha on yellow. The disabled attribute itself stays: that is
       what actually blocks a second submit. */
    #loginSubmit.is-busy:disabled { opacity: 1; color: #1a1a1a; }
    /* Reduced motion drops the grow-in travel but only slows the spin — a
       frozen spinner reads as a hung request rather than a working one. */
    @media (prefers-reduced-motion: reduce) {
        #loginSubmit .login-spin { transition: none; }
        #loginSubmit.is-busy .login-spin { animation-duration: 2.4s; }
    }
</style>
@endpush

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-sm text-gray-500 mt-1">Log in to manage your cropping schedules.</p>
        </div>

        <div class="card card-body">
            <form id="loginForm" method="POST" action="{{ route('login.attempt') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="form-input" placeholder="you@example.com" required autofocus autocomplete="email">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-700 hover:underline">Forgot password?</a>
                    </div>
                    <input id="password" name="password" type="password"
                        class="form-input" placeholder="••••••••" required autocomplete="current-password">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm text-gray-700 select-none cursor-pointer py-1">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Keep me logged in
                </label>

                <button type="submit" id="loginSubmit" class="btn btn-accent btn-lg w-full">
                    <svg class="login-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span data-login-label>Log In</span>
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            No account yet?
            <a href="{{ route('signup') }}" class="font-bold text-brand-700 hover:underline">Create one for free</a>
        </p>

        @if (app()->environment('local'))
            {{-- Quick-fill test logins — only rendered in the local environment. --}}
            <div class="mt-6 rounded-2xl border border-dashed border-brand-300 bg-brand-50/70 p-4"
                 x-data="{ fill(email, password) {
                     document.getElementById('email').value = email;
                     document.getElementById('password').value = password;
                     document.getElementById('password').focus();
                 } }">
                <div class="flex items-center gap-2 mb-3">
                    <span class="badge badge-yellow">Test logins</span>
                    <span class="text-xs text-gray-500">Local preview only</span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800">Member</p>
                            <p class="text-xs text-gray-500 truncate">demo@anisystem.test · demo1234</p>
                        </div>
                        <button type="button" @click="fill('demo@anisystem.test', 'demo1234')" class="btn btn-outline btn-sm shrink-0">Fill</button>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800">Super admin <span class="font-normal text-gray-400">· mother site</span></p>
                            <p class="text-xs text-gray-500 truncate">admin@themesbrand.com · 12345678</p>
                        </div>
                        <button type="button" @click="fill('admin@themesbrand.com', '12345678')" class="btn btn-outline btn-sm shrink-0">Fill</button>
                    </div>
                    {{-- Kathleen wears more than one hat, which is what makes
                         her the account worth testing the chooser with. The
                         password is not written here because nobody told this
                         file what it is — the row fills the address and puts
                         the caret where the password goes. --}}
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800">Kathleen <span class="font-normal text-gray-400">· admin + farm owner</span></p>
                            <p class="text-xs text-gray-500 truncate">kathleen.madriaga@gmail.com · type the password</p>
                        </div>
                        <button type="button" @click="fill('kathleen.madriaga@gmail.com', '')" class="btn btn-outline btn-sm shrink-0">Fill</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('loginForm');
        const btn = document.getElementById('loginSubmit');
        if (!form || !btn) return;
        const label = btn.querySelector('[data-login-label]');

        form.addEventListener('submit', (e) => {
            // A second Enter or tap while the POST is in flight would log the
            // attempt twice and eat a slot of the login throttle, so the flag
            // is checked before anything else — it beats the disabled
            // attribute, which cannot land until after this handler returns.
            if (btn.dataset.busy === '1') { e.preventDefault(); return; }
            btn.dataset.busy = '1';
            btn.classList.add('is-busy');
            btn.setAttribute('aria-busy', 'true');
            if (label) label.textContent = 'Signing in…';
            // Deferred: a submit button disabled inside its own submit handler
            // can cancel the very submission it is reporting on.
            setTimeout(() => { btn.disabled = true; }, 0);
        });

        // Back-button restores the page mid-spin from the bfcache, disabled
        // button and all; without this the form is dead on arrival.
        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            delete btn.dataset.busy;
            btn.disabled = false;
            btn.classList.remove('is-busy');
            btn.removeAttribute('aria-busy');
            if (label) label.textContent = 'Log In';
        });
    })();
</script>
@endpush
