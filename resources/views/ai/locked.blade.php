@extends('layouts.app')

@section('title', 'AI Technician')
@section('page-title', 'AI Technician')
@section('back', route('app.dashboard'))

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card p-8 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4 text-3xl">🤖</div>
        {{-- A worker is not the one who buys: the plan that shut this door
             belongs to the farm they are standing in, so they are told whose
             decision it is and the shop button stays away. --}}
        @php $__lockedGrant = \App\Support\WorkerContext::activeGrant(); @endphp
        @if ($__lockedGrant)
            <h2 class="text-xl font-bold text-gray-900" style="font-family:var(--font-heading)">Not on this farm's plan</h2>
            <p class="text-sm text-gray-500 mt-2">
                The AI Technician is not part of the plan
                <strong>{{ optional($__lockedGrant->boss)->full_name ?: 'this farm' }}</strong> is on.
                Only the farm owner can change that — mention it to them if you need it for the work.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('app.dashboard') }}" class="btn btn-white">Back to dashboard</a>
            </div>
        @else
            <h2 class="text-xl font-bold text-gray-900" style="font-family:var(--font-heading)">AI Technician is a Boss feature</h2>
            <p class="text-sm text-gray-500 mt-2">
                Your <strong class="capitalize">{{ $tier === 'none' ? 'current' : $tier }}</strong> plan doesn't include the AI Technician.
                Upgrade to <strong>Boss</strong> or <strong>Lifetime</strong> to ask the AI about your crops and buy AI credits.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('account.subscription') }}" class="btn btn-primary">See plans</a>
                <a href="{{ route('app.dashboard') }}" class="btn btn-white">Back to dashboard</a>
            </div>
        @endif
    </div>
</div>
@endsection
