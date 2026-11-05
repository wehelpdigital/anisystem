{{-- The start door for a locked member (App\Support\AutoLock): any [data-auto-start] button opens this instead of the
     lot count and the price. @once: a page with two buttons has one door. --}}
@once
<style>
    .aub-veil { position: fixed; inset: 0; z-index: 210; display: flex; align-items: center; justify-content: center; padding: 1rem;
        background: rgb(12 20 8 / .6); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .aub-veil.is-on { opacity: 1; }
    .aub-veil[hidden] { display: none; }
    .aub-card { width: 100%; max-width: 24rem; padding: 1.4rem 1.3rem 1.2rem; border-radius: 1.3rem; text-align: center; background: var(--color-white);
        box-shadow: 0 30px 70px -30px rgb(0 0 0 / .6); transform: translateY(14px) scale(.98); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .aub-veil.is-on .aub-card { transform: none; }
    .aub-card img { width: 4.5rem; height: 4.5rem; margin: 0 auto .6rem; object-fit: contain; }
    .aub-card h3 { font-size: 1.1rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900); }
    .aub-card p { margin-top: .5rem; font-size: .875rem; line-height: 1.5; color: var(--color-gray-600); }
    .aub-card button { margin-top: 1rem; width: 100%; }
    @media (prefers-reduced-motion: reduce) { .aub-veil, .aub-card { transition: none; } }
</style>
<div class="aub-veil" id="autoBuilding" role="dialog" aria-modal="true" aria-labelledby="autoBuildingT" hidden>
    <div class="aub-card">
        <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">
        <h3 id="autoBuildingT">{{ \App\Support\AutoLock::TITLE }}</h3>
        <p>Anee Auto is the season Anee plans for you and keeps planning every morning. It is not open yet, and it opens here as soon as it is ready. Everything else in anee.io is ready to use.</p>
        <button type="button" class="btn btn-primary" data-aub-close>OK</button>
    </div>
</div>
<script>
    (() => {
        const veil = document.getElementById('autoBuilding');
        let timer = null;
        const close = () => {
            veil.classList.remove('is-on');
            clearTimeout(timer);
            timer = setTimeout(() => { veil.hidden = true; }, 280);
        };
        const open = () => {
            clearTimeout(timer);
            veil.hidden = false;
            void veil.offsetWidth;
            veil.classList.add('is-on');
            window.registerOverlay?.('autoBuilding', close);
            veil.querySelector('[data-aub-close]').focus();
        };
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-auto-start]')) { e.preventDefault(); open(); return; }
            if (e.target === veil || e.target.closest('[data-aub-close]')) close();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !veil.hidden) close(); });
    })();
</script>
@endonce
