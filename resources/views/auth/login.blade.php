@extends('layouts.public')

@section('title', 'Log In')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="AniSystem" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-sm text-gray-500 mt-1">Log in to manage your cropping schedules.</p>
        </div>

        <div class="card card-body">
            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4" novalidate>
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

                <button type="submit" class="btn btn-accent btn-lg w-full">Log In</button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            No account yet?
            <a href="{{ route('signup') }}" class="font-bold text-brand-700 hover:underline">Create one for free</a>
        </p>
    </div>
</div>
@endsection
