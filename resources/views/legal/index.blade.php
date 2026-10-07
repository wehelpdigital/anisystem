@extends('layouts.public')

@section('title', 'Legal and Policies: Privacy, Terms and Cookies')
@section('meta_description', 'The anee.io Privacy Policy, Terms of Service, Cookie Policy and About page: how your farm data is kept, the rules of the app, and who we are.')

{{-- /legal (2026-10-07): the list of the footer's legal pages. It wears
     the saved theme like the pages themselves (see legal/show). --}}
@section('honours-theme-cookie', true)

@php
    $lgIcons = [
        'privacy' => 'M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3zM9 12l2 2 4-4',
        'terms' => 'M8 3h6l5 5v11a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2zM14 3v5h5M9 13h6M9 17h6',
        'cookies' => 'M12 3a9 9 0 109 9 3 3 0 01-3-3 3 3 0 01-3-3 3 3 0 01-3-3zM8.5 12.5h.01M12 16h.01M15.5 13.5h.01M9 8.5h.01',
        'about' => 'M12 21c-4-3-7-6.5-7-10.5a7 7 0 0114 0C19 14.5 16 18 12 21zM12 7v4M12 14h.01',
    ];
    $lgDoc = 'M8 3h6l5 5v11a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2zM14 3v5h5';
@endphp

@push('head')
<style>
    .lgi-page { background: var(--color-gray-50); }
    .lgi-shell { max-width: 56rem; margin: 0 auto; padding: 2rem 1rem 3.5rem; }
    @media (min-width: 768px) { .lgi-shell { padding: 3.25rem 1.5rem 5rem; } }
    .lgi-kick { display: inline-flex; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700); }
    .lgi-h { margin: .5rem 0 0; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.015em; line-height: 1.15; font-size: 1.85rem; color: var(--color-gray-900); }
    @media (min-width: 768px) { .lgi-h { font-size: 2.4rem; } }
    .lgi-lead { margin-top: .75rem; max-width: 40rem; font-size: 1rem; line-height: 1.65; color: var(--color-gray-600); }
    .lgi-grid { display: grid; gap: .9rem; margin-top: 1.75rem; }
    @media (min-width: 640px) { .lgi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.1rem; margin-top: 2.25rem; } }
    .lgi-card { display: flex; flex-direction: column; gap: .55rem; padding: 1.25rem 1.2rem 1.15rem; border-radius: 1.25rem; text-decoration: none;
        background: var(--color-white); border: 1px solid var(--color-gray-100);
        box-shadow: 0 1px 2px rgb(16 24 40 / .04), 0 8px 24px -12px rgb(16 24 40 / .12);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 768px) { .lgi-card { padding: 1.5rem 1.5rem 1.3rem; } }
    .lgi-card:hover { border-color: var(--color-brand-200); transform: translateY(-2px); box-shadow: 0 1px 2px rgb(16 24 40 / .05), 0 14px 30px -14px rgb(16 24 40 / .22); }
    .lgi-ico { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .9rem; color: var(--color-brand-700); background: var(--color-brand-50); }
    .lgi-ico svg { width: 1.35rem; height: 1.35rem; }
    .lgi-card-h { margin: .35rem 0 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.25; color: var(--color-gray-900); }
    .lgi-card-p { font-size: .92rem; line-height: 1.6; color: var(--color-gray-600); }
    .lgi-card-f { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-top: auto; padding-top: .55rem; font-size: .8rem; font-weight: 600; color: var(--color-gray-500); }
    .lgi-go { display: inline-flex; align-items: center; gap: .3rem; font-weight: 800; color: var(--color-brand-700); }
    .lgi-go svg { width: .95rem; height: .95rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lgi-card:hover .lgi-go svg { transform: translateX(3px); }
    .lgi-foot { margin-top: 1.75rem; font-size: .9rem; color: var(--color-gray-600); }
    .lgi-foot a { color: var(--color-brand-700); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    html.dark .lgi-card { border-color: var(--color-gray-200); box-shadow: 0 1px 3px rgb(0 0 0 / .4), 0 10px 28px -12px rgb(0 0 0 / .6); }
    html.dark .lgi-kick, html.dark .lgi-go, html.dark .lgi-foot a, html.dark .lgi-ico { color: var(--color-brand-800); }
    html.dark body > footer.bg-gray-900 {
        --color-gray-900: #0d1014; --color-gray-800: #262d36;
        --color-gray-500: #7d8794; --color-gray-400: #98a2ae; --color-gray-300: #ccd4dd;
    }
    html.dark body > header img[src*="images/logo.png"] { content: url('{{ asset('images/site/logo-white.png') }}?v=anee'); }
    @media (prefers-reduced-motion: reduce) { .lgi-card, .lgi-go svg { transition: none; } .lgi-card:hover { transform: none; } }
</style>
@endpush

@section('content')
<div class="lgi-page">
    <div class="lgi-shell">
        <span class="lgi-kick">anee.io</span>
        <h1 class="lgi-h">Legal and policies</h1>
        <p class="lgi-lead">How anee.io looks after your farm data, the rules for using the app, the cookies it sets, and who is behind it. Plain words, each on its own page.</p>

        <div class="lgi-grid">
            @foreach ($pages as $p)
                <a href="{{ route('legal.show', ['slug' => $p->slug]) }}" class="lgi-card">
                    <span class="lgi-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $lgIcons[$p->slug] ?? $lgDoc }}"/></svg></span>
                    <h2 class="lgi-card-h">{{ $p->title }}</h2>
                    <span class="lgi-card-p">{{ \App\Support\CommunityText::plain($p->body, 150) }}</span>
                    <span class="lgi-card-f">
                        @if ($p->updated_at)<span>Updated {{ $p->updated_at->format('F j, Y') }}</span>@else<span></span>@endif
                        <span class="lgi-go">Read <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 7l5 5-5 5M6 12h12"/></svg></span>
                    </span>
                </a>
            @endforeach
        </div>

        <p class="lgi-foot">Questions about any of these? Write to <a href="mailto:support@anee.io">support@anee.io</a> and a real person replies.</p>
    </div>
</div>
@endsection
