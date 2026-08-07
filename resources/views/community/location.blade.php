@extends('layouts.app')

@section('title', $label . ' — Community')
@section('page-title', 'Community')
@section('page-subtitle', $label)

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'wall'])

<div class="card p-4 mb-4 flex items-center gap-3">
    <div class="avatar avatar-lg" style="font-size:1.4rem;background:#fef3c7;">📍</div>
    <div class="min-w-0">
        <h2 class="font-bold text-gray-900 text-lg truncate" style="font-family:var(--font-heading)">{{ $label }}</h2>
        <p class="text-sm text-gray-500">
            {{ $members->count() }} {{ \Illuminate\Support\Str::plural('member', $members->count()) }}
            · {{ $wallPosts->count() }} {{ \Illuminate\Support\Str::plural('post', $wallPosts->count()) }}
        </p>
    </div>
</div>

{{-- Members from this place --}}
@if ($members->isNotEmpty())
    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Farmers here</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-6">
        @foreach ($members as $member)
            <a href="{{ route('community.connect.profile', ['userId' => $member->id]) }}"
                class="card card-hover p-3 flex items-center gap-2.5 min-w-0">
                @include('community.partials.avatar', ['user' => $member, 'size' => 'avatar-md'])
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $member->full_name }}</p>
                    @if (in_array((int) $member->id, $friendIds, true))
                        <span class="badge badge-green">Co-farmer</span>
                    @elseif ($member->statusBubble)
                        <p class="text-xs text-gray-400 truncate">{{ $member->statusBubble }}</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

{{-- Posts from / tagging this place --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Posts</p>
@if ($wallPosts->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">📍</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No posts from {{ $label }} yet</p>
        <p class="text-sm text-gray-500 mt-1">Mention <strong>📍{{ $label }}</strong> in a post to put it on the map.</p>
    </div>
@endif

@foreach ($wallPosts as $post)
    @php $author = $post->author; $place = trim(implode(', ', array_filter([$author->city, $author->province]))); @endphp
    <article class="card p-4 mb-3 wall-post" id="wallpost-{{ $post->id }}" data-post-id="{{ $post->id }}">
        <header class="flex items-start gap-3">
            @include('community.partials.avatar', ['user' => $author, 'size' => 'avatar-md'])
            <div class="min-w-0 grow">
                <p class="text-sm leading-tight">
                    <a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $author->full_name }}</a>
                    @if (in_array((int) $post->authorUserId, $friendIds, true))<span class="badge badge-green align-middle ml-1">Co-farmer</span>@endif
                </p>
                <p class="text-xs text-gray-400">@if ($place)📍 {{ $place }} · @endif{{ $post->created_at?->diffForHumans() }}</p>
            </div>
        </header>
        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($post->body) !!}</p>
        @if ($post->imagePath)
            <div class="post-media"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->imagePath) }}" alt="" loading="lazy"></div>
        @endif
        @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $post->id, 'summary' => $post->reactionSummary ?? null])
        <div class="mt-3 pt-2 border-t border-gray-100">
            <a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}#wallpost-{{ $post->id }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                💬 {{ $post->comments_count }} {{ $post->comments_count === 1 ? 'comment' : 'comments' }} — view on wall →
            </a>
        </div>
    </article>
@endforeach
@endsection

@push('scripts')
@include('community.partials.react-js')
@endpush
