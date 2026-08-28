@extends('layouts.app')

@section('title', 'Tutorials')
@section('page-title', 'Tutorials')
@section('page-subtitle', 'Learn anee.io')

@push('head')
<style>
    .tut-cat { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); margin:1.4rem 0 .7rem; }
    .tut-cat:first-of-type { margin-top:.4rem; }
    .tut-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(15rem,1fr)); gap:1rem; }
    .tut-card { cursor:pointer; overflow:hidden; border-radius:1rem; border:1px solid var(--color-gray-100);
        background:var(--color-white); box-shadow:var(--shadow-card); transition:transform .12s ease, box-shadow .15s ease; }
    .tut-card:hover { transform:translateY(-2px); box-shadow:0 10px 30px -12px rgb(0 0 0 / .25); }
    .tut-thumb { position:relative; aspect-ratio:16/9; background:#000; overflow:hidden; }
    .tut-thumb img { width:100%; height:100%; object-fit:cover; opacity:.92; transition:opacity .2s ease; }
    .tut-card:hover .tut-thumb img { opacity:1; }
    .tut-play { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; }
    .tut-play span { width:3.2rem; height:3.2rem; border-radius:999px; background:rgb(0 0 0 / .55); color:#fff;
        display:flex; align-items:center; justify-content:center; font-size:1.3rem; padding-left:.2rem; transition:transform .15s ease, background .2s ease; }
    .tut-card:hover .tut-play span { transform:scale(1.08); background:var(--color-brand-600); }
    .tut-info { padding:.75rem .9rem .9rem; }
    .tut-title { font-weight:700; color:var(--color-gray-900); line-height:1.25; font-size:.92rem; }
    .tut-desc { font-size:.8rem; color:var(--color-gray-500); margin-top:.2rem; }

    /* Player modal */
    .tut-modal { position:fixed; inset:0; z-index:120; display:none; align-items:center; justify-content:center; padding:1rem;
        background:rgb(0 0 0 / .82); backdrop-filter:blur(2px); }
    .tut-modal.show { display:flex; animation:tutFade .2s ease; }
    @keyframes tutFade { from { opacity:0; } to { opacity:1; } }
    .tut-stage { width:100%; max-width:56rem; }
    .tut-stage-inner { position:relative; border-radius:1rem; overflow:hidden; background:#000;
        box-shadow:0 24px 60px -20px rgb(0 0 0 / .7); animation:tutRise .28s cubic-bezier(.22,1,.36,1); }
    @keyframes tutRise { from { opacity:0; transform:translateY(16px) scale(.98); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .tut-modal.show, .tut-stage-inner { animation:none; } }
    .tut-stage-inner iframe { width:100%; aspect-ratio:16/9; border:0; display:block; }
    .tut-bar { display:flex; align-items:center; gap:.5rem; padding:.6rem .3rem 0; color:#fff; }
    .tut-bar .tut-modal-title { font-weight:700; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .tut-bar button { color:#fff; background:rgb(255 255 255 / .12); border-radius:.5rem; padding:.35rem .6rem; margin-left:auto; }
    .tut-bar button:hover { background:rgb(255 255 255 / .22); }
    .tut-bar .tut-fs { margin-left:0; }
</style>
@endpush

@section('content')
@php $hasAny = collect($grouped)->flatten()->isNotEmpty(); @endphp

@if (! $hasAny)
    <div class="card p-8 text-center">
        <div class="empty-tile">🎬</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No tutorials yet</p>
        <p class="text-sm text-gray-500 mt-1">Video guides will appear here soon.</p>
    </div>
@else
    @foreach ($grouped as $category => $items)
        <h2 class="tut-cat">{{ $category }}</h2>
        <div class="tut-grid">
            @foreach ($items as $tut)
                <div class="tut-card" data-yt="{{ $tut->youtubeId }}" data-title="{{ $tut->title }}" tabindex="0" role="button" aria-label="Play {{ $tut->title }}">
                    <div class="tut-thumb">
                        @if ($tut->coverUrl())
                            <img src="{{ $tut->coverUrl() }}" alt="" loading="lazy">
                        @endif
                        <div class="tut-play"><span>▶</span></div>
                    </div>
                    <div class="tut-info">
                        <div class="tut-title">{{ $tut->title }}</div>
                        @if ($tut->description)<div class="tut-desc">{{ \Illuminate\Support\Str::limit($tut->description, 90) }}</div>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- Player modal --}}
    <div class="tut-modal" id="tutModal">
        <div class="tut-stage">
            <div class="tut-stage-inner" id="tutStageInner"></div>
            <div class="tut-bar">
                <span class="tut-modal-title" id="tutModalTitle"></span>
                <button type="button" class="tut-fs" id="tutFullscreen">⛶ Fullscreen</button>
                <button type="button" id="tutClose">✕ Close</button>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function tutorials() {
    const modal = document.getElementById('tutModal');
    if (!modal) return;
    const stage = document.getElementById('tutStageInner');
    const titleEl = document.getElementById('tutModalTitle');

    function open(vid, title) {
        if (!vid) { window.toast && toast('This tutorial has no video yet.', 'error'); return; }
        titleEl.textContent = title || '';
        stage.innerHTML = '<iframe src="https://www.youtube.com/embed/' + encodeURIComponent(vid) +
            '?autoplay=1&rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.remove('show');
        stage.innerHTML = '';   // stop playback
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.tut-card').forEach((card) => {
        const go = () => open(card.getAttribute('data-yt'), card.getAttribute('data-title'));
        card.addEventListener('click', go);
        card.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
    });

    document.getElementById('tutClose').addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('show')) close(); });

    document.getElementById('tutFullscreen').addEventListener('click', () => {
        const el = stage.querySelector('iframe') || stage;
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    });
})();
</script>
@endpush
