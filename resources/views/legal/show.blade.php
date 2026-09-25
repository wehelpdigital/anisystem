@extends('layouts.public')

@section('title', $page->title)
@section('meta_description', \App\Support\CommunityText::plain($page->body, 155))

{{-- A member who reads the Privacy Policy from the app's footer at night
     should not be flashed with white: these pages wear the saved theme, as
     the login page does. Declared at the top level because the layout tests
     it in <head>, before the content section is yielded. --}}
@section('honours-theme-cookie', true)

@php
    $prev = url()->previous();
    $back = ($prev && $prev !== url()->current() && ! str_contains($prev, '/legal/'))
        ? $prev
        : (auth()->check() ? route('app.dashboard') : route('home'));
    $hasToc = count($toc) >= 3;
    $updated = $page->updated_at;
@endphp

@push('head')
<style>
    /* ---- the page ------------------------------------------------------ */
    .lgl-page { background: var(--color-gray-50); }
    .lgl-shell { max-width: 72rem; margin: 0 auto; padding: 1.1rem 1rem 3rem; }
    @media (min-width: 768px) { .lgl-shell { padding: 2.25rem 1.5rem 4.5rem; } }

    /* Back link + the switcher between the footer pages. */
    .lgl-top { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
    @media (min-width: 768px) { .lgl-top { margin-bottom: 1.5rem; } }
    .lgl-back { display: inline-flex; align-items: center; gap: .3rem; flex-shrink: 0; font-size: .85rem; font-weight: 700;
        color: var(--color-gray-600); padding: .45rem .7rem .45rem .5rem; border-radius: 999px;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lgl-back:hover { background: var(--color-gray-100); color: var(--color-gray-900); }
    .lgl-back svg { width: 1rem; height: 1rem; }
    .lgl-switch { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch;
        padding: .15rem .1rem; margin-left: auto; }
    .lgl-switch::-webkit-scrollbar { display: none; }
    .lgl-chip { flex-shrink: 0; font-size: .8rem; font-weight: 700; line-height: 1; padding: .55rem .85rem; border-radius: 999px;
        border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lgl-chip:hover { border-color: var(--color-brand-300); color: var(--color-brand-700); }
    .lgl-chip:active { transform: scale(.96); }
    .lgl-chip.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    /* Four chips and the back link fit a 400px phone without scrolling. */
    @media (max-width: 480px) {
        .lgl-top { gap: .35rem; }
        .lgl-back { padding: .45rem .5rem .45rem .3rem; }
        .lgl-switch { gap: .3rem; }
        .lgl-chip { padding: .52rem .68rem; font-size: .78rem; }
    }

    /* Contents beside the page on a wide screen; folded above it on a phone. */
    .lgl-grid { display: block; }
    @media (min-width: 1024px) {
        .lgl-grid.has-toc { display: grid; grid-template-columns: 15.5rem minmax(0, 1fr); gap: 2rem; align-items: start; }
    }
    .lgl-aside { display: none; }
    @media (min-width: 1024px) { .lgl-aside { display: block; position: sticky; top: 6.5rem; } }
    .lgl-aside-in { max-height: calc(100vh - 8rem); overflow-y: auto; padding: 1rem .6rem 1rem .4rem; scrollbar-width: thin; }
    .lgl-aside-h { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--color-gray-500); margin: 0 0 .5rem .6rem; }
    .lgl-toc { list-style: none; margin: 0; padding: 0; }
    .lgl-toc li { margin: 0; }
    .lgl-toc a { display: flex; align-items: flex-start; gap: .55rem; padding: .42rem .6rem; border-radius: .7rem; font-size: .84rem; line-height: 1.35;
        color: var(--color-gray-600); font-weight: 600;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lgl-toc a:hover { background: var(--color-gray-100); color: var(--color-gray-900); }
    .lgl-toc a.is-active { background: var(--color-brand-50); color: var(--color-brand-700); }
    .lgl-num { flex-shrink: 0; display: inline-grid; place-items: center; min-width: 1.35rem; height: 1.35rem; border-radius: 999px;
        font-size: .68rem; font-weight: 800; background: var(--color-gray-100); color: var(--color-gray-600);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lgl-toc a.is-active .lgl-num { background: var(--color-brand-600); color: #fff; }
    .lgl-dot { flex-shrink: 0; width: .4rem; height: .4rem; margin: .45rem .45rem 0 .45rem; border-radius: 999px; background: var(--color-gray-300); }
    .lgl-toc a.is-active .lgl-dot { background: var(--color-brand-500); }

    /* ---- the card ------------------------------------------------------ */
    .lgl-card { background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: 1.25rem;
        box-shadow: 0 1px 2px rgb(16 24 40 / .04), 0 8px 24px -12px rgb(16 24 40 / .12);
        padding: 1.5rem 1.15rem 1.75rem; max-width: 50rem; }
    @media (min-width: 640px) { .lgl-card { padding: 2.25rem 2.25rem 2.5rem; } }
    @media (min-width: 1024px) { .lgl-card { padding: 2.75rem 3.25rem 3rem; } }
    .lgl-grid:not(.has-toc) .lgl-card { margin: 0 auto; }
    .lgl-head h1 { font-family: var(--font-heading); font-weight: 800; letter-spacing: -.015em; line-height: 1.15;
        font-size: 1.65rem; color: var(--color-gray-900); margin: 0; }
    @media (min-width: 768px) { .lgl-head h1 { font-size: 2.15rem; } }
    .lgl-date { display: inline-flex; align-items: center; gap: .4rem; margin-top: .6rem; font-size: .8rem; font-weight: 600; color: var(--color-gray-500); }
    .lgl-date svg { width: .95rem; height: .95rem; }

    /* Phone contents: a fold that opens with the house ease. */
    .lgl-fold { margin: 1.25rem 0 1.5rem; border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-gray-50); }
    @media (min-width: 1024px) { .lgl-grid.has-toc .lgl-fold { display: none; } }
    .lgl-fold-btn { width: 100%; display: flex; align-items: center; gap: .6rem; padding: .8rem 1rem; font-size: .9rem; font-weight: 800;
        color: var(--color-gray-900); text-align: left; border-radius: 1rem; }
    .lgl-fold-btn small { font-size: .75rem; font-weight: 600; color: var(--color-gray-500); margin-left: auto; }
    .lgl-fold-btn svg { width: 1.1rem; height: 1.1rem; color: var(--color-gray-500); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lgl-fold.is-open .lgl-fold-btn svg { transform: rotate(180deg); }
    .lgl-fold-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .lgl-fold.is-open .lgl-fold-body { grid-template-rows: 1fr; }
    .lgl-fold-in { overflow: hidden; min-height: 0; }
    .lgl-fold-in .lgl-toc { padding: 0 .5rem .6rem; opacity: 0; transform: translateY(-4px);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lgl-fold.is-open .lgl-fold-in .lgl-toc { opacity: 1; transform: none; }

    /* ---- the words ----------------------------------------------------- */
    .legal-body { margin-top: 1.5rem; font-size: .98rem; line-height: 1.72; color: var(--color-gray-700); overflow-wrap: break-word; }
    @media (min-width: 768px) { .legal-body { font-size: 1.02rem; } }
    .legal-body > :first-child { margin-top: 0; }
    .legal-body h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; line-height: 1.3; letter-spacing: -.005em;
        color: var(--color-gray-900); margin: 2.1rem 0 .75rem; padding-top: 1.6rem; border-top: 1px solid var(--color-gray-100);
        scroll-margin-top: 5.5rem; }
    @media (min-width: 768px) { .legal-body h3 { font-size: 1.28rem; scroll-margin-top: 6.5rem; } }
    .legal-body blockquote + h3, .legal-body > h3:first-child { border-top: 0; padding-top: 0; }
    .legal-body h4 { font-family: var(--font-heading); font-weight: 700; font-size: 1rem; color: var(--color-gray-800); margin: 1.4rem 0 .5rem; }
    .legal-body p { margin: 0 0 1rem; }
    .legal-body ul { list-style: none; margin: 0 0 1.15rem; padding: 0; }
    .legal-body ul > li { position: relative; padding-left: 1.3rem; margin: .6rem 0; }
    .legal-body ul > li::before { content: ''; position: absolute; left: .28rem; top: .68em; width: .42rem; height: .42rem; border-radius: 999px;
        background: var(--color-brand-500); }
    .legal-body ol { list-style: decimal; margin: 0 0 1.15rem; padding-left: 1.4rem; }
    .legal-body ol > li { margin: .5rem 0; padding-left: .2rem; }
    .legal-body ol > li::marker { color: var(--color-brand-600); font-weight: 700; }
    .legal-body strong, .legal-body b { color: var(--color-gray-900); font-weight: 700; }
    .legal-body em { color: var(--color-gray-500); }
    .legal-body a { color: var(--color-brand-700); font-weight: 600; text-decoration: underline; text-underline-offset: 2px;
        text-decoration-thickness: 1px; text-decoration-color: color-mix(in srgb, currentColor 40%, transparent);
        transition: color .28s cubic-bezier(.22,1,.36,1), text-decoration-color .28s cubic-bezier(.22,1,.36,1); }
    .legal-body a:hover { text-decoration-color: currentColor; }
    .legal-body blockquote { position: relative; margin: 0 0 1.75rem; padding: 1rem 1.1rem 1rem 1.35rem; border-radius: 1rem;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-100); color: var(--color-gray-800); overflow: hidden; }
    .legal-body blockquote::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--color-brand-500); }
    .legal-body blockquote p:last-child { margin-bottom: 0; }
    .legal-body blockquote strong { color: var(--color-brand-800); }

    /* The end of the page: who to ask, and the other pages. */
    .lgl-foot { margin-top: 2.25rem; padding-top: 1.25rem; border-top: 1px solid var(--color-gray-100);
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1.25rem;
        font-size: .85rem; color: var(--color-gray-500); }
    .lgl-foot a { color: var(--color-brand-700); font-weight: 700; }
    .lgl-foot nav { display: flex; flex-wrap: wrap; gap: .35rem 1rem; }
    .lgl-foot nav a { color: var(--color-gray-600); font-weight: 600; transition: color .28s cubic-bezier(.22,1,.36,1); }
    .lgl-foot nav a:hover, .lgl-foot nav a[aria-current] { color: var(--color-brand-700); }

    /* Back to the top, for the long ones on a phone. */
    .lgl-up { position: fixed; right: 1rem; bottom: calc(1rem + env(safe-area-inset-bottom, 0px)); z-index: 30;
        width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center;
        background: var(--color-white); color: var(--color-gray-700); border: 1px solid var(--color-gray-200);
        box-shadow: 0 6px 18px -6px rgb(16 24 40 / .3);
        opacity: 0; transform: translateY(.75rem) scale(.9); pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lgl-up.is-shown { opacity: 1; transform: none; pointer-events: auto; }
    .lgl-up:hover { color: var(--color-brand-700); }
    .lgl-up svg { width: 1.2rem; height: 1.2rem; }

    /* ---- night --------------------------------------------------------- */
    html.dark .lgl-card { border-color: var(--color-gray-200); box-shadow: 0 1px 3px rgb(0 0 0 / .4), 0 10px 28px -12px rgb(0 0 0 / .6); }
    html.dark .legal-body a, html.dark .lgl-foot a, html.dark .lgl-toc a.is-active { color: var(--color-brand-800); }
    html.dark .lgl-foot nav a { color: var(--color-gray-600); }
    html.dark .lgl-foot nav a:hover, html.dark .lgl-foot nav a[aria-current] { color: var(--color-brand-800); }
    html.dark .lgl-chip.is-on { color: #fff; }
    html.dark .legal-body blockquote strong { color: var(--color-brand-900); }
    /* The site's dark footer is painted from the grey ramp, which night mode
       turns inside out; re-point it here so it stays a dark band with light
       words, and give the header the logo drawn for dark ground. */
    html.dark body > footer.bg-gray-900 {
        --color-gray-900: #0d1014; --color-gray-800: #262d36;
        --color-gray-500: #7d8794; --color-gray-400: #98a2ae; --color-gray-300: #ccd4dd;
    }
    html.dark body > header img[src*="images/logo.png"] { content: url('{{ asset('images/site/logo-white.png') }}?v=anee'); }

    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
    @media (prefers-reduced-motion: reduce) {
        .lgl-page *, .lgl-up { transition: none !important; }
    }
</style>
@endpush

@section('content')
<div class="lgl-page">
    <div class="lgl-shell">
        <div class="lgl-top">
            <a href="{{ $back }}" class="lgl-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                Back
            </a>
            @if ($pages->count() > 1)
                <nav class="lgl-switch" aria-label="Legal and info pages">
                    @foreach ($pages as $p)
                        <a href="{{ route('legal.show', ['slug' => $p->slug]) }}"
                           class="lgl-chip {{ $p->slug === $page->slug ? 'is-on' : '' }}"
                           @if ($p->slug === $page->slug) aria-current="page" @endif>{{ \App\Models\AsLegalPage::SHORT[$p->slug] ?? $p->title }}</a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="lgl-grid {{ $hasToc ? 'has-toc' : '' }}">
            @if ($hasToc)
                <aside class="lgl-aside" aria-label="On this page">
                    <div class="lgl-aside-in">
                        <p class="lgl-aside-h">On this page</p>
                        <ol class="lgl-toc" data-lgl-toc>
                            @foreach ($toc as $t)
                                <li><a href="#{{ $t['id'] }}" data-lgl-to="{{ $t['id'] }}">
                                    @if ($t['num'])<span class="lgl-num">{{ $t['num'] }}</span>@else<span class="lgl-dot"></span>@endif
                                    <span>{{ $t['text'] }}</span>
                                </a></li>
                            @endforeach
                        </ol>
                    </div>
                </aside>
            @endif

            <article class="lgl-card">
                <header class="lgl-head">
                    <h1>{{ $page->title }}</h1>
                    @if ($updated)
                        <p class="lgl-date">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                            Last updated <time datetime="{{ $updated->format('Y-m-d') }}">{{ $updated->format('F j, Y') }}</time>
                        </p>
                    @endif
                </header>

                @if ($hasToc)
                    <div class="lgl-fold" data-lgl-fold>
                        <button type="button" class="lgl-fold-btn" aria-expanded="false" aria-controls="lglFoldBody">
                            On this page
                            <small>{{ count($toc) }} sections</small>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="lgl-fold-body" id="lglFoldBody">
                            <div class="lgl-fold-in">
                                <ol class="lgl-toc">
                                    @foreach ($toc as $t)
                                        <li><a href="#{{ $t['id'] }}" tabindex="-1">
                                            @if ($t['num'])<span class="lgl-num">{{ $t['num'] }}</span>@else<span class="lgl-dot"></span>@endif
                                            <span>{{ $t['text'] }}</span>
                                        </a></li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="legal-body">
                    {!! $html !!}
                </div>

                <footer class="lgl-foot">
                    <span>Questions? Write to <a href="mailto:support@anee.io">support@anee.io</a></span>
                    @if ($pages->count() > 1)
                        <nav aria-label="Other pages">
                            @foreach ($pages as $p)
                                <a href="{{ route('legal.show', ['slug' => $p->slug]) }}" @if ($p->slug === $page->slug) aria-current="page" @endif>{{ \App\Models\AsLegalPage::SHORT[$p->slug] ?? $p->title }}</a>
                            @endforeach
                        </nav>
                    @endif
                </footer>
            </article>
        </div>
    </div>
</div>

<button type="button" class="lgl-up" id="lglUp" aria-label="Back to the top" tabindex="-1">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
</button>
@endsection

@push('scripts')
<script>
(() => {
    // On a narrow phone the switcher scrolls: bring the page being read
    // into view, sideways only (scrollIntoView would move the page too).
    const sw = document.querySelector('.lgl-switch');
    const on = sw && sw.querySelector('.lgl-chip.is-on');
    if (on && sw.scrollWidth > sw.clientWidth) {
        sw.scrollLeft = Math.max(0, on.offsetLeft - sw.offsetLeft - (sw.clientWidth - on.offsetWidth) / 2);
    }

    // The phone contents fold. Its links stay out of the tab order while shut.
    const fold = document.querySelector('[data-lgl-fold]');
    if (fold) {
        const btn = fold.querySelector('.lgl-fold-btn');
        const links = fold.querySelectorAll('.lgl-fold-in a');
        btn.addEventListener('click', () => {
            const open = !fold.classList.contains('is-open');
            fold.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            links.forEach((a) => a.tabIndex = open ? 0 : -1);
        });
    }

    // The wide-screen contents follow the reading: the section at the top
    // of the screen is the one lit.
    const toc = document.querySelector('[data-lgl-toc]');
    if (toc && 'IntersectionObserver' in window) {
        const byId = {};
        toc.querySelectorAll('a[data-lgl-to]').forEach((a) => { byId[a.dataset.lglTo] = a; });
        const heads = Array.from(document.querySelectorAll('.legal-body h3[id]'));
        const light = (id) => Object.entries(byId).forEach(([k, a]) => a.classList.toggle('is-active', k === id));
        const seen = new Set();
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e) => { if (e.isIntersecting) seen.add(e.target.id); else seen.delete(e.target.id); });
            const first = heads.find((h) => seen.has(h.id));
            if (first) light(first.id);
        }, { rootMargin: '-15% 0px -70% 0px' });
        heads.forEach((h) => io.observe(h));
        if (heads[0]) light(heads[0].id);
    }

    // Back to the top, once the reader is well into the page.
    const up = document.getElementById('lglUp');
    if (up) {
        let shown = false;
        const paint = () => {
            const want = window.scrollY > 700;
            if (want === shown) return;
            shown = want;
            up.classList.toggle('is-shown', want);
            up.tabIndex = want ? 0 : -1;
        };
        window.addEventListener('scroll', paint, { passive: true });
        paint();
        up.addEventListener('click', () => {
            const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: still ? 'auto' : 'smooth' });
        });
    }
})();
</script>
@endpush
