{{-- THE REPORT, HELD UP — the full-screen view every report in the
     Reports module opens into (2026-09-21, the owner's ask: "like the What
     to Plant analysis"). A fresh report lands in it the moment it is
     generated and saved; a row on the Saved shelf opens into the same
     screen. Its actions -- ask Anee about it, rename or describe it,
     delete it -- are small round icons in the top bar, left of the X
     (2026-09-25: the bottom bar is gone, and the X is the only close).

     One partial, one API:

         window.reportView.open({ title, node, actions, onClose })
             node    -> a DOM element the page already rendered; it is moved
                        into the view and handed back where it was on close
             actions -> [{ label, icon?, face?, kind: 'primary'|'ghost'|'danger', href?, onClick? }]
                        each one an icon button in the top bar; label is its
                        tooltip and aria-label. An action whose icon is
                        'close' or 'plus' (the old Close / New report) is
                        not drawn -- the X closes the view.
         window.reportView.close()
         window.reportView.setTitle(text)

     The styles are the analysis pages' own .va-view, said here once so a
     report page can wear them without carrying the whole sheet. --}}
@once
<style>
    .va-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-view.is-on { opacity: 1; transform: none; }
    .va-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
        padding-top: max(.7rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--color-gray-200); }
    .va-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-view-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); font-size: 1rem; cursor: pointer; }
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom)); }
    html.va-view-lock { overflow: hidden; }
    /* A sheet opened from inside the view -- the pen's editor, the delete's
       confirm -- has to clear it, and the toast has to clear them both. */
    html.va-view-lock .sheet { z-index: 120; }
    html.va-view-lock .sheet-backdrop { z-index: 110; }
    html.va-view-lock #confirm-sheet { z-index: 125; }
    html.va-view-lock #toast-stack { z-index: 130; }
    html.dark .va-view { background: #0d110a; }
    html.dark .va-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .va-view-bar b { color: #e8efe1; }
    html.dark .va-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }

    /* The actions: small round icons in the top bar, left of the X -- not
       a bar of pills under the report. Ask Anee wears her face on a green
       ring; delete is the one red icon. The label rides as the tooltip
       (and the screen reader's name); on a wide screen Ask Anee says it. */
    /* Scoped to the bar: the review prompt has an .rv-acts of its own. */
    .va-view-bar .rv-acts { flex: none; display: flex; align-items: center; justify-content: flex-end; gap: .4rem; margin: 0; }
    .va-view-bar .rv-acts[hidden] { display: none; }
    .rv-btn { position: relative; flex: none; height: 2.2rem; min-width: 2.2rem; padding: 0; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
        font-size: .78rem; font-weight: 800; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700); cursor: pointer; text-decoration: none; white-space: nowrap;
        opacity: 0; transform: scale(.8);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), background .2s, border-color .2s, color .2s; }
    .va-view.is-on .rv-btn { opacity: 1; transform: none; }
    .va-view.is-on .rv-btn:hover { transform: translateY(-1px); border-color: var(--color-brand-300); }
    .rv-btn:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
    .rv-btn img { width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; }
    .rv-btn svg { width: 1.02rem; height: 1.02rem; }
    .rv-btn .rv-btn-t { display: none; }
    .rv-btn.is-primary { border: 2px solid var(--color-brand-600); background: var(--color-brand-50); color: var(--color-brand-800); }
    .rv-btn.is-danger { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
    .va-view.is-on .rv-btn.is-danger:hover { border-color: #f87171; }
    @media (min-width: 640px) {
        .rv-btn.is-primary { padding: 0 .75rem 0 .2rem; }
        .rv-btn.is-primary .rv-btn-t { display: inline; }
    }
    /* A tooltip that also shows on a pointer hover or keyboard focus,
       quicker than the browser's own title. */
    .rv-btn[data-tip]::after { content: attr(data-tip); position: absolute; top: calc(100% + .45rem); right: 0; z-index: 3; padding: .3rem .55rem; border-radius: .5rem;
        background: var(--color-gray-900); color: #fff; font-size: .7rem; font-weight: 700; white-space: nowrap; pointer-events: none;
        opacity: 0; transform: translateY(-3px); transition: opacity .2s cubic-bezier(.22,1,.36,1), transform .2s cubic-bezier(.22,1,.36,1); }
    @media (hover: hover) { .rv-btn[data-tip]:hover::after { opacity: 1; transform: none; } }
    .rv-btn[data-tip]:focus-visible::after { opacity: 1; transform: none; }
    html.dark .rv-btn { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .rv-btn.is-primary { background: #1c2913; border-color: #6b9f3d; color: #cfe6b8; }
    html.dark .rv-btn.is-danger { color: #fca5a5; background: #2a1414; border-color: #5b2323; }
    html.dark .rv-btn[data-tip]::after { background: #e8efe1; color: #0d110a; }
    @media (prefers-reduced-motion: reduce) { .va-view, .rv-btn, .rv-btn[data-tip]::after { transition: none; } }
    @media print { .va-view-bar { display: none !important; } .va-view { position: static; overflow: visible; } }

    /* WHAT THIS REPORT IS -- the card at the top of every report's Generate
       tab, before the form that makes one: what it adds up, what it shows. */
    .rx-about { display: flex; gap: .85rem; align-items: flex-start; padding: 1rem 1.05rem; margin-bottom: 1rem; border-radius: 1.1rem; position: relative; overflow: hidden;
        background: linear-gradient(135deg, #f4f9ee 0%, #fdfaf0 100%); border: 1px solid #d9e8c8; }
    .rx-about::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: linear-gradient(180deg, #5c8f34, #b7862b); }
    .rx-about-e { flex: none; font-size: 1.5rem; line-height: 1.1; }
    .rx-about-e img { width: 1.9rem; height: 1.9rem; border-radius: 999px; object-fit: cover; }
    .rx-about-t { min-width: 0; flex: 1 1 auto; }
    .rx-about-t > b { display: block; font-family: var(--font-heading); font-size: 1rem; color: #2f5219; margin-bottom: .25rem; }
    .rx-about-t li b { font-weight: 800; color: #2f5219; }
    .rx-about-t p { font-size: .82rem; line-height: 1.55; color: #3f4a37; }
    .rx-about-t ul { margin: .45rem 0 0; padding: 0; list-style: none; display: grid; gap: .25rem; }
    .rx-about-t li { font-size: .8rem; line-height: 1.45; color: #3f4a37; padding-left: 1.1rem; position: relative; }
    .rx-about-t li::before { content: '✓'; position: absolute; left: 0; top: 0; color: #4a7c2a; font-weight: 800; }
    .rx-about-note { margin-top: .5rem; font-size: .74rem; color: #6b7a5e; }
    html.dark .rx-about { background: linear-gradient(135deg, #17200f 0%, #221d10 100%); border-color: #2f3f1f; }
    html.dark .rx-about-t > b, html.dark .rx-about-t li b { color: #cfe6b8; }
    html.dark .rx-about-t p, html.dark .rx-about-t li { color: #b7c2ad; }
    html.dark .rx-about-note { color: #93a684; }
    /* The same card, folded to its title: the head is a button, the body
       folds on grid rows (animated, never a snap) and the choice is kept
       per report in localStorage. Wired by the script below on load. */
    .rx-about.is-fold { display: block; padding: 0; }
    .rx-about-head { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left; padding: .8rem 1.05rem; cursor: pointer; }
    .rx-about-head .rx-about-e { font-size: 1.25rem; }
    .rx-about-title { flex: 1 1 auto; min-width: 0; font-family: var(--font-heading); font-size: .98rem; font-weight: 700; color: #2f5219; }
    .rx-about-hint { flex: none; font-size: .72rem; font-weight: 700; color: #4a5a3c; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rx-about.is-min .rx-about-hint { opacity: .8; }
    .rx-about-c { flex: none; width: 1rem; height: 1rem; color: #4a5a3c; opacity: .6; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rx-about.is-min .rx-about-c { transform: rotate(-90deg); }
    .rx-about-body { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .rx-about.is-min .rx-about-body { grid-template-rows: 0fr; opacity: 0; }
    .rx-about-in { min-height: 0; overflow: hidden; }
    .rx-about-in .rx-about-t { padding: 0 1.05rem .95rem 1.05rem; }
    .rx-about-in .rx-about-t > p:first-child { margin-top: 0; }
    html.dark .rx-about-title { color: #cfe6b8; }
    html.dark .rx-about-hint, html.dark .rx-about-c { color: #a8bd93; }
    @media (prefers-reduced-motion: reduce) { .rx-about-body, .rx-about-c, .rx-about-hint { transition: none; } }

    /* The shelf with nothing on it yet. */
    .rx-empty { text-align: center; padding: 2.4rem 1.5rem; }
    .rx-empty-e { display: inline-flex; width: 3.4rem; height: 3.4rem; border-radius: 1rem; background: var(--color-brand-50); color: var(--color-brand-700); align-items: center; justify-content: center; margin-bottom: .7rem; }
    .rx-empty-e svg { width: 1.6rem; height: 1.6rem; }
    .rx-empty-t { font-weight: 800; color: var(--color-gray-900); }
    .rx-empty-p { font-size: .84rem; color: var(--color-gray-500); max-width: 20rem; margin: .3rem auto 0; line-height: 1.5; }
    html.dark .rx-empty-e { background: #22301a; color: #a5c97e; }
    html.dark .rx-empty-t { color: #e8efe1; }

    /* Figures said as tags, not as a run-on line. */
    .rx-stats { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .rx-stat { display: inline-flex; align-items: baseline; gap: .3rem; padding: .28rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .rx-stat b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    html.dark .rx-stat { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .rx-stat b { color: #e8efe1; }
</style>

<div class="va-view" id="rvView" hidden role="dialog" aria-modal="true" aria-label="Report">
    <div class="va-view-bar">
        <b id="rvTitle">Report</b>
        <div class="rv-acts" id="rvActs" hidden></div>
        <button type="button" class="va-view-x" id="rvX" aria-label="Close" title="Close">✕</button>
    </div>
    <div class="va-view-body"><div id="rvBody"></div></div>
</div>

<script>
(() => {
    const view = document.getElementById('rvView');
    const body = document.getElementById('rvBody');
    const acts = document.getElementById('rvActs');
    const title = document.getElementById('rvTitle');
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    // Where the node came from, so it can be put back exactly there.
    let held = null;   // { node, parent, next, onClose }
    const ICONS = {
        anee: '',
        pen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>',
        plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
        dot: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="3"/></svg>',
    };
    // The X is the close: an old "Close" / "New report" action is not drawn.
    const isCloser = (a) => a && (a.icon === 'close' || a.icon === 'plus');
    function paintActs(list) {
        acts.innerHTML = '';
        const shown = (list || []).filter((a) => a && !isCloser(a));
        shown.forEach((a) => {
            const el = document.createElement(a.href ? 'a' : 'button');
            if (!a.href) el.type = 'button';
            el.className = 'rv-btn' + (a.kind === 'primary' ? ' is-primary' : (a.kind === 'danger' ? ' is-danger' : ''));
            if (a.href) el.href = a.href;
            el.setAttribute('aria-label', a.label || '');
            el.setAttribute('title', a.label || '');
            el.setAttribute('data-tip', a.label || '');
            el.innerHTML = (a.face ? `<img src="${esc(a.face)}" alt="">` : (ICONS[a.icon] || ICONS.dot)) + `<span class="rv-btn-t">${esc(a.label)}</span>`;
            if (a.onClick) el.addEventListener('click', (e) => { if (!a.href) e.preventDefault(); a.onClick(e); });
            acts.appendChild(el);
        });
        acts.hidden = !shown.length;
    }
    function open({ title: t, node, html, actions, onClose } = {}) {
        if (held) putBack();
        title.textContent = t || 'Report';
        body.innerHTML = '';
        if (node) {
            held = { node, parent: node.parentNode, next: node.nextSibling, onClose, wasHidden: node.hidden };
            node.hidden = false;
            body.appendChild(node);
        } else {
            held = { node: null, onClose };
            body.innerHTML = html || '';
        }
        paintActs(actions);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => view.classList.add('is-on')));
    }
    function putBack() {
        if (!held) return;
        const h = held;
        held = null;
        if (h.node && h.parent) {
            h.node.hidden = h.wasHidden;
            if (h.next && h.next.parentNode === h.parent) h.parent.insertBefore(h.node, h.next);
            else h.parent.appendChild(h.node);
        }
        body.innerHTML = '';
        if (typeof h.onClose === 'function') h.onClose();
    }
    function close() {
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        setTimeout(() => { view.hidden = true; putBack(); }, 300);
    }
    document.getElementById('rvX').addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !view.hidden) close(); });
    window.reportView = { open, close, setTitle: (t) => { title.textContent = t || 'Report'; }, isOpen: () => !view.hidden, actions: paintActs };

    // WHAT THIS REPORT IS, folded to its title on request. Every .rx-about
    // on the page becomes head + body; the fold is remembered per report.
    function foldAbouts() {
        document.querySelectorAll('.rx-about:not(.is-fold)').forEach((card, i) => {
            const t = card.querySelector('.rx-about-t');
            const e = card.querySelector('.rx-about-e');
            const b = t ? t.querySelector(':scope > b') : null;
            if (!t || !b) return;
            const key = 'anee-rx-about-min:' + location.pathname + ':' + i;
            let min = false;
            try { min = localStorage.getItem(key) === '1'; } catch (_) { /* opens full */ }
            const head = document.createElement('button');
            head.type = 'button'; head.className = 'rx-about-head';
            head.innerHTML = `${e ? e.outerHTML : ''}<span class="rx-about-title">${esc(b.textContent)}</span><span class="rx-about-hint">Tap to read</span><svg class="rx-about-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>`;
            b.remove();
            if (e) e.remove();
            const body = document.createElement('div'); body.className = 'rx-about-body';
            const inner = document.createElement('div'); inner.className = 'rx-about-in';
            inner.appendChild(t); body.appendChild(inner);
            card.innerHTML = '';
            card.appendChild(head); card.appendChild(body);
            card.classList.add('is-fold');
            const paint = () => { card.classList.toggle('is-min', min); head.setAttribute('aria-expanded', min ? 'false' : 'true'); };
            head.addEventListener('click', () => { min = !min; try { localStorage.setItem(key, min ? '1' : '0'); } catch (_) { /* not remembered */ } paint(); });
            paint();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', foldAbouts); else foldAbouts();
})();
</script>
@endonce
