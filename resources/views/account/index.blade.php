@extends('layouts.app')

@section('title', 'My Account')
@section('page-title', 'My Account')
@section('page-subtitle', 'Profile & security')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Profile card --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-4 mb-5">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-brand-600 text-white text-xl font-bold shrink-0">
                    {{ $user->initials }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $user->full_name }}</h2>
                    <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4" novalidate>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="form-label">First name</label>
                        <input id="firstName" name="firstName" type="text"
                            value="{{ old('firstName', $user->firstName) }}" class="form-input" required>
                        @error('firstName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="form-label">Last name</label>
                        <input id="lastName" name="lastName" type="text"
                            value="{{ old('lastName', $user->lastName) }}" class="form-input" required>
                        @error('lastName') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="phone" class="form-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric"
                        value="{{ old('phone', $user->phone) }}" class="form-input" placeholder="09XXXXXXXXX" required>
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Email address</label>
                    <input type="email" value="{{ $user->email }}" class="form-input bg-gray-50 text-gray-500" disabled>
                    <p class="form-hint">Your email cannot be changed. Contact support if you need to update it.</p>
                </div>

                <div class="pt-1">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change password card --}}
    <div class="card">
        <div class="card-body">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Change Password</h2>

            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4" novalidate>
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password"
                        class="form-input" required autocomplete="current-password">
                    @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password"
                            class="form-input" placeholder="At least 8 characters" required autocomplete="new-password">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            class="form-input" required autocomplete="new-password">
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Subscription shortcut --}}
    <a href="{{ route('account.subscription') }}" class="card card-hover block">
        <div class="card-body flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-gray-900">My Subscription</h3>
                <p class="text-sm text-gray-500">View your plan, status and payment history.</p>
            </div>
            <svg class="w-6 h-6 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
</div>
@endsection
