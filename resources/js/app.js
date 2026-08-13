import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/* ------------------------------------------------------------------ */
/* Reveal-on-scroll (public marketing site)                             */
/* Elements tagged .reveal fade/rise in as they enter the viewport.     */
/* The hidden start-state is gated on <html class="js"> (set pre-paint  */
/* by an inline head script), so content is never stuck invisible if    */
/* JS fails, and reduced-motion users get everything shown at once.     */
/* ------------------------------------------------------------------ */
(function revealOnScroll() {
    const start = () => {
        window.__revealBooted = true; // tells the layout failsafe the observer is live
        const els = document.querySelectorAll('.reveal');
        if (!els.length) return;

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced || !('IntersectionObserver' in window)) {
            els.forEach((el) => el.classList.add('is-visible'));
            return;
        }

        const io = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
        );
        els.forEach((el) => io.observe(el));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

/* ------------------------------------------------------------------ */
/* Client-app motion helpers                                            */
/* - Auto-animate items inserted into [data-animate-list] containers    */
/*   (new lot/worker/material/activity, duplicate, etc.).               */
/* - window.animateIn(el) / window.animateOut(el, done) for manual use. */
/* Respects prefers-reduced-motion.                                     */
/* ------------------------------------------------------------------ */
(function appMotion() {
    const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Animate a single element in (used for AJAX-added cards/rows).
    window.animateIn = function animateIn(el) {
        if (!el || reduced()) return;
        el.classList.add('list-item-enter');
        el.addEventListener('animationend', () => el.classList.remove('list-item-enter'), { once: true });
    };

    // Animate an element out, then run done() (typically el.remove()).
    window.animateOut = function animateOut(el, done) {
        if (!el) return;
        if (reduced()) { done && done(); return; }
        el.classList.add('list-item-leave');
        let finished = false;
        const finish = () => {
            if (finished) return;
            finished = true;
            done && done();
        };
        el.addEventListener('animationend', finish, { once: true });
        setTimeout(finish, 400); // failsafe if animationend doesn't fire
    };

    const start = () => {
        if (reduced()) return;
        const lists = document.querySelectorAll('[data-animate-list]');
        if (!lists.length) return;

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    // Only animate genuinely NEW elements. Nodes that were merely
                    // moved (drag/arrow reorder) already carry data-animated, so
                    // they are skipped — no flicker during reordering.
                    if (node.nodeType === 1 && !node.dataset.animated) {
                        node.dataset.animated = '1';
                        window.animateIn(node);
                    }
                });
            }
        });

        lists.forEach((list) => {
            // Mark items already present at load so they aren't re-animated when moved.
            [...list.children].forEach((child) => { if (child.nodeType === 1) child.dataset.animated = '1'; });
            observer.observe(list, { childList: true });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

/* ------------------------------------------------------------------ */
/* Release the page-entrance class once it has played                   */
/*                                                                      */
/* .app-enter animates <main>'s opacity, and an element with a running  */
/* or filling animation on opacity is a stacking context. Every fixed   */
/* overlay rendered inside the page — the AI float, the team chat panel */
/* and its camera/call buttons, Quick Capture, the note editor, the     */
/* whiteboard — is therefore trapped inside <main> and painted beneath  */
/* the mobile tab bar (.tabbar, z-30), however high its own z-index is. */
/* Dropping the class after the fade removes the stacking context, so   */
/* those overlays stack against the viewport as their z-index intends.  */
/* Nothing moves: the animation has already finished at opacity 1.      */
/* ------------------------------------------------------------------ */
(function releasePageEntrance() {
    const start = () => {
        document.querySelectorAll('.app-enter').forEach((el) => {
            const release = () => el.classList.remove('app-enter');
            el.addEventListener('animationend', release, { once: true });
            // Reduced motion sets `animation: none`, so animationend never
            // fires — and a trapped overlay is worse than a missing fade.
            setTimeout(release, 800);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

/* ------------------------------------------------------------------ */
/* CSRF + API helper                                                    */
/* All schedule-manager endpoints reply {success, message, data}.       */
/* ------------------------------------------------------------------ */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

/**
 * api('/app/sm-lots-store?scheduleId=1', {method:'POST', body:{...}})
 * - body objects are JSON-encoded; FormData passes through untouched.
 * - resolves with the parsed envelope; rejects with Error(message) on
 *   {success:false}, HTTP errors and 422 validation (first error message).
 */
window.api = async function api(url, { method = 'GET', body = null, headers = {} } = {}) {
    const opts = {
        method,
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            ...headers,
        },
        credentials: 'same-origin',
    };

    if (body instanceof FormData) {
        opts.body = body;
    } else if (body !== null) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }

    const res = await fetch(url, opts);

    let json = null;
    try {
        json = await res.json();
    } catch {
        /* non-JSON response */
    }

    if (res.status === 419) {
        throw new Error('Your session expired. Please refresh the page.');
    }
    if (res.status === 403 && json?.locked) {
        window.location.href = '/account/subscription';
        throw new Error(json.message || 'Subscription required.');
    }
    if (!res.ok || (json && json.success === false)) {
        const msg =
            json?.message ||
            (json?.errors ? Object.values(json.errors).flat()[0] : null) ||
            `Request failed (${res.status})`;
        const err = new Error(msg);
        err.status = res.status;
        err.errors = json?.errors || null;
        // Some endpoints explain a rejection in `data` (e.g. why a plan is not
        // ready to publish), so callers can render something better than `msg`.
        err.data = json?.data || null;
        throw err;
    }

    return json ?? { success: true };
};

/* ------------------------------------------------------------------ */
/* Toasts                                                               */
/* ------------------------------------------------------------------ */

function toastStack() {
    let el = document.getElementById('toast-stack');
    if (!el) {
        el = document.createElement('div');
        el.id = 'toast-stack';
        document.body.appendChild(el);
    }
    return el;
}

window.toast = function toast(message, type = 'success', timeout = 3200) {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span class="grow">${escapeHtml(message)}</span>`;
    toastStack().appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-shown'));
    setTimeout(() => {
        el.classList.remove('is-shown');
        setTimeout(() => el.remove(), 250);
    }, timeout);
};

/* ------------------------------------------------------------------ */
/* Sheet (bottom-sheet on mobile / dialog on desktop)                   */
/* Markup: <div class="sheet hidden" id="mySheet">…</div>               */
/* ------------------------------------------------------------------ */

let backdropEl = null;
const openSheets = [];

/* ------------------------------------------------------------------ */
/* Back closes what is on top, instead of leaving the page.            */
/*                                                                     */
/* On a phone the system Back button is how people dismiss things. With*/
/* nothing listening it navigated away from the page while a sheet was */
/* still open over it — you pressed Back to close a form and landed    */
/* somewhere else entirely. Every overlay now owns one history entry:  */
/* opening pushes it, Back pops it and closes the overlay, and closing */
/* any other way removes it again so the entry never outlives what it  */
/* stands for.                                                         */
/* ------------------------------------------------------------------ */
const overlayStack = [];      // [{ key, close, id }] — newest last
let overlaySeq = 0;           // every entry gets its own id, never reused
let unwinding = false;        // we are removing our own entry
let poppingBack = false;      // Back is closing this one; do not touch history

window.registerOverlay = function registerOverlay(key, close) {
    if (overlayStack.some((o) => o.key === key)) return;
    const id = ++overlaySeq;
    overlayStack.push({ key, close, id });
    // Same URL: this entry exists to be popped, not to be linked to.
    history.pushState({ __overlay: id }, '', location.href);
};

/**
 * Drop an overlay's entry without touching history.
 *
 * For closing a sheet as part of navigating somewhere: rewinding here would
 * race the navigation's own pushState, and the popstate would arrive after it
 * — sending the reader back to where they just came from. The abandoned entry
 * is swallowed by the popstate handler the next time Back is pressed.
 */
window.forgetOverlay = function forgetOverlay(key) {
    const i = overlayStack.findIndex((o) => o.key === key);
    if (i >= 0) overlayStack.splice(i, 1);
};

window.unregisterOverlay = function unregisterOverlay(key) {
    const i = overlayStack.findIndex((o) => o.key === key);
    if (i < 0) return;
    const [entry] = overlayStack.splice(i, 1);
    if (poppingBack) return;
    // Rewind only when the entry on top is exactly this one. Overlays do not
    // always close in the order they opened — the drawing pad hands its
    // picture to a naming sheet and closes behind it — and counting entries
    // rather than identifying them meant closing the pad rewound the sheet's
    // entry instead. One rewind too many lands on the state under the module,
    // which is how saving a drawing dropped you back into Activities.
    if (!history.state || history.state.__overlay !== entry.id) return;
    unwinding = true;
    history.back();
};

window.addEventListener('popstate', (e) => {
    if (unwinding) { unwinding = false; return; }

    // An entry whose overlay closed out of order is left behind — nothing on
    // screen answers to it. Landing on one would spend a Back press doing
    // nothing visible, so it is swallowed and the press carries on to what the
    // user actually meant. Each swallow consumes one entry, so this ends.
    const id = e.state && e.state.__overlay;
    if (id && !overlayStack.some((o) => o.id === id)) {
        unwinding = true;
        history.back();
        return;
    }

    if (!overlayStack.length) return;
    const top = overlayStack[overlayStack.length - 1];
    poppingBack = true;
    try { top.close(); } finally { poppingBack = false; }
});

function ensureBackdrop() {
    if (!backdropEl) {
        backdropEl = document.createElement('div');
        backdropEl.className = 'sheet-backdrop hidden';
        backdropEl.addEventListener('click', () => {
            const top = openSheets[openSheets.length - 1];
            if (top && top.dataset.static !== 'true') window.closeSheet(top.id);
        });
        document.body.appendChild(backdropEl);
    }
    return backdropEl;
}

window.openSheet = function openSheet(id) {
    const el = document.getElementById(id);
    if (!el) return;
    document.body.appendChild(el); // escape any transformed ancestors
    el.classList.remove('hidden');
    ensureBackdrop().classList.remove('hidden');
    requestAnimationFrame(() => {
        ensureBackdrop().classList.add('is-open');
        el.classList.add('is-open');
    });
    openSheets.push(el);
    document.documentElement.style.overflow = 'hidden';
    // Floating widgets (the AI technician, the team chat) sit above sheets in
    // the stack, so their bubbles landed on top of a sheet's Cancel/Save row.
    // A sheet is modal: nothing else should be reachable while it is open.
    document.documentElement.classList.add('sheet-open');

    // On a phone, opening a sheet must not raise the keyboard. A field that
    // takes focus as the sheet mounts covers half of it before the user has
    // read what it says, and several sheets do that without ever calling
    // focus() — a date or number input can pick it up on its own. Blur once
    // the sheet has settled; tapping the field you want still works normally.
    // Guarded to the sheet's own contents so nothing else loses focus.
    if (window.matchMedia('(pointer: coarse)').matches) {
        setTimeout(() => {
            const active = document.activeElement;
            if (active && el.contains(active) && typeof active.blur === 'function') active.blur();
        }, 120);
    }
    window.registerOverlay('sheet:' + id, () => window.closeSheet(id));
    el.dispatchEvent(new CustomEvent('sheet:open'));
};

window.closeSheet = function closeSheet(id) {
    const el = document.getElementById(id);
    if (!el) return;
    window.unregisterOverlay('sheet:' + id);
    el.classList.remove('is-open');
    const idx = openSheets.indexOf(el);
    if (idx >= 0) openSheets.splice(idx, 1);
    if (openSheets.length === 0) {
        ensureBackdrop().classList.remove('is-open');
        document.documentElement.style.overflow = '';
        document.documentElement.classList.remove('sheet-open');
    }
    setTimeout(() => {
        el.classList.add('hidden');
        if (openSheets.length === 0) ensureBackdrop().classList.add('hidden');
    }, 250);
    el.dispatchEvent(new CustomEvent('sheet:close'));
};

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && openSheets.length) {
        const top = openSheets[openSheets.length - 1];
        if (top.dataset.static !== 'true') window.closeSheet(top.id);
    }
});

document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-sheet-open]');
    if (opener) {
        e.preventDefault();
        window.openSheet(opener.getAttribute('data-sheet-open'));
    }
    const closer = e.target.closest('[data-sheet-close]');
    if (closer) {
        e.preventDefault();
        window.closeSheet(closer.closest('.sheet')?.id || closer.getAttribute('data-sheet-close'));
    }
});

/* ------------------------------------------------------------------ */
/* Confirm dialog (promise-based)                                       */
/* ------------------------------------------------------------------ */

window.confirmAction = function confirmAction({
    title = 'Are you sure?',
    message = '',
    detail = '',
    confirmText = 'Confirm',
    confirmClass = 'btn-danger',
} = {}) {
    return new Promise((resolve) => {
        let el = document.getElementById('confirm-sheet');
        if (!el) {
            el = document.createElement('div');
            el.id = 'confirm-sheet';
            el.className = 'sheet hidden';
            el.style.setProperty('--sheet-width', '26rem');
            document.body.appendChild(el);
        }
        el.innerHTML = `
            <div class="sheet-handle"></div>
            <div class="sheet-body pt-5">
                <h3 class="text-lg font-bold text-gray-900 mb-1">${escapeHtml(title)}</h3>
                <p class="text-sm text-gray-600">${escapeHtml(message)}</p>
                ${detail ? `<p class="text-xs text-gray-400 mt-2">${escapeHtml(detail)}</p>` : ''}
            </div>
            <div class="sheet-footer">
                <button type="button" class="btn btn-ghost" data-confirm-no>Cancel</button>
                <button type="button" class="btn ${confirmClass}" data-confirm-yes>${escapeHtml(confirmText)}</button>
            </div>`;
        const done = (answer) => {
            window.closeSheet('confirm-sheet');
            resolve(answer);
        };
        el.querySelector('[data-confirm-no]').addEventListener('click', () => done(false));
        el.querySelector('[data-confirm-yes]').addEventListener('click', () => done(true));
        window.openSheet('confirm-sheet');
    });
};

/* ------------------------------------------------------------------ */
/* Small shared utilities                                               */
/* ------------------------------------------------------------------ */

/* ------------------------------------------------------------------ */
/* Rich-text toolbars on a touch screen                                  */
/* ------------------------------------------------------------------ */

/**
 * Make a Quill toolbar work under a finger.
 *
 * Two things went wrong on phones, and they looked like one bug —
 * "sometimes the buttons do nothing, sometimes they just bring up the
 * keyboard".
 *
 * 1. A tap on a toolbar button moves focus out of the editor before the
 *    click lands, so the format is applied to a selection that no longer
 *    exists. Preventing the default on touchstart/mousedown keeps the caret
 *    where it was; the click still fires.
 *
 * 2. Some editors deliberately open read-only, so opening a note does not
 *    throw the keyboard over it. Tapping a toolbar button on one of those
 *    formats nothing at all, because there is nothing editable to format —
 *    and the only way to discover that is to tap the text first. A toolbar
 *    tap now arms the editor exactly as a tap on the text does.
 *
 * Pickers (the header dropdown) are left alone: they need the browser's own
 * behaviour to open.
 */
/**
 * The toolbar every rich-text box in the app uses.
 *
 * There were seven of them, all slightly different: one had headings, one had
 * strikethrough, one had code blocks nobody asked for, and the note editors
 * had none of it. Formatting a note should not depend on which screen you
 * happened to open — these are the marks a farm record actually needs, and
 * they are the same everywhere.
 */
window.SM_RICH_TOOLBAR = [
    [{ header: [2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote'],
    ['link'],
    ['clean'],
];

/**
 * A small link dialog of our own.
 *
 * Quill's link button opens a tooltip positioned inside the editor's own
 * container. Inside a bottom sheet that container scrolls, so the tooltip
 * lands off-screen or under an edge and the button looks dead. This one is
 * fixed to the viewport, so there is nothing to clip it.
 */
window.smLinkPrompt = function smLinkPrompt(current) {
    return new Promise((resolve) => {
        const wrap = document.createElement('div');
        wrap.className = 'sm-linkbox';
        wrap.innerHTML = `
            <div class="sm-linkbox-card" role="dialog" aria-modal="true" aria-label="Link address">
                <label class="sm-linkbox-lbl" for="smLinkUrl">Link address</label>
                <input id="smLinkUrl" type="url" inputmode="url" autocomplete="off"
                       spellcheck="false" placeholder="https://example.com">
                <div class="sm-linkbox-foot">
                    ${current ? '<button type="button" data-lk="remove">Remove link</button>' : ''}
                    <button type="button" data-lk="cancel">Cancel</button>
                    <button type="button" data-lk="ok" class="is-go">Add link</button>
                </div>
            </div>`;
        document.body.appendChild(wrap);
        const input = wrap.querySelector('#smLinkUrl');
        input.value = current || '';
        const done = (val) => { wrap.remove(); resolve(val); };
        wrap.addEventListener('click', (e) => {
            if (e.target === wrap) return done(undefined);
            const b = e.target.closest('[data-lk]');
            if (!b) return;
            const act = b.getAttribute('data-lk');
            if (act === 'ok') return done(input.value.trim());
            if (act === 'remove') return done(null);
            done(undefined);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); done(input.value.trim()); }
            if (e.key === 'Escape') { e.preventDefault(); done(undefined); }
        });
        setTimeout(() => { input.focus(); input.select(); }, 30);
    });
};

/**
 * Make a Quill toolbar survive a touchscreen.
 *
 * The first attempt called preventDefault on touchstart to stop the editor
 * losing focus. On a phone that also cancels the click the browser would have
 * sent afterwards — so the format never applied and the only thing that
 * happened was the keyboard sliding up. Hence: hold focus for a mouse, where
 * cancelling mousedown is safe and click still fires, and for a touch let the
 * tap through untouched. Quill remembers the last selection and restores it
 * itself when a toolbar button asks to format, so nothing is lost by letting
 * the editor blur for the length of a tap.
 *
 * Two handlers are replaced outright: link, which needs a dialog that a
 * scrolling sheet cannot clip, and clean, which did nothing visible when
 * nothing was selected.
 */
window.smQuillTouch = function smQuillTouch(quill) {
    if (!quill) return quill;
    const toolbar = quill.getModule && quill.getModule('toolbar');
    const bar = toolbar && toolbar.container;
    if (!bar || bar.dataset.smTouchFixed === '1') return quill;
    bar.dataset.smTouchFixed = '1';

    const root = quill.root;
    let lastTouch = 0;

    // Read-only until first touch (see the callers): a toolbar tap is just as
    // clear an intention to write as a tap on the text itself.
    const arm = () => {
        if (root.getAttribute('contenteditable') === 'false') {
            root.setAttribute('contenteditable', 'true');
        }
    };

    bar.addEventListener('touchstart', () => { lastTouch = Date.now(); arm(); }, { passive: true });

    bar.addEventListener('mousedown', (e) => {
        // The compatibility mousedown a tap fires afterwards must be left
        // alone; cancelling it takes the click with it on some browsers.
        if (Date.now() - lastTouch < 800) return;
        const hit = e.target.closest('button, .ql-picker-label');
        if (!hit) return;
        arm();
        // A picker label keeps its default so its menu opens; a button only
        // needs its click, not the focus change that comes with it.
        if (hit.classList.contains('ql-picker-label')) return;
        e.preventDefault();
    });

    const savedRange = () => {
        const r = quill.selection && quill.selection.savedRange;
        return r && r.index !== undefined ? r : null;
    };

    /**
     * Put the toolbar's on/off marks back after the editor loses its caret.
     *
     * Quill decides what a button does from its own ql-active class: marked
     * active it removes the format, unmarked it applies it. Those marks are
     * derived from the current selection, and a tap on a phone takes the
     * selection away before the click lands — so every button came back
     * unmarked and bold could be switched on all day and never off. The
     * selection is still remembered; the marks are simply re-derived from it.
     */
    const remark = () => {
        const saved = savedRange();
        if (saved && typeof toolbar.update === 'function') toolbar.update(saved);
    };

    quill.on('selection-change', (range) => {
        // Only on the way out. Losing the caret should not lose the state of
        // the buttons — visually either, or the toolbar flickers off at the
        // exact moment you reach for it.
        if (range) return;
        setTimeout(() => { if (!quill.hasFocus()) remark(); }, 0);
    });

    // Whatever the input device, a toolbar press should act on the words the
    // caret was last in — even if the editor has never been focused at all.
    // Capture phase: this has to be settled before Quill's own handler reads
    // the marks.
    bar.addEventListener('click', (e) => {
        if (!e.target.closest('button, .ql-picker-label, .ql-picker-item')) return;
        arm();
        if (!savedRange()) {
            quill.setSelection(Math.max(0, quill.getLength() - 1), 0, 'silent');
        }
        if (!quill.hasFocus()) remark();
    }, true);

    if (toolbar && typeof toolbar.addHandler === 'function') {
        toolbar.addHandler('link', async () => {
            const range = quill.getSelection(true) || { index: quill.getLength() - 1, length: 0 };
            const current = quill.getFormat(range).link || '';
            const answer = await window.smLinkPrompt(current);
            if (answer === undefined) return;              // cancelled
            if (answer === null || answer === '') {        // remove
                quill.format('link', false, 'user');
                return;
            }
            // A bare domain is still a link; the browser needs the scheme.
            const url = /^(https?:|mailto:|tel:)/i.test(answer) ? answer : 'https://' + answer;
            if (range.length === 0) {
                // Nothing selected: write the address and link that, rather
                // than silently doing nothing.
                quill.insertText(range.index, answer, { link: url }, 'user');
                quill.setSelection(range.index + answer.length, 0, 'silent');
            } else {
                quill.formatText(range.index, range.length, 'link', url, 'user');
            }
        });

        toolbar.addHandler('clean', () => {
            const range = quill.getSelection(true);
            if (!range) return;
            if (range.length > 0) {
                quill.removeFormat(range.index, range.length, 'user');
                return;
            }
            // Nothing selected — clear the line the caret is in. Clearing
            // "from here on", which is what Quill does by default, looks
            // exactly like a button that does not work.
            const [line, offset] = quill.getLine(range.index);
            if (!line) return;
            const start = range.index - offset;
            quill.removeFormat(start, line.length(), 'user');
            quill.setSelection(range.index, 0, 'silent');
        });
    }

    return quill;
};

window.escapeHtml = function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};

window.fmtPeso = function fmtPeso(value) {
    return '₱ ' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

window.fmtNumber = function fmtNumber(value, decimals = 0) {
    return Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
};

/* Toggleable chip groups: <div data-chip-group><button class="chip" data-value="1">…</button></div> */
document.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-chip-group] .chip');
    if (!chip || chip.hasAttribute('data-chip-manual')) return;
    e.preventDefault();
    const group = chip.closest('[data-chip-group]');
    if (group.dataset.single === 'true') {
        group.querySelectorAll('.chip').forEach((c) => c.classList.remove('is-selected'));
        chip.classList.add('is-selected');
    } else {
        chip.classList.toggle('is-selected');
    }
    group.dispatchEvent(new CustomEvent('chips:change', { bubbles: true }));
});

window.chipValues = function chipValues(groupEl) {
    return [...groupEl.querySelectorAll('.chip.is-selected')]
        .map((c) => c.getAttribute('data-value'))
        .filter((v) => v !== null);
};

/* Blocking full-screen loader for heavy transitions (version switches, big
   duplications) where the page will reload or redirect: shows a translucent
   overlay + spinner so the app never looks frozen. Returns { hide } for
   failure paths; a successful navigation replaces the DOM and clears it. */
window.screenLoader = function screenLoader(label = 'Working…') {
    let el = document.getElementById('screenLoaderOverlay');
    if (!el) {
        el = document.createElement('div');
        el.id = 'screenLoaderOverlay';
        el.className = 'screen-loader';
        el.innerHTML = '<span class="spin" aria-hidden="true"></span><p data-loader-label></p>';
        document.body.appendChild(el);
    }
    el.querySelector('[data-loader-label]').textContent = label;
    el.classList.remove('hidden');
    return { hide: () => el.classList.add('hidden') };
};

/* Animate a button (or toolbar item) that would otherwise snap in/out via a
   display:none class: squeeze the width + fade with the house easing, then
   apply the class so the steady state stays plain CSS. The margin compensates
   the parent's flex gap so neighbours slide instead of jumping. */
window.animToggleHidden = function animToggleHidden(el, hide, cls = 'hidden') {
    if (!el) return;
    if (!!hide === el.classList.contains(cls) && !el.__animHideTimer) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        el.classList.toggle(cls, !!hide);
        return;
    }
    if (el.__animHideTimer) { clearTimeout(el.__animHideTimer); el.__animHideTimer = null; }

    const EASE = '.28s cubic-bezier(.22,1,.36,1)';
    const gap = parseFloat(getComputedStyle(el.parentElement).columnGap) || 0;
    const props = ['width', 'minWidth', 'paddingLeft', 'paddingRight', 'marginLeft',
        'opacity', 'transform', 'overflow', 'whiteSpace', 'pointerEvents', 'transition'];
    const clear = () => props.forEach((p) => { el.style[p] = ''; });
    const prep = () => {
        el.style.overflow = 'hidden';
        el.style.whiteSpace = 'nowrap';
        el.style.minWidth = '0';
    };
    const squeeze = () => {
        el.style.width = '0px';
        el.style.paddingLeft = '0px';
        el.style.paddingRight = '0px';
        if (gap) el.style.marginLeft = -gap + 'px';
        el.style.opacity = '0';
        el.style.transform = 'scale(.9)';
    };
    const run = `width ${EASE}, padding ${EASE}, margin ${EASE}, opacity ${EASE}, transform ${EASE}`;

    if (hide) {
        el.style.width = el.getBoundingClientRect().width + 'px';
        prep();
        el.style.pointerEvents = 'none';
        void el.offsetWidth;
        el.style.transition = run;
        squeeze();
        el.__animHideTimer = setTimeout(() => {
            el.__animHideTimer = null;
            el.classList.add(cls);
            clear();
        }, 300);
    } else {
        el.classList.remove(cls);
        const w = el.getBoundingClientRect().width;
        prep();
        squeeze();
        void el.offsetWidth;
        el.style.transition = run;
        el.style.width = w + 'px';
        el.style.paddingLeft = '';
        el.style.paddingRight = '';
        el.style.marginLeft = '';
        el.style.opacity = '1';
        el.style.transform = 'none';
        el.__animHideTimer = setTimeout(() => {
            el.__animHideTimer = null;
            clear();
        }, 300);
    }
};

/* Chip strips (.scroll-chips) pan natively under a finger, but a mouse has no
   native drag-to-scroll — grab-and-slide for desktop, delegated so strips
   injected later (SPA modules) work too. A real drag swallows the click that
   follows it, so sliding never activates a chip. */
document.addEventListener('pointerdown', (e) => {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    const strip = e.target.closest('.scroll-chips');
    if (!strip || strip.scrollWidth <= strip.clientWidth) return;
    const startX = e.clientX;
    const startLeft = strip.scrollLeft;
    let dragged = false;
    const move = (ev) => {
        const dx = ev.clientX - startX;
        if (!dragged && Math.abs(dx) > 4) dragged = true;
        if (dragged) strip.scrollLeft = startLeft - dx;
    };
    const up = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', up);
        if (dragged) {
            strip.addEventListener('click', (ce) => {
                ce.preventDefault();
                ce.stopPropagation();
            }, { capture: true, once: true });
            // The once-listener lingers if no click follows; harmless, it
            // clears on the next real click, which a fresh drag re-adds.
        }
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
});
