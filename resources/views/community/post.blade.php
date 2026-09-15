{{-- One post, on a page of its own.

     A share quotes the post it carries, and "view original" had nowhere to
     send anybody: the quote linked to the wall with #wallpost-N on the end,
     which only lands if that post happens to be on the first page of it. A
     post older than a screenful was simply unreachable.

     The same card the wall draws, so everything on it still works — comments,
     reactions, saving, sharing it on.

     Expects: $post, $savedIds, $friendIds, $followingIds. --}}
@extends('layouts.app')

@section('title', 'Post')
@section('body-class', 'plaza-ground')
@section('page-title', 'Post')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')

{{-- Two columns on a wide screen: the post, and the rail (your chats, the rooms, the wall). --}}
<div class="plaza-shell">
<div class="plaza-center">
<div class="pp-wrap">
    @include('community.partials.feed-post', [
        'post' => $post,
        'friendIds' => $friendIds,
        'followingIds' => $followingIds,
        'savedIds' => $savedIds,
    ])
    <a href="{{ route('community.index') }}" class="btn btn-white btn-sm pp-back">Back to the News Feed</a>
</div>

@include('community.partials.post-actions')
@include('community.partials.wall-comments-modal')
@include('community.partials.report-js')
</div>{{-- /plaza-center --}}
<aside class="plaza-side plaza-side-right">
    @include('community.partials.plaza-rail', ['rail' => ['chats', 'discussions', 'posts']])
</aside>
</div>{{-- /plaza-shell --}}
@endsection

@push('styles')
<style>
    .pp-wrap { max-width: 40rem; margin: 0 auto; }
    /* Beside the rail the post has a column of its own and fills it. */
    @media (min-width: 1024px) { .pp-wrap { max-width: none; } }
    .pp-back { display: block; width: 100%; text-align: center; margin-top: .5rem; }
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
