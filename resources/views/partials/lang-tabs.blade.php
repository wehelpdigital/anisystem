{{-- ================================================================
     English and Taglish in one card (2026-10-17, the owner: "Anong epekto
     sayo o sa bukid mo ... give the english and taglish version in a mini
     tab in that card"). A news story's "so what" card carries both texts
     (the callout's text and textTl, App\Support\NewsTaglish); a small switch
     in the card shows one of them.

     One approach for both places that draw the card:
       - the public page (public/site/blocks) draws the whole switch itself:
         the tab list, the two panels, the English one hidden;
       - the Technician's Blog stores its article as HTML that keeps only
         classes and lang (CommunityText::articleHtml), so there the card
         holds the two panels (the English one .is-off) and this script makes
         the tab list.
     Markup either way: .lt card > [b title] .lt-pane.lt-en + .lt-pane.lt-tl
     (lang="fil"), with .lt-head > b + .lt-tabs > .lt-tab[data-lang] when drawn.

     Taglish opens first (the owner asked for the part in Taglish, the email
     already says it so, and the card's own title is Tagalog). The reader's
     last pick is remembered (localStorage, same key on the site and in the
     app) and turns every card on the page; without storage it simply opens
     on Taglish each time. Colours come from --lt-* set by each page.
     ================================================================ --}}
@once
<style>
    .lt { --lt-ease: cubic-bezier(.22,1,.36,1); }
    .lt-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .45rem .75rem; }
    .lt-head > b { flex: 1 1 11rem; min-width: 0; margin: 0 !important; }
    .lt-tabs { position: relative; isolation: isolate; flex: none; display: inline-grid; grid-template-columns: 1fr 1fr; padding: .2rem; border-radius: 999px;
        background: var(--lt-track, #fff); border: 1px solid var(--lt-line, #c9e0ad); }
    /* The green thumb slides under the tab that is on. */
    .lt-tabs::before { content: ''; position: absolute; z-index: -1; top: .2rem; bottom: .2rem; left: .2rem; width: calc(50% - .2rem); border-radius: 999px;
        background: var(--lt-on, #4a7c2a); box-shadow: 0 2px 6px -2px rgb(20 33 12 / .4); transition: transform .28s var(--lt-ease); }
    .lt-tabs.is-tl::before { transform: translateX(100%); }
    .lt-tabs.is-still::before { transition: none; }
    .lt-tab { appearance: none; margin: 0; border: 0; background: transparent; cursor: pointer; min-height: 2rem; padding: .3rem .85rem; border-radius: 999px;
        font: inherit; font-size: .78rem; font-weight: 800; line-height: 1.2; letter-spacing: .01em; white-space: nowrap; color: var(--lt-ink, #2d5016);
        transition: color .28s var(--lt-ease); -webkit-tap-highlight-color: transparent; }
    .lt-tab[aria-selected="true"] { color: var(--lt-on-ink, #fff); }
    .lt-tab:focus-visible { outline: 2px solid var(--lt-on, #4a7c2a); outline-offset: 2px; }
    @media (max-width: 639.98px) { .lt-tab { min-height: 2.25rem; padding: .35rem .95rem; font-size: .8rem; } }
    .lt-head ~ .lt-pane { margin-top: .4rem; }
    .lt-pane:focus-visible { outline: 2px solid var(--lt-on, #4a7c2a); outline-offset: 3px; border-radius: .35rem; }
    .lt-pane[hidden], .lt-pane.is-off { display: none !important; }
    .lt-pane.is-in { animation: ltIn .28s var(--lt-ease) both; }
    @keyframes ltIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .lt-tabs::before, .lt-tab { transition: none; }
        .lt-pane.is-in { animation: none; }
    }
    html.sm-still .lt-tabs::before, html.sm-still .lt-tab { transition: none; }
    html.sm-still .lt-pane.is-in { animation: none; }
</style>
<script>
(() => {
    if (window.aneeLangTabs) return;
    window.aneeLangTabs = true;
    const KEY = 'anee.soWhat.lang';
    const FIRST = 'tl';
    const NAMES = { en: 'English', tl: 'Taglish' };
    const EASE = 'cubic-bezier(.22,1,.36,1)';
    const still = () => document.documentElement.classList.contains('sm-still')
        || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const stored = () => { try { const v = window.localStorage.getItem(KEY); return v === 'en' || v === 'tl' ? v : null; } catch (_) { return null; } };
    const remember = (v) => { try { window.localStorage.setItem(KEY, v); } catch (_) {} };
    let pick = stored() || FIRST;
    let seq = 0;

    const parts = (card) => ({ en: card.querySelector('.lt-pane.lt-en'), tl: card.querySelector('.lt-pane.lt-tl'), list: card.querySelector('.lt-tabs') });

    // The Tech Blog's card: the tab list made here, the panels tied to it.
    function makeTabs(card, p) {
        const id = 'lt' + (++seq) + '-' + Math.random().toString(36).slice(2, 6);
        const list = document.createElement('div');
        list.className = 'lt-tabs' + (FIRST === 'tl' ? ' is-tl' : '');
        list.setAttribute('role', 'tablist');
        list.setAttribute('aria-label', 'Language');
        ['en', 'tl'].forEach((lang) => {
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'lt-tab';
            tab.id = id + '-tab-' + lang;
            tab.dataset.lang = lang;
            tab.textContent = NAMES[lang];
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', id + '-' + lang);
            list.appendChild(tab);
            p[lang].id = id + '-' + lang;
            p[lang].setAttribute('role', 'tabpanel');
            p[lang].setAttribute('aria-labelledby', tab.id);
            p[lang].tabIndex = 0;
        });
        const head = document.createElement('div');
        head.className = 'lt-head';
        const box = p.en.parentNode;
        const title = [...box.children].find((el) => el.tagName === 'B');
        box.insertBefore(head, title || p.en);
        if (title) head.appendChild(title);
        head.appendChild(list);
        p.list = list;
    }

    function ready(card) {
        const p = parts(card);
        if (!p.en || !p.tl || card.classList.contains('is-ready')) return;
        if (!p.list) makeTabs(card, p);
        p.en.classList.remove('is-off');
        p.tl.classList.remove('is-off');
        card.classList.add('is-ready');
        card.ltOn = null;
        show(card, pick, 'none');
    }

    // mode: 'full' (the card's height and the words move), 'fade' (the words only), 'none'.
    function show(card, lang, mode) {
        if (card.ltOn === lang) return;
        const p = parts(card);
        const h0 = mode === 'full' ? card.getBoundingClientRect().height : 0;
        ['en', 'tl'].forEach((k) => {
            const on = k === lang;
            p[k].hidden = !on;
            const tab = p.list && p.list.querySelector('.lt-tab[data-lang="' + k + '"]');
            if (tab) {
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
            }
        });
        if (p.list) {
            if (mode === 'none') p.list.classList.add('is-still');
            p.list.classList.toggle('is-tl', lang === 'tl');
            if (mode === 'none') { void p.list.offsetWidth; p.list.classList.remove('is-still'); }
        }
        const was = card.ltOn;
        card.ltOn = lang;
        if (mode === 'none' || was === null) return;
        p[lang].classList.remove('is-in');
        void p[lang].offsetWidth;
        p[lang].classList.add('is-in');
        if (mode !== 'full' || !card.animate) return;
        const h1 = card.getBoundingClientRect().height;
        if (Math.abs(h1 - h0) < 1) return;
        if (card.ltAnim) card.ltAnim.cancel();
        card.style.overflow = 'hidden';
        const a = card.animate([{ height: h0 + 'px' }, { height: h1 + 'px' }], { duration: 280, easing: EASE });
        card.ltAnim = a;
        const done = () => { if (card.ltAnim === a) { card.ltAnim = null; card.style.overflow = ''; } };
        a.onfinish = done;
        a.oncancel = done;
    }

    const scrollerOf = (el) => {
        for (let n = el.parentElement; n && n !== document.body && n !== document.documentElement; n = n.parentElement) {
            const s = getComputedStyle(n).overflowY;
            if ((s === 'auto' || s === 'scroll') && n.scrollHeight > n.clientHeight + 1) return n;
        }
        return document.scrollingElement || document.documentElement;
    };

    // A pick turns every card on the page. The card tapped moves; the cards above it change
    // at once and the page is held still by hand (so the words under the thumb stay put),
    // the ones below in sight move too, the ones out of sight just change.
    function choose(card, lang) {
        pick = lang;
        remember(lang);
        const motion = !still();
        const scroller = scrollerOf(card);
        const y0 = card.getBoundingClientRect().top;
        const vh = window.innerHeight || document.documentElement.clientHeight;
        const anchor = scroller.style.overflowAnchor;
        scroller.style.overflowAnchor = 'none';
        document.querySelectorAll('.lt.is-ready').forEach((c) => {
            if (c === card) return;
            const r = c.getBoundingClientRect();
            const seen = r.bottom > 0 && r.top < vh;
            show(c, lang, !motion || !seen ? 'none' : (r.top > y0 ? 'full' : 'fade'));
        });
        show(card, lang, motion ? 'full' : 'none');
        const dy = card.getBoundingClientRect().top - y0;
        if (Math.abs(dy) > .5) scroller.scrollBy(0, dy);
        requestAnimationFrame(() => { scroller.style.overflowAnchor = anchor; });
    }

    document.addEventListener('click', (e) => {
        const tab = e.target.closest ? e.target.closest('.lt .lt-tab') : null;
        const card = tab && tab.closest('.lt');
        if (card && card.classList.contains('is-ready')) choose(card, tab.dataset.lang);
    });
    // The tabs' keys: left and right (round), Home and End.
    document.addEventListener('keydown', (e) => {
        const tab = e.target.closest ? e.target.closest('.lt .lt-tab') : null;
        if (!tab) return;
        const tabs = [...tab.parentNode.querySelectorAll('.lt-tab')];
        const at = tabs.indexOf(tab);
        const to = { ArrowRight: (at + 1) % tabs.length, ArrowLeft: (at - 1 + tabs.length) % tabs.length, Home: 0, End: tabs.length - 1 }[e.key];
        if (to === undefined) return;
        e.preventDefault();
        tabs[to].focus();
        const card = tab.closest('.lt');
        if (card && card.classList.contains('is-ready')) choose(card, tabs[to].dataset.lang);
    });

    const start = () => document.querySelectorAll('.lt').forEach(ready);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
</script>
@endonce
