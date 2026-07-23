@extends('layouts.app')

@section('title', 'Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Learn from other farmers')

@section('content')

<div class="card overflow-hidden mb-4">
    <div class="card-body bg-gradient-to-br from-brand-600 to-brand-800 !rounded-2xl text-white">
        <h2 class="text-xl md:text-2xl font-bold">Share a plan, see everyone else's</h2>
        <p class="text-sm text-brand-100 mt-1.5">
            The Community runs on give-and-take. Publish one of your cropping plans and you
            get to read, question and rate every plan other members have shared.
        </p>
    </div>
</div>

<div class="card p-5 mb-4">
    <h3 class="font-bold text-gray-900">What a plan needs before it can be shared</h3>
    <ul class="mt-3 space-y-2 text-sm text-gray-600">
        <li class="flex items-start gap-2">
            <svg class="w-5 h-5 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            At least {{ \App\Services\CommunityService::MIN_ACTIVITIES }} activities — enough for someone to actually follow the season.
        </li>
        <li class="flex items-start gap-2">
            <svg class="w-5 h-5 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            At least {{ \App\Services\CommunityService::MIN_LOTS }} lot, so people can see what it was grown on.
        </li>
    </ul>
    <p class="text-xs text-gray-500 mt-3">
        Publishing shares the plan itself — title, crop, lots and the activity timeline.
        Your workers, costs and post-harvest figures stay private.
    </p>
</div>

<h3 class="font-bold text-gray-900 mb-2">Your cropping plans</h3>

@forelse ($candidates as $schedule)
    <div class="card p-4 mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h4 class="font-bold text-gray-900 leading-snug">{{ $schedule->title }}</h4>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $schedule->eligibility['activities'] }} {{ \Illuminate\Support\Str::plural('activity', $schedule->eligibility['activities']) }}
                    · {{ $schedule->eligibility['lots'] }} {{ \Illuminate\Support\Str::plural('lot', $schedule->eligibility['lots']) }}
                </p>
            </div>
            @if ($schedule->eligibility['ok'])
                <button type="button" class="btn btn-primary btn-sm shrink-0 js-publish"
                        data-id="{{ $schedule->id }}" data-title="{{ $schedule->title }}">Share it</button>
            @else
                <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}" class="btn btn-white btn-sm shrink-0">Open</a>
            @endif
        </div>
        @if (! $schedule->eligibility['ok'])
            <ul class="mt-3 space-y-1">
                @foreach ($schedule->eligibility['reasons'] as $reason)
                    <li class="text-sm text-gray-500 flex items-start gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-500 mt-1.5 shrink-0"></span>{{ $reason }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@empty
    <div class="card p-8 text-center">
        <p class="font-semibold text-gray-700">No cropping plans yet</p>
        <p class="text-sm text-gray-500 mt-1">Build a season first, then come back and share it.</p>
        <a href="{{ route('sm.create') }}" class="btn btn-primary mt-4">New cropping schedule</a>
    </div>
@endforelse
@endsection

@push('sheets')
    @include('community.partials.publish-sheet')
@endpush

@push('scripts')
    @include('community.partials.publish-js')
@endpush
