@extends('layouts.app')

@section('title', 'Discussions — Community')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community')
@section('page-subtitle', 'Talk crops with other farmers')
@section('back', route('community.index'))

@php use App\Support\CommunityAvatar; @endphp

@push('head')
@include('community.partials.plaza-css')
<style>
    /* The section head is one row wherever the two halves fit: the copy takes
       whatever is left, the button keeps its own width and never squeezes the
       heading into a narrow column beside it. Below that width the button
       wraps to a full-width line instead of a stub in the corner. */
    /* Stacked first, paired only where there is honestly room.
       Side-by-side held all the way down to 358px, which on a real phone left
       "Sali ka sa usapan" wrapping into a two-line column beside a button —
       the two-column look the owner has now flagged twice. The heading takes
       its own line, the button takes a full one under it, and they share a
       row from 30rem up where both fit without either bending. */
    /* Two small buttons beside the words, at every width.
     *
     * The button used to take a whole line of its own on a phone because it
     * was a full-sized one carrying three words; shrunk to a plus and a word
     * — and to a plus alone on the narrowest screens — it sits beside the
     * heading the way the bars in the rest of the community do. */
    /* The heading, the line that says what the page is, and under both the
       two things you can do about it — outlined in the house green, the same
       button a schedule wears on the dashboard, because they are the same
       kind of thing: a control you press, not a label on the heading. */
    .disc-head { margin-bottom:.85rem; }
    .disc-head-title { font-family:var(--font-heading); font-size:1.05rem; font-weight:800; line-height:1.25;
        color:var(--color-gray-900); }
    .disc-head-sub { font-size:.78rem; line-height:1.4; color:var(--color-gray-500); margin-top:.2rem; }
    .disc-head-acts { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-top:.6rem; }
    .dh-filter { display:inline-flex; align-items:center; gap:.35rem; flex:0 0 auto;
        max-width:11rem; padding:.25rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:800;
        background:var(--color-brand-50); color:var(--color-brand-700);
        border:1px solid var(--color-brand-200); }
    .dh-filter b { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dh-filter.hidden { display:none; }
    html.dark .dh-filter { background:rgb(61 104 35 / .25); border-color:#3f5626; color:#bfe19a; }

    /* --- A discussion is a band, not a tile ---
       This page IS the list of rooms, so each one runs the full width of the
       screen with its own colour drawn along the top and the bottom — the
       shape the wall's posts already have, and the shape the owner asked for
       here. The bottom strip is the mirror of the top: it fades from the
       other side, so a run of rooms reads as bands rather than as boxes that
       lost their corners.

       The card surface is the app's own, which in the dark is the dark green
       every other surface wears. */
    .disc-grid { display: grid; gap: .85rem; }
    .disc-card { overflow: visible; position: relative; border-radius: 0;
        border-left: 0; border-right: 0;
        margin-left: calc(var(--plaza-gutter, 1rem) * -1);
        margin-right: calc(var(--plaza-gutter, 1rem) * -1);
        --dc-a: #4a7c2a; --dc-b: #8fc267; }
    .disc-card::before, .disc-card::after { content: ''; position: absolute; inset: 0 0 auto 0;
        height: 3px; pointer-events: none;
        /* Above the cover photo, which starts at the very top of the band and
           is inside a positioned wrapper — without this the top strip was
           painted underneath it and only the bottom one was ever seen. */
        z-index: 3;
        background: linear-gradient(90deg, var(--dc-a), var(--dc-b) 55%, transparent); }
    .disc-card::after { inset: auto 0 0 0;
        background: linear-gradient(270deg, var(--dc-a), var(--dc-b) 55%, transparent); }
    /* Each room in its own colour, kept by its id so it is the same colour
       every time you come back — the wall picks its posts' hues the same way,
       and a list that changed colour on every load would only be noise. */
    .dc-hue-1 { --dc-a: #1d4ed8; --dc-b: #7aa5f5; }
    .dc-hue-2 { --dc-a: #b45309; --dc-b: #ecc06a; }
    .dc-hue-3 { --dc-a: #0f766e; --dc-b: #6cc9bf; }
    .dc-hue-4 { --dc-a: #7c3aed; --dc-b: #b393f5; }
    .dc-hue-5 { --dc-a: #be185d; --dc-b: #f090b8; }
    /* A hover that lifts a full-width band lifts the whole page with it. */
    .disc-card.card-hover:hover { transform: none; }
    .dc-top { position: relative; }
    .dc-cover { height: 6.5rem; overflow: hidden; border-radius: 0; }
    .dc-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .dc-face { position: absolute; left: .9rem; bottom: -1.35rem;
        display: flex; align-items: center; justify-content: center;
        width: 3.5rem; height: 3.5rem; border-radius: .95rem; overflow: hidden;
        border: 3px solid var(--color-white); background: var(--color-white);
        font-family: var(--font-heading); font-weight: 800; color: #fff;
        box-shadow: 0 10px 22px -14px rgb(0 0 0 / .8); text-decoration: none; }
    .dc-face img { width: 100%; height: 100%; object-fit: cover; }
    /* Room for the face, which stands half off the cover. */
    .dc-body { padding-top: 1.85rem !important; }

    /* One action per card: Join until you are in, Open once you are. They
       swap in place, so the card never grows or shifts under the thumb. */
    .disc-act { margin-top:auto; }
    /* The one button on a room's card, alive the way the tip of the day is:
       a green that drifts across it on the app's shared tide (gradSweep,
       layout) rather than a flat fill. The oversize is what gives the
       gradient room to move; the fill under it stays put so the button never
       loses its colour mid-sweep. */
    .disc-act .btn { width:100%; border:0; color:#fff;
        background-image:linear-gradient(120deg, #2f5219, #4a7c2a 28%, #6b9f3d 52%, #4a7c2a 76%, #2f5219);
        background-size:220% 100%;
        /* Same tide, different clocks: a column of buttons sweeping in
           lockstep reads as one animation on six cards rather than six cards
           that happen to be alive. */
        animation:gradSweep var(--sw-t, 10s) ease-in-out infinite alternate;
        animation-delay:var(--sw-d, 0s); }
    .disc-act .btn:hover { filter:brightness(1.06); }
    @media (prefers-reduced-motion: reduce) { .disc-act .btn { animation:none; } }
    .disc-act .is-off { display:none; }
    .disc-join { transition:opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .disc-join.is-going { opacity:0; transform:scale(.96); pointer-events:none; }
    @keyframes discSwap { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:none; } }
    .disc-open.is-arriving { animation:discSwap var(--dur) var(--ease-house); }

    /* The tail of the list: a button, a loader, or the end of the road —
       never two of them at once (the wall's shape, in this page's words). */
    .disc-tail { text-align:center; margin-top:.75rem; padding-bottom:.5rem; }
    .disc-tail[hidden] { display:none; }
    /* The same red circle the nav and the bell use. */
    .disc-new { display:inline-flex; align-items:center; justify-content:center;
        min-width:1.15rem; height:1.15rem; padding:0 .3rem; border-radius:999px;
        background:#ef4444; color:#fff; font-size:.625rem; font-weight:800;
        line-height:1; vertical-align:middle; margin-left:.25rem; }
    .gb-well { display:flex; align-items:center; justify-content:center; overflow:hidden;
        width:100%; height:5rem; border-radius:.75rem; cursor:pointer; text-align:center;
        background:var(--color-gray-100); border:1px dashed var(--color-gray-300);
        transition:border-color var(--dur) var(--ease-house), background var(--dur) var(--ease-house); }
    .gb-well:hover { border-color:var(--color-brand-400); background:var(--color-brand-50); }
    .gb-well i { font-style:normal; font-size:.75rem; font-weight:600; color:var(--color-gray-400);
        padding:0 .75rem 1.25rem; }
    .gb-well img { width:100%; height:100%; object-fit:cover; object-position:50% var(--gb-band, 50%); }
    /* Only a well with a picture in it can be dragged, and it says so. */
    .gb-well:has(img) { cursor:grab; }
    .gb-well.is-dragging { cursor:grabbing; }
    .gb-drag { position:absolute; left:.4rem; bottom:.4rem; display:inline-flex; align-items:center;
        gap:.25rem; padding:.2rem .45rem; border-radius:999px; pointer-events:none;
        background:rgb(0 0 0 / .55); color:#fff; font-size:.6rem; font-weight:700;
        transition:opacity var(--dur) var(--ease-house); }
    .gb-drag svg { width:.75rem; height:.75rem; }
    .gb-drag.hidden { display:none; }
    .gb-well.is-dragging .gb-drag { opacity:0; }
    .gb-well { position:relative; }
    /* Missing, after the save was refused: the ask, made visible. */
    .gb-well.is-wanted, .gb-face.is-wanted { border-color:#ef4444; border-style:solid; }

    .gb-tip { margin-top:.15rem; line-height:1.35; }
    .gb-req { font-size:.62rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
        color:var(--color-brand-700); background:var(--color-brand-50);
        padding:.1rem .4rem; border-radius:999px; margin-left:.25rem; }
    /* The face sits half over the cover above it, the way it will in the
       room — so the sheet shows what is being made, not two form fields. */
    /* flex-end, not center: the face hangs up over the cover, and its words
       belong under the well rather than across the middle of it. */
    .gb-face-row { display:flex; align-items:flex-end; gap:.75rem; margin-top:-2.25rem; }
    .gb-face-row > div { padding-bottom:.1rem; }
    .gb-face { position:relative; flex:0 0 auto; width:4.5rem; height:4.5rem; border-radius:1.15rem;
        overflow:hidden; cursor:pointer; display:flex; align-items:center; justify-content:center;
        border:3px solid var(--color-white); background:var(--color-gray-200);
        box-shadow:0 10px 22px -14px rgb(0 0 0 / .8); }
    .gb-face img { width:100%; height:100%; object-fit:cover; }
    .gb-face-mono { font-family:var(--font-heading); font-weight:800; font-size:1.2rem; color:var(--color-gray-500); }
    .gb-face-cam { position:absolute; right:.15rem; bottom:.15rem; width:1.35rem; height:1.35rem;
        border-radius:999px; background:var(--color-brand-600); color:#fff;
        display:flex; align-items:center; justify-content:center; }
    .gb-face-cam svg { width:.85rem; height:.85rem; }
    .gb-face-lbl { font-size:.85rem; font-weight:700; color:var(--color-gray-900); }
    .gb-face-sub { font-size:.72rem; color:var(--color-gray-400); }

    /* One row per way in. The AI composer's rows, in this page's words —
       that sheet's class is scoped to its own page. */
    .gb-src { display:flex; align-items:center; gap:.75rem; width:100%; padding:.7rem .8rem;
        border:0; border-radius:.85rem; background:transparent; text-align:left; cursor:pointer;
        font-size:.9rem; font-weight:700; color:var(--color-gray-800);
        transition:background var(--dur) var(--ease-house); }
    .gb-src:hover { background:var(--color-gray-100); }
    .gb-src .ic { width:2.4rem; height:2.4rem; border-radius:.8rem; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        background:var(--color-brand-50); color:var(--color-brand-700); }
    .gb-src .ic svg { width:1.25rem; height:1.25rem; }
    .gb-src .sub { display:block; font-size:.72rem; font-weight:600; color:var(--color-gray-400); }
    @media (prefers-reduced-motion: reduce) { .gb-well, .gb-src { transition:none; } }

    /* WHO CAN COME IN — two tiles, and a second question under them.

       Tiles rather than a dropdown: there are two answers, each needs a
       line explaining what it means in practice, and a closed dropdown
       shows neither. The real radios stay in the markup and are only made
       invisible, so a keyboard and a screen reader still meet a radio
       group and the sheet's FormData still has something to read. */
    .gb-door { display:flex; flex-direction:column; gap:.45rem; }
    .gb-pick { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
    .gb-opt { position:relative; display:flex; flex-direction:column; align-items:center;
        gap:.15rem; padding:.7rem .55rem; border-radius:.9rem; cursor:pointer; text-align:center;
        border:1.5px solid var(--color-gray-200); background:#fff;
        transition:border-color var(--dur) var(--ease-house), background var(--dur) var(--ease-house),
            box-shadow var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .gb-opt input { position:absolute; opacity:0; width:1px; height:1px; pointer-events:none; }
    .gb-opt:active { transform:scale(.985); }
    .gb-opt.is-on { border-color:var(--color-brand-500); background:var(--color-brand-50);
        box-shadow:0 0 0 3px rgb(107 159 61 / .12); }
    .gb-opt input:focus-visible + .gb-opt-i,
    .gb-opt:focus-within { outline:none; }
    .gb-opt:focus-within { box-shadow:0 0 0 3px rgb(107 159 61 / .3); }
    .gb-opt-i { display:flex; align-items:center; justify-content:center;
        width:1.7rem; height:1.7rem; color:var(--color-gray-400);
        transition:color var(--dur) var(--ease-house); }
    .gb-opt-i svg { width:1.15rem; height:1.15rem; }
    .gb-opt.is-on .gb-opt-i { color:var(--color-brand-700); }
    .gb-opt-t { font-size:.86rem; font-weight:800; color:var(--color-gray-900); line-height:1.2; }
    .gb-opt.is-on .gb-opt-t { color:var(--color-brand-800); }
    .gb-opt-s { font-size:.68rem; font-weight:600; color:var(--color-gray-400); line-height:1.25; }
    /* The second question is one step in from the first, so the page reads
       as "private, and here is what private means here". */
    .gb-how { display:flex; flex-direction:column; gap:.45rem; padding:.65rem .7rem;
        border-radius:.9rem; background:var(--color-gray-50);
        border:1px solid var(--color-gray-100); }
    .gb-how.is-opening { animation:gbHowIn .3s var(--ease-house) both; }
    @keyframes gbHowIn { from { opacity:0; transform:translateY(-.35rem); } to { opacity:1; transform:none; } }
    .gb-pick-2 .gb-opt { padding:.6rem .5rem; }
    html.dark .gb-opt { background:#1c2417; border-color:#2f3a26; }
    html.dark .gb-opt.is-on { background:#25311b; border-color:var(--color-brand-500); }
    html.dark .gb-opt-t { color:#e8efe1; }
    html.dark .gb-how { background:#1a2213; border-color:#2b3423; }
    @media (prefers-reduced-motion: reduce) {
        .gb-opt { transition:none; }
        .gb-how.is-opening { animation:none; }
    }
    /* The lock on a shut room's name. Small and grey — it is a fact about
       the room, not a warning about it. */
    .dc-lock { display:inline-flex; align-items:center; justify-content:center;
        vertical-align:middle; width:1.05rem; height:1.05rem; margin-left:.15rem;
        color:var(--color-gray-400); }
    .dc-lock svg { width:100%; height:100%; }
    html.dark .dc-lock { color:#8a978a; }

    .disc-spin { display:flex; align-items:center; justify-content:center; gap:.35rem; padding:.9rem 0; }
    .disc-spin i { display:block; width:.45rem; height:.45rem; border-radius:9999px;
        background:var(--color-brand-400); animation:discDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .disc-spin i:nth-child(2) { animation-delay:.12s; }
    .disc-spin i:nth-child(3) { animation-delay:.24s; }
    @keyframes discDot { 0%,100% { opacity:.25; transform:translateY(0); } 50% { opacity:1; transform:translateY(-.25rem); } }
    .disc-end { font-size:.78rem; font-weight:600; color:var(--color-gray-400); padding:1rem 0 .4rem; }
    .disc-spin[hidden], .disc-end[hidden] { display:none; }

    /* Cards past the first page wait off-stage and arrive a page at a time. */
    .disc-card.is-paged-in { animation:discSwap .32s var(--ease-house) both; }

    @media (prefers-reduced-motion: reduce) {
        .disc-join, .disc-open.is-arriving, .disc-card.is-paged-in { transition:none; animation:none; }
        /* A loader that stops looks like a page that broke; slow it instead. */
        .disc-spin i { animation-duration:2.6s; }
    }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'groups'])

<div class="disc-head">
    <h2 class="disc-head-title">Sali ka sa usapan</h2>
    <p class="disc-head-sub">Post questions, share what works — every room here is a conversation somebody started.</p>
    <div class="disc-head-acts">
        <button type="button" id="createGroupBtn" class="btn btn-outline btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            New discussion
        </button>
        <button type="button" id="discSearchBtn" class="btn btn-outline btn-sm" title="Search discussions" aria-label="Search discussions">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            Search
        </button>
        {{-- A filter is a thing that is ON, and it says so where it can be
             seen once the sheet is shut. --}}
        <button type="button" class="dh-filter hidden" id="discFilterChip" title="Clear the search">
            <b></b>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
</div>

{{-- An empty room and an empty answer are different things: a search that
     matched nothing keeps its field, or there is no way back to the list. --}}
@if ($groups->isEmpty() && ($q ?? '') === '')
    <div class="card">
        <div class="card-body text-center py-14">
            <div class="empty-tile">👥</div>
            <h2 class="text-lg font-bold text-gray-900 mb-1" style="font-family:var(--font-heading)">Wala pang discussions</h2>
            <p class="text-sm text-gray-500 mb-5">Ikaw ang mag-umpisa — invite kapwa magsasaka to talk shop.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createGroupBtn').click()">Start the first discussion</button>
        </div>
    </div>
@else
    <div class="disc-grid stagger-children" id="groupsGrid">
        @include('community.groups.partials.cards', ['groups' => $groups])
    </div>

    <div class="card p-8 text-center" id="discNone" @unless ($groups->isEmpty()) hidden @endunless>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">No discussion matches that. Try one word instead of a phrase.</p>
    </div>

    <div class="disc-tail" id="discTail" @unless ($hasMore) hidden @endunless>
        <button type="button" id="discMore" class="btn btn-white btn-sm" data-infinite>Show more discussions</button>
        <div class="disc-spin" id="discSpin" role="status" aria-label="Loading more discussions" hidden><i></i><i></i><i></i></div>
        <p class="disc-end" id="discEnd" hidden>🌾 Iyan na ang lahat ng usapan.</p>
    </div>
@endif

{{-- The field, behind the magnifier: it filters the list as you type, and
     closing the sheet leaves the answer on screen. --}}
<div class="sheet hidden" id="discSearchSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search discussions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        @include('community.partials.live-search', [
            'id' => 'discFind',
            'value' => $q ?? '',
            'placeholder' => 'Search discussions…',
            'label' => 'Search discussions — name or what it is about',
        ])
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Show the discussions</button>
    </div>
</div>

{{-- Create group sheet --}}
<div class="sheet hidden" id="createGroupSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">New discussion</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-2.5">
        {{-- Both pictures, up front. A room made without them is a coloured
             square with two letters in it, and the one making it is the only
             person who can fix that. --}}
        <div>
            <label class="form-label">Cover photo <span class="gb-req">required</span></label>
            <button type="button" class="gb-well" id="groupBannerPreview" data-pic="banner">
                <i>Add a wide photo for the top of the discussion</i>
                {{-- A banner is a wide slot and a phone photo is a tall
                     picture, so centring it is a guess — a field with sky
                     above it comes out as sky. Drag says which band shows,
                     exactly as an account's own cover is framed. --}}
                <span class="gb-drag hidden" id="groupBannerDrag">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16l-3 3m3-3l3 3m-3 13l-3-3m3 3l3-3"/></svg>
                    Drag to choose what shows
                </span>
            </button>
        </div>
        <div class="gb-face-row">
            <button type="button" class="gb-face" id="groupMonogramPreview" data-pic="image">
                <span class="gb-face-mono">?</span>
                <span class="gb-face-cam" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </button>
            <div class="min-w-0">
                <p class="gb-face-lbl">Discussion photo <span class="gb-req">required</span></p>
                <p class="gb-face-sub">The room's face, wherever it is listed.</p>
            </div>
        </div>
        <div>
            <label class="form-label" for="groupName">Discussion name</label>
            <input type="text" id="groupName" class="form-input" maxlength="150" placeholder="e.g. Rice Growers of Central Luzon">
            <p class="form-hint gb-tip">Tip: pangalanan mo per crop o per lugar — "Palay — Nueva Ecija".</p>
        </div>
        <div>
            <label class="form-label" for="groupDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="groupDesc" class="form-textarea" rows="2" maxlength="500" placeholder="What's this discussion about?"></textarea>
        </div>
        {{-- WHO CAN COME IN.

             Two choices, each with the sentence that explains it, because
             "Private" alone does not tell a farmer what will actually
             happen when somebody taps Join. Choosing Private opens a second
             question — a password to hand out, or your say-so each time —
             and only the password answer asks for a password. --}}
        <div class="gb-door">
            <span class="form-label">Who can join?</span>
            <div class="gb-pick">
                <label class="gb-opt is-on">
                    <input type="radio" name="gbPrivacy" value="public" checked>
                    <span class="gb-opt-i" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/></svg>
                    </span>
                    <span class="gb-opt-t">Public</span>
                    <span class="gb-opt-s">Sinuman sa AniSenso</span>
                </label>
                <label class="gb-opt">
                    <input type="radio" name="gbPrivacy" value="private">
                    <span class="gb-opt-i" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
                    </span>
                    <span class="gb-opt-t">Private</span>
                    <span class="gb-opt-s">Ikaw ang magpapapasok</span>
                </label>
            </div>

            <div class="gb-how hidden" id="gbHow">
                <span class="form-label">How do they get in?</span>
                <div class="gb-pick gb-pick-2">
                    <label class="gb-opt is-on">
                        <input type="radio" name="gbMode" value="approval" checked>
                        <span class="gb-opt-t">You approve</span>
                        <span class="gb-opt-s">Each one asks, you say yes or no</span>
                    </label>
                    <label class="gb-opt">
                        <input type="radio" name="gbMode" value="password">
                        <span class="gb-opt-t">Password</span>
                        <span class="gb-opt-s">You share it with whoever you want</span>
                    </label>
                </div>
                <div class="hidden" id="gbPassWrap">
                    <label class="form-label" for="groupPass">Password</label>
                    <input type="text" id="groupPass" class="form-input" maxlength="60" autocomplete="off"
                           placeholder="e.g. anipalay2026">
                    {{-- Shown, not dotted: the organiser is writing a secret
                         they intend to read out to other people, not one
                         they are trying to keep from the room. --}}
                    <p class="form-hint">At least 4 characters. You can see it again later, so you can pass it on.</p>
                </div>
            </div>
        </div>
        {{-- The three doors, shared by both pictures: whichever well was
             tapped is the one the pick lands in. --}}
        <input type="file" id="groupPicFile" accept="image/jpeg,image/png,image/webp" class="hidden">
        <input type="file" id="groupPicCam" accept="image/*" capture="environment" class="hidden">
    </div>
    <div class="sheet-footer">
        {{-- One button. The ✕ in the header is the way out, and a Cancel
             beside Create was two ways of saying no to one of saying yes. --}}
        <button type="button" class="btn btn-primary comp-send" id="createGroupSave" style="margin-top:0">Create discussion</button>
    </div>
</div>
{{-- Where a picture comes from. The same three the AI composer, the
     messenger and the notes all offer, in the same order. --}}
<div class="sheet hidden" id="groupPicSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="groupPicTitle">Add a photo</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="gb-src" data-src="cam">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Use the camera now</span></span>
        </button>
        <button type="button" class="gb-src" data-src="file">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>From this phone<span class="sub">Pick a picture you already have</span></span>
        </button>
        <button type="button" class="gb-src" data-src="gallery">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the AniSenso gallery<span class="sub">A photo your seasons already keep</span></span>
        </button>
    </div>
</div>
{{-- The gallery sheet itself. @once inside, so including it here is safe. --}}
@include('sm.partials.media-picker')
{{-- Tap a room's face to see it big, with the room's own facts under it. --}}
@include('community.partials.avatar-zoom')
{{-- What a shut discussion asks for, if the card's button leads to one. --}}
@include('community.partials.door-pass')
@endsection

@push('scripts')
{{-- Rooms are looked at too: the same counter the wall uses. --}}
@include('community.partials.views-js')
@include('community.partials.infinite-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.getElementById('createGroupBtn')?.addEventListener('click', () => openSheet('createGroupSheet'));

    /* ---------------- the two pictures ----------------
       A camera file, a phone file and a gallery path are three different
       things; what the save needs to know is only which picture each one is.
       So one object holds both, and every door writes into it. */
    const pics = { image: null, banner: null };   // {file} | {path, url}
    let picking = null;                           // which well opened the sheet

    /* Which band of the banner shows. A new picture has a new middle, so
       every pick puts it back to 50 rather than carrying the last photo's
       framing onto a photo of something else. */
    let bannerBand = 50;

    function setBand(v) {
        bannerBand = Math.max(0, Math.min(100, Math.round(v)));
        document.getElementById('groupBannerPreview')?.style.setProperty('--gb-band', bannerBand + '%');
    }

    function showPic(which, url) {
        const box = document.getElementById(which === 'banner' ? 'groupBannerPreview' : 'groupMonogramPreview');
        if (!box) return;
        box.classList.remove('is-wanted');
        box.querySelector('i')?.remove();
        box.querySelector('.gb-face-mono')?.remove();
        box.querySelector('img')?.remove();
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        box.prepend(img);
        if (which === 'banner') {
            setBand(50);
            document.getElementById('groupBannerDrag')?.classList.remove('hidden');
        }
    }

    /* Drag maps the pointer's travel to the PHOTO's travel, not to the box:
       moving a finger the height of the well sweeps the whole picture, so a
       tall one is reachable end to end in one gesture. The same arithmetic
       the account page's cover uses, so the two feel identical. */
    (() => {
        const well = document.getElementById('groupBannerPreview');
        if (!well) return;
        let drag = null;
        const from = (e) => (e.touches ? e.touches[0].clientY : e.clientY);
        const start = (e) => {
            if (!well.querySelector('img')) return;
            drag = { y: from(e), band: bannerBand, moved: false };
            well.classList.add('is-dragging');
        };
        const move = (e) => {
            if (!drag) return;
            const travelled = from(e) - drag.y;
            if (Math.abs(travelled) > 3) drag.moved = true;
            e.preventDefault();
            setBand(drag.band - (travelled / Math.max(1, well.clientHeight)) * 100);
        };
        const end = () => {
            if (!drag) return;
            // A drag must not also open the picture chooser underneath it.
            if (drag.moved) well.dataset.justDragged = '1';
            drag = null;
            well.classList.remove('is-dragging');
        };
        well.addEventListener('mousedown', start);
        well.addEventListener('touchstart', start, { passive: true });
        window.addEventListener('mousemove', move);
        window.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('mouseup', end);
        window.addEventListener('touchend', end);
    })();

    function tookFile(f) {
        if (!f || !picking) return;
        pics[picking] = { file: f };
        showPic(picking, URL.createObjectURL(f));
        if (picking === 'image') groupImageChosen = true;
        picking = null;
    }

    // Either well opens the same three doors; the well that was tapped is
    // remembered, so the pick knows where to land.
    document.querySelectorAll('[data-pic]').forEach((well) => {
        well.addEventListener('click', () => {
            // The gesture that framed the picture must not also replace it.
            if (well.dataset.justDragged) { delete well.dataset.justDragged; return; }
            picking = well.getAttribute('data-pic');
            const title = document.getElementById('groupPicTitle');
            if (title) title.textContent = picking === 'banner' ? 'Add a cover photo' : 'Add the discussion photo';
            openSheet('groupPicSheet');
        });
    });

    document.querySelectorAll('.gb-src').forEach((row) => {
        row.addEventListener('click', () => {
            const src = row.getAttribute('data-src');
            window.closeSheet && window.closeSheet('groupPicSheet');
            if (src === 'cam') { document.getElementById('groupPicCam')?.click(); return; }
            if (src === 'file') { document.getElementById('groupPicFile')?.click(); return; }
            // The gallery every season keeps, asked across all of them: a
            // farmer choosing a room's picture is remembering a photo, not a
            // schedule.
            const which = picking;
            if (!window.smPickMedia) { toast('The gallery is not available here.', 'error'); return; }
            window.smPickMedia({
                allSchedules: true,
                kinds: 'image',
                title: 'Choose from your gallery',
                onPick: (item) => {
                    if (!which || !item) return;
                    pics[which] = { path: item.path, url: item.url };
                    showPic(which, item.url);
                    if (which === 'image') groupImageChosen = true;
                    picking = null;
                },
            });
        });
    });

    // Shown before it is sent: a wrong pick is caught here, not after a save.
    document.getElementById('groupPicFile')?.addEventListener('change', (e) => { tookFile(e.target.files && e.target.files[0]); e.target.value = ''; });
    document.getElementById('groupPicCam')?.addEventListener('change', (e) => { tookFile(e.target.files && e.target.files[0]); e.target.value = ''; });

    let groupImageChosen = false;

    // Live monogram preview — mirrors the PHP crc32 hue formula.
    const crcTable = (() => {
        const t = [];
        for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; }
        return t;
    })();
    const crc32str = (s) => {
        const bytes = new TextEncoder().encode(s);
        let c = 0xFFFFFFFF;
        for (const b of bytes) c = crcTable[(c ^ b) & 0xFF] ^ (c >>> 8);
        return (c ^ 0xFFFFFFFF) >>> 0;
    };
    document.getElementById('groupName')?.addEventListener('input', (e) => {
        if (groupImageChosen) return;   // don't overwrite a chosen photo with the monogram
        const name = e.target.value.trim();
        const mono = document.querySelector('#groupMonogramPreview .gb-face-mono');
        if (!mono) return;
        mono.textContent = (name ? name.split(/\s+/).map((w) => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() : '?') || '?';
        const face = document.getElementById('groupMonogramPreview');
        face.className = 'gb-face av-h' + (name ? crc32str(name.toLowerCase()) % 8 : 7);
    });

    /* ---------------- Who can come in ----------------
       Two radio groups painted as tiles. The tick lives on the real radio;
       the class only follows it, so a keyboard arrowing through the group
       repaints correctly without a second source of truth. */
    (function theDoor() {
        const how = document.getElementById('gbHow');
        const passWrap = document.getElementById('gbPassWrap');
        const pass = document.getElementById('groupPass');

        const paint = (group) => {
            document.querySelectorAll('input[name="' + group + '"]').forEach((r) => {
                r.closest('.gb-opt')?.classList.toggle('is-on', r.checked);
            });
        };
        const chosen = (group) => document.querySelector('input[name="' + group + '"]:checked')?.value;

        const settle = (animate) => {
            const shut = chosen('gbPrivacy') === 'private';
            how.classList.toggle('hidden', !shut);
            if (shut && animate) {
                how.classList.remove('is-opening');
                void how.offsetWidth;   // restart the run rather than skip it
                how.classList.add('is-opening');
            }
            passWrap.classList.toggle('hidden', !(shut && chosen('gbMode') === 'password'));
        };

        document.addEventListener('change', (e) => {
            const r = e.target.closest('input[name="gbPrivacy"], input[name="gbMode"]');
            if (!r) return;
            paint(r.name);
            settle(true);
            if (r.name === 'gbMode' && r.value === 'password') setTimeout(() => pass?.focus(), 260);
        });

        paint('gbPrivacy');
        paint('gbMode');
        settle(false);
    })();

    document.getElementById('createGroupSave')?.addEventListener('click', async (e) => {
        // Captured now: `currentTarget` is null once this handler awaits.
        const saveBtn = e.currentTarget;
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDesc').value.trim();
        if (!name) { toast('Give your discussion a name.', 'error'); return; }
        /* Both pictures, and said plainly: the room is going to be listed
           beside rooms that have them. */
        const missing = ['image', 'banner'].filter((k) => !pics[k]);
        if (missing.length) {
            missing.forEach((k) => document.getElementById(k === 'banner' ? 'groupBannerPreview' : 'groupMonogramPreview')?.classList.add('is-wanted'));
            toast(missing.length === 2
                ? 'Add a discussion photo and a cover photo.'
                : (missing[0] === 'banner' ? 'Add a cover photo.' : 'Add a discussion photo.'), 'error');
            return;
        }
        const privacy = document.querySelector('input[name="gbPrivacy"]:checked')?.value || 'public';
        const joinMode = document.querySelector('input[name="gbMode"]:checked')?.value || 'approval';
        const joinPassword = (document.getElementById('groupPass')?.value || '').trim();
        // Asked here as well as on the server, so the answer arrives before
        // the two photos have finished uploading.
        if (privacy === 'private' && joinMode === 'password' && joinPassword.length < 4) {
            document.getElementById('groupPass')?.focus();
            toast('Give the password at least 4 characters.', 'error');
            return;
        }
        const fd = new FormData();
        fd.append('name', name);
        if (description) fd.append('description', description);
        fd.append('privacy', privacy);
        fd.append('bannerPos', String(bannerBand));
        if (privacy === 'private') {
            fd.append('joinMode', joinMode);
            if (joinMode === 'password') fd.append('joinPassword', joinPassword);
        }
        // A file goes up; a gallery pick goes up as the path it already has.
        [['image', 'imagePath'], ['banner', 'bannerPath']].forEach(([key, pathKey]) => {
            const pic = pics[key];
            if (pic.file) fd.append(key, pic.file);
            else if (pic.path) fd.append(pathKey, pic.path);
        });
        saveBtn.disabled = true;
        try {
            const res = await fetch(@json(route('community.groups.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) { toast(data.message); window.location = data.data.url; }
            else toast(data.message || 'Could not create discussion.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { saveBtn.disabled = false; }
    });

    /* ---------------- Join from a card ----------------
       The card's one action changes word rather than the reader learning a
       new place to tap: Join fades out and Open arrives where it stood. */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.disc-join');
        if (!btn || btn.dataset.busy) return;
        const name = btn.getAttribute('data-name') || 'this discussion';
        const door = btn.getAttribute('data-door') || 'open';
        const askForPassword = window.askForPassword || (() => Promise.resolve(null));

        // Already knocked. Asking twice does nothing but look like it failed.
        if (btn.dataset.asked) {
            toast('You already asked to join ' + name + '. ' + 'Hintayin lang natin sila.');
            return;
        }

        /* Each door asks its own question, because the answer to "are you
           sure" is different for each: joining an open room is a small act,
           a password is a thing you have to have been given, and asking is
           a message to a person. */
        let said = null;
        if (door === 'password') {
            said = await askForPassword(name);
            if (said === null) return;
        } else {
            const ok = await (window.confirmAction ? window.confirmAction({
                title: door === 'approval' ? 'Ask to join ' + name + '?' : 'Join ' + name + '?',
                message: door === 'approval'
                    ? 'The organiser will decide, and you will hear back either way.'
                    : 'You will see its topics on your wall, and the others there will see you as a member.',
                confirmText: door === 'approval' ? 'Ask to join' : 'Join',
                confirmClass: 'btn-primary',
            }) : Promise.resolve(true));
            if (!ok) return;
        }

        btn.dataset.busy = '1';
        const id = btn.getAttribute('data-group-id');
        const card = btn.closest('[data-group-card]');
        const open = card?.querySelector('.disc-open');
        btn.style.opacity = '.6';
        try {
            let data, outcome;
            /* A wrong password is a typo, not a refusal, so the sheet comes
               straight back with the field shaking rather than making them
               find the room's button again. They leave the loop by backing
               out of the sheet, which answers null. */
            for (;;) {
                const res = await fetch(`/app/community/groups/${id}/join`, {
                    method: 'POST',
                    headers: { ...jsonHeaders, 'Content-Type': 'application/json' },
                    body: said === null ? undefined : JSON.stringify({ password: said }),
                });
                data = await res.json();
                outcome = data.data?.outcome;
                if (outcome !== 'wrong') break;
                said = await askForPassword(name);
                if (said === null) { btn.style.opacity = ''; return; }
            }

            // Asked, not joined: the card records the knock and stops
            // offering to knock again.
            if (data.success && outcome === 'waiting') {
                toast(data.message);
                btn.textContent = 'Asked';
                btn.dataset.asked = '1';
                btn.style.opacity = '';
                return;
            }
            if (data.success && outcome === 'password') {
                btn.style.opacity = '';
                toast(data.message, 'error');
                return;
            }

            if (data.success) {
                toast(data.message);
                btn.textContent = '✓';
                btn.style.opacity = '';
                const finish = () => {
                    btn.classList.add('is-off');
                    btn.classList.remove('is-going');
                    open?.classList.remove('is-off');
                    if (!reduceMotion) {
                        open?.classList.add('is-arriving');
                        open?.addEventListener('animationend', () => open.classList.remove('is-arriving'), { once: true });
                    }
                    card?.querySelector('.group-joined-tag')?.classList.remove('hidden');
                };
                if (reduceMotion) finish();
                else {
                    setTimeout(() => {
                        btn.classList.add('is-going');
                        btn.addEventListener('transitionend', finish, { once: true });
                        setTimeout(finish, 500);   // safety if transitionend is missed
                    }, 300);
                }
            } else { toast(data.message, 'error'); btn.style.opacity = ''; }
        } catch (_) { toast('Network error — try again.', 'error'); btn.style.opacity = ''; }
        finally { delete btn.dataset.busy; }
    });

    /* ---------------- Scroll pagination ----------------
       A page is a fetch, not a reveal. The list used to ship every discussion
       in the first response and merely uncover them as the reader scrolled,
       which is the cost pagination exists to avoid: a farm with three hundred
       usapan paid for all of them to see eight. The server now sends one
       screenful and says whether another exists.

       One page in flight at a time, a loader while it turns, a plain line at
       the end — and a failure stops the automatic pull: a scroll handler that
       retries on every frame turns one dead network into a storm, so the
       button comes back and waits to be asked. */
    const PAGE_URL = @json(route('community.groups.page'));
    const grid = document.getElementById('groupsGrid');
    const tail = document.getElementById('discTail');
    const moreBtn = document.getElementById('discMore');
    const spin = document.getElementById('discSpin');
    const endNote = document.getElementById('discEnd');
    const findEl = document.getElementById('discFind');
    const noneCard = document.getElementById('discNone');
    const findNote = document.getElementById('discFindNote');
    let nextPage = 2;
    let loading = false;
    let done = false;
    let autoPull = true;
    let query = (findEl?.value || '').trim();

    /* Hidden, not removed. A search is another first page, and a button that
       was deleted when the unfiltered list ran out has nothing to come back
       to when the answer is longer than one screen. */
    function finish() {
        done = true;
        if (moreBtn) { moreBtn.hidden = true; moreBtn.disabled = true; }
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    const pageUrl = (page) => PAGE_URL + '?page=' + page
        + (query ? '&q=' + encodeURIComponent(query) : '');

    function land(el, i) {
        grid.appendChild(el);
        if (reduceMotion) return;
        el.classList.add('is-paged-in');
        el.style.animationDelay = Math.min(i * 45, 300) + 'ms';
        el.addEventListener('animationend', () => {
            el.classList.remove('is-paged-in');
            el.style.animationDelay = '';
        }, { once: true });
    }

    async function loadPage() {
        if (!grid || done || loading) return;
        loading = true;
        if (moreBtn) { moreBtn.disabled = true; moreBtn.hidden = true; }
        if (spin) spin.hidden = false;
        try {
            const res = await fetch(pageUrl(nextPage), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = (await res.json()).data || {};
            const holder = document.createElement('div');
            holder.innerHTML = data.html || '';
            const fresh = Array.from(holder.children);
            fresh.forEach(land);
            nextPage = data.nextPage || nextPage + 1;
            autoPull = true;
            if (spin) spin.hidden = true;
            loading = false;
            if (!fresh.length || !data.hasMore) { finish(); return; }
            if (moreBtn) { moreBtn.disabled = false; moreBtn.hidden = false; }
            setTimeout(nearTail, 0);   // still near the bottom? keep going
        } catch (e) {
            loading = false;
            // Hand the next page back to the reader rather than to the scroll.
            autoPull = false;
            if (spin) spin.hidden = true;
            if (moreBtn) {
                moreBtn.disabled = false;
                moreBtn.hidden = false;
                moreBtn.textContent = 'Try again';
            }
            if (window.toast) toast('Could not load more discussions.', 'error');
        }
    }

    // 700px of runway, the margin the shared observer uses, so the next cards
    // are already there when the reader arrives.
    function nearTail() {
        if (!moreBtn || done || loading || !autoPull || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) loadPage();
    }
    /* Throttled on the clock rather than requestAnimationFrame: a tab that is
       not painting never delivers the frame, and the list would stop looking. */
    let lastLook = 0;
    function onScroll() {
        const now = Date.now();
        if (now - lastLook < 100) return;
        lastLook = now;
        nearTail();
    }
    /* Back-button arrivals.
       A browser restores this page exactly as it was left — so a room joined
       on its own page still says Join here, which is the "it says I am joined
       but the button is still Join" the owner hit. Nothing else on the page
       can tell; ask the server what is true. */
    window.addEventListener('pageshow', (ev) => {
        if (!ev.persisted) return;
        fetch(@json(route('community.groups.mine')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((j) => {
                const mine = new Set(((j.data && j.data.groupIds) || []).map(Number));
                document.querySelectorAll('[data-group-card]').forEach((card) => {
                    const joined = mine.has(Number(card.getAttribute('data-group-card')));
                    card.querySelector('.disc-join')?.classList.toggle('is-off', joined);
                    card.querySelector('.disc-open')?.classList.toggle('is-off', !joined);
                    card.querySelector('.group-joined-tag')?.classList.toggle('hidden', !joined);
                });
            })
            .catch(() => { /* leave the page as it was rather than break it */ });
    });

    /* ---------------- Search ----------------
       The same endpoint the pagination uses, asked for page one with the
       words on it. Everything the reader can see about the list — the cards,
       the tail, the "nothing matched" card, the line under the field — is put
       back to a first page here, because that is exactly what arrived. */
    function say(count, hasMore) {
        // Said in the sheet where it was asked for, and on the head, which is
        // what stays on screen once the sheet is shut.
        const chip = document.getElementById('discFilterChip');
        if (chip) {
            chip.classList.toggle('hidden', !query);
            if (query) chip.querySelector('b').textContent = '“' + query + '”';
        }
        if (!findNote) return;
        if (!query) { findNote.hidden = true; findNote.textContent = ''; return; }
        findNote.hidden = false;
        if (!count) { findNote.innerHTML = 'Walang tugma sa <b></b>.'; }
        else { findNote.innerHTML = (hasMore ? 'First ' : '') + count + ' '
            + (count === 1 ? 'discussion' : 'discussions') + ' matching <b></b>.'; }
        // The words go in as text, never as markup — they came from a keyboard.
        findNote.querySelector('b').textContent = '“' + query + '”';
    }

    async function search(q) {
        if (!grid) return;
        query = q;
        // A shared link, a reload and a back button should all show what is
        // on the screen right now.
        try {
            const url = new URL(window.location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            history.replaceState(null, '', url);
        } catch (_) { /* an address bar that will not be written is not a failure */ }

        loading = true;
        try {
            const res = await fetch(pageUrl(1), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = (await res.json()).data || {};
            grid.innerHTML = data.html || '';
            const count = grid.children.length;
            if (noneCard) noneCard.hidden = count > 0;
            nextPage = data.nextPage || 2;
            done = !data.hasMore;
            autoPull = true;
            if (spin) spin.hidden = true;
            if (endNote) endNote.hidden = true;
            if (moreBtn) {
                moreBtn.hidden = !data.hasMore;
                moreBtn.disabled = false;
                moreBtn.textContent = 'Show more discussions';
            }
            if (tail) tail.hidden = !data.hasMore;
            say(count, !!data.hasMore);
        } catch (_) {
            if (window.toast) toast('Could not search just now.', 'error');
        } finally {
            loading = false;
            setTimeout(nearTail, 0);
        }
    }

    const filterChip = document.getElementById('discFilterChip');
    document.getElementById('discSearchBtn')?.addEventListener('click', () => {
        window.openSheet?.('discSearchSheet');
        // No `always`: the phone keypad should wait for a tap on the field.
        window.smFocus?.(findEl, { delay: 140 });
    });
    filterChip?.addEventListener('click', () => {
        if (!findEl) return;
        findEl.value = '';
        findEl.dispatchEvent(new Event('input', { bubbles: true }));
    });

    if (findEl) {
        window.plazaLiveSearch?.(findEl, search);
        if (query) say(grid ? grid.children.length : 0, !!(tail && !tail.hidden));
    }

    moreBtn?.addEventListener('click', () => { autoPull = true; loadPage(); });
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    if (tail && !tail.hidden) nearTail();   // a short list can end with the tail already in view
});
</script>
@endpush
