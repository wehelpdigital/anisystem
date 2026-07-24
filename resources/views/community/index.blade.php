@extends('layouts.app')

@section('title', 'Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Crop plans shared by other members')

@push('head')
    <style>
        .stars { display: inline-flex; gap: .05rem; }
        .stars svg { width: .85rem; height: .85rem; }
        .star-on { color: #f5c518; }
        .star-off { color: #d1d5db; }
        html.dark .star-off { color: #3a414c; }
    </style>
@endpush

@section('content')

{{-- Your own shared plans, so the owner can find their inbox --}}
@if ($myPlans->isNotEmpty())
    <div class="card p-4 mb-4">
        <h3 class="font-bold text-gray-900 mb-3">Your shared plans</h3>
        <div class="space-y-2">
            @foreach ($myPlans as $mine)
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('community.show', ['id' => $mine->id]) }}" class="min-w-0 grow">
                        <span class="block font-semibold text-gray-900 truncate">{{ $mine->title }}</span>
                        <span class="block text-xs text-gray-500">
                            {{ $mine->commentCount }} {{ \Illuminate\Support\Str::plural('comment', $mine->commentCount) }}
                            @if ($mine->ratingCount)
                                · {{ $mine->avgRating }}★ from {{ $mine->ratingCount }}
                            @else
                                · not rated yet
                            @endif
                        </span>
                    </a>
                    <button type="button" class="btn btn-white btn-sm shrink-0 js-unpublish"
                            data-id="{{ $mine->id }}" data-title="{{ $mine->title }}">Unshare</button>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Search + crop filter --}}
<form method="GET" action="{{ route('community.index') }}" class="mb-4">
    <div class="relative">
        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" name="q" value="{{ $filters['q'] }}" class="form-input pl-10!"
               placeholder="Search crop, variety, place or plan name…">
    </div>
    @if ($crops->isNotEmpty())
        <div class="scroll-chips mt-3">
            <a href="{{ route('community.index', array_filter(['q' => $filters['q']])) }}"
               class="chip shrink-0 {{ $filters['crop'] === '' ? 'is-selected' : '' }}">All crops</a>
            @foreach ($crops as $crop)
                <a href="{{ route('community.index', array_filter(['q' => $filters['q'], 'crop' => $crop])) }}"
                   class="chip shrink-0 {{ $filters['crop'] === $crop ? 'is-selected' : '' }}">{{ $crop }}</a>
            @endforeach
        </div>
    @endif
</form>

@forelse ($plans as $plan)
    <a href="{{ route('community.show', ['id' => $plan->id]) }}" class="card p-4 mb-3 block hover:shadow-card-lg transition">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900 leading-snug">{{ $plan->title }}</h3>
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                    @if ($plan->cropType)
                        <span class="badge badge-green">{{ $plan->cropType }}</span>
                    @endif
                    @if ($plan->cropVariety)
                        <span class="badge badge-gray">{{ $plan->cropVariety }}</span>
                    @endif
                    @if ($plan->publicRegion)
                        <span class="badge badge-gray">{{ $plan->publicRegion }}</span>
                    @endif
                </div>
            </div>
            <div class="shrink-0 text-right">
                @include('community.partials.stars', ['value' => $plan->avgRating])
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $plan->ratingCount ? $plan->avgRating . ' · ' . $plan->ratingCount : 'No ratings' }}
                </p>
            </div>
        </div>

        @if ($plan->publicSummary)
            <p class="text-sm text-gray-600 mt-2">{{ $plan->publicSummary }}</p>
        @endif

        <p class="text-xs text-gray-500 mt-3">
            {{ $plan->activityCount }} {{ \Illuminate\Support\Str::plural('step', $plan->activityCount) }}
            · {{ $plan->commentCount }} {{ \Illuminate\Support\Str::plural('comment', $plan->commentCount) }}
            · shared by {{ optional($plan->owner)->firstName ?: 'a member' }}
            @if ($plan->publishedAt)
                {{ $plan->publishedAt->diffForHumans() }}
            @endif
        </p>
    </a>
@empty
    <div class="card p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
        <p class="font-semibold text-gray-700 mt-3">
            {{ ($filters['q'] || $filters['crop']) ? 'Nothing matches that search' : 'No plans shared yet' }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
            {{ ($filters['q'] || $filters['crop'])
                ? 'Try a different crop or place.'
                : 'You are early. Yours will be the first one other members read.' }}
        </p>
        @if ($filters['q'] || $filters['crop'])
            <a href="{{ route('community.index') }}" class="btn btn-white mt-4">Clear search</a>
        @endif
    </div>
@endforelse
@endsection

@push('sheets')
    @include('community.partials.publish-sheet')
@endpush

@push('scripts')
    @include('community.partials.publish-js')
@endpush
