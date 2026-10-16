{{-- "Get anee.io on your phone" (2026-10-17, the owner: "if the app is not
     yet installed show a modal box randomly but persistent ... to force users
     to install the app in the phone").

     Only in the browser, never inside the installed app. On a phone always;
     on a computer only where the browser can install it. It comes back every
     now and then: a first look a few seconds into a visit, then a check every
     minute or two, each a coin toss, never twice within GAP, never over an
     open sheet, a wait or a field being typed in. "Do not show for 1 day"
     sets the cookie anee_install_snooze for a day. Installing (the browser's
     own prompt, or the steps for iPhone and for browsers without one) ends
     it on this browser. The browser's install prompt is shared with the
     account menu's button (resources/js/app.js keeps it on
     window.aneeInstallPrompt). --}}
<div class="in-wrap" id="installNudge" hidden aria-hidden="true">
    <div class="in-backdrop" data-in-close></div>
    <div class="in-card" role="dialog" aria-modal="true" aria-labelledby="inTitle" aria-describedby="inSub">
        <span class="in-shine" aria-hidden="true"></span>
        <button type="button" class="in-x" data-in-close aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <div class="in-body">
            <span class="in-icon" aria-hidden="true"><img src="{{ asset('images/pwa/icon-192.png') }}?v=anee" alt="" width="64" height="64"></span>
            <h3 class="in-title" id="inTitle">Get anee.io on your phone</h3>
            <p class="in-sub" id="inSub">Install it like any app. It is free and takes a few seconds.</p>
            <ul class="in-why">
                <li><span aria-hidden="true">📲</span><span><b>One tap from your home screen</b><small>No browser, no typing the address.</small></span></li>
                <li><span aria-hidden="true">📶</span><span><b>Works with a weak signal</b><small>Your season stays open out in the field.</small></span></li>
                <li><span aria-hidden="true">🔔</span><span><b>Tells you when you are needed</b><small>Tasks, your team's messages and Anee's answers.</small></span></li>
            </ul>
            {{-- How to, where the browser has no install prompt of its own. --}}
            <div class="in-steps" id="inSteps" hidden></div>
            <div class="in-acts">
                <button type="button" class="in-go" id="inGo">Install the app</button>
                <button type="button" class="in-later" id="inLater">Do not show for 1 day</button>
            </div>
        </div>
    </div>
</div>

