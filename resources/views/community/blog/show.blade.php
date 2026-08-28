@extends('layouts.app')

@section('title', $post->title . ' — Blog')
@section('body-class', 'plaza-ground')
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
    /* --- The head of an article: the cover, then the headline and who wrote
       it, in one piece. The cover used to float inset with rounded corners
       above a bare headline, which on a phone read as a picture that had
       wandered in from somewhere else. --- */
    .article-hero { position:relative; margin-bottom:1.15rem; border-radius:1.1rem; overflow:hidden;
        border:1px solid var(--color-gray-200); background:var(--color-white); box-shadow:var(--shadow-card); }
    .article-hero-body { padding:1.05rem 1.15rem 1.15rem; }
    .article-hero .article-cover { margin:0; border-radius:0; }
    @media (max-width:640px) {
        .article-hero { border-radius:0; border-left:0; border-right:0;
            margin-left:calc(var(--plaza-gutter, 1rem) * -1);
            margin-right:calc(var(--plaza-gutter, 1rem) * -1); }
        /* The breadcrumb trail wrapped to three lines here and said nothing
           the app bar's back arrow and title do not. */
        .bc { display:none; }
    }
    /* Who wrote it, with a face: the wall's way of introducing an author. */
    .article-byline { display:flex; align-items:center; gap:.6rem; margin-top:.85rem;
        padding-top:.8rem; border-top:1px solid var(--color-gray-100); }
    html.dark .article-byline { border-top-color:rgb(255 255 255 / .08); }
    .article-byline .avatar { width:2.1rem; height:2.1rem; font-size:.7rem; flex:none; }
    .article-byline b { display:block; font-size:.82rem; font-weight:800; color:var(--color-gray-800); line-height:1.25; }
    .article-byline i { display:block; font-style:normal; font-size:.72rem; color:var(--color-gray-400); }
    .article-cover { position:relative; border-radius:1rem; overflow:hidden; margin-bottom:1rem; background:var(--color-brand-50); }
    .article-cover img { width:100%; height:auto; display:block; max-height:22rem; object-fit:cover;
        opacity:0; transition:opacity .28s ease; }
    .article-cover img.is-loaded { opacity:1; }
    /* Hold a cover-shaped box and shimmer while the hero decodes, so the
       headline doesn't leap down the page when it lands. */
    .article-cover:not(:has(img.is-loaded)) { aspect-ratio:16/9; max-height:22rem; }
    .article-cover:not(:has(img.is-loaded))::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:articleShimmer 1.15s linear infinite; }
    @keyframes articleShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    @media (prefers-reduced-motion: reduce) {
        .article-cover:not(:has(img.is-loaded))::before { animation:none; background:rgb(255 255 255 / .25); }
    }
    .article-title { font-family:var(--font-heading); font-weight:800; font-size:1.5rem; line-height:1.22; color:var(--color-gray-900); }
    @media (min-width:640px) { .article-title { font-size:1.7rem; } }
    /* The sentence the article was filed under, in the reader's own words. */
    .article-standfirst { margin-top:.45rem; font-size:.92rem; line-height:1.5; color:var(--color-gray-500); }
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

    @php
        $byline = trim((string) $post->authorName) ?: 'anee.io Team';
        $bylineInitials = \Illuminate\Support\Str::of($byline)->explode(' ')
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    @endphp
    <div class="article-hero plaza-accent">
        @if ($post->coverUrl())
            <div class="article-cover"><img src="{{ $post->coverUrl() }}" alt=""
                onload="this.classList.add('is-loaded')" onerror="this.closest('.article-cover')?.remove()"></div>
        @endif
        <div class="article-hero-body">
            <h1 class="article-title">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="article-standfirst">{{ $post->excerpt }}</p>
            @endif
            <div class="article-byline">
                <span class="avatar {{ \App\Support\CommunityAvatar::hue($byline) }}">{{ $bylineInitials }}</span>
                <span class="min-w-0">
                    <b>{{ $byline }}</b>
                    <i>
                        @if ($post->publishedAt){{ $post->publishedAt->format('F j, Y') }} · @endif
                        {{ number_format($post->viewCount) }} {{ \Illuminate\Support\Str::plural('read', $post->viewCount) }}
                    </i>
                </span>
            </div>
        </div>
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
