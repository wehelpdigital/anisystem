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
    <div class="masonry-2">
        @foreach ($friends as $friend)
            @php
                $latest = $latestPosts->get($friend->id);
                $place = trim(implode(', ', array_filter([$friend->city, $friend->province])));
            @endphp
            <div class="card p-4 flex flex-col">
                {{-- Name + photo link straight to the wall — no separate "Visit wall" button. --}}
                <div class="flex items-start gap-3">
                    @include('community.partials.avatar-status', ['user' => $friend, 'size' => 'avatar-lg'])
                    <div class="min-w-0 grow">
                        <a href="{{ route('community.connect.profile', ['userId' => $friend->id]) }}" class="font-bold text-gray-900 hover:text-brand-700 leading-snug block" style="font-family:var(--font-heading)">{{ $friend->full_name }}</a>
                        @if (filled($friend->headline))<p class="text-xs text-gray-600 font-medium mt-0.5 line-clamp-1">{{ $friend->headline }}</p>@endif
                        @if ($place)<p class="text-xs text-gray-500 mt-0.5">📍 {{ $place }}</p>@endif
                        @if ($friend->bio)<p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $friend->bio }}</p>@endif
                    </div>
                    @include('community.partials.dm-btn', ['user' => $friend])
                </div>
                @include('community.partials.farm-chips', ['user' => $friend])
                <div class="mt-3 pt-3 border-t border-gray-100 grow">
                    @if ($latest)
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Latest</p>
                        {{-- Commentable inline: react, comment, reply (with emoji + photo) right here. --}}
                        <div class="wall-post" id="wallpost-{{ $latest->id }}" data-post-id="{{ $latest->id }}">
                            @if ($latest->body)
                                <div class="plaza-clamp" data-clamp>
                                    <div class="plaza-clamp-body text-sm text-gray-700 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($latest->body) !!}</div>
                                    <button type="button" class="plaza-clamp-toggle hidden">Read more</button>
                                </div>
                            @endif
                            @if ($latest->imagePath)
                                <img src="{{ \App\Support\MediaStore::url($latest->imagePath) }}"
                                     alt="Photo from {{ $friend->full_name }}" loading="lazy"
                                     class="post-img rounded-lg mt-2 max-h-40 w-auto border border-gray-100">
                            @elseif (!$latest->body)
                                <p class="text-sm text-gray-400">📷 Shared a photo.</p>
                            @endif
                            <p class="text-[11px] text-gray-400 mt-1">{{ $latest->created_at?->diffForHumans() }}</p>
                            @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $latest->id, 'summary' => $latest->reactionSummary ?? null])
                            @php $tops = $latest->comments->whereNull('parentId')->sortBy('id')->values(); @endphp
                            <div class="mt-2 space-y-1.5 wall-comments">
                                @if ($tops->count() > 1)
                                    <button type="button" class="js-view-all-comments text-xs font-semibold text-brand-700 hover:text-brand-800" data-post-id="{{ $latest->id }}">View all {{ $latest->comments->count() }} comments</button>
                                @endif
                                @foreach ($tops->slice(-1) as $comment)
                                    @include('community.connect.partials.wall-comment', [
                                        'comment' => $comment,
                                        'isReply' => false,
                                        'replies' => $latest->comments->where('parentId', $comment->id)->sortBy('id'),
                                    ])
                                @endforeach
                            </div>
                            @include('community.partials.wall-comment-form', ['postId' => $latest->id])
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Wala pang bago sa wall.</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
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
