@extends('layouts.app')

@section('title', 'Reports — ' . $schedule->title)
@section('page-title', 'Reports')
@section('page-subtitle', $schedule->title)
@section('help-key', 'reports')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    @php
        $reports = [
            [
                'label' => 'Labor Report',
                'desc' => 'Worker days and labor cost across the schedule.',
                'url' => route('sm.labor.report', ['id' => $schedule->id]),
                'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
                'badge' => null,
            ],
            [
                'label' => 'Expenses Report',
                'desc' => 'Every peso the season spends — materials, labor, services, stock buys and the day book — with the income beside it.',
                'url' => route('sm.expenses.report', ['id' => $schedule->id]),
                'icon' => 'M9 17v-4m3 4v-6m3 6v-2M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z',
                'badge' => null,
            ],
            [
                'label' => 'Profit Report',
                'desc' => 'The harvest against the whole spend — per lot, with margins, cost per kilo and honest warnings.',
                'url' => route('sm.profit.report', ['id' => $schedule->id]),
                'icon' => 'M3 17l6-6 4 4 8-8m0 0v5m0-5h-5',
                'badge' => null,
            ],
            [
                'label' => 'Post Harvest Report',
                'desc' => 'Yield & revenue vs. materials + labour + expenses, with savable copies.',
                'url' => route('sm.revenue-report', ['id' => $schedule->id]),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'badge' => null,
            ],
            // Post Harvest Observations lived here too, but it was only a link
            // to the module that already has its own tile in the hub — the
            // same screen offered twice.
        ];
    @endphp
    @foreach ($reports as $r)
        <a href="{{ $r['url'] }}" class="card card-hover block">
            <div class="p-4 flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}"/></svg>
                </div>
                <div class="min-w-0 grow">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900">{{ $r['label'] }}</span>
                        @if ($r['badge'] !== null && $r['badge'] > 0)<span class="badge badge-green">{{ $r['badge'] }}</span>@endif
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $r['desc'] }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
    @endforeach
</div>
@endsection
