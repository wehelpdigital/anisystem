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
        ['Documentation', 'documentation', (int) $documentationCount,
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['Post-harvest', 'post-harvest', (int) $postHarvestCount,
            'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        // Pencil-over-page, not the document glyph: Documentation two rows up
        // wears that one, and two tiles with the same icon read as one.
        ['Notes', 'notes', (int) $notesCount,
            'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
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

            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                @include('sm.partials.community-switch', ['schedule' => $schedule])
                <div class="flex items-center gap-3 shrink-0">
                    @if ($schedule->isPublic)
                        <a href="{{ route('community.show', ['id' => $schedule->id]) }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View in Community →</a>
                    @endif
                    <button type="button" id="statusToggleBtn" data-locked="{{ $schedule->isLocked() ? 1 : 0 }}" class="btn btn-sm {{ $schedule->isLocked() ? 'btn-white' : 'btn-primary' }}">
                        {{ $schedule->isLocked() ? 'Reopen' : 'Mark completed' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Featured: Activities (2/3) + Quick Capture (1/3) as matched CTA tiles --}}
    <style>
        .cta-tile { cursor: pointer; text-decoration: none; transition: background-color .15s ease, color .15s ease, transform .1s ease; }
        .cta-tile:active { transform: scale(.99); }
        .cta-tile .cta-chip { color: #fff; transition: background-color .15s ease; }
        .cta-tile .cta-arrow { opacity: .5; }

        /* Activities — green */
        .act-cta { background-color: #dcecd2; color: #234a19; }
        .act-cta:hover { background-color: #c6e0b5; color: #1c3d14; }
        .act-cta .cta-chip { background-color: #4c8a39; }
        .act-cta:hover .cta-chip { background-color: #3d7129; }
        .act-cta .cta-sub { color: #3f6b2c; }

        /* Quick Capture — orange, darkens on hover */
        .qc-cta { background-color: #fbe6c8; color: #6f3806; }
        .qc-cta:hover { background-color: #f0b263; color: #5a2c02; }
        .qc-cta .cta-chip { background-color: #e0912e; }
        .qc-cta:hover .cta-chip { background-color: #b5680b; }
        .qc-cta .cta-sub { color: #834710; }
        .qc-cta:hover .cta-sub { color: #5a2c02; }
    </style>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        {{-- Activities (2/3) --}}
        <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}"
            class="cta-tile act-cta sm:col-span-2 rounded-2xl p-5 flex items-center gap-4">
            <span class="cta-chip w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <span class="min-w-0 grow">
                <span class="flex items-center gap-2 flex-wrap">
                    <span class="text-lg font-bold leading-tight">Activities</span>
                    <span class="badge badge-yellow">{{ $schedule->activities_count }}</span>
                </span>
                <span class="cta-sub block text-sm leading-snug mt-0.5">The heart of your schedule — the day-by-day timeline.</span>
            </span>
            <svg class="cta-arrow w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- Quick Capture (1/3) --}}
        <button type="button" id="quickCaptureBtn"
            class="cta-tile qc-cta rounded-2xl p-5 flex flex-col items-start justify-center gap-2 text-left">
            <span class="cta-chip w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <span class="text-lg font-bold leading-tight">Quick Capture</span>
            <span class="cta-sub text-sm leading-snug">Snap a photo → notes or AI</span>
        </button>
    </div>

    {{-- Collab Room, Share and Reports live as square tiles in the grid below. --}}
    @if (\App\Support\ScheduleTeam::hasTeam($schedule))
        @include('sm.partials.collab-enter-modal', ['schedule' => $schedule])
    @endif

    {{-- Module grid + the team/share/report actions, all as matched square tiles. --}}
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

        {{-- Collab Room — right after AI Technician --}}
        @if (\App\Support\ScheduleTeam::hasTeam($schedule))
            <a href="{{ route('sm.collab', ['id' => $schedule->id]) }}" data-collab-open class="card card-hover block">
                <div class="p-4 flex flex-col gap-3">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M5 4v11a2 2 0 002 2h10a2 2 0 002-2V4M8 9h8M8 12h5M12 17v4m-3 0h6"/></svg>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">Collab Room</span>
                </div>
            </a>
        @endif

        {{-- Share this schedule --}}
        <button type="button" id="shareScheduleBtn" class="card card-hover block w-full text-left">
            <div class="p-4 flex flex-col gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                <span class="font-bold text-gray-900 text-sm">Share</span>
            </div>
        </button>

        {{-- Reports --}}
        <a href="{{ route('sm.reports', ['id' => $schedule->id]) }}" class="card card-hover block">
            <div class="p-4 flex flex-col gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H12a1 1 0 01-1-1V3.055zM15 3.936A9.02 9.02 0 0120.064 9H15V3.936z"/></svg>
                </div>
                <span class="font-bold text-gray-900 text-sm">Reports</span>
            </div>
        </a>
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

    @include('sm.partials.share-sheet', ['schedule' => $schedule])
    @include('sm.partials.quick-capture', ['fixedScheduleId' => $schedule->id])
    @include('sm.partials.ai-float', ['schedule' => $schedule])
    {{-- Team chat + whiteboard now live in the Collab Room. --}}
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

    // Mark completed (lock) / reopen (unlock).
    document.getElementById('statusToggleBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const locked = btn.getAttribute('data-locked') === '1';
        if (!locked) {
            const ok = await confirmAction({
                title: 'Mark as completed?',
                message: 'The schedule will be locked (read-only) until you reopen it.',
                confirmText: 'Mark completed',
            });
            if (!ok) return;
        }
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.status')), { method: 'POST', body: { id: {{ $schedule->id }}, status: locked ? 'setup' : 'completed' } });
            toast(res.message);
            setTimeout(() => window.location.reload(), 500);
        } catch (err) { toast(err.message || 'Could not update.', 'error'); btn.disabled = false; }
    });
});
</script>
@endpush
