@extends('layouts.app')

@section('title', 'Members')
@section('body-class', 'plaza-ground')
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
<div class="card p-3 mem-info" id="memInfo">
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

{{-- The same band every list in the community carries: a bordered Search
     button that opens a sheet, and a chip that says what is filtered. --}}
<div class="mem-band mb-4">
    <button type="button" id="memSearchBtn" class="btn btn-outline btn-sm" title="Search members" aria-label="Search members">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        Search
    </button>
    @if ($anyFilter)
        <a href="{{ route('community.connect.members') }}" class="mem-chip" title="Clear the search">
            <b>{{ $filters['q'] }}</b>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </a>
    @endif
    {{-- Brought back by the (i), which only exists while the panel is away.
         Green like its neighbour: the two buttons on this band are the same
         kind of thing. --}}
    <button type="button" class="btn btn-outline btn-sm shrink-0 hidden" id="memInfoOpen" aria-label="About this page" title="About this page">i</button>
</div>

<div class="sheet hidden" id="memSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search members</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <form method="GET" action="{{ route('community.connect.members') }}" role="search">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-input w-full mb-3"
                   placeholder="Name, place, crop, or what they do…" autocomplete="off">
            <button type="submit" class="btn btn-primary w-full">Show the members</button>
        </form>
    </div>
</div>

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
    .mem-band { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .mem-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem;
        border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-700);
        font-size: .74rem; font-weight: 700; }
    .mem-chip b { max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .mem-chip { background: rgb(61 104 35 / .3); color: #a3d284; }
    .mem-info-ico { display: inline-flex; align-items: center; justify-content: center; flex: none;
        width: 1.5rem; height: 1.5rem; border-radius: 999px; font-weight: 800; font-size: .8rem;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    /* The note folds open and shut rather than snapping: height, air and
       opacity all ease together on the house timing. */
    .mem-info { overflow: hidden; max-height: 14rem; margin-bottom: .75rem;
        transition: max-height .34s cubic-bezier(.22,1,.36,1), opacity .28s ease,
            transform .34s cubic-bezier(.22,1,.36,1), margin .34s cubic-bezier(.22,1,.36,1),
            padding .34s cubic-bezier(.22,1,.36,1); }
    .mem-info.is-away { max-height: 0; opacity: 0; transform: translateY(-.35rem);
        margin-bottom: 0; padding-top: 0; padding-bottom: 0; }
    @media (prefers-reduced-motion: reduce) { .mem-info { transition-duration: .01s; } }

    /* The member card's own look lives in the shared community stylesheet
       (plaza-css) — three pages draw that card, and only this one used to
       carry its rules. */

    /* One column at every width now: a member card is a band that runs the
       full width of the page, and two bands side by side are two half-bands
       — which is the tile this shape was chosen to stop being. */
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
   made the old paragraph feel like clutter. It folds rather than snaps —
   see .mem-info.is-away. */
(function memberInfo() {
    const KEY = 'anisystem.members.info.hidden';
    const panel = document.getElementById('memInfo');
    const openBtn = document.getElementById('memInfoOpen');
    const closeBtn = document.getElementById('memInfoClose');
    if (!panel || !openBtn) return;

    const show = (on) => {
        panel.classList.toggle('is-away', !on);
        openBtn.classList.toggle('hidden', on);
    };
    let hidden = false;
    try { hidden = localStorage.getItem(KEY) === '1'; } catch (_) {}
    if (hidden) {
        // Arrive already folded, without playing the fold.
        const t = panel.style.transition;
        panel.style.transition = 'none';
        show(false);
        void panel.offsetHeight;
        panel.style.transition = t;
    }

    closeBtn?.addEventListener('click', () => {
        show(false);
        try { localStorage.setItem(KEY, '1'); } catch (_) {}
    });
    openBtn.addEventListener('click', () => {
        show(true);
        try { localStorage.removeItem(KEY); } catch (_) {}
    });
})();

/* The search lives behind its bordered button, like every other list here. */
document.getElementById('memSearchBtn')?.addEventListener('click', () => {
    window.openSheet?.('memSearchSheet');
    // No auto-focus: the phone keypad waits for a tap on the field.
});

/* Older members, fetched as the reader nears the bottom. The button was
 * wired to nothing at all — infinite-js has been auto-clicking a dead
 * button since the day it shipped. This is its handler: fetch the next
 * page, append the cards, put the button away when the well is dry. */
(function membersMore() {
    const btn = document.getElementById('membersMore');
    const grid = document.getElementById('membersGrid');
    if (!btn || !grid) return;
    let page = 2, busy = false;
    btn.addEventListener('click', async () => {
        if (busy) return;
        busy = true;
        btn.disabled = true;
        btn.textContent = 'Loading…';
        try {
            const url = new URL(@json(route('community.connect.members')), window.location.origin);
            url.searchParams.set('page', String(page));
            const q = btn.getAttribute('data-q') || '';
            if (q) url.searchParams.set('q', q);
            const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            const d = (await res.json()).data || {};
            grid.insertAdjacentHTML('beforeend', d.html || '');
            page += 1;
            if (!d.hasMore) document.getElementById('membersTail')?.remove();
        } catch (_) {
            window.toast?.('Could not load more members.', 'error');
        } finally {
            busy = false;
            btn.disabled = false;
            btn.textContent = 'Load more';
        }
    });
})();
</script>
@endpush
