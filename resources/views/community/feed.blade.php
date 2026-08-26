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

    /* The pictures waiting to go up, as pictures. A strip of thumbnails
       rather than a list of file names: the photo that has been through the
       editor has a name nobody would recognise, and the one picked from a
       season has a name about a field. */
    .comp-shots { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem; }
    .comp-shots.hidden { display:none; }
    .comp-shot-one { position:relative; width:4.5rem; height:4.5rem; border-radius:.6rem;
        overflow:hidden; background:var(--color-gray-100); flex:none; }
    .comp-shot-one img { width:100%; height:100%; object-fit:cover; display:block; }
    .comp-shot-one button { position:absolute; top:.15rem; right:.15rem; width:1.35rem; height:1.35rem;
        border:0; border-radius:999px; cursor:pointer; display:flex; align-items:center; justify-content:center;
        background:rgb(17 24 39 / .62); color:#fff; font-size:.7rem; line-height:1; }
    .comp-shot-one button:hover { background:rgb(185 28 28 / .9); }
    html.dark .comp-shot-one { background:rgb(255 255 255 / .06); }
    /* A clip's tile: the shot tile gone dark, wearing the clapperboard. */
    .comp-shot-one.is-clip { background:#10131a; }
    .comp-shot-one.is-clip::after { content:'\1F3AC'; position:absolute; inset:0; display:flex;
        align-items:center; justify-content:center; font-size:1.15rem; pointer-events:none;
        text-shadow:0 1px 4px rgb(0 0 0 / .6); }
    .comp-shot-one.is-clip img { opacity:.85; }


    /* The bar the wall opens with: what you came to do, then a word about
       where you are. */
    .wall-bar { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; }
    .wb-act { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; }
    .wb-hint { margin-left: auto; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    @media (max-width: 599px) { .wb-hint { display: none; } }
    /* The words stay. A magnifier alone is a guess, and this bar is two
       buttons on a line with room for both — the count beside them is what
       gives way when the line is tight. */
    .wb-filter { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0;
        max-width: 11rem; padding: .25rem .55rem; border-radius: 999px;
        font-size: .72rem; font-weight: 800;
        background: var(--color-brand-50); color: var(--color-brand-700);
        border: 1px solid var(--color-brand-200); }
    .wb-filter b { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wb-filter.hidden { display: none; }
    html.dark .wb-filter { background: rgb(61 104 35 / .25); border-color: #3f5626; color: #bfe19a; }
    /* In a sheet the composer is not a band across the page: the sheet owns
       its edges and its padding, and the head still needs the room the status
       cloud hangs in. */
    .comp-sheeted { position: relative; padding-top: 1.4rem; }

    .comp-card { margin-left: calc(var(--plaza-gutter, 1rem) * -1);
        margin-right: calc(var(--plaza-gutter, 1rem) * -1);
        padding: .85rem var(--plaza-gutter, 1rem);
        border-radius: 0; border-left: 0; border-right: 0; }
    /* The head sits as a post's head does: face, then the name and place
       beside it. The cloud hangs above the face and out of the card, which is
       why the card buys room for it (see #feedComposer below). */
    .comp-top { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: .7rem; }
    /* The name block centres on the face; the face keeps flex-start so the
       cloud's overhang is measured from the top as it always was. */
    .comp-top > .min-w-0 { align-self: center; }
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
    /* The composer lives in a sheet now, and the margin that used to sit
       here was for the card it once followed on the page: under a sheet
       header it only doubled the header's own padding into a dead band. The
       cloud's hanging room is .comp-sheeted's padding-top, which stays. */

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
{{-- What you came to do, as two buttons rather than a box you scroll past.
     The wall is the posts; writing one and looking for one are errands, and
     both open from the bottom over what you were reading — the same shape
     the discussion room uses. --}}
<div class="wall-bar" id="wallBar">
    <button type="button" id="wallWriteBtn" class="btn btn-outline btn-sm wb-act">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
        <span class="wb-act-lbl">New post</span>
    </button>
    <button type="button" id="wallSearchBtn" class="btn btn-outline btn-sm wb-act" title="Search the wall">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <span class="wb-act-lbl">Search</span>
    </button>
    {{-- A filter is a thing that is ON, and it has to say so where it can be
         seen once the sheet is shut. --}}
    <button type="button" class="wb-filter hidden" id="wallFilterChip" title="Clear the search">
        <b></b>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <span class="wb-hint" id="wallBarHint">Ano'ng balita sa bukid?</span>
</div>

<div class="sheet hidden" id="wallComposerSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Write a post</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    {{-- Enough top padding for the thought bubble's overhang, plus a
         breath so it does not read as touching the header rule. --}}
    <div class="sheet-body" style="padding-top:1rem;padding-bottom:1.1rem">
<div class="comp-sheeted" id="feedComposer" data-video-host>
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

    {{-- What is coming with the post, shown as itself: the pictures, not
         their file names. A photo that has been through the editor is written
         back under a name the editor invented about a file nobody has seen. --}}
    <div class="comp-shots hidden" id="feedShots"></div>
    <div class="comp-shots hidden" id="feedClips"></div>
    <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>

    {{-- The ways to add to it, said out loud. Four unlabelled icons in a row
         are four guesses; the line above them costs nothing and answers all
         four. --}}
    <div class="comp-add comp-add-box">
        <span class="comp-add-lbl">Add to your post</span>
        <div class="comp-add-row">
            {{-- One door to three ways in — this device, the camera, or the
                 pictures the app already keeps for you. --}}
            <button type="button" class="wall-act" id="feedPhotoBtn" title="Add photos" aria-label="Add photos">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </button>
            <input type="file" id="feedImage" accept="image/jpeg,image/png,image/webp" class="hidden" multiple>
            {{-- capture= asks the phone for its camera rather than its files. --}}
            <input type="file" id="feedCamera" accept="image/*" capture="environment" class="hidden">
            {{-- Two doors behind one icon — upload or the gallery — the same
                 pair a comment's video button offers. --}}
            <button type="button" class="wall-act" id="feedVideoBtn" title="Add a video" aria-label="Add a video">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
            <input type="file" id="feedVideoFiles" accept="video/*" class="hidden" multiple>
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
    </div>
</div>

{{-- Where a picture comes from. The same three the rest of the app offers,
     and the gallery is the one that is not obvious: every photo the farm has
     kept across its seasons, which is where the useful ones already are. --}}
<div class="sheet hidden" id="wallPhotoSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add photos</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="plaza-srcs">
            <button type="button" class="plaza-src" id="feedSrcUpload">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v1.5A2.5 2.5 0 006.5 21h11a2.5 2.5 0 002.5-2.5V17"/></svg></span>
                <span class="plaza-src-t"><b>Upload from this device</b><small>Pick one photo or several at once.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="feedSrcCamera">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 012-2h1.4l1-1.6h7.2l1 1.6H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V8z"/><circle cx="12" cy="13" r="3.4"/></svg></span>
                <span class="plaza-src-t"><b>Take a photo now</b><small>Open the camera and shoot what you see.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="feedSrcGallery">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 15l3-3.5 2.4 2.8L15 11l3 4"/></svg></span>
                <span class="plaza-src-t"><b>From my gallery</b><small>Photos your seasons already keep.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-3">Up to 8 photos in one post.</p>
    </div>
</div>

<div class="sheet hidden" id="wallSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search the wall</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        @include('community.partials.live-search', [
            'id' => 'wallFind',
            'placeholder' => 'Search posts…',
            'label' => 'Search the wall — words or who wrote them',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the posts</button>
    </div>
</div>



{{-- Where a clip comes from — the comment box's two doors, in a sheet.
     Filming stays its own button beside the icon, because a phone already
     looking at the thing should not have to read a menu first. --}}
<div class="sheet hidden" id="wallVideoSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a video</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <div class="plaza-srcs">
            <button type="button" class="plaza-src" id="feedVSrcUpload">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v1.5A2.5 2.5 0 006.5 21h11a2.5 2.5 0 002.5-2.5V17"/></svg></span>
                <span class="plaza-src-t"><b>Upload from phone</b><small>One clip or several at once — up to a minute each.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
            <button type="button" class="plaza-src" id="feedVSrcGallery">
                <span class="plaza-src-ic"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 10.5v5l4.5-2.5-4.5-2.5z"/></svg></span>
                <span class="plaza-src-t"><b>From my gallery</b><small>Clips your seasons already keep.</small></span>
                <svg class="plaza-src-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
    </div>
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
                {{-- The cursor has to be the same measure the wall is ordered
                     by, or the next page starts somewhere else entirely. A
                     lifted post carries no computed moment; its own time is
                     the honest answer for it. --}}
                data-before="{{ \Illuminate\Support\Carbon::parse($posts->last()->lastActivityAt ?: $posts->last()->created_at)->toIso8601String() }}">Load more posts</button>
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
@include('community.partials.avatar-zoom')
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
    const findEl = document.getElementById('wallFind');
    const findNote = document.getElementById('wallFindNote');
    const filterChip = document.getElementById('wallFilterChip');
    let loading = false;
    let done = false;                 // the wall ended; stop asking, for good
    let query = '';

    /* Hidden, not removed: a search is another first page, and a button that
       was deleted when the wall ran out has nothing to come back to when the
       answer runs longer than a screen. */
    function finish() {
        // Out of posts: no button, no loader, one line saying so.
        done = true;
        if (moreBtn) { moreBtn.hidden = true; moreBtn.disabled = true; }
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    const feedUrl = (before) => @json(route('community.feed-more'))
        + '?before=' + encodeURIComponent(before || '')
        + (query ? '&q=' + encodeURIComponent(query) : '');

    async function loadMore() {
        if (!moreBtn || done || loading || moreBtn.disabled) return;
        const before = moreBtn.getAttribute('data-before') || '';
        if (!before) { finish(); return; }
        loading = true;
        moreBtn.disabled = true;
        moreBtn.hidden = true;
        if (spin) spin.hidden = false;
        try {
            const res = await fetch(feedUrl(before), { headers: { Accept: 'application/json' } });
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

    /* ---- Composer: the pictures --------------------------------------------
     *
     * A post used to carry one photo, held in the file input itself. Several
     * cannot live there — a picture picked from a season is a path and never
     * was a file — so the composer keeps its own list and the input is only
     * a way of adding to it. Each entry is {file} or {path, url}; the strip
     * below the field draws them, and the send builds images[] and
     * galleryPaths[] out of the same list.
     */
    const MAX_SHOTS = 8;
    const fileInput = document.getElementById('feedImage');
    const camInput = document.getElementById('feedCamera');
    const shotsRow = document.getElementById('feedShots');
    const shots = [];

    function paintShots() {
        if (!shotsRow) return;
        shotsRow.classList.toggle('hidden', shots.length === 0);
        shotsRow.innerHTML = shots.map((s, i) =>
            '<span class="comp-shot-one"><img src="' + s.url + '" alt="">'
            + '<button type="button" data-shot="' + i + '" aria-label="Remove photo">✕</button></span>').join('');
    }
    shotsRow?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-shot]');
        if (!btn) return;
        const i = Number(btn.dataset.shot);
        const gone = shots.splice(i, 1)[0];
        // Object URLs are ours to release; a long session leaks them otherwise.
        if (gone && gone.file) { try { URL.revokeObjectURL(gone.url); } catch (_) {} }
        paintShots();
    });
    function addFile(f) {
        if (!f) return;
        if (shots.length >= MAX_SHOTS) { toast('That is eight photos — the most a post carries.', 'error'); return; }
        shots.push({ file: f, url: URL.createObjectURL(f) });
    }
    function addPick(item) {
        if (!item || !item.path) return;
        if (shots.length >= MAX_SHOTS) { toast('That is eight photos — the most a post carries.', 'error'); return; }
        if (shots.some((s) => s.path === item.path)) return;   // the same picture twice is once
        shots.push({ path: item.path, url: item.url || '' });
    }

    fileInput?.addEventListener('change', async () => {
        const picked = [...(fileInput.files || [])];
        /* One photo still goes through the editor — filters, a word, an arrow
         * at the thing you mean. Several do not: five photos would be five
         * trips through it, which is not what "add photos" asked for. */
        if (picked.length === 1 && window.smEditInto) {
            await window.smEditInto(fileInput);
            addFile((fileInput.files || [])[0]);
        } else {
            picked.forEach(addFile);
        }
        fileInput.value = '';
        paintShots();
    });
    camInput?.addEventListener('change', () => {
        const f = (camInput.files || [])[0];
        addFile(f);
        camInput.value = '';
        paintShots();
    });

    /* The three ways in. */
    document.getElementById('feedPhotoBtn')?.addEventListener('click', () => window.openSheet?.('wallPhotoSheet'));
    document.getElementById('feedSrcUpload')?.addEventListener('click', () => {
        window.closeSheet?.('wallPhotoSheet');
        fileInput?.click();
    });
    document.getElementById('feedSrcCamera')?.addEventListener('click', () => {
        window.closeSheet?.('wallPhotoSheet');
        camInput?.click();
    });
    document.getElementById('feedSrcGallery')?.addEventListener('click', () => {
        window.closeSheet?.('wallPhotoSheet');
        if (typeof window.smPickMedia !== 'function') { toast('The gallery is not available here.', 'error'); return; }
        /* Tap to collect, then one button to bring them all: a post takes
           eight photographs and picking them one sheet-open at a time was
           eight sheet-opens. The picker has always had this mode — the AI
           technician's question uses it — and the room left for them is
           whatever the post has not already taken. */
        window.smPickMedia({
            allSchedules: true, kinds: 'image', title: 'From your gallery',
            multiple: true,
            max: Math.max(1, MAX_SHOTS - shots.length),
            onPick: (item) => { addPick(item); paintShots(); },
        });
    });

    /* ---- Clips: the comment box's model, worn by the composer -------------
     * One list holds every clip — files to upload and gallery references —
     * capped at three alongside whatever the record button holds. Tiles sit
     * in their own strip with a ✕ apiece. */
    const MAX_CLIPS = 3;
    const clips = [];
    const clipsRow = document.getElementById('feedClips');
    /* The record button's slot lives on the composer element; asked for by
     * id, because this block runs before the submit handler names it. */
    const clipTally = () => {
        const h = document.getElementById('feedComposer');
        return clips.length + ((h && window.plazaVideoFile && window.plazaVideoFile(h)) ? 1 : 0);
    };
    function paintClips() {
        if (!clipsRow) return;
        clipsRow.classList.toggle('hidden', clips.length === 0);
        clipsRow.innerHTML = clips.map((c, i) =>
            '<span class="comp-shot-one is-clip">' + (c.url ? '<img src="' + c.url + '" alt="">' : '')
            + '<button type="button" data-clip="' + i + '" aria-label="Remove video">✕</button></span>').join('');
    }
    clipsRow?.addEventListener('click', (e) => {
        const rm = e.target.closest('[data-clip]');
        if (!rm) return;
        const gone = clips.splice(Number(rm.dataset.clip), 1)[0];
        if (gone && gone.file) { try { URL.revokeObjectURL(gone.url); } catch (_) {} }
        paintClips();
    });
    function addClipFile(f) {
        if (!f) return;
        if (clipTally() >= MAX_CLIPS) { toast('That is three clips — the most a post carries.', 'error'); return; }
        clips.push({ file: f, url: '' });
    }
    function addClipPick(item) {
        if (!item || !item.path) return;
        if (clipTally() >= MAX_CLIPS) { toast('That is three clips — the most a post carries.', 'error'); return; }
        if (clips.some((c) => c.path === item.path)) return;
        clips.push({ path: item.path, url: item.posterUrl || item.url || '' });
    }
    function clearClips() {
        clips.length = 0;
        const vf = document.getElementById('feedVideoFiles');
        if (vf) vf.value = '';
        paintClips();
    }
    document.getElementById('feedVideoBtn')?.addEventListener('click', () => window.openSheet?.('wallVideoSheet'));
    document.getElementById('feedVSrcUpload')?.addEventListener('click', () => {
        window.closeSheet?.('wallVideoSheet');
        document.getElementById('feedVideoFiles')?.click();
    });
    document.getElementById('feedVideoFiles')?.addEventListener('change', (e) => {
        [...(e.target.files || [])].forEach(addClipFile);
        e.target.value = '';
        paintClips();
    });
    document.getElementById('feedVSrcGallery')?.addEventListener('click', () => {
        window.closeSheet?.('wallVideoSheet');
        if (typeof window.smPickMedia !== 'function') { toast('The gallery is not available here.', 'error'); return; }
        window.smPickMedia({
            allSchedules: true, kinds: 'video', title: 'A clip from my gallery',
            multiple: true,
            max: Math.max(1, MAX_CLIPS - clipTally()),
            onPick: (item) => { addClipPick(item); paintClips(); },
        });
    });

    /* ---------------- the two doors on the bar ----------------
       Both are sheets, so both come up from the bottom over the wall rather
       than pushing it down the page. */
    document.getElementById('wallWriteBtn')?.addEventListener('click', () => {
        window.openSheet?.('wallComposerSheet');
        window.smFocus?.(document.getElementById('feedPostBody'), { delay: 140 });
    });
    document.getElementById('wallSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('wallSearchSheet');
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
                // Typed words go in as text, never as markup.
                findNote.querySelector('b').textContent = '\u201c' + query + '\u201d';
            }
        }
        if (filterChip) {
            filterChip.classList.toggle('hidden', !query);
            if (query) filterChip.querySelector('b').textContent = '\u201c' + query + '\u201d';
        }
    }

    async function searchWall(q) {
        const wrap = document.getElementById('feedWrap');
        if (!wrap) return;
        query = q;
        loading = true;
        try {
            // No cursor: this is the top of the answer, not a continuation.
            const res = await fetch(feedUrl(''), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error('search failed');
            const d = data.data || {};
            wrap.innerHTML = d.html || '';
            const count = wrap.querySelectorAll('.feed-post').length;
            if (!count) {
                wrap.innerHTML = '<div class="card p-8 text-center" id="wallNone">'
                    + '<div class="empty-tile">\uD83D\uDD0E</div>'
                    + '<p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>'
                    + '<p class="text-sm text-gray-500 mt-1">No post here says that \u2014 in the words or in who wrote them.</p></div>';
            }
            done = !(d.hasMore && d.before);
            if (spin) spin.hidden = true;
            if (endNote) endNote.hidden = true;
            if (moreBtn) {
                moreBtn.setAttribute('data-before', d.before || '');
                moreBtn.hidden = done;
                moreBtn.disabled = false;
                moreBtn.textContent = 'Load more posts';
            }
            sayFound(count, !!d.hasMore);
        } catch (_) {
            toast('Could not search just now.', 'error');
        } finally {
            loading = false;
            setTimeout(nearTail, 0);
        }
    }
    if (findEl) window.plazaLiveSearch?.(findEl, searchWall);

    document.getElementById('feedPostSubmit')?.addEventListener('click', async (e) => {
        const host = document.getElementById('feedComposer');
        const body = document.getElementById('feedPostBody').value.trim();
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!body && !shots.length && !clips.length && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const fd = new FormData();
        if (body) fd.append('body', body);
        // Files go up; a picture the app already keeps travels as its path.
        shots.forEach((s) => {
            if (s.file) fd.append('images[]', s.file);
            else if (s.path) fd.append('galleryPaths[]', s.path);
        });
        if (vid) fd.append('video', vid);
        // The clip list splits the way a comment's does: files up, picks by path.
        clips.forEach((c) => {
            if (c.file) fd.append('videos[]', c.file);
            else if (c.path) fd.append('galleryVideoPaths[]', c.path);
        });
        fd.append('render', 'feed'); // return a feed-post card to prepend
        const btn = e.currentTarget;
        const prev = btn.textContent;
        btn.disabled = true;
        btn.textContent = (vid || shots.length > 2) ? 'Uploading…' : 'Posting…';
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
            shots.forEach((s) => { if (s.file) { try { URL.revokeObjectURL(s.url); } catch (_) {} } });
            shots.length = 0;
            paintShots();
            if (window.plazaClearVideo) window.plazaClearVideo(host);
            clearClips();
            window.closeSheet?.('wallComposerSheet');
            toast('Shared sa wall mo! 🌾');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.textContent = prev; }
    });
});
</script>
@endpush
