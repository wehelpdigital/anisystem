@extends('layouts.app')

@section('title', 'My Co-Farmers — Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('help-key', 'community-cofarmers')
@section('page-subtitle', 'Your co-farmers and their latest')

@push('head')
@include('community.partials.plaza-css')
<style>
    .cf-head { margin-bottom:.85rem; }
    .cf-head-title { font-family:var(--font-heading); font-size:1.05rem; font-weight:800; line-height:1.25;
        color:var(--color-gray-900); }
    .cf-head-sub { font-size:.78rem; line-height:1.4; color:var(--color-gray-500); margin-top:.2rem; }
    .cf-head-acts { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-top:.6rem; }
    .cf-filter { display:inline-flex; align-items:center; gap:.35rem; flex:0 0 auto;
        max-width:11rem; padding:.25rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:800;
        background:var(--color-brand-50); color:var(--color-brand-700);
        border:1px solid var(--color-brand-200); }
    .cf-filter b { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cf-filter.hidden { display:none; }
    html.dark .cf-filter { background:rgb(61 104 35 / .25); border-color:#3f5626; color:#bfe19a; }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'cofarmers'])

{{-- The same bar every list in the community carries: what the page is, and
     the one thing you can do about it. --}}
{{-- The same band the members page opens with — nothing above the people.
     The nav pill already says where you are; a heading block on top of it
     was the one thing keeping the two pages from reading as one design. --}}
<div class="cf-head cf-head-acts" style="margin-top:0">
    <button type="button" id="cfSearchBtn" class="btn btn-outline btn-sm" title="Search your co-farmers" aria-label="Search your co-farmers">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        Search
    </button>
    <button type="button" class="cf-filter hidden" id="cfFilterChip" title="Clear the search">
        <b></b>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
</div>

<div class="sheet hidden" id="cfSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search your co-farmers</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        @include('community.partials.live-search', [
            'id' => 'cfFind',
            'value' => $q ?? '',
            'placeholder' => 'Search co-farmers…',
            'label' => 'Search your co-farmers — name, place or what they do',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the co-farmers</button>
    </div>
</div>


@if ($friends->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">🤝</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Wala ka pang co-farmers</p>
        <p class="text-sm text-gray-500 mt-1 mb-4">Connect with members para makita mo dito ang mga balita nila.</p>
        <a href="{{ route('community.connect.members') }}" class="btn btn-primary">Find members</a>
    </div>
@else
    {{-- The members page's card, so a co-farmer looks the same wherever they
         are met: cover, face, what they last said and how it went. --}}
    <div id="cofarmersGrid">
        @include('community.connect.partials.members', ['members' => $friends])
    </div>

    <div class="card p-8 text-center" id="cfNone" hidden>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">Nobody here answers to that — by name, by place, or by what they do.</p>
    </div>
    @include('partials.list-pager', ['noun' => 'co-farmer', 'paginator' => $friends,
        'rowsUrl' => route('community.cofarmers') . '?rows=1'])
@endif

@include('community.partials.post-actions')
@include('community.partials.wall-comments-modal')
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.avatar-zoom')
@include('community.partials.mutual-js')
{{-- The ✕ beside Follow (and any Connect this page ever draws) speaks
     through the connection handlers — which this page never loaded. --}}
@include('community.connect.partials.connect-js')
<script>
(() => {
    const grid = document.getElementById('cofarmersGrid');
    const findEl = document.getElementById('cfFind');
    if (!grid || !findEl) return;
    const note = document.getElementById('cfFindNote');
    const none = document.getElementById('cfNone');
    const chip = document.getElementById('cfFilterChip');
    const pager = document.querySelector('[data-list-pager]') || document.getElementById('cofarmersTail');
    const BASE = @json(route('community.cofarmers'));
    let query = findEl.value.trim();

    function say(count) {
        if (note) {
            if (!query) { note.hidden = true; note.textContent = ''; }
            else {
                note.hidden = false;
                note.innerHTML = count
                    ? count + ' ' + (count === 1 ? 'co-farmer' : 'co-farmers') + ' matching <b></b>.'
                    : 'Walang tugma sa <b></b>.';
                note.querySelector('b').textContent = '\u201c' + query + '\u201d';
            }
        }
        if (chip) {
            chip.classList.toggle('hidden', !query);
            if (query) chip.querySelector('b').textContent = '\u201c' + query + '\u201d';
        }
    }

    async function search(q) {
        query = q;
        try {
            const url = new window.URL(window.location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            url.searchParams.delete('page');
            history.replaceState(null, '', url);
        } catch (_) { /* an address bar that will not be written is not a failure */ }
        try {
            // The page already answers with cards alone for its own scroller.
            const res = await fetch(BASE + '?rows=1' + (q ? '&q=' + encodeURIComponent(q) : ''), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            grid.innerHTML = await res.text();
            const count = grid.querySelectorAll('[data-member-card]').length;
            if (none) none.hidden = count > 0;
            // A filtered answer of one page has nothing to page through.
            if (pager) pager.hidden = !!q;
            say(count);
        } catch (_) {
            window.toast?.('Could not search just now.', 'error');
        }
    }

    window.plazaLiveSearch?.(findEl, search);
    document.getElementById('cfSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('cfSearchSheet');
        // No `always`: the phone keypad should wait for a tap on the field.
        window.smFocus?.(findEl, { delay: 140 });
    });
    chip?.addEventListener('click', () => {
        findEl.value = '';
        findEl.dispatchEvent(new Event('input', { bubbles: true }));
    });
    if (query) say(grid.querySelectorAll('[data-member-card]').length);
})();
</script>
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.clamp-js')
@endpush
