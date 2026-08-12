@extends('layouts.app')

@section('title', 'Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Crop plans shared by other members')

@php use App\Support\CommunityAvatar; @endphp

@push('head')
    @include('community.partials.plaza-css')
    <style>
        .chip.is-selected { box-shadow: 0 2px 8px -2px rgb(74 124 42 / .45); }
        .plan-row { border-radius: .75rem; padding: .5rem .75rem; margin: 0 -.25rem; transition: background-color .18s cubic-bezier(.22,1,.36,1); }
        .plan-row:hover { background: var(--color-gray-100); }
        .stars { display: inline-flex; gap: .05rem; }
        .stars svg { width: .85rem; height: .85rem; }
        .star-on { color: #f5c518; }
        .star-off { color: #d1d5db; }
        html.dark .star-off { color: #3a414c; }
    </style>
@endpush

@section('content')

{{-- Community sections --}}
@include('community.partials.nav', ['active' => 'plans'])

{{-- Your own shared plans, so the owner can find their inbox --}}
@if ($myPlans->isNotEmpty())
    <div class="card p-4 mb-4">
        <h3 class="font-bold text-gray-900 mb-3">Your shared plans</h3>
        <div class="space-y-2">
            @foreach ($myPlans as $mine)
                <div class="flex items-center justify-between gap-3 plan-row">
                    <a href="{{ route('community.show', ['id' => $mine->id]) }}" class="flex items-center gap-2.5 min-w-0 grow">
                        <span class="avatar avatar-sm {{ CommunityAvatar::hue(auth()->user()->full_name ?? '?') }}">{{ auth()->user()->initials ?? '?' }}</span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-900 truncate">{{ $mine->title }}</span>
                            <span class="block text-xs text-gray-500">
                                💬 {{ $mine->commentCount }}
                                @if ($mine->ratingCount)
                                    · {{ $mine->avgRating }}<span class="star-on">★</span> from {{ $mine->ratingCount }}
                                @else
                                    · not rated yet
                                @endif
                            </span>
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
<form method="GET" action="{{ route('community.plans') }}" class="mb-4">
    <div class="relative">
        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" name="q" value="{{ $filters['q'] }}" class="form-input pl-10!"
               placeholder="Search crop, variety, place or plan name…">
    </div>
    @if ($crops->isNotEmpty())
        <div class="scroll-chips mt-3">
            <a href="{{ route('community.plans', array_filter(['q' => $filters['q']])) }}"
               class="chip shrink-0 {{ $filters['crop'] === '' ? 'is-selected' : '' }}">All crops</a>
            @foreach ($crops as $crop)
                <a href="{{ route('community.plans', array_filter(['q' => $filters['q'], 'crop' => $crop])) }}"
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

        <p class="text-xs text-gray-500 mt-3 flex items-center flex-wrap gap-x-1.5 gap-y-1">
            <span>📋 {{ $plan->activityCount }} {{ \Illuminate\Support\Str::plural('step', $plan->activityCount) }}</span>
            <span>· 💬 {{ $plan->commentCount }}</span>
            <span class="inline-flex items-center gap-1.5">· <span class="avatar overflow-hidden {{ CommunityAvatar::hue(optional($plan->owner)->full_name ?: '?') }}" style="width:1.5rem;height:1.5rem;font-size:.55rem;">@if (optional($plan->owner)->avatarPath)<img src="{{ \App\Support\MediaStore::url($plan->owner->avatarPath) }}" alt="" class="w-full h-full object-cover">@else{{ optional($plan->owner)->initials ?: '?' }}@endif</span>
            <span class="font-medium text-gray-700">{{ optional($plan->owner)->full_name ?: 'a member' }}</span></span>
            @if (filled(optional($plan->owner)->statusBubble))
                <span class="text-brand-700 font-medium">· 💭 {{ \Illuminate\Support\Str::limit($plan->owner->statusBubble, 32) }}</span>
            @endif
            @if ($plan->publishedAt)
                <span>{{ $plan->publishedAt->diffForHumans() }}</span>
            @endif
        </p>
    </a>
@empty
    <div class="card p-8 text-center">
        <div class="empty-tile">🌾</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">
            {{ ($filters['q'] || $filters['crop']) ? 'Nothing matches that search' : 'Wala pang shared plans 🌱' }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
            {{ ($filters['q'] || $filters['crop'])
                ? 'Try a different crop or place.'
                : 'You are early. Yours will be the first one other members read.' }}
        </p>
        @if ($filters['q'] || $filters['crop'])
            <a href="{{ route('community.plans') }}" class="btn btn-white mt-4">Clear search</a>
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
