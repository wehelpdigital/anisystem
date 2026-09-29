{{-- "How to chat with Anee", full screen.

     Opened by any link marked data-anee-guide — "Check this for a complete
     guide" at the foot of the how-to-ask card in every chat (the chat page,
     the floating chat, the schedule chat, the Collab Room). The words are a
     How-to Guide page the mother app's block builder writes (moduleKey
     "anee-chat"), fetched the first time the window opens and kept for the
     visit; App\Support\AneeChatGuide holds the starting text. 2026-09-29. --}}
<div class="acg" id="aneeGuide" hidden role="dialog" aria-modal="true" aria-labelledby="aneeGuideTitle">
    <div class="acg-bar">
        <img class="acg-face" src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">
        <b class="acg-title" id="aneeGuideTitle">{{ \App\Support\AneeChatGuide::TITLE }}</b>
        <button type="button" class="acg-x" id="aneeGuideX" aria-label="Close the guide" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <div class="acg-scroll" id="aneeGuideScroll">
        <div class="acg-body">
            <p class="acg-sum" id="aneeGuideSum" hidden></p>
            <div class="acg-card" id="aneeGuideBody">
                <div class="acg-load" aria-label="Loading the guide"><i></i><i></i><i></i><i></i></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* The link on the how-to-ask cards. */
    .anee-guide-link { display: inline-flex; align-items: center; gap: .3rem; margin-top: .65rem; font-size: .78rem; font-weight: 800;
        color: #3d6823; text-decoration: underline; text-underline-offset: 2px; text-decoration-thickness: 1.5px; }
    .anee-guide-link svg { width: .85rem; height: .85rem; flex: none; }
    .anee-guide-link:hover { color: #2f5219; }
    html.dark .anee-guide-link { color: #a8cc7e; }
    html.dark .anee-guide-link:hover { color: #cfe6b8; }

    /* The window: above the floating chat (200) and its sheets. */
    html.acg-lock { overflow: hidden; }
    .acg { position: fixed; inset: 0; z-index: 230; display: flex; flex-direction: column; background: var(--color-gray-50, #f9fafb);
        opacity: 0; transform: translateY(14px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .acg.is-on { opacity: 1; transform: none; }
    .acg[hidden] { display: none !important; }
    .acg-bar { flex: none; display: flex; align-items: center; gap: .65rem; padding: calc(.7rem + env(safe-area-inset-top, 0px)) 1rem .7rem;
        background: var(--color-white, #fff); border-bottom: 1px solid var(--color-gray-200, #e5e7eb); }
    .acg-face { width: 2.1rem; height: 2.1rem; border-radius: 999px; object-fit: cover; flex: none; }
    .acg-title { flex: 1 1 auto; min-width: 0; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--color-gray-900, #111827);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .acg-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        color: var(--color-gray-600, #4b5563); background: var(--color-gray-100, #f3f4f6); cursor: pointer; }
    .acg-x:hover { background: var(--color-gray-200, #e5e7eb); }
    .acg-x:focus { outline: none; } .acg-x:focus-visible { outline: 2px solid #6b9f3d; outline-offset: 2px; }
    .acg-x svg { width: 1.1rem; height: 1.1rem; }
    .acg-scroll { flex: 1 1 auto; overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; }
    .acg-body { max-width: 44rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom, 0px)); }
    .acg-sum { font-size: .9rem; line-height: 1.55; color: var(--color-gray-600, #4b5563); margin: .1rem .2rem .8rem; }
    .acg-card { background: var(--color-white, #fff); border: 1px solid var(--color-gray-200, #e5e7eb); border-radius: 1.1rem; padding: 1.15rem 1.15rem .4rem; }
    .acg-load { display: grid; gap: .6rem; padding-bottom: .8rem; }
    .acg-load i { display: block; height: .8rem; border-radius: 999px; background: var(--color-gray-100, #f3f4f6); animation: acgPulse 1.2s ease-in-out infinite alternate; }
    .acg-load i:nth-child(1) { width: 45%; height: 1rem; } .acg-load i:nth-child(2) { width: 92%; } .acg-load i:nth-child(3) { width: 80%; } .acg-load i:nth-child(4) { width: 66%; }
    @keyframes acgPulse { from { opacity: .5; } to { opacity: 1; } }
    .acg-err { font-size: .88rem; color: var(--color-gray-600, #4b5563); padding-bottom: .8rem; }
    .acg-err button { margin-left: .3rem; font-weight: 800; color: #3d6823; text-decoration: underline; }
    /* The guide's blocks, as the help pages draw them (help/show). */
    .acg .tut-h { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900, #111827); margin: 1.4rem 0 .5rem; }
    .acg .tut-h:first-child { margin-top: 0; }
    .acg .tut-p { font-size: .92rem; line-height: 1.65; color: var(--color-gray-600, #4b5563); margin-bottom: .75rem; }
    .acg .tut-steps, .acg .tut-tips { margin: 0 0 1rem 1.15rem; display: flex; flex-direction: column; gap: .4rem; }
    .acg .tut-steps { list-style: decimal; }
    .acg .tut-tips { list-style: disc; }
    .acg .tut-steps li, .acg .tut-tips li { font-size: .92rem; line-height: 1.55; color: var(--color-gray-700, #374151); padding-left: .2rem; }
    .acg .tut-callout { display: flex; flex-direction: column; gap: .15rem; padding: .75rem .9rem; border-radius: .8rem; margin: 0 0 1rem;
        font-size: .88rem; line-height: 1.55; background: #f1f8ea; color: #2f5219; border: 1px solid #cfe3bd; }
    .acg .tut-callout:first-child { margin-top: 0; }
    .acg .tut-callout strong { font-weight: 800; }
    .acg .tut-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .acg .tut-good { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .acg .tut-figure { margin: 0 0 1rem; }
    .acg .tut-figure img { width: 100%; border-radius: .8rem; border: 1px solid var(--color-gray-200, #e5e7eb); }
    .acg .tut-figure figcaption { font-size: .74rem; color: var(--color-gray-400, #9ca3af); margin-top: .35rem; text-align: center; }
    .acg .tut-video { position: relative; padding-top: 56.25%; margin: 0 0 1rem; border-radius: .8rem; overflow: hidden; background: #000; }
    .acg .tut-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .acg .tut-hr { border: 0; border-top: 1px solid var(--color-gray-200, #e5e7eb); margin: 1.25rem 0; }
    html.dark .acg { background: #0f140c; }
    html.dark .acg-bar, html.dark .acg-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .acg-title, html.dark .acg .tut-h { color: #e8efe1; }
    html.dark .acg-x { background: #22301a; color: #cbd5c0; }
    html.dark .acg-x:hover { background: #2b3a1c; }
    html.dark .acg-sum, html.dark .acg .tut-p, html.dark .acg-err { color: #b7c2ad; }
    html.dark .acg .tut-steps li, html.dark .acg .tut-tips li { color: #cbd5c0; }
    html.dark .acg .tut-callout { background: #1a2513; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .acg .tut-warn { background: #262012; border-color: #6b4f16; color: #f0d9a8; }
    html.dark .acg .tut-good { background: #13241c; border-color: #2c5a44; color: #a7e3c4; }
    html.dark .acg .tut-hr, html.dark .acg .tut-figure img { border-color: #2b3a1c; }
    html.dark .acg-load i { background: #22301a; }
    @media (prefers-reduced-motion: reduce) { .acg { transition: none; transform: none; } .acg-load i { animation: none; } }
</style>

<script>
(() => {
    const el = document.getElementById('aneeGuide');
    if (!el || window.aneeChatGuide) return;
    const body = document.getElementById('aneeGuideBody');
    const sum = document.getElementById('aneeGuideSum');
    const title = document.getElementById('aneeGuideTitle');
    const scroller = document.getElementById('aneeGuideScroll');
    const x = document.getElementById('aneeGuideX');
    const URL = @json(route('anee.guide'));
    let loaded = false, loading = false, lastFocus = null, shutTimer = null;

    async function load() {
        if (loading) return;
        loading = true;
        try {
            const res = await fetch(URL, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error('not ok');
            const d = json.data || {};
            title.textContent = d.title || title.textContent;
            sum.textContent = d.summary || '';
            sum.hidden = !d.summary;
            body.innerHTML = d.html || '<p class="tut-p">The guide is being written. Check back soon.</p>';
            loaded = true;
        } catch (_) {
            body.innerHTML = '<p class="acg-err">The guide did not load. Check your connection.<button type="button" data-acg-retry>Try again</button></p>';
        } finally { loading = false; }
    }
    function open() {
        clearTimeout(shutTimer);
        lastFocus = document.activeElement;
        el.hidden = false;
        document.documentElement.classList.add('acg-lock');
        scroller.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-on')));
        if (!loaded) load();
        try { x.focus({ preventScroll: true }); } catch (_) { /* not focusable yet */ }
    }
    function close() {
        if (el.hidden) return;
        el.classList.remove('is-on');
        document.documentElement.classList.remove('acg-lock');
        shutTimer = setTimeout(() => { el.hidden = true; }, 300);
        if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (_) {} }
    }
    // Captured at the document, so the tap never also folds the card the
    // link sits in (every how-to-ask card toggles on a click anywhere in it).
    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('[data-anee-guide]');
        if (!a) return;
        e.preventDefault();
        e.stopPropagation();
        open();
    }, true);
    x.addEventListener('click', close);
    body.addEventListener('click', (e) => { if (e.target.closest('[data-acg-retry]')) { body.innerHTML = '<div class="acg-load"><i></i><i></i><i></i><i></i></div>'; load(); } });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !el.hidden) { e.stopPropagation(); close(); } }, true);
    window.aneeChatGuide = { open, close };
})();
</script>
