@extends('layouts.app')

@section('title', 'Technician\'s Blog · Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('help-key', 'community-blog')
@section('page-subtitle', 'Technician\'s Blog')

@push('head')
@include('community.partials.plaza-css')
{{-- The article band itself, so Home draws the same one. --}}
@include('community.blog.partials.card-css')
<style>
    /* --- The name plate. A section's head, not a notice in a box: the title
       carries its own weight, the line under it says what the section is for,
       and the run of numbers sits quietly below. Edge to edge on a phone,
       like the wall's posts and a discussion's head. --- */
    .blog-hero { position:relative; margin-bottom:1rem; border-radius:1.1rem; overflow:hidden;
        border:1px solid var(--color-gray-200); background:var(--color-white);
        box-shadow:var(--shadow-card); }
    .blog-hero-in { display:flex; align-items:center; gap:.9rem; padding:1.15rem 1.15rem .85rem; }
    .blog-hero-mark { flex:none; width:3.1rem; height:3.1rem; border-radius:1rem; font-size:1.5rem;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg, var(--color-brand-50), var(--color-brand-100));
        border:1px solid var(--color-brand-100); }
    .blog-hero h1 { font-family:var(--font-heading); font-size:1.3rem; font-weight:800; line-height:1.2;
        color:var(--color-gray-900); }
    .blog-hero h1 + p { margin-top:.2rem; font-size:.83rem; line-height:1.4; color:var(--color-gray-500); }
    .blog-hero-facts { display:flex; flex-wrap:wrap; gap:.15rem .9rem; padding:0 1.15rem 1rem;
        font-size:.72rem; font-weight:700; color:var(--color-gray-400); }
    .blog-hero-facts b { color:var(--color-gray-600); font-weight:800; }
    /* The plate is the first band of the run, so it is edge to edge like the
       rest of them rather than a rounded card sitting on top of a column. */
    .blog-hero { border-radius:0; border-left:0; border-right:0;
        margin-left:calc(var(--plaza-gutter, 1rem) * -1);
        margin-right:calc(var(--plaza-gutter, 1rem) * -1); }
    /* And a card on a desk, like the articles under it. */
    @media (min-width:1024px) {
        .blog-hero { margin-left:0; margin-right:0; border-radius:1rem; border:1px solid var(--color-gray-200); overflow:hidden; }
        html.dark .blog-hero { border-color:#2b3a1c; }
    }
    .blog-bar { display:flex; align-items:center; gap:.5rem; margin-bottom:.85rem; }
    .bb-act { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0; }
    .bb-hint { margin-left:auto; font-size:.72rem; font-weight:600; color:var(--color-gray-400); }
    @media (max-width:479px) { .bb-hint { display:none; } }
    /* The button keeps its word at every width — see the wall and the room. */
    .bb-filter { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0;
        max-width:11rem; padding:.25rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:800;
        background:var(--color-brand-50); color:var(--color-brand-700);
        border:1px solid var(--color-brand-200); }
    .bb-filter b { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bb-filter.hidden { display:none; }
    html.dark .bb-filter { background:rgb(61 104 35 / .25); border-color:#3f5626; color:#bfe19a; }

    /* --- The categories (2026-10-23): the public blog's own, one chip each,
       with how many articles wear it. A row that slides under the thumb on a
       phone, edge to edge like the bands; wrapped lines on a desk. --- */
    .blog-cats { display:flex; gap:.4rem; overflow-x:auto; scrollbar-width:none; -webkit-overflow-scrolling:touch;
        margin:0 calc(var(--plaza-gutter, 1rem) * -1) .85rem; padding:.1rem var(--plaza-gutter, 1rem) .3rem;
        scroll-padding-inline:var(--plaza-gutter, 1rem);
        -webkit-mask-image:linear-gradient(90deg, transparent 0, #000 .75rem, #000 calc(100% - 1.5rem), transparent 100%);
        mask-image:linear-gradient(90deg, transparent 0, #000 .75rem, #000 calc(100% - 1.5rem), transparent 100%); }
    .blog-cats::-webkit-scrollbar { display:none; }
    @media (min-width:1024px) {
        .blog-cats { flex-wrap:wrap; overflow:visible; margin-left:0; margin-right:0; padding-left:0; padding-right:0;
            -webkit-mask-image:none; mask-image:none; }
    }
    .blog-cat { flex:none; display:inline-flex; align-items:center; gap:.4rem; min-height:2.3rem; padding:.35rem .5rem .35rem .85rem;
        border-radius:999px; font-size:.78rem; font-weight:800; line-height:1; white-space:nowrap; cursor:pointer;
        color:var(--color-gray-700); background:var(--color-white); border:1px solid var(--color-gray-200);
        transition:background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .blog-cat small { min-width:1.45rem; padding:.22rem .42rem; border-radius:999px; text-align:center; font-size:.68rem; font-weight:800;
        color:var(--color-gray-500); background:var(--color-gray-100);
        transition:background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .blog-cat:hover { border-color:var(--color-brand-300); color:var(--color-brand-700); }
    .blog-cat:active { transform:scale(.96); }
    .blog-cat.is-on { color:#fff; background:var(--color-brand-600); border-color:var(--color-brand-600);
        box-shadow:0 8px 18px -10px rgb(61 104 35 / .8); }
    .blog-cat.is-on small { color:#fff; background:rgb(255 255 255 / .22); }
    html.dark .blog-cat { border-color:#2b3a1c; }
    html.dark .blog-cat.is-on { color:#fff; background:#4a7c2a; border-color:#4a7c2a; }

    /* A new set of articles rises into place instead of snapping in. */
    .blog-grid { transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .blog-grid.is-loading { opacity:.45; }
    .blog-grid.is-fresh > .blog-card { animation:blogRise .42s cubic-bezier(.22,1,.36,1) both; }
    .blog-grid.is-fresh > .blog-card:nth-child(2) { animation-delay:.05s; }
    .blog-grid.is-fresh > .blog-card:nth-child(3) { animation-delay:.1s; }
    .blog-grid.is-fresh > .blog-card:nth-child(4) { animation-delay:.15s; }
    .blog-grid.is-fresh > .blog-card:nth-child(n+5) { animation-delay:.2s; }
    @keyframes blogRise { from { opacity:0; transform:translateY(10px); } }
    @media (prefers-reduced-motion: reduce) {
        .blog-cat, .blog-cat small, .blog-grid { transition:none; }
        .blog-grid.is-fresh > .blog-card { animation:none; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const grid = document.getElementById('blogGrid');
    const findEl = document.getElementById('blogFind');
    if (!grid || !findEl) return;
    const note = document.getElementById('blogFindNote');
    const none = document.getElementById('blogNone');
    const pager = document.getElementById('blogPager');
    const chip = document.getElementById('blogFilterChip');
    const cats = document.getElementById('blogCats');
    const hint = document.getElementById('blogBarHint');
    const URL_BASE = @json(route('community.blog'));
    let query = findEl.value.trim();
    let cat = cats?.querySelector('.blog-cat.is-on')?.dataset.cat || '';
    let asked = 0;

    function say(count, total) {
        if (note) {
            if (!query) { note.hidden = true; note.textContent = ''; }
            else {
                note.hidden = false;
                note.innerHTML = count
                    ? total + ' ' + (total === 1 ? 'article' : 'articles') + ' matching <b></b>.'
                    : 'Walang tugma sa <b></b>.';
                // Typed words go in as text, never as markup.
                note.querySelector('b').textContent = '\u201c' + query + '\u201d';
            }
        }
        if (chip) {
            chip.classList.toggle('hidden', !query);
            if (query) chip.querySelector('b').textContent = '\u201c' + query + '\u201d';
        }
    }

    // The words and the category travel together: a search inside Fertilizer stays in Fertilizer.
    async function load() {
        const mine = ++asked;
        try {
            const url = new window.URL(window.location.href);
            if (query) url.searchParams.set('q', query); else url.searchParams.delete('q');
            if (cat) url.searchParams.set('cat', cat); else url.searchParams.delete('cat');
            url.searchParams.delete('page');
            history.replaceState(null, '', url);
        } catch (_) { /* an address bar that will not be written is not a failure */ }
        grid.classList.add('is-loading');
        try {
            const res = await fetch(URL_BASE + '?rows=1' + (query ? '&q=' + encodeURIComponent(query) : '') + (cat ? '&cat=' + encodeURIComponent(cat) : ''), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const d = (await res.json()).data || {};
            if (mine !== asked) return;   // a newer tap already asked
            grid.classList.remove('is-fresh');
            grid.innerHTML = d.html || '';
            void grid.offsetWidth;        // so the rise starts over
            grid.classList.add('is-fresh');
            window.blogCovers?.();   // the fresh cards bring fresh sliders
            const count = grid.children.length;
            if (none) none.hidden = count > 0;
            // The pages of this answer, not of the one before it.
            if (pager) { pager.innerHTML = d.pager || ''; pager.hidden = !d.pager; }
            if (hint) hint.textContent = (d.total || 0) + ' ' + ((d.total || 0) === 1 ? 'article' : 'articles');
            say(count, d.total || 0);
        } catch (_) {
            window.toast?.('Could not load the articles just now.', 'error');
        } finally {
            if (mine === asked) grid.classList.remove('is-loading');
        }
    }
    function search(q) { query = q; load(); }

    cats?.addEventListener('click', (e) => {
        const b = e.target.closest('.blog-cat');
        if (!b || (b.dataset.cat || '') === cat) return;
        cat = b.dataset.cat || '';
        cats.querySelectorAll('.blog-cat').forEach((x) => {
            const on = x === b;
            x.classList.toggle('is-on', on);
            x.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        b.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        load();
    });

    window.plazaLiveSearch?.(findEl, search);
    document.getElementById('blogSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('blogSearchSheet');
        // No `always`: the phone keypad should wait for a tap on the field.
        window.smFocus?.(findEl, { delay: 140 });
    });
    chip?.addEventListener('click', () => {
        findEl.value = '';
        findEl.dispatchEvent(new Event('input', { bubbles: true }));
    });
    if (query) say(grid.children.length, grid.children.length);
})();

/* The covers drift and can be pushed.
 *
 * Each card with more than one cover crossfades through them on its own
 * randomly-set clock — the wall of cards never flips in unison — and a
 * horizontal drag slides to the next or previous one with the direction
 * the thumb gave it. A drag is not a tap: the card is a link, and the
 * click that ends a swipe is swallowed so the reader stays on the list. */
(function () {
    function bind(box) {
        if (box.__covers) return;
        box.__covers = true;
        const imgs = [...box.querySelectorAll('.bc-img')];
        if (imgs.length < 2) return;
        let idx = 0, timer = null, swiped = false;
        function show(next, dir) {
            if (next === idx) return;
            const cur = imgs[idx], nxt = imgs[next];
            imgs.forEach((im) => im.classList.remove('bc-out-l', 'bc-out-r', 'bc-prep-l', 'bc-prep-r'));
            if (dir) {
                nxt.classList.add(dir === 'l' ? 'bc-prep-r' : 'bc-prep-l');
                void nxt.offsetWidth;              // the start position must be seen
                cur.classList.add(dir === 'l' ? 'bc-out-l' : 'bc-out-r');
            }
            cur.classList.remove('is-on');
            nxt.classList.remove('bc-prep-l', 'bc-prep-r');
            nxt.classList.add('is-on');
            idx = next;
        }
        function arm() {
            clearTimeout(timer);
            timer = setTimeout(() => { show((idx + 1) % imgs.length); arm(); }, 4200 + Math.random() * 3600);
        }
        arm();
        let x0 = null, y0 = null;
        box.addEventListener('pointerdown', (e) => { x0 = e.clientX; y0 = e.clientY; });
        box.addEventListener('pointerup', (e) => {
            if (x0 === null) return;
            const dx = e.clientX - x0, dy = e.clientY - y0;
            x0 = null;
            if (Math.abs(dx) > 36 && Math.abs(dx) > Math.abs(dy) * 1.4) {
                swiped = true;
                show(dx < 0 ? (idx + 1) % imgs.length : (idx - 1 + imgs.length) % imgs.length, dx < 0 ? 'l' : 'r');
                arm();
            }
        });
        box.closest('a')?.addEventListener('click', (e) => {
            if (swiped) { e.preventDefault(); swiped = false; }
        });
    }
    window.blogCovers = () => document.querySelectorAll('.blog-cover[data-covers]').forEach(bind);
    window.blogCovers();
})();
</script>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'blog'])

{{-- Two columns on a wide screen: the page, and its rail (the wall, your chats, the rooms). --}}
<div class="plaza-shell">
<div class="plaza-center">
@include('partials.ad-slot', ['placement' => 'community'])

<div class="blog-hero plaza-accent">
    <div class="blog-hero-in">
        <div class="blog-hero-mark">📰</div>
        <div class="min-w-0">
            <h1>Technician's Blog</h1>
            <p>Guides, farm news and advice for your farm.</p>
        </div>
    </div>
    @if ($posts->total())
        <p class="blog-hero-facts">
            <span><b>{{ number_format($posts->total()) }}</b> {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}</span>
            @if ($latest = $posts->first())
                @if ($latest->publishedAt)<span>Latest {{ $latest->publishedAt->diffForHumans() }}</span>@endif
            @endif
        </p>
    @endif
</div>

{{-- Looking for one particular article is an errand, so it opens the way
     every other errand in the community does — from the bottom, over what
     you were reading. --}}
<div class="blog-bar">
    <button type="button" id="blogSearchBtn" class="btn btn-outline btn-sm bb-act" title="Search the blog" aria-label="Search the blog">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <span class="bb-act-lbl">Search</span>
    </button>
    <button type="button" class="bb-filter hidden" id="blogFilterChip" title="Clear the search">
        <b></b>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <span class="bb-hint" id="blogBarHint">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}</span>
</div>

@if (($cats ?? collect())->count() > 1)
    {{-- The public blog's categories, newest first; a tap narrows the list in place. --}}
    <div class="blog-cats" id="blogCats" role="group" aria-label="Show only">
        <button type="button" class="blog-cat {{ ($cat ?? '') === '' ? 'is-on' : '' }}" data-cat="" aria-pressed="{{ ($cat ?? '') === '' ? 'true' : 'false' }}">All<small>{{ number_format($all ?? $posts->total()) }}</small></button>
        @foreach ($cats as $c)
            <button type="button" class="blog-cat {{ ($cat ?? '') === $c['name'] ? 'is-on' : '' }}" data-cat="{{ $c['name'] }}" aria-pressed="{{ ($cat ?? '') === $c['name'] ? 'true' : 'false' }}">{{ $c['name'] }}<small>{{ number_format($c['n']) }}</small></button>
        @endforeach
    </div>
@endif

<div class="sheet hidden" id="blogSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search the blog</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        @include('community.partials.live-search', [
            'id' => 'blogFind',
            'value' => $q ?? '',
            'placeholder' => 'Search articles…',
            'label' => 'Search the blog by title, words or author',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show articles</button>
    </div>
</div>

@if ($posts->isEmpty() && ($q ?? '') === '')
    <div class="card p-8 text-center">
        <div class="empty-tile">📰</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No articles yet</p>
        <p class="text-sm text-gray-500 mt-1">The team hasn't published anything yet. Check back soon.</p>
    </div>
@else
    <div class="blog-grid" id="blogGrid">
        @include('community.blog.partials.cards', ['posts' => $posts])
    </div>

    <div class="card p-8 text-center" id="blogNone" hidden>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">No article matches that title, text or author.</p>
    </div>

    <div class="mt-6" id="blogPager" @if (! $posts->hasPages()) hidden @endif>{{ $posts->links('community.partials.blog-pagination') }}</div>
@endif
</div>{{-- /plaza-center --}}
<aside class="plaza-side plaza-side-right">
    @include('community.partials.plaza-rail', ['rail' => ['posts', 'chats', 'discussions']])
</aside>
</div>{{-- /plaza-shell --}}
@endsection
