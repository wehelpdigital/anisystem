@extends('layouts.app')

@section('title', '#' . $tag . ' — Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('page-subtitle', 'Posts tagged #' . $tag)

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'wall'])

<div class="card p-4 mb-4 flex items-center gap-3 plaza-accent">
    <div class="avatar avatar-lg av-h4" style="font-size:1.4rem;">#</div>
    <div>
        <h2 class="font-bold text-gray-900 text-lg" style="font-family:var(--font-heading)">#{{ $tag }}</h2>
        <p class="text-sm text-gray-500">{{ $wallPosts->count() + $groupPosts->count() }} {{ \Illuminate\Support\Str::plural('post', $wallPosts->count() + $groupPosts->count()) }} with this tag</p>
    </div>
</div>

@if ($wallPosts->isEmpty() && $groupPosts->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">🔖</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Wala pang posts na may #{{ $tag }}</p>
        <p class="text-sm text-gray-500 mt-1">Be the first — use <strong>#{{ $tag }}</strong> in a post.</p>
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
            <div class="post-media media-skel"><img src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="" loading="lazy"
                onload="this.classList.add('is-loaded')" onerror="this.closest('.media-skel')?.classList.add('is-gone')"></div>
        @endif
        @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $post->id, 'summary' => $post->reactionSummary ?? null])
        <div class="mt-3 pt-2 border-t border-gray-100">
            <a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}#wallpost-{{ $post->id }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                💬 {{ $post->comments_count }} {{ $post->comments_count === 1 ? 'comment' : 'comments' }} — view on wall →
            </a>
        </div>
    </article>
@endforeach

@if ($groupPosts->isNotEmpty())
    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mt-6 mb-2">From your groups</p>
    @foreach ($groupPosts as $post)
        <article class="card p-4 mb-3">
            <header class="flex items-start gap-3">
                @include('community.partials.avatar', ['user' => $post->author, 'size' => 'avatar-md'])
                <div class="min-w-0 grow">
                    <p class="text-sm leading-tight">
                        <a href="{{ route('community.connect.profile', ['userId' => optional($post->author)->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ optional($post->author)->full_name ?: 'Member' }}</a>
                        @if ($post->group)<span class="text-xs text-gray-400">in <a href="{{ route('community.groups.show', ['id' => $post->group->id]) }}" class="text-brand-700 hover:text-brand-800">{{ $post->group->name }}</a></span>@endif
                    </p>
                    <p class="text-xs text-gray-400">{{ $post->created_at?->diffForHumans() }}</p>
                </div>
            </header>
            @if ($post->title)<h3 class="font-bold text-gray-900 mt-2" style="font-family:var(--font-heading)">{{ $post->title }}</h3>@endif
            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($post->body) !!}</p>
        </article>
    @endforeach
@endif
@endsection

@push('scripts')
@include('community.partials.react-js')
@endpush
