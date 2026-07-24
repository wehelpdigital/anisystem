@extends('layouts.share')

@php
    use Illuminate\Support\Carbon;
    $dayType = $schedule->dayType ?: 'DAS';
    $today = Carbon::today()->format('Y-m-d');
@endphp

@section('share-title', $schedule->title)
@section('og-title', $schedule->title . ' — cropping plan')
@section('og-description', $ogDescription)
@section('og-type', 'article')

@section('content')
    {{-- Plan header --}}
    <div class="card p-5 md:p-6 mb-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 mb-1">Cropping plan</p>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight break-words">{{ $schedule->title }}</h1>
                @if($schedule->cropType || $schedule->cropVariety)
                    <p class="text-sm text-gray-500 mt-1">
                        {{ trim($schedule->cropType . ' ' . ($schedule->cropVariety ? '· ' . $schedule->cropVariety : '')) }}
                    </p>
                @endif
            </div>
        </div>

        @if($schedule->description)
            <div class="text-sm text-gray-700 mt-3 leading-relaxed break-words">{!! $schedule->description !!}</div>
        @endif

        <div class="flex flex-wrap gap-2 mt-4">
            <span class="badge badge-gray">{{ $schedule->lots->count() }} {{ \Illuminate\Support\Str::plural('lot', $schedule->lots->count()) }}</span>
            <span class="badge badge-gray">{{ $schedule->workers->count() }} {{ \Illuminate\Support\Str::plural('worker', $schedule->workers->count()) }}</span>
            <span class="badge badge-gray">{{ $schedule->activities->count() }} {{ \Illuminate\Support\Str::plural('activity', $schedule->activities->count()) }}</span>
            <span class="badge badge-green">Day counter: {{ $dayType }}</span>
        </div>
    </div>

    {{-- Lots --}}
    @if($schedule->lots->count())
        <section class="mb-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Lots</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($schedule->lots as $lot)
                    <div class="card p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-900 truncate">{{ $lot->lotName }}</span>
                            @if($lot->lotSize)
                                <span class="text-xs text-gray-500 shrink-0">{{ rtrim(rtrim(number_format((float) $lot->lotSize, 4, '.', ''), '0'), '.') }} {{ $lot->lotSizeUnit }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1 space-x-2">
                            @if($lot->variety)<span>{{ $lot->variety }}</span>@endif
                            @if(!empty($lotDayZero[$lot->id]))
                                <span>{{ $dayType }} 0: {{ $lotDayZero[$lot->id]->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Workers --}}
    @if($schedule->workers->count())
        <section class="mb-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Workers</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($schedule->workers as $worker)
                    <span class="item-tag worker-tag">{{ $worker->workerName }}</span>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Timeline --}}
    <section>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Schedule</h2>
        @if(count($timeline))
            <div class="space-y-4">
                @foreach($timeline as $dateKey => $rows)
                    @php
                        $isNoDate = $dateKey === 'no-date';
                        $dateC = $isNoDate ? null : Carbon::parse($dateKey);
                        $isToday = $dateKey === $today;
                    @endphp
                    <div class="share-day{{ $isToday ? ' share-day-today' : '' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-semibold text-gray-900">
                                {{ $isNoDate ? 'Unscheduled' : $dateC->format('l, M j, Y') }}
                            </h3>
                            @if($isToday)<span class="badge badge-green">Today</span>@endif
                        </div>
                        <div class="space-y-2">
                            @foreach($rows as $row)
                                @php $a = $row['activity']; @endphp
                                <div class="card p-3.5 share-activity prio-{{ $a->priority }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-semibold text-gray-900 leading-snug break-words">{{ $a->activityTitle }}</h4>
                                        <span class="pill pill-{{ $a->priority }} shrink-0">{{ ucfirst($a->priority) }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        @foreach($row['das'] as $d)
                                            <span class="item-tag lot-tag">{{ $d['lot'] }}@if($d['das']) · {{ $d['das'] }}@endif</span>
                                        @endforeach
                                    </div>
                                    @if($a->description)
                                        <div class="text-sm text-gray-700 mt-2 leading-relaxed break-words">{!! $a->description !!}</div>
                                    @endif
                                    @if($a->imageUrl())
                                        <div class="mt-2"><img src="{{ $a->imageUrl() }}" alt="Reference" loading="lazy" class="rounded-lg max-h-64 w-auto"></div>
                                    @endif
                                    @if($a->workers->count())
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @foreach($a->workers as $w)
                                                <span class="item-tag worker-tag">{{ $w->workerName }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card p-6 text-center text-sm text-gray-500">No activities scheduled yet.</div>
        @endif
    </section>
@endsection
