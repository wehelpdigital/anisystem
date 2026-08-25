{{-- Everything this member kept.

     The same cards the wall draws, so a saved post behaves exactly as it did
     where it was found — you can still open its comments, share it on, or take
     the bookmark back off (which is how you remove one from here). Each card
     also carries a way back to the post's own page, because a kept post has
     been lifted out of the conversation it came from.

     Expects: $posts, $savedIds, $hasMore, $before, $savedTotal. --}}
@extends('layouts.app')

@section('title', 'Saved posts')
@section('body-class', 'plaza-ground')
@section('page-title', 'Saved posts')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')
@include('community.partials.nav', ['active' => 'wall'])

<div class="sv-wrap">
    <div class="sv-head">
        <h2 class="sv-title">Saved posts</h2>
        <p class="sv-sub">Only you can see these. Tap the bookmark on a post to take it off this list.</p>
    </div>

    {{-- The one errand there is here: finding the one you kept. Same bordered
         button and same sheet as the wall's, so it is learned once. --}}
    @if ($savedTotal > 0)
        <div class="wall-bar" id="savedBar">
            <button type="button" id="savedSearchBtn" class="btn btn-outline btn-sm wb-act" title="Search your saved posts">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <span class="wb-act-lbl">Search</span>
            </button>
            {{-- A filter is a thing that is ON, and it has to say so where it
                 can be seen once the sheet is shut. --}}
            <button type="button" class="wb-filter hidden" id="savedFilterChip" title="Clear the search">
                <b></b>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <span class="wb-hint" id="savedBarHint">{{ $savedTotal }} {{ \Illuminate\Support\Str::plural('post', $savedTotal) }} kept</span>
        </div>
    @endif

    <div id="savedWrap">
        @forelse ($posts as $post)
            @include('community.partials.feed-post', [
                'post' => $post,
                'friendIds' => $friendIds,
                'followingIds' => $followingIds,
                'savedIds' => $savedIds,
                'permalink' => true,
            ])
        @empty
            <div class="card p-8 text-center">
                <div class="empty-tile">🔖</div>
                <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Wala pang naka-save</p>
                <p class="text-sm text-gray-500 mt-1">
                    When a post is worth coming back to — a fertiliser rate, a price, a photo of a pest —
                    tap its bookmark and it waits here.
                </p>
                <a href="{{ route('community.index') }}" class="btn btn-primary btn-sm mt-4">Go to the wall</a>
            </div>
        @endforelse
    </div>

    @if ($posts->isNotEmpty())
        <div class="sv-tail" id="savedTail">
            <button type="button" id="savedLoadMore" class="btn btn-white btn-sm" data-infinite
                    data-before="{{ $before }}" {{ $hasMore ? '' : 'hidden' }}>Load more saved posts</button>
            <div class="sv-spin" id="savedSpin" role="status" aria-label="Loading more saved posts" hidden><i></i><i></i><i></i></div>
            <p class="sv-end" id="savedEnd" {{ $hasMore ? 'hidden' : '' }}>🔖 That's everything you have kept.</p>
        </div>
    @endif
</div>

<div class="sheet hidden" id="savedSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search your saved posts</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        @include('community.partials.live-search', [
            'id' => 'savedFind',
            'placeholder' => 'Search what you kept…',
            'label' => 'Search your saved posts — words or who wrote them',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the posts</button>
    </div>
</div>

@include('community.partials.post-actions')
@include('community.partials.wall-comments-modal')
@include('community.partials.report-js')
@endsection

@push('styles')
<style>
    .sv-wrap { max-width: 40rem; margin: 0 auto; }
    .sv-head { margin-bottom: 1rem; }
    .sv-title { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--color-gray-900); }
    .sv-sub { font-size: .85rem; color: var(--color-gray-500); margin-top: .2rem; line-height: 1.55; }

    /* The bar, borrowed from the wall so the search button is the same
       button in the same place. (The wall keeps these rules in its own page,
       which is why they are said again here rather than shared: two pages,
       one shape, no third file for four declarations.) */
    .wall-bar { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; }
    .wb-act { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; }
    .wb-hint { margin-left: auto; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    @media (max-width: 599px) { .wb-hint { display: none; } }
    .wb-filter { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0;
        max-width: 11rem; padding: .25rem .55rem; border-radius: 999px;
        font-size: .72rem; font-weight: 800;
        background: var(--color-brand-50); color: var(--color-brand-700);
        border: 1px solid var(--color-brand-200); }
    .wb-filter b { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wb-filter.hidden { display: none; }
    html.dark .wb-filter { background: rgb(61 104 35 / .25); border-color: #3f5626; color: #bfe19a; }

    /* The tail: a button for anybody who would rather press one, three dots
       while the next page is on its way, and a word when there is no more. */
    .sv-tail { text-align: center; padding-bottom: 1rem; }
    .sv-spin { display: flex; align-items: center; justify-content: center; gap: .35rem; padding: .9rem 0; }
    .sv-spin i { display: block; width: .45rem; height: .45rem; border-radius: 9999px;
        background: var(--color-brand-400); animation: svDot .9s ease-in-out infinite; }
    .sv-spin i:nth-child(2) { animation-delay: .12s; }
    .sv-spin i:nth-child(3) { animation-delay: .24s; }
    @keyframes svDot { 0%, 80%, 100% { opacity: .35; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-.25rem); } }
    .sv-end { font-size: .78rem; font-weight: 600; color: var(--color-gray-400); padding: 1.1rem 0 .4rem; }
    .sv-spin[hidden], .sv-end[hidden], #savedLoadMore[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .sv-spin i { animation-duration: 2.6s; } }
</style>
@endpush

@push('scripts')
@include('community.partials.views-js')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.react-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
<script>
/* Saved posts: looking through them, and more of them as you go.
 *
 * The same two behaviours the wall has, said in this page's names. One
 * endpoint answers both: a search is page one with words on it, so what
 * matched keeps paging like anything else. */
document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.getElementById('savedWrap');
    if (!wrap) return;
    const moreBtn = document.getElementById('savedLoadMore');
    const spin = document.getElementById('savedSpin');
    const endNote = document.getElementById('savedEnd');
    const findEl = document.getElementById('savedFind');
    const findNote = document.getElementById('savedFindNote');
    const filterChip = document.getElementById('savedFilterChip');
    const hint = document.getElementById('savedBarHint');
    const toast = (m, k) => (window.smToast ? window.smToast(m, k) : null);

    let query = '';
    let loading = false;
    let done = !moreBtn || moreBtn.hidden;

    const url = (before) => @json(route('community.saved-more'))
        + '?before=' + encodeURIComponent(before || '')
        + (query ? '&q=' + encodeURIComponent(query) : '');

    function finish() {
        done = true;
        if (moreBtn) moreBtn.hidden = true;
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    async function loadMore() {
        if (!moreBtn || done || loading || moreBtn.disabled) return;
        const before = moreBtn.getAttribute('data-before') || '';
        if (!before) { finish(); return; }
        loading = true;
        moreBtn.disabled = true;
        moreBtn.hidden = true;
        if (spin) spin.hidden = false;
        try {
            const res = await fetch(url(before), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const d = (data && data.data) || {};
            const tmp = document.createElement('div');
            tmp.innerHTML = d.html || '';
            // Guarded by id: a post cannot be its own neighbour on the page.
            [...tmp.querySelectorAll('.feed-post')].forEach((el) => {
                if (!document.getElementById(el.id)) wrap.appendChild(el);
            });
            if (spin) spin.hidden = true;
            if (d.hasMore && d.before) {
                moreBtn.setAttribute('data-before', d.before);
                moreBtn.disabled = false;
                moreBtn.hidden = false;
                moreBtn.textContent = 'Load more saved posts';
                loading = false;
                setTimeout(nearTail, 0);   // still near the bottom? keep going
                return;
            }
            finish();
        } catch (_) {
            // Left for a deliberate retry rather than hammering the server.
            if (spin) spin.hidden = true;
            moreBtn.hidden = false;
            moreBtn.disabled = false;
            moreBtn.textContent = 'Try again';
            toast('Could not load more.', 'error');
        } finally {
            loading = false;
        }
    }

    // 700px of runway, the same margin every other list here uses, so the
    // next cards are already there when the reader arrives.
    function nearTail() {
        if (!moreBtn || done || loading || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) loadMore();
    }
    /* Throttled on the clock rather than on a frame: a tab that is not
       painting never delivers one, and the check would never look again. */
    let lastLook = 0;
    window.addEventListener('scroll', () => {
        const now = Date.now();
        if (now - lastLook < 100) return;
        lastLook = now;
        nearTail();
    }, { passive: true });
    window.addEventListener('resize', nearTail, { passive: true });
    moreBtn?.addEventListener('click', loadMore);
    nearTail();   // a short list can end with the tail already in view

    /* ---- the search ---- */
    document.getElementById('savedSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('savedSearchSheet');
        // No `always`: the phone keypad should wait for a tap on the field.
        window.smFocus?.(findEl, { delay: 140 });
    });
    filterChip?.addEventListener('click', () => {
        if (!findEl) return;
        findEl.value = '';
        findEl.dispatchEvent(new Event('input', { bubbles: true }));
    });

    /* What came back, said twice: in the sheet where it was asked for, and on
       the bar, which is what stays on screen once the sheet is shut. */
    function sayFound(count, hasMore) {
        if (findNote) {
            if (!query) { findNote.hidden = true; findNote.textContent = ''; }
            else {
                findNote.hidden = false;
                findNote.innerHTML = count
                    ? (hasMore ? 'First ' : '') + count + ' ' + (count === 1 ? 'post' : 'posts') + ' matching <b></b>.'
                    : 'Walang tugma sa <b></b>.';
                findNote.querySelector('b').textContent = '“' + query + '”';
            }
        }
        if (filterChip) {
            filterChip.classList.toggle('hidden', !query);
            if (query) filterChip.querySelector('b').textContent = '“' + query + '”';
        }
        if (hint) hint.hidden = !!query;
    }

    async function search(q) {
        query = q;
        loading = true;
        try {
            const res = await fetch(url(''), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const d = (data && data.data) || {};
            wrap.innerHTML = d.html || '';
            const count = wrap.querySelectorAll('.feed-post').length;
            if (!count) {
                wrap.innerHTML = '<div class="card p-8 text-center">'
                    + '<div class="empty-tile">🔎</div>'
                    + '<p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>'
                    + '<p class="text-sm text-gray-500 mt-1">Nothing you kept says that — in the words or in who wrote it.</p></div>';
            }
            done = !(d.hasMore && d.before);
            if (spin) spin.hidden = true;
            if (endNote) endNote.hidden = true;
            if (moreBtn) {
                moreBtn.setAttribute('data-before', d.before || '');
                moreBtn.hidden = done;
                moreBtn.disabled = false;
                moreBtn.textContent = 'Load more saved posts';
            }
            sayFound(count, !!d.hasMore);
        } catch (_) {
            toast('Could not search just now.', 'error');
        } finally {
            loading = false;
            setTimeout(nearTail, 0);
        }
    }
    if (findEl) window.plazaLiveSearch?.(findEl, search);
});
</script>
@endpush
