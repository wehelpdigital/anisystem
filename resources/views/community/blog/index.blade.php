@extends('layouts.app')

@section('title', 'Technician\'s Blog — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Technician\'s Blog')

@push('head')
@include('community.partials.plaza-css')
<style>
    .blog-grid { display:grid; grid-template-columns:1fr; gap:1rem; }
    @media (min-width:640px) { .blog-grid { grid-template-columns:repeat(2,1fr); } }
    @media (min-width:1024px) { .blog-grid { grid-template-columns:repeat(3,1fr); } }
    .blog-card { display:flex; flex-direction:column; overflow:hidden; border-radius:1rem; border:1px solid var(--color-gray-100);
        background:var(--color-white); box-shadow:var(--shadow-card); text-decoration:none; transition:transform .12s ease, box-shadow .15s ease; }
    .blog-card:hover { transform:translateY(-2px); box-shadow:0 10px 30px -12px rgb(0 0 0 / .25); }
    .blog-cover { aspect-ratio:16/9; background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50)); overflow:hidden; }
    .blog-cover img { width:100%; height:100%; object-fit:cover; }
    .blog-cover-fallback { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:2.4rem; }
    .blog-body { padding:.85rem 1rem 1rem; display:flex; flex-direction:column; gap:.35rem; }
    .blog-title { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .blog-excerpt { font-size:.83rem; color:var(--color-gray-500); line-height:1.4; }
    .blog-meta { margin-top:.4rem; font-size:.72rem; color:var(--color-gray-400); display:flex; gap:.6rem; flex-wrap:wrap; }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'blog'])

<div class="card p-4 mb-4 flex items-center gap-3">
    <div class="avatar avatar-lg" style="font-size:1.4rem;background:var(--color-brand-50)">📰</div>
    <div class="min-w-0">
        <h2 class="font-bold text-gray-900 text-lg" style="font-family:var(--font-heading)">Technician's Blog</h2>
        <p class="text-sm text-gray-500">Guides and advice from the AniSenso team.</p>
    </div>
</div>

@if ($posts->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">📰</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No articles yet</p>
        <p class="text-sm text-gray-500 mt-1">The team hasn't published anything yet — check back soon.</p>
    </div>
@else
    <div class="blog-grid">
        @foreach ($posts as $post)
            <a href="{{ route('community.blog.show', ['id' => $post->id]) }}" class="blog-card">
                <div class="blog-cover">
                    @if ($post->coverUrl())
                        <img src="{{ $post->coverUrl() }}" alt="" loading="lazy">
                    @else
                        <div class="blog-cover-fallback">🌾</div>
                    @endif
                </div>
                <div class="blog-body">
                    <span class="blog-title">{{ $post->title }}</span>
                    @if ($post->excerpt)<span class="blog-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</span>@endif
                    <span class="blog-meta">
                        @if ($post->authorName)<span>✍️ {{ $post->authorName }}</span>@endif
                        @if ($post->publishedAt)<span>{{ $post->publishedAt->format('M j, Y') }}</span>@endif
                        <span>💬 {{ $post->comments_count }}</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $posts->links('community.partials.blog-pagination') }}</div>
@endif
@endsection
