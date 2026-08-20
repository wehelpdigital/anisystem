@extends('layouts.app')

@section('title', 'Members')
@section('page-title', 'Members')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')
@include('community.partials.nav', ['active' => 'members'])

{{-- Who to meet first: friends of friends, farmers nearby, and the people
     whose threads you have already been talking in. --}}
{{-- The wall's band, so the same feature looks like the same feature. --}}
@include('community.partials.pymk')

@php $anyFilter = $filters['q'] !== ''; @endphp

{{-- The explanation is an info panel, not a paragraph wedged beside a button.
     Closed, it leaves an (i) that brings it back — the same bargain the
     activity modules strike with their own help text, and the reason it is
     remembered per browser: a farmer who has read it once has read it. --}}
<div class="card p-3 mb-3 mem-info" id="memInfo" hidden>
    <div class="flex items-start gap-2.5">
        <span class="mem-info-ico" aria-hidden="true">i</span>
        <p class="text-sm text-gray-600 leading-relaxed grow">
            These are members <strong>not yet in your contacts</strong>. Search by name, place, crop or what
            somebody does — one field looks at all of it.
        </p>
        <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 shrink-0"
                id="memInfoClose" aria-label="Hide this note">✕</button>
    </div>
</div>

<form method="GET" action="{{ route('community.connect.members') }}" class="mem-search mb-4" role="search" id="memberFilters">
    <div class="relative grow">
        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" name="q" value="{{ $filters['q'] }}" class="form-input pl-11! w-full"
               placeholder="Search members — name, place, crop…" autocomplete="off">
    </div>
    <button type="submit" class="btn btn-primary btn-sm shrink-0">Search</button>
    @if ($anyFilter)<a href="{{ route('community.connect.members') }}" class="btn btn-white btn-sm shrink-0">Clear</a>@endif
    {{-- Brought back by the (i), which only exists while the panel is away. --}}
    <button type="button" class="btn btn-white btn-sm shrink-0 hidden" id="memInfoOpen" aria-label="About this page" title="About this page">i</button>
</form>

@if ($members->isEmpty())
    <div class="card p-8 text-center text-sm text-gray-500">
        {{ $anyFilter ? 'No members match that search.' : "You're connected with everyone here — check back as more farmers join." }}
    </div>
@else
    <div id="membersGrid">
        @include('community.connect.partials.members', ['members' => $members])
    </div>
    @if ($hasMore)
        <div class="text-center mt-4" id="membersTail">
            <button type="button" id="membersMore" class="btn btn-white btn-sm"
                    data-q="{{ $filters['q'] }}" data-infinite>Load more</button>
        </div>
    @endif
@endif

@include('community.partials.post-actions')
@endsection

@push('styles')
<style>
    .mem-search { display: flex; align-items: center; gap: .5rem; }
    .mem-info-ico { display: inline-flex; align-items: center; justify-content: center; flex: none;
        width: 1.5rem; height: 1.5rem; border-radius: 999px; font-weight: 800; font-size: .8rem;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    .mem-info[hidden] { display: none; }

    /* The member card's own look lives in the shared community stylesheet
       (plaza-css) — three pages draw that card, and only this one used to
       carry its rules. */

    /* Two columns where there is room; one where a card is the screen. */
    @media (min-width: 768px) {
        #membersGrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; align-items: start; }
        #membersGrid .mc-card { margin-bottom: 0; }
    }
</style>
@endpush

@push('scripts')
{{-- The band fetches its own people; without this it stays a row of
     shimmering placeholders forever. --}}
@include('community.partials.pymk-js')
@include('community.connect.partials.connect-js')
@include('community.partials.infinite-js')
<script>
/* The info panel remembers being dismissed, per browser: somebody who has read
   what this page is has read it, and being told again on every visit is what
   made the old paragraph feel like clutter. */
(function memberInfo() {
    const KEY = 'anisystem.members.info.hidden';
    const panel = document.getElementById('memInfo');
    const openBtn = document.getElementById('memInfoOpen');
    const closeBtn = document.getElementById('memInfoClose');
    if (!panel || !openBtn) return;

    const show = (on) => {
        panel.hidden = !on;
        openBtn.classList.toggle('hidden', on);
    };
    let hidden = false;
    try { hidden = localStorage.getItem(KEY) === '1'; } catch (_) {}
    show(!hidden);

    closeBtn?.addEventListener('click', () => {
        show(false);
        try { localStorage.setItem(KEY, '1'); } catch (_) {}
    });
    openBtn.addEventListener('click', () => {
        show(true);
        try { localStorage.removeItem(KEY); } catch (_) {}
    });
})();
</script>
@endpush
