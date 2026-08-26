{{-- Account wall on a member profile. Any member can post; comments too.
     Expects: $member, $isSelf. Posts load via AJAX and render with the
     community wall's own card (feed-post), so a post here looks and works —
     comments sheet, views, reactions — exactly as it does on the wall. --}}
<div id="wallRoot" data-wall-user="{{ $member->id }}">
    {{-- The wall's own band: a bordered New post button, not a form lying
         open on somebody's profile. --}}
    <div class="pfw-band">
        <button type="button" id="wallNewPostBtn" class="btn btn-outline btn-sm" title="Write on this wall">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New post
        </button>
    </div>

    <div id="wallPosts">
        <div class="text-center text-sm text-gray-400 py-4" id="wallLoading">Loading…</div>
    </div>
    <div class="text-center mt-1">
        <button type="button" id="wallLoadMore" class="btn btn-white btn-sm hidden" data-next="2" data-infinite>Load more</button>
    </div>
</div>

{{-- The composer, in a sheet dressed like the community wall's: who is
     writing and where, the words, the coloured attach row, and one gradient
     Post running the width. Same ids the posting JS always used. --}}
<div class="sheet hidden" id="pfComposerSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">{{ $isSelf ? 'Write a post' : 'Write on ' . $member->firstName . "'s wall" }}</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" data-video-host>
        <div class="comp-top">
            @include('community.partials.avatar', ['user' => auth()->user(), 'size' => 'avatar-md', 'link' => false])
            <div class="min-w-0">
                <b>{{ auth()->user()->full_name }}</b>
                <i>{{ $isSelf ? 'Posting on your wall' : 'Posting on ' . $member->firstName . "'s wall" }}</i>
            </div>
        </div>
        <textarea id="wallBody" class="form-textarea mt-3" rows="3" data-mentionable data-preview="#wallPreview"
                  placeholder="{{ $isSelf ? 'Share something…' : 'Write on ' . $member->firstName . "'s wall…" }}"></textarea>
        <div id="wallPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>
        <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>
        <div class="flex items-center gap-1 mt-2">
            <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span id="wallImageLabel" class="hidden"></span>
                <input type="file" id="wallImage" accept="image/*" capture="environment" class="hidden">
            </label>
            <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
            <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
            </button>
            <input type="file" class="js-video-file hidden" accept="video/*">
            <button type="button" class="wall-act js-emoji-btn" data-target="wallBody" aria-label="Add an emoji" title="Emoji">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
        </div>
        <button type="button" id="wallPostBtn" class="btn btn-primary comp-send">Post</button>
    </div>
</div>

@include('community.partials.wall-comments-modal')

@push('styles')
<style>
    .pfw-band { display:flex; margin-bottom:1rem; }
    /* The composer head — the same two lines the wall's own sheet opens
       with. Written here because those pages scope their copy to
       themselves. */
    #pfComposerSheet .comp-top { display:flex; align-items:center; gap:.75rem; }
    #pfComposerSheet .comp-top b { display:block; font-size:.9rem; font-weight:800; color:var(--color-gray-900); }
    #pfComposerSheet .comp-top i { display:block; font-style:normal; font-size:.74rem; color:var(--color-gray-500); margin-top:.1rem; }
</style>
@endpush

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.views-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.composer-preview-js')
@include('community.partials.infinite-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wallRoot');
    if (!root) return;
    const wallUser = root.getAttribute('data-wall-user');
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const wall = document.getElementById('wallPosts');
    const loadMore = document.getElementById('wallLoadMore');

    // The wall's own card, asked for by name: the server renders feed-post
    // for these pages, so a profile post is the community wall's post.
    async function loadPage(page, append) {
        const res = await fetch(`/app/community/members/${wallUser}/wall?page=${page}&render=feed`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (append) wall.insertAdjacentHTML('beforeend', data.data.html);
        else wall.innerHTML = data.data.html || '<div class="text-center text-sm text-gray-400 py-4" id="wallEmpty">No wall posts yet.</div>';
        if (data.data.hasMore) { loadMore.classList.remove('hidden'); loadMore.setAttribute('data-next', data.data.nextPage); }
        else loadMore.classList.add('hidden');
    }
    loadPage(1, false)
        .then(() => deepLinkToPost())
        .catch(() => { document.getElementById('wallLoading')?.remove(); });

    // Bell notifications deep-link to #wallpost-N. Load more pages until the
    // post appears, then scroll to it and flash a highlight.
    async function deepLinkToPost(tries = 0) {
        const m = (location.hash || '').match(/^#wallpost-(\d+)$/);
        if (!m) return;
        const el = document.getElementById('wallpost-' + m[1]);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.remove('flash-target'); void el.offsetWidth; el.classList.add('flash-target');
            return;
        }
        if (tries < 5 && !loadMore.classList.contains('hidden')) {
            await loadPage(loadMore.getAttribute('data-next'), true);
            return deepLinkToPost(tries + 1);
        }
    }

    document.getElementById('wallNewPostBtn')?.addEventListener('click', () => {
        window.openSheet?.('pfComposerSheet');
        // No auto-focus: the phone keypad waits for a tap on the field.
    });

    document.getElementById('wallImage')?.addEventListener('change', (e) => {
        const lbl = document.getElementById('wallImageLabel');
        if (e.target.files[0]) { lbl.textContent = '1 photo'; lbl.className = 'text-xs font-semibold text-emerald-600'; }
        else { lbl.textContent = ''; lbl.className = 'hidden'; }
    });

    document.getElementById('wallPostBtn')?.addEventListener('click', async (e) => {
        const host = document.getElementById('wallBody').closest('[data-video-host]');
        const body = document.getElementById('wallBody').value.trim();
        const img = document.getElementById('wallImage').files[0];
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!body && !img && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const fd = new FormData();
        fd.append('render', 'feed');   // hand back the wall's own card
        if (body) fd.append('body', body);
        if (img) fd.append('image', img);
        if (vid) fd.append('video', vid);
        const btn = e.currentTarget;
        const prev = btn.textContent;
        btn.disabled = true;
        btn.textContent = vid ? 'Uploading…' : 'Posting…';
        try {
            const res = await fetch(`/app/community/members/${wallUser}/wall`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('wallEmpty')?.remove();
                wall.insertAdjacentHTML('afterbegin', data.data.html);
                window.closeSheet?.('pfComposerSheet');
                document.getElementById('wallBody').value = '';
                document.getElementById('wallImage').value = '';
                document.getElementById('wallImageLabel').textContent = 'Photo';
                if (window.plazaClearVideo) window.plazaClearVideo(host);
                toast(data.message);
            } else toast(data.message || 'Could not post.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.textContent = prev; }
    });

    // Load more.
    loadMore?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const page = btn.getAttribute('data-next');
        btn.disabled = true; btn.textContent = 'Loading…';
        try { await loadPage(page, true); }
        finally { btn.disabled = false; btn.textContent = 'Load more'; }
    });
});
</script>
@endpush
