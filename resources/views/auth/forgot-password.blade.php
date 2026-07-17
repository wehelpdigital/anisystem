@extends('layouts.public')

@section('title', 'Forgot Password')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Forgot your password?</h1>
            <p class="text-sm text-gray-500 mt-1">Enter your email and we will send you a link to reset it.</p>
        </div>

        <div class="card card-body">
            @if (session('success'))
                <div class="rounded-xl bg-brand-50 border border-brand-200 text-brand-800 text-sm px-4 py-3 mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="form-input" placeholder="you@example.com" required autofocus autocomplete="email">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full">Send Reset Link</button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Remembered it?
            <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline">Back to log in</a>
        </p>
    </div>
</div>
@endsection
