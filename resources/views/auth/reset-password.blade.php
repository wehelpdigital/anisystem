@extends('layouts.public')

@section('title', 'Reset Password')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Set a new password</h1>
            <p class="text-sm text-gray-500 mt-1">Choose a strong password for your AniSystem account.</p>
        </div>

        <div class="card card-body">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}"
                        class="form-input" placeholder="you@example.com" required autocomplete="email">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password"
                        class="form-input" placeholder="At least 8 characters" required autofocus autocomplete="new-password">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="form-input" placeholder="Repeat your new password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-accent btn-lg w-full">Reset Password</button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline">Back to log in</a>
        </p>
    </div>
</div>
@endsection
