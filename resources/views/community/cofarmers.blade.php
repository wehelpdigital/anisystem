@extends('layouts.app')

@section('title', 'My Co-Farmers — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Your co-farmers and their latest')

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'cofarmers'])

@if ($friends->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">🤝</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Wala ka pang co-farmers</p>
        <p class="text-sm text-gray-500 mt-1 mb-4">Connect with members para makita mo dito ang mga balita nila.</p>
        <a href="{{ route('community.connect.members') }}" class="btn btn-primary">Find members</a>
    </div>
@else
    <div class="masonry-2" id="cofarmersGrid">
        @foreach ($friends as $friend)
            @include('community.partials.cofarmer-card', ['friend' => $friend, 'latestPosts' => $latestPosts])
        @endforeach
    </div>
    @include('partials.list-pager', ['noun' => 'co-farmer', 'paginator' => $friends,
        'rowsUrl' => route('community.cofarmers') . '?rows=1'])
@endif

@include('community.partials.wall-comments-modal')
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.clamp-js')
@endpush
