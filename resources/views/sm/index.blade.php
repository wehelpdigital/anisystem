@extends('layouts.app')

@section('title', 'Cropping Schedules')
@section('page-title', 'Cropping Schedules')
@section('page-subtitle', 'Plan and manage your seasons')

@php
    $statusBadges = [
        'draft' => 'bg-gray-100 text-gray-600',
        'setup' => 'bg-blue-100 text-blue-700',
        'generated' => 'bg-indigo-100 text-indigo-700',
        'completed' => 'bg-brand-100 text-brand-800',
        'archived' => 'bg-gray-800 text-white',
    ];
@endphp

@section('content')

    {{-- Top bar: search + desktop CTA --}}
    <div class="flex flex-col md:flex-row md:items-center gap-3 mb-4 md:mb-6">
        {{-- Search runs as you type (see the script below); the button-less form
             still submits on Enter as a no-JS fallback. --}}
        <form method="GET" action="{{ route('sm.index') }}" role="search" id="scheduleSearchForm" class="flex-1">
            <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="text" name="search" id="scheduleSearch" value="{{ request('search') }}" class="form-input pl-11! pr-16! w-full"
                    placeholder="Search schedules…" aria-label="Search schedules" autocomplete="off" enterkeyhint="search">
                <svg id="scheduleSearchSpin" class="hidden absolute right-9 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <button type="button" id="scheduleSearchClear" class="{{ request('search') ? '' : 'hidden' }} absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-gray-400 hover:bg-gray-100" aria-label="Clear search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </form>

        {{-- Desktop CTA. Wrapped so `hidden` reliably hides it on phones (a
             bare `.btn` is unlayered CSS and would otherwise beat `hidden`);
             the floating + button is the phone equivalent. --}}
        <div class="hidden md:flex shrink-0">
            <a href="{{ route('sm.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                New Cropping Schedule
            </a>
        </div>
    </div>

    {{-- Live-search swaps this block's contents (see script). --}}
    <div id="scheduleResults">
    @if ($schedules->isEmpty())
        {{-- Friendly empty state --}}
        <div class="card">
            <div class="card-body text-center py-14">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 8h6"/></svg>
                </div>
                @if (request()->filled('search'))
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No schedules match your search</h2>
                    <p class="text-sm text-gray-500 mb-5">Try a different search, or clear it to see all your schedules.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-outline">Clear search</a>
                @else
                    <h2 class="text-lg font-bold text-gray-900 mb-1">No cropping schedules yet</h2>
                    <p class="text-sm text-gray-500 mb-5">Create your first schedule to start planning lots, workers and day-by-day activities.</p>
                    <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        New Cropping Schedule
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger-children" id="schedulesGrid">
            @foreach ($schedules as $s)
                <div class="card card-hover flex flex-col" data-schedule-card="{{ $s->id }}">
                    <div class="card-body flex flex-col grow">
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <h2 class="font-bold text-gray-900 leading-snug min-w-0">{{ $s->title }}</h2>
                            <span class="badge shrink-0 capitalize {{ $statusBadges[$s->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $s->status }}</span>
                        </div>

                        @if ($s->description)
                            <p class="text-sm text-gray-500 mb-3">{{ \Illuminate\Support\Str::limit($s->description, 100) }}</p>
                        @endif

                        <div class="flex items-center gap-4 text-xs text-gray-500 font-medium mt-auto mb-3">
                            <span class="inline-flex items-center gap-1" title="Lots">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                                {{ $s->lots_count }} {{ \Illuminate\Support\Str::plural('lot', $s->lots_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Workers">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                                {{ $s->workers_count }} {{ \Illuminate\Support\Str::plural('worker', $s->workers_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="Activities">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"/></svg>
                                {{ $s->activities_count }}
                            </span>
                        </div>

                        <p class="text-xs text-gray-400 mb-3">Created {{ $s->created_at->format('M j, Y') }}</p>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('sm.hub', ['id' => $s->id]) }}" class="btn btn-primary flex-1">Open</a>
                            <button type="button"
                                class="btn btn-ghost px-3! text-red-500 hover:bg-red-50!"
                                data-delete-schedule="{{ $s->id }}" data-title="{{ $s->title }}"
                                aria-label="Delete schedule">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    @endif
    </div>{{-- /#scheduleResults --}}

    {{-- Mobile floating action button --}}
    <a href="{{ route('sm.create') }}"
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg flex items-center justify-center"
        aria-label="New cropping schedule">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
    </a>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ---- Live search: fetch as you type and swap the results in place.
    (() => {
        const form = document.getElementById('scheduleSearchForm');
        const input = document.getElementById('scheduleSearch');
        const clearBtn = document.getElementById('scheduleSearchClear');
        const spin = document.getElementById('scheduleSearchSpin');
        const results = document.getElementById('scheduleResults');
        if (!form || !input || !results) return;

        const BASE = @json(route('sm.index'));
        let token = 0;
        let debounce = null;

        async function runSearch(push = true) {
            const q = input.value.trim();
            clearBtn.classList.toggle('hidden', q === '');
            const url = BASE + (q ? ('?search=' + encodeURIComponent(q)) : '');
            const mine = ++token;
            spin.classList.remove('hidden');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const html = await res.text();
                if (mine !== token) return;                 // a newer keystroke won
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('scheduleResults');
                if (fresh) results.innerHTML = fresh.innerHTML;
                if (push) history.replaceState(null, '', url);
            } catch (_) {
                /* keep the current results on a transient failure */
            } finally {
                if (mine === token) spin.classList.add('hidden');
            }
        }

        input.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(runSearch, 250);
        });
        // Enter shouldn't full-reload; run the search immediately instead.
        form.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(debounce); runSearch(); });
        clearBtn.addEventListener('click', () => { input.value = ''; input.focus(); clearTimeout(debounce); runSearch(); });
    })();

    // Delete schedule (soft delete) -> remove card.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-delete-schedule]');
        if (!btn) return;

        const id = btn.getAttribute('data-delete-schedule');
        const title = btn.getAttribute('data-title') || 'this schedule';

        const ok = await confirmAction({
            title: 'Delete schedule?',
            message: `"${title}" and its modules will be hidden from your account.`,
            detail: 'Lots, workers and activities tied to it are preserved but no longer visible.',
            confirmText: 'Delete',
        });
        if (!ok) return;

        try {
            const res = await api(`{{ route('sm.destroy') }}?id=${id}`, { method: 'DELETE' });
            toast(res.message);
            document.querySelector(`[data-schedule-card="${id}"]`)?.remove();
            if (!document.querySelector('[data-schedule-card]')) window.location.reload();
        } catch (err) {
            toast(err.message, 'error');
        }
    });
});
</script>
@endpush
