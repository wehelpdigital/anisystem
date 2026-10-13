{{-- The members' gate (2026-10-14): the rest of a field guide, in the page
     for search engines and blurred for a guest, under an invitation to join
     anee.io free. Needs $page and $gated (the blocks behind the gate, keys
     kept so their heading anchors match the contents). See
     App\Support\ContentGate. --}}
@php
    $G = \App\Support\ContentGate::class;
    $tl = ($page->lang ?? '') === 'tl';
    $behind = $G::behind(array_values($gated), 0);
    $mins = max(1, (int) round($G::wordsFrom(array_values($gated), 0) / 200));
    $name = \App\Support\SitePages::shortTitle($page);
    $here = '/' . $page->section . '/' . $page->slug;
    $title = $tl ? 'Basahin ang buong gabay, libre' : match ($page->section) {
        'crop-varieties', 'rice-varieties' => 'See the full record of ' . $name,
        'pests' => 'Read how to stop ' . $name,
        'diseases' => 'Read how to manage ' . $name,
        'weeds' => 'Read how to control ' . $name,
        'land-preparation' => 'Read every step of ' . \Illuminate\Support\Str::lower($name),
        default => 'Read the full guide, free',
    };
@endphp
@once
@push('head')
<style>
    .sp-gate { position: relative; margin-top: 1.4rem; }
    .sp-gated { position: relative; max-height: 30rem; overflow: hidden; filter: blur(5px); opacity: .85; pointer-events: none; user-select: none;
        -webkit-mask-image: linear-gradient(#000 0, #000 35%, transparent 96%); mask-image: linear-gradient(#000 0, #000 35%, transparent 96%); }
    .sp-gate-card { position: relative; z-index: 2; margin-top: -17rem; padding: 1.5rem 1.4rem 1.4rem; border-radius: 1.5rem; text-align: center;
        background: linear-gradient(165deg, #ffffff 0%, #f6faf0 100%); border: 1px solid #d9e8c6; box-shadow: 0 30px 70px -40px rgb(20 33 12 / .65);
        opacity: 0; transform: translateY(14px); transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .sp-gate-card.is-in { opacity: 1; transform: none; }
    .sp-gate-card.is-pulse { box-shadow: 0 0 0 6px rgb(134 181 86 / .28), 0 30px 70px -40px rgb(20 33 12 / .65); }
    .sp-gate-lock { width: 3rem; height: 3rem; margin: 0 auto .7rem; display: grid; place-items: center; border-radius: 999px; color: #fff;
        background: radial-gradient(120% 120% at 30% 20%, #6aa33a, #2d5016); box-shadow: 0 12px 26px -14px rgb(45 80 22 / .9); }
    .sp-gate-lock svg { width: 1.35rem; height: 1.35rem; }
    .sp-gate-card h2 { margin: 0 !important; font-family: var(--font-heading); font-size: clamp(1.25rem, 2.6vw, 1.55rem) !important; font-weight: 800; line-height: 1.2; color: #14210c; }
    .sp-gate-card > p { margin: .55rem auto 0; max-width: 34rem; font-size: .95rem; line-height: 1.6; color: #4b5563; }
    .sp-gate-in { margin: 1rem auto 0; max-width: 30rem; display: grid; gap: .35rem; text-align: left; }
    .sp-gate-in span { display: flex; align-items: flex-start; gap: .5rem; padding: .45rem .7rem; border-radius: .75rem; background: #fff; border: 1px solid #e3ecd8;
        font-size: .86rem; font-weight: 700; color: #1f3410; line-height: 1.35; }
    .sp-gate-in span::before { content: ''; flex: none; width: .95rem; height: .95rem; margin-top: .1rem; border-radius: 999px; background: #e4f1d6 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232d5016' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center / .6rem no-repeat; }
    .sp-gate-in small { font-size: .78rem; font-weight: 700; color: #6b7f5a; padding-left: .3rem; }
    .sp-gate-perks { display: flex; flex-wrap: wrap; justify-content: center; gap: .3rem .9rem; margin-top: .9rem; font-size: .78rem; font-weight: 800; color: #3d6823; }
    .sp-gate-acts { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; margin-top: 1.1rem; }
    .sp-gate-acts .btn { min-width: 11rem; }
    .sp-gate-tools { margin-top: .9rem; font-size: .8rem; line-height: 1.55; color: #6b7280; }
    @media (max-width: 639.98px) { .sp-gate-card { margin-top: -14rem; padding: 1.25rem 1rem 1.1rem; } .sp-gate-acts .btn { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { .sp-gate-card { transition: none; opacity: 1; transform: none; } }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* The gate's card comes up as it scrolls in, and a contents link into
       the gated part brings the reader to the card instead. */
    const card = document.querySelector('[data-gate-card]');
    if (!card) return;
    const show = () => card.classList.add('is-in');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) { show(); io.disconnect(); } }, { rootMargin: '0px 0px -10% 0px' });
        io.observe(card);
    } else { show(); }
    const gated = document.querySelector('.sp-gated');
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a || !gated) return;
        const t = document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)));
        if (!t || !gated.contains(t)) return;
        e.preventDefault();
        show();
        card.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
        card.classList.add('is-pulse');
        setTimeout(() => card.classList.remove('is-pulse'), 1400);
    });
})();
</script>
@endpush
@endonce
<div class="sp-gate">
    <div class="sp-gated" id="spGated">
        @include('public.site.blocks', ['blocks' => $gated])
    </div>
    <div class="sp-gate-card" data-gate-card>
        <div class="sp-gate-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg></div>
        <h2>{{ $title }}</h2>
        <p>{{ $tl
            ? 'Mag-sign up nang libre sa anee.io at bubukas ang buong pahina, pati ang lahat ng gabay sa pananim, binhi, paghahanda ng lupa, peste, sakit at damo.'
            : 'Sign up free to anee.io and the whole page opens, with every guide on crops, varieties, land preparation, pests, diseases and weeds.' }}</p>
        @if ($behind)
            <div class="sp-gate-in">
                @foreach (array_slice($behind, 0, 5) as $h)<span>{{ $h }}</span>@endforeach
                <small>{{ count($behind) > 5 ? ($tl ? 'At ' . (count($behind) - 5) . ' pa' : 'And ' . (count($behind) - 5) . ' more ' . (count($behind) - 5 === 1 ? 'section' : 'sections')) . ', ' : '' }}{{ $tl ? 'mga ' . $mins . ' minutong babasahin' : 'about ' . $mins . ' more minutes of reading' }}</small>
            </div>
        @endif
        <div class="sp-gate-perks">
            <span>✓ {{ $tl ? 'Libre habambuhay sa Libre plan' : 'Free for good on the Libre plan' }}</span>
            <span>✓ {{ $tl ? 'Walang credit card' : 'No credit card' }}</span>
            <span>✓ {{ $tl ? 'Gumagana sa kahit anong phone' : 'Works on any phone' }}</span>
        </div>
        <div class="sp-gate-acts">
            <a href="{{ route('signup') }}?read={{ urlencode($here) }}" class="btn btn-accent">{{ $tl ? 'Mag-sign up nang libre' : 'Sign up free' }}</a>
            <a href="{{ route('login') }}?read={{ urlencode($here) }}" class="btn btn-outline">{{ $tl ? 'May account na ako' : 'I have an account' }}</a>
        </div>
        <p class="sp-gate-tools">{{ $tl
            ? 'Kasama rin ang mga tool: ang kalendaryo ng bawat gawain, ang pest at disease finder, ang variety finder, at si Anee, ang smart farm technician na sumasagot sa Tagalog.'
            : 'The tools come with it: a cropping calendar that dates every task, the pest, disease and variety finders, and Anee, the smart farm technician who answers in Tagalog or English.' }}</p>
    </div>
</div>
