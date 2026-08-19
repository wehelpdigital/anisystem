{{-- Every picture from every season.

     This is the Gallery module asked across all of them rather than one, so
     it wears the module's chrome exactly: the same shelf picker, the same
     shelves, the same tiles. A grower who has learned where photos live
     inside a season should not have to learn a second place.

     What it does not do is make things — an album belongs to a season, so
     albums are made in the season's own Gallery and only read here.

     Expects: $tab, $items, $albums, $team, $counts, $hasMore, $total, $q. --}}
@extends('layouts.app')

@section('title', 'Global Gallery')
@section('page-title', 'Global Gallery')
@section('page-subtitle', 'Every season, one shelf')
@section('back', route('sm.index'))

@push('head')
@include('sm.partials.gallery-chrome-css')
<style>
    /* The season a picture came from is the one thing a global shelf has to
       say that a season's own shelf never does. */
    .gh-season { display: block; font-size: .62rem; font-weight: 700; color: var(--color-brand-700);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .gh-season { color: #a5c97e; }
    .gh-search-form { display: flex; gap: .5rem; align-items: center; margin-bottom: .7rem; flex-wrap: wrap; }
    .gh-albums { display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: .7rem; }
    .gh-album { display: flex; flex-direction: column; border-radius: .8rem; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); text-align: left; width: 100%;
        cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .gh-album:hover { transform: translateY(-2px); border-color: #a8cc7e; }
    .gh-album-shot { position: relative; aspect-ratio: 4/3; background: #0b1220; }
    .gh-album-shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gh-album-n { position: absolute; right: .35rem; bottom: .35rem; padding: .1rem .45rem; border-radius: 999px;
        background: rgb(17 24 39 / .72); color: #fff; font-size: .62rem; font-weight: 800; }
    .gh-album-info { padding: .45rem .55rem .55rem; }
    .gh-album-info b { display: block; font-size: .78rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .gh-album { background: #151b12; border-color: #2b3a1c; }
    html.dark .gh-album-info b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .gh-album { transition: none; } .gh-album:hover { transform: none; } }
    /* Opening an album: its pictures, in place, with a way back. */
    .gh-open-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .7rem; }
    .gh-open-head h2 { font-family: var(--font-heading); font-size: .95rem; font-weight: 800;
        color: var(--color-gray-900); line-height: 1.2; }
    html.dark .gh-open-head h2 { color: #e8efe1; }
    .gh-open-head p { font-size: .72rem; color: var(--color-gray-500); }
    .gh-tail { text-align: center; margin-top: 1rem; }
    .gh-spin { display: inline-flex; gap: .25rem; }
    .gh-spin i { width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-brand-400);
        animation: ghDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .gh-spin i:nth-child(2) { animation-delay: .15s; }
    .gh-spin i:nth-child(3) { animation-delay: .3s; }
    @keyframes ghDot { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }
    .gh-spin[hidden], .gh-end[hidden], .gh-tail[hidden] { display: none; }
    .gh-end { font-size: .82rem; font-weight: 600; color: var(--color-gray-400); }
    @media (prefers-reduced-motion: reduce) {
        /* Slowed, not stopped: a still loader reads as a stuck page. */
        .gh-spin i { animation-duration: 2.4s; }
    }
</style>
@endpush

@section('content')
@php
    $shelves = [
        ['all', 'All Media', $counts['all'], 'Every picture your seasons have kept, newest first.'],
        ['albums', 'Albums', $counts['albums'], 'The ones you put together on purpose, from every season.'],
        ['videos', 'Videos', $counts['videos'], 'Clips on their own, because you pick a video and scan photos.'],
        ['team', 'Team box', $counts['team'], 'What the Collab Rooms made: recordings, whiteboards, saved maps.'],
    ];
    $now = collect($shelves)->firstWhere(0, $tab) ?: $shelves[0];
@endphp

{{-- The shelf picker, the same button the season's Gallery uses. --}}
<div class="ga-shelfbar">
    <button type="button" id="gaTabBtn" class="btn btn-white btn-sm" aria-haspopup="dialog" title="Which shelf?">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span id="gaTabNow">{{ $now[1] }}</span>
        <span class="ga-n">{{ $now[2] ?? '' }}</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
</div>

{{-- The shelves, asked for once. Links rather than panes: each shelf is a
     different question of the database, and building all four to show one is
     how a page with forty seasons behind it stops opening. --}}
<div class="ga-modal hidden" id="gaTabModal" role="dialog" aria-modal="true" aria-label="Which shelf?">
    <div class="ga-modal-back" data-ga-close></div>
    <div class="ga-modal-card">
        <div class="ga-modal-head">
            <p class="font-bold text-gray-900">Which shelf?</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-ga-close aria-label="Close">✕</button>
        </div>
        <div class="ga-modal-body" role="tablist">
            @foreach ($shelves as [$key, $label, $n, $why])
                <a href="{{ route('gallery.hub') }}?tab={{ $key }}{{ $q !== '' ? '&q=' . urlencode($q) : '' }}"
                   class="ga-opt{{ $key === $tab ? ' is-on' : '' }}" role="tab"
                   aria-selected="{{ $key === $tab ? 'true' : 'false' }}">
                    <span class="ga-opt-txt">
                        <b>{{ $label }} @if ($n !== null)<span class="ga-n">{{ $n }}</span>@endif</b>
                        <i>{{ $why }}</i>
                    </span>
                    <span class="ga-opt-tick" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="ga-pane is-in">
    {{-- One search box for the whole shelf, whichever shelf it is. --}}
    <form method="GET" action="{{ route('gallery.hub') }}" class="gh-search-form" role="search">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label class="ga-search">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Search by what it was about…" autocomplete="off">
        </label>
        <button type="submit" class="btn btn-primary btn-sm shrink-0">Search</button>
        @if ($q !== '')
            <a href="{{ route('gallery.hub') }}?tab={{ $tab }}" class="btn btn-white btn-sm shrink-0">Clear</a>
        @endif
    </form>

    @if ($tab === 'albums')
        <p class="tb-say">
            Albums from every season. One is made inside the season it belongs to —
            here they are only read, so a picture never loses which season it came from.
        </p>
        @if (empty($albums))
            <p class="ga-none">{{ $q !== '' ? 'No album matches that.' : 'No albums yet. Make one inside a season’s Gallery — a corner of the field, a problem you are following, the pictures a buyer asked for.' }}</p>
        @else
            <div class="gh-albums" id="ghAlbums">
                @foreach ($albums as $a)
                    <button type="button" class="gh-album" data-album="{{ $a['id'] }}"
                            data-title="{{ $a['title'] }}" data-season="{{ $a['scheduleTitle'] }}"
                            data-pictures="{{ json_encode($a['pictures']) }}">
                        <span class="gh-album-shot">
                            @if ($a['cover'])
                                <img src="{{ $a['cover'] }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                            <span class="gh-album-n">{{ $a['count'] }}</span>
                        </span>
                        <span class="gh-album-info">
                            <b>{{ $a['title'] }}</b>
                            <span class="gh-season">{{ $a['scheduleTitle'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
            {{-- An album, opened in place. --}}
            <div id="ghOpen" hidden>
                <div class="gh-open-head">
                    <button type="button" class="btn btn-white btn-sm" id="ghBack">← All albums</button>
                    <span class="min-w-0">
                        <h2 id="ghOpenTitle">Album</h2>
                        <p id="ghOpenSeason"></p>
                    </span>
                </div>
                <div class="ga-all" id="ghOpenGrid"></div>
            </div>
        @endif
    @elseif ($tab === 'team')
        <p class="tb-say">What every Collab Room made — recordings, whiteboards and saved maps, newest first.</p>
        @if (empty($team))
            <p class="ga-none">{{ $q !== '' ? 'Nothing in the Team boxes matches that.' : 'Nothing yet. Recordings, whiteboards and saved maps from a Collab Room gather here.' }}</p>
        @else
            <div class="tb-grid">
                @foreach ($team as $row)
                    <a class="tb-card" href="{{ $row['href'] ?: $row['url'] }}" @unless ($row['href']) target="_blank" rel="noopener" @endunless>
                        <span class="tb-shot">
                            @if ($row['video'])
                                <video src="{{ $row['url'] }}" @if ($row['posterUrl']) poster="{{ $row['posterUrl'] }}" @endif muted playsinline preload="metadata"></video>
                            @else
                                <img src="{{ $row['url'] }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                            <span class="tb-kind">{{ $row['kind'] }}</span>
                        </span>
                        <span class="tb-info">
                            <b>{{ $row['title'] }}</b>
                            <span class="gh-season">{{ $row['scheduleTitle'] }}</span>
                            <i>{{ $row['when'] }}@if ($row['by']) · {{ $row['by'] }}@endif</i>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        @if (empty($items))
            <p class="ga-none">
                {{ $q !== ''
                    ? 'Nothing in any season matches that.'
                    : 'Nothing here yet. Photos you take, drawings you make and maps you save all gather here on their own.' }}
            </p>
        @else
            <div class="ga-all" id="ghGrid">
                @include('sm.partials.gallery-hub-tiles', ['items' => $items])
            </div>
            <div class="gh-tail" id="ghTail" @unless ($hasMore) hidden @endunless>
                <button type="button" id="ghMore" class="btn btn-white btn-sm" data-page="2"
                        data-q="{{ $q }}" data-tab="{{ $tab }}">Show more</button>
                <div class="gh-spin" id="ghSpin" role="status" aria-label="Loading more" hidden><i></i><i></i><i></i></div>
                <p class="gh-end" id="ghEnd" hidden>🌾 Iyan na ang lahat.</p>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
@include('community.partials.lightbox-js')
<script>
(function galleryHub() {
    const $ = (id) => document.getElementById(id);

    /* The shelf picker, behaving as it does in the season's Gallery: the
       button opens the sheet, the sheet closes on its backdrop, its ✕ or
       Escape. The difference is that a shelf here is a link — see the view. */
    const modal = $('gaTabModal');
    const openModal = (on) => {
        if (!modal) return;
        if (on) {
            modal.classList.remove('hidden');
            requestAnimationFrame(() => modal.classList.add('is-open'));
        } else {
            modal.classList.remove('is-open');
            setTimeout(() => modal.classList.add('hidden'), 280);
        }
    };
    $('gaTabBtn')?.addEventListener('click', () => openModal(true));
    modal?.addEventListener('click', (e) => { if (e.target.closest('[data-ga-close]')) openModal(false); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) openModal(false);
    });

    /* An album opens in place rather than in a new page: it is a handful of
       pictures, and going back to the shelf should not cost a page load. */
    const albums = $('ghAlbums');
    const open = $('ghOpen');
    if (albums && open) {
        const grid = $('ghOpenGrid');
        const esc = (s) => String(s == null ? '' : s)
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        albums.addEventListener('click', (e) => {
            const card = e.target.closest('.gh-album');
            if (!card) return;
            let pics = [];
            try { pics = JSON.parse(card.getAttribute('data-pictures') || '[]'); } catch (_) { pics = []; }
            $('ghOpenTitle').textContent = card.getAttribute('data-title') || 'Album';
            $('ghOpenSeason').textContent = card.getAttribute('data-season') || '';
            grid.innerHTML = pics.map((p) => `
                <div class="ga-wrap">
                    <a class="ga-item" href="${esc(p.url)}"${p.video ? ' target="_blank" rel="noopener"' : ' data-lightbox'}>
                        <span class="ga-shot">
                            ${p.video
                                ? `<video src="${esc(p.url)}" muted playsinline preload="metadata" onloadeddata="this.classList.add('is-loaded')"></video><span class="ga-play"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>`
                                : `<img src="${esc(p.url)}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.closest('.ga-shot')?.classList.add('is-gone')">`}
                        </span>
                        <span class="ga-info"><span class="ga-it">${esc(p.caption || 'Untitled')}</span></span>
                    </a>
                </div>`).join('');
            albums.hidden = true;
            open.hidden = false;
            open.classList.add('ga-pane', 'is-in');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        $('ghBack')?.addEventListener('click', () => {
            open.hidden = true;
            albums.hidden = false;
        });
    }

    /* Paged from the server, the way the wall and the discussions list are: a
       farm with ten seasons of photographs is not a page. One request at a
       time, a loader while it flies, a clean stop at the end. */
    const grid = $('ghGrid');
    const more = $('ghMore');
    const spin = $('ghSpin');
    const end = $('ghEnd');
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
            if (more.dataset.tab === 'videos') params.set('kinds', 'video');
            const r = await fetch(@json(route('gallery.hub')) + '?' + params.toString(), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const d = (await r.json()).data || {};
            (d.items || []).forEach((it) => {
                if (more.dataset.tab === 'all' && it.type === 'video') return;   // stills shelf
                const shot = it.posterUrl || (it.type === 'image' ? it.url : null);
                const wrap = document.createElement('div');
                wrap.className = 'ga-wrap';
                wrap.innerHTML = '<a class="ga-item" href="' + (it.url || '#') + '"'
                    + (it.type === 'image' ? ' data-lightbox' : ' target="_blank" rel="noopener"') + '>'
                    + '<span class="ga-shot">'
                    + (shot ? '<img src="' + shot + '" alt="" loading="lazy" onload="this.classList.add(\'is-loaded\')">' : '')
                    + '<span class="ga-kind is-' + (it.kind || 'image') + '">' + (it.kind || 'file') + '</span></span>'
                    + '<span class="ga-info"><span class="ga-it">' + (it.title || 'Untitled') + '</span>'
                    + '<span class="gh-season">' + (it.scheduleTitle || '') + '</span></span></a>';
                grid.appendChild(wrap);
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
