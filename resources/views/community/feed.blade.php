@extends('layouts.app')

{{-- The wall gets its own ground: the cards are grey and the page was grey
     too, so a column of bands read as one undifferentiated slab. --}}
@section('body-class', 'plaza-ground')

@section('title', 'Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Kamustahan ng mga magsasaka')

@push('head')
@include('community.partials.plaza-css')
@push('styles')
<style>
    /* The composer, in the homepage's proportions rather than its own. */
    /* The composer runs to both edges, like the posts under it and the
       suggestions below them: the wall is a column of bands, and a rounded
       box floating on that column was the odd one out. */
    /* A dark green ground, so the cards sit ON something.
       The wall is a column of full-width bands; against the app's usual grey
       they and the page were the same colour and the edges between them did
       all the work. Green is the house colour, taken almost to black — dark
       enough to stay a background, coloured enough that a grey card reads as
       a card. */
    body.plaza-ground { background: #eef4e8; }
    html.dark body.plaza-ground { background: #0b140d; }

    .comp-card { margin-left: calc(var(--plaza-gutter, 1rem) * -1);
        margin-right: calc(var(--plaza-gutter, 1rem) * -1);
        padding: .85rem var(--plaza-gutter, 1rem);
        border-radius: 0; border-left: 0; border-right: 0; }
    /* The head sits as a post's head does: face, then the name and place
       beside it. The cloud hangs above the face and out of the card, which is
       why the card buys room for it (see #feedComposer below). */
    .comp-top { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: .7rem; }
    /* The attached photo, shown as itself. */
    .comp-shot { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; padding: .45rem .5rem;
        border-radius: .7rem; background: var(--color-gray-100); }
    .comp-shot.hidden { display: none; }
    .comp-shot img { width: 3rem; height: 3rem; border-radius: .5rem; object-fit: cover; flex: none;
        background: var(--color-gray-200); }
    .comp-shot-txt { display: flex; flex-direction: column; min-width: 0; flex: 1 1 auto; }
    .comp-shot-txt b { font-size: .76rem; font-weight: 800; color: var(--color-gray-800); }
    .comp-shot-txt i { font-style: normal; font-size: .68rem; color: var(--color-gray-500); }
    .comp-shot-x { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; border: 0; cursor: pointer;
        background: transparent; color: var(--color-gray-400); font-size: .8rem; }
    .comp-shot-x:hover { color: #b91c1c; background: var(--color-gray-200); }
    /* The ways to add to a post, named: four unlabelled icons are four
       guesses, and the line beside them answers all of them at once.
       No rule above it — a divider between the field and the things that
       fill it separates what belongs together, and cost a rem and a half of
       air to do it. */
    .comp-add { margin-top: .55rem; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    /* A box of its own, so the row is a thing to reach into rather than four
       icons adrift under the field. */
    .comp-add-box { padding: .35rem .5rem .35rem .7rem; border-radius: .8rem;
        border: 1px solid var(--color-gray-200); background: var(--color-gray-50); }
    html.dark .comp-add-box { border-color: rgb(255 255 255 / .08); background: rgb(255 255 255 / .03); }
    .comp-add-lbl { font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .comp-add-row { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; margin-left: auto; }
    html.dark .comp-shot { background: rgb(255 255 255 / .05); }
    /* The icons carry their own weight now the rule is gone. */
    .comp-add-row .wall-act { width: 2.15rem; height: 2.15rem; border-radius: .6rem;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .comp-add-row .wall-act:hover { background: var(--color-gray-100); }
    html.dark .comp-add-row .wall-act:hover { background: rgb(255 255 255 / .06); }
    @media (prefers-reduced-motion: reduce) { .comp-add-row .wall-act { transition: none; } }
    .comp-hint { font-size: .72rem; color: var(--color-gray-400); margin-top: .35rem; }
    .comp-hint b { color: var(--color-gray-500); font-weight: 800; }
    /* A thought bubble floats above the avatar and out of the card, so a card
       that has one needs the room; one that does not would just look adrift. */
    /* The same trade a post carrying a cloud makes: the card keeps its own
       padding, the cloud rides ITS top edge rather than being tucked inside,
       and the margin above is what keeps it off the card before. Padding here
       instead would sit the cloud lower than every cloud under it. */
    #feedComposer { margin-top: 1.35rem; }

    /* People you may know — a rail that scrolls sideways on a phone. */
    /* The band's own rules live in plaza-css — the members page draws it too. */

    /* On a phone the wall is the screen: a card that keeps side margins and
       rounded corners is a card pretending it is not the whole page. */
    @media (max-width: 640px) {
        .plaza-center .fp-card, .plaza-center .feed-post {
            border-radius: 0; border-left: 0; border-right: 0;
            margin-left: calc(var(--plaza-gutter, 1rem) * -1);
            margin-right: calc(var(--plaza-gutter, 1rem) * -1);
        }
    }


</style>
@endpush
<style>
    /* Feed + a single right rail on wide screens (co-farmer requests, your
       discussions, what's new in the blog, sponsors). Below 1024px there is no
       column to sit in, so the rail's cards ride inside the feed instead —
       see the mobile block in the wall itself. */
    .plaza-side { display: none; }
    @media (min-width: 1024px) {
        .plaza-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 17.5rem;
            gap: 1.25rem;
            align-items: start;
        }
        .plaza-side {
            display: block;
            position: sticky;
            top: 5rem;
            max-height: calc(100vh - 6rem);
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .plaza-side::-webkit-scrollbar { width: 6px; }
        .plaza-side::-webkit-scrollbar-thumb { background: rgb(0 0 0 / .12); border-radius: 3px; }
    }
    /* Wide desktops have room to let the rail's article titles breathe. */
    @media (min-width: 1280px) {
        .plaza-shell { grid-template-columns: minmax(0, 1fr) 19rem; }
    }

    /* The tail of the wall: one place that is either a button, a loader, or
       the end of the road — never two of them at once. */
    .feed-tail { text-align: center; margin-top: .25rem; padding-bottom: 1rem; }
    .feed-spin { display: flex; align-items: center; justify-content: center; gap: .35rem; padding: .9rem 0; }
    .feed-spin i { display: block; width: .45rem; height: .45rem; border-radius: 9999px;
        background: var(--color-brand-400); animation: feedDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .feed-spin i:nth-child(2) { animation-delay: .12s; }
    .feed-spin i:nth-child(3) { animation-delay: .24s; }
    @keyframes feedDot { 0%, 100% { opacity: .25; transform: translateY(0); } 50% { opacity: 1; transform: translateY(-.25rem); } }
    .wall-end { font-size: .78rem; font-weight: 600; color: var(--color-gray-400); padding: 1.1rem 0 .4rem; }
    /* Both carry `hidden`, and both set display — say so louder than they do. */
    .feed-spin[hidden], .wall-end[hidden] { display: none; }
    /* A loader that stops looks like a page that broke; slow it instead. */
    @media (prefers-reduced-motion: reduce) {
        .feed-spin i { animation-duration: 2.6s; }
    }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'wall'])

{{-- Reels first, straight under the nav.
     They are the thing with the shortest shelf life on this page — a clip
     somebody shot this morning — and they are read by scrolling sideways,
     which only works if the rail is where a thumb already is. --}}
@include('community.partials.reels')

@php
    // The wall controller does not gather the rail, so the rail gathers
    // itself: your discussions (freshest talk first) and the newest articles
    // from the Technician's Blog. Both are small, indexed reads.
    $meId = (int) auth()->id();
    $myGroupIds = \App\Models\CommunityGroupMember::where('userId', $meId)
        ->where('deleteStatus', 1)
        ->pluck('groupId');
    $railGroups = \App\Models\CommunityGroup::active()
        ->whereIn('id', $myGroupIds)
        ->withCount(['members as member_count'])
        ->withMax(['posts as last_post_at'], 'created_at')
        ->get()
        ->sortByDesc(fn ($g) => (string) ($g->last_post_at ?? ''))
        ->take(5)
        ->values();
    // Joined nothing yet: show what is out there rather than an empty box —
    // the card changes its title so it is not pretending they are yours.
    $railGroupsAreMine = $railGroups->isNotEmpty();
    if (! $railGroupsAreMine) {
        $railGroups = ($recentGroups ?? collect())->take(5);
    }
    $railArticles = \App\Models\AsCommunityBlogPost::active()
        ->published()
        ->withCount('comments')
        ->orderByDesc('publishedAt')
        ->orderByDesc('id')
        ->limit(3)
        ->get();
@endphp

<div class="plaza-shell">
{{-- CENTER — the feed (full width beside the rail) --}}
<div class="plaza-center min-w-0">
{{-- The right rail folds away on phones and takes the co-farmer requests with
     it — so on small screens the requests announce themselves up top instead
     of silently not existing. --}}
@if (($friendRequestCount ?? 0) > 0 && ($friendRequests ?? collect())->isNotEmpty())
    <a href="{{ route('community.connect.requests') }}" class="card p-3 mb-4 lg:hidden flex items-center gap-3 plaza-accent">
        <span class="flex -space-x-2 shrink-0">
            @foreach ($friendRequests->take(3) as $reqUser)
                @include('community.partials.avatar', ['user' => $reqUser, 'size' => 'avatar-sm', 'link' => false])
            @endforeach
        </span>
        <span class="min-w-0 grow">
            <span class="block text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">{{ $friendRequestCount }} co-farmer {{ Str::plural('request', $friendRequestCount) }}</span>
            <span class="block text-xs text-gray-500 truncate">{{ $friendRequests->first()->full_name }}{{ $friendRequestCount > 1 ? ' and others are' : ' is' }} waiting for you</span>
        </span>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </a>
@endif
{{-- Share to your own wall, straight from the feed — the same shape the
     homepage composer has: your face, what's on your mind floating above it,
     and a field with room to write in. --}}
<div class="card comp-card mb-4 plaza-accent" id="feedComposer" data-video-host>
    {{-- The same head a post has: the cloud above the face, the name beside
         it, the place under the name — so the box you write in looks like
         the post it becomes. The one difference is that this cloud is yours,
         so it is a button and not somebody else's thought. --}}
    @php
        $mePlace = trim(implode(', ', array_filter([auth()->user()?->city, auth()->user()?->province])));
    @endphp
    <div class="comp-top">
        <button type="button" id="feedMe" class="comp-me status-cloud-wrap shrink-0"
                data-status-bubble title="Set what's on your mind">
            <span class="status-cloud{{ filled(auth()->user()?->statusBubble) ? '' : ' is-empty' }}">
                <span class="status-cloud-text" data-status-text data-empty-label="💭 What's on your mind?">{{ auth()->user()?->statusBubble ?: "💭 What's on your mind?" }}</span>
            </span>
            <span class="avatar avatar-md {{ \App\Support\CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} overflow-hidden">
                @if (auth()->user()?->avatarPath)
                    <img src="{{ \App\Support\MediaStore::url(auth()->user()->avatarPath) }}" alt="" class="w-full h-full object-cover">
                @else
                    {{ auth()->user()->initials ?? '?' }}
                @endif
            </span>
        </button>
        <div class="min-w-0 grow fp-head-txt">
            <p class="text-sm leading-tight font-semibold text-gray-900">{{ auth()->user()->full_name }}</p>
            {{-- Where you farm, or — if you have not said — how long you
                 have been here. The same fallback a post uses, so the box you
                 write in still matches the post it becomes. --}}
            @if ($mePlace)
                <p class="text-xs text-gray-400">📍 {{ $mePlace }}</p>
            @elseif (auth()->user()?->created_at)
                <p class="text-xs text-gray-400">🌱 Member since {{ auth()->user()->created_at->timezone('Asia/Manila')->format('M Y') }}</p>
            @endif
        </div>
    </div>

    {{-- The @ and # hint lives in the placeholder, where it is read at the
         moment it applies. --}}
    <textarea id="feedPostBody" class="form-textarea w-full comp-box" rows="4" maxlength="4000" data-mentionable data-preview="#feedPreview"
        placeholder="Kamusta ang bukid, {{ auth()->user()->firstName }}? Type @ to mention a co-farmer, # to tag a topic."></textarea>
    <div id="feedPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>

    {{-- What is coming with the post, shown as itself. The photo has usually
         just been through the editor, so its file name is one the editor
         invented about a file nobody has seen. --}}
    <div class="comp-shot hidden" id="feedChip">
        <img id="feedChipThumb" src="" alt="">
        <span class="comp-shot-txt"><b>Photo ready</b><i id="feedChipName"></i></span>
        <button type="button" id="feedChipClear" class="comp-shot-x" aria-label="Remove photo">✕</button>
    </div>
    <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>

    {{-- The ways to add to it, said out loud. Four unlabelled icons in a row
         are four guesses; the line above them costs nothing and answers all
         four. --}}
    <div class="comp-add comp-add-box">
        <span class="comp-add-lbl">Add to your post</span>
        <div class="comp-add-row">
            <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input type="file" id="feedImage" accept="image/*" class="hidden">
            </label>
            <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
            <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
            </button>
            <input type="file" class="js-video-file hidden" accept="video/*">
            <button type="button" class="wall-act js-emoji-btn" data-target="feedPostBody" aria-label="Add an emoji" title="Emoji">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
        </div>
    </div>

    <button type="button" id="feedPostSubmit" class="btn btn-primary comp-send">Post</button>
</div>

@include('community.partials.pymk')

{{-- The feed: friends and kapit-bahay provinces first --}}
<div id="feedWrap">
    @forelse ($posts as $post)
        @include('community.partials.feed-post', [
            'post' => $post,
            'friendIds' => $friendIds,
            'followingIds' => $followingIds ?? [],
            'savedIds' => $savedIds ?? [],
        ])
        {{-- Rooms and articles, dealt in where a reader is already moving
             rather than pinned to fixed seats where they read as ads. The
             plan comes from the controller: a few of each, alternating,
             every three to five posts. --}}
        @php $slot = ($interruptions ?? [])[$loop->iteration] ?? null; @endphp
        @if ($slot && $slot['kind'] === 'discussion')
            @include('community.partials.feed-discussion', ['discussion' => $slot['item']])
        @elseif ($slot)
            @include('community.partials.feed-article', ['article' => $slot['item']])
        @endif
        {{-- Phones have no rail. Stacking it above the wall would bury the
             feed under four cards before the first post; stacking it below an
             endless feed means nobody ever reaches it. So it rides here — a
             reader who has passed three posts is already scrolling. --}}
        @if ($loop->iteration === 3)
            <div class="lg:hidden" id="feedRailMobile">
                @include('community.partials.wall-rail', ['withRequests' => false])
            </div>
        @endif
    @empty
        <div class="card p-8 text-center">
            <div class="empty-tile">🏠</div>
            <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Tahimik pa ang kapitbahayan</p>
            <p class="text-sm text-gray-500 mt-1">Ikaw ang mauna — share what's happening sa bukid mo.</p>
        </div>
    @endforelse
    {{-- A wall too short for the plan to reach still gets one of each. --}}
    @if (empty($interruptions) && ($injectDiscussion ?? null))
        @include('community.partials.feed-discussion', ['discussion' => $injectDiscussion])
    @endif
    @if (empty($interruptions) && ($injectArticle ?? null))
        @include('community.partials.feed-article', ['article' => $injectArticle])
    @endif
    @if ($posts->count() < 3)
        {{-- Too few posts for the rail to ride after the third one. --}}
        <div class="lg:hidden" id="feedRailMobile">
            @include('community.partials.wall-rail', ['withRequests' => false])
        </div>
    @endif
</div>
@if ($posts->isNotEmpty())
    <div class="feed-tail" id="feedTail">
        <button type="button" id="feedLoadMore" class="btn btn-white btn-sm" data-infinite
                data-before="{{ optional($posts->last()->created_at)->toIso8601String() }}">Load more posts</button>
        <div class="feed-spin" id="feedSpin" role="status" aria-label="Loading older posts" hidden><i></i><i></i><i></i></div>
        <p class="wall-end" id="feedEnd" hidden>🌾 Nasa dulo ka na — that's the whole wall for now.</p>
    </div>
@endif
</div>{{-- /plaza-center --}}

{{-- RIGHT rail — co-farmer requests, your discussions, the blog, sponsors --}}
<aside class="plaza-side plaza-side-right">
    @include('community.partials.wall-rail', ['withRequests' => true])
</aside>
</div>{{-- /plaza-shell --}}

@include('community.partials.photo-editor')
@include('community.partials.post-actions')
@include('community.partials.pymk-js')
@include('community.partials.views-js')
@include('community.partials.wall-comments-modal')
@include('community.partials.report-js')
{{-- Tapping your own photo on the composer asks what is on your mind — the
     same cloud the wall draws over every other member's face. --}}
@include('community.partials.status-modal')
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.composer-preview-js')
@include('community.partials.infinite-js')
@endpush
@include('community.connect.partials.connect-js')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    /* ---- Infinite scroll -------------------------------------------------
       infinite-js watches [data-infinite] and clicks it as it nears the
       viewport; this is what that click does. One in-flight request at a
       time (the `loading` latch AND the hidden button, because the observer
       fires on its own schedule), a loader while it waits, and a plain end
       note when the wall runs out — the button used to just vanish.

       The wall does not lean on that observer alone. An IntersectionObserver
       only reports a *change*: a trigger that is already inside the pre-load
       margin when a page lands never crosses the line again, and the feed sits
       there waiting for a scroll that is not coming (measured: a wall that
       stopped after one page while its Load-more button was fully in view).
       So the scroll position is also read directly, and both paths funnel
       through the same latch so they cannot fetch twice. */
    const moreBtn = document.getElementById('feedLoadMore');
    const spin = document.getElementById('feedSpin');
    const endNote = document.getElementById('feedEnd');
    let loading = false;
    let done = false;                 // the wall ended; stop asking, for good

    function finish() {
        // Out of posts: no button, no loader, one line saying so.
        done = true;
        if (moreBtn) moreBtn.remove();
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
            const res = await fetch(@json(route('community.feed-more')) + '?before=' + encodeURIComponent(before), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const tmp = document.createElement('div');
            tmp.innerHTML = data.data.html;
            const wrap = document.getElementById('feedWrap');
            // Page 1 is ranked, so an older page can repeat a post it lifted.
            [...tmp.querySelectorAll('.feed-post')].forEach((el) => { if (!document.getElementById(el.id)) wrap.appendChild(el); });
            if (spin) spin.hidden = true;
            if (data.data.hasMore && data.data.before) {
                moreBtn.setAttribute('data-before', data.data.before);
                moreBtn.disabled = false;
                moreBtn.hidden = false;
                moreBtn.textContent = 'Load more posts'; // clears a previous failure's label
                loading = false;
                // Still near the bottom? Keep going without waiting for a scroll.
                setTimeout(nearTail, 0);
                return;
            }
            finish();
        } catch (_) {
            // Leave the button for a deliberate retry rather than hammering.
            if (spin) spin.hidden = true;
            moreBtn.hidden = false;
            moreBtn.disabled = false;
            moreBtn.textContent = 'Try again';
            toast('Could not load more.', 'error');
        } finally {
            loading = false;
        }
    }

    // 700px of runway, the same margin the shared observer uses, so a reader
    // meets the next posts already there rather than a spinner.
    function nearTail() {
        if (!moreBtn || done || loading || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) loadMore();
    }
    /* Throttled on the clock, not on requestAnimationFrame: a tab that is not
       painting (backgrounded, or a headless run) never delivers the frame, and
       an rAF-gated check would then never look again. */
    let lastLook = 0;
    function onScroll() {
        const now = Date.now();
        if (now - lastLook < 100) return;
        lastLook = now;
        nearTail();
    }
    moreBtn?.addEventListener('click', loadMore);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    // A short wall can end with the tail already in view on first paint.
    nearTail();

    /* ---- Composer -------------------------------------------------------- */
    const fileInput = document.getElementById('feedImage');
    const chip = document.getElementById('feedChip');

    fileInput?.addEventListener('change', async () => {
        // The photo goes through the editor before it is a photo you are
        // posting: filters, a word, an arrow at the thing you mean. The
        // result is written back into the input, so the send path below is
        // untouched. Backing out clears the pick.
        if (fileInput.files[0] && window.smEditInto) await window.smEditInto(fileInput);
        const f = fileInput.files[0];
        chip.classList.toggle('hidden', !f);
        showShot(f);
    });
    /* The picture, not its file name.
     *
     * A photo that has been through the editor is written back as a name the
     * editor made up ("a1b2c3d4.webp"), which tells the person nothing about
     * a file they have never seen. The object URL is released when the chip
     * is cleared or replaced, so a long session does not leak them. */
    let shotUrl = null;
    function showShot(f) {
        const img = document.getElementById('feedChipThumb');
        const name = document.getElementById('feedChipName');
        if (shotUrl) { try { URL.revokeObjectURL(shotUrl); } catch (_) {} shotUrl = null; }
        if (!f) { img.removeAttribute('src'); name.textContent = ''; return; }
        shotUrl = URL.createObjectURL(f);
        img.src = shotUrl;
        // Its size is the honest thing to say about a file nobody named.
        const kb = Math.max(1, Math.round(f.size / 1024));
        name.textContent = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
    }
    document.getElementById('feedChipClear')?.addEventListener('click', () => {
        fileInput.value = '';
        chip.classList.add('hidden');
        showShot(null);
    });

    document.getElementById('feedPostSubmit')?.addEventListener('click', async (e) => {
        const host = document.getElementById('feedComposer');
        const body = document.getElementById('feedPostBody').value.trim();
        const img = fileInput?.files[0];
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!body && !img && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const fd = new FormData();
        if (body) fd.append('body', body);
        if (img) fd.append('image', img);
        if (vid) fd.append('video', vid);
        fd.append('render', 'feed'); // return a feed-post card to prepend
        const btn = e.currentTarget;
        const prev = btn.textContent;
        btn.disabled = true;
        btn.textContent = vid ? 'Uploading…' : 'Posting…';
        try {
            const res = await fetch(@json(route('community.wall.post', ['userId' => auth()->id()])), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (!data.success) { toast(data.message || 'Could not post.', 'error'); return; }
            // Prepend the new post to the feed (no reload) with an entrance animation.
            const wrap = document.getElementById('feedWrap');
            if (wrap && data.data?.html) {
                wrap.querySelector('.card.p-8.text-center')?.remove(); // drop empty state
                wrap.insertAdjacentHTML('afterbegin', data.data.html);
                const added = wrap.firstElementChild;
                if (added) { added.classList.add('plaza-comment-enter'); added.addEventListener('animationend', () => added.classList.remove('plaza-comment-enter'), { once: true }); added.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            }
            // Clear the composer.
            document.getElementById('feedPostBody').value = '';
            document.getElementById('feedPostBody').dispatchEvent(new Event('input', { bubbles: true }));
            if (fileInput) fileInput.value = '';
            chip?.classList.add('hidden');
            if (window.plazaClearVideo) window.plazaClearVideo(host);
            toast('Shared sa wall mo! 🌾');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.textContent = prev; }
    });
});
</script>
@endpush
