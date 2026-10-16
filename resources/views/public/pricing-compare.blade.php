@extends('layouts.public')

{{-- Pricing comparison (2026-10-01): every plan side by side, a row per
     limit or tool, read off config/tiers.php by App\Support\PlanCompare
     (the numbers the app's gates read). A desk shows all the plans; a phone
     picks two to set against each other. The header row stays on screen
     while the rows scroll under it. --}}
@include('public.partials.site-css')

@section('title', 'Pricing Comparison: Every anee.io Plan Side by Side')
@section('meta_description', 'Compare anee.io plans side by side: seasons, lots, Anee the smart farm technician, the field helpers, NPK Plus, Satellite Weather, workers, reports and storage. Libre is free forever.')

@push('head')
    <link rel="canonical" href="{{ route('pricing.compare') }}">
@endpush

@php
    // The table is the shared partial (partials/plan-compare, also on the app's
    // My Subscription); the hero and the call to action only need the free plan.
    $pcPlans = \App\Support\PlanCompare::plans();
    $pcFree = collect($pcPlans)->search(fn ($t) => empty($t['price']));
@endphp

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 spark-field">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center animate-fade-up" style="z-index:1">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Compare plans</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Every Plan, Side by Side</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                Every limit below is the same one the app uses. {{ $pcFree !== false ? $pcPlans[$pcFree]['name'] : 'Libre' }} is free forever.
                The other plans are paid in {{ \App\Support\Region::currencyName() }} through {{ \App\Support\Region::payMethod() }}.
            </p>
            <a href="{{ route('pricing') }}" class="pc-back">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6-6l-6 6 6 6"/></svg>
                See the plans as cards
            </a>
        </div>
    </section>

    {{-- ================= THE TABLE ================= --}}
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            @include('partials.plan-compare')

            <div class="pc-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg pc-go">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    Start for free forever on {{ $pcFree !== false ? $pcPlans[$pcFree]['name'] : 'Libre' }}
                </a>
                <p>No payment. No trial that runs out. Upgrade inside the app when your farm needs more.</p>
            </div>
        </div>
    </section>

@endsection

@push('head')
<style>
    .pc-back { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1.4rem; padding: .55rem 1rem; border-radius: 999px;
        font-size: .9rem; font-weight: 800; color: #fff; border: 1.5px solid rgb(255 255 255 / .3); transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pc-back:hover { background: rgb(255 255 255 / .1); }
    .pc-back svg { width: 1rem; height: 1rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .pc-back:hover svg { transform: translateX(-3px); }

    .pc-cta { margin-top: 2.4rem; display: flex; flex-direction: column; align-items: center; gap: .7rem; text-align: center; }
    .pc-go { gap: .55rem; box-shadow: 0 18px 36px -18px rgb(199 158 0 / .85); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .pc-go:hover { transform: translateY(-2px); }
    .pc-go svg { width: 1.2rem; height: 1.2rem; }
    .pc-cta p { font-size: .9rem; font-weight: 700; color: #4a7c2a; }
    @media (max-width: 479.98px) { .pc-go { width: 100%; justify-content: center; } }

    @media (prefers-reduced-motion: reduce) {
        .pc-back, .pc-back svg, .pc-go { animation: none !important; transition: none !important; }
    }
</style>
@endpush
