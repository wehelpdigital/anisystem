@extends('layouts.app')

@section('title', $group->name . ' — Discussions')
@section('page-title', $group->name)
@section('page-subtitle', 'Discussion')
@section('back', route('community.groups.index'))
{{-- A room is a place you are in; the bar underneath is for leaving it. The
     Collab Room claims the screen the same way. --}}
@section('body-class', 'plaza-ground hide-tabbar')

@php use App\Support\CommunityAvatar; @endphp

@push('head')
@include('community.partials.plaza-css')
<style>
    /* The topic box: the wall's field, in this page's words. */
    .disc-composer-box { min-height:5.5rem; resize:vertical; }
    .disc-composer-head { margin-bottom:.6rem; }
    .disc-composer-title { font-family:var(--font-heading); font-weight:800; font-size:.98rem;
        line-height:1.2; color:var(--color-gray-900); }
    .disc-composer-sub { margin-top:.15rem; font-size:.76rem; line-height:1.45; color:var(--color-gray-500); }

    /* The facts line's look lives in plaza-css — the wall draws it too. */
    /* Formatting shows through on rendered topic bodies. */
    .group-post .activity-description-content, .group-post-body { font-size:.875rem; }
    .group-post-body ul { list-style:disc; padding-left:1.25rem; }
    .group-post-body ol { list-style:decimal; padding-left:1.25rem; }
    .group-post-body a { color:var(--color-brand-700); text-decoration:underline; }
    .group-post-body h3, .group-post-body h4 { font-weight:700; margin:.25rem 0; }
    .group-post-body blockquote { border-left:3px solid var(--color-gray-200); padding-left:.75rem; color:var(--color-gray-500); }
    /* A pasted URL or a long crop name must not push the room sideways on a
       360px screen — nothing in a topic body may set the page's width. */
    .group-post-body, .group-post-body * { overflow-wrap:anywhere; }
    .group-post-body img, .group-post-body iframe, .group-post-body table { max-width:100%; }
    .group-post-body pre { overflow-x:auto; }

    /* --- The room's head: a place banner, not a wall of text. The name, who
       is in it, and the one thing you can do about that, in one row. --- */
    /* The room opens on its own cover.
     *
     * With the section bar gone the page's opening padding was the only
     * thing above the picture — a band of ground between the app bar and the
     * room, which is the same hole the bar itself used to leave. The room
     * takes that padding back, so the cover meets the app bar.
     *
       The pull is the page's padding less the banner's own overhang — the
       cover is dragged up out of the card it sits in, and taking the whole
       padding as well tucked the top of the picture behind the app bar. */
    #groupRoot { margin-top:-1rem; }
    @media (min-width:768px) { #groupRoot { margin-top:-2rem; } }
    .disc-hero { padding:.85rem; margin-bottom:.75rem; border-radius:1.1rem; }
    /* The head of the room. Cover, then the face half on it, then the name
       and the numbers across the whole card. */
    /* overflow:visible, so the face can stand on the cover's edge; the
       picture keeps the corners the clip used to give it. */
    .disc-hero.has-banner .disc-banner { position:relative; overflow:visible; }
    .disc-hero.has-banner .disc-banner img { border-radius:1.1rem 1.1rem 0 0; }
    .disc-face { position:absolute; left:1rem; bottom:-1.6rem; z-index:1;
        display:flex; align-items:center; justify-content:center;
        width:4.5rem; height:4.5rem; border-radius:1.15rem; overflow:hidden;
        border:3px solid var(--color-white); background:var(--color-white);
        font-family:var(--font-heading); font-weight:800; font-size:1.3rem; color:#fff;
        box-shadow:0 10px 24px -14px rgb(0 0 0 / .8); }
    .disc-face img { width:100%; height:100%; object-fit:cover; }
    .disc-hero.has-banner .disc-hero-row { padding-top:2.1rem; }
    /* No cover: the face keeps its place in the row instead of hanging off
       an edge that is not there. */
    .disc-hero:not(.has-banner) .disc-face { position:static; width:3.5rem; height:3.5rem;
        box-shadow:none; border-width:0; margin-bottom:.5rem; }
    /* The room is the screen on a phone, like the wall's posts. */
    .disc-hero { position:relative; }
    /* The house colours across the top of the room, in the room's own weight:
       a hairline read as an accident on a card this size, and the band now
       stands where the empty strip used to be. */
    .disc-hero::before { content:''; position:absolute; inset:0 0 auto 0; height:.75rem; z-index:2;
        border-top-left-radius:inherit; border-top-right-radius:inherit; pointer-events:none;
        background:linear-gradient(120deg, #2f5219, #6b9f3d 28%, #b8d38e 48%, #4a7c2a 72%, #2f5219);
        background-size:220% 100%; animation:gradSweep 12s ease-in-out infinite alternate; }
    @media (max-width:640px) {
        /* The composer used to be on the page and bled to both edges with the
           hero. It lives in a sheet now, which owns its own edges — the
           negative margins would have hung it outside them. */
        .disc-hero {
            border-radius:0; border-left:0; border-right:0;
            margin-left:calc(var(--plaza-gutter, 1rem) * -1);
            margin-right:calc(var(--plaza-gutter, 1rem) * -1);
        }
    }

    /* Two quiet doors under the cover, on the right. */
    .disc-hero-tools { position:absolute; right:.85rem; display:flex; align-items:center; gap:.4rem; z-index:3; }
    .disc-hero.has-banner .disc-hero-tools { top:calc(7rem + .5rem); }
    .disc-hero:not(.has-banner) .disc-hero-tools { top:.85rem; }
    @media (min-width:640px) { .disc-hero.has-banner .disc-hero-tools { top:calc(9.5rem + .5rem); } }
    .disc-tool { display:inline-flex; align-items:center; justify-content:center;
        width:2.1rem; height:2.1rem; border-radius:999px; cursor:pointer;
        border:1px solid var(--color-gray-200); background:var(--color-white);
        color:var(--color-gray-500); box-shadow:0 4px 12px -8px rgb(0 0 0 / .6);
        transition:background var(--dur) var(--ease-house), color var(--dur) var(--ease-house); }
    .disc-tool svg { width:1rem; height:1rem; }
    .disc-tool:hover { background:var(--color-gray-100); color:var(--color-gray-800); }
    .disc-tool-leave:hover { color:#dc2626; border-color:#fca5a5; background:#fef2f2; }
    .disc-hero-join { display:block; width:100%; margin-top:.7rem; }
    @media (prefers-reduced-motion: reduce) {
        .disc-hero::before { animation:none; }
        .disc-tool { transition:none; }
    }
    .disc-hero-num { display:inline-flex; align-items:center; gap:.25rem; }
    /* Being in the room is worth seeing, but it is a fact about you rather
       than a button: green words in a green outline, not a filled pill. */
    .disc-kasali { display:inline-flex; align-items:center; gap:.2rem;
        padding:.08rem .45rem; border-radius:999px; font-size:.68rem; font-weight:800;
        color:var(--color-brand-700); background:transparent;
        border:1px solid var(--color-brand-300); }
    .disc-started { display:flex; align-items:center; gap:.4rem; margin-top:.45rem;
        font-size:.75rem; color:var(--color-gray-500); }
    .disc-started .avatar { width:1.3rem; height:1.3rem; font-size:.55rem; }
    .disc-started a { font-weight:700; color:var(--color-gray-700); text-decoration:none; }
    .disc-started a:hover { color:var(--color-brand-700); }
    .eg-pics { display:flex; gap:.6rem; align-items:flex-start; }
    .eg-pic { display:block; cursor:pointer; }
    .eg-pic-wide { flex:1; min-width:0; }
    .eg-pic-lbl { display:block; font-size:.72rem; font-weight:700; color:var(--color-gray-500); margin-bottom:.25rem; }
    .eg-pic-box { display:flex; align-items:center; justify-content:center; overflow:hidden;
        width:4.5rem; height:4.5rem; border-radius:.75rem; background:var(--color-gray-100);
        border:1px dashed var(--color-gray-300); color:var(--color-gray-400); font-size:.75rem; }
    .eg-pic-box img { width:100%; height:100%; object-fit:cover; }
    .eg-pic-box i { font-style:normal; font-weight:800; }
    .eg-pic-banner { width:100%; height:4.5rem; }
    /* The banner bleeds to the card's edge and takes the top two corners with
       it; the padding below is the card's own again. */
    .disc-hero.has-banner { padding-top:0; }
    /* The card has no top padding when it carries a banner, so the banner has
       nothing to climb out of — and the negative margin was hoisting the
       picture clean off the top of its own card, which is the blank strip
       that kept appearing above it. It bleeds sideways only. */
    .disc-hero.has-banner .disc-banner { margin-top:0; }
    .disc-banner { margin:-.85rem -.85rem .75rem; height:7rem; overflow:hidden;
        border-top-left-radius:1.1rem; border-top-right-radius:1.1rem; background:var(--color-gray-100); }
    .disc-banner img { width:100%; height:100%; object-fit:cover; display:block; }
    @media (min-width:640px) {
        .disc-banner { margin:-1.15rem -1.15rem 1rem; height:9.5rem; }
    }
    .disc-hero-row { display:block; }
    .disc-hero-title { font-family:var(--font-heading); font-size:1.05rem; font-weight:800; line-height:1.25;
        color:var(--color-gray-900); overflow-wrap:anywhere; }
    .disc-hero-meta { display:flex; flex-wrap:wrap; gap:.1rem .7rem; margin-top:.25rem;
        font-size:.72rem; font-weight:700; color:var(--color-gray-500); }
    .disc-hero-desc { margin-top:.55rem; font-size:.82rem; line-height:1.45; color:var(--color-gray-600);
        overflow-wrap:anywhere; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .disc-hero-desc.is-open { display:block; }
    .disc-hero-more { display:inline-block; margin-top:.3rem; border:0; background:transparent; padding:0;
        font-size:.72rem; font-weight:800; color:var(--color-brand-700); cursor:pointer; }
    .disc-hero-more[hidden] { display:none; }
    @media (min-width:640px) {
        .disc-hero { padding:1.15rem; margin-bottom:1rem; }
        .disc-hero-title { font-size:1.3rem; }
        .disc-hero-desc { font-size:.9rem; -webkit-line-clamp:3; }
    }

    /* --- The room's two views, behind one door.
       A segmented switch said "this screen is special". Every other module in
       the app changes view through the same hamburger with the current view
       written beside it, and a discussion is a module. --- */
    .disc-viewbar { display:flex; align-items:center; gap:.6rem; margin-bottom:.85rem; }
    .disc-viewbar .rv-chev { transition:transform var(--dur) var(--ease-house); }
    .disc-viewbar.is-open .rv-chev { transform:rotate(180deg); }
    .rv-hint { font-size:.72rem; font-weight:600; color:var(--color-gray-400); margin-left:auto; }
    .rv-act { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0; }
    /* The count goes first when the row gets tight — it is the least of the
       three. The buttons keep their words at every width: a tooltip is not
       something a phone can show, so an icon alone is a guess. */
    @media (max-width:599px) { .rv-hint { display:none; } }
    .rv-filter { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0;
        max-width:11rem; padding:.25rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:800;
        background:var(--color-brand-50); color:var(--color-brand-700);
        border:1px solid var(--color-brand-200); }
    .rv-filter b { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .rv-filter.hidden { display:none; }
    html.dark .rv-filter { background:rgb(61 104 35 / .25); border-color:#3f5626; color:#bfe19a; }

    /* --- Group chat, full screen.
       A conversation is the whole job while you are in it, so it takes the
       screen: the room's head, the topics, the footer and the app bar all
       step aside, the page underneath stops scrolling, and one ✕ brings it
       all back. --- */
    html.room-chat-open, html.room-chat-open body { overflow:hidden; }
    body.room-chat-open footer,
    body.room-chat-open .plaza-nav,
    body.room-chat-open .disc-hero,
    body.room-chat-open .disc-viewbar { display:none; }
    body.room-chat-open #paneChat {
        position:fixed; inset:0; z-index:70; margin:0;
        background:var(--color-white); display:flex; flex-direction:column;
        animation:chatFullIn .28s cubic-bezier(.22,1,.36,1); }
    body.room-chat-open #paneChat .chat-shell { flex:1 1 auto; min-height:0; gap:0; height:100%; }
    body.room-chat-open #paneChat .chat-card,
    body.room-chat-open #paneChat aside.chat-card {
        height:100%; max-height:none; border-radius:0; border-left:0; border-right:0;
        border-top:0; box-shadow:none; }
    @media (min-width:1024px) {
        /* The members list keeps its column; it is the one thing on this
           screen that answers "who is even here". */
        body.room-chat-open #paneChat aside.chat-card { border-right:0; border-left:1px solid var(--color-gray-200); }
    }
    /* The overlay's own head: the room's name and the way back. Drawn only
       when there is something to come back from. */
    .chat-full-bar { display:none; align-items:center; gap:.6rem; flex:none;
        padding:.55rem .75rem; border-bottom:1px solid var(--color-gray-200);
        background:var(--color-white); }
    body.room-chat-open .chat-full-bar { display:flex; }
    .chat-full-x { display:inline-flex; align-items:center; justify-content:center; width:2.15rem; height:2.15rem;
        border:0; border-radius:999px; background:transparent; color:var(--color-gray-500); cursor:pointer; flex:none;
        transition:background-color var(--dur) var(--ease-house), color var(--dur) var(--ease-house); }
    .chat-full-x:hover { background:var(--color-gray-100); color:var(--color-gray-900); }
    .chat-full-name { font-family:var(--font-heading); font-weight:800; font-size:.95rem;
        color:var(--color-gray-900); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .chat-full-sub { margin-left:auto; flex:none; font-size:.72rem; font-weight:700; color:var(--color-gray-400); }
    /* In full screen the bar says what this is, so the card's own head — a
       title nobody needs twice and one button — goes, and the button moves up
       into the bar (see showView). */
    body.room-chat-open .chat-head { display:none; }
    .chat-full-bar .btn { flex:none; }
    @keyframes chatFullIn { from { opacity:0; transform:translateY(1.25%); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion:reduce) { body.room-chat-open #paneChat { animation:none; } }

    /* Composer: on a phone the editor gets the whole width — the avatar was
       spending 3rem of a 360px screen to say who is typing. */
    /* No padding of its own inside a sheet: the sheet body has it already. */
    .disc-composer { padding:0; }
    @media (max-width:479px) {
        .disc-composer .disc-composer-av { display:none; }
    }


    /* Topics: the replies sit below a divider, and nothing is glued to it. */
    .post-thread { margin-top:.9rem; padding-top:.85rem; border-top:1px solid var(--color-gray-100); }
    html.dark .post-thread { border-top-color:rgb(255 255 255 / .08); }
    .post-thread-more { display:inline-block; margin:.15rem 0 .55rem; border:0; background:transparent; padding:0;
        font-size:.75rem; font-weight:800; color:var(--color-brand-700); cursor:pointer; }

    /* A long body is folded to about six lines with a way in; the whole thing
       is read in the thread modal rather than swallowing the room. */
    .group-post-body.is-clamped { display:-webkit-box; -webkit-line-clamp:6; -webkit-box-orient:vertical;
        overflow:hidden; }
    .post-readmore { display:inline-block; margin-top:.3rem; border:0; background:transparent; padding:0;
        font-size:.75rem; font-weight:800; color:var(--color-brand-700); cursor:pointer; }

    /* ---- The thread modal ---- */
    .thread-modal { position:fixed; inset:0; z-index:60; display:flex; align-items:flex-end; justify-content:center; }
    .thread-modal.hidden { display:none; }
    .thread-modal-back { position:absolute; inset:0; background:rgb(17 24 39 / .5); backdrop-filter:blur(2px);
        animation:thmFade .28s cubic-bezier(.22,1,.36,1); }
    .thread-modal-card { position:relative; display:flex; flex-direction:column; width:100%; max-width:42rem;
        max-height:92dvh; background:var(--color-white); border-radius:1.1rem 1.1rem 0 0;
        box-shadow:0 -8px 40px rgb(0 0 0 / .22); animation:thmUp .28s cubic-bezier(.22,1,.36,1); }
    @keyframes thmFade { from { opacity:0; } }
    @keyframes thmUp { from { opacity:0; transform:translateY(14px); } }
    .thread-modal.is-closing .thread-modal-card { animation:thmDown .2s cubic-bezier(.22,1,.36,1) forwards; }
    .thread-modal.is-closing .thread-modal-back { animation:thmFadeOut .2s cubic-bezier(.22,1,.36,1) forwards; }
    @keyframes thmDown { to { opacity:0; transform:translateY(14px); } }
    @keyframes thmFadeOut { to { opacity:0; } }
    @media (prefers-reduced-motion:reduce) {
        .thread-modal-card, .thread-modal-back, .thread-modal.is-closing .thread-modal-card,
        .thread-modal.is-closing .thread-modal-back { animation:none; }
    }
    .thread-modal-head { display:flex; align-items:center; gap:.5rem; padding:.85rem 1rem .6rem;
        border-bottom:1px solid var(--color-gray-100); }
    .thread-modal-title { font-family:var(--font-heading); font-weight:800; font-size:.95rem;
        color:var(--color-gray-900); flex:1 1 auto; }
    .thread-modal-x { border:0; background:transparent; color:var(--color-gray-400); font-size:1rem;
        cursor:pointer; padding:.2rem .3rem; }
    /* The scrolling part, and the only one — the head stays put. */
    .thread-modal-body { flex:1 1 auto; min-height:0; overflow-y:auto; padding:.85rem 1rem 1.1rem;
        -webkit-overflow-scrolling:touch; }
    /* Inside the modal the post is the page: no card chrome, nothing folded. */
    .thread-modal-body .group-post { border:0; box-shadow:none; padding:1.4rem 0 0; margin:0; background:transparent; }
    .thread-modal-body .group-post::before { display:none; }   /* the edge belongs to the card, not the sheet */

    /* The answers and the box to write one belong to the thread, and the
       thread is the modal. In the room the topic is a topic: what was
       said, and how many have answered it. */
    .group-post .post-thread, .group-post .post-reply-form { display:none; }
    .thread-modal-body .group-post .post-thread { display:block; }
    .thread-modal-body .group-post .post-reply-form { display:flex; }
    .thread-modal-body .topic-acts { display:none; }

    /* The row under a topic: the same shape the wall's posts carry. */
    .topic-acts { display:flex; align-items:center; gap:.15rem; margin-top:.5rem;
        padding-top:.5rem; border-top:1px solid var(--color-gray-100); }
    .topic-act { display:inline-flex; align-items:center; gap:.35rem;
        border:0; background:transparent; cursor:pointer; padding:.4rem .55rem;
        border-radius:.6rem; font-size:.78rem; font-weight:700; color:var(--color-gray-500);
        transition:background var(--dur) var(--ease-house), color var(--dur) var(--ease-house); }
    .topic-act:hover { background:var(--color-gray-100); color:var(--color-brand-700); }
    .topic-act svg { width:1.05rem; height:1.05rem; }
    .topic-acts .v-eye { margin-left:auto; font-size:.75rem; font-weight:700; color:var(--color-gray-400); padding-right:.35rem; }
    @media (prefers-reduced-motion: reduce) { .topic-act { transition:none; } }

    /* On a phone the room is the screen, the same way the wall is. */
    @media (max-width: 640px) {
        .group-post {
            border-radius:0; border-left:0; border-right:0;
            margin-left:calc(var(--plaza-gutter, 1rem) * -1);
            margin-right:calc(var(--plaza-gutter, 1rem) * -1);
        }
        .thread-modal-body .group-post { margin-left:0; margin-right:0; }
    }
    .thread-modal-body .post-replies.is-collapsed > .group-reply { display:block; }
    .thread-modal-body .post-thread-more, .thread-modal-body .post-readmore { display:none; }
    .thread-modal-body .group-post-body.is-clamped { -webkit-line-clamp:unset; display:block; overflow:visible; }
    html.dark .thread-modal-head { border-bottom-color:rgb(255 255 255 / .08); }
    @media (min-width:640px) {
        .thread-modal { align-items:center; padding:1.5rem; }
        .thread-modal-card { border-radius:1.1rem; max-height:86dvh; }
    }
    .post-thread .replies-label { margin-bottom:.45rem; }
    /* The conversation arriving, a beat apart. */
    .thm-in { animation:thmPart .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes thmPart { from { opacity:0; transform:translateY(8px); } }
    @media (prefers-reduced-motion:reduce) { .thm-in { animation:none; } }

    /* .reply-lead itself is styled in plaza-css — the wall's comment sheet
       draws it too, and borrowed the markup without the rules. */
    .group-post .reply-lead { display:none; }
    .thread-modal-body .group-post .reply-lead { display:flex; }
    @media (max-width:479px) {
        .group-post .react-bar { gap:.3rem; }
        /* One level of nesting is all a phone can indent and still read. */
        .reply-thread { margin-left:1.35rem; }
    }

    /* --- Group chat: dvh so a phone's collapsing URL bar doesn't swallow the
       composer; vh stays as the fallback for browsers without it. --- */
    .chat-card { height:70vh; height:70dvh; }
    #paneChat { scroll-margin-top:4rem; }
    @media (max-width:1023px) {
        /* The card is parked under the sticky app bar when the tab opens, so
           this height leaves the composer sitting just above the tab bar
           instead of behind it. */
        .chat-card { height:calc(100vh - 9rem); height:calc(100dvh - 9rem); min-height:min(20rem, 60dvh); }
    }
    .chat-send { min-height:2.4rem; }

    /* The tail of the topics: a button, a loader, or the end of the road —
       never two of them at once (the wall's shape, in this room's words). */
    .disc-tail { text-align:center; margin-top:.25rem; padding-bottom:.5rem; }
    .disc-spin { display:flex; align-items:center; justify-content:center; gap:.35rem; padding:.9rem 0; }
    .disc-spin i { display:block; width:.45rem; height:.45rem; border-radius:9999px;
        background:var(--color-brand-400); animation:discDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .disc-spin i:nth-child(2) { animation-delay:.12s; }
    .disc-spin i:nth-child(3) { animation-delay:.24s; }
    @keyframes discDot { 0%,100% { opacity:.25; transform:translateY(0); } 50% { opacity:1; transform:translateY(-.25rem); } }
    .disc-end { font-size:.78rem; font-weight:600; color:var(--color-gray-400); padding:1rem 0 .4rem; }
    .disc-spin[hidden], .disc-end[hidden] { display:none; }

    @media (prefers-reduced-motion: reduce) {
        .disc-viewbar .rv-chev, .chat-full-x { transition:none; }
        /* A loader that stops looks like a page that broke; slow it instead. */
        .disc-spin i { animation-duration:2.6s; }
    }
</style>
@endpush

@section('content')
{{-- No section bar in a room.
     Discussions / Saved / Chat are the ways OUT of the community's rooms,
     and inside one they read as a second set of tabs belonging to the room
     itself — which is what was confusing. The back arrow in the app bar is
     the way out of here; the bar is waiting on the page it goes back to. --}}
<div data-group-member="{{ $isMember ? 1 : 0 }}" id="groupRoot" data-group-id="{{ $group->id }}"
     data-view="group:{{ $group->id }}">

    {{-- Group header: the place banner.

         The cover is the room's face — a discussion with one looks like
         somewhere rather than like a row in a list — and it sits OUTSIDE the
         padded body so it can run to the card's own rounded edge. --}}
    <div class="card disc-hero group-hero {{ CommunityAvatar::hue($group->name) }}{{ $group->bannerImagePath ? ' has-banner' : '' }}">
        @if ($group->bannerImagePath)
            <div class="disc-banner">
                <img src="{{ \App\Support\MediaStore::url($group->bannerImagePath) }}" alt="" loading="lazy">
                <span class="disc-face {{ CommunityAvatar::hue($group->name) }}">
                    @if ($group->coverImagePath)<img src="{{ \App\Support\MediaStore::url($group->coverImagePath) }}" alt="">@else{{ CommunityAvatar::monogram($group->name) }}@endif
                </span>
            </div>
        @endif
        <div class="disc-hero-row">
            @unless ($group->bannerImagePath)
                <span class="disc-face {{ CommunityAvatar::hue($group->name) }}">
                    @if ($group->coverImagePath)<img src="{{ \App\Support\MediaStore::url($group->coverImagePath) }}" alt="">@else{{ CommunityAvatar::monogram($group->name) }}@endif
                </span>
            @endunless
            <h2 class="disc-hero-title">{{ $group->name }}</h2>
            <p class="disc-hero-meta">
                <span class="disc-hero-num">🧑‍🌾 <span id="memberCount">{{ $memberCount }}</span> {{ \Illuminate\Support\Str::plural('member', $memberCount) }}</span>
                <span class="disc-hero-num">💬 {{ $topicCount }} {{ \Illuminate\Support\Str::plural('topic', $topicCount) }}</span>
                {{-- How many have looked in. Counted on arrival, like every
                     other thing in the community that carries an eye. --}}
                <span class="disc-hero-num">@include('community.partials.views-count', ['kind' => 'group', 'id' => $group->id, 'count' => $group->viewCount ?? 0])</span>
                <span id="heroMemberTag" class="disc-kasali {{ $isMember ? '' : 'hidden' }}">✓ Kasali ka</span>
            </p>
        </div>
        {{-- The two small doors, under the cover on the right: change the
             room, or leave it. Both are quiet — the room is the page, and
             neither of these is what a reader came for. --}}
        <div class="disc-hero-tools">
            @if ($canEditGroup ?? false)
                <button type="button" id="editGroupBtn" class="disc-tool" title="Edit this discussion" aria-label="Edit this discussion">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 15l-4 1 1-4 8.6-8.4z"/></svg>
                </button>
            @endif
            @if ($isMember && ! $isOwner)
                <button type="button" id="joinLeaveBtn" class="disc-tool disc-tool-leave"
                        data-joined="1" data-name="{{ $group->name }}"
                        title="Leave this discussion" aria-label="Leave this discussion">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif
            @if ($isOwner)
                <span class="badge badge-green">Owner</span>
            @endif
        </div>
        @unless ($isMember || $isOwner)
            {{-- For somebody outside the room this is the whole page's offer,
                 so it keeps its words and its width. --}}
            <button type="button" id="joinLeaveBtn" class="btn btn-primary btn-sm disc-hero-join"
                    data-joined="0" data-name="{{ $group->name }}">Join this discussion</button>
        @endunless
        {{-- Whose room this is. A reader deciding whether to trust what is
             said in here wants to know who keeps it. --}}
        @if ($group->creator)
            <p class="disc-started">
                @include('community.partials.avatar', ['user' => $group->creator, 'size' => 'avatar-sm', 'link' => false])
                <span>Started by <a href="{{ route('community.connect.profile', ['userId' => $group->creator->id]) }}">{{ $group->creator->full_name }}</a>@if ($group->created_at) · {{ $group->created_at->timezone('Asia/Manila')->format('M Y') }}@endif</span>
            </p>
        @endif
        @if ($group->description)
            {{-- Clamped by default: the description is context, not the room. --}}
            <p class="disc-hero-desc" id="heroDesc">{{ $group->description }}</p>
            <button type="button" class="disc-hero-more" id="heroDescMore" hidden>Show more</button>
        @endif
    </div>

    @if ($canEditGroup ?? false)
    {{-- Renaming the room and changing its two pictures. Kept in the room
         rather than on a settings page: this is where you notice the name is
         wrong. Either picture left empty keeps the one it had. --}}
    <div class="sheet hidden" id="editGroupSheet" style="--sheet-width:28rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Edit this discussion</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body space-y-3">
            <div>
                <label class="form-label" for="egName">Name</label>
                <input type="text" id="egName" class="form-input" maxlength="150" value="{{ $group->name }}">
            </div>
            <div>
                <label class="form-label" for="egDesc">What is this discussion about?</label>
                <textarea id="egDesc" class="form-textarea" rows="3" maxlength="500">{{ $group->description }}</textarea>
            </div>
            <div class="eg-pics">
                <label class="eg-pic">
                    <span class="eg-pic-lbl">Badge</span>
                    <span class="eg-pic-box" id="egCoverBox">
                        @if ($group->coverImagePath)
                            <img src="{{ \App\Support\MediaStore::url($group->coverImagePath) }}" alt="">
                        @else
                            <i>{{ CommunityAvatar::monogram($group->name) }}</i>
                        @endif
                    </span>
                    <input type="file" id="egCover" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
                <label class="eg-pic eg-pic-wide">
                    <span class="eg-pic-lbl">Cover photo</span>
                    <span class="eg-pic-box eg-pic-banner" id="egBannerBox">
                        @if ($group->bannerImagePath)
                            <img src="{{ \App\Support\MediaStore::url($group->bannerImagePath) }}" alt="">
                        @else
                            <i>Add a cover</i>
                        @endif
                    </span>
                    <input type="file" id="egBanner" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
            </div>
            <button type="button" id="egSave" class="btn btn-primary w-full">Save changes</button>
        </div>
    </div>
    @endif

    {{-- The room's two views, opened the way every module is opened. --}}
    <div class="disc-viewbar" id="roomViewBar">
        <button type="button" id="roomViewBtn" class="btn btn-white btn-sm" aria-haspopup="dialog" aria-expanded="false" title="Switch view">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span id="roomViewLabel">Topics</span>
            <svg class="w-3.5 h-3.5 rv-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
        @if ($isMember)
        <button type="button" id="startTopicBtn" class="btn btn-outline btn-sm rv-act" title="Start a topic">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            <span class="rv-act-lbl">New topic</span>
        </button>
        @endif
        <button type="button" id="topicSearchBtn" class="btn btn-outline btn-sm rv-act" title="Search this discussion">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <span class="rv-act-lbl">Search</span>
        </button>
        {{-- A filter is a thing that is ON; the bar says so, and says it in
             the one place that stays on screen while the sheet is closed. --}}
        <button type="button" class="rv-filter hidden" id="topicFilterChip" title="Clear the search">
            <b></b>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <span class="rv-hint" id="roomViewHint">{{ $topicCount }} {{ \Illuminate\Support\Str::plural('topic', $topicCount) }}</span>
    </div>

    <div id="paneDiscussion" role="tabpanel">
    {{-- Starting a topic is a thing you do, not a box you scroll past.
         It opens from the bottom over the room you were reading, the way
         every other errand in this app opens — and the room itself is the
         topics, which is what the page should be showing. --}}
    @if ($isMember)
    <div class="sheet hidden" id="topicComposerSheet" style="--sheet-width:36rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Start a topic</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body" style="padding-bottom:1.1rem">
            <div class="disc-composer" id="composerCard" data-video-host>
                {{-- What the box is for: an unlabelled field asks for nothing,
                     and the sheet's own title says only where you are. --}}
                <p class="disc-composer-sub">Ask a question or share what worked. Everyone in this discussion gets told about it — use @ to tag a co-farmer.</p>
            <div class="flex items-start gap-3">
                <span class="avatar avatar-md disc-composer-av {{ CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} mt-1">{{ auth()->user()->initials ?? '?' }}</span>
                <div class="min-w-0 grow">
                    <input type="text" id="postTitle" class="form-input mb-2" maxlength="191" required placeholder="Topic title *">
                    {{-- Plain words, like the wall's box. data-mentionable is what
                         gives it @names — the rich editor could not have them at
                         all, because the mention script binds to fields. --}}
                    <textarea id="postBody" class="form-textarea disc-composer-box" rows="4" maxlength="4000"
                              data-mentionable placeholder="Magtanong o magbahagi sa usapan…"></textarea>
                    <div id="attachChipWrap" class="mt-2 hidden">
                <span class="attach-chip">
                    <img src="" alt="" id="attachThumb">
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-gray-700 truncate" id="attachName"></span>
                        <span class="text-[0.625rem] font-bold text-gray-400" id="postImageLabel">Photo</span>
                    </span>
                    <button type="button" id="attachRemove" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 shrink-0" aria-label="Remove attachment">✕</button>
                </span>
            </div>
            <div class="flex items-center justify-between gap-2 mt-2">
                <div class="flex items-center gap-1">
                    <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="file" id="postImage" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </label>
                    {{-- Upload a clip, or film one on the spot: the same pair the
                         wall composer carries, driven by the same shared script. --}}
                    <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
                    </button>
                    <input type="file" class="js-video-file hidden" accept="video/*">
                    <span class="js-video-chip items-center gap-2 text-xs font-semibold text-gray-600" style="display:none">
                        <span class="js-video-name"></span>
                        <button type="button" class="js-video-clear text-red-600 font-bold">Remove</button>
                    </span>
                    <label class="hidden">
                    </label>
                    <button type="button" class="wall-act js-emoji-btn" data-target="postBody" aria-label="Add an emoji" title="Emoji">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                </div>
                <button type="button" id="postSubmit" class="btn btn-primary btn-sm">Post</button>
                </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    @endif

    @unless ($isMember)
        <div class="card p-5 mb-4 text-center" id="joinPrompt">
            <div class="empty-tile" style="width:3.5rem;height:3.5rem;font-size:1.5rem;">🌾</div>
            <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Members lang muna dito</p>
            <p class="text-sm text-gray-600 mt-1 mb-3">Join {{ $group->name }} para maka-post at maka-reply.</p>
            <button type="button" class="btn btn-primary w-full" id="joinFromGate">Sali sa usapan</button>
        </div>
    @endunless

    {{-- Posts --}}
    {{-- The same field, behind the bar's magnifier: it filters the room
         as you type, and closing the sheet leaves the answer on screen.
         A room with nothing in it has nothing to search. --}}
    @if ($posts->isNotEmpty())
    <div class="sheet hidden" id="topicSearchSheet" style="--sheet-width:30rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Search this discussion</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body" style="padding-bottom:1.1rem">
            @include('community.partials.live-search', [
                'id' => 'topicFind',
                'placeholder' => 'Search topics…',
                'label' => 'Search this discussion',
            ])
            <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the topics</button>
        </div>
    </div>
    @endif

    <div id="postsWrap">
        @if ($posts->isEmpty())
            <div class="card p-8 text-center" id="postsEmpty">
                <div class="empty-tile">🌱</div>
                <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Tahimik pa rito</p>
                <p class="text-sm text-gray-500 mt-1">Ikaw ang mauna — share what's happening sa bukid mo.</p>
            </div>
        @else
            @include('community.groups.partials.posts', ['posts' => $posts, 'group' => $group])
        @endif
    </div>

    <div class="card p-8 text-center" id="topicNone" hidden>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">No topic here says that — in the question or in the answers under it.</p>
    </div>

    @if ($posts->isNotEmpty())
        {{-- The reader meets the next page already there; the button stays for
             a deliberate retry (and for anyone driving by keyboard). --}}
        <div class="disc-tail" id="postsTail">
            <button type="button" id="loadMoreBtn" class="btn btn-white btn-sm" data-next="2" data-infinite @unless ($hasMore) hidden @endunless>Show older topics</button>
            <div class="disc-spin" id="postsSpin" role="status" aria-label="Loading older topics" hidden><i></i><i></i><i></i></div>
            <p class="disc-end" id="postsEnd" @if ($hasMore) hidden @endif>🌾 Nasa dulo ka na — iyan ang buong usapan.</p>
        </div>
    @endif

    {{-- A room can run to thousands of pixels, and the two places anyone
         wants are its ends. The composer is already at the top of the page
         and one tap away up there, so the corner is better spent on the
         journey than on a second Write button. Drawn for a visitor too:
         reading a long room is not a member's privilege. --}}
    <div class="disc-jumps is-hidden" id="discJumps" aria-hidden="true">
        <button type="button" id="discJumpTop" aria-label="Jump to the top">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
        </button>
        <button type="button" id="discJumpBottom" aria-label="Jump to the bottom">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </div>
    </div>{{-- /paneDiscussion --}}

    {{-- ===================== GROUP CHAT PANE ===================== --}}
    <div id="paneChat" class="hidden" role="tabpanel">
        {{-- Full screen's own head: which room this is, and the way back to
             its topics. Drawn for a member and a visitor alike — the way out
             cannot live inside a card only members are given. --}}
        <div class="chat-full-bar">
            <button type="button" id="chatFullBack" class="chat-full-x" aria-label="Back to topics" title="Back to topics">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="chat-full-name">{{ $group->name }}</span>
            <span class="chat-full-sub">Group chat</span>
        </div>
        @if ($isMember)
            <div class="chat-shell">
            <div class="card overflow-hidden chat-card" style="display:flex;flex-direction:column;">
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 shrink-0 chat-head">
                    <span class="font-bold text-gray-900 text-sm chat-head-title">Group chat</span>
                    {{-- Mobile members toggle; desktop shows the persistent sidebar instead. --}}
                    <button type="button" id="chatMembersToggle" class="btn btn-white btn-sm ms-auto shrink-0 lg:hidden">
                        👥 <span id="chatOnlineCount">Members</span>
                    </button>
                </div>
                <div id="chatMembersPanel" class="hidden lg:hidden border-b border-gray-100 max-h-48 overflow-y-auto p-1" style="background:var(--color-gray-50)">
                    <p class="text-xs text-gray-400 text-center py-3">Loading…</p>
                </div>
                <div id="chatScroll" class="grow overflow-y-auto p-3" style="display:flex;flex-direction:column;gap:.5rem;background:var(--color-gray-50)">
                    <p class="text-xs text-gray-400 text-center py-4" id="chatLoading">Loading…</p>
                </div>
                <div class="border-t border-gray-100 p-2 shrink-0" data-video-host>
                    <div class="flex items-center gap-0.5 mb-1.5">
                        <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <input type="file" id="chatImage" accept="image/jpeg,image/png,image/webp" class="hidden">
                        </label>
                        <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
                        </button>
                        <input type="file" class="js-video-file hidden" accept="video/*">
                        <button type="button" class="wall-act js-emoji-btn" data-target="chatInput" title="Add an emoji" aria-label="Add an emoji">
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                        <span class="js-video-chip ms-auto items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" id="chatInput" data-mentionable class="form-input grow" maxlength="5000" placeholder="Message the group…">
                        <button type="button" id="chatSend" class="btn btn-primary btn-sm chat-send shrink-0">Send</button>
                    </div>
                    <div id="chatAttach" class="hidden mt-1.5 text-xs text-gray-500 flex items-center gap-2">
                        <img id="chatAttachThumb" alt="" style="width:2.2rem;height:2.2rem;object-fit:cover;border-radius:.4rem;">
                        <span id="chatAttachName" class="truncate"></span>
                        <button type="button" id="chatAttachX" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                </div>
            </div>
            {{-- Desktop members sidebar: who's in the group + online status + DM. --}}
            <aside class="card hidden lg:flex chat-card" style="flex-direction:column;">
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 shrink-0">
                    <span class="font-bold text-gray-900 text-sm">👥 Members</span>
                    <span id="chatOnlineCountSide" class="text-xs font-semibold text-gray-400 ms-auto"></span>
                </div>
                <div id="chatMembersSidebar" class="grow overflow-y-auto p-1.5 space-y-0.5" style="background:var(--color-gray-50)">
                    <p class="text-xs text-gray-400 text-center py-3">Loading…</p>
                </div>
            </aside>
            </div>{{-- /.chat-shell --}}
        @else
            <div class="card p-6 text-center">
                <div class="empty-tile" style="width:3.5rem;height:3.5rem;font-size:1.5rem;">💬</div>
                <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Members only</p>
                <p class="text-sm text-gray-600 mt-1 mb-3">Join {{ $group->name }} to chat with the group.</p>
                <button type="button" class="btn btn-primary w-full" id="joinFromChat">Sali sa usapan</button>
            </div>
        @endif
    </div>
</div>
    {{-- A long thread, read on its own.
         A topic with twenty answers pushes every other topic off the page, so
         the room keeps the last few inline and opens the rest here. The post
         itself moves in and back out again — it is not re-rendered — so
         reacting, replying and deleting behave exactly as they do in the
         room, because they are literally the same nodes. --}}
    <div class="thread-modal hidden" id="threadModal" role="dialog" aria-modal="true" aria-label="Thread">
        <div class="thread-modal-back" data-thread-close></div>
        <div class="thread-modal-card">
            <div class="thread-modal-head">
                <h3 class="thread-modal-title">Thread</h3>
                <button type="button" class="thread-modal-x" data-thread-close aria-label="Close">✕</button>
            </div>
            <div class="thread-modal-body" id="threadModalBody"></div>
        </div>
    </div>

@endsection

{{-- The room's views, in the same sheet every module's switcher opens: a row
     per view, the current one ticked. --}}
@push('sheets')
<div class="sheet hidden" id="roomViewSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">{{ $group->name }}</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        @foreach ([
            ['discussion', 'Topics', 'What the room is talking about.', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['chat', 'Group Chat', 'Everyone at once, full screen.', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ] as [$key, $label, $blurb, $icon])
            <button type="button" class="room-view-row w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50"
                    data-room-view="{{ $key }}">
                <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="grow min-w-0">
                    <span class="block">{{ $label }}</span>
                    <span class="block text-xs font-normal text-gray-400">{{ $blurb }}</span>
                </span>
                <svg class="w-4 h-4 text-brand-600 room-view-check hidden" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>
@endpush

@push('scripts')
{{-- Rooms are looked at too: the same counter the wall uses. --}}
@include('community.partials.views-js')
@include('community.partials.report-js')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.video-js')
@include('community.partials.chat-media-js')
@include('community.partials.infinite-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const root = document.getElementById('groupRoot');
    const groupId = root.getAttribute('data-group-id');
    const wrap = document.getElementById('postsWrap');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Reactions are handled by the shared community.partials.react-js include. */

    /* ---------------- the head: description is context, not the room ---------------- */
    const heroDesc = document.getElementById('heroDesc');
    const heroMore = document.getElementById('heroDescMore');
    if (heroDesc && heroMore) {
        // Only offer the toggle when there is something the clamp is hiding.
        if (heroDesc.scrollHeight - heroDesc.clientHeight > 4) heroMore.hidden = false;
        heroMore.addEventListener('click', () => {
            const open = heroDesc.classList.toggle('is-open');
            heroMore.textContent = open ? 'Show less' : 'Show more';
        });
    }

    /* ---------------- join / leave: everything swaps live ---------------- */
    async function setMembership(join) {
        const btn = document.getElementById('joinLeaveBtn');
        const res = await fetch(`/app/community/groups/${groupId}/${join ? 'join' : 'leave'}`, { method: 'POST', headers: jsonHeaders });
        const data = await res.json();
        if (!data.success) { toast(data.message, 'error'); return false; }
        toast(join ? 'Salamat sa pagsali! 🌾' : data.message);
        root.setAttribute('data-group-member', join ? '1' : '0');
        const count = document.getElementById('memberCount');
        const n = parseInt(count.textContent || '0', 10) + (join ? 1 : -1);
        count.textContent = String(Math.max(0, n));
        if (!reduceMotion) { count.classList.remove('tick'); void count.offsetWidth; count.classList.add('tick'); }
        document.getElementById('heroMemberTag')?.classList.toggle('hidden', !join);
        if (btn) {
            btn.textContent = join ? 'Leave this discussion' : 'Join this discussion';
            btn.classList.toggle('btn-primary', !join);
            btn.classList.toggle('btn-white', join);
            btn.setAttribute('data-joined', join ? '1' : '0');
        }
        const gate = document.getElementById('joinPrompt');
        const composer = document.getElementById('composerCard');
        if (join) {
            if (gate) {
                gate.style.maxHeight = gate.scrollHeight + 'px';
                requestAnimationFrame(() => gate.classList.add('gate-out'));
                gate.addEventListener('transitionend', () => gate.remove(), { once: true });
                if (reduceMotion) gate.remove();
            }
            composer.classList.remove('hidden');
            if (!reduceMotion) {
                composer.classList.add('is-entering');
                composer.addEventListener('animationend', () => composer.classList.remove('is-entering'), { once: true });
            }
        } else {
            composer.classList.add('hidden');
        }
        return true;
    }

    document.getElementById('joinLeaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const joined = btn.getAttribute('data-joined') === '1';
        /* Both directions are asked about.

           Leaving costs you the room: the topics, the chat, everything behind
           the gate. Joining puts your name in a room other people can see —
           the owner asked for that one to be a decision rather than a tap.

           `message` is the word confirmAction reads; this said `body`, and
           the sheet asked the question over an empty space. */
        const name = btn.getAttribute('data-name') || 'this discussion';
        const ok = window.confirmAction
            ? await window.confirmAction(joined ? {
                title: 'Leave ' + name + '?',
                message: 'You will stop seeing its topics and chat, and you will have to join again to come back.',
                confirmText: 'Leave',
            } : {
                title: 'Join ' + name + '?',
                message: 'You will be able to post and reply here, and the others in the room will see you as a member.',
                confirmText: 'Join',
                confirmClass: 'btn-primary',
            })
            : true;
        if (!ok) return;
        btn.disabled = true;
        try {
            const done = await setMembership(!joined);
            // Out means out: the room closes behind you rather than leaving a
            // reader looking at posts they no longer have.
            if (done !== false && joined) {
                toast('You left the discussion.');
                setTimeout(() => { window.location.href = @json(route('community.groups.index')); }, 400);
            }
        }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally {
            // It may have been replaced by the X on the way in.
            if (btn.isConnected) { btn.disabled = false; delete btn.dataset.busy; }
        }
    });
    document.getElementById('joinFromGate')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;   // null once this awaits
        btn.disabled = true;
        try { await setMembership(true); }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; }
    });
    // Joining from the chat tab's gate: the chat itself only arrives on a
    // reload, so say so rather than leaving a dead pane behind.
    document.getElementById('joinFromChat')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;   // null once this awaits
        btn.disabled = true;
        try { if (await setMembership(true)) window.location.reload(); }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; }
    });

    /* ---------------- composer: a plain box + photo chip ---------------- */
    const bodyBox = document.getElementById('postBody');
    const getBodyHtml = () => (bodyBox ? bodyBox.value : '');
    const getBodyText = () => (bodyBox ? bodyBox.value.trim() : '');
    const clearBody = () => { if (bodyBox) { bodyBox.value = ''; bodyBox.style.height = ''; } };

    /* Grows with what is written, up to a point — a four-row box that never
       changes makes a long question feel like it does not belong here. */
    bodyBox?.addEventListener('input', () => {
        bodyBox.style.height = 'auto';
        bodyBox.style.height = Math.min(bodyBox.scrollHeight, 320) + 'px';
    });

    const imgInput = document.getElementById('postImage');
    const chipWrap = document.getElementById('attachChipWrap');
    let gifPick = null;   // { url, preview } from the Giphy picker

    function showChip(src, name, isGif) {
        document.getElementById('attachThumb').src = src;
        document.getElementById('attachName').textContent = name;
        document.getElementById('postImageLabel').textContent = isGif ? 'GIF' : 'Photo';
        chipWrap.classList.remove('hidden');
    }
    function clearAttach() {
        imgInput.value = ''; gifPick = null;
        chipWrap.classList.add('hidden');
    }
    imgInput?.addEventListener('change', () => {
        if (!imgInput.files[0]) return;
        gifPick = null;
        showChip(URL.createObjectURL(imgInput.files[0]), imgInput.files[0].name, false);
    });
    document.getElementById('attachRemove')?.addEventListener('click', clearAttach);

    /* GIF picker removed — attachments are photo-only now. */

    document.getElementById('postSubmit')?.addEventListener('click', async (e) => {
        const postBtn = e.currentTarget;   // null once this awaits
        const titleVal = document.getElementById('postTitle').value.trim();
        if (!titleVal) { toast('Add a topic title.', 'error'); document.getElementById('postTitle').focus(); return; }
        const text = getBodyText();
        if (!text) { toast('Write something first.', 'error'); return; }
        const fd = new FormData();
        fd.append('title', titleVal);
        fd.append('body', getBodyHtml());
        if (imgInput.files && imgInput.files[0]) fd.append('image', imgInput.files[0]);
        postBtn.disabled = true;
        try {
            // The shared video script owns the picked clip; ask it rather than
            // reaching into the input, which a recording never touches.
            const host = document.getElementById('composerCard');
            const clip = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
            if (clip) fd.append('video', clip);
            const res = await fetch(`/app/community/groups/${groupId}/post`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('postsEmpty')?.remove();
                wrap.insertAdjacentHTML('afterbegin', data.data.html);
                wrap.firstElementChild?.classList.add('post-enter');
                document.getElementById('postTitle').value = '';
                clearBody();
                clearAttach();
                window.closeSheet?.('topicComposerSheet');
                toast(data.message);
            } else toast(data.message || 'Could not post.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { postBtn.disabled = false; }
    });

    /* ---------------- replies (delegated) ----------------
       Replies can carry a photo, so they go up as multipart form data;
       data-parent-id marks a nested reply-to-reply form. */
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.post-reply-form');
        if (!form) return;
        e.preventDefault();
        const input = form.querySelector('input[type="text"]');
        const fileInput = form.querySelector('.js-comment-file');
        const text = input.value.trim();
        const file = fileInput && fileInput.files[0];
        // ...or a picture already kept here, pointed at rather than sent.
        const pick = form.dataset.pickPath || '';
        if (!text && !file && !pick) { toast('Write something or add a photo.', 'error'); return; }
        const postId = form.getAttribute('data-post-id');
        const parentId = form.getAttribute('data-parent-id');
        // Prepend the @mention token when replying tags someone (pill shown).
        const mId = form.dataset.mentionId, mName = form.dataset.mentionName;
        const token = (mId && mName)
            ? (window.plazaMentionToken ? window.plazaMentionToken(mName, mId) : `@[${mName}](${mId}) `)
            : '';
        const sendBody = (token + text).trim();
        const fd = new FormData();
        if (sendBody) fd.append('body', sendBody);
        if (file) fd.append('image', file);
        else if (pick) fd.append('galleryPath', pick);
        if (parentId) fd.append('parentId', parentId);
        const sendBtn = form.querySelector('button[type="submit"]');
        input.disabled = true;
        window.plazaCommentFx?.startSending(sendBtn);
        try {
            const res = await fetch(`/app/community/posts/${postId}/reply`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                if (parentId) {
                    // The nested reply lands where the temporary form was,
                    // and the form folds away under it.
                    bumpReplyCount(postId, 1);
                    form.insertAdjacentHTML('beforebegin', data.data.html);
                    form.previousElementSibling?.classList.add('post-enter');
                    shrinkOut(form);
                } else {
                    const replies = form.closest('.group-post').querySelector('.post-replies');
                    replies.insertAdjacentHTML('beforeend', data.data.html);
                    replies.lastElementChild?.classList.add('post-enter');
                    bumpReplyCount(postId, 1);
                    input.value = '';
                    if (fileInput) fileInput.value = '';
                    delete form.dataset.pickPath;
                    window.plazaSetChip(form, null);
                    input.focus();
                }
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { input.disabled = false; window.plazaCommentFx?.stopSending(sendBtn); }
    });

    /* The card says how many have answered; the thread is where answering
       happens, so the number has to follow it back out. */
    function bumpReplyCount(postId, by) {
        const el = document.querySelector(`[data-reply-count="${postId}"]`);
        if (!el) return;
        const n = Math.max(0, parseInt(el.textContent || '0', 10) + by);
        el.textContent = String(n);
        const word = el.nextSibling;
        if (word && word.nodeType === 3) word.textContent = n === 1 ? ' comment' : ' comments';
    }

    /* Reply to a reply: a small form slides into the thread. */
    const SVG_R_PHOTO = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    const SVG_R_SMILE = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const SVG_R_SEND = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>';
    const SVG_R_X = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
    const gEsc = (s) => (window.escapeHtml ? window.escapeHtml(s) : String(s == null ? '' : s));
    function groupMentionPill(name) {
        return `<span class="reply-mention-pill inline-flex items-center gap-1 text-[0.688rem] font-semibold text-brand-700 bg-brand-50 border border-brand-100 rounded-full pl-2 pr-1 py-0.5 shrink-0" title="This reply notifies @${gEsc(name)}">@${gEsc(name)}<button type="button" class="js-reply-mention-x w-4 h-4 flex items-center justify-center rounded-full text-brand-400 hover:text-red-500 hover:bg-white leading-none" aria-label="Remove mention">×</button></span>`;
    }
    function nestedReplyFormHtml(postId, parentId, mentionId, mentionName) {
        const hasMention = mentionId && mentionName;
        const attrs = hasMention ? ` data-mention-id="${gEsc(mentionId)}" data-mention-name="${gEsc(mentionName)}"` : '';
        return `<form class="post-reply-form wall-reply-form flex flex-wrap items-center gap-2 mt-2 mb-3" data-post-id="${postId}" data-parent-id="${parentId}"${attrs}>
            ${hasMention ? groupMentionPill(mentionName) : ''}
            <span class="reply-shell">
                <input type="text" placeholder="Sumagot…" maxlength="4000">
                <button type="button" class="emoji-btn js-comment-photo" aria-label="Attach a photo" title="Photo">${SVG_R_PHOTO}</button>
                <input type="file" class="js-comment-file hidden" accept="image/*">
                <button type="button" class="emoji-btn js-emoji-btn" aria-label="Add an emoji" title="Emoji">${SVG_R_SMILE}</button>
                <button type="submit" class="reply-send" aria-label="Reply">${SVG_R_SEND}</button>
                <button type="button" class="js-reply-cancel btn-ghost rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 shrink-0" aria-label="Cancel reply" title="Cancel">${SVG_R_X}</button>
            </span>
            <span class="attach-chip hidden js-comment-chip"><span class="js-chip-name"></span><button type="button" class="js-chip-clear" aria-label="Remove photo">✕</button></span>
        </form>`;
    }
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-group-reply');
        if (!btn) return;
        if (root.getAttribute('data-group-member') !== '1') { toast('Join the discussion to reply.', 'error'); return; }
        const parentId = btn.getAttribute('data-parent-id');
        const post = btn.closest('.group-post');
        const zone = post && post.querySelector(`.reply-thread[data-parent-id="${parentId}"]`);
        if (!zone) return;
        const mId = btn.dataset.authorId, mName = btn.dataset.authorName;
        let form = zone.querySelector('form.post-reply-form');
        if (!form) {
            zone.insertAdjacentHTML('beforeend', nestedReplyFormHtml(post.getAttribute('data-post-id'), parentId, mId, mName));
            form = zone.querySelector('form.post-reply-form');
            growIn(form);
        } else if (mId && mName && !form.dataset.mentionId) {
            form.dataset.mentionId = mId;
            form.dataset.mentionName = mName;
            form.insertAdjacentHTML('afterbegin', groupMentionPill(mName));
        }
        // Shown, not typed into: on a phone the keyboard would cover the
        // comment being answered.
        if (!window.matchMedia('(pointer: coarse)').matches) form.querySelector('input[type="text"]').focus();
    });

    // Remove the "replying to @Name" pill (and drop the pending mention).
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.js-reply-mention-x');
        if (!x) return;
        const form = x.closest('form.post-reply-form');
        if (form) { delete form.dataset.mentionId; delete form.dataset.mentionName; }
        x.closest('.reply-mention-pill')?.remove();
    });

    /* A field that grows into its place.
     *
     * Dropped in at full height it shoves everything under it down in one
     * frame, which on a phone reads as the thread jumping — and inside a
     * scrolling sheet it can push what you were reading off the screen. This
     * opens it from nothing to its own height, so the rest moves with it. */
    function growIn(el) {
        if (!el || reduceMotion) return;
        /* Measured with the box unconstrained and laid out, or the target is
           whatever the stylesheet's cap happened to allow — which is how a
           two-line form ended up animating to a one-line height. */
        el.style.maxHeight = 'none';
        const h = el.getBoundingClientRect().height;
        el.style.overflow = 'hidden';
        el.style.maxHeight = '0px';
        el.style.opacity = '0';
        requestAnimationFrame(() => {
            el.style.transition = 'max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1)';
            el.style.maxHeight = h + 'px';
            el.style.opacity = '1';
        });
        const done = () => {
            el.style.transition = '';
            el.style.maxHeight = '';
            el.style.overflow = '';
            el.style.opacity = '';
        };
        // On max-height alone: opacity finishes at the same moment, and
        // whichever fired first used to clear the height mid-animation —
        // the snap the owner saw.
        el.addEventListener('transitionend', (e) => { if (e.propertyName === 'max-height') done(); });
        setTimeout(done, 460);   // a tab that never painted still tidies up
    }

    /* And the way back out: to nothing, then gone. */
    function shrinkOut(el, after) {
        if (!el) return;
        if (reduceMotion) { el.remove(); after && after(); return; }
        el.style.overflow = 'hidden';
        el.style.maxHeight = el.scrollHeight + 'px';
        requestAnimationFrame(() => {
            el.style.transition = 'max-height .22s cubic-bezier(.22,1,.36,1), opacity .22s cubic-bezier(.22,1,.36,1)';
            el.style.maxHeight = '0px';
            el.style.opacity = '0';
        });
        let gone = false;
        const go = () => { if (gone) return; gone = true; el.remove(); after && after(); };
        el.addEventListener('transitionend', go, { once: true });
        setTimeout(go, 320);
    }

    /* Cancel a reply → discard the text and remove the field. */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-reply-cancel');
        if (!btn) return;
        const form = btn.closest('form.post-reply-form');
        // Out the way it came in, so the thread closes over it rather than
        // snapping shut.
        if (form && form.getAttribute('data-parent-id')) shrinkOut(form);
    });

    /* ---------------- delete: the feed heals ---------------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.post-delete-btn');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete post?', message: 'This removes the post and its replies.', confirmText: 'Delete' });
        if (!ok) return;
        const postId = btn.getAttribute('data-post-id');
        try {
            const res = await fetch(`/app/community/posts/${postId}`, { method: 'DELETE', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) {
                const card = btn.closest('.group-post');
                card.style.maxHeight = card.scrollHeight + 'px';
                requestAnimationFrame(() => { card.classList.add('is-removing'); card.style.maxHeight = '0'; });
                card.addEventListener('transitionend', () => card.remove(), { once: true });
                if (reduceMotion) card.remove();
                toast(data.message);
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
    });

    /* ---------------- delete own reply → tombstone ---------------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-reply-delete');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete reply?', message: 'It will show as "This comment was deleted".', confirmText: 'Delete' });
        if (!ok) return;
        const id = btn.getAttribute('data-reply-id');
        try {
            const res = await fetch(`/app/community/reply/${id}`, { method: 'DELETE', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) {
                const holder = btn.closest('.group-reply').querySelector('.flex > .min-w-0');
                if (holder) holder.innerHTML = '<div class="bg-gray-50 rounded-xl rounded-tl-md px-3 py-2 text-xs text-gray-400 italic group-reply-tombstone tombstone-in">This comment was deleted</div>';
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
    });

    /* ---------------- older topics: scroll pagination ----------------
       The wall's contract, in this room: one page in flight at a time (the
       `loading` latch AND the hidden button, because the shared observer
       fires on its own schedule), a loader while it waits, and one plain line
       when the room runs out. An IntersectionObserver only reports a change,
       so a trigger that is already inside the pre-load margin when a page
       lands would never cross it again — the scroll position is read directly
       as well, and both paths funnel through the same latch. */
    const moreBtn = document.getElementById('loadMoreBtn');
    const spin = document.getElementById('postsSpin');
    const endNote = document.getElementById('postsEnd');
    const tail = document.getElementById('postsTail');
    const findEl = document.getElementById('topicFind');
    const findNote = document.getElementById('topicFindNote');
    const noneCard = document.getElementById('topicNone');
    let loading = false;
    let done = !moreBtn || moreBtn.hidden;
    let query = '';

    /* Hidden, not removed: a search is another first page, and a button that
       was deleted when the room ran out has nothing to come back to when the
       answer is longer than one screen. */
    function finishPosts() {
        done = true;
        if (moreBtn) { moreBtn.hidden = true; moreBtn.disabled = true; }
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    async function loadMore() {
        if (!moreBtn || done || loading || moreBtn.disabled) return;
        const page = moreBtn.getAttribute('data-next');
        loading = true;
        moreBtn.disabled = true;
        moreBtn.hidden = true;
        if (spin) spin.hidden = false;
        try {
            const res = await fetch(postsUrl(page), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error('load failed');
            wrap.insertAdjacentHTML('beforeend', data.data.html);
            if (spin) spin.hidden = true;
            if (data.data.hasMore) {
                moreBtn.setAttribute('data-next', data.data.nextPage);
                moreBtn.disabled = false;
                moreBtn.hidden = false;
                moreBtn.textContent = 'Show older topics';   // clears a previous failure's label
                loading = false;
                setTimeout(nearTail, 0);   // still near the bottom? keep going
                return;
            }
            finishPosts();
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

    // 700px of runway, the margin the shared observer uses, so the reader
    // meets the older topics already there rather than a spinner.
    function nearTail() {
        if (!moreBtn || done || loading || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) loadMore();
    }
    /* Throttled on the clock, not on requestAnimationFrame: a tab that is not
       painting (backgrounded, or a headless run) never delivers the frame. */
    let lastLook = 0;
    function onScroll() {
        const now = Date.now();
        if (now - lastLook < 100) return;
        lastLook = now;
        nearTail();
    }
    const postsUrl = (page) => `/app/community/groups/${groupId}/posts?page=${page}`
        + (query ? '&q=' + encodeURIComponent(query) : '');

    /* ---------------- searching the room ----------------
       The same endpoint the pagination uses, asked for page one with the
       words on it — so a match found on the fourth page is on the first one
       here. The server looks in the replies too: a room is searched to find
       where something was said, and that is as often an answer as a question. */
    function say(count, hasMore) {
        if (!findNote) return;
        if (!query) { findNote.hidden = true; findNote.textContent = ''; return; }
        findNote.hidden = false;
        if (!count) findNote.innerHTML = 'Walang tugma sa <b></b>.';
        else findNote.innerHTML = (hasMore ? 'First ' : '') + count + ' '
            + (count === 1 ? 'topic' : 'topics') + ' matching <b></b>.';
        // Typed words go in as text, never as markup.
        findNote.querySelector('b').textContent = '“' + query + '”';
    }

    /* What the bar shows while the sheet is shut. */
    function sayOnBar() {
        const chip = document.getElementById('topicFilterChip');
        if (!chip) return;
        chip.classList.toggle('hidden', !query);
        if (query) chip.querySelector('b').textContent = '“' + query + '”';
    }

    async function search(q) {
        const host = document.getElementById('postsWrap');
        if (!host) return;
        query = q;
        loading = true;
        try {
            const res = await fetch(postsUrl(1), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error('search failed');
            host.innerHTML = data.data.html || '';
            const count = host.children.length;
            if (noneCard) noneCard.hidden = count > 0;
            done = !data.data.hasMore;
            if (spin) spin.hidden = true;
            if (endNote) endNote.hidden = true;
            if (moreBtn) {
                moreBtn.setAttribute('data-next', data.data.nextPage || 2);
                moreBtn.hidden = !data.data.hasMore;
                moreBtn.disabled = false;
                moreBtn.textContent = 'Show older topics';
            }
            if (tail) tail.hidden = !data.data.hasMore;
            say(count, !!data.data.hasMore);
            sayOnBar();
        } catch (_) {
            toast('Could not search just now.', 'error');
        } finally {
            loading = false;
            setTimeout(nearTail, 0);
        }
    }

    if (findEl) window.plazaLiveSearch?.(findEl, search);

    /* The two doors on the bar. Both are sheets, so both come up from the
       bottom over the room rather than pushing it down the page. */
    document.getElementById('startTopicBtn')?.addEventListener('click', () => {
        window.openSheet?.('topicComposerSheet');
        window.smFocus?.(document.getElementById('postTitle'), { delay: 140 });
    });
    document.getElementById('topicSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('topicSearchSheet');
        window.smFocus?.(document.getElementById('topicFind'), { delay: 140, always: true });
    });
    // Tapping the chip is how a search is called off from the room.
    document.getElementById('topicFilterChip')?.addEventListener('click', () => {
        if (!findEl) return;
        findEl.value = '';
        findEl.dispatchEvent(new Event('input', { bubbles: true }));
    });

    moreBtn?.addEventListener('click', loadMore);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    nearTail();   // a short room can end with the tail already in view

    /* ---------------- jump to either end of the room ----------------
     * Off the screen until the page has actually been scrolled, so a short
     * room never carries buttons it has no use for. The same threshold in
     * both directions: past a screenful there is somewhere to go back to. */
    const jumps = document.getElementById('discJumps');
    if (jumps) {
        const behavior = reduceMotion ? 'auto' : 'smooth';
        const paintJumps = () => {
            // The chat pane takes the whole screen and scrolls itself; the
            // topics' buttons have no business floating over it.
            const chatting = document.documentElement.classList.contains('room-chat-open');
            const far = window.scrollY > Math.min(360, window.innerHeight * 0.6);
            jumps.classList.toggle('is-hidden', chatting || !far);
            jumps.setAttribute('aria-hidden', jumps.classList.contains('is-hidden') ? 'true' : 'false');
        };
        window.addEventListener('scroll', paintJumps, { passive: true });
        window.addEventListener('resize', paintJumps, { passive: true });
        document.addEventListener('click', () => setTimeout(paintJumps, 60));
        paintJumps();

        document.getElementById('discJumpTop')?.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior });
        });
        document.getElementById('discJumpBottom')?.addEventListener('click', () => {
            // The document's own end, so "older topics" loaded since still
            // count as further down.
            window.scrollTo({ top: document.documentElement.scrollHeight, behavior });
        });
    }
});
</script>

<script>
// ===================== GROUP CHAT (tab) =====================
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const groupId = document.getElementById('groupRoot')?.getAttribute('data-group-id');
    const isMember = document.getElementById('groupRoot')?.getAttribute('data-group-member') === '1';
    const esc = window.escapeHtml || ((s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; });
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const paneDiscussion = document.getElementById('paneDiscussion');
    const paneChat = document.getElementById('paneChat');
    const viewBar = document.getElementById('roomViewBar');
    const viewLabel = document.getElementById('roomViewLabel');
    let chatStarted = false, lastId = 0, pollTimer = null;

    /* Which of the room's two views is showing.
     *
     * Chat takes the whole screen: a conversation is the whole job while you
     * are in it, and the room's head, its topics, the footer and the app bar
     * have nothing to say meanwhile. The page underneath is locked so the
     * thing that scrolls is the messages and only the messages. */
    // Where the chat pane lives in the page, so it can be put back exactly
    // there when it stops being the whole screen.
    const chatHome = document.createComment('paneChat');
    paneChat?.parentNode?.insertBefore(chatHome, paneChat);

    /* The Members button rides up into the full-screen bar and comes back
     * down after. Moved rather than duplicated: one button, one handler, one
     * panel it opens — a second copy would only be right half the time. */
    const membersBtn = document.getElementById('chatMembersToggle');
    const membersHome = membersBtn ? document.createComment('membersBtn') : null;
    if (membersBtn && membersHome) membersBtn.parentNode.insertBefore(membersHome, membersBtn);
    const fullBar = document.querySelector('.chat-full-bar');
    const fullSub = document.querySelector('.chat-full-sub');
    function placeMembersBtn(full) {
        if (!membersBtn || !fullBar || !membersHome) return;
        if (full) fullBar.insertBefore(membersBtn, fullSub);
        else membersHome.parentNode?.insertBefore(membersBtn, membersHome);
    }

    function showView(view) {
        // A visitor who has not joined sees the "members only" card, and a
        // card like that has no use for the whole screen.
        const chat = view === 'chat';
        const full = chat && isMember;
        // Out of the page's boxes while it is the screen, back into them
        // after: fixed positioning answers to a transformed ancestor, and
        // this page animates its cards.
        if (full) {
            if (paneChat.parentElement !== document.body) document.body.appendChild(paneChat);
        } else if (chatHome.parentNode && paneChat.parentElement === document.body) {
            chatHome.parentNode.insertBefore(paneChat, chatHome);
        }
        placeMembersBtn(full);
        paneChat.classList.toggle('hidden', !chat);
        paneDiscussion.classList.toggle('hidden', chat);
        viewLabel.textContent = chat ? 'Group Chat' : 'Topics';
        document.body.classList.toggle('room-chat-open', full);
        document.documentElement.classList.toggle('room-chat-open', full);
        document.querySelectorAll('#roomViewSheet .room-view-row').forEach((row) => {
            row.querySelector('.room-view-check')?.classList.toggle('hidden', row.dataset.roomView !== view);
        });
        if (chat) {
            // A guest's pane is the members-only card; the chat endpoints 403
            // for them, so don't ask.
            if (isMember && !chatStarted) startChat();
            // Newest message in view the moment it opens, not after a scroll.
            const scroller = document.getElementById('chatScroll');
            if (scroller) requestAnimationFrame(() => { scroller.scrollTop = scroller.scrollHeight; });
        }
    }

    document.getElementById('roomViewBtn')?.addEventListener('click', () => {
        viewBar?.classList.add('is-open');
        window.openSheet?.('roomViewSheet');
    });
    document.addEventListener('sm:sheet-closed', () => viewBar?.classList.remove('is-open'));

    document.addEventListener('click', (e) => {
        const row = e.target.closest('#roomViewSheet .room-view-row');
        if (!row) return;
        window.closeSheet?.('roomViewSheet');
        showView(row.dataset.roomView);
    });

    // The way out of full screen: the ✕ in the chat's head, or Escape.
    document.getElementById('chatFullBack')?.addEventListener('click', () => showView('discussion'));
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !document.body.classList.contains('room-chat-open')) return;
        // Not while something else is on top of the chat — a lightbox, a
        // sheet, the recorder — those close themselves first.
        if (document.querySelector('.sheet.is-open, .lightbox:not(.hidden), .thread-modal:not(.hidden)')) return;
        showView('discussion');
    });

    // Nothing should be able to leave the lock behind: a link out of the chat
    // takes the page with it, but a bfcache restore brings the class back.
    window.addEventListener('pagehide', () => {
        document.body.classList.remove('room-chat-open');
        document.documentElement.classList.remove('room-chat-open');
    });

    showView('discussion');

    // Render a chat body: escape, then bold @mentions and 📍locations.
    function renderChatBody(text) {
        let h = esc(text);
        h = h.replace(/@\[([^\]]{1,80})\]\(\d+\)/g, (_, n) => '<strong>@' + n + '</strong>');
        h = h.replace(/📍\s*\[([^\]]{1,90})\]/gu, (_, l) => '<strong>📍 ' + l + '</strong>');
        return h;
    }
    function bubble(m, animate) {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;gap:.5rem;max-width:88%;' + (m.mine ? 'align-self:flex-end;flex-direction:row-reverse;' : 'align-self:flex-start;');
        if (animate) wrap.className = 'chat-bubble-in';
        const av = m.avatar
            ? `<img src="${esc(m.avatar)}" alt="" style="width:1.8rem;height:1.8rem;border-radius:999px;object-fit:cover;flex-shrink:0;">`
            : `<span style="width:1.8rem;height:1.8rem;border-radius:999px;background:var(--color-brand-100);color:var(--color-brand-700);font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${esc(m.initials || '?')}</span>`;
        const bg = m.mine ? 'background:var(--color-brand-600);color:#fff;' : 'background:#fff;color:var(--color-gray-800);border:1px solid var(--color-gray-100);';
        let inner = '';
        if (!m.mine) inner += `<div style="font-size:.7rem;font-weight:700;color:var(--color-gray-400);margin-bottom:.1rem;">${esc(m.name)}</div>`;
        if (m.body) inner += `<div style="font-size:.9rem;line-height:1.4;white-space:pre-wrap;word-break:break-word;">${renderChatBody(m.body)}</div>`;
        if (m.image) inner += `<img src="${esc(m.image)}" alt="" loading="lazy" data-lightbox style="max-width:100%;border-radius:.5rem;margin-top:.15rem;display:block;">`;
        if (m.video) inner += `<video controls preload="metadata" playsinline ${m.poster ? `poster="${esc(m.poster)}"` : ''} style="max-width:100%;max-height:14rem;border-radius:.5rem;margin-top:.15rem;display:block;background:#000;"><source src="${esc(m.video)}" type="video/mp4"></video>`;
        wrap.innerHTML = `${av}<div style="min-width:0;border-radius:.9rem;padding:.45rem .7rem;${bg}">${inner}</div>`;
        return wrap;
    }

    function appendMessages(list, scrollEl, animate) {
        list.forEach((m) => {
            if (m.id) lastId = Math.max(lastId, m.id);
            scrollEl.appendChild(bubble(m, animate));
        });
        scrollEl.scrollTop = scrollEl.scrollHeight;
    }

    async function startChat() {
        const scroll = document.getElementById('chatScroll');
        if (!scroll) return;
        try {
            const r = await fetch(`/app/community/groups/${groupId}/chat`, { headers: { Accept: 'application/json' } });
            let d = null;
            try { d = await r.json(); } catch (_) { d = null; }
            if (!d || !d.success || !d.data || !Array.isArray(d.data.messages)) {
                // Non-success (e.g. not a member / session) — show the reason, allow retry.
                scroll.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">' + esc((d && d.message) || 'Could not load the chat.') + '</p>';
                return;
            }
            chatStarted = true;
            scroll.innerHTML = '';
            if (!d.data.messages.length) scroll.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">No messages yet — say hello 👋</p>';
            appendMessages(d.data.messages, scroll);
            lastId = d.data.maxId || lastId;
            if (!pollTimer) pollTimer = setInterval(pollChat, 5000);
        } catch (e) {
            scroll.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Could not load the chat. <button type="button" class="js-chat-retry" style="color:var(--color-brand-600);text-decoration:underline;cursor:pointer">Retry</button></p>';
            scroll.querySelector('.js-chat-retry')?.addEventListener('click', () => { chatStarted = false; startChat(); });
        }
    }
    async function pollChat() {
        if (document.getElementById('paneChat').classList.contains('hidden')) return;
        try {
            const r = await fetch(`/app/community/groups/${groupId}/chat?after=${lastId}`, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (d.data.messages.length) {
                const scroll = document.getElementById('chatScroll');
                document.querySelector('#chatScroll p')?.remove();
                appendMessages(d.data.messages.filter((m) => !m.mine), scroll);
            }
        } catch (_) { /* transient */ }
    }

    // Composer
    const input = document.getElementById('chatInput');
    const fileInput = document.getElementById('chatImage');
    const attach = document.getElementById('chatAttach');
    const attachThumb = document.getElementById('chatAttachThumb');
    const attachName = document.getElementById('chatAttachName');
    const clearAttach = () => { if (fileInput) fileInput.value = ''; attach?.classList.add('hidden'); };
    fileInput?.addEventListener('change', () => {
        const f = fileInput.files[0];
        if (f) { attachThumb.src = URL.createObjectURL(f); attachName.textContent = f.name; attach.classList.remove('hidden'); }
        else clearAttach();
    });
    document.getElementById('chatAttachX')?.addEventListener('click', clearAttach);

    async function sendChat() {
        const host = input?.closest('[data-video-host]');
        const text = (input?.value || '').trim();
        const file = fileInput?.files[0];
        const video = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!text && !file && !video) return;
        const sendBtn = document.getElementById('chatSend');
        const prevBtn = sendBtn.innerHTML;
        sendBtn.disabled = true;
        sendBtn.innerHTML = video
            ? 'Uploading…'
            : '<svg class="w-4 h-4 plaza-spin" style="display:inline-block;vertical-align:-2px" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
        const fd = new FormData();
        if (text) fd.append('body', text);
        if (file) fd.append('image', file);
        if (video) fd.append('video', video);
        try {
            const r = await fetch(`/app/community/groups/${groupId}/chat`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const d = await r.json();
            if (d.success) {
                input.value = ''; clearAttach();
                if (window.plazaClearVideo) window.plazaClearVideo(host);
                const scroll = document.getElementById('chatScroll');
                document.querySelector('#chatScroll p')?.remove();
                appendMessages([d.data], scroll, true); // animate the new bubble
            } else if (window.toast) toast(d.message || 'Could not send.', 'error');
        } catch (_) { if (window.toast) toast('Network error.', 'error'); }
        finally { sendBtn.disabled = false; sendBtn.innerHTML = prevBtn; }
    }
    document.getElementById('chatSend')?.addEventListener('click', sendChat);
    input?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); sendChat(); } });

    /* ---- A long thread reads in its own modal ------------------------
     *
     * Twenty answers inline push every other topic off the page. The room
     * keeps the last few and opens the rest here.
     *
     * The post is MOVED into the modal, not copied: reacting, replying,
     * deleting and the reply composer are all delegated off document and
     * all reach for .closest('.group-post'), so the same nodes in a new
     * place keep working with nothing rewritten. A marker holds its place
     * in the room, and closing puts it back exactly where it was. */
    const threadModal = document.getElementById('threadModal');
    const threadBody = document.getElementById('threadModalBody');
    let threadSlot = null, threadPost = null;

    function openThread(post) {
        if (!post || !threadModal || threadPost) return;
        threadSlot = document.createComment('thread-slot');
        post.parentNode.insertBefore(threadSlot, post);
        threadPost = post;
        threadBody.appendChild(post);
        const t = post.querySelector('h3');
        document.querySelector('.thread-modal-title').textContent =
            (t && t.textContent.trim()) ? t.textContent.trim().slice(0, 80) : 'Thread';
        threadModal.classList.remove('hidden');
        // The page behind must not scroll under a full-height sheet.
        document.documentElement.style.overflow = 'hidden';
        threadBody.scrollTop = 0;

        /* The sheet rose, but everything inside it landed at once. The topic
           comes first, then the answers under it, a beat apart — which is
           also the order somebody reads them in. */
        if (!reduceMotion) {
            const parts = [post, ...post.querySelectorAll('.group-reply')];
            parts.forEach((el, i) => {
                el.classList.add('thm-in');
                el.style.animationDelay = Math.min(i * 45, 320) + 'ms';
                el.addEventListener('animationend', () => {
                    el.classList.remove('thm-in');
                    el.style.animationDelay = '';
                }, { once: true });
            });
        }
    }

    function closeThread() {
        if (!threadModal || !threadPost) return;
        const post = threadPost, slot = threadSlot;
        threadPost = null; threadSlot = null;
        threadModal.classList.add('is-closing');
        const done = () => {
            threadModal.classList.remove('is-closing');
            threadModal.classList.add('hidden');
            document.documentElement.style.overflow = '';
            if (slot && slot.parentNode) { slot.parentNode.insertBefore(post, slot); slot.remove(); }
            else document.getElementById('postsWrap')?.appendChild(post);
        };
        // Wait out the close animation, but never hang on a browser that
        // skipped it (reduced motion, a hidden tab).
        let finished = false;
        const once = () => { if (finished) return; finished = true; done(); };
        threadModal.querySelector('.thread-modal-card')
            ?.addEventListener('animationend', once, { once: true });
        setTimeout(once, 260);
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-thread-close]')) { closeThread(); return; }
        const btn = e.target.closest('.js-view-all-replies, .post-readmore');
        if (!btn) return;
        openThread(btn.closest('.group-post'));

    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && threadPost) closeThread(); });

    /* Fold any post whose body runs long, and give it a way in.
     *
     * Measured rather than counted: markup makes a character count a poor
     * guess at height, and this is about how much room it takes on screen. */
    function foldLongPosts(scope) {
        (scope || document).querySelectorAll('.group-post-body:not([data-folded])').forEach((b) => {
            b.setAttribute('data-folded', '1');
            if (b.closest('.thread-modal-body')) return;
            // ~6 lines at .875rem/1.5 is about 126px; past that it is folded.
            if (b.scrollHeight <= 150) return;
            b.classList.add('is-clamped');
            const more = document.createElement('button');
            more.type = 'button';
            more.className = 'post-readmore';
            more.textContent = 'Read more';
            b.after(more);
        });
    }
    foldLongPosts(document);
    /* Posts arrive later too — "load more", and a topic just written.
     *
     * Watched rather than announced: every path that adds a post already
     * writes into this one container, and an observer cannot be forgotten
     * by the next path somebody adds. */
    const postsHost = document.getElementById('postsWrap');
    if (postsHost && 'MutationObserver' in window) {
        new MutationObserver((records) => {
            records.forEach((r) => r.addedNodes.forEach((n) => {
                if (n.nodeType === 1) foldLongPosts(n);
            }));
        }).observe(postsHost, { childList: true, subtree: true });
    }

    // ---- Members panel: online/offline presence + PM a member ----
    const membersToggle = document.getElementById('chatMembersToggle');
    const membersPanel = document.getElementById('chatMembersPanel');
    const membersSidebar = document.getElementById('chatMembersSidebar');
    async function loadMembers() {
        try {
            const r = await fetch(`/app/community/groups/${groupId}/chat-members`, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            const count = d.data.online + '/' + d.data.total + ' online';
            ['chatOnlineCount', 'chatOnlineCountSide'].forEach((id) => { const el = document.getElementById(id); if (el) el.textContent = count; });
            const rows = (arr) => arr.sort((a, b) => (b.online - a.online)).map((m) => {
                const av = m.avatar
                    ? `<img src="${esc(m.avatar)}" alt="" style="width:1.8rem;height:1.8rem;border-radius:999px;object-fit:cover;">`
                    : `<span style="width:1.8rem;height:1.8rem;border-radius:999px;background:var(--color-brand-100);color:var(--color-brand-700);font-size:.65rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">${esc(m.initials || '?')}</span>`;
                const dot = `<span title="${m.online ? 'Online' : 'Offline'}" style="width:.5rem;height:.5rem;border-radius:999px;flex-shrink:0;background:${m.online ? '#22c55e' : '#cbd5e1'};"></span>`;
                const pm = (!m.isMe && m.allowMessages)
                    ? `<button type="button" class="js-dm-member inline-flex items-center justify-center w-7 h-7 rounded-full text-gray-400 hover:text-brand-700 hover:bg-white shrink-0" title="Message ${esc(m.name)}" data-pm="${m.id}" data-name="${esc(m.name)}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></button>`
                    : (m.isMe ? '<span class="text-[0.625rem] font-bold text-gray-400 shrink-0">You</span>' : '');
                return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white">${dot}${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(m.name)}</span>${pm}</div>`;
            }).join('');
            const html = rows(d.data.members.slice());
            if (membersPanel) membersPanel.innerHTML = html;
            if (membersSidebar) membersSidebar.innerHTML = html || '<p class="text-xs text-gray-400 text-center py-3">No members yet.</p>';
        } catch (_) {
            const err = '<p class="text-xs text-red-500 text-center py-3">Could not load members.</p>';
            if (membersPanel) membersPanel.innerHTML = err;
            if (membersSidebar) membersSidebar.innerHTML = err;
        }
    }
    membersToggle?.addEventListener('click', () => {
        membersPanel.classList.toggle('hidden');
        if (!membersPanel.classList.contains('hidden')) loadMembers();
    });
    // Load members (sidebar + count) when the chat tab opens, and refresh
    // presence periodically while it's visible.
    const _origStartChat = startChat;
    startChat = async function () { await _origStartChat(); loadMembers(); };
    setInterval(() => { const pane = document.getElementById('paneChat'); if (isMember && pane && !pane.classList.contains('hidden')) loadMembers(); }, 45000);

    // Preload the group chat in the background (after the Discussion tab settles)
    // so switching to the Group Chat tab is instant instead of showing a loader.
    if (isMember) setTimeout(() => { if (!chatStarted) startChat(); }, 400);

    // DM a member from either the sidebar or the mobile panel.
    document.getElementById('paneChat')?.addEventListener('click', (e) => {
        const pm = e.target.closest('[data-pm]');
        if (!pm) return;
        if (window.plazaOpenDm) window.plazaOpenDm(parseInt(pm.getAttribute('data-pm'), 10), pm.getAttribute('data-name'));
        else window.location = '/app/community?dm=' + pm.getAttribute('data-pm');
    });
});

    /* ---------------- editing the room ---------------- */
    (function editGroup() {
        const btn = document.getElementById('editGroupBtn');
        if (!btn) return;
        const sheet = 'editGroupSheet';
        const preview = (input, box) => input?.addEventListener('change', () => {
            const f = input.files && input.files[0];
            if (!f) return;
            // Shown before it is sent, so a wrong pick is caught here rather
            // than after a save.
            const url = URL.createObjectURL(f);
            box.innerHTML = '<img src="' + url + '" alt="">';
        });
        preview(document.getElementById('egCover'), document.getElementById('egCoverBox'));
        preview(document.getElementById('egBanner'), document.getElementById('egBannerBox'));

        btn.addEventListener('click', () => window.openSheet?.(sheet));
        document.getElementById('egSave')?.addEventListener('click', async (e) => {
            const save = e.currentTarget;
            const name = document.getElementById('egName').value.trim();
            if (!name) { window.toast?.('A discussion needs a name.', 'error'); return; }
            const was = save.textContent;
            save.disabled = true; save.textContent = 'Saving…';
            const fd = new FormData();
            fd.append('name', name);
            fd.append('description', document.getElementById('egDesc').value.trim());
            const cover = document.getElementById('egCover').files[0];
            const banner = document.getElementById('egBanner').files[0];
            if (cover) fd.append('image', cover);
            if (banner) fd.append('banner', banner);
            try {
                const gid = document.getElementById('groupRoot')?.getAttribute('data-group-id');
                const r = await fetch(@json(url('/app/community/groups')) + '/' + gid + '/edit', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                    credentials: 'same-origin',
                    body: fd,
                });
                const j = await r.json();
                if (!r.ok || j.success === false) throw new Error(j.message || 'Could not save.');
                window.closeSheet?.(sheet);
                window.toast?.(j.message);
                // The room is renamed in front of the reader rather than after
                // a reload they did not ask for.
                const title = document.querySelector('.disc-hero-title');
                if (title) title.textContent = j.data.name;
                const desc = document.getElementById('heroDesc');
                if (desc) desc.textContent = j.data.description || '';
            } catch (err) { window.toast?.(err.message, 'error'); }
            finally { save.disabled = false; save.textContent = was; }
        });
    })();
</script>
@endpush
