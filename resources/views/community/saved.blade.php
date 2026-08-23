{{-- Everything this member kept.

     The same cards the wall draws, so a saved post behaves exactly as it did
     where it was found — you can still open its comments, share it on, or take
     the bookmark back off (which is how you remove one from here).

     Expects: $posts, $savedIds. --}}
@extends('layouts.app')

@section('title', 'Saved posts')
@section('body-class', 'plaza-ground')
@section('page-title', 'Saved posts')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')
@include('community.partials.nav', ['active' => 'wall'])

<div class="sv-wrap">
    <div class="sv-head">
        <h2 class="sv-title">Saved posts</h2>
        <p class="sv-sub">Only you can see these. Tap the bookmark on a post to take it off this list.</p>
    </div>

    @forelse ($posts as $post)
        @include('community.partials.feed-post', [
            'post' => $post,
            'friendIds' => $friendIds,
            'followingIds' => $followingIds,
            'savedIds' => $savedIds,
        ])
    @empty
        <div class="card p-8 text-center">
            <div class="empty-tile">🔖</div>
            <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Wala pang naka-save</p>
            <p class="text-sm text-gray-500 mt-1">
                When a post is worth coming back to — a fertiliser rate, a price, a photo of a pest —
                tap its bookmark and it waits here.
            </p>
            <a href="{{ route('community.index') }}" class="btn btn-primary btn-sm mt-4">Go to the wall</a>
        </div>
    @endforelse
</div>

@include('community.partials.post-actions')
@include('community.partials.wall-comments-modal')
@include('community.partials.report-js')
@endsection

@push('styles')
<style>
    .sv-wrap { max-width: 40rem; margin: 0 auto; }
    .sv-head { margin-bottom: 1rem; }
    .sv-title { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--color-gray-900); }
    .sv-sub { font-size: .85rem; color: var(--color-gray-500); margin-top: .2rem; line-height: 1.55; }
</style>
@endpush

@push('scripts')
@include('community.partials.views-js')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.react-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@endpush
