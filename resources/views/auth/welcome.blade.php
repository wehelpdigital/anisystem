@extends('layouts.public')

@section('title', 'Welcome to anee.io')

{{-- After a signup that began on a members' guide: where to now (Auth\WelcomeController, 2026-11-16). --}}
@push('head')
    <style>
        .wc-hello { animation: wcIn .28s cubic-bezier(.22,1,.36,1) both; }
        .wc-picks { display: grid; gap: .75rem; margin-top: 1.25rem; }
        .wc-pick { display: flex; align-items: center; gap: .9rem; padding: .95rem 1rem; border-radius: 1.1rem; text-align: left;
            background: #fff; border: 1.5px solid #e5e7eb; color: inherit; text-decoration: none;
            animation: wcIn .28s cubic-bezier(.22,1,.36,1) both;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .wc-pick:nth-child(2) { animation-delay: .06s; } .wc-pick:nth-child(3) { animation-delay: .12s; }
        .wc-pick:hover, .wc-pick:focus-visible { border-color: #4a7c2a; box-shadow: 0 6px 18px -10px rgb(45 80 22 / .45); transform: translateY(-1px); }
        .wc-ico { flex: none; width: 2.75rem; height: 2.75rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.35rem; background: #f3f8ec; }
        .wc-k { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #4a7c2a; }
        .wc-t { margin-top: .05rem; font-size: 1rem; font-weight: 800; line-height: 1.3; color: #111827; }
        .wc-s { margin-top: .2rem; font-size: .82rem; line-height: 1.45; color: #4b5563; }
        .wc-arrow { flex: none; width: 1.2rem; height: 1.2rem; color: #9ca3af; transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .wc-pick:hover .wc-arrow { transform: translateX(2px); color: #4a7c2a; }
        /* The guide: the first choice, and it looks it. */
        .wc-pick.is-main { border-color: #f5c518; background: #fffbea; }
        .wc-pick.is-main .wc-ico { background: #f5c518; }
        .wc-pick.is-main:hover, .wc-pick.is-main:focus-visible { border-color: #d9ab0b; box-shadow: 0 8px 22px -12px rgb(180 140 10 / .6); }
        .wc-go { display: inline-flex; margin-top: .6rem; padding: .45rem .9rem; border-radius: 999px; background: #f5c518; color: #1a1a1a; font-size: .85rem; font-weight: 800; }
        .wc-note { margin-top: 1rem; font-size: .78rem; line-height: 1.5; color: #6b7280; text-align: center; }
        html.dark .wc-pick { background: #111827; border-color: #374151; }
        html.dark .wc-ico { background: #142010; }
        html.dark .wc-k { color: #a8cc7e; } html.dark .wc-t { color: #f3f4f6; } html.dark .wc-s { color: #c7d2bd; }
        html.dark .wc-pick.is-main { background: #2a2306; border-color: #a8870c; }
        html.dark .wc-pick.is-main .wc-ico { background: #f5c518; }
        @keyframes wcIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .wc-hello, .wc-pick { animation: none; } .wc-pick, .wc-arrow { transition: none; } }
    </style>
@endpush

@section('content')
@php
    $arrow = '<svg class="wc-arrow" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
@endphp
<div class="bg-gray-50 py-10 md:py-16 px-4 min-h-[70vh] flex items-start justify-center">
    <div class="w-full max-w-md">
        @include('auth.partials.signup-steps', ['at' => 3, 'reading' => true])

        <div class="text-center wc-hello">
            <h1 class="text-2xl font-bold text-gray-900">Welcome to anee.io{{ $first !== '' ? ', ' . $first : '' }}!</h1>
            <p class="text-sm text-gray-600 mt-1.5">Your free Libre account is ready. Where would you like to go now?</p>
        </div>

        <div class="wc-picks">
            <a href="{{ route('signup.welcome.go', ['to' => 'read']) }}" class="wc-pick is-main">
                <span class="wc-ico" aria-hidden="true">📖</span>
                <span class="min-w-0 flex-1">
                    <span class="wc-k block">Keep reading</span>
                    <span class="wc-t block">{{ $reading['title'] }}</span>
                    <span class="wc-s block">{{ $plusOnly
                        ? 'Your free account opens the start of this guide. The whole guide comes with ' . \App\Support\Tier::planName(\App\Support\ContentGate::PLUS_PLAN) . '.'
                        : 'The whole guide is open to you now.' }}</span>
                    <span class="wc-go">Back to the guide</span>
                </span>
            </a>

            <a href="{{ route('signup.welcome.go', ['to' => 'dashboard']) }}" class="wc-pick">
                <span class="wc-ico" aria-hidden="true">🌱</span>
                <span class="min-w-0 flex-1">
                    <span class="wc-k block">My dashboard</span>
                    <span class="wc-t block">Start using anee.io</span>
                    <span class="wc-s block">Plan your first season and meet Anee, your smart farm technician.</span>
                </span>
                {!! $arrow !!}
            </a>

            <a href="{{ route('signup.welcome.go', ['to' => 'home']) }}" class="wc-pick">
                <span class="wc-ico" aria-hidden="true">🏠</span>
                <span class="min-w-0 flex-1">
                    <span class="wc-k block">anee.io home</span>
                    <span class="wc-t block">Look around first</span>
                    <span class="wc-s block">The guides, the farm tools and how anee.io works.</span>
                </span>
                {!! $arrow !!}
            </a>
        </div>

        <p class="wc-note">The guide also waits on your dashboard, so you can go back to it any time.</p>
    </div>
</div>
@endsection
