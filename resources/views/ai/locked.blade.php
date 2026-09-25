@extends('layouts.app')

@section('title', 'AI Technician')
@section('page-title', 'AI Technician')
@section('back', route('app.dashboard'))

@push('head')
<style>
    /* Anee's face, the same crying clip the upgrade sheet wears, in the ring
       the analysis loaders frame her in. Reduced motion shows the still. */
    .al-face { position: relative; width: 7.5rem; height: 7.5rem; margin: 0 auto 1.1rem;
        animation: alFace .42s cubic-bezier(.22,1,.36,1) both; }
    @keyframes alFace { from { transform: scale(.85); opacity: 0; } to { transform: none; opacity: 1; } }
    .al-ring { position: absolute; inset: 0; border-radius: 999px; overflow: hidden; background: #cfe3bd;
        box-shadow: 0 0 0 5px var(--color-white), 0 18px 40px -16px rgb(40 70 15 / .55); }
    .al-ring video, .al-ring img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
    .al-ring video::-webkit-media-controls, .al-ring video::-webkit-media-controls-enclosure { display: none !important; }
    .al-ring .al-still { display: none; }
    .al-halo { position: absolute; inset: -6px; border-radius: 999px; pointer-events: none; animation: alHalo 2.4s ease-in-out infinite; }
    @keyframes alHalo { 0%, 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / .35); } 50% { box-shadow: 0 0 0 14px rgb(107 159 61 / 0); } }
    html.dark .al-ring { background: #2f4d24; box-shadow: 0 0 0 5px var(--color-white), 0 18px 40px -16px rgb(0 0 0 / .7); }
    @media (prefers-reduced-motion: reduce) {
        .al-face, .al-halo { animation: none; }
        .al-ring video { display: none; }
        .al-ring .al-still { display: block; }
    }
</style>
@endpush

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card p-8 text-center">
        <div class="al-face" aria-hidden="true">
            <span class="al-halo"></span>
            <span class="al-ring">
                <video src="{{ asset('videos/anee/crying.mp4') }}" poster="{{ asset('videos/anee/crying.jpg') }}"
                       muted autoplay loop playsinline preload="auto" disablepictureinpicture disableremoteplayback tabindex="-1"></video>
                <img class="al-still" src="{{ asset('videos/anee/crying.jpg') }}" alt="">
            </span>
        </div>
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
            @php
                /* The rung that opens Anee for this plan -- worked out from
                   config/tiers.php, the same answer the upgrade sheet gives. */
                $__rung = \App\Support\Tier::unlocksAt('ai', $tier);
                $__rungName = \App\Support\Tier::planName($__rung);
                $__rungPrice = \App\Support\Region::tierPrice($__rung);
            @endphp
            <h2 class="text-xl font-bold text-gray-900" style="font-family:var(--font-heading)">Anee comes with {{ $__rungName }}</h2>
            <p class="text-sm text-gray-500 mt-2">
                Your <strong>{{ config('tiers.' . $tier . '.name') ?? ucfirst($tier) }}</strong> plan does not include Anee.
                @if ($__rung === 'libreAnee')
                    <strong>{{ $__rungName }}</strong> is your plan exactly as it is, plus the whole of Anee — the chat, the analyses,
                    Realign and the credit shop{{ $__rungPrice !== null ? ' — for ' . \App\Support\Region::priceTag($__rungPrice) . ' a month' : '' }}.
                @else
                    <strong>{{ $__rungName }}</strong> brings the whole of Anee — the chat, the analyses, Realign and the credit shop{{ $__rungPrice !== null ? ' — for ' . \App\Support\Region::priceTag($__rungPrice) . ' a month' : '' }}.
                @endif
                Every plan above it has Anee too.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                @if ($__rung === 'libreAnee')
                    <a href="{{ route('purchase.plans', ['plan' => 'libre-anee']) }}" class="btn btn-primary">Add Anee</a>
                    <a href="{{ route('account.subscription') }}" class="btn btn-white">See all plans</a>
                @else
                    <a href="{{ route('account.subscription') }}" class="btn btn-primary">See {{ $__rungName }}</a>
                @endif
                <a href="{{ route('app.dashboard') }}" class="btn btn-white">Back to dashboard</a>
            </div>
        @endif
    </div>
</div>
@endsection
