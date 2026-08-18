@extends('layouts.app')

@section('title', $group->name . ' — Discussions')
@section('page-title', $group->name)
@section('page-subtitle', 'Discussion')
@section('back', route('community.groups.index'))

@php use App\Support\CommunityAvatar; @endphp

@push('head')
@include('community.partials.plaza-css')
<style>
    /* WYSIWYG topic editor — formatting toolbar, NO raw-HTML source. */
    #postBodyEditor { background:#fff; border:1px solid #d1d5db; border-top:0; border-radius:0 0 .6rem .6rem; }
    /* Height and look come from the shared rules in app.css. */
    #postBodyEditor .ql-editor.ql-blank::before { font-style:normal; color:#9ca3af; left:.75rem; }
    .ql-toolbar.ql-snow { border:1px solid #d1d5db; border-radius:.6rem .6rem 0 0; }
    html.dark #postBodyEditor, html.dark .ql-toolbar.ql-snow { background:#171f10; border-color:#2b3a1c; }
    html.dark #postBodyEditor .ql-editor { color:#e5e9df; }
    html.dark .ql-snow .ql-stroke { stroke:#9fb389; }
    html.dark .ql-snow .ql-fill { fill:#9fb389; }
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
    .disc-hero { padding:.85rem; margin-bottom:.75rem; }
    .disc-hero-row { display:flex; align-items:flex-start; gap:.7rem; }
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

    /* --- Topics / Group chat: a segmented switch, both halves always visible,
       with the selected half carried across on the house easing. --- */
    .disc-seg { position:relative; display:grid; grid-template-columns:1fr 1fr; gap:0; padding:.25rem;
        margin-bottom:.85rem; background:var(--color-gray-100); border-radius:999px; }
    html.dark .disc-seg { background:rgb(255 255 255 / .07); }
    .disc-seg::before { content:''; position:absolute; z-index:0; top:.25rem; bottom:.25rem; left:.25rem;
        width:calc(50% - .25rem); border-radius:999px; background:var(--color-white, #fff);
        box-shadow:0 1px 3px rgb(0 0 0 / .12); transition:transform var(--dur) var(--ease-house); }
    html.dark .disc-seg::before { background:#25301c; }
    .disc-seg[data-active="chat"]::before { transform:translateX(100%); }
    .disc-seg-btn { position:relative; z-index:1; border:0; background:transparent; cursor:pointer;
        min-height:2.4rem; padding:.35rem .5rem; border-radius:999px; font-size:.85rem; font-weight:700;
        color:var(--color-gray-500); display:flex; align-items:center; justify-content:center; gap:.35rem;
        transition:color var(--dur) var(--ease-house); }
    .disc-seg-btn.is-selected { color:var(--color-brand-700); }
    html.dark .disc-seg-btn.is-selected { color:var(--color-brand-300); }
    .disc-seg-btn .seg-ico { font-size:.95rem; line-height:1; }

    /* Composer: on a phone the editor gets the whole width — the avatar was
       spending 3rem of a 360px screen to say who is typing. */
    .disc-composer { padding:.85rem; }
    @media (max-width:479px) {
        .disc-composer .disc-composer-av { display:none; }
        .disc-composer .ql-toolbar.ql-snow { padding:.25rem; }
    }
    @media (min-width:640px) { .disc-composer { padding:1rem; } }

    /* Topics: the replies sit below a divider, and nothing is glued to it. */
    .post-thread { margin-top:.9rem; padding-top:.85rem; border-top:1px solid var(--color-gray-100); }
    html.dark .post-thread { border-top-color:rgb(255 255 255 / .08); }
    .post-thread-more { display:inline-block; margin:.15rem 0 .55rem; border:0; background:transparent; padding:0;
        font-size:.75rem; font-weight:800; color:var(--color-brand-700); cursor:pointer; }
    .post-thread .replies-label { margin-bottom:.45rem; }
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
        .disc-seg::before, .disc-seg-btn { transition:none; }
        /* A loader that stops looks like a page that broke; slow it instead. */
        .disc-spin i { animation-duration:2.6s; }
    }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'groups'])
<div data-group-member="{{ $isMember ? 1 : 0 }}" id="groupRoot" data-group-id="{{ $group->id }}">

    {{-- Group header: the place banner --}}
    <div class="card disc-hero group-hero {{ CommunityAvatar::hue($group->name) }}">
        <div class="disc-hero-row">
            <span class="avatar avatar-md avatar-sq overflow-hidden {{ CommunityAvatar::hue($group->name) }}">@if ($group->coverImagePath)<img src="{{ \App\Support\MediaStore::url($group->coverImagePath) }}" alt="" class="w-full h-full object-cover">@else{{ CommunityAvatar::monogram($group->name) }}@endif</span>
            <div class="min-w-0 grow">
                <h2 class="disc-hero-title">{{ $group->name }}</h2>
                <p class="disc-hero-meta">
                    <span>🧑‍🌾 <span id="memberCount">{{ $memberCount }}</span> {{ \Illuminate\Support\Str::plural('member', $memberCount) }}</span>
                    <span id="heroMemberTag" class="{{ $isMember ? '' : 'hidden' }}">✓ Kasali ka</span>
                </p>
            </div>
            @unless ($isOwner)
                <button type="button" id="joinLeaveBtn" class="btn btn-sm shrink-0 {{ $isMember ? 'btn-white' : 'btn-primary' }}"
                        data-joined="{{ $isMember ? 1 : 0 }}">{{ $isMember ? 'Leave' : 'Join' }}</button>
            @else
                <span class="badge badge-green shrink-0">Owner</span>
            @endunless
        </div>
        @if ($group->description)
            {{-- Clamped by default: the description is context, not the room. --}}
            <p class="disc-hero-desc" id="heroDesc">{{ $group->description }}</p>
            <button type="button" class="disc-hero-more" id="heroDescMore" hidden>Show more</button>
        @endif
    </div>

    {{-- Tabs: Discussion topics vs. live group chat --}}
    <div class="disc-seg" id="groupTabs" data-active="discussion" role="tablist" aria-label="Discussion or group chat">
        <button type="button" class="disc-seg-btn is-selected" data-tab="discussion" role="tab" aria-selected="true" aria-controls="paneDiscussion">
            <span class="seg-ico" aria-hidden="true">🗂️</span>Topics
        </button>
        <button type="button" class="disc-seg-btn" data-tab="chat" role="tab" aria-selected="false" aria-controls="paneChat">
            <span class="seg-ico" aria-hidden="true">💬</span>Group Chat
        </button>
    </div>

    <div id="paneDiscussion" role="tabpanel">
    {{-- Composer (members only) --}}
    <div class="card disc-composer mb-4 plaza-accent {{ $isMember ? '' : 'hidden' }}" id="composerCard">
        <div class="flex items-start gap-3">
            <span class="avatar avatar-md disc-composer-av {{ CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} mt-1">{{ auth()->user()->initials ?? '?' }}</span>
            <div class="min-w-0 grow">
                <input type="text" id="postTitle" class="form-input mb-2" maxlength="191" required placeholder="Topic title *">
                {{-- WYSIWYG topic body (Quill). Format with the toolbar — bold,
                     lists, links, quotes — no raw HTML editor. --}}
                <div id="postBodyEditor" data-placeholder="Magtanong o magbahagi sa usapan… Format mo kung kailangan."></div>
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
                <button type="button" class="wall-act js-emoji-btn" data-target="postBody" aria-label="Add an emoji" title="Emoji">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
            <button type="button" id="postSubmit" class="btn btn-primary btn-sm">Post</button>
            </div>
            </div>
        </div>
    </div>

    @unless ($isMember)
        <div class="card p-5 mb-4 text-center" id="joinPrompt">
            <div class="empty-tile" style="width:3.5rem;height:3.5rem;font-size:1.5rem;">🌾</div>
            <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Members lang muna dito</p>
            <p class="text-sm text-gray-600 mt-1 mb-3">Join {{ $group->name }} para maka-post at maka-reply.</p>
            <button type="button" class="btn btn-primary w-full" id="joinFromGate">Sali sa usapan</button>
        </div>
    @endunless

    {{-- Posts --}}
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

    @if ($posts->isNotEmpty())
        {{-- The reader meets the next page already there; the button stays for
             a deliberate retry (and for anyone driving by keyboard). --}}
        <div class="disc-tail" id="postsTail">
            <button type="button" id="loadMoreBtn" class="btn btn-white btn-sm" data-next="2" data-infinite @unless ($hasMore) hidden @endunless>Show older topics</button>
            <div class="disc-spin" id="postsSpin" role="status" aria-label="Loading older topics" hidden><i></i><i></i><i></i></div>
            <p class="disc-end" id="postsEnd" @if ($hasMore) hidden @endif>🌾 Nasa dulo ka na — iyan ang buong usapan.</p>
        </div>
    @endif

    @if ($isMember)
        <button type="button" class="write-fab is-hidden" id="writeFab" aria-label="Write a post">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
    @endif
    </div>{{-- /paneDiscussion --}}

    {{-- ===================== GROUP CHAT PANE ===================== --}}
    <div id="paneChat" class="hidden" role="tabpanel">
        @if ($isMember)
            <div class="chat-shell">
            <div class="card overflow-hidden chat-card" style="display:flex;flex-direction:column;">
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 shrink-0">
                    <span class="font-bold text-gray-900 text-sm">Group chat</span>
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
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.video-js')
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
            btn.textContent = join ? 'Leave' : 'Join';
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
            document.getElementById('writeFab')?.classList.remove('is-hidden');
        } else {
            composer.classList.add('hidden');
        }
        return true;
    }

    document.getElementById('joinLeaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const joined = btn.getAttribute('data-joined') === '1';
        btn.disabled = true;
        try { await setMembership(!joined); }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; }
    });
    document.getElementById('joinFromGate')?.addEventListener('click', async (e) => {
        e.currentTarget.disabled = true;
        try { await setMembership(true); }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });
    // Joining from the chat tab's gate: the chat itself only arrives on a
    // reload, so say so rather than leaving a dead pane behind.
    document.getElementById('joinFromChat')?.addEventListener('click', async (e) => {
        e.currentTarget.disabled = true;
        try { if (await setMembership(true)) window.location.reload(); }
        catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });

    /* ---------------- composer: WYSIWYG body + photo chip ---------------- */
    // Minimal toolbar — formatting only, deliberately no code-block / HTML source.
    let bodyQuill = null;
    const editorEl = document.getElementById('postBodyEditor');
    if (editorEl && typeof Quill !== 'undefined') {
        bodyQuill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: editorEl.getAttribute('data-placeholder') || '',
            // The app's one toolbar (resources/js/app.js) — a post is written
            // the same way a note is.
            modules: { toolbar: window.SM_RICH_TOOLBAR },
        });
        window.smQuillTouch?.(bodyQuill);
    }
    const getBodyHtml = () => bodyQuill ? bodyQuill.root.innerHTML : '';
    const getBodyText = () => bodyQuill ? bodyQuill.getText().trim() : '';
    const clearBody = () => { if (bodyQuill) bodyQuill.setContents([]); };

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
        const titleVal = document.getElementById('postTitle').value.trim();
        if (!titleVal) { toast('Add a topic title.', 'error'); document.getElementById('postTitle').focus(); return; }
        const text = getBodyText();
        if (!text) { toast('Write something first.', 'error'); return; }
        const fd = new FormData();
        fd.append('title', titleVal);
        fd.append('body', getBodyHtml());
        if (imgInput.files && imgInput.files[0]) fd.append('image', imgInput.files[0]);
        e.currentTarget.disabled = true;
        try {
            const res = await fetch(`/app/community/groups/${groupId}/post`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('postsEmpty')?.remove();
                wrap.insertAdjacentHTML('afterbegin', data.data.html);
                wrap.firstElementChild?.classList.add('post-enter');
                document.getElementById('postTitle').value = '';
                clearBody();
                clearAttach();
                toast(data.message);
            } else toast(data.message || 'Could not post.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
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
        if (!text && !file) { toast('Write something or add a photo.', 'error'); return; }
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
        if (parentId) fd.append('parentId', parentId);
        const sendBtn = form.querySelector('button[type="submit"]');
        input.disabled = true;
        window.plazaCommentFx?.startSending(sendBtn);
        try {
            const res = await fetch(`/app/community/posts/${postId}/reply`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                if (parentId) {
                    // The nested reply lands where the temporary form was.
                    form.insertAdjacentHTML('beforebegin', data.data.html);
                    form.previousElementSibling?.classList.add('post-enter');
                    form.remove();
                } else {
                    const replies = form.closest('.group-post').querySelector('.post-replies');
                    replies.insertAdjacentHTML('beforeend', data.data.html);
                    replies.lastElementChild?.classList.add('post-enter');
                    input.value = '';
                    if (fileInput) fileInput.value = '';
                    window.plazaSetChip(form, null);
                    input.focus();
                }
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { input.disabled = false; window.plazaCommentFx?.stopSending(sendBtn); }
    });

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
            </span>
            <button type="button" class="js-reply-cancel btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 shrink-0" aria-label="Cancel reply" title="Cancel">${SVG_R_X}</button>
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
        } else if (mId && mName && !form.dataset.mentionId) {
            form.dataset.mentionId = mId;
            form.dataset.mentionName = mName;
            form.insertAdjacentHTML('afterbegin', groupMentionPill(mName));
        }
        form.querySelector('input[type="text"]').focus();
    });

    // Remove the "replying to @Name" pill (and drop the pending mention).
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.js-reply-mention-x');
        if (!x) return;
        const form = x.closest('form.post-reply-form');
        if (form) { delete form.dataset.mentionId; delete form.dataset.mentionName; }
        x.closest('.reply-mention-pill')?.remove();
    });

    /* Cancel a reply → discard the text and remove the field. */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-reply-cancel');
        if (!btn) return;
        const form = btn.closest('form.post-reply-form');
        if (form && form.getAttribute('data-parent-id')) form.remove();
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
    let loading = false;
    let done = !moreBtn || moreBtn.hidden;

    function finishPosts() {
        done = true;
        moreBtn?.remove();
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
            const res = await fetch(`/app/community/groups/${groupId}/posts?page=${page}`, { headers: { Accept: 'application/json' } });
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
    moreBtn?.addEventListener('click', loadMore);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    nearTail();   // a short room can end with the tail already in view

    /* ---------------- write-FAB: shows when the composer scrolls away ---------------- */
    const fab = document.getElementById('writeFab');
    const composerCard = document.getElementById('composerCard');
    if (fab && composerCard && 'IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            fab.classList.toggle('is-hidden', entry.isIntersecting || composerCard.classList.contains('hidden'));
        }, { threshold: 0 }).observe(composerCard);
        fab.addEventListener('click', () => {
            composerCard.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            // The composer's first field is the title — the old `body` var died
            // when the body became a Quill editor, and focusing an undefined
            // threw before the keyboard could come up.
            window.smFocus?.(document.getElementById('postTitle'), { delay: reduceMotion ? 0 : 350 });
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
    const tabs = document.getElementById('groupTabs');
    let chatStarted = false, lastId = 0, pollTimer = null;

    tabs?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-tab]');
        if (!btn) return;
        const chat = btn.getAttribute('data-tab') === 'chat';
        tabs.querySelectorAll('.disc-seg-btn').forEach((c) => {
            const on = c === btn;
            c.classList.toggle('is-selected', on);
            c.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        tabs.setAttribute('data-active', chat ? 'chat' : 'discussion');
        paneChat.classList.toggle('hidden', !chat);
        paneDiscussion.classList.toggle('hidden', chat);
        if (chat) {
            // The chat is a full-height column: park its top under the app bar
            // so the composer lands above the tab bar instead of below the fold.
            paneChat.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            // A guest's pane is the members-only card; the chat endpoints 403
            // for them, so don't ask.
            if (isMember && !chatStarted) startChat();
        }
    });

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

    // "View all replies" → reveal the collapsed (older) replies inline with a
    // small staggered animation (accordion, no modal).
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-view-all-replies');
        if (!btn) return;
        const post = btn.closest('.group-post');
        const replies = post && post.querySelector('.post-replies');
        if (!replies) return;
        const hidden = Array.from(replies.querySelectorAll(':scope > .group-reply')).slice(0, -3);
        replies.classList.remove('is-collapsed');
        hidden.forEach((el, i) => {
            el.classList.add('comment-reveal');
            el.style.animationDelay = Math.min(i * 45, 400) + 'ms';
            el.addEventListener('animationend', () => { el.classList.remove('comment-reveal'); el.style.animationDelay = ''; }, { once: true });
        });
        btn.remove();
    });

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
</script>
@endpush
