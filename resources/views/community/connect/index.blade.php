@extends('layouts.app')

@section('title', 'Members — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Find and connect with other members')
@section('back', route('community.index'))

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'members'])

@include('community.partials.recommended', ['recommendations' => $recommendations])

@php $anyFilter = $filters['q'] !== '' || $filters['province'] !== '' || $filters['crop'] !== ''; @endphp
<div class="flex items-start justify-between gap-3 mb-3">
    <p class="text-sm text-gray-500">Showing members <strong>not yet in your contacts</strong>. Filter to find co-farmers near you or growing the same crop.</p>
    <a href="{{ route('community.connect.requests') }}" class="btn btn-white btn-sm shrink-0 relative">
        Requests
        @if ($pendingCount > 0)
            <span class="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[0.625rem] font-bold inline-flex items-center justify-center">{{ $pendingCount }}</span>
        @endif
    </a>
</div>

{{-- Phones: search owns a row, the two selects share one — half the height
     the old single-column stack spent before the first member appeared. --}}
<form method="GET" action="{{ route('community.connect.members') }}" class="card p-3 mb-4 plaza-accent" role="search" id="memberFilters">
    <div class="grid grid-cols-2 gap-2 lg:grid-cols-[1fr_11rem_11rem_auto]">
        <div class="relative col-span-2 lg:col-span-1">
            <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-input pl-11! w-full" placeholder="Search by name or place…" autocomplete="off">
        </div>
        <select name="province" class="form-select" onchange="this.form.submit()" aria-label="Filter by province">
            <option value="">📍 All provinces</option>
            @foreach ($provinces as $p)<option value="{{ $p }}" @selected($filters['province'] === $p)>{{ $p }}</option>@endforeach
        </select>
        <select name="crop" class="form-select" onchange="this.form.submit()" aria-label="Filter by crop">
            <option value="">🌾 All crops</option>
            @foreach ($crops as $cr)<option value="{{ $cr }}" @selected($filters['crop'] === $cr)>{{ ucfirst($cr) }}</option>@endforeach
        </select>
        <div class="flex gap-2 col-span-2 lg:col-span-1">
            <button type="submit" class="btn btn-primary btn-sm grow">Filter</button>
            @if ($anyFilter)<a href="{{ route('community.connect.members') }}" class="btn btn-white btn-sm" title="Clear filters">Clear</a>@endif
        </div>
    </div>
</form>

@if ($members->isEmpty())
    <div class="card p-8 text-center text-sm text-gray-500">
        {{ $anyFilter ? 'No members match those filters.' : "You're connected with everyone here — check back as more farmers join." }}
    </div>
@else
    <div class="masonry-2" id="membersGrid">
        @include('community.connect.partials.members', ['members' => $members])
    </div>
    @if ($hasMore)
        <div class="text-center mt-3">
            <button type="button" id="loadMoreMembers" class="btn btn-white" data-next="2"
                    data-q="{{ $filters['q'] }}" data-province="{{ $filters['province'] }}" data-crop="{{ $filters['crop'] }}" data-infinite>Load more</button>
        </div>
    @endif
@endif
@endsection

@include('community.connect.partials.connect-js')

@push('scripts')
@include('community.partials.infinite-js')
<script>
document.getElementById('loadMoreMembers')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const page = btn.getAttribute('data-next');
    const q = btn.getAttribute('data-q') || '';
    btn.disabled = true; btn.textContent = 'Loading…';
    try {
        const province = btn.getAttribute('data-province') || '';
        const crop = btn.getAttribute('data-crop') || '';
        const res = await fetch(`{{ route('community.connect.members') }}?page=${page}&q=${encodeURIComponent(q)}&province=${encodeURIComponent(province)}&crop=${encodeURIComponent(crop)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        document.getElementById('membersGrid').insertAdjacentHTML('beforeend', data.data.html);
        if (data.data.hasMore) { btn.setAttribute('data-next', data.data.nextPage); btn.disabled = false; btn.textContent = 'Load more'; }
        else btn.remove();
    } catch (_) { btn.disabled = false; btn.textContent = 'Load more'; toast('Could not load more.', 'error'); }
});
</script>
@endpush
