{{-- Every picture from every season.

     Global Notes gathers the words; this gathers what was photographed,
     drawn, filmed and saved as a map — because "that photo of the pest, some
     time last year" is a memory of a picture, not of a schedule.

     Expects: $items (one page), $hasMore, $total, $q. --}}
@extends('layouts.app')

@section('title', 'Global Gallery')
@section('page-title', 'Global Gallery')
@section('page-subtitle', 'Everything your seasons have kept')
@section('back', route('sm.index'))

@section('content')
<form method="GET" action="{{ route('gallery.hub') }}" class="gh-search mb-4" role="search">
    <div class="relative grow">
        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" name="q" value="{{ $q }}" class="form-input pl-11! w-full"
               placeholder="Search by name, season, or where it came from…" autocomplete="off">
    </div>
    <button type="submit" class="btn btn-primary btn-sm shrink-0">Search</button>
    @if ($q !== '')<a href="{{ route('gallery.hub') }}" class="btn btn-white btn-sm shrink-0">Clear</a>@endif
</form>

@if (empty($items))
    <div class="card p-8 text-center">
        <div class="empty-tile">🖼️</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">
            {{ $q !== '' ? 'Walang tugma' : 'Wala pang naka-save na larawan' }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
            {{ $q !== ''
                ? 'Nothing in any season matches that.'
                : 'Photos you take, drawings you make and maps you save all gather here.' }}
        </p>
    </div>
@else
    <p class="gh-count">{{ $total }} {{ \Illuminate\Support\Str::plural('item', $total) }} across your seasons</p>
    <div class="gh-grid" id="ghGrid">
        @include('sm.partials.gallery-hub-tiles', ['items' => $items])
    </div>
    <div class="gh-tail" id="ghTail" @unless ($hasMore) hidden @endunless>
        <button type="button" id="ghMore" class="btn btn-white btn-sm" data-page="2"
                data-q="{{ $q }}">Show more</button>
        <div class="gh-spin" id="ghSpin" role="status" aria-label="Loading more" hidden><i></i><i></i><i></i></div>
        <p class="gh-end" id="ghEnd" hidden>🌾 Iyan na ang lahat.</p>
    </div>
@endif
@endsection

@push('styles')
<style>
    .gh-search { display: flex; align-items: center; gap: .5rem; }
    .gh-count { font-size: .78rem; font-weight: 700; color: var(--color-gray-400); margin-bottom: .5rem; }
    .gh-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: .55rem; }
    .gh-tile { position: relative; display: block; border-radius: .7rem; overflow: hidden;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .gh-tile:hover { transform: translateY(-2px); border-color: var(--color-brand-300); }
    .gh-shot { position: relative; aspect-ratio: 1; background: var(--color-gray-100); }
    .gh-shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gh-blank { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; color: var(--color-gray-400); }
    .gh-badge { position: absolute; left: .3rem; top: .3rem; padding: .1rem .35rem; border-radius: 999px;
        background: rgb(17 24 39 / .72); color: #fff; font-size: .6rem; font-weight: 800; }
    .gh-meta { padding: .3rem .45rem .45rem; }
    .gh-name { display: block; font-size: .7rem; font-weight: 700; color: var(--color-gray-700);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gh-sub { display: block; font-size: .62rem; color: var(--color-gray-400);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gh-tail { text-align: center; margin-top: 1rem; }
    .gh-spin { display: inline-flex; gap: .25rem; }
    .gh-spin i { width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-brand-400);
        animation: ghDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .gh-spin i:nth-child(2) { animation-delay: .15s; }
    .gh-spin i:nth-child(3) { animation-delay: .3s; }
    @keyframes ghDot { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }
    .gh-spin[hidden], .gh-end[hidden], .gh-tail[hidden] { display: none; }
    .gh-end { font-size: .82rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .gh-tile { background: #1c2416; border-color: #2b3a1c; }
    html.dark .gh-shot { background: #151b12; }
    @media (prefers-reduced-motion: reduce) {
        .gh-tile { transition: none; }
        .gh-tile:hover { transform: none; }
        /* Slowed, not stopped: a still loader reads as a stuck page. */
        .gh-spin i { animation-duration: 2.4s; }
    }
</style>
@endpush

@push('scripts')
@include('community.partials.lightbox-js')
<script>
/* Paged from the server, the way the wall and the discussions list are: a farm
   with ten seasons of photographs is not a page. One request at a time, a
   loader while it flies, a clean stop at the end. */
(function galleryHub() {
    const grid = document.getElementById('ghGrid');
    const tail = document.getElementById('ghTail');
    const more = document.getElementById('ghMore');
    const spin = document.getElementById('ghSpin');
    const end = document.getElementById('ghEnd');
    if (!grid || !more) return;

    let busy = false, done = false, autoPull = true;

    async function load() {
        if (busy || done) return;
        busy = true;
        more.hidden = true;
        if (spin) spin.hidden = false;
        try {
            const params = new URLSearchParams({ json: '1', page: more.dataset.page || '2' });
            if (more.dataset.q) params.set('q', more.dataset.q);
            const r = await fetch(@json(route('gallery.hub')) + '?' + params.toString(), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const d = (await r.json()).data || {};
            (d.items || []).forEach((it) => {
                const shot = it.posterUrl || (it.type === 'image' ? it.url : null);
                const a = document.createElement('a');
                a.className = 'gh-tile';
                a.href = it.url || '#';
                if (it.type === 'image') a.setAttribute('data-lightbox', '');
                a.innerHTML = '<span class="gh-shot">'
                    + (shot ? '<img src="' + shot + '" alt="" loading="lazy">' : '<span class="gh-blank">🎬</span>')
                    + '<span class="gh-badge">' + (it.kind || 'file') + '</span></span>'
                    + '<span class="gh-meta"><span class="gh-name">' + (it.title || 'Untitled') + '</span>'
                    + '<span class="gh-sub">' + (it.scheduleTitle || '') + '</span></span>';
                grid.appendChild(a);
            });
            more.dataset.page = String(d.nextPage || (parseInt(more.dataset.page, 10) + 1));
            if (spin) spin.hidden = true;
            busy = false;
            if (!d.hasMore) { done = true; more.remove(); if (end) end.hidden = false; return; }
            more.hidden = false;
            setTimeout(near, 0);
        } catch (e) {
            busy = false;
            // Hand it back rather than letting the scroll hammer a dead network.
            autoPull = false;
            if (spin) spin.hidden = true;
            more.hidden = false;
            more.textContent = 'Try again';
        }
    }

    function near() {
        if (busy || done || !autoPull || more.hidden) return;
        if (more.getBoundingClientRect().top < window.innerHeight + 600) load();
    }
    let last = 0;
    const onScroll = () => {
        const now = Date.now();
        if (now - last < 100) return;
        last = now;
        near();
    };
    more.addEventListener('click', () => { autoPull = true; load(); });
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    near();
})();
</script>
@endpush
