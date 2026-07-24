@extends('layouts.share')

@php
    use Illuminate\Support\Carbon;
    $dayType = $schedule->dayType ?: 'DAS';
    $isToday = $date->isSameDay(Carbon::today());
@endphp

@section('share-title', $date->format('M j, Y') . ' — ' . $schedule->title)
@section('og-title', $schedule->title . ' · ' . $date->format('M j, Y'))
@section('og-description', $ogDescription)
@section('og-type', 'article')

@section('content')
    <div class="mb-4">
        <a href="{{ route('share.schedule', $schedule->shareToken) }}" class="text-sm font-semibold text-brand-600 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ $schedule->title }}
        </a>
    </div>

    {{-- Day header --}}
    <div class="card p-5 md:p-6 mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 mb-1">
            {{ $isToday ? 'Today' : $date->format('l') }}
        </p>
        <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight">{{ $date->format('F j, Y') }}</h1>
        <div class="flex flex-wrap gap-2 mt-3">
            <span class="badge badge-green">{{ count($rows) }} {{ \Illuminate\Support\Str::plural('activity', count($rows)) }}</span>
            @if ($schedule->cropType)<span class="badge badge-gray">{{ $schedule->cropType }}</span>@endif
        </div>
    </div>

    {{-- The day's activities --}}
    @if (count($rows))
        <div class="space-y-2">
            @foreach ($rows as $row)
                @php $a = $row['activity']; @endphp
                <div class="card p-3.5 share-activity prio-{{ $a->priority }}">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-gray-900 leading-snug break-words">
                            {{ $a->activityTitle }}
                            @unless ($row['isStart'])
                                <span class="text-xs font-normal text-gray-400">(continues)</span>
                            @endunless
                        </h3>
                        <span class="pill pill-{{ $a->priority }} shrink-0">{{ ucfirst($a->priority) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        @foreach ($row['das'] as $d)
                            <span class="item-tag lot-tag">{{ $d['lot'] }}@if($d['das']) · {{ $d['das'] }}@endif</span>
                        @endforeach
                    </div>
                    @if ($a->description)
                        <div class="text-sm text-gray-700 mt-2 leading-relaxed break-words">{!! $a->description !!}</div>
                    @endif
                    @if ($a->imageUrl())
                        <div class="mt-2"><img src="{{ $a->imageUrl() }}" alt="Reference" loading="lazy" class="rounded-lg max-h-64 w-auto"></div>
                    @endif
                    @if ($a->workers->count())
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach ($a->workers as $w)
                                <span class="item-tag worker-tag">{{ $w->workerName }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="card p-6 text-center text-sm text-gray-500">Nothing scheduled for this day.</div>
    @endif
@endsection
