@extends('layouts.public')

@section('title', 'Set your password')

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo.png') }}?v=anee" alt="anee.io" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Join {{ $bossName }}'s farm</h1>
            <p class="text-sm text-gray-500 mt-1">Set a password for <strong>{{ $email }}</strong> to get started.</p>
        </div>

        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-3 py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('worker.invite.accept', ['token' => $token]) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="form-label" for="firstName">First name</label>
                            <input type="text" id="firstName" name="firstName" class="form-input" required maxlength="100" value="{{ old('firstName') }}">
                        </div>
                        <div>
                            <label class="form-label" for="lastName">Last name</label>
                            <input type="text" id="lastName" name="lastName" class="form-input" maxlength="100" value="{{ old('lastName') }}">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required minlength="8" placeholder="At least 8 characters">
                    </div>
                    <div>
                        <label class="form-label" for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Set password &amp; log in</button>
                </form>
            </div>
        </div>

        <p class="text-center text-sm text-gray-500 mt-4">
            Already have an account? <a href="{{ route('login') }}" class="text-brand-600 font-semibold">Log in</a>
        </p>
    </div>
</div>
@endsection
