@extends('layouts.app')
@section('title', 'Shop')
@section('page-title', 'Shop')
@section('page-subtitle', 'Everything the season needs, in one place')

@section('content')
<style>
    /* One page, one promise. The storefront breathes the way the schedule
       page's green door does — the same tide, so the app stays one app. */
    .shop-hero { position: relative; overflow: hidden; border-radius: 1.25rem; padding: 2.5rem 1.5rem 2.25rem;
        text-align: center; color: #fff;
        background: linear-gradient(130deg, #7bb24a, #4a7c2a 35%, #2d5016 70%, #6b9f3d);
        background-size: 240% 100%; animation: shopTide 7s ease-in-out infinite alternate; }
    @keyframes shopTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .shop-hero-ico { width: 4.25rem; height: 4.25rem; margin: 0 auto 1rem; border-radius: 1.1rem;
        background: rgb(255 255 255 / .16); display: flex; align-items: center; justify-content: center;
        animation: shopBob 3.2s ease-in-out infinite; }
    .shop-hero-ico svg { width: 2.4rem; height: 2.4rem; }
    @keyframes shopBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    .shop-soon { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1.25rem;
        padding: .5rem 1.1rem; border-radius: 999px; font-weight: 800; font-size: .85rem;
        background: rgb(255 255 255 / .92); color: #2d5016; }
    .shop-soon i { width: .55rem; height: .55rem; border-radius: 999px; background: #4a7c2a;
        animation: shopPulse 1.8s ease-in-out infinite; }
    @keyframes shopPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .5; } }
    .shop-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-top: 1.25rem; }
    @media (min-width: 768px) { .shop-grid { grid-template-columns: repeat(4, 1fr); } }
    .shop-tile { display: flex; flex-direction: column; align-items: center; gap: .5rem;
        padding: 1.1rem .75rem; border-radius: 1rem; background: #fff; border: 1px solid #e5e7eb;
        color: #374151; font-weight: 700; font-size: .82rem; text-align: center; }
    .shop-tile span:first-child { font-size: 1.6rem; }
    .shop-tile i { font-style: normal; font-weight: 500; font-size: .72rem; color: #9ca3af; }
    html.dark .shop-tile { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .shop-tile i { color: #8aa172; }
    @media (prefers-reduced-motion: reduce) {
        .shop-hero, .shop-hero-ico, .shop-soon i { animation: none; }
    }
</style>

<div class="max-w-3xl mx-auto">
    <div class="shop-hero">
        <div class="shop-hero-ico">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.2-4.2A2 2 0 016.1 3.5h11.8a2 2 0 011.9 1.3L21 9M3 9v10a2 2 0 002 2h14a2 2 0 002-2V9M3 9h18M9 21v-6a2 2 0 012-2h2a2 2 0 012 2v6"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold mb-2">The anee Shop</h1>
        <p class="text-sm opacity-90 max-w-md mx-auto leading-relaxed">
            This is where you will get all you need for the season — seeds and
            fertilizer, tools and safety gear, delivered against the schedule
            you are already keeping here.
        </p>
        <span class="shop-soon"><i></i> Coming soon</span>
    </div>

    {{-- A taste of the shelves, greyed until the doors open. --}}
    <div class="shop-grid">
        <div class="shop-tile"><span>🌾</span>Seeds &amp; varieties<i>Certified, by crop</i></div>
        <div class="shop-tile"><span>🧂</span>Fertilizer &amp; inputs<i>Priced per bag</i></div>
        <div class="shop-tile"><span>🛠️</span>Tools &amp; equipment<i>From bolo to pump</i></div>
        <div class="shop-tile"><span>🧤</span>Safety gear<i>For every activity</i></div>
    </div>

    <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
        We will say so here — and in your notices — the day the doors open.
    </p>
</div>
@endsection
