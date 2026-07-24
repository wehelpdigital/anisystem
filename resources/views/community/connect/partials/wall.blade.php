{{-- Account wall on a member profile. Any member can post; comments too.
     Expects: $member, $isSelf. Posts load via AJAX (load-more pattern). --}}
<div class="card p-4 mb-4" id="wallRoot" data-wall-user="{{ $member->id }}">
    <h3 class="font-bold text-gray-900 mb-3">{{ $isSelf ? 'Your wall' : $member->firstName . "'s wall" }}</h3>

    {{-- Composer --}}
    <div class="mb-4">
        <textarea id="wallBody" class="form-textarea" rows="2"
                  placeholder="{{ $isSelf ? 'Share something…' : 'Write on ' . $member->firstName . "'s wall…" }}"></textarea>
        <div class="flex items-center justify-between gap-2 mt-2">
            <label class="btn btn-white btn-sm cursor-pointer mb-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span id="wallImageLabel">Photo</span>
                <input type="file" id="wallImage" accept="image/*" capture="environment" class="hidden">
            </label>
            <button type="button" id="wallPostBtn" class="btn btn-primary btn-sm">Post</button>
        </div>
    </div>

    <div id="wallPosts">
        <div class="text-center text-sm text-gray-400 py-4" id="wallLoading">Loading wall…</div>
    </div>
    <div class="text-center mt-1">
        <button type="button" id="wallLoadMore" class="btn btn-white btn-sm hidden" data-next="2">Load more</button>
    </div>
</div>

@push('scripts')
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
    loadPage(1, false).catch(() => { document.getElementById('wallLoading')?.remove(); });

    document.getElementById('wallImage')?.addEventListener('change', (e) => {
        document.getElementById('wallImageLabel').textContent = e.target.files[0] ? '1 photo' : 'Photo';
    });

    document.getElementById('wallPostBtn')?.addEventListener('click', async (e) => {
        const body = document.getElementById('wallBody').value.trim();
        const img = document.getElementById('wallImage').files[0];
        if (!body && !img) { toast('Write something or add a photo.', 'error'); return; }
        const fd = new FormData();
        if (body) fd.append('body', body);
        if (img) fd.append('image', img);
        e.currentTarget.disabled = true;
        try {
            const res = await fetch(`/app/community/members/${wallUser}/wall`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('wallEmpty')?.remove();
                wall.insertAdjacentHTML('afterbegin', data.data.html);
                document.getElementById('wallBody').value = '';
                document.getElementById('wallImage').value = '';
                document.getElementById('wallImageLabel').textContent = 'Photo';
                toast(data.message);
            } else toast(data.message || 'Could not post.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });

    // Comment (delegated).
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.wall-comment-form');
        if (!form) return;
        e.preventDefault();
        const input = form.querySelector('input');
        const body = input.value.trim();
        if (!body) return;
        const postId = form.getAttribute('data-post-id');
        input.disabled = true;
        try {
            const res = await fetch(`/app/community/wall/${postId}/comment`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ body }) });
            const data = await res.json();
            if (data.success) { form.closest('.wall-post').querySelector('.wall-comments').insertAdjacentHTML('beforeend', data.data.html); input.value = ''; }
            else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { input.disabled = false; }
    });

    // Delete post (delegated).
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.wall-delete-btn');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete post?', message: 'This removes the post and its comments.', confirmText: 'Delete' });
        if (!ok) return;
        const postId = btn.getAttribute('data-post-id');
        try {
            const res = await fetch(`/app/community/wall/${postId}`, { method: 'DELETE', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) { btn.closest('.wall-post').remove(); toast(data.message); }
            else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
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
