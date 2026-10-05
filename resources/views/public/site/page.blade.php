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
    $isFeature = $page->section === 'features';
    $isQuestion = $page->section === 'questions';
    $feat = $isFeature ? $S::feature($page) : null;
    // A tall picture on a feature page is a phone screen: it stands in a
    // phone beside the words instead of being cropped into a banner.
    $portrait = false;
    if ($isFeature && $heroSrc && ! preg_match('#^https?://#i', (string) ($hero['src'] ?? ''))) {
        $size = @getimagesize(public_path(ltrim((string) $hero['src'], '/')));
        $portrait = $size && $size[1] > $size[0] * 1.15;
    }
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
    @if ($heroSrc && trim((string) ($hero['alt'] ?? '')) !== '')<meta property="og:image:alt" content="{{ $hero['alt'] }}">@endif
    @if ($isFeature)
        <meta property="og:type" content="website">
    @else
        <meta property="article:published_time" content="{{ ($page->publishedAt ?? $updated)->toAtomString() }}">
        <meta property="article:modified_time" content="{{ $updated->toAtomString() }}">
        <meta property="article:section" content="{{ $page->category ?: $meta['label'] }}">
        @foreach (array_slice(array_values(array_unique(array_filter(array_merge([(string) $page->focusKeyword], (array) ($page->keywords ?? []))))), 0, 8) as $tag)
            <meta property="article:tag" content="{{ $tag }}">
        @endforeach
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $page->metaDescription }}">
    @if ($heroSrc)<meta name="twitter:image" content="{{ $heroSrc }}">@endif
    {{-- One graph, every node tied to the others (App\Support\PageSchema). --}}
    <script type="application/ld+json">{!! json_encode(\App\Support\PageSchema::graph($page, $blocks, $meta, $canonical, $faq), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article>
        <header class="sp-hero {{ $isFeature ? 'is-feature' : '' }}" @if ($feat) style="--h: {{ $feat['hue'] }}" @endif>
            <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 sm:pt-10 pb-8">
                <nav class="sp-crumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ $sectionUrl }}">{{ $meta['crumb'] }}</a>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="truncate max-w-[14rem] sm:max-w-none">{{ $S::shortTitle($page) }}</span>
                </nav>
                <div class="{{ $portrait ? 'sp-fhero' : '' }}">
                {{-- The title and its intro use the page's full width: there is no sidebar beside them. --}}
                <div class="mt-5">
                    @if ($feat)
                        <div class="sp-fbadge">
                            <span class="sp-fico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feat['icon'] }}"/></svg></span>
                            <span>{{ $feat['name'] }}</span>
                        </div>
                    @elseif ($page->category)
                        <span class="sp-chip">{{ $page->category }}</span>
                    @endif
                    <h1 class="sp-h1 mt-3">{{ $page->title }}</h1>
                    @if ($page->excerpt)<p class="sp-lead mt-4">{!! $S::inline($page->excerpt) !!}</p>@endif
                    @if ($isFeature)
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                            <a href="{{ route('pricing') }}" class="btn btn-outline btn-lg">See plans</a>
                            <span class="text-sm text-gray-500">Free forever on Libre. No card needed.</span>
                        </div>
                    @else
                    <div class="sp-meta mt-4">
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>{{ $minutes }} min read</span>
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>Updated {{ $updated->timezone('Asia/Manila')->format('F j, Y') }}</span>
                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $isQuestion ? 'Answered by Anee, the anee.io AI technician' : 'By the anee.io agriculture team' }}</span>
                    </div>
                    @endif
                </div>
                @if ($heroSrc && $portrait)
                    <figure class="sp-phone">
                        <img src="{{ $heroSrc }}" alt="{{ $hero['alt'] ?? $page->title }}" fetchpriority="high">
                    </figure>
                @elseif ($heroSrc)
                    <figure class="sp-figure mt-7 {{ $isFeature ? 'is-product' : '' }}">
                        <img src="{{ $heroSrc }}" alt="{{ $hero['alt'] ?? $page->title }}" fetchpriority="high">
                        @if (trim((string) ($hero['credit'] ?? '')) !== '')<figcaption>Photo: {{ $hero['credit'] }}</figcaption>@endif
                    </figure>
                @endif
                </div>
            </div>
        </header>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            @if (count($toc) > 1)
                {{-- A phone's table of contents: under the title, folded. --}}
                <div class="sp-mtoc" x-data="{ o: false }" :class="o && 'is-open'">
                    <button type="button" class="sp-mtoc-h" @click="o = !o" :aria-expanded="o">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h10M4 18h13"/></svg>
                        <span>On this page</span>
                        <small>{{ count($toc) }} sections</small>
                        <i aria-hidden="true"></i>
                    </button>
                    <div class="sp-mtoc-fold"><nav>
                        @foreach ($toc as $t)<a href="#{{ $t['id'] }}" @click="o = false">{{ $t['text'] }}</a>@endforeach
                    </nav></div>
                </div>
            @endif
            <div class="sp-wrap">
                <div class="sp-body" id="spBody">
                    @include('public.site.blocks', ['blocks' => $blocks])
                </div>
                <aside class="sp-side">
                    @if ($isQuestion)
                        {{-- The door this page came through, held open for the next farmer. --}}
                        <div class="sp-ask">
                            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee">
                            <b>Have your own farming question?</b>
                            <p>Ask Anee one question for free. Tell her about your farm and the answer comes to your email.</p>
                            <a href="{{ url('/ask-anee') }}" class="btn btn-accent">Ask Anee for free</a>
                        </div>
                    @endif
                    @if (count($toc) > 1)
                        <div class="sp-card sp-toc-card">
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
                            : 'A cropping calendar that dates every task, a record of your fertilizer and costs, and Anee, the AI technician who answers in Tagalog or English.' }}</p>
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

    @if ($isFeature)
    {{-- The rest of the product, one card each. --}}
    <section class="bg-[#f9fbf6] border-t border-[#e4efd4]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <h2 class="font-heading text-2xl font-bold text-ink">More of what anee.io does</h2>
                <a href="{{ route('features') }}" class="text-sm font-extrabold text-brand-700 hover:text-brand-800">See all features ›</a>
            </div>
            @include('public.site.feature-grid', ['pages' => \App\Support\SitePages::inSection('features'), 'except' => $page->slug])
        </div>
    </section>
    @else
    {{-- Every section, a door away: guides feed each other. --}}
    <section class="bg-[#f9fbf6] border-t border-[#e4efd4]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                <h2 class="font-heading text-2xl font-bold text-ink">{{ $isQuestion ? 'More questions farmers asked' : 'More guides for your farm' }}</h2>
                @unless ($isQuestion)
                <div class="sp-tabs">
                    <a href="{{ $S::url('questions') }}" class="{{ $isQuestion ? 'is-on' : '' }}">Farmers' questions</a>
                    <a href="{{ $S::url('crops') }}" class="{{ $page->section === 'crops' ? 'is-on' : '' }}">Crop guides</a>
                    <a href="{{ $S::url('problems') }}" class="{{ $page->section === 'problems' ? 'is-on' : '' }}">Crop problems</a>
                    <a href="{{ $S::url('blog') }}" class="{{ $page->section === 'blog' ? 'is-on' : '' }}">Blog</a>
                    <a href="{{ route('features') }}" class="{{ $page->section === 'features' ? 'is-on' : '' }}">Features</a>
                </div>
                @endunless
            </div>
            <div class="sp-grid">
                @foreach ($related as $r)
                    @include('public.site.tile', ['p' => $r])
                @endforeach
            </div>
        </div>
    </section>
    @endif
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
