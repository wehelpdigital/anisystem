@extends('layouts.public')

{{-- /weeds (2026-10-06): the weeds of Philippine rice fields. The three
     groups, the catalogue (one card per weed profile, filtered by group and
     searched by any of its names), the weed control helper by rice age, and
     the long guides. The words of each weed live on its page; the card facts
     come from App\Support\WeedCatalogue and the helper's from
     App\Support\WeedControl. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $W = \App\Support\WeedControl::class;
    $C = \App\Support\WeedCatalogue::class;
    $live = $pages->keyBy('slug');
    // The weeds, in the catalogue's order, that have a live page.
    $weeds = collect($C::WEEDS)->filter(fn ($w, $slug) => $live->has($slug))
        ->map(function ($w, $slug) use ($live, $S) {
            $h = is_array($live[$slug]->heroImage) ? $live[$slug]->heroImage : [];
            return $w + ['slug' => $slug, 'url' => $S::pageUrl($live[$slug]),
                'thumb' => $S::img($h['thumb'] ?? ($h['src'] ?? null)), 'alt' => $h['alt'] ?? $w['name']];
        });
    $guides = $pages->filter(fn ($p) => ! $C::get($p->slug))->values();
    $count = fn ($g) => $weeds->where('group', $g)->count();
    // One photo per group for the hero, the first weed that has one.
    $faces = collect(array_keys($W::GROUPS))->map(fn ($g) => $weeds->where('group', $g)->first(fn ($w) => $w['thumb']))->filter()->values();
    $groupIcons = [
        'grasses' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15',
        'sedges' => 'M12 4.5l7.5 13h-15zM12 17.5V21',
        'broadleaves' => 'M5 19C5 10 10 5 19 5c0 9-5 14-14 14zM5 19L15 9M9.5 14.5h4M12 12V8.5',
    ];
    $groupHue = ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    @if ($faces->count())<meta property="og:image" content="{{ $faces[0]['thumb'] }}">@endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <style>
        .wk-hero { display: grid; gap: 2rem; align-items: center; }
        @media (min-width: 1024px) { .wk-hero { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 3rem; } }
        .wk-jump { display: flex; flex-wrap: wrap; gap: .5rem; }
        .wk-jump a { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .95rem; border-radius: 999px; font-size: .88rem; font-weight: 800;
            color: #2d5016; background: #fff; border: 1px solid #d7e8c2; text-decoration: none; box-shadow: 0 8px 18px -16px rgb(20 33 12 / .6);
            transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-jump a:hover { transform: translateY(-2px); border-color: #a8cc7e; background: #f9fbf6; }
        .wk-jump svg { width: 1rem; height: 1rem; color: #4a7c2a; }
        .wk-mosaic { position: relative; display: grid; grid-template-columns: 1.15fr 1fr; grid-template-rows: 1fr 1fr; gap: .6rem; height: 19rem; }
        .wk-mosaic figure { position: relative; margin: 0; overflow: hidden; border-radius: 1.2rem; background: #e4efd4; box-shadow: 0 22px 40px -30px rgb(20 33 12 / .7); }
        .wk-mosaic figure:first-child { grid-row: span 2; }
        .wk-mosaic img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .wk-mosaic figure:hover img { transform: scale(1.04); }
        .wk-mosaic figcaption { position: absolute; left: .6rem; bottom: .6rem; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .6rem; border-radius: 999px;
            font-size: .72rem; font-weight: 800; color: #fff; background: rgb(15 26 10 / .62); backdrop-filter: blur(4px); }
        .wk-mosaic figcaption i { width: .5rem; height: .5rem; border-radius: 999px; background: hsl(var(--g) 60% 62%); }
        @media (max-width: 639.98px) { .wk-mosaic { height: 15rem; } }

        .wk-sec-h { max-width: 46rem; }
        .wk-sec-h h2 { font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: clamp(1.6rem, 3.2vw, 2.2rem); line-height: 1.2; text-wrap: balance; }
        .wk-sec-h p { margin-top: .7rem; color: #4b5563; line-height: 1.7; }

        /* The three groups */
        .wk-groups { display: grid; gap: 1rem; }
        @media (min-width: 768px) { .wk-groups { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .wk-group { --g: 98; display: flex; flex-direction: column; gap: .7rem; padding: 1.25rem; border-radius: 1.25rem; background: #fff;
            border: 1px solid hsl(var(--g) 35% 86%); box-shadow: 0 16px 34px -30px rgb(20 33 12 / .6); }
        .wk-group-top { display: flex; align-items: center; gap: .75rem; }
        .wk-gico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; color: hsl(var(--g) 55% 26%);
            background: linear-gradient(145deg, hsl(var(--g) 60% 95%), hsl(var(--g) 50% 86%)); box-shadow: inset 0 0 0 1px hsl(var(--g) 40% 78%); }
        .wk-gico svg { width: 1.5rem; height: 1.5rem; }
        .wk-group h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; color: #14210c; }
        .wk-group h3 small { display: block; font-family: inherit; font-size: .78rem; font-weight: 700; color: hsl(var(--g) 40% 35%); }
        .wk-group ul { display: grid; gap: .4rem; font-size: .9rem; line-height: 1.5; color: #374151; }
        .wk-group li { display: flex; gap: .5rem; }
        .wk-group li::before { content: ''; flex: none; width: .4rem; height: .4rem; margin-top: .5rem; border-radius: 999px; background: hsl(var(--g) 50% 45%); }
        .wk-group button { margin-top: auto; align-self: flex-start; display: inline-flex; align-items: center; gap: .35rem; font-size: .86rem; font-weight: 800;
            color: hsl(var(--g) 55% 26%); padding: .45rem .85rem; border-radius: 999px; background: hsl(var(--g) 55% 94%);
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-group button:hover { background: hsl(var(--g) 50% 88%); }
        .wk-group button svg { width: .9rem; height: .9rem; }

        /* The catalogue */
        .wk-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; }
        .wk-search { position: relative; flex: 1 1 18rem; max-width: 26rem; }
        .wk-search svg { position: absolute; left: .85rem; top: 50%; translate: 0 -50%; width: 1.05rem; height: 1.05rem; color: #6b9f3d; pointer-events: none; }
        .wk-search input { width: 100%; padding: .7rem .9rem .7rem 2.5rem; border-radius: 999px; border: 1px solid #d7e8c2; background: #fff; font-size: .95rem; color: #14210c;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .wk-search input:focus { outline: none; border-color: #86b556; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
        .wk-cats button { display: inline-flex; align-items: center; gap: .4rem; }
        .wk-cats button i { font-style: normal; font-size: .72rem; font-weight: 800; opacity: .7; }
        .wk-count { font-size: .85rem; color: #6b7280; }
        .wk-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 12.75rem), 1fr)); }
        .wk-card { --g: 98; display: flex; flex-direction: column; overflow: hidden; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px -26px rgb(20 33 12 / .55); border-color: hsl(var(--g) 40% 75%); }
        .wk-ph { position: relative; aspect-ratio: 4 / 3; background: radial-gradient(circle at 30% 20%, hsl(var(--g) 45% 92%), hsl(var(--g) 35% 84%)); overflow: hidden; }
        .wk-ph img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .wk-card:hover .wk-ph img { transform: scale(1.05); }
        .wk-ph .none { position: absolute; inset: 0; display: grid; place-items: center; color: hsl(var(--g) 40% 55%); }
        .wk-ph .none svg { width: 2.6rem; height: 2.6rem; }
        .wk-tag { position: absolute; left: .55rem; top: .55rem; padding: .22rem .55rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .05em;
            text-transform: uppercase; color: hsl(var(--g) 60% 20%); background: hsl(var(--g) 60% 93% / .94); }
        .wk-in { padding: .8rem .9rem 1rem; display: flex; flex-direction: column; gap: .2rem; flex: 1; }
        .wk-in b { font-family: var(--font-heading); font-size: 1.02rem; line-height: 1.3; color: #14210c; }
        .wk-in em { font-size: .82rem; color: #4b5563; }
        .wk-in p { margin-top: .3rem; font-size: .8rem; line-height: 1.5; color: #6b7280; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .wk-in p span { font-weight: 700; color: #4b5563; }
        /* A phone shows two weeds a row, so the catalogue is not a long scroll. */
        @media (max-width: 639.98px) {
            .wk-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
            .wk-in { padding: .6rem .65rem .75rem; }
            .wk-in b { font-size: .92rem; }
            .wk-in em { font-size: .74rem; }
            .wk-in p { font-size: .72rem; }
            .wk-tag { left: .4rem; top: .4rem; font-size: .6rem; }
        }
        .wk-card.is-out { display: none; }
        .wk-card.is-in { animation: spIn .32s cubic-bezier(.22,1,.36,1) both; }
        .wk-empty { display: none; padding: 2rem 1rem; text-align: center; color: #6b7280; border: 1px dashed #d7e8c2; border-radius: 1.1rem; }
        .wk-empty.is-on { display: block; animation: spIn .32s cubic-bezier(.22,1,.36,1) both; }
        .wk-empty a { color: #3d6823; font-weight: 700; }

        /* The weed control helper */
        .wc-sec { background: linear-gradient(180deg, #f6faf1, #eef5e6); border-top: 1px solid #e4efd4; border-bottom: 1px solid #e4efd4; scroll-margin-top: 5rem; }
        .wc { display: grid; gap: 1.25rem; }
        @media (min-width: 1024px) { .wc { grid-template-columns: minmax(0, 23rem) minmax(0, 1fr); gap: 1.75rem; align-items: start; } }
        .wc-ask { display: grid; gap: 1.1rem; padding: 1.25rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); }
        @media (min-width: 1024px) { .wc-ask { position: sticky; top: 6rem; } }
        .wc-q > b { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; }
        .wc-q > b i { font-style: normal; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; color: #fff; background: #4a7c2a; }
        .wc-q > small { display: block; margin: .15rem 0 0 2.15rem; font-size: .8rem; color: #6b7280; }
        .wc-opts { margin-top: .7rem; display: flex; flex-wrap: wrap; gap: .4rem; }
        .wc-opts button { padding: .5rem .8rem; border-radius: .8rem; font-size: .86rem; font-weight: 700; color: #374151; background: #f6f8f3; border: 1px solid #e5ebdf;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        .wc-opts button small { display: block; font-size: .7rem; font-weight: 600; opacity: .75; }
        .wc-opts button:hover:not([disabled]) { border-color: #a8cc7e; }
        .wc-opts button[aria-checked="true"], .wc-opts button[aria-pressed="true"] { background: #2d5016; border-color: #2d5016; color: #fff; }
        .wc-opts button[disabled] { opacity: .4; cursor: not-allowed; }
        .wc-seg button { flex: 1 1 8rem; text-align: left; }
        .wc-days { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .wc-days button { text-align: center; white-space: nowrap; }
        .wc-gs { display: grid; gap: .45rem; }
        .wc-gs button { --g: 98; display: flex; align-items: center; gap: .7rem; text-align: left; padding: .6rem .75rem; }
        .wc-gs button span.ico { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; color: hsl(var(--g) 55% 26%); background: hsl(var(--g) 50% 90%);
            transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button span.ico svg { width: 1.2rem; height: 1.2rem; }
        .wc-gs button[aria-pressed="true"] span.ico { background: rgb(255 255 255 / .16); color: #fff; }
        .wc-gs button .tick { margin-left: auto; flex: none; width: 1.25rem; height: 1.25rem; border-radius: .4rem; border: 2px solid #c9d6bb; display: grid; place-items: center;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button .tick svg { width: .8rem; height: .8rem; opacity: 0; transform: scale(.5); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button[aria-pressed="true"] .tick { background: #f5c518; border-color: #f5c518; color: #3b2f00; }
        .wc-gs button[aria-pressed="true"] .tick svg { opacity: 1; transform: none; }
        .wc-out { min-width: 0; }
        .wc-card { border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 24px 48px -36px rgb(20 33 12 / .65); overflow: hidden; }
        .wc-when { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .8rem; padding: 1rem 1.25rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 80%); }
        .wc-when b { font-family: var(--font-heading); font-size: 1.15rem; color: #fff; }
        .wc-when small { width: 100%; font-size: .82rem; color: #cfe0bd; }
        .wc-timing { padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #3b2f00; background: #f5c518; }
        .wc-body { padding: 1.15rem 1.25rem 1.3rem; display: grid; gap: 1.1rem; }
        .wc-body h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: #14210c; }
        .wc-steps { margin-top: .55rem; display: grid; gap: .5rem; counter-reset: wc; }
        .wc-steps li { position: relative; padding-left: 2.1rem; font-size: .95rem; line-height: 1.6; color: #374151; counter-increment: wc; }
        .wc-steps li::before { content: counter(wc); position: absolute; left: 0; top: .1rem; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: grid; place-items: center;
            font-size: .75rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
        .wc-ings { margin-top: .6rem; display: flex; flex-wrap: wrap; gap: .45rem; }
        .wc-ings li { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .45rem .42rem .75rem; border-radius: .8rem; background: #f6f8f3; border: 1px solid #e5ebdf;
            font-size: .88rem; font-weight: 700; color: #1f2937; }
        .wc-ings li em { font-style: normal; font-size: .68rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; color: #2d5016; background: #e4efd4; white-space: nowrap; cursor: help; }
        .wc-split { display: grid; gap: .9rem; }
        @media (min-width: 640px) { .wc-split { grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); } }
        .wc-split > div { --g: 98; padding: .8rem; border-radius: 1rem; background: hsl(var(--g) 45% 97%); border: 1px solid hsl(var(--g) 35% 88%); }
        .wc-split > div > b { font-size: .82rem; font-weight: 800; color: hsl(var(--g) 55% 25%); }
        .wc-note { padding: .8rem .95rem; border-radius: .9rem; font-size: .9rem; line-height: 1.55; background: #fff8e6; border: 1px solid #f5d98a; color: #6b4a00; }
        .wc-note.ok { background: #f3f8ec; border-color: #c9e0ad; color: #24400f; }
        .wc-legend { font-size: .8rem; line-height: 1.55; color: #6b7280; }
        .wc-card.is-swap .wc-when, .wc-card.is-swap .wc-body > * { animation: wcIn .34s cubic-bezier(.22,1,.36,1) both; }
        .wc-card.is-swap .wc-body > *:nth-child(2) { animation-delay: .04s; }
        .wc-card.is-swap .wc-body > *:nth-child(3) { animation-delay: .08s; }
        .wc-card.is-swap .wc-body > *:nth-child(4) { animation-delay: .12s; }
        @keyframes wcIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .wc-fine { margin-top: 1.1rem; display: flex; gap: .6rem; font-size: .85rem; line-height: 1.6; color: #4b5563; }
        .wc-fine svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; color: #c79e00; }
        .wc-fine a { color: #3d6823; font-weight: 700; text-decoration: underline; text-decoration-color: #a8cc7e; text-underline-offset: 3px; }
        @media (prefers-reduced-motion: reduce) {
            .wk-jump a, .wk-mosaic img, .wk-card, .wk-ph img, .wc-opts button, .wc-gs button .tick, .wc-gs button .tick svg { transition: none; }
            .wk-card.is-in, .wk-empty.is-on, .wc-card.is-swap .wc-when, .wc-card.is-swap .wc-body > * { animation: none; }
        }
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
            <div class="wk-hero mt-5">
                <div>
                    <span class="sp-chip">{{ $meta['kicker'] }}</span>
                    <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                    <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
                    <div class="wk-jump mt-6">
                        <a href="#catalogue"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a weed</a>
                        <a href="#control"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>Control by rice age</a>
                        <a href="#guides"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>Read the guides</a>
                    </div>
                    <div class="mt-5">
                        <div class="sp-tabs">
                            <a href="{{ $S::url('crops') }}">Crop guides</a>
                            <a href="{{ $S::url('pests') }}">Crop pests</a>
                            <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                            <a href="{{ $S::url('weeds') }}" class="is-on">Weeds and grasses</a>
                            <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                        </div>
                    </div>
                </div>
                @if ($faces->count() === 3)
                    <div class="wk-mosaic" aria-hidden="true">
                        @foreach ($faces as $f)
                            <figure style="--g: {{ $groupHue[$f['group']] }}">
                                <img src="{{ $f['thumb'] }}" alt="" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <figcaption><i></i>{{ $W::GROUPS[$f['group']]['label'] }}: {{ $f['name'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- The three groups: the first thing to know about any weed. --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Grasses, Sedges or Broadleaves?</h2>
                <p>
                    Every weed in a rice field belongs to one of three groups, and the group decides what kills it. Look at the stem and the leaf first.
                    Our guide to the <a href="{{ $S::url('weeds', 'types-of-weeds') }}" class="font-bold text-brand-700 underline decoration-[#a8cc7e] underline-offset-4">types of weeds</a> goes further.
                </p>
            </div>
            <div class="wk-groups mt-8">
                @php($marks = [
                    'grasses' => ['Round, hollow stems with clear joints', 'Long, narrow leaves in two rows, with parallel veins', 'A leaf sheath split open around the stem'],
                    'sedges' => ['Solid, three sided stems with no joints', 'Narrow leaves with no ligule at the collar', 'A leaf sheath closed all the way around the stem'],
                    'broadleaves' => ['Wide leaves with a net of veins', 'Many plant families and many shapes', 'Flowers, stems and branches of every color'],
                ])
                @foreach ($W::GROUPS as $g => $info)
                    <div class="wk-group" style="--g: {{ $groupHue[$g] }}">
                        <div class="wk-group-top">
                            <span class="wk-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$g] }}"/></svg></span>
                            <h3>{{ $info['label'] }}<small>{{ $count($g) }} weeds in the catalogue</small></h3>
                        </div>
                        <ul>@foreach ($marks[$g] as $m)<li>{{ $m }}</li>@endforeach</ul>
                        <button type="button" data-show-group="{{ $g }}">See the {{ strtolower($info['label']) }}
                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The catalogue --}}
    <section class="bg-[#fbfcf9] border-t border-[#eef3e8]" id="catalogue" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Weeds of Philippine Rice Fields</h2>
                <p>{{ $weeds->count() }} weeds that grow in Philippine rice fields. Search by any name you know, English, scientific or local, like bayakibok or gabi gabi.</p>
            </div>
            <div class="wk-tools mt-7">
                <label class="wk-search">
                    <span class="sr-only">Search the weeds</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" id="wkQ" placeholder="Search a name, like humay humay" autocomplete="off">
                </label>
                <div class="sp-cats wk-cats" id="wkCats" role="group" aria-label="Weed group">
                    <button type="button" class="is-on" data-group="">All <i>{{ $weeds->count() }}</i></button>
                    @foreach ($W::GROUPS as $g => $info)
                        <button type="button" data-group="{{ $g }}">{{ $info['label'] }} <i>{{ $count($g) }}</i></button>
                    @endforeach
                </div>
                <span class="wk-count" id="wkCount" aria-live="polite"></span>
            </div>
            <div class="wk-grid mt-6" id="wkGrid">
                @foreach ($weeds as $w)
                    <a href="{{ $w['url'] }}" class="wk-card" style="--g: {{ $groupHue[$w['group']] }}" data-group="{{ $w['group'] }}"
                       data-q="{{ \Illuminate\Support\Str::lower($w['name'] . ' ' . $w['sci'] . ' ' . $w['local']) }}">
                        <span class="wk-ph">
                            @if ($w['thumb'])
                                <img src="{{ $w['thumb'] }}" alt="{{ $w['alt'] }}" loading="lazy" width="480" height="360">
                            @else
                                <span class="none"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$w['group']] }}"/></svg></span>
                            @endif
                            <span class="wk-tag">{{ $W::GROUPS[$w['group']]['label'] }}</span>
                        </span>
                        <span class="wk-in">
                            <b>{{ $w['name'] }}</b>
                            @if ($w['sci'] !== $w['name'])<em>{{ $w['sci'] }}</em>@endif
                            @if ($w['local'] !== '')<p><span>Local names:</span> {{ $w['local'] }}</p>@endif
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="wk-empty mt-6" id="wkEmpty">
                No weed by that name in the catalogue yet. Try its scientific name, or take a photo and
                <a href="{{ url('/features/ai-agricultural-technician') }}">ask Anee, the smart farm technician</a>.
            </div>
        </div>
    </section>

    {{-- The weed control helper --}}
    <section class="wc-sec" id="control">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <span class="sp-chip">Weed control helper</span>
                <h2 class="mt-3">Weed Control by Rice Age</h2>
                <p>
                    Tell us how you planted, how old your rice is and which weeds you see. You get what to do first and the active ingredients that work at that age,
                    from PhilRice recommendations. Active ingredients only, never brands.
                </p>
            </div>
            <div class="wc mt-8">
                <div class="wc-ask">
                    <div class="wc-q">
                        <b><i>1</i>How did you plant?</b>
                        <div class="wc-opts wc-seg" role="radiogroup" aria-label="How did you plant?" id="wcMethod">
                            @foreach ($W::METHODS as $k => $m)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" data-method="{{ $k }}">{{ $m['label'] }}<small>{{ $m['local'] }}</small></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i>2</i>How old is your rice?</b>
                        <small id="wcDaysHint">Days after transplanting</small>
                        <div class="wc-opts wc-days" role="radiogroup" aria-label="How old is your rice?" id="wcDays">
                            @foreach (['0-5', '6-10', '11-20', '21-30', '31-40'] as $d)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" data-days="{{ $d }}">{{ str_replace('-', ' to ', $d) }}<small>days</small></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i>3</i>Which weeds do you see?</b>
                        <small>Pick one or more</small>
                        <div class="wc-opts wc-gs" id="wcGroups">
                            @foreach ($W::GROUPS as $g => $info)
                                <button type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-g="{{ $g }}" style="--g: {{ $groupHue[$g] }}">
                                    <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$g] }}"/></svg></span>
                                    <span>{{ $info['label'] }}<small>{{ $info['hint'] }}</small></span>
                                    <span class="tick"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="wc-out" aria-live="polite">
                    <div class="wc-card" id="wcCard">
                        <noscript><div class="wc-body"><p>Turn on JavaScript to use the helper, or read the <a href="{{ $S::url('weeds', 'herbicides-for-rice-weeds') }}">tables in our herbicide guide</a>.</p></div></noscript>
                    </div>
                    <p class="wc-fine">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        <span>
                            Use only herbicides registered with the <a href="{{ $S::url('blog', 'fertilizer-and-pesticide-authority') }}">Fertilizer and Pesticide Authority</a>, and follow the label for the rate, the timing and the protective clothing.
                            Herbicides vary by region and weed, so check with your municipal agriculturist. Our <a href="{{ $S::url('weeds', 'herbicides-for-rice-weeds') }}">herbicide guide</a> explains the groups.
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- The long guides --}}
    @if ($guides->count())
        <section class="bg-white" id="guides" style="scroll-margin-top: 5rem">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <div class="wk-sec-h">
                    <h2>Weed Guides for Rice Farmers</h2>
                    <p>The whole plan, start to finish: how to tell the weeds apart, how to keep them out across a season, and how to use herbicides safely.</p>
                </div>
                <div class="sp-grid mt-8">
                    @foreach ($guides as $p)
                        @include('public.site.tile', ['p' => $p])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Not sure which weed it is?</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Send Anee a Photo From the Field</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
                Anee, the smart farm technician in anee.io, tells you which weed it most likely is, its group and what to do at the age of your rice.
                Then anee.io puts the weeding days on your season calendar. In Tagalog or English.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ url('/features/ai-agricultural-technician') }}" class="btn btn-lg btn-on-dark">Meet Anee</a>
            </div>
        </div>
    </section>

    <script type="application/json" id="wcData">{!! json_encode([
        'table' => $W::TABLE,
        'methods' => $W::METHODS,
        'groups' => collect($W::GROUPS)->map(fn ($g) => $g['label']),
        'ingredients' => $W::INGREDIENTS,
        'modes' => $W::MODES,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endsection

@push('scripts')
<script>
(() => {
    /* The catalogue: a group chip and the search box narrow the cards
       together; the cards that stay rise into place. ?group= and #catalogue
       open it already narrowed (a weed page's group link). */
    const grid = document.getElementById('wkGrid');
    const cats = document.getElementById('wkCats');
    const q = document.getElementById('wkQ');
    const count = document.getElementById('wkCount');
    const empty = document.getElementById('wkEmpty');
    if (!grid || !cats || !q) return;
    const cards = [...grid.querySelectorAll('.wk-card')];
    let group = '';
    const norm = (s) => s.toLowerCase().replace(/[^a-z0-9ñ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    const apply = () => {
        const words = norm(q.value).split(' ').filter(Boolean);
        let shown = 0;
        cards.forEach((c) => {
            const hay = norm(c.dataset.q);
            const on = (!group || c.dataset.group === group) && words.every((w) => hay.includes(w));
            const was = !c.classList.contains('is-out');
            c.classList.toggle('is-out', !on);
            if (on && !was) { c.classList.remove('is-in'); c.style.animationDelay = Math.min(shown, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); }
            if (on) shown++;
        });
        count.textContent = shown === cards.length ? cards.length + ' weeds' : 'Showing ' + shown + ' of ' + cards.length + ' weeds';
        empty.classList.toggle('is-on', shown === 0);
    };
    const setGroup = (g) => {
        group = g;
        cats.querySelectorAll('button').forEach((b) => b.classList.toggle('is-on', b.dataset.group === g));
        apply();
    };
    cats.addEventListener('click', (e) => { const b = e.target.closest('button[data-group]'); if (b) setGroup(b.dataset.group); });
    q.addEventListener('input', apply);
    document.querySelectorAll('[data-show-group]').forEach((b) => b.addEventListener('click', () => {
        setGroup(b.dataset.showGroup);
        document.getElementById('catalogue').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }));
    const want = new URLSearchParams(location.search).get('group');
    if (want && cats.querySelector('[data-group="' + CSS.escape(want) + '"]')) setGroup(want); else apply();
})();
</script>
<script>
(() => {
    /* The weed control helper: method, rice age and the groups seen pick one
       window of WeedControl::TABLE. Several groups at once take the
       ingredients every one of them lists; none in common shows each group's
       own list instead. */
    const el = document.getElementById('wcData');
    const card = document.getElementById('wcCard');
    if (!el || !card) return;
    const D = JSON.parse(el.textContent);
    // A weed page's "Open the helper" link names its group (?group=).
    const asked = new URLSearchParams(location.search).get('group');
    const st = { method: 'transplanted', days: '0-5', groups: [D.groups[asked] ? asked : 'grasses'] };
    const methodBox = document.getElementById('wcMethod');
    const daysBox = document.getElementById('wcDays');
    const groupBox = document.getElementById('wcGroups');
    const hint = document.getElementById('wcDaysHint');
    const ORDER = ['grasses', 'sedges', 'broadleaves'];
    const TIMING = { pre: 'Pre emergence', early: 'Early post emergence', post: 'Post emergence', rescue: 'Rescue only' };
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const join = (a) => a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const chip = (name) => {
        const g = (D.ingredients[name] || []).slice().sort((a, b) => a - b);
        if (!g.length) return '';
        const title = g.map((n) => 'Group ' + n + ': ' + (D.modes[n] || '')).join('. ');
        return '<em title="' + esc(title) + '">' + (g.length > 1 ? 'Groups ' + g.join(' and ') : 'Group ' + g[0]) + '</em>';
    };
    const list = (names) => '<ul class="wc-ings">' + names.map((n) => '<li><span>' + esc(n) + '</span>' + chip(n) + '</li>').join('') + '</ul>';
    const HUE = { grasses: 98, sedges: 168, broadleaves: 38 };

    const render = (swap) => {
        const m = D.table[st.method];
        if (!m[st.days]) st.days = Object.keys(m).pop();
        const w = m[st.days];
        // the controls
        methodBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.method === st.method)));
        daysBox.querySelectorAll('button').forEach((b) => {
            b.disabled = !m[b.dataset.days];
            b.setAttribute('aria-checked', String(b.dataset.days === st.days));
        });
        groupBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(st.groups.includes(b.dataset.g))));
        hint.textContent = 'Days ' + D.methods[st.method].after;

        const gs = ORDER.filter((g) => st.groups.includes(g));
        const names = gs.map((g) => D.groups[g].toLowerCase());
        let ings = '';
        if (!gs.length) {
            ings = '<div><h3>Active ingredients</h3><p class="wc-note ok">Pick the weeds you see in step 3 and the active ingredients for them show here.</p></div>';
        } else {
            const lists = gs.map((g) => w[g] || []);
            const common = lists.reduce((a, l) => a.filter((x) => l.includes(x)));
            if (common.length) {
                ings = '<div><h3>Active ingredients that work on ' + esc(join(names)) + '</h3>' + list(common) + '</div>';
            } else if (gs.length === 1) {
                ings = '<div><h3>Active ingredients for ' + esc(names[0]) + '</h3><p class="wc-note">No herbicide is listed for ' + esc(names[0]) + ' at this age. Pull them by hand and keep the water up.</p></div>';
            } else {
                ings = '<div><h3>Active ingredients by group</h3><p class="wc-note">No single active ingredient covers ' + esc(join(names)) + ' at this age. Treat each group with its own, and space the sprays as the labels say.</p></div>'
                    + '<div class="wc-split">' + gs.map((g) => '<div style="--g: ' + HUE[g] + '"><b>' + esc(D.groups[g]) + '</b>'
                        + (w[g] && w[g].length ? list(w[g]) : '<p class="wc-legend" style="margin-top:.4rem">None listed at this age. Hand weed.</p>') + '</div>').join('') + '</div>';
            }
            if (ings.includes('wc-ings')) ings += '<p class="wc-legend">The group number tells how a herbicide kills. Use a different group next season so the weeds do not learn to survive it.</p>';
        }
        card.innerHTML =
            '<div class="wc-when"><span class="wc-timing">' + esc(TIMING[w.timing] || '') + '</span><b>' + esc(w.label) + ' ' + esc(D.methods[st.method].after) + '</b>'
            + '<small>' + esc(w.stage) + '</small></div>'
            + '<div class="wc-body"><div><h3>Do this first</h3><ol class="wc-steps">' + w.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol></div>' + ings + '</div>';
        if (swap) { card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap'); }
    };
    methodBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-method]'); if (b && b.dataset.method !== st.method) { st.method = b.dataset.method; render(true); } });
    daysBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-days]'); if (b && !b.disabled && b.dataset.days !== st.days) { st.days = b.dataset.days; render(true); } });
    groupBox.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-g]');
        if (!b) return;
        const g = b.dataset.g;
        st.groups = st.groups.includes(g) ? st.groups.filter((x) => x !== g) : st.groups.concat(g);
        render(true);
    });
    // Arrow keys move within a radio group, as a radio group should.
    [methodBox, daysBox].forEach((box) => box.addEventListener('keydown', (e) => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
        const bs = [...box.querySelectorAll('button:not([disabled])')];
        const i = bs.indexOf(document.activeElement);
        if (i < 0) return;
        e.preventDefault();
        const n = bs[(i + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : bs.length - 1)) % bs.length];
        n.focus(); n.click();
    }));
    render(false);
})();
</script>
@endpush
