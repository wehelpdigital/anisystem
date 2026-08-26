@extends('layouts.app')

@section('title', $member->full_name . ' — Community')
@section('body-class', 'plaza-ground pf-full')
@section('page-title', 'Community')
@section('page-subtitle', $member->full_name)
@section('back', route('community.connect.members'))

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
<div>
    @include('community.partials.nav', ['active' => (int) $member->id === (int) auth()->id() ? 'profile' : 'members'])

    {{-- The profile header.

         A cover with the face sitting on its edge, then the name, then the
         numbers that answer "is this somebody worth following" — which is
         the decision this page exists to support. Everything descriptive
         moved out into the About panel below; it used to sit here as loose
         chips and pushed the buttons off a phone's first screen. --}}
    {{-- The knock on the door, FIRST — above the profile itself: a request
         is the one thing here waiting on YOUR answer, so it meets you before
         the cover does. --}}
    @if (! $isSelf && $status === 'pending_in')
        <div class="card pf-request mb-4">
            <div class="pf-request-head">
                <span class="pf-request-ico" aria-hidden="true">🤝</span>
                <div class="pf-request-txt">
                    <b>{{ $member->firstName }} wants to be your co-farmer</b>
                    <span>{{ $member->full_name }} sent you a co-farmer request. Accepting connects your farms — you will see each other's news and can reach each other any time.</span>
                </div>
            </div>
            <span class="conn-action pf-request-acts" data-member-id="{{ $member->id }}" data-status="pending_in">
                <button type="button" class="btn btn-primary btn-sm conn-btn conn-grad" data-action="accept">Accept</button>
                <button type="button" class="btn btn-white btn-sm conn-btn" data-action="decline">Not now</button>
            </span>
        </div>
    @endif

    <div class="card pf-head mb-4">
        @if (filled($member->coverPath))
            {{-- The band the owner dragged into place, not the middle the
                 browser would have guessed. --}}
            <div class="pf-cover" style="background-image:url('{{ \App\Support\MediaStore::url($member->coverPath) }}'); background-position: 50% {{ (int) ($member->coverPos ?? 50) }}%"></div>
        @else
            {{-- No cover yet: the app's drifting header green stands in, so a
                 bare profile still opens with a header instead of a name
                 floating in white space. --}}
            <div class="pf-cover profile-cover-fallback" aria-hidden="true"></div>
        @endif

        <div class="pf-body">
            {{-- Follow, the wall's own green pill, in flow at the card's top
                 right — fully below the cover, never over it. --}}
            @unless ($isSelf)
                <div class="pf-quick">
                    <button type="button" class="fp-follow {{ $isFollowed ? 'is-on' : '' }}"
                            data-follow="{{ $member->id }}" data-name="{{ $member->full_name }}"
                            aria-pressed="{{ $isFollowed ? 'true' : 'false' }}">
                        <span class="on">Following</span><span class="off">+ Follow</span>
                    </button>
                </div>
            @endunless
            <div class="pf-id">
                <span class="status-avatar pf-face" data-self="{{ $isSelf ? 1 : 0 }}">
                    @include('community.partials.avatar', ['user' => $member, 'size' => 'avatar-lg', 'link' => false, 'showOnline' => true])
                    {{-- Thought bubble floating over the profile pic. --}}
                    <span class="status-bubble {{ filled($member->statusBubble) ? '' : 'is-empty' }}" id="statusBubble"
                          @if ($isSelf) role="button" tabindex="0" title="Set your status" data-status-bubble @endif><span class="status-bubble-text" @if ($isSelf) data-status-text @endif>{{ $member->statusBubble ?: ($isSelf ? "💭 What's on your mind?" : '') }}</span></span>
                </span>
                {{-- Face first, name under it, the rank on its own line —
                     the same order the ranking page and the member cards
                     speak, and nothing left printing over the cover. --}}
                <div class="pf-name">
                    <h2>{{ $member->full_name }}</h2>
                    <p class="pf-rank">@include('community.partials.rank-badge', ['rankUser' => $member, 'rankBig' => true])</p>
                    @if (filled($member->headline))
                        <p class="pf-headline">{{ $member->headline }}</p>
                    @endif
                    @if (filled($member->location))
                        <p class="pf-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $member->location }}</p>
                    @endif
                </div>
            </div>

            {{-- The numbers, as a row that scrolls rather than wraps: five of
                 them stacked two-and-three looks like a table nobody reads. --}}
            <div class="pf-stats">
                <span class="pf-stat"><b>{{ $postCount }}</b><i>{{ \Illuminate\Support\Str::plural('post', $postCount) }}</i></span>
                <span class="pf-stat"><b>{{ $followerCount }}</b><i>{{ \Illuminate\Support\Str::plural('follower', $followerCount) }}</i></span>
                <span class="pf-stat"><b>{{ $followingCount }}</b><i>following</i></span>
                {{-- Shared plans are not here: the tab below already carries
                     that count, and a fifth number pushed the row off a
                     360px screen entirely rather than half-showing. --}}
                <span class="pf-stat"><b>{{ $connectionCount }}</b><i>{{ \Illuminate\Support\Str::plural('connection', $connectionCount) }}</i></span>
            </div>

            @if (filled($member->bio))
                <p class="pf-bio">{{ $member->bio }}</p>
            @endif

            {{-- The whole reason most visits happen, wearing the composers'
                 living green across the card's full width. --}}
            @if (! $isSelf && $member->allowMessages)
                <button type="button" class="btn btn-primary comp-send pf-msg js-open-dm"
                        data-dm-user="{{ $member->id }}" data-dm-name="{{ $member->full_name }}">
                    💬 Message {{ $member->firstName }}
                </button>
            @endif

            {{-- Message and Follow live in the corner now (see .pf-quick);
                 what stays down here is the deliberate connection act — and
                 an incoming request gets its own card below instead. --}}
            @if ($isSelf || $status !== 'pending_in')
                <div class="pf-acts">
                    @if ($isSelf)
                        <a href="{{ route('account.index') }}" class="btn btn-white btn-sm">✏️ Edit profile</a>
                    @else
                        @include('community.connect.partials.action', ['status' => $status, 'memberId' => $member->id])
                    @endif
                </div>
            @endif
        </div>
    </div>


    {{-- About: labelled rows, not a paragraph of emoji chips.

         The chips read fine at five words and turned into a wall at fifteen;
         a visitor scanning for "what do they grow" now has a line to land on.
         Hidden entirely when there is nothing to say, so an empty profile is
         not a list of blanks. --}}
    @php
        $about = array_filter([
            'Location' => $member->location,
            'Does' => $member->profession,
            'Farming for' => filled($member->yearsFarming)
                ? $member->yearsFarming . ' ' . \Illuminate\Support\Str::plural('year', (int) $member->yearsFarming)
                : null,
            'Farm size' => $member->farmSize,
            'Grows' => $member->cropsGrown,
            'Method' => $member->farmingMethod,
        ], fn ($v) => filled($v));
    @endphp
    @if (! empty($about) || $isSelf)
        <div class="card pf-about mb-4">
            <h3>About</h3>
            @if (! empty($about))
                <dl class="pf-rows">
                    @foreach ($about as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @else
                <p class="pf-about-empty">
                    Nothing here yet — what you grow and how long you have farmed
                    helps other farmers know whether to ask you.
                    <a href="{{ route('account.index') }}">Fill it in</a>.
                </p>
            @endif
        </div>
    @endif

    {{-- Wall | Shared Plans tabs --}}
    {{-- Three green doors with their icons. Shared Plans left the row — the
         plans a member shares already reach you where plans are used. --}}
    <div class="profile-tabs flex gap-1.5 mb-4" role="tablist" id="profileTabs">
        <button type="button" class="profile-tab is-active" data-tab="wall" aria-selected="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
            Wall</button>
        <button type="button" class="profile-tab" data-tab="photos" aria-selected="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Photos <span class="pt-n">{{ $photos->total() }}</span></button>
        <button type="button" class="profile-tab" data-tab="videos" aria-selected="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Videos <span class="pt-n">{{ $videos->total() }}</span></button>
    </div>

    <div data-tab-panel="wall">
        @include('community.connect.partials.wall', ['member' => $member, 'isSelf' => $isSelf])
    </div>

    <div data-tab-panel="plans" class="hidden">
        <div class="card p-4">
            <h3 class="font-bold text-gray-900 mb-2" style="font-family:var(--font-heading)">Shared plans</h3>
            @if ($plans->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($plans as $plan)
                        <a href="{{ route('community.show', ['id' => $plan->id]) }}" class="flex items-center justify-between gap-2 p-2.5 rounded-lg hover:bg-gray-50 transition">
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 text-sm truncate">{{ $plan->title }}</span>
                                @if ($plan->cropType)<span class="block text-xs text-gray-500">{{ $plan->cropType }}@if($plan->publicRegion) · {{ $plan->publicRegion }}@endif</span>@endif
                            </span>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-4 text-center">{{ $isSelf ? 'You have' : $member->firstName . ' has' }} not shared any plans yet.</p>
            @endif
        </div>
    </div>

    <div data-tab-panel="photos" class="hidden">
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="font-bold text-gray-900" style="font-family:var(--font-heading)">Photos</h3>
                @if ($isSelf)
                    <label class="btn btn-primary btn-sm cursor-pointer mb-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add photos
                        <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
                    </label>
                @endif
            </div>
            <div class="profile-photos-grid {{ $photos->isEmpty() ? 'hidden' : '' }}" id="profilePhotosGrid">
                @foreach ($photos as $photo)
                    @include('community.connect.partials.photo-tile', ['item' => $photo])
                @endforeach
            </div>
            @include('partials.list-pager', ['noun' => 'photo', 'paginator' => $photos,
                'rowsUrl' => route('community.connect.profile', ['userId' => $member->id]) . '?rows=1&tab=photos'])
            <p class="text-sm text-gray-400 py-6 text-center {{ $photos->isNotEmpty() ? 'hidden' : '' }}" id="profilePhotosEmpty">
                {{ $isSelf ? 'Add photos of your farm, harvest, or yourself — tap “Add photos”.' : $member->firstName . ' has not added any photos yet.' }}
            </p>
        </div>
    </div>

    <div data-tab-panel="videos" class="hidden">
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="font-bold text-gray-900" style="font-family:var(--font-heading)">Videos</h3>
                @if ($isSelf)
                    <label class="btn btn-primary btn-sm cursor-pointer mb-0" id="profileVideoAddBtn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add video
                        <input type="file" id="profileVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-msvideo,video/3gpp,video/x-m4v" class="hidden">
                    </label>
                @endif
            </div>
            @if ($isSelf)
                <div class="profile-video-uploading hidden" id="profileVideoUploading">
                    <span class="profile-video-spin" aria-hidden="true"></span>
                    <span>Uploading &amp; compressing your video… this can take a moment for longer clips.</span>
                </div>
            @endif
            <div class="profile-videos-grid {{ $videos->isEmpty() ? 'hidden' : '' }}" id="profileVideosGrid">
                @foreach ($videos as $video)
                    @include('community.connect.partials.video-tile', ['item' => $video])
                @endforeach
            </div>
            @include('partials.list-pager', ['noun' => 'video', 'paginator' => $videos,
                'rowsUrl' => route('community.connect.profile', ['userId' => $member->id]) . '?rows=1&tab=videos'])
            <p class="text-sm text-gray-400 py-6 text-center {{ $videos->isNotEmpty() ? 'hidden' : '' }}" id="profileVideosEmpty">
                {{ $isSelf ? 'Share a short clip of your farm or harvest — tap “Add video”. It’s compressed automatically.' : $member->firstName . ' has not added any videos yet.' }}
            </p>
        </div>
    </div>
</div>

@include('community.partials.post-actions')
@if ($isSelf)
    @include('community.partials.status-modal')
@endif
@endsection

@push('head')
<style>
    /* ---- Profile header ---- */
    .pf-head { padding:0; overflow:hidden; }
    .pf-cover { height:7rem; background-size:cover; background-repeat:no-repeat; background-color:var(--color-gray-100); }
    @media (min-width:640px) { .pf-cover { height:11rem; } }
    /* The 1px of top padding is load-bearing: without it, .pf-id's negative
       margin collapses THROUGH this box's top edge and drags the whole body
       — and everything absolutely anchored to it — up over the cover. */
    .pf-body { padding:1px 1rem 1rem; position:relative; }
    /* The face sits on the cover's edge — half over, half under — and the
       name stands UNDER it, centred, the way the ranking page and every
       member card introduce a person. Side by side, the name printed over
       the cover behind the thought cloud. */
    .pf-id { display:flex; flex-direction:column; align-items:center; text-align:center;
        margin-top:-2.75rem; }
    .pf-face { position:relative; display:inline-block; flex:none; width:5.5rem; height:5.5rem;
        border-radius:999px; box-shadow:0 0 0 3px var(--color-white); background:var(--color-white); }
    /* A dashed green ring turning slowly around the face, its green washing
       light-to-deep as it goes — the page's one touch of ceremony. The
       dashes carry the motion, the colour sweep carries the gradient. */
    .pf-face::after { content:''; position:absolute; inset:-8px; border-radius:999px;
        border:2.5px dashed #6b9f3d; pointer-events:none; z-index:0;
        animation:pfRingSpin 18s linear infinite, pfRingGlow 5s ease-in-out infinite alternate; }
    @keyframes pfRingSpin { to { transform:rotate(360deg); } }
    @keyframes pfRingGlow {
        from { border-color:#3d6823; filter:drop-shadow(0 0 2px rgb(61 104 35 / .35)); }
        to { border-color:#a9d383; filter:drop-shadow(0 0 5px rgb(143 194 103 / .55)); } }
    @media (prefers-reduced-motion: reduce) { .pf-face::after { animation:none; } }
    /* The face is a circle whatever shape the file is.
       On your own profile the avatar arrives wrapped in .avatar-online-wrap
       (it carries the green dot), and that wrapper is an inline-block with no
       height — so `height:100%` on the avatar inside it resolved against
       nothing and the picture kept its own proportions: 80x53 for a landscape
       photo, taller than its box for a portrait one, which is what was
       leaning on the name below. Each link in the chain is given the size. */
    .pf-face > .avatar-online-wrap { display:block; width:100%; height:100%; }
    .pf-face .avatar { display:block; width:100%; height:100%; font-size:1.5rem;
        border-radius:999px; overflow:hidden; }
    .pf-face .avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    /* The dot rides on the rim of the circle, not on the picture's corner. */
    .pf-face .avatar-online-dot { position:absolute; right:.15rem; bottom:.15rem; z-index:2; }
    @media (min-width:640px) { .pf-face { width:6rem; height:6rem; } }
    .pf-name { min-width:0; padding-bottom:.15rem; }
    .pf-name h2 { font-family:var(--font-heading); font-size:1.15rem; font-weight:800; line-height:1.2;
        color:var(--color-gray-900); overflow-wrap:anywhere; }
    /* Each line of the introduction breathes: name, then the rank it earned,
       then what they say they do, then where — none of them crowding. */
    .pf-name { margin-top:.55rem; }
    .pf-rank { margin-top:.55rem; }
    .pf-rank .rankb { pointer-events:none; }
    .pf-headline { font-size:.82rem; font-weight:600; color:var(--color-gray-600); margin-top:.55rem; }
    .pf-loc { display:flex; align-items:center; justify-content:center; gap:.3rem; font-size:.78rem; color:var(--color-gray-500); margin-top:.5rem; }
    .pf-loc svg { width:.85rem; height:.85rem; color:#e11d48; flex:none; }

    /* Follow — the wall's own green pill — pinned to the body's top right,
       which begins exactly where the cover ends: just under it, over
       nothing. (pf-body is the positioning context, not the card, so the
       cover's height never enters the arithmetic.) */
    .pf-quick { position:absolute; right:.9rem; top:.6rem; z-index:3; }
    /* The full-width Message: comp-send brings the moving gradient; this
       only rounds it into the card's own corners and gives it air. */
    .pf-msg { margin-top:.9rem; border-radius:.85rem; }

    /* The knock on the door. */
    .pf-request { padding:1rem; border:1.5px solid rgb(107 159 61 / .4);
        background:linear-gradient(115deg, #f4faee, #e6f2d9 55%, #f7fbf2); }
    html.dark .pf-request { border-color:rgb(143 194 103 / .3);
        background:linear-gradient(115deg, #1c2415, #243019 55%, #1a2113); }
    .pf-request-head { display:flex; align-items:flex-start; gap:.75rem; }
    .pf-request-ico { flex:none; width:2.6rem; height:2.6rem; border-radius:.8rem; font-size:1.3rem;
        display:inline-flex; align-items:center; justify-content:center;
        background:rgb(255 255 255 / .8); box-shadow:0 2px 8px rgb(47 82 25 / .15); }
    html.dark .pf-request-ico { background:rgb(0 0 0 / .3); }
    .pf-request-txt b { display:block; font-family:var(--font-heading); font-size:.95rem; font-weight:800;
        color:var(--color-gray-900); }
    .pf-request-txt span { display:block; font-size:.8rem; color:var(--color-gray-600); line-height:1.5; margin-top:.25rem; }
    .pf-request-acts { display:flex; gap:.5rem; margin-top:.85rem; }
    .pf-request-acts .btn { flex:1 1 0; justify-content:center; }

    /* This page is a visit, not a hallway — the bottom tab bar steps away
       and the back arrow is the way home. */
    body.pf-full .tabbar { display:none; }
    body.pf-full { padding-bottom:0; }
    body.pf-full main { padding-bottom:1.5rem; }

    /* Five numbers: a centred row that scrolls, never a grid that wraps. */
    .pf-stats { display:flex; gap:.9rem; margin-top:.85rem; padding-bottom:.15rem;
        justify-content:center; overflow-x:auto; scrollbar-width:none; }
    .pf-stats::-webkit-scrollbar { display:none; }
    .pf-stat { flex:none; display:flex; align-items:baseline; gap:.28rem; }
    .pf-stat b { font-size:.95rem; font-weight:800; color:var(--color-gray-900); }
    .pf-stat i { font-style:normal; font-size:.72rem; font-weight:600; color:var(--color-gray-500); }
    .pf-bio { font-size:.85rem; color:var(--color-gray-700); margin-top:.75rem; text-align:center;
        white-space:pre-line; overflow-wrap:anywhere; }
    .pf-acts { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.5rem;
        margin-top:.9rem; padding-top:.9rem; border-top:1px solid var(--color-gray-100); }
    html.dark .pf-acts { border-top-color:rgb(255 255 255 / .08); }

    /* ---- About panel ---- */
    .pf-about { padding:1rem; }
    .pf-about h3 { font-family:var(--font-heading); font-size:.9rem; font-weight:800;
        color:var(--color-gray-900); margin-bottom:.5rem; }
    .pf-rows > div { display:flex; gap:.75rem; padding:.4rem 0; border-top:1px solid var(--color-gray-100); }
    .pf-rows > div:first-child { border-top:0; }
    .pf-rows dt { flex:none; width:6.5rem; font-size:.78rem; font-weight:700; color:var(--color-gray-500); }
    .pf-rows dd { min-width:0; font-size:.82rem; color:var(--color-gray-800); overflow-wrap:anywhere; }
    .pf-about-empty { font-size:.8rem; color:var(--color-gray-400); }
    .pf-about-empty a { color:var(--color-brand-700); font-weight:700; text-decoration:underline; }
    html.dark .pf-rows > div { border-top-color:rgb(255 255 255 / .08); }

    /* Stand-in cover: the same slow green the messenger and nav wear. */
    .profile-cover-fallback {
        background:linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size:240% 240%; animation:gradSweep 14s ease-in-out infinite alternate; }
    @media (prefers-reduced-motion: reduce) { .profile-cover-fallback { animation:none; } }

    /* Four tabs don't fit a phone as equal columns — they keep their words
       and the row scrolls instead of crushing "Shared Plans" to a smudge. */
    .profile-tabs { overflow-x:auto; scrollbar-width:none; }
    .profile-tabs::-webkit-scrollbar { display:none; }
    /* Green doors: outline pills that fill solid when stood in. */
    .profile-tab { flex:1; white-space:nowrap; min-height:2.75rem; padding:.55rem .75rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
        border:1.5px solid var(--color-brand-300); background:var(--color-white); border-radius:.7rem;
        font-size:.85rem; font-weight:700; color:var(--color-brand-700); cursor:pointer;
        transition:background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1),
            border-color .28s cubic-bezier(.22,1,.36,1); }
    .profile-tab svg { width:1rem; height:1rem; flex:none; }
    .profile-tab .pt-n { font-size:.7rem; font-weight:800; opacity:.75; }
    .profile-tab.is-active { border-color:transparent; color:#fff;
        background-image:linear-gradient(120deg, #2f5219, #4a7c2a 60%, #3d6823); }
    html.dark .profile-tab { background:#1f2817; border-color:#3f5626; color:#bfe19a; }
    html.dark .profile-tab.is-active { border-color:transparent; color:#fff; }

    /* Accept, asking for the tap: a soft ripple ring breathing out of it. */
    .pf-request-acts .btn { flex:1 1 0; justify-content:center; }
    .pf-request-acts [data-action="accept"] { position:relative;
        animation:pfAskRipple 2.2s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes pfAskRipple {
        0% { box-shadow:0 0 0 0 rgb(74 124 42 / .45); }
        60% { box-shadow:0 0 0 .65rem rgb(74 124 42 / 0); }
        100% { box-shadow:0 0 0 0 rgb(74 124 42 / 0); } }
    @media (prefers-reduced-motion: reduce) { .pf-request-acts [data-action="accept"] { animation:none; } }

    /* The About card opens on a brand gradient edge — painted as the card's
       own background sized to its top few pixels, so the corner radius is
       the card's and not a stub's. */
    .pf-about { background-image:linear-gradient(90deg, #2f5219, #6b9f3d 45%, #a9d383 75%, #4a7c2a);
        background-size:100% 4px; background-repeat:no-repeat; }
    [data-tab-panel].hidden { display:none; }
    /* A tab change arrives, it doesn't snap. */
    @keyframes profilePanelIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
    [data-tab-panel]:not(.hidden) { animation:profilePanelIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    @media (prefers-reduced-motion: reduce) { [data-tab-panel]:not(.hidden) { animation:none; } }

    /* Chat bubble above the profile pic, with a tail pointing down at the photo. */
    .status-bubble { position:absolute; bottom:calc(100% + .35rem); left:.25rem; right:auto;
        max-width:12rem;
        background:#fff; border:1px solid #e5e7eb; border-radius:.7rem; padding:.2rem .6rem;
        box-shadow:0 3px 10px rgba(0,0,0,.12); z-index:2;
        transition:transform .15s var(--ease-house,cubic-bezier(.22,1,.36,1)), box-shadow .15s ease; }
    .status-bubble-text { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        font-size:.7rem; font-weight:600; color:#374151; }
    /* The same tail the wall's cloud wears: a square of the bubble turned on
       its corner, inheriting fill and border. The border-triangle it replaces
       carried a drop-shadow filter, and the two together frayed into stray
       pixels under the bubble. */
    .status-bubble::after { content:''; position:absolute; left:.9rem; bottom:-.3rem;
        width:.55rem; height:.55rem; transform:rotate(45deg);
        background:inherit; border:inherit; border-top:0; border-left:0;
        border-bottom-right-radius:.12rem; }
    .status-avatar[data-self="1"] .status-bubble { cursor:pointer; }
    .status-avatar[data-self="1"] .status-bubble:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(0,0,0,.18); }
    /* Empty own-profile state: a noticeable brand-tinted invite. */
    .status-avatar[data-self="1"] .status-bubble.is-empty { background:var(--color-brand-50);
        border-color:var(--color-brand-300); border-style:dashed; }
    .status-avatar[data-self="1"] .status-bubble.is-empty .status-bubble-text { color:var(--color-brand-700); font-weight:700; }
    .status-avatar[data-self="0"] .status-bubble.is-empty { display:none; }
    html.dark .status-bubble { background:#232a1c; border-color:#3a4a2c; }
    html.dark .status-bubble-text { color:#dbe6cf; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty { background:#1c2a12; border-color:#3a5a1c; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty .status-bubble-text { color:#a7d977; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty::after { border-top-color:#1c2a12; }
</style>
@endpush

@include('community.connect.partials.connect-js')
@include('community.partials.avatar-zoom')
@push('scripts')
@include('community.partials.emoji-js')
<script>
(() => {
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const say = (m, t) => { if (window.toast) toast(m, t); };
    const syncEmpty = (kind) => {
        const grid = document.getElementById(kind === 'photo' ? 'profilePhotosGrid' : 'profileVideosGrid');
        const empty = document.getElementById(kind === 'photo' ? 'profilePhotosEmpty' : 'profileVideosEmpty');
        if (!grid || !empty) return;
        const any = !!grid.querySelector('.profile-photo-tile, .profile-video-tile');
        grid.classList.toggle('hidden', !any);
        empty.classList.toggle('hidden', any);
    };

    // Deleting. Confirmed first — a photo off a profile is not undoable.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-photo-delete, .js-video-delete');
        if (!btn) return;
        e.preventDefault();
        const isPhoto = btn.classList.contains('js-photo-delete');
        const id = btn.getAttribute(isPhoto ? 'data-photo-id' : 'data-video-id');
        if (!id) return;
        const ok = window.confirmAction
            ? await confirmAction({ title: isPhoto ? 'Delete this photo?' : 'Delete this video?', message: 'It comes off your profile for everyone.', confirmText: 'Delete' })
            : confirm('Delete?');
        if (!ok) return;
        try {
            const res = await fetch('{{ url('/app/community/profile') }}/' + (isPhoto ? 'photos' : 'videos') + '/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not delete.');
            btn.closest('.profile-photo-tile, .profile-video-tile')?.remove();
            syncEmpty(isPhoto ? 'photo' : 'video');
            say(data.message || 'Deleted.');
        } catch (err) { say(err.message, 'error'); }
    });

    // Adding photos: the endpoint answers with rendered tiles, newest first.
    document.getElementById('profilePhotoInput')?.addEventListener('change', async (e) => {
        const files = [...(e.target.files || [])];
        e.target.value = '';
        if (!files.length) return;
        const fd = new FormData();
        files.forEach((f) => fd.append('photos[]', f));
        say('Uploading…');
        try {
            const res = await fetch(@json(route('community.profile.photos.store')), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not upload.');
            const grid = document.getElementById('profilePhotosGrid');
            if (grid && data.data?.html) grid.insertAdjacentHTML('afterbegin', data.data.html);
            syncEmpty('photo');
            say(data.message || 'Added.');
        } catch (err) { say(err.message, 'error'); }
    });

    // Adding a video: XHR so the long compress-and-upload shows the veil the
    // markup always had, and a percent while the bytes actually move.
    document.getElementById('profileVideoInput')?.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        e.target.value = '';
        if (!f) return;
        const veil = document.getElementById('profileVideoUploading');
        veil?.classList.remove('hidden');
        const fd = new FormData();
        fd.append('video', f);
        const x = new XMLHttpRequest();
        x.open('POST', @json(route('community.profile.videos.store')));
        x.setRequestHeader('X-CSRF-TOKEN', CSRF);
        x.setRequestHeader('Accept', 'application/json');
        x.upload.onprogress = (ev) => {
            if (!ev.lengthComputable || !veil) return;
            const pct = Math.round((ev.loaded / ev.total) * 100);
            const label = veil.querySelector('span:last-child');
            // 100% of the bytes up is not saved — the server is still
            // compressing, which is the slow half for a long clip.
            if (label) label.textContent = pct < 100 ? 'Uploading your video… ' + pct + '%' : 'Compressing… this can take a moment for longer clips.';
        };
        x.onload = () => {
            veil?.classList.add('hidden');
            let data = {};
            try { data = JSON.parse(x.responseText); } catch (_) { }
            if (x.status >= 200 && x.status < 300 && data.success) {
                const grid = document.getElementById('profileVideosGrid');
                if (grid && data.data?.html) grid.insertAdjacentHTML('afterbegin', data.data.html);
                syncEmpty('video');
                say(data.message || 'Video added.');
            } else { say(data.message || 'Could not upload the video.', 'error'); }
        };
        x.onerror = () => { veil?.classList.add('hidden'); say('The connection dropped mid-upload — try again.', 'error'); };
        x.send(fd);
    });
})();

document.getElementById('profileTabs')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.profile-tab');
    if (!btn) return;
    const tab = btn.getAttribute('data-tab');
    document.querySelectorAll('#profileTabs .profile-tab').forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.querySelectorAll('[data-tab-panel]').forEach((p) => {
        p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== tab);
    });
});


</script>
@endpush
