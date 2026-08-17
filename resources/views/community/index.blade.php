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

<div id="plansList">
@foreach ($plans as $plan)
    @include('community.partials.plan-card-row', ['plan' => $plan])
@endforeach
</div>
@include('partials.list-pager', ['noun' => 'plan', 'paginator' => $plans,
    'rowsUrl' => route('community.plans') . '?rows=1&q=' . urlencode($filters['q'] ?? '') . '&crop=' . urlencode($filters['crop'] ?? '')])
@if ($plans->total() === 0)
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
@endif

@endsection

@push('sheets')
    @include('community.partials.publish-sheet')
@endpush

@push('scripts')
    @include('community.partials.publish-js')
@endpush
