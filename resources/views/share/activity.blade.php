@extends('layouts.share')

@php
    use Illuminate\Support\Carbon;
    $dayType = $schedule->dayType ?: 'DAS';
    $startC = $activity->targetDate ? Carbon::parse($activity->targetDate) : null;
    $endC = $activity->targetEndDate ? Carbon::parse($activity->targetEndDate) : null;
    $isRange = $startC && $endC && $endC->greaterThan($startC);
@endphp

@section('share-title', $activity->activityTitle)
@section('og-title', $activity->activityTitle)
@section('og-description', $ogDescription)
@section('og-type', 'article')
@if($activity->imageUrl())
    @section('og-image', $activity->imageUrl())
@endif

@section('content')
    <div class="mb-4">
        <a href="{{ route('share.schedule', $schedule->shareToken) }}" class="text-sm font-semibold text-brand-600 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ $schedule->title }}
        </a>
    </div>

    <div class="card p-5 md:p-6 share-activity prio-{{ $activity->priority }}">
        <div class="flex items-start justify-between gap-3">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight break-words">{{ $activity->activityTitle }}</h1>
            <span class="pill pill-{{ $activity->priority }} shrink-0">{{ ucfirst($activity->priority) }}</span>
        </div>

        <div class="flex flex-wrap gap-2 mt-3">
            @if($startC)
                <span class="badge badge-green">
                    {{ $startC->format('l, M j, Y') }}@if($isRange) → {{ $endC->format('M j') }}@endif
                </span>
            @else
                <span class="badge badge-gray">Unscheduled</span>
            @endif
        </div>

        @if(count($dasLabels))
            <div class="flex flex-wrap gap-1.5 mt-3">
                @foreach($dasLabels as $d)
                    <span class="item-tag lot-tag">{{ $d['lot'] }}@if($d['das']) · {{ $d['das'] }}@endif</span>
                @endforeach
            </div>
        @endif

        @if($activity->description)
            <div class="text-sm text-gray-700 mt-4 leading-relaxed break-words">{!! $activity->description !!}</div>
        @endif

        @if($activity->imageUrl())
            <div class="mt-4"><img src="{{ $activity->imageUrl() }}" alt="Reference" loading="lazy" class="rounded-lg max-h-80 w-auto"></div>
        @endif

        @if($activity->workers->count())
            <div class="mt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Assigned</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($activity->workers as $w)
                        <span class="item-tag worker-tag">{{ $w->workerName }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
