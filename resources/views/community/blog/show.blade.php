@extends('layouts.app')

@section('title', $post->title . ' — Blog')
@section('page-title', 'Technician\'s Blog')
@section('page-subtitle', \Illuminate\Support\Str::limit($post->title, 40))
@section('back', route('community.blog'))

@push('head')
@include('community.partials.plaza-css')
<style>
    .article { max-width:44rem; margin:0 auto; }
    .bc { display:flex; align-items:center; flex-wrap:wrap; gap:.15rem; font-size:.8rem; margin-bottom:.9rem; }
    .bc a { display:inline-flex; align-items:center; padding:.3rem .55rem; border-radius:.5rem; font-weight:600; color:var(--color-brand-700); text-decoration:none; }
    .bc a:hover { background:var(--color-brand-50); }
    .bc .sep { color:var(--color-gray-300); }
    .bc .cur { padding:.3rem .55rem; color:var(--color-gray-500); font-weight:600; max-width:16rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .article-cover { border-radius:1rem; overflow:hidden; margin-bottom:1rem; background:var(--color-brand-50); }
    .article-cover img { width:100%; height:auto; display:block; max-height:22rem; object-fit:cover; }
    .article-title { font-family:var(--font-heading); font-weight:800; font-size:1.6rem; line-height:1.2; color:var(--color-gray-900); }
    .article-meta { font-size:.8rem; color:var(--color-gray-400); margin:.4rem 0 1rem; display:flex; gap:.8rem; flex-wrap:wrap; }
    .article-body { font-size:.98rem; line-height:1.7; color:var(--color-gray-800); }
    .article-body p { margin:0 0 .9rem; }
    .article-body h3, .article-body h4 { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); margin:1.3rem 0 .5rem; }
    .article-body ul, .article-body ol { margin:0 0 .9rem 1.3rem; }
    .article-body blockquote { border-left:3px solid var(--color-brand-300); padding-left:.9rem; color:var(--color-gray-500); font-style:italic; }
</style>
@endpush

@section('content')
<div class="article">

    {{-- Easy-click breadcrumbs --}}
    <nav class="bc" aria-label="Breadcrumb">
        <a href="{{ route('community.index') }}">🤝 Community</a>
        <span class="sep">›</span>
        <a href="{{ route('community.blog') }}">📰 Tech Blog</a>
        <span class="sep">›</span>
        <span class="cur">{{ $post->title }}</span>
    </nav>

    @if ($post->coverUrl())
        <div class="article-cover"><img src="{{ $post->coverUrl() }}" alt=""></div>
    @endif
    <h1 class="article-title">{{ $post->title }}</h1>
    <div class="article-meta">
        @if ($post->authorName)<span>✍️ {{ $post->authorName }}</span>@endif
        @if ($post->publishedAt)<span>{{ $post->publishedAt->format('F j, Y') }}</span>@endif
        <span>👁️ {{ number_format($post->viewCount) }}</span>
    </div>

    <div class="article-body">{!! \App\Support\CommunityText::safeHtml($post->body) !!}</div>

    {{-- Comments --}}
    <div class="mt-8" id="blogComments">
        <h3 class="font-bold text-gray-900 mb-3" style="font-family:var(--font-heading)">
            Comments <span class="text-gray-400 font-normal" id="blogCommentCount">({{ $comments->count() }})</span>
        </h3>

        <div class="card p-3 mb-4 emoji-scope">
            <textarea id="blogCommentInput" class="form-textarea" rows="3" maxlength="5000" placeholder="Share your thoughts…"></textarea>

            <div id="blogAttach" class="hidden mt-2">
                <div class="flex items-center gap-2">
                    <img id="blogAttachThumb" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-100">
                    <button type="button" id="blogAttachRemove" class="text-xs font-semibold text-gray-400 hover:text-red-500">✕ Remove photo</button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 mt-2">
                <div class="flex items-center gap-1">
                    <button type="button" class="btn btn-white btn-sm js-emoji-btn" data-target="blogCommentInput" title="Add emoji" aria-label="Add emoji">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <label class="btn btn-white btn-sm cursor-pointer mb-0" title="Add a photo">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="file" id="blogCommentImage" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </label>
                </div>
                <button type="button" id="blogCommentSend" class="btn btn-primary btn-sm">Post comment</button>
            </div>
        </div>

        <div id="blogCommentList" class="space-y-3">
            @forelse ($comments as $comment)
                @include('community.blog.partials.comment', ['comment' => $comment])
            @empty
                <p class="text-sm text-gray-400 text-center py-4" id="blogNoComments">Be the first to comment.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.react-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
<script>
(function blogComments() {
    const $ = (id) => document.getElementById(id);
    const input = $('blogCommentInput');
    const fileInput = $('blogCommentImage');
    const attach = $('blogAttach');
    const attachThumb = $('blogAttachThumb');
    const SEND_URL = @json(route('community.blog.comment', ['id' => $post->id]));

    const clearAttach = () => {
        fileInput.value = '';
        attach.classList.add('hidden');
        if (attachThumb.src) { try { URL.revokeObjectURL(attachThumb.src); } catch (_) {} attachThumb.removeAttribute('src'); }
    };
    fileInput.addEventListener('change', () => {
        const f = fileInput.files[0];
        if (f) { attachThumb.src = URL.createObjectURL(f); attach.classList.remove('hidden'); }
        else clearAttach();
    });
    $('blogAttachRemove').addEventListener('click', clearAttach);

    $('blogCommentSend').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const body = input.value.trim();
        const file = fileInput.files[0];
        if (!body && !file) { window.toast && toast('Write a comment or add a photo.', 'error'); return; }
        const prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('is-sending');
        btn.innerHTML = '<span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 plaza-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>Posting…</span>';
        try {
            const fd = new FormData();
            if (body) fd.append('body', body);
            if (file) fd.append('image', file);
            const res = await window.api(SEND_URL, { method: 'POST', body: fd });
            $('blogNoComments')?.remove();
            $('blogCommentList').insertAdjacentHTML('afterbegin', res.data.html);
            window.plazaCommentFx?.animateIn($('blogCommentList').firstElementChild);
            input.value = '';
            clearAttach();
            const c = $('blogCommentCount');
            const n = $('blogCommentList').querySelectorAll('[data-blog-comment]').length;
            if (c) c.textContent = '(' + n + ')';
            window.toast && toast('Comment posted.');
        } catch (err) {
            window.toast && toast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-sending');
            btn.innerHTML = prevHtml;
        }
    });
})();
</script>
@endpush
