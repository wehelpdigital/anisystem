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
                {{-- Anee Knowledgebase (2026-10-30, the owner): anee.io's own guides, each shelf opening in a full window. --}}
                <div class="stc-partner is-anee">
                    <div class="stc-p-head">
                        <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="Anee" width="64" height="64" loading="lazy">
                        <span><b>Anee Knowledgebase</b><small>Guides from anee.io's farm technicians</small></span>
                    </div>
                    <p class="stc-p-about">What Anee knows about your crop, written for the field. Open a shelf and read it here, any time.</p>
                    <div class="stc-shelves">
                        @foreach (\App\Support\Knowledge::SHELVES as $kk => $k)
                            @php $kn = \App\Support\Knowledge::count($kk); @endphp
                            <button type="button" class="stc-shelf" data-kb-open="{{ $kk }}">
                                <span class="stc-kb-i" aria-hidden="true">{{ $k['icon'] }}</span>
                                <span class="stc-s-txt"><b>{{ $k['label'] }}</b><small>{{ $kn }} {{ $kn === 1 ? 'guide' : 'guides' }} · {{ $k['about'] }}</small></span>
                                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @endforeach
                    </div>
                </div>
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

@include('partials.knowledge-modal')

@push('head')
<style>
    /* ---- The Stash card. House curve; still under reduced motion. ---- */
    /* Green (2026-10-28, the owner: "change the stash from yellow to somewhat green"): yellow is for calls to action. */
    .stc { margin-top: .75rem; border-radius: 1rem; overflow: hidden; border: 1px solid #cfe3b8; background: var(--color-white); }
    html.dark .stc { border-color: #2b3a1c; }
    .stc-head { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .7rem .8rem; text-align: left; cursor: pointer; color: #f1f7e9;
        background: linear-gradient(120deg, #5b8a35, #3f6b24 55%, #2d5016); transition: filter .28s cubic-bezier(.22,1,.36,1); }
    .stc-head:hover { filter: brightness(1.08); }
    .stc-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .7rem; display: grid; place-items: center; color: #e8f5d6; background: rgb(255 255 255 / .14); }
    .stc-ico svg { width: 1.3rem; height: 1.3rem; }
    .stc-txt { min-width: 0; flex: 1 1 auto; }
    .stc-txt b { display: block; font-size: .9rem; font-weight: 800; }
    .stc-txt i { display: block; font-style: normal; font-size: .75rem; color: rgb(241 247 233 / .82); }
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
    .stc-shelf:hover { transform: translateX(2px); border-color: #7fb84e; }
    .stc-shelf > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .stc-books { flex: none; display: flex; align-items: flex-end; gap: 2px; height: 1.6rem; }
    .stc-books i { display: block; width: .42rem; border-radius: 2px; background: #2d5016; }
    .stc-books i:nth-child(1) { height: 1.3rem; } .stc-books i:nth-child(2) { height: 1.6rem; background: #a8cc7e; } .stc-books i:nth-child(3) { height: 1.1rem; background: #4a7c2a; transform: rotate(8deg); transform-origin: bottom; }
    .stc-s-txt { min-width: 0; flex: 1 1 auto; }
    .stc-s-txt b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .stc-s-txt small { font-size: .72rem; color: var(--color-gray-500); }
    .stc-more { font-size: .72rem; color: var(--color-gray-500); text-align: center; }
    .stc-partner.is-anee { background: linear-gradient(135deg, #f4f9ee, #fffbe8); border-color: #dbe9c8; }
    html.dark .stc-partner.is-anee { background: linear-gradient(135deg, #16200f, #1f1c0e); border-color: #2b3a1c; }
    .stc-partner.is-anee .stc-p-head img { border-radius: 999px; }
    button.stc-shelf { width: 100%; text-align: left; cursor: pointer; }
    .stc-kb-i { flex: none; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; font-size: 1.05rem; background: #eef5e6; }
    html.dark .stc-kb-i { background: #22301a; }
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
