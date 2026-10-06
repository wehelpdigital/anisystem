@extends('layouts.public')

{{-- A section's front page: crop guides, crop problems or the blog. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $cats = $pages->pluck('category')->filter()->unique()->values();
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
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
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}" class="{{ $section === 'crops' ? 'is-on' : '' }}">Crop guides</a>
                    <a href="{{ $S::url('problems') }}" class="{{ $section === 'problems' ? 'is-on' : '' }}">Crop problems</a>
                    <a href="{{ $S::url('blog') }}" class="{{ $section === 'blog' ? 'is-on' : '' }}">Blog</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            @if ($cats->count() > 1)
                <div class="sp-cats mb-6" id="spCats">
                    <button type="button" class="is-on" data-cat="">All</button>
                    @foreach ($cats as $c)<button type="button" data-cat="{{ $c }}">{{ $c }}</button>@endforeach
                </div>
            @endif
            @if ($pages->count())
                <div class="sp-grid" id="spGrid">
                    @foreach ($pages as $p)
                        @include('public.site.tile', ['p' => $p])
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">Guides are coming soon.</p>
            @endif
        </div>
    </section>

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Put the guides to work</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Plan the season, then let anee.io keep count</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
                Build the cropping calendar from these guides, track every bag of fertilizer and every peso, and ask Anee,
                the smart farm technician, when a leaf looks wrong. In Tagalog or English.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ route('features') }}" class="btn btn-lg btn-on-dark">See every feature</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    /* The category chips narrow the grid; the cards that stay rise into place. */
    const bar = document.getElementById('spCats');
    const grid = document.getElementById('spGrid');
    if (!bar || !grid) return;
    bar.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-cat]');
        if (!b) return;
        bar.querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b));
        const want = b.dataset.cat;
        let i = 0;
        grid.querySelectorAll('.sp-tile').forEach((t) => {
            const show = !want || t.dataset.cat === want;
            t.classList.toggle('is-out', !show);
            t.classList.remove('is-in');
            if (show) { t.style.animationDelay = Math.min(i++, 10) * 30 + 'ms'; void t.offsetWidth; t.classList.add('is-in'); }
        });
    });
})();
</script>
@endpush
