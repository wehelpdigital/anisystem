{{-- The Community from inside somebody's farm (2026-10-15, the owner).

     A worker standing in a boss's farm has no Community there: every worker
     login is a full account of its own, and the Community (feed, groups,
     blog, messages, co-farmers) belongs to that account, never to the farm
     they work on (WorkerContext::canUseCommunity). Rather than hide the door
     or answer it with "no access", any way into /app/community from a farm
     opens this notice: it says why, and one tap switches to the worker's own
     account (worker.switch, bossId 0) and lands on the page they were going
     to, the blog, a post, a group, their messages.

     Included by layouts.app only while WorkerContext::inWorkerContext(). It
     catches every same origin link into /app/community; a page that reaches
     the Community some other way calls window.aneeWorkerCommunity(url).
     The refusal page (sm.community-switch) opens it on load. --}}
@php
    $wcsGrant = \App\Support\WorkerContext::activeGrant();
    $wcsBoss = trim((string) optional(optional($wcsGrant)->boss)->full_name) ?: 'this farm';
    $wcsFarm = $wcsBoss === 'this farm' ? 'this farm' : $wcsBoss . '\'s farm';
    $wcsMe = auth()->user();
@endphp
<style>
    .wcs-veil { position: fixed; inset: 0; z-index: 240; display: flex; align-items: flex-end; justify-content: center; background: rgb(12 20 8 / .55);
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .wcs-veil.is-on { opacity: 1; }
    .wcs-veil[hidden] { display: none; }
    .wcs-card { position: relative; width: 100%; max-width: 27rem; max-height: 92vh; overflow-y: auto; border-radius: 1.5rem 1.5rem 0 0; background: var(--color-white);
        padding-bottom: env(safe-area-inset-bottom); transform: translateY(24px); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .wcs-veil.is-on .wcs-card { transform: none; }
    @media (min-width: 640px) { .wcs-veil { align-items: center; padding: 1rem; } .wcs-card { border-radius: 1.5rem; } }
    .wcs-top { position: relative; padding: 1.3rem 1.25rem 1.1rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #6aa33a 0%, #2d5016 55%, #142509 100%); }
    .wcs-x { position: absolute; right: .7rem; top: .7rem; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: rgb(255 255 255 / .16); }
    .wcs-x svg { width: 1rem; height: 1rem; }
    .wcs-kick { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #2b3a1c; background: #f5c518; }
    .wcs-top h3 { margin-top: .55rem; padding-right: 1.6rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.25rem; line-height: 1.22; }
    .wcs-top p { margin-top: .35rem; font-size: .86rem; line-height: 1.55; color: #d6e8c2; }
    /* The two hats, side by side: the farm you are in, and your own account. */
    .wcs-hats { display: flex; align-items: center; justify-content: center; gap: .6rem; padding: 1.1rem 1.15rem .2rem; }
    .wcs-hat { flex: 1 1 0; min-width: 0; display: grid; justify-items: center; gap: .3rem; padding: .7rem .5rem; border-radius: 1rem; text-align: center; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .wcs-hat.is-to { background: #f1f8e9; border-color: #cfe5b8; }
    .wcs-face { width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; overflow: hidden; font-size: .8rem; font-weight: 800; color: var(--color-brand-800, #2d5016); background: var(--color-brand-100, #e2efd3); }
    .wcs-face img { width: 100%; height: 100%; object-fit: cover; }
    .wcs-hat b { max-width: 100%; font-size: .8rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wcs-hat small { font-size: .7rem; color: var(--color-gray-500); }
    .wcs-arrow { flex: none; width: 1.6rem; height: 1.6rem; color: #4a7c2a; animation: wcsNudge 1.6s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes wcsNudge { 0%, 100% { transform: translateX(-2px); } 50% { transform: translateX(3px); } }
    .wcs-body { padding: .9rem 1.15rem 1.2rem; display: grid; gap: .75rem; }
    .wcs-to { font-size: .88rem; line-height: 1.5; color: var(--color-gray-600); text-align: center; }
    .wcs-to b { color: var(--color-gray-900); }
    .wcs-acts { display: grid; gap: .5rem; }
    .wcs-acts .btn { width: 100%; justify-content: center; text-align: center; text-wrap: balance; line-height: 1.25; }
    .wcs-acts .btn[disabled] { opacity: .7; }
    .wcs-stay { justify-self: center; padding: .35rem .6rem; font-size: .82rem; font-weight: 700; color: var(--color-gray-500); text-decoration: underline; text-underline-offset: 3px; }
    html.dark .wcs-card { background: #141a10; }
    html.dark .wcs-hat { background: #1a2114; border-color: #2b3a1c; } html.dark .wcs-hat.is-to { background: #1f2b15; border-color: #3d5427; }
    html.dark .wcs-hat b, html.dark .wcs-to b { color: #eef3e8; } html.dark .wcs-to { color: #b9c6ad; }
    html.dark .wcs-face { background: #2a3a1d; color: #cfe0bb; }
    @media (prefers-reduced-motion: reduce) { .wcs-veil, .wcs-card { transition: none; } .wcs-arrow { animation: none; } }
</style>
<div class="wcs-veil" id="wcsBox" hidden role="dialog" aria-modal="true" aria-labelledby="wcsTitle">
    <div class="wcs-card">
        <div class="wcs-top">
            <button type="button" class="wcs-x" data-wcs-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            <span class="wcs-kick">Community</span>
            <h3 id="wcsTitle">Open <span data-wcs-what>the Community</span> in your own account</h3>
            <p>You are working in {{ $wcsFarm }} right now. The Community is part of your own account: your feed, your cofarmers and your own name on every post.</p>
        </div>
        <div class="wcs-hats" aria-hidden="true">
            <span class="wcs-hat">
                <span class="wcs-face">{{ optional(optional($wcsGrant)->boss)->initials ?: '?' }}</span>
                <b>{{ $wcsBoss === 'this farm' ? 'This farm' : $wcsBoss }}</b><small>You work here</small>
            </span>
            <svg class="wcs-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            <span class="wcs-hat is-to">
                <span class="wcs-face">
                    @if (filled(optional($wcsMe)->avatarPath))
                        <img src="{{ \App\Support\MediaStore::url($wcsMe->avatarPath) }}" alt="" data-avatar-fallback data-initials="{{ optional($wcsMe)->initials ?: '?' }}">
                    @else
                        {{ optional($wcsMe)->initials ?: '?' }}
                    @endif
                </span>
                <b>{{ optional($wcsMe)->full_name ?: 'You' }}</b><small>Your own account</small>
            </span>
        </div>
        <div class="wcs-body">
            <p class="wcs-to">Switch to your own account and we take you straight to <b data-wcs-what>the Community</b>. You can come back to {{ $wcsFarm }} any time from Switch farm.</p>
            <form method="POST" action="{{ route('worker.switch') }}" class="wcs-acts" id="wcsForm">
                @csrf
                <input type="hidden" name="bossId" value="0">
                <input type="hidden" name="next" id="wcsNext" value="{{ route('community.index', [], false) }}">
                <button type="submit" class="btn btn-primary btn-lg" id="wcsGo"><span id="wcsGoText">Switch and open the Community</span></button>
            </form>
            <button type="button" class="wcs-stay" data-wcs-close>Stay in {{ $wcsFarm }}</button>
        </div>
    </div>
</div>
<script>
(() => {
    const box = document.getElementById('wcsBox');
    if (!box || window.aneeWorkerCommunity) return;
    const nextIn = document.getElementById('wcsNext'), go = document.getElementById('wcsGo'), goText = document.getElementById('wcsGoText');
    // What the tap was going to: named in the notice and on the button.
    const NAMES = [
        [/^\/app\/community\/blog\/\d+/, 'this blog post'], [/^\/app\/community\/blog/, 'the Blog'],
        [/^\/app\/community\/groups\/\d+/, 'this group'], [/^\/app\/community\/groups/, 'Discussion Groups'],
        [/^\/app\/community\/messages\/\d+/, 'this conversation'], [/^\/app\/community\/messages/, 'your messages'],
        [/^\/app\/community\/members\/\d+/, 'this farmer\'s page'], [/^\/app\/community\/members/, 'the members'],
        [/^\/app\/community\/post\/\d+/, 'this post'], [/^\/app\/community\/wall\//, 'this post'],
        [/^\/app\/community\/reels/, 'Reels'], [/^\/app\/community\/ranking/, 'the Ranking'],
        [/^\/app\/community\/saved/, 'your saved posts'], [/^\/app\/community\/co-farmers/, 'your cofarmers'],
        [/^\/app\/community\/hashtag\//, 'this hashtag'], [/^\/app\/community\/location\//, 'this place'],
        [/^\/app\/community\/my-photos/, 'your photos'], [/^\/app\/community\/requests/, 'your requests'],
    ];
    const nameOf = (path) => (NAMES.find(([re]) => re.test(path)) || [null, 'the Community'])[1];
    const isCommunity = (u) => u.origin === location.origin && /^\/app\/community(\/|$)/.test(u.pathname);
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    let onClose = null;

    const close = (how) => {
        if (box.hidden) return;
        window.unregisterOverlay?.('wcs');
        box.classList.remove('is-on');
        setTimeout(() => { box.hidden = true; document.documentElement.style.overflow = ''; if (onClose && how !== 'go') onClose(); }, reduce() ? 0 : 280);
    };
    // opts.onClose: what closing does (the refusal page goes back to the farm's dashboard).
    window.aneeWorkerCommunity = (url, opts = {}) => {
        let u;
        try { u = new URL(url || '/app/community', location.href); } catch (e) { u = new URL('/app/community', location.href); }
        if (!isCommunity(u)) u = new URL('/app/community', location.href);
        nextIn.value = u.pathname + u.search + u.hash;
        const what = nameOf(u.pathname);
        box.querySelectorAll('[data-wcs-what]').forEach((el) => { el.textContent = what; });
        // One text node: the button is a flex row, and a word beside a span gets a gap.
        goText.textContent = 'Switch and open ' + what;
        go.disabled = false;
        onClose = opts.onClose || null;
        box.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => box.classList.add('is-on')));
        window.registerOverlay?.('wcs', () => close());
        setTimeout(() => go.focus({ preventScroll: true }), reduce() ? 0 : 300);
    };
    box.addEventListener('click', (e) => { if (e.target === box || e.target.closest('[data-wcs-close]')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !box.hidden) close(); });
    document.getElementById('wcsForm').addEventListener('submit', () => {
        go.disabled = true;
        goText.textContent = 'Switching to your account…';
        window.forgetOverlay?.('wcs');
    });

    // Back from the Community brings this page out of the cache with the notice up: put it away.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        box.classList.remove('is-on'); box.hidden = true; document.documentElement.style.overflow = ''; go.disabled = false;
    });

    // Every way into the Community from a farm comes here first: the nav,
    // the tab bar, a link in a note or a notification.
    document.addEventListener('click', (e) => {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.hasAttribute('download')) return;
        let u;
        try { u = new URL(a.href, location.href); } catch (err) { return; }
        if (!isCommunity(u)) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        window.aneeWorkerCommunity(u.href);
    }, true);
})();
</script>
