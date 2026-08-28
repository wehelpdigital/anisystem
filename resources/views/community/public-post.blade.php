{{-- A shared post, read by somebody who is not (yet) a member.

     Deliberately NOT the member feed-post partial: that one carries actions
     this reader cannot take. Here the post is quiet and complete, and every
     way to join in is an invitation to sign in — a page that looks dead gives
     a visitor no reason to sign up.

     Expects: $post (the share, possibly), $shown (what was actually written),
     $ogTitle, $ogDescription, $ogImage. --}}
@extends('layouts.public')

@section('title', $ogTitle)
@section('meta_description', $ogDescription)

@push('head')
    {{-- The link preview. Absolute URLs on purpose: a scraper has no page to
         resolve a relative one against. --}}
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="anee.io">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="{{ $shown->imagePath ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    {{-- One post is not a sitemap: let it be previewed, not indexed as if the
         community were public. --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="pp-wrap">
    <article class="pp-card">
        @php
            $author = $shown->author;
            $when = $shown->created_at?->diffForHumans();
            $sharer = $post->sharedPost ? $post->author : null;
        @endphp

        @if ($sharer)
            <p class="pp-shared">{{ $sharer->full_name }} shared this{{ $post->body ? ':' : '' }}</p>
            @if ($post->body)
                <p class="pp-note">{{ $post->body }}</p>
            @endif
        @endif

        <div class="pp-head">
            <span class="pp-face">
                @if ($author?->avatarPath)
                    <img src="{{ \App\Support\MediaStore::url($author->avatarPath) }}" alt="">
                @else
                    {{ $author?->initials ?: '?' }}
                @endif
            </span>
            <div class="min-w-0">
                <p class="pp-name">{{ $author?->full_name ?: 'A farmer' }}</p>
                <p class="pp-when">{{ $when }}@if ($author?->city) · {{ $author->city }}@endif</p>
            </div>
        </div>

        @if (trim((string) $shown->body) !== '')
            <p class="pp-body">{{ $shown->body }}</p>
        @endif

        @if ($shown->videoPath)
            <video class="pp-media" controls preload="metadata"
                   @if ($shown->videoPoster) poster="{{ \App\Support\MediaStore::url($shown->videoPoster) }}" @endif>
                <source src="{{ \App\Support\MediaStore::url($shown->videoPath) }}">
            </video>
        @elseif ($shown->imagePath)
            <img class="pp-media" src="{{ \App\Support\MediaStore::url($shown->imagePath) }}" alt="">
        @endif

        {{-- The invitation. It says what the numbers are, then asks. --}}
        <div class="pp-foot">
            <span class="pp-count">💬 {{ $post->comment_count }} {{ \Illuminate\Support\Str::plural('comment', $post->comment_count) }}</span>
            <a href="{{ route('signup') }}" class="btn btn-primary btn-sm">Join to reply</a>
        </div>
    </article>

    <div class="pp-join">
        <p class="pp-join-t">This is a post from anee.io</p>
        <p class="pp-join-s">A place where Filipino farmers plan their seasons and compare what actually worked. Sign in to comment, react and follow.</p>
        <div class="pp-join-b">
            <a href="{{ route('signup') }}" class="btn btn-primary">Create a free account</a>
            <a href="{{ route('login') }}" class="btn btn-white">I already have one</a>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .pp-wrap { max-width: 40rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
    .pp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.1rem;
        box-shadow: 0 1px 2px rgb(0 0 0 / .05), 0 8px 24px -18px rgb(0 0 0 / .35); }
    .pp-shared { font-size: .8rem; font-weight: 700; color: #6b7280; }
    .pp-note { font-size: .95rem; color: #1f2937; margin: .25rem 0 .75rem; line-height: 1.6; }
    .pp-head { display: flex; align-items: center; gap: .7rem; }
    .pp-face { width: 2.75rem; height: 2.75rem; border-radius: 999px; flex: none; overflow: hidden;
        display: flex; align-items: center; justify-content: center; background: #eef2e6;
        color: #3d6823; font-weight: 800; }
    .pp-face img { width: 100%; height: 100%; object-fit: cover; }
    .pp-name { font-weight: 700; color: #111827; }
    .pp-when { font-size: .75rem; color: #9ca3af; }
    .pp-body { margin-top: .8rem; font-size: 1rem; line-height: 1.65; color: #1f2937; white-space: pre-wrap; }
    .pp-media { display: block; width: 100%; border-radius: .75rem; margin-top: .9rem; }
    .pp-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        margin-top: 1rem; padding-top: .8rem; border-top: 1px solid #f3f4f6; }
    .pp-count { font-size: .82rem; font-weight: 700; color: #6b7280; }
    .pp-join { text-align: center; margin-top: 1.25rem; padding: 1.25rem 1rem;
        border: 1px dashed #d1d5db; border-radius: 1rem; background: #fbfdf9; }
    .pp-join-t { font-weight: 800; color: #111827; }
    .pp-join-s { font-size: .9rem; color: #6b7280; margin-top: .35rem; line-height: 1.6; }
    .pp-join-b { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; margin-top: .9rem; }
</style>
@endpush
