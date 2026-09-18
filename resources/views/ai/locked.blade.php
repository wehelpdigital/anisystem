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
            @php $__anee = config('tiers.libreAnee'); @endphp
            <h2 class="text-xl font-bold text-gray-900" style="font-family:var(--font-heading)">Anee comes with Libre + Anee</h2>
            <p class="text-sm text-gray-500 mt-2">
                Your <strong>{{ config('tiers.' . $tier . '.name') ?? ucfirst($tier) }}</strong> plan does not include Anee.
                <strong>Libre + Anee</strong> is your plan exactly as it is, plus the whole of Anee — the chat, the analyses,
                Realign and the credit shop — for {{ \App\Support\Region::priceTag(\App\Support\Region::tierPrice('libreAnee')) }} a month.
                Every plan above it has Anee too.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('purchase.plans', ['plan' => 'libre-anee']) }}" class="btn btn-primary">Add Anee</a>
                <a href="{{ route('account.subscription') }}" class="btn btn-white">See all plans</a>
                <a href="{{ route('app.dashboard') }}" class="btn btn-white">Back to dashboard</a>
            </div>
        @endif
    </div>
</div>
@endsection
