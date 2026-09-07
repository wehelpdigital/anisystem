@extends('layouts.public')

@section('title', 'Confirm Your Email')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="card card-body text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-brand-50 ring-1 ring-brand-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Check your inbox</h1>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                We sent a confirmation link
                @if ($email) to <span class="font-semibold text-gray-900">{{ $email }}</span>@endif.
                Tap it and your free account opens right away.
            </p>
            <p class="mt-3 text-xs text-gray-400">
                Nothing there after a minute? Look in the spam folder — or send a fresh link below.
            </p>

            <form method="POST" action="{{ route('verify.resend') }}" class="mt-6 space-y-3">
                @csrf
                @unless ($email)
                    <input name="email" type="email" class="form-input" placeholder="you@example.com" required>
                @endunless
                <button type="submit" class="btn btn-outline w-full">Resend the confirmation email</button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Wrong address?
            <a href="{{ route('signup') }}" class="font-bold text-brand-700 hover:underline">Sign up again</a>
        </p>
    </div>
</div>
@endsection
