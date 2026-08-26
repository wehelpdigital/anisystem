@extends('layouts.app')

@section('title', 'Technician\'s Blog — Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('page-subtitle', 'Technician\'s Blog')

@push('head')
@include('community.partials.plaza-css')
<style>
    /* --- An article is a band, not a tile ---
       Three to a row on a desktop and one to a row on a phone meant the same
       page had two personalities, and a 16:9 cover across a full-width tile
       is half a screen of photograph before a word of the article. One column
       of bands, each with its own colour along the top and the bottom — the
       shape the discussions and the wall already read in — and from 640px up
       the cover steps aside to the left so the words start at the top. */
    .blog-grid { display:grid; grid-template-columns:1fr; gap:.85rem; }
    .blog-card { position:relative; display:flex; flex-direction:column; overflow:hidden;
        border-radius:0; border-left:0; border-right:0;
        border-top:1px solid var(--color-gray-100); border-bottom:1px solid var(--color-gray-100);
        margin-left:calc(var(--plaza-gutter, 1rem) * -1);
        margin-right:calc(var(--plaza-gutter, 1rem) * -1);
        background:var(--color-white); box-shadow:var(--shadow-card); text-decoration:none;
        --bl-a:#4a7c2a; --bl-b:#8fc267;
        transition:box-shadow .28s cubic-bezier(.22,1,.36,1); }
    /* Above the cover, which starts at the very edge of the band. */
    .blog-card::before, .blog-card::after { content:''; position:absolute; inset:0 0 auto 0; height:3px;
        z-index:3; pointer-events:none;
        background:linear-gradient(90deg, var(--bl-a), var(--bl-b) 55%, transparent); }
    .blog-card::after { inset:auto 0 0 0;
        background:linear-gradient(270deg, var(--bl-a), var(--bl-b) 55%, transparent); }
    /* Each article keeps its colour by id, so it is the same one every visit. */
    .bl-hue-1 { --bl-a:#1d4ed8; --bl-b:#7aa5f5; }
    .bl-hue-2 { --bl-a:#b45309; --bl-b:#ecc06a; }
    .bl-hue-3 { --bl-a:#0f766e; --bl-b:#6cc9bf; }
    .bl-hue-4 { --bl-a:#7c3aed; --bl-b:#b393f5; }
    .bl-hue-5 { --bl-a:#be185d; --bl-b:#f090b8; }
    /* A band that lifts on hover lifts the page with it; it deepens instead. */
    .blog-card:hover { box-shadow:0 10px 30px -12px rgb(0 0 0 / .25); }
    .blog-cover { position:relative; height:9.5rem; background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50)); overflow:hidden; }
    @media (min-width:640px) {
        .blog-card { flex-direction:row; align-items:stretch; }
        .blog-cover { flex:none; width:16rem; height:auto; min-height:8.5rem; }
        .blog-body { flex:1 1 auto; justify-content:center; padding:1rem 1.25rem; }
        .blog-title { font-size:1.05rem; }
    }
    /* Covers fade up out of a shimmer instead of popping in — the gallery's
       loading language. A 404 just leaves the quiet brand gradient. */
    .blog-cover img { width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity .28s ease; }
    .blog-cover img.is-loaded { opacity:1; }
    /* A story with several covers stacks them and shows one at a time. The
       page drifts through them on its own clock (crossfade); a thumb slides
       them left or right (the prep/out classes give the slide its
       direction). No dots and no arrows, on purpose. */
    .blog-cover img.bc-img { position:absolute; inset:0; opacity:0;
        transition:opacity .7s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1); }
    .blog-cover img.bc-img.is-on.is-loaded { opacity:1; }
    .blog-cover img.bc-prep-l { transform:translateX(-32%); transition:none; }
    .blog-cover img.bc-prep-r { transform:translateX(32%); transition:none; }
    .blog-cover img.bc-img.is-on { transform:translateX(0); }
    .blog-cover img.bc-out-l { transform:translateX(-32%); }
    .blog-cover img.bc-out-r { transform:translateX(32%); }
    .blog-cover[data-covers] { touch-action:pan-y; }
    .blog-more { font-size:.8rem; font-weight:800; color:var(--color-brand-700);
        transition:color .28s cubic-bezier(.22,1,.36,1); }
    .blog-card:hover .blog-more { text-decoration:underline; }
    html.dark .blog-more { color:var(--color-brand-300, #a3d284); }
    @media (prefers-reduced-motion: reduce) {
        .blog-cover .bc-img { transition:opacity .3s ease; transform:none !important; }
    }
    .blog-cover:has(img)::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:blogShimmer 1.15s linear infinite; }
    .blog-cover:has(img.is-loaded)::before, .blog-cover:not(:has(img))::before { display:none; }
    @keyframes blogShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    @media (prefers-reduced-motion: reduce) {
        .blog-card { transition:none; }
        .blog-cover:has(img)::before { animation:none; background:rgb(255 255 255 / .25); }
    }
    .blog-cover-fallback { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:2.4rem; }
    .blog-body { padding:.85rem 1rem 1rem; display:flex; flex-direction:column; gap:.35rem; }
    .blog-title { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .blog-excerpt { font-size:.83rem; color:var(--color-gray-500); line-height:1.4; }
    .blog-meta { margin-top:.4rem; font-size:.72rem; color:var(--color-gray-400); display:flex; gap:.6rem; flex-wrap:wrap; }

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
    const URL_BASE = @json(route('community.blog'));
    let query = findEl.value.trim();

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

    async function search(q) {
        query = q;
        try {
            const url = new window.URL(window.location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            url.searchParams.delete('page');
            history.replaceState(null, '', url);
        } catch (_) { /* an address bar that will not be written is not a failure */ }
        try {
            const res = await fetch(URL_BASE + '?rows=1' + (q ? '&q=' + encodeURIComponent(q) : ''), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const d = (await res.json()).data || {};
            grid.innerHTML = d.html || '';
            window.blogCovers?.();   // the fresh cards bring fresh sliders
            const count = grid.children.length;
            if (none) none.hidden = count > 0;
            // A filtered answer of one page has nothing to page through.
            if (pager) pager.hidden = !d.hasMore;
            say(count, d.total || 0);
        } catch (_) {
            window.toast?.('Could not search just now.', 'error');
        }
    }

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

<div class="blog-hero plaza-accent">
    <div class="blog-hero-in">
        <div class="blog-hero-mark">📰</div>
        <div class="min-w-0">
            <h1>Technician's Blog</h1>
            <p>Guides and advice from the AniSenso team.</p>
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
            'label' => 'Search the blog — title, words or author',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the articles</button>
    </div>
</div>

@if ($posts->isEmpty() && ($q ?? '') === '')
    <div class="card p-8 text-center">
        <div class="empty-tile">📰</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No articles yet</p>
        <p class="text-sm text-gray-500 mt-1">The team hasn't published anything yet — check back soon.</p>
    </div>
@else
    <div class="blog-grid" id="blogGrid">
        @include('community.blog.partials.cards', ['posts' => $posts])
    </div>

    <div class="card p-8 text-center" id="blogNone" hidden>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">No article says that — in its title, its words, or who wrote it.</p>
    </div>

    <div class="mt-6" id="blogPager">{{ $posts->links('community.partials.blog-pagination') }}</div>
@endif
@endsection
