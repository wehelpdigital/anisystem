{{-- THE REPORT, HELD UP — the full-screen view every report in the
     Reports module opens into (2026-09-21, the owner's ask: "like the What
     to Plant analysis"). A fresh report lands in it the moment it is
     generated and saved; a row on the Saved shelf opens into the same
     screen. Under the report, one row of small actions: ask Anee about it,
     rename or describe it, delete it, and close.

     One partial, one API:

         window.reportView.open({ title, node, actions, onClose })
             node    -> a DOM element the page already rendered; it is moved
                        into the view and handed back where it was on close
             actions -> [{ label, icon?, kind: 'primary'|'ghost'|'danger', href?, onClick? }]
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
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(5.5rem + env(safe-area-inset-bottom)); }
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

    /* The actions: one row of small pills pinned to the bottom, not a
       second set of tabs. Ask Anee is the one green pill; the rest are
       quiet; delete is red only in its word. */
    .rv-acts { position: sticky; bottom: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: .4rem; justify-content: center; padding: .6rem .8rem;
        padding-bottom: max(.6rem, env(safe-area-inset-bottom)); background: rgb(250 250 248 / .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-top: 1px solid var(--color-gray-200); }
    .rv-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .42rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700); cursor: pointer; text-decoration: none; white-space: nowrap;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .2s, border-color .2s; }
    .rv-btn:hover { transform: translateY(-1px); border-color: var(--color-brand-300); }
    .rv-btn img { width: 1rem; height: 1rem; border-radius: 999px; object-fit: cover; }
    .rv-btn svg { width: .95rem; height: .95rem; }
    .rv-btn.is-primary { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .rv-btn.is-primary:hover { background: var(--color-brand-700); }
    .rv-btn.is-danger { color: #b91c1c; }
    html.dark .rv-acts { background: rgb(13 17 10 / .94); border-color: #2b3a1c; }
    html.dark .rv-btn { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .rv-btn.is-primary { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .rv-btn.is-danger { color: #fca5a5; }
    @media (prefers-reduced-motion: reduce) { .va-view, .rv-btn { transition: none; } }
    @media print { .va-view-bar, .rv-acts { display: none !important; } .va-view { position: static; overflow: visible; } }

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
        <button type="button" class="va-view-x" id="rvX" aria-label="Close">✕</button>
    </div>
    <div class="va-view-body"><div id="rvBody"></div></div>
    <div class="rv-acts" id="rvActs"></div>
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
    };
    function paintActs(list) {
        acts.innerHTML = '';
        (list || []).forEach((a) => {
            const el = document.createElement(a.href ? 'a' : 'button');
            if (!a.href) el.type = 'button';
            el.className = 'rv-btn' + (a.kind === 'primary' ? ' is-primary' : (a.kind === 'danger' ? ' is-danger' : ''));
            if (a.href) el.href = a.href;
            el.innerHTML = (a.face ? `<img src="${esc(a.face)}" alt="">` : (ICONS[a.icon] || '')) + `<span>${esc(a.label)}</span>`;
            if (a.onClick) el.addEventListener('click', (e) => { if (!a.href) e.preventDefault(); a.onClick(e); });
            acts.appendChild(el);
        });
        acts.hidden = !(list || []).length;
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
