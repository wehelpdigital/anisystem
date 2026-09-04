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
                'label' => 'Anee Season Report',
                'desc' => 'Anee reads the whole finished season — money, weather, harvest, your notes — and writes the debrief. 300 credits.',
                'url' => route('sm.anee.season', ['id' => $schedule->id]),
                'icon' => 'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5',
                'badge' => null,
            ],
            [
                'label' => 'Analyze So Far',
                'desc' => 'A mid-season read: where the crop stands, the risks, and what to do next — rescue calls included. 200 credits.',
                'url' => route('sm.anee.sofar', ['id' => $schedule->id]),
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'badge' => null,
            ],
            [
                'label' => 'View as Protocol',
                'desc' => 'Turn a lot\'s season into an easy-to-read recipe — every step by its day count, ending on the yield it made.',
                'url' => route('sm.protocol.report', ['id' => $schedule->id]),
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h4',
                'badge' => null,
            ],
            [
                'label' => 'Compare Reports',
                'desc' => 'Two saved reports, one above the other — with Anee\'s read of the difference if you want it (30 credits).',
                'url' => route('sm.compare.report', ['id' => $schedule->id]),
                'icon' => 'M9 4v16m6-16v16M4 8h4m8 0h4M4 16h4m8 0h4',
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
