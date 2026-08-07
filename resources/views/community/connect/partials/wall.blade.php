{{-- Account wall on a member profile. Any member can post; comments too.
     Expects: $member, $isSelf. Posts load via AJAX (load-more pattern). --}}
<div class="card p-4 mb-4" id="wallRoot" data-wall-user="{{ $member->id }}">
    <h3 class="font-bold text-gray-900 mb-3">{{ $isSelf ? 'Your wall' : $member->firstName . "'s wall" }}</h3>

    {{-- Composer --}}
    <div class="mb-4" data-video-host>
        <textarea id="wallBody" class="form-textarea" rows="2" data-mentionable data-preview="#wallPreview"
                  placeholder="{{ $isSelf ? 'Share something…' : 'Write on ' . $member->firstName . "'s wall…" }}"></textarea>
        <div id="wallPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>
        <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold">Remove</button></span>
        <div class="flex items-center justify-between gap-2 mt-2">
            <div class="flex items-center gap-1">
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
            <button type="button" id="wallPostBtn" class="btn btn-primary btn-sm">Post</button>
        </div>
    </div>

    <div id="wallPosts">
        <div class="text-center text-sm text-gray-400 py-4" id="wallLoading">Loading wall…</div>
    </div>
    <div class="text-center mt-1">
        <button type="button" id="wallLoadMore" class="btn btn-white btn-sm hidden" data-next="2" data-infinite>Load more</button>
    </div>
</div>

@include('community.partials.wall-comments-modal')

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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wallRoot');
    if (!root) return;
    const wallUser = root.getAttribute('data-wall-user');
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const wall = document.getElementById('wallPosts');
    const loadMore = document.getElementById('wallLoadMore');

    async function loadPage(page, append) {
        const res = await fetch(`/app/community/members/${wallUser}/wall?page=${page}`, { headers: { Accept: 'application/json' } });
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
