{{-- The Stash on the dashboard (2026-10-07): a card that folds open, with
     the partners who share resources with anee.io's farmers, and under each
     partner its shelves. Folded or open is remembered on this phone. --}}
@php $stCounts = \App\Support\Stash::counts(); @endphp
<section class="stc" id="stashCard">
    <button type="button" class="stc-head" id="stashHead" aria-expanded="false" aria-controls="stashBody">
        <span class="stc-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V7z"/><path d="M3 4h18v3H3z"/><path d="M10 11h4"/></svg>
        </span>
        <span class="stc-txt"><b>The Stash</b><i>Magazines, guides and more, shared by anee.io's partners. Free to read.</i></span>
        <svg class="stc-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="stc-fold" id="stashBody">
        <div>
            <div class="stc-in">
                @foreach (\App\Support\Stash::PARTNERS as $pk => $p)
                    <div class="stc-partner">
                        <div class="stc-p-head">
                            <img src="{{ asset($p['logo']) }}" alt="{{ $p['name'] }} logo" width="64" height="64" loading="lazy">
                            <span><b>{{ $p['name'] }}</b><small>{{ $p['full'] }}</small></span>
                        </div>
                        <p class="stc-p-about">{{ $p['about'] }}</p>
                        <div class="stc-shelves">
                            @foreach ($p['types'] as $tk => $t)
                                @php $c = $stCounts[$pk][$tk] ?? ['n' => 0, 'ready' => 0]; @endphp
                                <a href="{{ route('stash.shelf', [$pk, $tk]) }}" class="stc-shelf">
                                    <span class="stc-books" aria-hidden="true"><i></i><i></i><i></i></span>
                                    <span class="stc-s-txt"><b>{{ $t['label'] }}</b><small>{{ $c['n'] }} {{ $c['n'] === 1 ? 'issue' : 'issues' }}</small></span>
                                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="stc-more">More partners are joining. Their resources land here, free for every anee.io farmer.</p>
            </div>
        </div>
    </div>
</section>

@push('head')
<style>
    /* ---- The Stash card. House curve; still under reduced motion. ---- */
    .stc { margin-top: .75rem; border-radius: 1rem; overflow: hidden; border: 1px solid #e9d8a6; background: var(--color-white); }
    html.dark .stc { border-color: #4a3d16; }
    .stc-head { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .7rem .8rem; text-align: left; cursor: pointer; color: #3b2f00;
        background: linear-gradient(120deg, #fde68a, #f5c518 60%, #e0aa2a); transition: filter .28s cubic-bezier(.22,1,.36,1); }
    .stc-head:hover { filter: brightness(1.04); }
    .stc-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .7rem; display: grid; place-items: center; background: rgb(59 47 0 / .12); }
    .stc-ico svg { width: 1.3rem; height: 1.3rem; }
    .stc-txt { min-width: 0; flex: 1 1 auto; }
    .stc-txt b { display: block; font-size: .9rem; font-weight: 800; }
    .stc-txt i { display: block; font-style: normal; font-size: .75rem; color: rgb(59 47 0 / .78); }
    .stc-chev { flex: none; width: 1.1rem; height: 1.1rem; transform: rotate(-90deg); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .stc.is-open .stc-chev { transform: none; }
    .stc-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s cubic-bezier(.22,1,.36,1); }
    .stc.is-open .stc-fold { grid-template-rows: 1fr; }
    .stc-fold > div { min-height: 0; overflow: hidden; }
    .stc-in { padding: .8rem; display: grid; gap: .7rem; opacity: 0; transform: translateY(-6px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .stc.is-open .stc-in { opacity: 1; transform: none; transition-delay: .06s; }
    .stc-partner { border-radius: .9rem; padding: .75rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .stc-partner { background: #121a0d; border-color: #2b3a1c; }
    .stc-p-head { display: flex; align-items: center; gap: .65rem; }
    .stc-p-head img { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .7rem; object-fit: cover; box-shadow: 0 6px 14px -8px rgb(0 0 0 / .5); }
    .stc-p-head b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .stc-p-head small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .stc-p-about { margin-top: .45rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); }
    .stc-shelves { margin-top: .55rem; display: grid; gap: .4rem; }
    .stc-shelf { display: flex; align-items: center; gap: .7rem; padding: .55rem .65rem; border-radius: .8rem; text-decoration: none; background: var(--color-white);
        border: 1px solid var(--color-gray-200); transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .stc-shelf:hover { transform: translateX(2px); border-color: #e0aa2a; }
    .stc-shelf > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .stc-books { flex: none; display: flex; align-items: flex-end; gap: 2px; height: 1.6rem; }
    .stc-books i { display: block; width: .42rem; border-radius: 2px; background: #2d5016; }
    .stc-books i:nth-child(1) { height: 1.3rem; } .stc-books i:nth-child(2) { height: 1.6rem; background: #f5c518; } .stc-books i:nth-child(3) { height: 1.1rem; background: #4a7c2a; transform: rotate(8deg); transform-origin: bottom; }
    .stc-s-txt { min-width: 0; flex: 1 1 auto; }
    .stc-s-txt b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .stc-s-txt small { font-size: .72rem; color: var(--color-gray-500); }
    .stc-more { font-size: .72rem; color: var(--color-gray-500); text-align: center; }
    @media (prefers-reduced-motion: reduce) { .stc-fold, .stc-in, .stc-chev, .stc-shelf, .stc-head { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const card = document.getElementById('stashCard'), head = document.getElementById('stashHead');
    if (!card || !head) return;
    const KEY = 'anee.stashOpen';
    const set = (on) => { card.classList.toggle('is-open', on); head.setAttribute('aria-expanded', String(on)); };
    try { set(localStorage.getItem(KEY) === '1'); } catch (_) {}
    head.addEventListener('click', () => {
        const on = !card.classList.contains('is-open');
        set(on);
        try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (_) {}
    });
})();
// Folded, this head and the Global and Quick Tools head above it stand the
// same height (the owner's call): their lines are about as long, and on the
// few widths where one wraps a word sooner, the shorter head grows to match.
(() => {
    const a = document.getElementById('globalToolsHead'), b = document.getElementById('stashHead');
    if (!a || !b || !window.ResizeObserver) return;
    const even = () => {
        a.style.minHeight = b.style.minHeight = '';
        const h = Math.max(a.offsetHeight, b.offsetHeight);
        a.style.minHeight = b.style.minHeight = h + 'px';
    };
    let w = 0;
    new ResizeObserver(([e]) => { const n = Math.round(e.contentRect.width); if (n !== w) { w = n; even(); } }).observe(b.closest('section').parentElement);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(even);
})();
</script>
@endpush
