@extends('layouts.app')

@section('title', $group->name . ' — Groups')
@section('page-title', $group->name)
@section('page-subtitle', 'Group')
@section('back', route('community.groups.index'))

@push('head')
<style>
    [data-group-member="0"] .post-reply-form { display: none; }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto" data-group-member="{{ $isMember ? 1 : 0 }}" id="groupRoot" data-group-id="{{ $group->id }}">

    {{-- Group header --}}
    <div class="card p-4 mb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-gray-900 leading-snug">{{ $group->name }}</h2>
                @if ($group->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $group->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-2"><span id="memberCount">{{ $memberCount }}</span> {{ \Illuminate\Support\Str::plural('member', $memberCount) }}</p>
            </div>
            @unless ($isOwner)
                <button type="button" id="joinLeaveBtn" class="btn btn-sm shrink-0 {{ $isMember ? 'btn-white' : 'btn-primary' }}"
                        data-joined="{{ $isMember ? 1 : 0 }}">{{ $isMember ? 'Leave' : 'Join' }}</button>
            @else
                <span class="badge badge-green shrink-0">Owner</span>
            @endunless
        </div>
    </div>

    {{-- Composer (members only) --}}
    <div class="card p-4 mb-4 {{ $isMember ? '' : 'hidden' }}" id="composerCard">
        <input type="text" id="postTitle" class="form-input mb-2" maxlength="191" placeholder="Topic title (optional)">
        <textarea id="postBody" class="form-textarea" rows="3" maxlength="8000" placeholder="Share something with the group…"></textarea>
        <div class="flex items-center justify-between gap-2 mt-2">
            <label class="btn btn-white btn-sm cursor-pointer mb-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span id="postImageLabel">Photo</span>
                <input type="file" id="postImage" accept="image/*" capture="environment" class="hidden">
            </label>
            <button type="button" id="postSubmit" class="btn btn-primary btn-sm">Post</button>
        </div>
    </div>

    @unless ($isMember)
        <div class="card p-4 mb-4 text-center" id="joinPrompt">
            <p class="text-sm text-gray-600">Join this group to post and reply.</p>
        </div>
    @endunless

    {{-- Posts --}}
    <div id="postsWrap">
        @if ($posts->isEmpty())
            <div class="card p-6 text-center text-sm text-gray-500" id="postsEmpty">No posts yet — start the conversation.</div>
        @else
            @include('community.groups.partials.posts', ['posts' => $posts, 'group' => $group])
        @endif
    </div>

    @if ($hasMore)
        <div class="text-center mt-2">
            <button type="button" id="loadMoreBtn" class="btn btn-white" data-next="2">Load more</button>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const root = document.getElementById('groupRoot');
    const groupId = root.getAttribute('data-group-id');
    const wrap = document.getElementById('postsWrap');

    // Join / leave.
    document.getElementById('joinLeaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const joined = btn.getAttribute('data-joined') === '1';
        btn.disabled = true;
        try {
            const res = await fetch(`/app/community/groups/${groupId}/${joined ? 'leave' : 'join'}`, { method: 'POST', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) { toast(data.message); location.reload(); }
            else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; }
    });

    // Photo label.
    document.getElementById('postImage')?.addEventListener('change', (e) => {
        document.getElementById('postImageLabel').textContent = e.target.files[0] ? '1 photo' : 'Photo';
    });

    // New post.
    document.getElementById('postSubmit')?.addEventListener('click', async (e) => {
        const body = document.getElementById('postBody').value.trim();
        if (!body) { toast('Write something first.', 'error'); return; }
        const fd = new FormData();
        fd.append('title', document.getElementById('postTitle').value.trim());
        fd.append('body', body);
        const img = document.getElementById('postImage').files[0];
        if (img) fd.append('image', img);
        e.currentTarget.disabled = true;
        try {
            const res = await fetch(`/app/community/groups/${groupId}/post`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('postsEmpty')?.remove();
                wrap.insertAdjacentHTML('afterbegin', data.data.html);
                document.getElementById('postTitle').value = '';
                document.getElementById('postBody').value = '';
                document.getElementById('postImage').value = '';
                document.getElementById('postImageLabel').textContent = 'Photo';
                toast(data.message);
            } else toast(data.message || 'Could not post.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });

    // Reply (delegated).
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.post-reply-form');
        if (!form) return;
        e.preventDefault();
        const input = form.querySelector('input');
        const body = input.value.trim();
        if (!body) return;
        const postId = form.getAttribute('data-post-id');
        input.disabled = true;
        try {
            const res = await fetch(`/app/community/posts/${postId}/reply`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ body }) });
            const data = await res.json();
            if (data.success) {
                form.closest('.group-post').querySelector('.post-replies').insertAdjacentHTML('beforeend', data.data.html);
                input.value = '';
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { input.disabled = false; }
    });

    // Delete post (delegated).
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.post-delete-btn');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete post?', message: 'This removes the post and its replies.', confirmText: 'Delete' });
        if (!ok) return;
        const postId = btn.getAttribute('data-post-id');
        try {
            const res = await fetch(`/app/community/posts/${postId}`, { method: 'DELETE', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) { btn.closest('.group-post').remove(); toast(data.message); }
            else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
    });

    // Load more.
    document.getElementById('loadMoreBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const page = btn.getAttribute('data-next');
        btn.disabled = true; btn.textContent = 'Loading…';
        try {
            const res = await fetch(`/app/community/groups/${groupId}/posts?page=${page}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.success) {
                wrap.insertAdjacentHTML('beforeend', data.data.html);
                if (data.data.hasMore) { btn.setAttribute('data-next', data.data.nextPage); btn.disabled = false; btn.textContent = 'Load more'; }
                else btn.remove();
            }
        } catch (_) { btn.disabled = false; btn.textContent = 'Load more'; toast('Could not load more.', 'error'); }
    });
});
</script>
@endpush
