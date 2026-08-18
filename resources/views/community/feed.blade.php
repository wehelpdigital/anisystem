@extends('layouts.app')

@section('title', 'Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Kamustahan ng mga magsasaka')

@push('head')
@include('community.partials.plaza-css')
<style>
    /* Feed + a single right rail on wide screens (co-farmer requests, recent
       discussions, sponsors). The rail folds away on tablet/mobile so the feed
       stays full-width; with no left column the wall itself is wider. */
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
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'wall'])

<div class="plaza-shell">
{{-- CENTER — the feed (now full width beside a single right rail) --}}
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
{{-- Share to your own wall, straight from the feed --}}
<div class="card p-4 mb-4 plaza-accent" id="feedComposer" data-video-host>
    <div class="flex items-start gap-3">
        @include('community.partials.avatar', ['user' => auth()->user(), 'size' => 'avatar-md', 'link' => false])
        <div class="min-w-0 grow">
            <textarea id="feedPostBody" class="form-textarea" rows="2" maxlength="4000" data-mentionable data-preview="#feedPreview"
                placeholder="Kamusta ang bukid mo ngayon, {{ auth()->user()->firstName }}? Use @ to mention, # to tag"></textarea>
            <div id="feedPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>
            <span class="attach-chip hidden mt-2" id="feedChip"><span id="feedChipName" class="text-xs font-semibold text-gray-700 truncate"></span><button type="button" id="feedChipClear" class="btn-ghost rounded-full w-6 h-6 flex items-center justify-center text-gray-400 hover:text-red-500" aria-label="Remove photo">✕</button></span>
            <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-1">
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
                <button type="button" id="feedPostSubmit" class="btn btn-primary btn-sm">Share on my wall</button>
            </div>
        </div>
    </div>
</div>

@include('community.partials.recommended', ['recommendations' => $recommendations])

{{-- The feed: friends and kapit-bahay provinces first --}}
<div id="feedWrap">
    @forelse ($posts as $post)
        @include('community.partials.feed-post', ['post' => $post, 'friendIds' => $friendIds])
    @empty
        <div class="card p-8 text-center">
            <div class="empty-tile">🏠</div>
            <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Tahimik pa ang kapitbahayan</p>
            <p class="text-sm text-gray-500 mt-1">Ikaw ang mauna — share what's happening sa bukid mo.</p>
        </div>
    @endforelse
</div>
@if ($posts->isNotEmpty())
    <div class="text-center mt-1">
        <button type="button" id="feedLoadMore" class="btn btn-white btn-sm" data-infinite
                data-before="{{ optional($posts->last()->created_at)->toIso8601String() }}">Load more</button>
    </div>
@endif
</div>{{-- /plaza-center --}}

{{-- RIGHT rail — co-farmer requests, recent discussions + sponsored slot --}}
<aside class="plaza-side plaza-side-right">
    @include('community.partials.side-requests', ['requests' => $friendRequests, 'requestCount' => $friendRequestCount])
    @include('community.partials.side-groups', ['groups' => $recentGroups])
    @if ($sponsors->isNotEmpty())
        @include('community.partials.side-sponsors', ['sponsors' => $sponsors])
    @endif
</aside>
</div>{{-- /plaza-shell --}}

@include('community.partials.wall-comments-modal')
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

    // Infinite scroll: append older posts, deduping any the ranked page showed.
    document.getElementById('feedLoadMore')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        if (btn.disabled) return;
        btn.disabled = true; btn.textContent = 'Loading…';
        try {
            const res = await fetch(@json(route('community.feed-more')) + '?before=' + encodeURIComponent(btn.getAttribute('data-before') || ''), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const tmp = document.createElement('div');
            tmp.innerHTML = data.data.html;
            const wrap = document.getElementById('feedWrap');
            [...tmp.querySelectorAll('.feed-post')].forEach((el) => { if (!document.getElementById(el.id)) wrap.appendChild(el); });
            if (data.data.hasMore && data.data.before) { btn.setAttribute('data-before', data.data.before); btn.disabled = false; btn.textContent = 'Load more'; }
            else btn.remove();
        } catch (_) { btn.disabled = false; btn.textContent = 'Load more'; toast('Could not load more.', 'error'); }
    });
    const fileInput = document.getElementById('feedImage');
    const chip = document.getElementById('feedChip');

    fileInput?.addEventListener('change', () => {
        const f = fileInput.files[0];
        chip.classList.toggle('hidden', !f);
        if (f) document.getElementById('feedChipName').textContent = f.name;
    });
    document.getElementById('feedChipClear')?.addEventListener('click', () => {
        fileInput.value = ''; chip.classList.add('hidden');
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
