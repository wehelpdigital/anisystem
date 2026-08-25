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
            Albums from every season, each with what is in it. One is made inside
            the season it belongs to — here they are only read, so a picture
            never loses which season it came from.
        </p>
        @if (empty($albums))
            <p class="ga-none">{{ $q !== '' ? 'No album matches that.' : 'No albums yet. Make one inside a season\u2019s Gallery — a corner of the field, a problem you are following, the pictures a buyer asked for.' }}</p>
        @else
            @foreach ($albums as $a)
                {{-- The season Gallery's own album section: a heading, then the
                     pictures in it. No add/rename/delete — an album belongs to
                     its season, so the header links there instead. --}}
                <div class="ga-album">
                    <div class="ga-head">
                        <span class="min-w-0 grow">
                            <span class="ga-title block">{{ $a['title'] }}</span>
                            @if (filled($a['description']))
                                <span class="ga-desc block">{{ $a['description'] }}</span>
                            @endif
                            <span class="gh-season">{{ $a['scheduleTitle'] }}</span>
                            <span class="ga-count mt-1 inline-block">{{ $a['count'] }} {{ \Illuminate\Support\Str::plural('picture', $a['count']) }}</span>
                        </span>
                        <span class="ga-acts">
                            <a class="ga-act" href="{{ route('sm.gallery', ['id' => $a['scheduleId'], 'tab' => 'albums']) }}"
                               title="Open in {{ $a['scheduleTitle'] }}" aria-label="Open in its season">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </span>
                    </div>
                    @if ($a['count'])
                        <div class="ga-grid">
                            @foreach ($a['pictures'] as $im)
                                <div class="ga-cell" data-lb-type="{{ $im['video'] ? 'video' : 'image' }}"
                                     data-lb-url="{{ $im['url'] }}" data-lb-caption="{{ $im['caption'] }}"
                                     @if ($im['video'] && empty($im['posterUrl']) && ! empty($im['path'])) data-needs-frame="{{ $im['path'] }}" data-clip-url="{{ $im['url'] }}" data-frame-replace="video" @endif
                                     @if (filled($im['caption'])) title="{{ $im['caption'] }}" @endif>
                                    @if ($im['video'])
                                        @if (! empty($im['posterUrl']))
                                            {{-- The frame the registry already keeps: a picture,
                                                 not a film the phone must decode for one. --}}
                                            <img src="{{ $im['posterUrl'] }}" alt="{{ $im['caption'] ?: 'Clip in this album' }}" loading="lazy"
                                                 onload="this.classList.add('is-loaded')"
                                                 onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">
                                        @else
                                            <video src="{{ $im['url'] }}#t=0.1" preload="metadata" playsinline muted
                                                   aria-label="{{ $im['caption'] ?: 'Clip in this album' }}"
                                                   onloadeddata="this.classList.add('is-loaded')"
                                                   onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();"></video>
                                        @endif
                                        <span class="ga-vid" aria-hidden="true">▶</span>
                                    @else
                                        <img src="{{ $im['url'] }}" alt="{{ $im['caption'] }}" loading="lazy"
                                             onload="this.classList.add('is-loaded')"
                                             onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">
                                    @endif
                                    @if (filled($im['caption']))
                                        <span class="ga-cap"><b>{{ $im['caption'] }}</b></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="ga-empty">Nothing in here yet — add pictures in the season it belongs to.</p>
                    @endif
                </div>
            @endforeach
        @endif
    @elseif ($tab === 'team')
        <p class="tb-say">What every Collab Room made — recordings, whiteboards and saved maps, newest first.</p>
        @if (empty($team))
            <p class="ga-none">{{ $q !== '' ? 'Nothing in the Team boxes matches that.' : 'Nothing yet. Recordings, whiteboards and saved maps from a Collab Room gather here.' }}</p>
        @else
            <div class="tb-grid">
                @foreach ($team as $row)
                    {{-- The module's own team card. A recording is watched here,
                         so it is a plain card with a save button; a drawing or a
                         map is a way back to the thing itself, so it is a link
                         and carries no save button — an anchor inside an anchor
                         is invalid HTML and the parser tears the card in half. --}}
                    @php
                        $goes = filled($row['href']);
                        $clipSrc = $row['url'] . ($row['posterUrl'] ? '' : '#t=0.1');
                    @endphp
                    <{{ $goes ? 'a' : 'div' }} class="tb-card" @if ($goes) href="{{ $row['href'] }}" @endif>
                        <span class="tb-shot"
                              @if ($row['video'] && empty($row['posterUrl']) && ! empty($row['path'])) data-needs-frame="{{ $row['path'] }}" data-clip-url="{{ $row['url'] }}" data-frame-mode="poster" @endif>
                            <span class="tb-kind">{{ $row['kind'] }}</span>
                            @if ($row['video'])
                                <video src="{{ $clipSrc }}"
                                       @if ($row['posterUrl']) poster="{{ $row['posterUrl'] }}" @endif
                                       preload="metadata" playsinline controls></video>
                                @unless ($row['posterUrl'])<span class="tb-play"><span>▶</span></span>@endunless
                            @else
                                <img src="{{ $row['url'] }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                            @unless ($goes)
                                {{-- A recording is a thing people want off the app
                                     and onto a phone. Re-served through our own
                                     origin so the browser saves rather than opens. --}}
                                <a class="tb-save" title="Save to this device" aria-label="Save"
                                   href="{{ route('media.save') }}?u={{ urlencode($row['url']) }}&n={{ urlencode($row['title'] ?: 'recording') }}"
                                   download onclick="event.stopPropagation()">
                                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0l-3.5-3.5M12 14l3.5-3.5M5 19h14"/></svg>
                                </a>
                            @endunless
                        </span>
                        <span class="tb-body">
                            <span class="tb-title">{{ $row['title'] }}</span>
                            @if (filled($row['note']))
                                <span class="tb-note">{{ $row['note'] }}</span>
                            @endif
                            <span class="gh-season">{{ $row['scheduleTitle'] }}</span>
                            <span class="tb-meta">{{ collect([$row['by'], $row['when']])->filter()->implode(' · ') }}</span>
                        </span>
                    </{{ $goes ? 'a' : 'div' }}>
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
@include('sm.partials.clip-frames-js')
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
                const wants = (!shot && it.type === 'video' && it.path)
                    ? ' data-needs-frame="' + it.path.replace(/"/g, '&quot;') + '" data-clip-url="' + (it.url || '') + '"'
                    : '';
                wrap.innerHTML = '<a class="ga-item" href="' + (it.url || '#') + '"'
                    + (it.type === 'image' ? ' data-lightbox' : ' target="_blank" rel="noopener"') + '>'
                    + '<span class="ga-shot"' + wants + '>'
                    + (shot ? '<img src="' + shot + '" alt="" loading="lazy" onload="this.classList.add(\'is-loaded\')">' : '')
                    + '<span class="ga-kind is-' + (it.kind || 'image') + '">' + (it.kind || 'file') + '</span></span>'
                    + '<span class="ga-info"><span class="ga-it">' + (it.title || 'Untitled') + '</span>'
                    + '<span class="gh-season">' + (it.scheduleTitle || '') + '</span></span></a>';
                grid.appendChild(wrap);
            });
            more.dataset.page = String(d.nextPage || (parseInt(more.dataset.page, 10) + 1));
            // Any clip that arrived frameless asks for its frame now.
            window.smClipFrames?.();
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
