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
                'img' => asset('images/icons/tea.png'),
                'badge' => null,
            ],
            [
                'label' => 'Expenses Report',
                'desc' => 'Every peso the season spends — materials, labor, services, stock buys and the day book — with the income beside it.',
                'url' => route('sm.expenses.report', ['id' => $schedule->id]),
                'img' => asset('images/icons/money-bag.png'),
                'badge' => null,
            ],
            [
                'label' => 'Profit Report',
                'desc' => 'The harvest against the whole spend — per lot, with margins, cost per kilo and honest warnings.',
                'url' => route('sm.profit.report', ['id' => $schedule->id]),
                'img' => asset('images/icons/profit.png'),
                'badge' => null,
            ],
            [
                'label' => 'Anee Season Report',
                'desc' => 'Anee reads the whole finished season — money, weather, harvest, your notes — and writes the debrief. 300 credits.',
                'url' => route('sm.anee.season', ['id' => $schedule->id]),
                'img' => asset('images/anee/emoji/thinking.png'),
                'badge' => null,
            ],
            [
                'label' => 'Analyze So Far',
                'desc' => 'A mid-season read: where the crop stands, the risks, and what to do next — rescue calls included. 200 credits.',
                'url' => route('sm.anee.sofar', ['id' => $schedule->id]),
                'img' => asset('images/icons/calendar.png'),
                'badge' => null,
            ],
            [
                'label' => 'View as Protocol',
                'desc' => 'Turn a lot\'s season into an easy-to-read recipe — every step by its day count, ending on the yield it made.',
                'url' => route('sm.protocol.report', ['id' => $schedule->id]),
                'img' => asset('images/icons/checklist.png'),
                'badge' => null,
            ],
            // Comparing is generating: the whole page is a Run button, so a
            // view-level worker is not offered a door that only answers no.
            ...(\App\Support\WorkerContext::canWriteModule('reports') ? [[
                'label' => 'Compare Reports',
                'desc' => 'Two saved reports, one above the other — with Anee\'s read of the difference if you want it (30 credits).',
                'url' => route('sm.compare.report', ['id' => $schedule->id]),
                'img' => asset('images/icons/ab-testing.png'),
                'badge' => null,
            ]] : []),
            // Post Harvest Observations lived here too, but it was only a link
            // to the module that already has its own tile in the hub — the
            // same screen offered twice.
        ];
    @endphp
    @foreach ($reports as $r)
        <a href="{{ $r['url'] }}" class="card card-hover block">
            <div class="p-4 flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
                    <img src="{{ $r['img'] }}" alt="" class="w-7 h-7" style="object-fit:contain" loading="lazy">
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
