{{-- The members' gate's styles and script, shared by the guide pages'
     gate (content-gate) and the catalogue and finder hubs' gate (hub-gate).
     Pushed once. See App\Support\ContentGate. --}}
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
    .sp-gate-foot { margin: .8rem auto 0 !important; font-size: .85rem !important; font-weight: 800; color: #3d6823 !important; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .sp-gate-foot:empty { display: none; }
    .sp-gate-free { margin: .7rem auto 0 !important; font-size: .78rem !important; font-weight: 700; color: #6b7f5a !important; }
    .sp-gate-acts { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; margin-top: 1.1rem; }
    .sp-gate-acts .btn { min-width: 11rem; }
    .sp-gate-tools { margin-top: .9rem; font-size: .8rem; line-height: 1.55; color: #6b7280; }
    .sp-gate-as { margin: -.3rem 0 1rem; padding: .55rem .8rem; border-radius: .8rem; background: #fff7e0; border: 1px solid #f3dc9b; font-size: .82rem; line-height: 1.45; color: #6b4e00; }
    .sp-gate-as a { font-weight: 800; color: #6b4e00; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }
    @media (max-width: 639.98px) { .sp-gate-card { margin-top: -14rem; padding: 1.25rem 1rem 1.1rem; } .sp-gate-acts .btn { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { .sp-gate-card { transition: none; opacity: 1; transform: none; } }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* The gate's card comes up as it scrolls in, and a link into the gated
       part (or to the card) brings the reader to the card instead. */
    const card = document.querySelector('[data-gate-card]');
    if (!card) return;
    const show = () => card.classList.add('is-in');
    /* Arrived on the card itself (the "See it as a guest" link, or the
       chip at the top): show it at once, without waiting to scroll in. */
    if (location.hash === '#spGateCard') show();
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) { show(); io.disconnect(); } }, { rootMargin: '0px 0px -10% 0px' });
        io.observe(card);
    } else { show(); }
    const gated = document.querySelector('.sp-gated');
    const toCard = () => {
        show();
        card.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
        card.classList.add('is-pulse');
        setTimeout(() => card.classList.remove('is-pulse'), 1400);
    };
    window.aneeGateCard = toCard;
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a) return;
        const t = document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)));
        if (!t || (t !== card && !(gated && gated.contains(t)))) return;
        e.preventDefault();
        toCard();
    });
})();
</script>
@endpush
@endonce
