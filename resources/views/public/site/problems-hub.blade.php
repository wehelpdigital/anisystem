@extends('layouts.public')

{{-- /problems (2026-10-06): the front door to the three sections that used
     to be one. Each door shows its section's picture, its first guides and
     how many it holds. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $all = \App\Support\SitePages::SECTIONS;
    $doors = [
        'pests' => ['hue' => 28, 'icon' => 'M12 8a3 3 0 100-6 3 3 0 000 6zm0 0v13m-6-9h12M7 7L4 4m13 3l3-3M6 16l-3 3m15-3l3 3M8 12a4 4 0 008 0', 'go' => 'See every crop pest'],
        'diseases' => ['hue' => 350, 'icon' => 'M12 21c-4.4 0-8-3.4-8-8 0-6 8-10 8-10s8 4 8 10c0 4.6-3.6 8-8 8zm-2-9h.01M14 15h.01M14 10h.01', 'go' => 'See every crop disease'],
        'weeds' => ['hue' => 98, 'icon' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15', 'go' => 'See every weed'],
    ];
    // A page that is one pest, disease or weed of a catalogue (not a guide).
    $isEntry = fn ($sec, $p) => $sec === 'weeds'
        ? (bool) \App\Support\WeedCatalogue::get($p->slug)
        : (bool) \App\Support\ProblemCatalogue::get($sec, $p->slug);
    $nouns = ['pests' => 'pests', 'diseases' => 'diseases', 'weeds' => 'weeds'];
    // A door's picture: the first page in it that has one. The pests and
    // diseases doors show a pest and a disease (their catalogue's first, as
    // their hub does), not a guide's farmer, so each door says what it holds.
    $pic = function ($pages, $sec) use ($S) {
        if ($sec !== 'weeds') {
            $order = array_flip(array_keys(\App\Support\ProblemCatalogue::entries($sec)));
            $pages = $pages->sortBy(fn ($p) => $order[$p->slug] ?? PHP_INT_MAX);
        }
        foreach ($pages as $p) {
            $h = is_array($p->heroImage) ? $p->heroImage : [];
            if ($src = $S::img($h['thumb'] ?? ($h['src'] ?? null))) {
                return [$src, $h['alt'] ?? $p->title];
            }
        }
        return [asset('images/site/fields-aerial.jpg'), ''];
    };
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    {{-- @@context: a bare @context is a Blade directive, and printed PHP into this JSON. --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => collect($groups)->keys()->map(fn ($s) => ['@type' => 'CollectionPage', 'name' => $all[$s]['hubTitle'], 'url' => $S::url($s)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <style>
        .pb-doors { display: grid; gap: 1.25rem; }
        @media (min-width: 900px) { .pb-doors { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .pb-door { --h: 98; display: flex; flex-direction: column; overflow: hidden; border-radius: 1.4rem; background: #fff; border: 1px solid #e5ebdf;
            box-shadow: 0 24px 48px -40px rgb(20 33 12 / .7); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .pb-door:hover { transform: translateY(-4px); box-shadow: 0 30px 56px -38px rgb(20 33 12 / .7); }
        .pb-pic { position: relative; display: block; aspect-ratio: 16 / 10; overflow: hidden; background: hsl(var(--h) 40% 90%); }
        .pb-pic img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .pb-door:hover .pb-pic img { transform: scale(1.04); }
        .pb-pic::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 45%, rgb(10 18 6 / .7)); }
        .pb-pic b { position: absolute; z-index: 1; left: 1.1rem; bottom: .9rem; right: 1.1rem; display: flex; align-items: center; gap: .6rem;
            font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; color: #fff; }
        .pb-ico { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: grid; place-items: center; color: hsl(var(--h) 60% 28%); background: hsl(var(--h) 70% 94%); }
        .pb-ico svg { width: 1.35rem; height: 1.35rem; }
        .pb-in { padding: 1.1rem 1.2rem 1.25rem; display: flex; flex-direction: column; gap: .8rem; flex: 1; }
        .pb-in > p { font-size: .93rem; line-height: 1.6; color: #4b5563; }
        .pb-in ul { display: grid; gap: 0; }
        .pb-in li a { display: flex; align-items: center; gap: .4rem; min-height: 2.5rem; padding: .25rem 0; font-size: .92rem; font-weight: 700; color: #3d6823; text-decoration: none; }
        .pb-in li a:hover { text-decoration: underline; }
        .pb-in li svg { flex: none; width: .85rem; height: .85rem; color: #86b556; }
        .pb-go { margin-top: auto; display: inline-flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .7rem 1rem; border-radius: .9rem;
            font-weight: 800; font-size: .92rem; color: #fff; background: #2d5016; text-decoration: none; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .pb-go:hover { background: #3d6823; }
        .pb-go { flex-wrap: wrap; row-gap: .15rem; }
        .pb-go small { font-size: .75rem; font-weight: 700; color: #cfe0bd; white-space: nowrap; }
        @media (prefers-reduced-motion: reduce) { .pb-door, .pb-pic img, .pb-go { transition: none; } }
    </style>
@endpush

@section('content')
    <section class="sp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-10 sm:pt-14">
            <nav class="sp-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span>{{ $meta['crumb'] }}</span>
            </nav>
            <div class="mt-5 max-w-3xl">
                <span class="sp-chip">{{ $meta['kicker'] }}</span>
                <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="pb-doors">
                @foreach ($groups as $sec => $pages)
                    @php([$src, $alt] = $pic($pages, $sec))
                    <div class="pb-door" style="--h: {{ $doors[$sec]['hue'] }}">
                        <a href="{{ $S::url($sec) }}" class="pb-pic">
                            <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
                            <b><span class="pb-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $doors[$sec]['icon'] }}"/></svg></span>{{ $all[$sec]['label'] }}</b>
                        </a>
                        <div class="pb-in">
                            <p>{{ $all[$sec]['intro'] }}</p>
                            <ul>
                                {{-- Each door lists its guides first, then the first of its catalogue. --}}
                                @foreach ($pages->sortBy(fn ($p) => $isEntry($sec, $p) ? 1 : 0)->take(4) as $p)
                                    <li><a href="{{ $S::pageUrl($p) }}"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>{{ $S::shortTitle($p) }}</a></li>
                                @endforeach
                            </ul>
                            {{-- The counts the hub itself shows: "160 pests" (every crop's), then the guides apart. --}}
                            @php($nItems = $sec === 'weeds' ? count(\App\Support\FieldCatalogue::weeds()) : count(\App\Support\FieldCatalogue::problems($sec)))
                            @php($nGuides = $pages->filter(fn ($p) => ! $isEntry($sec, $p))->count())
                            <a href="{{ $S::url($sec) }}" class="pb-go">{{ $doors[$sec]['go'] }} <small>@if ($nItems){{ $nItems }} {{ $nouns[$sec] }}, @endif{{ $nGuides }} {{ $nGuides === 1 ? 'guide' : 'guides' }}</small></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">See something wrong in the field?</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Ask Anee Before You Buy a Spray</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
                Send a photo of the leaf, the insect or the weed. Anee, the smart farm technician in anee.io, reads it with your lot, its age and the weather,
                and tells you what it most likely is and what to do. In Tagalog or English.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ url('/features/ai-agricultural-technician') }}" class="btn btn-lg btn-on-dark">Meet Anee</a>
            </div>
        </div>
    </section>
@endsection
