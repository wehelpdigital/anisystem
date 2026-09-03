@extends('layouts.app')
@section('title', 'Shop')
@section('page-title', 'Shop')
@section('page-subtitle', 'Everything the season needs, in one place')

@section('content')
<style>
    /* One page, one promise. The storefront breathes the way the schedule
       page's green door does — the same tide, so the app stays one app. */
    /* The tip-of-the-day's deep green, worn by the storefront: the same
       near-black gradient with a slow sweep of light across it. */
    .shop-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 1.15rem 1rem 1.25rem;
        text-align: center; color: #e8efe1;
        background: linear-gradient(135deg, #10160c 0%, #1c2416 55%, #24301a 100%);
        box-shadow: 0 16px 40px -30px rgb(16 22 12 / .9); }
    @media (min-width: 640px) { .shop-hero { padding: 1.6rem 1.5rem 1.7rem; } }
    .shop-glow { position: absolute; inset: -40% -10%; pointer-events: none;
        background: radial-gradient(closest-side, rgb(134 181 86 / .35), transparent 70%);
        animation: shopGlow 7s ease-in-out infinite; }
    @keyframes shopGlow {
        0%, 100% { transform: translateX(-30%) scale(.9); opacity: .5; }
        50% { transform: translateX(30%) scale(1.1); opacity: .85; }
    }
    .shop-hero-ico { position: relative; width: 3rem; height: 3rem; margin: 0 auto .6rem; border-radius: .85rem;
        background: rgb(255 255 255 / .1); display: flex; align-items: center; justify-content: center;
        animation: shopBob 3.2s ease-in-out infinite; }
    .shop-hero-ico svg { width: 1.7rem; height: 1.7rem; }
    @keyframes shopBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    .shop-soon { position: relative; display: inline-flex; align-items: center; gap: .5rem; margin-top: .7rem;
        padding: .4rem .95rem; border-radius: 999px; font-weight: 800; font-size: .8rem;
        background: rgb(255 255 255 / .92); color: #2d5016; }
    .shop-soon i { width: .55rem; height: .55rem; border-radius: 999px; background: #4a7c2a;
        animation: shopPulse 1.8s ease-in-out infinite; }
    @keyframes shopPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .5; } }
    .shop-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-top: 1rem; }
    @media (min-width: 768px) { .shop-grid { grid-template-columns: repeat(3, 1fr); } }
    .shop-tile { display: flex; flex-direction: column; align-items: center; gap: .5rem;
        padding: 1.1rem .75rem; border-radius: 1rem; background: #fff; border: 1px solid #e5e7eb;
        color: #374151; font-weight: 700; font-size: .82rem; text-align: center; }
    .shop-tile span:first-child { font-size: 1.6rem; }
    .shop-tile i { font-style: normal; font-weight: 500; font-size: .72rem; color: #9ca3af; }
    html.dark .shop-tile { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .shop-tile i { color: #8aa172; }
    @media (prefers-reduced-motion: reduce) {
        .shop-hero-ico, .shop-soon i, .shop-glow { animation: none; }
    }
</style>

<div class="max-w-3xl mx-auto">
    <div class="shop-hero">
        <span class="shop-glow" aria-hidden="true"></span>
        <div class="shop-hero-ico">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.2-4.2A2 2 0 016.1 3.5h11.8a2 2 0 011.9 1.3L21 9M3 9v10a2 2 0 002 2h14a2 2 0 002-2V9M3 9h18M9 21v-6a2 2 0 012-2h2a2 2 0 012 2v6"/></svg>
        </div>
        <h1 class="relative text-xl sm:text-2xl font-extrabold">The Anee Shop</h1>
        <span class="shop-soon"><i></i> Coming soon</span>
    </div>

    {{-- A taste of the shelves, greyed until the doors open. --}}
    <div class="shop-grid">
        <div class="shop-tile"><span>🌾</span>Seeds &amp; varieties<i>Certified, by crop</i></div>
        <div class="shop-tile"><span>🧂</span>Fertilizer &amp; inputs<i>Priced per bag</i></div>
        <div class="shop-tile"><span>🛠️</span>Tools &amp; equipment<i>From bolo to pump</i></div>
        <div class="shop-tile"><span>🧤</span>Safety gear<i>For every activity</i></div>
        <div class="shop-tile"><span>📖</span>Cheat Sheets<i>Short how-tos that pay for themselves</i></div>
        <div class="shop-tile"><span>👕</span>Fashion<i>Farm wear that works</i></div>
    </div>

    <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
        We will say so here — and in your notices — the day the doors open.
    </p>
</div>
@endsection
