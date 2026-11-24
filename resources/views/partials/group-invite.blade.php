{{-- THE LEVEL UP FARMERS POPUP (2026-11-24, the owner: "inside anee app, have a random popup as well asking them to
     join if they havent and a button to say do not show this again"). App\Support\GroupInvite.

     Drawn only for a person who has not answered (joined, or do not show this again; both kept on the account). When
     it shows is up to the browser, and kept rare on purpose: one page in four, never in the first twenty seconds,
     never over another sheet, the tutorial card or an offline screen, at most once a day, and three days of quiet
     after "Not now". Join opens the group and counts as joined. --}}
@php $giUrl = \App\Support\GroupInvite::URL; @endphp
<div class="gi-wrap" id="giModal" hidden aria-hidden="true">
    <div class="gi-veil" data-gi-later></div>
    <div class="gi-card" role="dialog" aria-modal="true" aria-labelledby="giTitle">
        <button type="button" class="gi-x" data-gi-later aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <div class="gi-pic">
            <img src="{{ asset('images/site/photos/team-thumbs-480.webp') }}" alt="Two farmers giving a thumbs up beside their rice field" loading="lazy" width="480" height="277">
            <span class="gi-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#1877F2"/><path fill="#fff" d="M13.4 19.5v-6.1h2.05l.31-2.38H13.4V9.5c0-.69.19-1.16 1.18-1.16h1.26V6.21a16.9 16.9 0 00-1.84-.09c-1.82 0-3.07 1.11-3.07 3.15v1.75H8.87v2.38h2.06v6.1h2.47z"/></svg>Facebook group</span>
        </div>
        <div class="gi-body">
            <h3 class="gi-title" id="giTitle">Join Level Up Farmers</h3>
            <p class="gi-text">Our free Facebook group for Filipino farmers. Ask with a photo, learn from farmers who have seen it before, and get tips from our own technicians. Sama na!</p>
            <a href="{{ $giUrl }}" target="_blank" rel="noopener" class="gi-join" data-gi-join>Join the group</a>
            <div class="gi-row">
                <button type="button" class="gi-later" data-gi-later>Not now</button>
                <button type="button" class="gi-never" data-gi-never>Do not show this again</button>
            </div>
        </div>
    </div>
</div>

<style>
    .gi-wrap { position: fixed; inset: 0; z-index: 260; display: flex; align-items: flex-end; justify-content: center; }
    .gi-wrap[hidden] { display: none; }
    @media (min-width: 640px) { .gi-wrap { align-items: center; padding: 1rem; } }
    .gi-veil { position: absolute; inset: 0; background: rgb(8 14 5 / .6); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); opacity: 0;
        transition: opacity .32s cubic-bezier(.22,1,.36,1); }
    .gi-card { position: relative; width: 100%; max-width: 26rem; overflow: hidden; border-radius: 1.4rem 1.4rem 0 0; background: #fff;
        box-shadow: 0 -20px 60px -20px rgb(0 0 0 / .5); transform: translateY(100%); transition: transform .42s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 640px) { .gi-card { border-radius: 1.4rem; transform: translateY(18px) scale(.97); opacity: 0;
        transition: transform .38s cubic-bezier(.22,1,.36,1), opacity .38s cubic-bezier(.22,1,.36,1); } }
    .gi-wrap.is-on .gi-veil { opacity: 1; }
    .gi-wrap.is-on .gi-card { transform: none; opacity: 1; }
    .gi-x { position: absolute; top: .6rem; right: .6rem; z-index: 2; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center;
        color: #fff; background: rgb(0 0 0 / .45); border: 0; }
    .gi-x svg { width: 1.05rem; height: 1.05rem; }
    .gi-pic { position: relative; aspect-ratio: 16 / 8; overflow: hidden; background: #1d3310; }
    .gi-pic img { width: 100%; height: 100%; object-fit: cover; }
    .gi-pic::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 40%, rgb(12 24 7 / .55)); }
    .gi-badge { position: absolute; left: .8rem; bottom: .7rem; z-index: 1; display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem .3rem .3rem;
        border-radius: 999px; font-size: .75rem; font-weight: 800; color: #14210c; background: #fff; }
    .gi-badge svg { width: 1.25rem; height: 1.25rem; }
    .gi-body { padding: 1.1rem 1.2rem calc(1.1rem + env(safe-area-inset-bottom, 0px)); text-align: center; }
    .gi-title { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #14210c; }
    .gi-text { margin-top: .4rem; font-size: .9rem; line-height: 1.55; color: #4b5563; }
    .gi-join { display: flex; align-items: center; justify-content: center; margin-top: 1rem; padding: .85rem 1rem; border-radius: 999px; font-weight: 800;
        color: #1f1500; background: #f5c518; text-decoration: none; transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .gi-join:hover { background: #ffd84a; transform: translateY(-1px); }
    .gi-row { display: flex; justify-content: center; gap: 1.2rem; margin-top: .7rem; }
    .gi-later, .gi-never { padding: .4rem .2rem; font-size: .84rem; font-weight: 700; background: none; border: 0; cursor: pointer; }
    .gi-later { color: #4a7c2a; }
    .gi-never { color: #6b7280; text-decoration: underline; text-underline-offset: 3px; }
    html.dark .gi-card { background: #151b12; }
    html.dark .gi-title { color: #e8efe1; }
    html.dark .gi-text { color: #b9c9a8; }
    html.dark .gi-never { color: #9aa98c; }
    html.gi-lock, html.gi-lock body { overflow: hidden !important; }
    @media (prefers-reduced-motion: reduce) { .gi-veil, .gi-card { transition: none; } }
</style>

<script>
(() => {
    const wrap = document.getElementById('giModal');
    if (!wrap) return;
    const ANSWER = @json(route('group.answer'));
    const DAY = 864e5;
    const store = {
        get: (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* private window */ } },
    };
    const html = document.documentElement;
    // Already answered in this browser (the account answer arrives with the next page), embedded, or rolled out.
    if (store.get('anee_grp_answer') || html.classList.contains('is-frame') || html.classList.contains('is-embed')) return;
    if (Date.now() < Number(store.get('anee_grp_quiet') || 0)) return;
    if (Math.random() > 0.25) return;

    // Something else is on screen: the tutorial card, a sheet or any other open dialog (seen, not just present).
    const shown = (el) => !!el && !wrap.contains(el) && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
    const busy = () => {
        const tut = document.getElementById('tutvModal');
        return (tut && !tut.hidden) || html.classList.contains('tutv-lock')
            || [...document.querySelectorAll('.sheet:not(.hidden), [role="dialog"][aria-modal="true"]')].some(shown)
            || (window.aneeOffline && typeof window.aneeOffline.isDown === 'function' && window.aneeOffline.isDown())
            || document.visibilityState !== 'visible';
    };
    const tell = (answer) => {
        store.set('anee_grp_answer', answer);
        const body = new FormData();
        body.append('answer', answer);
        fetch(ANSWER, { method: 'POST', body, keepalive: true, credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', Accept: 'application/json' } }).catch(() => {});
    };
    const close = () => {
        wrap.classList.remove('is-on');
        html.classList.remove('gi-lock');
        setTimeout(() => { wrap.hidden = true; wrap.setAttribute('aria-hidden', 'true'); }, 420);
    };
    const open = () => {
        store.set('anee_grp_quiet', String(Date.now() + DAY));   // at most once a day
        wrap.hidden = false;
        wrap.setAttribute('aria-hidden', 'false');
        html.classList.add('gi-lock');
        requestAnimationFrame(() => requestAnimationFrame(() => wrap.classList.add('is-on')));
        wrap.querySelector('[data-gi-join]')?.focus({ preventScroll: true });
    };

    wrap.querySelectorAll('[data-gi-later]').forEach((b) => b.addEventListener('click', () => {
        store.set('anee_grp_quiet', String(Date.now() + 3 * DAY));
        close();
    }));
    wrap.querySelector('[data-gi-never]')?.addEventListener('click', () => { tell('never'); close(); });
    wrap.querySelector('[data-gi-join]')?.addEventListener('click', () => { tell('joined'); setTimeout(close, 250); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !wrap.hidden) { store.set('anee_grp_quiet', String(Date.now() + 3 * DAY)); close(); } });

    // Not in the first twenty seconds, and only when nothing else is on screen; a few more tries, then let it go.
    let tries = 0;
    const attempt = () => {
        if (busy()) { if (++tries < 6) setTimeout(attempt, 15000); return; }
        open();
    };
    setTimeout(attempt, 20000 + Math.random() * 25000);
})();
</script>
