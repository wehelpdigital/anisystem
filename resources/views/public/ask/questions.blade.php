@extends('layouts.public')

{{-- Every question Anee has answered (the "questions" section), newest
     first, drawn twelve at a time as the reader scrolls. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php $S = \App\Support\SitePages::class; @endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ url('/questions') }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ url('/questions') }}">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => url('/questions'),
        'hasPart' => $pages->getCollection()->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<style>
    .qa-ask { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; padding: 1.1rem 1.2rem; border-radius: 1.3rem;
        background: linear-gradient(135deg, #3d6823, #24400f 80%); color: #e8efe1; }
    .qa-ask img { width: 3.2rem; height: 3.2rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px rgb(255 255 255 / .85); }
    .qa-ask b { display: block; font-family: var(--font-heading); font-size: 1.15rem; color: #fff; }
    .qa-ask p { font-size: .9rem; color: #cfe0bd; }
    .qa-ask .btn { margin-left: auto; }
    @media (max-width: 639px) { .qa-ask .btn { margin-left: 0; width: 100%; justify-content: center; } }
    .qa-more { display: flex; justify-content: center; padding: 1.6rem 0 .4rem; min-height: 4rem; }
    .qa-spin { width: 1.8rem; height: 1.8rem; border-radius: 999px; border: 3px solid #dcebc9; border-top-color: #4e7a2a; animation: qaSpin .8s linear infinite; }
    @keyframes qaSpin { to { transform: rotate(360deg); } }
    .sp-tile.is-new { animation: qaIn .36s cubic-bezier(.22,1,.36,1) both; }
    @keyframes qaIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .qa-spin, .sp-tile.is-new { animation: none; } }
</style>

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
        <div class="qa-ask mt-7">
            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee">
            <div class="min-w-0">
                <b>Have a farming question?</b>
                <p>Ask Anee one question for free. The answer comes to your email.</p>
            </div>
            <a href="{{ url('/ask-anee') }}" class="btn btn-accent">Ask Anee</a>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        @if ($total)
            <p class="text-sm font-bold text-gray-500 mb-5">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('question', $total) }} answered</p>
            <div class="sp-grid" id="qaGrid">
                @include('public.ask.tiles', ['pages' => $pages->getCollection()])
            </div>
            <div class="qa-more" id="qaMore" data-next="{{ $pages->hasMorePages() ? $pages->currentPage() + 1 : '' }}">
                @if ($pages->hasMorePages())
                    <span class="qa-spin" aria-hidden="true"></span>
                    <noscript><a href="{{ $pages->nextPageUrl() }}">More questions ›</a></noscript>
                @endif
            </div>
        @else
            <div class="text-center py-10">
                <p class="text-gray-500">No questions answered yet. Be the first to ask.</p>
                <a href="{{ url('/ask-anee') }}" class="btn btn-accent mt-4">Ask Anee</a>
            </div>
        @endif
    </div>
</section>

<section class="anee-band">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
        <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Ask Anee anytime</p>
        <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Your whole season in one app</h2>
        <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
            A cropping calendar that dates every task, a record of your fertilizer and costs, and Anee, the smart farm technician who answers in Tagalog or English.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ route('signup') }}?utm_source=questions" class="btn btn-accent btn-lg">Try it for free</a>
            <a href="{{ route('features') }}" class="btn btn-lg btn-on-dark">See every feature</a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    /* The next twelve arrive as the reader nears the end of the list. */
    const grid = document.getElementById('qaGrid'), more = document.getElementById('qaMore');
    if (!grid || !more || !more.dataset.next || !('IntersectionObserver' in window)) return;
    let loading = false;
    const io = new IntersectionObserver(async (entries) => {
        if (!entries.some((e) => e.isIntersecting) || loading || !more.dataset.next) return;
        loading = true;
        try {
            const res = await fetch(`{{ url('/questions') }}?rows=1&page=${more.dataset.next}`, { headers: { Accept: 'application/json' } });
            const d = (await res.json()).data;
            const tmp = document.createElement('div');
            tmp.innerHTML = d.html;
            [...tmp.children].forEach((el, i) => { el.classList.add('is-new'); el.style.animationDelay = Math.min(i, 8) * 40 + 'ms'; grid.appendChild(el); });
            more.dataset.next = d.hasMore ? d.next : '';
            if (!d.hasMore) { more.innerHTML = ''; io.disconnect(); }
        } catch (_) {
            // A dropped page is tried again when the reader scrolls back down.
        } finally {
            loading = false;
        }
    }, { rootMargin: '600px 0px' });
    io.observe(more);
})();
</script>
@endpush
