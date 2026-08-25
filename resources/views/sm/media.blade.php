@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Media Box — ' . $schedule->title)
@section('page-title', 'Media Box')
@section('page-subtitle', $schedule->title)
@section('help-key', 'media')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* Two tabs, one shelf. Videos and photos want different eyes: you scan
           photos, you pick a video. Same grid, different contents. */
        .mb-tabs { display: flex; gap: .4rem; margin-bottom: .75rem; }
        .mb-tab { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .85rem;
            border: 2px solid var(--color-gray-200); background: var(--color-white); border-radius: 999px;
            font-size: .85rem; font-weight: 700; color: #374151; cursor: pointer;
            transition: background .25s ease, border-color .25s ease, color .25s ease; }
        .mb-tab:hover { border-color: #a8cc7e; background: #f3f8ec; }
        .mb-tab.is-active { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
        .mb-tab .mb-n { font-size: .72rem; opacity: .85; }
        html.dark .mb-tab { background: #1c2136; border-color: #2a3050; color: #cdd8c0; }
        html.dark .mb-tab.is-active { background: #4a7c2a; border-color: #6b9f3d; color: #fff; }

        .mb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr)); gap: .6rem; }
        .mb-cell { position: relative; border-radius: .75rem; overflow: hidden; background: var(--color-gray-100);
            border: 1px solid var(--color-gray-200); text-align: left; cursor: pointer;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .mb-cell:hover { transform: translateY(-1px); box-shadow: 0 10px 26px -18px rgb(0 0 0 / .45); }
        .mb-shot { position: relative; aspect-ratio: 1; background: #0b1220; }
        .mb-shot img { width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity .28s ease; }
        .mb-shot img.is-loaded { opacity: 1; }
        .mb-shot.is-gone::after { content: 'File missing'; position: absolute; inset: 0; display: flex;
            align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; color: #94a3b8; }
        .mb-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            color: #fff; pointer-events: none; text-shadow: 0 1px 8px rgb(0 0 0 / .7); }
        .mb-play svg { width: 2.1rem; height: 2.1rem; }
        .mb-meta { padding: .4rem .5rem .5rem; display: flex; flex-direction: column; gap: .15rem; }
        .mb-title { font-size: .74rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.25;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .mb-sub { font-size: .64rem; color: var(--color-gray-400); display: flex; gap: .3rem; align-items: center; }
        .mb-src { font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em;
            padding: .05rem .3rem; border-radius: .3rem; background: #e4efd4; color: #3d6823; }
        .mb-open { display: block; font-size: .64rem; font-weight: 700; color: #4a7c2a; margin-top: .1rem; }
        .mb-empty { text-align: center; color: var(--color-gray-400); font-size: .82rem; padding: 2rem .5rem; }
        .mb-find { position: relative; margin-bottom: .6rem; }
        .mb-find .form-input { padding-left: 2.1rem; padding-right: 2.1rem; }
        .mb-find .mbf-ico { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
            width: 1rem; height: 1rem; color: #9ca3af; pointer-events: none; }
        .mb-find .mbf-clear { position: absolute; right: .4rem; top: 50%; transform: translateY(-50%);
            width: 1.6rem; height: 1.6rem; border-radius: 999px; color: #9ca3af;
            display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
        .mb-count { font-size: .78rem; color: var(--color-gray-500); margin: -.25rem 0 .6rem; }
        .mb-cell.is-filtered { display: none !important; }
        .mb-row { display: flex; align-items: center; justify-content: space-between; gap: .4rem; margin-top: .15rem; }
        .mb-dl { flex: 0 0 auto; width: 1.6rem; height: 1.6rem; border-radius: .45rem; color: #4a7c2a;
            display: inline-flex; align-items: center; justify-content: center; background: #f3f8ec; }
        .mb-dl:hover { background: #4a7c2a; color: #fff; }
        .mb-dl svg { width: .95rem; height: .95rem; }
        html.dark .mb-dl { background: rgb(61 104 35 / .3); color: #a8cc7e; }
        html.dark .mb-title { color: #e5e9f5; }
        html.dark .mb-cell { background: #151b12; border-color: #2b3a1c; }
        html.dark .mb-src { background: rgb(61 104 35 / .35); color: #a8cc7e; }
        @media (prefers-reduced-motion: reduce) { .mb-cell, .mb-shot img { transition: none; } }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'media'])

@php
    $photos = collect($items)->where('kind', 'image')->values();
    $videos = collect($items)->where('kind', 'video')->values();
@endphp

{{-- A season fills this shelf quickly, and "the photo of the pump" is a
     search, not a scroll. It looks at the title and where the picture came
     from, which is everything a cell shows. --}}
<div class="mb-find">
    <svg class="mbf-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
    <input type="text" id="mbSearch" class="form-input" placeholder="Search photos and videos…" autocomplete="off" aria-label="Search media">
    <button type="button" id="mbSearchClear" class="mbf-clear hidden" aria-label="Clear search">✕</button>
</div>
<p class="mb-count hidden" id="mbCount" aria-live="polite"></p>

<div class="mb-tabs" role="tablist">
    <button type="button" class="mb-tab is-active" data-mb-tab="image" aria-selected="true">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
        Photos <span class="mb-n">{{ $photos->count() }}</span>
    </button>
    <button type="button" class="mb-tab" data-mb-tab="video" aria-selected="false">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
        Videos <span class="mb-n">{{ $videos->count() }}</span>
    </button>
</div>

@foreach ([['image', $photos], ['video', $videos]] as [$kind, $rows])
    <div class="mb-pane{{ $kind === 'video' ? ' hidden' : '' }}" data-mb-pane="{{ $kind }}">
        @if ($rows->isEmpty())
            <p class="mb-empty">
                @if ($kind === 'video')
                    No videos yet. Anything recorded in a note or the collab room lands here.
                @else
                    No photos yet. Anything attached to a note, an activity, a drawing or a question to the AI lands here.
                @endif
            </p>
        @else
            <div class="mb-grid">
                @foreach ($rows as $m)
                    <div class="mb-cell" data-lb-type="{{ $m['kind'] }}" data-lb-url="{{ $m['url'] }}" data-lb-poster="{{ $m['posterUrl'] ?? '' }}"
                        data-find="{{ mb_strtolower($m['title'] . ' ' . $m['source'] . ' ' . ($m['when'] ?? '')) }}">
                        {{-- A clip with no frame asks for one while the page
                             is open (clip-frames-js): a ring turns on the
                             tile until the picture lands, and the frame is
                             remembered so it is never cut twice. --}}
                        <div class="mb-shot"
                             @if ($m['kind'] === 'video' && empty($m['posterUrl']) && ! empty($m['path'])) data-needs-frame="{{ $m['path'] }}" data-clip-url="{{ $m['url'] }}" @endif>
                            @if ($m['kind'] === 'video')
                                @if (! empty($m['posterUrl']))
                                    <img src="{{ $m['posterUrl'] }}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.closest('.mb-shot')?.classList.add('is-gone'); this.remove();">
                                @endif
                                <span class="mb-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                            @else
                                <img src="{{ $m['url'] }}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.closest('.mb-shot')?.classList.add('is-gone'); this.remove();">
                            @endif
                        </div>
                        <div class="mb-meta">
                            <span class="mb-title">{{ $m['title'] ?: 'Untitled' }}</span>
                            <span class="mb-sub">
                                <span class="mb-src">{{ $m['source'] }}</span>
                                {{ $m['when'] }}
                            </span>
                            <span class="mb-row">
                                @if (! empty($m['href']))
                                    {{-- The gallery answers "what have we got";
                                         this answers "what was it about", which
                                         is the next question every time. --}}
                                    <a class="mb-open" href="{{ $m['href'] }}" data-mb-go>Where it lives →</a>
                                @endif
                                <a class="mb-dl" href="{{ $m['url'] }}" download data-mb-go title="Save to this device" aria-label="Download">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0l-3.5-3.5M12 14l3.5-3.5M5 19h14"/></svg>
                                </a>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

{{-- The shell already carries the lightbox; standalone needs its own. --}}
@if (! request()->boolean('partial'))
    @include('sm.partials.note-lightbox')
@include('sm.partials.clip-frames-js')
@endif
@endsection

@push('scripts')
<script>
(() => {
    const init = () => {
        document.querySelectorAll('.mb-tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                const want = tab.getAttribute('data-mb-tab');
                document.querySelectorAll('.mb-tab').forEach((t) => {
                    const on = t === tab;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                document.querySelectorAll('[data-mb-pane]').forEach((p) => {
                    p.classList.toggle('hidden', p.getAttribute('data-mb-pane') !== want);
                });
            });
        });
        // The links out of a cell — where it lives, and download — are links,
        // not taps on the picture; without this the lightbox would open on top
        // of whatever you asked for.
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-mb-go]')) e.stopPropagation();
        }, true);

        const search = document.getElementById('mbSearch');
        const count = document.getElementById('mbCount');
        const clear = document.getElementById('mbSearchClear');
        function runFind() {
            const q = (search.value || '').trim().toLowerCase();
            let shown = 0, total = 0;
            document.querySelectorAll('.mb-cell').forEach((c) => {
                const visiblePane = !c.closest('[data-mb-pane]').classList.contains('hidden');
                total += visiblePane ? 1 : 0;
                const hit = !q || (c.getAttribute('data-find') || '').includes(q);
                c.classList.toggle('is-filtered', !hit);
                if (hit && visiblePane) shown++;
            });
            count.textContent = q ? (shown + ' of ' + total + ' shown') : '';
            count.classList.toggle('hidden', !q);
            clear.classList.toggle('hidden', !q);
            // An empty tab with a search running should say why it is empty.
            document.querySelectorAll('[data-mb-pane]').forEach((p) => {
                const empty = p.querySelector('.mb-empty');
                if (!empty) return;
                empty.classList.toggle('hidden', !q ? false : false);
            });
        }
        search.addEventListener('input', runFind);
        clear.addEventListener('click', () => { search.value = ''; runFind(); search.focus(); });
        document.querySelectorAll('.mb-tab').forEach((t) => t.addEventListener('click', runFind));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endpush
