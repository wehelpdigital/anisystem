{{-- The article as anee.io will show it, before anybody can read it.

     The same stylesheet and the same sanitiser as the real page — a preview
     that renders differently is worse than no preview — and deliberately not
     the same in three ways: it will show a draft, it does not count as a
     read, and it says so at the top so nobody mistakes it for the published
     thing. The comment thread is left off: a conversation that has not
     happened is not part of what this will look like. --}}
@extends('layouts.app')

@section('title', $post->title . ' — Preview')
@section('body-class', 'plaza-ground')
@section('page-title', 'Preview')
@section('page-subtitle', \Illuminate\Support\Str::limit($post->title, 40))

@push('head')
@include('community.partials.plaza-css')
<style>
    .article { max-width:44rem; margin:0 auto; }
    .bc { display:flex; align-items:center; flex-wrap:wrap; gap:.15rem; font-size:.8rem; margin-bottom:.9rem; }
    .bc a { display:inline-flex; align-items:center; padding:.3rem .55rem; border-radius:.5rem; font-weight:600; color:var(--color-brand-700); text-decoration:none; }
    .bc a:hover { background:var(--color-brand-50); }
    .bc .sep { color:var(--color-gray-300); }
    .bc .cur { padding:.3rem .55rem; color:var(--color-gray-500); font-weight:600; max-width:16rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    /* --- The head of an article: the cover, then the headline and who wrote
       it, in one piece. The cover used to float inset with rounded corners
       above a bare headline, which on a phone read as a picture that had
       wandered in from somewhere else. --- */
    .article-hero { position:relative; margin-bottom:1.15rem; border-radius:1.1rem; overflow:hidden;
        border:1px solid var(--color-gray-200); background:var(--color-white); box-shadow:var(--shadow-card); }
    .article-hero-body { padding:1.05rem 1.15rem 1.15rem; }
    .article-hero .article-cover { margin:0; border-radius:0; }
    @media (max-width:640px) {
        .article-hero { border-radius:0; border-left:0; border-right:0;
            margin-left:calc(var(--plaza-gutter, 1rem) * -1);
            margin-right:calc(var(--plaza-gutter, 1rem) * -1); }
        /* The breadcrumb trail wrapped to three lines here and said nothing
           the app bar's back arrow and title do not. */
        .bc { display:none; }
    }
    /* Who wrote it, with a face: the wall's way of introducing an author. */
    .article-byline { display:flex; align-items:center; gap:.6rem; margin-top:.85rem;
        padding-top:.8rem; border-top:1px solid var(--color-gray-100); }
    html.dark .article-byline { border-top-color:rgb(255 255 255 / .08); }
    .article-byline .avatar { width:2.1rem; height:2.1rem; font-size:.7rem; flex:none; }
    .article-byline b { display:block; font-size:.82rem; font-weight:800; color:var(--color-gray-800); line-height:1.25; }
    .article-byline i { display:block; font-style:normal; font-size:.72rem; color:var(--color-gray-400); }
    .article-cover { position:relative; border-radius:1rem; overflow:hidden; margin-bottom:1rem; background:var(--color-brand-50); }
    .article-cover img { width:100%; height:auto; display:block; max-height:22rem; object-fit:cover;
        opacity:0; transition:opacity .28s ease; }
    .article-cover img.is-loaded { opacity:1; }
    /* Hold a cover-shaped box and shimmer while the hero decodes, so the
       headline doesn't leap down the page when it lands. */
    .article-cover:not(:has(img.is-loaded)) { aspect-ratio:16/9; max-height:22rem; }
    .article-cover:not(:has(img.is-loaded))::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:articleShimmer 1.15s linear infinite; }
    @keyframes articleShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    @media (prefers-reduced-motion: reduce) {
        .article-cover:not(:has(img.is-loaded))::before { animation:none; background:rgb(255 255 255 / .25); }
    }
    .article-title { font-family:var(--font-heading); font-weight:800; font-size:1.5rem; line-height:1.22; color:var(--color-gray-900); }
    @media (min-width:640px) { .article-title { font-size:1.7rem; } }
    /* The sentence the article was filed under, in the reader's own words. */
    .article-standfirst { margin-top:.45rem; font-size:.92rem; line-height:1.5; color:var(--color-gray-500); }
    .article-body { font-size:.98rem; line-height:1.7; color:var(--color-gray-800); }
    .article-body p { margin:0 0 .9rem; }
    .article-body h3, .article-body h4 { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); margin:1.3rem 0 .5rem; }
    .article-body ul, .article-body ol { margin:0 0 .9rem 1.3rem; }
    .article-body blockquote { border-left:3px solid var(--color-brand-300); padding-left:.9rem; color:var(--color-gray-500); font-style:italic; }
    /* The rest of what a built page can hold. An article writes h2 for its
       sections, so it gets the size the old h3 had and h3 steps down. */
    .article-body h2 { font-family:var(--font-heading); font-weight:800; color:var(--color-gray-900);
        font-size:1.28rem; margin:1.6rem 0 .55rem; }
    .article-body .a-fig { margin:1.2rem 0; }
    .article-body .a-fig img, .article-body > img { width:100%; height:auto; border-radius:.85rem; display:block;
        box-shadow:0 10px 26px -20px rgb(0 0 0 / .55); }
    .article-body .a-cap { font-size:.82rem; color:var(--color-gray-500); margin-top:.45rem; text-align:center; }
    .article-body hr { border:0; border-top:1px solid var(--color-gray-200); margin:1.6rem 0; }
    /* A row of pictures that stays a row until the screen says otherwise. */
    .article-body .a-gallery { display:grid; grid-template-columns:repeat(auto-fit,minmax(9rem,1fr)); gap:.5rem; margin:1.2rem 0; }
    .article-body .a-gallery img { width:100%; height:100%; aspect-ratio:4/3; object-fit:cover; border-radius:.7rem; }
    /* A note the writer wants read, not skimmed. */
    .article-body .a-note { border:1px solid var(--color-brand-200); background:var(--color-brand-50);
        border-radius:.85rem; padding:.85rem 1rem; margin:1.2rem 0; }
    .article-body .a-note.is-warn { border-color:#f0c98a; background:#fff8ec; }
    .article-body .a-note b { display:block; margin-bottom:.2rem; color:var(--color-gray-900); }
    .article-body .a-btn { display:inline-block; background:var(--color-brand-600); color:#fff !important;
        font-weight:700; padding:.55rem 1.1rem; border-radius:999px; text-decoration:none; margin:.4rem 0; }
    .article-body .a-embed { position:relative; padding-top:56.25%; margin:1.2rem 0; border-radius:.85rem; overflow:hidden; }
    .article-body .a-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
    .article-body table { width:100%; border-collapse:collapse; margin:1.2rem 0; font-size:.9rem; }
    .article-body th, .article-body td { border:1px solid var(--color-gray-200); padding:.45rem .6rem; text-align:left; }
    .article-body th { background:var(--color-gray-50); font-weight:700; }
    .prev-flag { border:1px dashed var(--color-brand-300); background:var(--color-brand-50);
        color:var(--color-gray-700); border-radius:.75rem; padding:.6rem .85rem; margin-bottom:1rem;
        font-size:.85rem; }
    .prev-flag b { color:var(--color-brand-700); }
</style>
@endpush

@section('content')
<div class="article">
    <div class="prev-flag">
        <b>Preview</b> — this is how it will look in anee.io.
        {{ $post->isPublished ? 'This article is published.' : 'This article is still a draft; nobody else can open it.' }}
    </div>

    @php
        $byline = trim((string) $post->authorName) ?: 'anee.io Team';
        $bylineInitials = \Illuminate\Support\Str::of($byline)->explode(' ')
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    @endphp
    <div class="article-hero plaza-accent">
        @if ($post->coverUrl())
            <div class="article-cover"><img src="{{ $post->coverUrl() }}" alt=""
                onload="this.classList.add('is-loaded')" onerror="this.closest('.article-cover')?.remove()"></div>
        @endif
        <div class="article-hero-body">
            <h1 class="article-title">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="article-standfirst">{{ $post->excerpt }}</p>
            @endif
            <div class="article-byline">
                <span class="avatar {{ \App\Support\CommunityAvatar::hue($byline) }}">{{ $bylineInitials }}</span>
                <span class="min-w-0">
                    <b>{{ $byline }}</b>
                    <i>
                        @if ($post->publishedAt){{ $post->publishedAt->format('F j, Y') }} · @endif
                        {{ number_format($post->viewCount) }} {{ \Illuminate\Support\Str::plural('read', $post->viewCount) }}
                    </i>
                </span>
            </div>
        </div>
    </div>

    {{-- An article is written by staff in a builder, not by a member in a
         comment box, so it goes through the wider list — the one that lets a
         picture, a pull quote and a section heading survive. --}}
    <div class="article-body">{!! \App\Support\CommunityText::articleHtml($post->body) !!}</div>

</div>
@endsection
