@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your farm at a glance')

@section('content')
@php
    $status = $subscription?->effective_status;
    $daysRemaining = $subscription?->daysRemaining();
    $isActive = $status === \App\Models\Subscription::STATUS_ACTIVE;
    $expiringSoon = $isActive && $daysRemaining !== null && $daysRemaining <= 7;

    $scheduleBadge = fn (?string $s) => match ($s) {
        'draft' => ['badge-gray', 'Draft'],
        'setup' => ['badge-yellow', 'Setting up'],
        'generated' => ['badge-green', 'Generated'],
        'completed' => ['badge-blue', 'Completed'],
        'archived' => ['badge-gray', 'Archived'],
        default => ['badge-gray', ucfirst((string) $s)],
    };
@endphp

<div class="max-w-4xl mx-auto space-y-5 md:space-y-6">

    {{-- Greeting card --}}
    <div class="card overflow-hidden">
        <div class="card-body bg-gradient-to-br from-brand-600 to-brand-800 !rounded-2xl text-white">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl md:text-2xl font-bold">Magandang araw, {{ $user->firstName }}!</h2>
                    <p class="text-sm text-brand-100 mt-1">Ready to plan a productive cropping season?</p>
                </div>
                <div class="shrink-0 text-right">
                    @if ($isActive)
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $expiringSoon ? 'bg-accent-500 text-ink' : 'bg-white/15 text-white' }}">
                            @if ($expiringSoon)
                                ⚠️ {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                            @else
                                {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                            @endif
                        </span>
                    @elseif ($status === 'pending')
                        <span class="inline-flex items-center rounded-full bg-accent-500 text-ink px-3 py-1.5 text-xs font-bold">Verification pending</span>
                    @else
                        <a href="{{ route('purchase.plans') }}" class="inline-flex items-center rounded-full bg-accent-500 text-ink px-3 py-1.5 text-xs font-bold hover:bg-accent-600 transition">Subscribe now</a>
                    @endif
                </div>
            </div>
            @if ($expiringSoon)
                <a href="{{ route('purchase.plans') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-accent-300 hover:text-accent-400">
                    Renew your subscription before it expires
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-3 gap-3 md:gap-4">
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-2xl md:text-3xl font-extrabold text-brand-700">{{ number_format($scheduleCount) }}</p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-0.5">My Schedules</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-sm md:text-lg font-extrabold text-gray-900 leading-tight pt-1.5 md:pt-2 truncate">
                    {{ $isActive ? $subscription->planName : '—' }}
                </p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-1">Active Plan</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-2xl md:text-3xl font-extrabold {{ $expiringSoon ? 'text-orange-600' : 'text-brand-700' }}">
                    {{ $isActive && $daysRemaining !== null ? number_format($daysRemaining) : '—' }}
                </p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-0.5">Days Remaining</p>
            </div>
        </div>
    </div>

    {{-- My Cropping Schedules --}}
    <div>
        <div class="flex items-center justify-between gap-3 mb-3 px-1">
            <h2 class="text-base md:text-lg font-bold text-gray-900">My Cropping Schedules</h2>
            @if ($latestSchedules->isNotEmpty())
                <a href="{{ route('sm.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">View all</a>
            @endif
        </div>

        @if ($latestSchedules->isEmpty())
            {{-- Empty state --}}
            <div class="card">
                <div class="card-body text-center py-10 md:py-14">
                    <svg class="w-24 h-24 mx-auto mb-4 text-brand-300" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3 2-5.5 5.5-5.5.2 2.8-1.6 5.5-5.5 5.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3-2-5.5-5.5-5.5C6.3 10.3 8.1 13 12 13z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16"/>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900">Plant your first schedule</h3>
                    <p class="text-sm text-gray-500 mt-1 max-w-xs mx-auto">
                        Create a cropping schedule to plan your lots, workers, activities and irrigation for the season.
                    </p>
                    <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg mt-5">
                        + New Cropping Schedule
                    </a>
                </div>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($latestSchedules as $schedule)
                    @php [$sBadge, $sLabel] = $scheduleBadge($schedule->status); @endphp
                    <div class="card card-hover">
                        <div class="card-body !p-4 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-gray-900 leading-snug min-w-0">{{ $schedule->title }}</h3>
                                <span class="badge {{ $sBadge }} shrink-0">{{ $sLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 mt-auto">
                                <p class="text-xs text-gray-500">Created {{ $schedule->created_at?->format('M j, Y') }}</p>
                                <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}" class="btn btn-outline btn-sm">Open</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg w-full mt-4">
                + New Cropping Schedule
            </a>
        @endif
    </div>
</div>
@endsection
