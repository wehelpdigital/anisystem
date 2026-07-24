@extends('layouts.app')

@section('title', $schedule->title)
@section('page-title', $schedule->title)
@section('page-subtitle', 'Schedule modules')
@section('back', route('sm.index'))

@php
    $statusBadges = [
        'draft' => 'bg-gray-100 text-gray-600',
        'setup' => 'bg-blue-100 text-blue-700',
        'generated' => 'bg-indigo-100 text-indigo-700',
        'completed' => 'bg-brand-100 text-brand-800',
        'archived' => 'bg-gray-800 text-white',
    ];

    // Module launcher cards: [label, moduleKey, count|null, svg path].
    // Each tile opens the Activities single-page shell with that module already
    // loaded (?module=key), so the module shows with the hamburger nav rather
    // than as its own standalone page.
    $moduleCards = [
        ['Settings', 'settings', null,
            'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ['Lots', 'lots', (int) $schedule->lots_count,
            'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2'],
        ['Workers', 'workers', (int) $schedule->workers_count,
            'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z'],
        ['Materials', 'materials', (int) $schedule->materials_count,
            'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['Services', 'services', (int) $schedule->services_count,
            'M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4'],
        ['Documentation', 'documentation', (int) $documentationCount,
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['Irrigation', 'irrigations', (int) $schedule->irrigations_count,
            'M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11zm-2.5 12a2.5 2.5 0 002.5 2.5'],
        ['Post-harvest', 'post-harvest', (int) $postHarvestCount,
            'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        ['AI Technician', 'ai', null,
            'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5'],
    ];
@endphp

@section('content')

    {{-- Header card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-gray-900 leading-snug">{{ $schedule->title }}</h2>
                    @if ($schedule->cropType || $schedule->cropVariety)
                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            @if ($schedule->cropType)
                                <span class="badge badge-green"><i class="not-italic">🌱</i> {{ $schedule->cropType }}</span>
                            @endif
                            @if ($schedule->cropVariety)
                                <span class="badge badge-gray">{{ $schedule->cropVariety }}</span>
                            @endif
                            @if ($schedule->dayType)
                                <span class="badge badge-yellow">{{ $schedule->dayType }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($schedule->description)
                        <p class="text-sm text-gray-500 mt-1.5">{{ $schedule->description }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-2">Created {{ $schedule->created_at->format('M j, Y') }}</p>
                </div>
                <span class="badge shrink-0 capitalize {{ $statusBadges[$schedule->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $schedule->status }}</span>
            </div>
        </div>
    </div>

    {{-- Featured: Activities --}}
    <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}"
        class="card card-hover block border-l-4 border-l-accent-500! mb-4">
        <div class="card-body">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-accent-500/15 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7 text-accent-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="min-w-0 grow">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-lg font-bold text-gray-900">Activities</h3>
                        <span class="badge badge-yellow">{{ $schedule->activities_count }}</span>
                    </div>
                    <p class="text-sm text-gray-500">The heart of your schedule — the day-by-day timeline.</p>
                </div>
                <span class="btn btn-primary shrink-0 hidden sm:inline-flex">Open</span>
                <svg class="w-6 h-6 text-gray-400 sm:hidden shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>
    </a>

    {{-- Module grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 stagger-children">
        @foreach ($moduleCards as [$label, $moduleKey, $count, $iconPath])
            <a href="{{ route('sm.activities', ['id' => $schedule->id, 'module' => $moduleKey]) }}" class="card card-hover block">
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between">
                        <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>
                        </div>
                        @if ($count !== null)
                            <span class="badge {{ $count > 0 ? 'badge-green' : 'badge-gray' }}">{{ $count }}</span>
                        @endif
                    </div>
                    <span class="font-bold text-gray-900 text-sm">{{ $label }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Danger zone --}}
    <div class="card border-red-100">
        <div class="card-body">
            <h3 class="font-bold text-red-700 mb-1">Danger zone</h3>
            <p class="text-sm text-gray-500 mb-4">Deleting hides this schedule and all its modules from your account.</p>
            <button type="button" id="deleteScheduleBtn" class="btn btn-danger-outline">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                Delete schedule
            </button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('deleteScheduleBtn')?.addEventListener('click', async () => {
        const ok = await confirmAction({
            title: 'Delete schedule?',
            message: @json('"' . $schedule->title . '" and its modules will be hidden from your account.'),
            detail: 'Lots, workers and activities tied to it are preserved but no longer visible.',
            confirmText: 'Delete',
        });
        if (!ok) return;

        try {
            const res = await api(`{{ route('sm.destroy') }}?id={{ $schedule->id }}`, { method: 'DELETE' });
            toast(res.message);
            window.location.href = @json(route('sm.index'));
        } catch (err) {
            toast(err.message, 'error');
        }
    });
});
</script>
@endpush
