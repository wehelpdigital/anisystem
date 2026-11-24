{{-- THE WAY IN, ALWAYS ONE TAP AWAY ON A PHONE (2026-11-24, the owner: pages "hard to understand ... in a viewer
     perspective make it better and persuasive"). On a phone the header shows only the logo and the menu, so on the long
     selling pages the button to register was screens away. This slim bar rises once the reader has scrolled past the
     first screen, and steps aside when the footer or the page's own last call is on screen. Guests only; phones only;
     the selling pages only (layouts/public decides). --}}
<div class="scta" id="stickyCta" aria-hidden="true">
    <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="36" height="36" loading="lazy">
    <span class="scta-t"><b>Free forever on Libre</b><small>No card. Ready in minutes.</small></span>
    <a href="{{ route('signup') }}" class="scta-go" tabindex="-1">Start free</a>
</div>
<style>
    .scta { position: fixed; left: .6rem; right: .6rem; bottom: calc(.6rem + env(safe-area-inset-bottom, 0px)); z-index: 45; display: flex; align-items: center; gap: .6rem;
        padding: .5rem .5rem .5rem .55rem; border-radius: 999px; background: rgb(16 28 10 / .94); box-shadow: 0 18px 40px -14px rgb(0 0 0 / .6), 0 0 0 1px rgb(255 255 255 / .08);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transform: translateY(150%); opacity: 0;
        transition: transform .4s cubic-bezier(.22,1,.36,1), opacity .4s cubic-bezier(.22,1,.36,1); }
    .scta.is-on { transform: none; opacity: 1; }
    .scta img { flex: none; width: 2.25rem; height: 2.25rem; border-radius: 999px; box-shadow: 0 0 0 2px #f5c518; }
    .scta-t { flex: 1; min-width: 0; line-height: 1.2; }
    .scta-t b { display: block; font-size: .84rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .scta-t small { display: block; font-size: .72rem; color: #b9cfa3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .scta-go { flex: none; padding: .6rem 1.05rem; border-radius: 999px; font-size: .9rem; font-weight: 800; color: #1f1500; background: #f5c518; text-decoration: none; }
    @media (min-width: 768px) { .scta { display: none; } }
    @media (prefers-reduced-motion: reduce) { .scta { transition: none; } }
</style>
<script>
(() => {
    const bar = document.getElementById('stickyCta');
    if (!bar || !('IntersectionObserver' in window)) return;
    const link = bar.querySelector('a');
    // Out of the way while the footer, or a big "start" button of the page's own, is in view.
    const blockers = new Set();
    const io = new IntersectionObserver((es) => {
        es.forEach((e) => (e.isIntersecting ? blockers.add(e.target) : blockers.delete(e.target)));
        paint();
    }, { threshold: 0 });
    const watch = () => document.querySelectorAll('footer, .btn-accent.btn-lg, .lu-join').forEach((el) => { if (!bar.contains(el)) io.observe(el); });
    let past = false;
    const paint = () => {
        const on = past && blockers.size === 0;
        bar.classList.toggle('is-on', on);
        bar.setAttribute('aria-hidden', on ? 'false' : 'true');
        link.tabIndex = on ? 0 : -1;
    };
    const onScroll = () => {
        const p = window.scrollY > window.innerHeight * 0.9;
        if (p !== past) { past = p; paint(); }
    };
    addEventListener('scroll', onScroll, { passive: true });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', watch); else watch();
    onScroll();
})();
</script>
