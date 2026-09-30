@extends('layouts.public')

{{-- One guide, blog post or feature page (App\Support\SitePages). --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $hero = is_array($page->heroImage) ? $page->heroImage : [];
    $heroSrc = $S::img($hero['src'] ?? null);
    $seoTitle = trim((string) ($page->metaTitle ?: $page->title));
    $updated = $page->updated_at ?? now();
    $sectionUrl = $page->section === 'features' ? route('features') : $S::url($page->section);
@endphp

@section('title_full', $seoTitle . ' | anee.io')
@section('meta_description', $page->metaDescription ?: \Illuminate\Support\Str::limit($S::plain($page->excerpt), 155))

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $page->metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="anee.io">
    <meta property="og:locale" content="{{ $page->lang === 'tl' ? 'tl_PH' : 'en_PH' }}">
    @if ($heroSrc)<meta property="og:image" content="{{ $heroSrc }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    @php
        $ld = [[
            '@context' => 'https://schema.org',
            '@type' => $page->section === 'features' ? 'WebPage' : 'Article',
            'headline' => $page->title,
            'description' => $page->metaDescription,
            'inLanguage' => $page->lang === 'tl' ? 'fil-PH' : 'en-PH',
            'mainEntityOfPage' => $canonical,
            'dateModified' => $updated->toAtomString(),
            'datePublished' => ($page->publishedAt ?? $updated)->toAtomString(),
            'author' => ['@type' => 'Organization', 'name' => 'anee.io', 'url' => url('/')],
            'publisher' => ['@type' => 'Organization', 'name' => 'anee.io', 'url' => url('/'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')]],
        ], [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $meta['label'], 'item' => $sectionUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $page->title, 'item' => $canonical],
            ],
        ]];
        if ($heroSrc) {
            $ld[0]['image'] = [$heroSrc];
        }
        if ($faq) {
            $ld[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faq),
            ];
        }
    @endphp
    @foreach ($ld as $graph)
        <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
@endpush

@section('content')
    <article>
        <header class="sp-hero">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 sm:pt-10 pb-8">
                <nav class="sp-crumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ $sectionUrl }}">{{ $meta['crumb'] }}</a>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="truncate max-w-[14rem] sm:max-w-none">{{ $S::shortTitle($page) }}</span>
                </nav>
                <div class="mt-5 max-w-3xl">
                    @if ($page->category)<span class="sp-chip">{{ $page->category }}</span>@endif
                    <h1 class="sp-h1 mt-3">{{ $page->title }}</h1>
                    @if ($page->excerpt)<p class="sp-lead mt-4">{!! $S::inline($page->excerpt) !!}</p>@endif
                    <div class="sp-meta mt-4">
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>{{ $minutes }} min read</span>
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>Updated {{ $updated->timezone('Asia/Manila')->format('F j, Y') }}</span>
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>By the anee.io agriculture team</span>
                    </div>
                </div>
                @if ($heroSrc)
                    <figure class="sp-figure mt-7">
                        <img src="{{ $heroSrc }}" alt="{{ $hero['alt'] ?? $page->title }}" fetchpriority="high">
                        @if (trim((string) ($hero['credit'] ?? '')) !== '')<figcaption>Photo: {{ $hero['credit'] }}</figcaption>@endif
                    </figure>
                @endif
            </div>
        </header>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            <div class="sp-wrap">
                <div class="sp-body" id="spBody">
                    @include('public.site.blocks', ['blocks' => $blocks])
                </div>
                <aside class="sp-side">
                    @if (count($toc) > 1)
                        <div class="sp-card">
                            <h4>On this page</h4>
                            <nav class="sp-toc" id="spToc">
                                @foreach ($toc as $t)<a href="#{{ $t['id'] }}" data-to="{{ $t['id'] }}">{{ $t['text'] }}</a>@endforeach
                            </nav>
                        </div>
                    @endif
                    <div class="sp-promo">
                        <b>{{ $page->lang === 'tl' ? 'Ang buong season mo, nasa isang app' : 'Your whole season in one app' }}</b>
                        <p>{{ $page->lang === 'tl'
                            ? 'Kalendaryo ng bawat gawain, abono at gastos na nakatala, at si Anee, ang AI technician na sumasagot sa Tagalog.'
                            : 'A cropping calendar that dates every task, fertilizer and costs on record, and Anee, the AI technician who answers in Tagalog or English.' }}</p>
                        <a href="{{ route('signup') }}" class="btn btn-accent">{{ $page->lang === 'tl' ? 'Magsimula nang libre' : 'Start free' }}</a>
                    </div>
                    @if ($related->count())
                        <div class="sp-card">
                            <h4>Keep reading</h4>
                            <div class="sp-rel">
                                @foreach ($related as $r)<a href="{{ $S::pageUrl($r) }}">{{ $r->title }}</a>@endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </article>

    {{-- Every section, a door away: guides feed each other. --}}
    <section class="bg-[#f9fbf6] border-t border-[#e4efd4]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <h2 class="font-heading text-2xl font-bold text-ink">More guides for your farm</h2>
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}" class="{{ $page->section === 'crops' ? 'is-on' : '' }}">Crop guides</a>
                    <a href="{{ $S::url('problems') }}" class="{{ $page->section === 'problems' ? 'is-on' : '' }}">Crop problems</a>
                    <a href="{{ $S::url('blog') }}" class="{{ $page->section === 'blog' ? 'is-on' : '' }}">Blog</a>
                    <a href="{{ route('features') }}" class="{{ $page->section === 'features' ? 'is-on' : '' }}">Features</a>
                </div>
            </div>
            <div class="sp-grid">
                @foreach ($related as $r)
                    @include('public.site.tile', ['p' => $r])
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    /* The table of contents follows the reader: the section on screen is lit. */
    const toc = document.getElementById('spToc');
    if (!toc || !('IntersectionObserver' in window)) return;
    const links = [...toc.querySelectorAll('a[data-to]')];
    const heads = links.map((a) => document.getElementById(a.dataset.to)).filter(Boolean);
    const light = (id) => links.forEach((a) => a.classList.toggle('is-on', a.dataset.to === id));
    const seen = new Map();
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => seen.set(e.target.id, e.isIntersecting));
        const first = heads.find((h) => seen.get(h.id));
        if (first) light(first.id);
    }, { rootMargin: '-90px 0px -65% 0px' });
    heads.forEach((h) => io.observe(h));
})();
</script>
@if ($preview)
<script>
    /* The builder redraws this preview on every change; it keeps the reader's place. */
    (() => {
        try {
            const y = parseInt(new URLSearchParams(location.search).get('y') || window.name.replace('y:', '') || '0', 10);
            if (y > 0) window.scrollTo(0, y);
            addEventListener('scroll', () => { window.name = 'y:' + Math.round(scrollY); }, { passive: true });
        } catch (_) {}
    })();
</script>
@endif
@endpush