<style>
    .in-wrap { position: fixed; inset: 0; z-index: 246; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .in-wrap { align-items: center; } }
    .in-wrap[hidden] { display: none; }
    .in-backdrop { position: absolute; inset: 0; background: rgb(3 7 18 / .55); animation: inFade .28s ease both; }
    .in-wrap.is-out .in-backdrop { animation: inFadeOut .26s ease both; }
    @keyframes inFade { from { opacity: 0; } }
    @keyframes inFadeOut { to { opacity: 0; } }
    .in-card { position: relative; width: min(28rem, 100%); max-height: calc(100dvh - 1rem); overflow-y: auto; border-radius: 1.3rem 1.3rem 0 0;
        background: var(--color-white, #fff); box-shadow: 0 -20px 60px -25px rgb(0 0 0 / .6);
        animation: inUp .42s cubic-bezier(.22,1,.36,1) both; padding-bottom: env(safe-area-inset-bottom, 0px); }
    .in-wrap.is-out .in-card { animation: inDown .3s cubic-bezier(.22,1,.36,1) both; }
    @media (min-width: 640px) {
        .in-card { border-radius: 1.3rem; animation-name: inPop; }
        .in-wrap.is-out .in-card { animation-name: inPopOut; }
    }
    @keyframes inUp { from { transform: translateY(100%); } }
    @keyframes inDown { to { transform: translateY(100%); } }
    @keyframes inPop { from { transform: scale(.94); opacity: 0; } }
    @keyframes inPopOut { to { transform: scale(.96); opacity: 0; } }
    /* One slow sweep of light so the card is noticed, then it settles. */
    .in-shine { position: absolute; inset: 0; pointer-events: none; z-index: 1;
        background: linear-gradient(100deg, transparent 30%, rgb(168 204 126 / .26) 50%, transparent 70%);
        background-size: 250% 100%; animation: inShine 2.6s ease-out 2; }
    @keyframes inShine { from { background-position: 140% 0; } to { background-position: -40% 0; } }
    .in-x { position: absolute; top: .65rem; right: .7rem; z-index: 2; width: 2.5rem; height: 2.5rem; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400, #9ca3af);
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .in-x svg { width: 1.05rem; height: 1.05rem; }
    .in-x:hover { background: var(--color-gray-100, #f3f4f6); }
    .in-body { position: relative; padding: 1.5rem 1.25rem 1.2rem; text-align: center; }
    .in-icon { display: inline-grid; place-items: center; width: 4.6rem; height: 4.6rem; border-radius: 1.3rem; background: #fff;
        box-shadow: 0 10px 26px -12px rgb(61 104 35 / .65), 0 0 0 1px rgb(61 104 35 / .12); animation: inBob 2.8s ease-in-out .6s 2; }
    .in-icon img { width: 4rem; height: 4rem; border-radius: 1rem; }
    @keyframes inBob { 50% { transform: translateY(-4px); } }
    .in-title { margin-top: .75rem; font-family: var(--font-heading, inherit); font-size: 1.22rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900, #111827); }
    .in-sub { margin-top: .3rem; font-size: .86rem; line-height: 1.5; color: var(--color-gray-500, #6b7280); }
    .in-why { display: grid; gap: .45rem; margin: 1rem 0 0; padding: 0; list-style: none; text-align: left; }
    .in-why li { display: flex; align-items: flex-start; gap: .65rem; padding: .55rem .7rem; border-radius: .9rem; background: #f6f9f2; border: 1px solid #e3edd8; }
    .in-why li > span:first-child { flex: none; font-size: 1.15rem; line-height: 1.4; }
    .in-why b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900, #111827); }
    .in-why small { display: block; font-size: .76rem; line-height: 1.4; color: var(--color-gray-500, #6b7280); }
    .in-steps { margin-top: .9rem; padding: .75rem .85rem; border-radius: .95rem; text-align: left; background: #fffbeb; border: 1px solid #fde68a; animation: inFade .3s ease both; }
    .in-steps p { font-size: .8rem; font-weight: 800; color: #92400e; }
    .in-steps ol { margin: .4rem 0 0; padding-left: 1.2rem; display: grid; gap: .3rem; list-style: decimal; }
    .in-steps li::marker { font-weight: 800; }
    .in-steps li { font-size: .84rem; line-height: 1.45; color: #78350f; }
    .in-steps svg { display: inline-block; width: 1.05rem; height: 1.05rem; vertical-align: -.2rem; margin: 0 .1rem; }
    .in-acts { display: grid; gap: .5rem; margin-top: 1.05rem; }
    .in-go { min-height: 3rem; border-radius: .95rem; font-size: .98rem; font-weight: 800; color: #fff; background: #4a7c2a;
        box-shadow: 0 12px 24px -14px rgb(61 104 35 / .9); transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .in-go:hover { background: #3d6823; }
    .in-go:active { transform: scale(.98); }
    .in-later { min-height: 2.75rem; border-radius: .9rem; font-size: .86rem; font-weight: 700; color: var(--color-gray-500, #6b7280);
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .in-later:hover { background: var(--color-gray-100, #f3f4f6); }
    html.dark .in-card { background: #151b12; }
    html.dark .in-title, html.dark .in-why b { color: #e8efe1; }
    html.dark .in-sub, html.dark .in-why small { color: #b7c2ad; }
    html.dark .in-why li { background: #1a2414; border-color: #2b3a1c; }
    html.dark .in-steps { background: #2a210a; border-color: #5c4a24; }
    html.dark .in-steps p, html.dark .in-steps li { color: #fcd34d; }
    html.dark .in-x:hover, html.dark .in-later:hover { background: #22301a; }
    @media (prefers-reduced-motion: reduce) {
        .in-card, .in-backdrop, .in-shine, .in-icon, .in-steps, .in-wrap.is-out .in-card, .in-wrap.is-out .in-backdrop { animation: none; transition: none; }
    }
</style>

<script>
(() => {
    const wrap = document.getElementById('installNudge');
    if (!wrap) return;
    const SNOOZE = 'anee_install_snooze';       // the cookie: a day of quiet
    const LAST = 'anee.installNudgeAt';          // when it last showed
    const DONE = 'anee.installedHere';           // installed from this browser
    const GAP = 12 * 60 * 1000;                  // never twice within this
    const installed = () => ['standalone', 'fullscreen', 'minimal-ui'].some((m) => window.matchMedia('(display-mode: ' + m + ')').matches)
        || window.navigator.standalone === true;
    const snoozed = () => document.cookie.split(';').some((c) => c.trim().startsWith(SNOOZE + '='));
    const store = (k, v) => { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (_) { return null; } return null; };
    if (installed() || snoozed() || store(DONE) === '1') return;

    const ua = navigator.userAgent || '';
    const iOS = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
    const android = /android/i.test(ua);
    const inApp = /FBAN|FBAV|FB_IAB|Instagram|Line\/|Messenger|TikTok/i.test(ua);
    const phone = iOS || android || (window.matchMedia('(pointer: coarse)').matches && Math.min(screen.width, screen.height) < 820);
    // The browser's own install prompt, kept by app.js; caught here too in case it comes later.
    const deferred = () => window.aneeInstallPrompt || null;
    window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); window.aneeInstallPrompt = e; });
    window.addEventListener('appinstalled', () => { store(DONE, '1'); close(); });

    const SHARE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-label="Share"><path d="M12 15V3m0 0l-4 4m4-4l4 4"/><path d="M7 10H5.5A1.5 1.5 0 004 11.5v8A1.5 1.5 0 005.5 21h13a1.5 1.5 0 001.5-1.5v-8a1.5 1.5 0 00-1.5-1.5H17"/></svg>';
    const DOTS = '<svg viewBox="0 0 24 24" fill="currentColor" aria-label="Menu"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>';
    // The steps where no prompt can be fired: iPhone's Share sheet, a browser's menu, or out of an app's own browser first.
    const steps = () => inApp
        ? '<p>Open anee.io in your phone\'s browser first</p><ol><li>Tap the menu ' + DOTS + ' at the top of this screen.</li><li>Tap <b>Open in browser</b> (or Open in Chrome or Safari).</li><li>There, tap <b>Install the app</b> when this card shows again.</li></ol>'
        : iOS
            ? '<p>On iPhone and iPad, in Safari</p><ol><li>Tap the Share button ' + SHARE + ' at the bottom of the screen.</li><li>Scroll down and tap <b>Add to Home Screen</b>.</li><li>Tap <b>Add</b>. anee.io is now on your home screen.</li></ol>'
            : '<p>In your browser</p><ol><li>Tap the menu ' + DOTS + ' at the top right.</li><li>Tap <b>Install app</b> or <b>Add to Home screen</b>.</li><li>Tap <b>Install</b>. anee.io is now on your home screen.</li></ol>';

    // Not over another card, a sheet, a wait or a field being typed in, and not in a hidden tab.
    const busy = () => document.hidden
        || !!document.querySelector('dialog[open], .sheet.is-open, .note-lb.is-open, .draw-modal.show, #tutvModal:not([hidden]), #reviewPrompt:not([hidden]), #aneeWaitVeil:not([hidden])')
        || document.documentElement.classList.contains('va-view-lock')
        || ['INPUT', 'TEXTAREA', 'SELECT'].includes((document.activeElement || {}).tagName || '')
        || (document.activeElement && document.activeElement.isContentEditable);
    const due = () => Date.now() - (parseInt(store(LAST) || '0', 10) || 0) > GAP;
    // A computer only where its browser can install the app; a phone always (it has a way, the steps say which).
    const able = () => phone || !!deferred();

    let open = false;
    function show() {
        if (open || installed() || snoozed()) return;
        open = true;
        const st = document.getElementById('inSteps');
        st.hidden = true; st.innerHTML = '';
        const go = document.getElementById('inGo');
        go.textContent = deferred() ? 'Install the app' : 'Show me how';
        delete go.dataset.step;
        wrap.classList.remove('is-out');
        wrap.hidden = false;
        wrap.setAttribute('aria-hidden', 'false');
        store(LAST, String(Date.now()));
        window.registerOverlay?.('installNudge', close);
        setTimeout(() => document.getElementById('inGo').focus({ preventScroll: true }), 60);
    }
    function close() {
        if (!open) return;
        open = false;
        window.unregisterOverlay?.('installNudge');
        const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        wrap.classList.add('is-out');
        setTimeout(() => { wrap.hidden = true; wrap.setAttribute('aria-hidden', 'true'); wrap.classList.remove('is-out'); }, still ? 0 : 300);
    }

    wrap.addEventListener('click', async (e) => {
        if (e.target.closest('[data-in-close]')) { close(); return; }
        if (e.target.closest('#inLater')) {
            document.cookie = SNOOZE + '=1; max-age=86400; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
            close();
            window.toast?.('We will not ask for a day.');
            return;
        }
        if (!e.target.closest('#inGo')) return;
        const pr = deferred();
        const go = document.getElementById('inGo');
        if (!pr) {
            // No prompt to fire here: the steps, shown in the card; the second tap closes it.
            if (go.dataset.step === 'shown') { close(); return; }
            const st = document.getElementById('inSteps');
            st.innerHTML = steps();
            st.hidden = false;
            go.textContent = 'Got it';
            go.dataset.step = 'shown';
            return;
        }
        window.aneeInstallPrompt = null;
        try {
            pr.prompt();
            const { outcome } = await pr.userChoice;
            if (outcome === 'accepted') store(DONE, '1');
        } catch (_) { /* the browser said no to a second prompt: the menu still has it */ }
        close();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && open) close(); });

    // Every now and then: a first look a few seconds in, then a check every one to two and a half minutes, each a coin toss.
    const tick = () => {
        if (installed() || snoozed() || store(DONE) === '1') return;
        if (!open && able() && due() && !busy() && Math.random() < 0.55) show();
        setTimeout(tick, 60000 + Math.random() * 90000);
    };
    setTimeout(tick, 7000 + Math.random() * 16000);
    // For a look on demand (the console, a test): the card as it would come.
    window.aneeInstallNudge = { show, close };
})();
</script>
