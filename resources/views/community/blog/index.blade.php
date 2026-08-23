@extends('layouts.app')

@section('title', 'Technician\'s Blog — Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('page-subtitle', 'Technician\'s Blog')

@push('head')
@include('community.partials.plaza-css')
<style>
    .blog-grid { display:grid; grid-template-columns:1fr; gap:1rem; }
    @media (min-width:640px) { .blog-grid { grid-template-columns:repeat(2,1fr); } }
    @media (min-width:1024px) { .blog-grid { grid-template-columns:repeat(3,1fr); } }
    .blog-card { display:flex; flex-direction:column; overflow:hidden; border-radius:1rem; border:1px solid var(--color-gray-100);
        background:var(--color-white); box-shadow:var(--shadow-card); text-decoration:none;
        transition:transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .blog-card:hover { transform:translateY(-2px); box-shadow:0 10px 30px -12px rgb(0 0 0 / .25); }
    .blog-cover { position:relative; aspect-ratio:16/9; background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50)); overflow:hidden; }
    /* Covers fade up out of a shimmer instead of popping in — the gallery's
       loading language. A 404 just leaves the quiet brand gradient. */
    .blog-cover img { width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity .28s ease; }
    .blog-cover img.is-loaded { opacity:1; }
    .blog-cover:has(img)::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:blogShimmer 1.15s linear infinite; }
    .blog-cover:has(img.is-loaded)::before, .blog-cover:not(:has(img))::before { display:none; }
    @keyframes blogShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    @media (prefers-reduced-motion: reduce) {
        .blog-card { transition:none; }
        .blog-cover:has(img)::before { animation:none; background:rgb(255 255 255 / .25); }
    }
    .blog-cover-fallback { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:2.4rem; }
    .blog-body { padding:.85rem 1rem 1rem; display:flex; flex-direction:column; gap:.35rem; }
    .blog-title { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .blog-excerpt { font-size:.83rem; color:var(--color-gray-500); line-height:1.4; }
    .blog-meta { margin-top:.4rem; font-size:.72rem; color:var(--color-gray-400); display:flex; gap:.6rem; flex-wrap:wrap; }

    /* --- The name plate. A section's head, not a notice in a box: the title
       carries its own weight, the line under it says what the section is for,
       and the run of numbers sits quietly below. Edge to edge on a phone,
       like the wall's posts and a discussion's head. --- */
    .blog-hero { position:relative; margin-bottom:1rem; border-radius:1.1rem; overflow:hidden;
        border:1px solid var(--color-gray-200); background:var(--color-white);
        box-shadow:var(--shadow-card); }
    .blog-hero-in { display:flex; align-items:center; gap:.9rem; padding:1.15rem 1.15rem .85rem; }
    .blog-hero-mark { flex:none; width:3.1rem; height:3.1rem; border-radius:1rem; font-size:1.5rem;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg, var(--color-brand-50), var(--color-brand-100));
        border:1px solid var(--color-brand-100); }
    .blog-hero h1 { font-family:var(--font-heading); font-size:1.3rem; font-weight:800; line-height:1.2;
        color:var(--color-gray-900); }
    .blog-hero h1 + p { margin-top:.2rem; font-size:.83rem; line-height:1.4; color:var(--color-gray-500); }
    .blog-hero-facts { display:flex; flex-wrap:wrap; gap:.15rem .9rem; padding:0 1.15rem 1rem;
        font-size:.72rem; font-weight:700; color:var(--color-gray-400); }
    .blog-hero-facts b { color:var(--color-gray-600); font-weight:800; }
    @media (max-width:640px) {
        .blog-hero { border-radius:0; border-left:0; border-right:0;
            margin-left:calc(var(--plaza-gutter, 1rem) * -1);
            margin-right:calc(var(--plaza-gutter, 1rem) * -1); }
    }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'blog'])

<div class="blog-hero plaza-accent">
    <div class="blog-hero-in">
        <div class="blog-hero-mark">📰</div>
        <div class="min-w-0">
            <h1>Technician's Blog</h1>
            <p>Guides and advice from the AniSenso team.</p>
        </div>
    </div>
    @if ($posts->total())
        <p class="blog-hero-facts">
            <span><b>{{ number_format($posts->total()) }}</b> {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}</span>
            @if ($latest = $posts->first())
                @if ($latest->publishedAt)<span>Latest {{ $latest->publishedAt->diffForHumans() }}</span>@endif
            @endif
        </p>
    @endif
</div>

@if ($posts->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">📰</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No articles yet</p>
        <p class="text-sm text-gray-500 mt-1">The team hasn't published anything yet — check back soon.</p>
    </div>
@else
    <div class="blog-grid">
        @foreach ($posts as $post)
            <a href="{{ route('community.blog.show', ['id' => $post->id]) }}" class="blog-card">
                <div class="blog-cover">
                    @if ($post->coverUrl())
                        <img src="{{ $post->coverUrl() }}" alt="" loading="lazy"
                            onload="this.classList.add('is-loaded')" onerror="this.remove()">
                    @else
                        <div class="blog-cover-fallback">🌾</div>
                    @endif
                </div>
                <div class="blog-body">
                    <span class="blog-title">{{ $post->title }}</span>
                    @if ($post->excerpt)<span class="blog-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</span>@endif
                    <span class="blog-meta">
                        @if ($post->authorName)<span>✍️ {{ $post->authorName }}</span>@endif
                        @if ($post->publishedAt)<span>{{ $post->publishedAt->format('M j, Y') }}</span>@endif
                        <span>💬 {{ $post->comments_count }}</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $posts->links('community.partials.blog-pagination') }}</div>
@endif
@endsection
