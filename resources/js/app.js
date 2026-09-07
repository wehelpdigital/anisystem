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

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || document.documentElement.classList.contains('sm-still');
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
/* A picture that will not load                                         */
/*                                                                      */
/* Two of these travel between apps: the assistant's avatar, set in the */
/* mother app, and a member's photo, which may live on the other app's  */
/* disk. Either can outlive its file. The browser's answer is a broken- */
/* image glyph, which reads as a broken SCREEN; this puts back what the */
/* markup would have drawn if there had never been a picture at all.    */
/*                                                                      */
/* Capture phase: an image's error event does not bubble.               */
/* ------------------------------------------------------------------ */
const AI_FACE_SVG =
    '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">' +
    '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

document.addEventListener('error', (e) => {
    const img = e.target;
    if (!img || img.tagName !== 'IMG') return;

    if (img.hasAttribute('data-ai-face')) {
        const holder = img.parentElement;
        img.remove();
        // The glyph goes where the picture was, at the size that slot draws.
        if (holder && !holder.querySelector('svg')) holder.insertAdjacentHTML('beforeend', AI_FACE_SVG);
        return;
    }

    if (img.hasAttribute('data-avatar-fallback')) {
        const holder = img.parentElement;
        const initials = img.getAttribute('data-initials') || '?';
        img.remove();
        if (holder && !holder.textContent.trim()) holder.textContent = initials;
    }
}, true);

/* ------------------------------------------------------------------ */
/* Client-app motion helpers                                            */
/* - Auto-animate items inserted into [data-animate-list] containers    */
/*   (new lot/worker/material/activity, duplicate, etc.).               */
/* - window.animateIn(el) / window.animateOut(el, done) for manual use. */
/* Respects prefers-reduced-motion.                                     */
/* ------------------------------------------------------------------ */
(function appMotion() {
    // The device's own setting, or the one asked for in Settings — a phone
    // that offers no such switch is exactly where the app's own matters.
    const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || document.documentElement.classList.contains('sm-still');

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

/**
 * A short message in the corner.
 *
 * timeout: 0 keeps it up until the caller says otherwise, for work whose
 * length nobody can predict — compressing a phone video takes as long as it
 * takes, and a notice that vanishes after three seconds reads as "finished"
 * when it means nothing of the sort. Every call returns a handle, so the
 * waiting case is `const t = toast(msg, 'info', 0); ...; t.close()`.
 */
window.toast = function toast(message, type = 'success', timeout = 3200) {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span class="grow">${escapeHtml(message)}</span>`;
    toastStack().appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-shown'));

    let timer = null;
    const close = () => {
        if (timer) { clearTimeout(timer); timer = null; }
        el.classList.remove('is-shown');
        setTimeout(() => el.remove(), 250);
    };
    if (timeout > 0) timer = setTimeout(close, timeout);

    return { close, el, say: (m) => { el.querySelector('.grow').textContent = m; } };
};

/**
 * A blocking wait, for work whose length nobody can predict.
 *
 * A toast is the wrong shape for compressing a phone video: it sits in a
 * corner, it can be missed, and it says nothing about whether the page is
 * still yours to touch. This dims the page, names what is happening, and
 * turns itself off — smBusy('Saving…') returns a handle with say() and
 * close().
 */
window.smBusy = function smBusy(text) {
    let el = document.getElementById('sm-busy');
    if (!el) {
        el = document.createElement('div');
        el.id = 'sm-busy';
        el.className = 'sm-busy';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.innerHTML = '<div class="sm-busy-card"><span class="sm-busy-spin"></span>'
            + '<span class="sm-busy-text"></span></div>';
        document.body.appendChild(el);
    }
    const label = el.querySelector('.sm-busy-text');
    label.textContent = text || 'Working…';
    el.classList.remove('hidden');
    requestAnimationFrame(() => el.classList.add('is-on'));
    document.body.style.overflow = 'hidden';

    return {
        say: (t) => { label.textContent = t; },
        close: () => {
            el.classList.remove('is-on');
            document.body.style.overflow = '';
            setTimeout(() => el.classList.add('hidden'), 260);
        },
    };
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

/* A second sheet over a first was white on white: the gallery picker opened
 * over the composer and the two read as one card. Whenever the stack is two
 * deep, a second dimmer rises just under the topmost sheet — same fade as
 * the base backdrop — and the top sheet wears its own tint (.sheet-above),
 * so what can be touched is unmistakably in front of what cannot. */
let stackBackdropEl = null;
function ensureStackBackdrop() {
    if (!stackBackdropEl) {
        stackBackdropEl = document.createElement('div');
        stackBackdropEl.className = 'sheet-backdrop sheet-backdrop-stack hidden';
        stackBackdropEl.addEventListener('click', () => {
            const top = openSheets[openSheets.length - 1];
            if (top && top.dataset.static !== 'true') window.closeSheet(top.id);
        });
        document.body.appendChild(stackBackdropEl);
    }
    return stackBackdropEl;
}

function layerSheets() {
    const bd = ensureStackBackdrop();
    openSheets.forEach((s, i) => {
        s.classList.toggle('sheet-above', openSheets.length >= 2 && i === openSheets.length - 1);
    });
    if (openSheets.length >= 2) {
        const top = openSheets[openSheets.length - 1];
        // The top sheet's own z-index, seated just before it in the DOM:
        // above every earlier sheet whatever ad-hoc z each one carries
        // (plain sheets are 50, the picker 150, the confirm 200), and under
        // the top one by document order alone.
        const z = getComputedStyle(top).zIndex;
        bd.style.zIndex = z === 'auto' ? '50' : z;
        top.parentNode.insertBefore(bd, top);
        bd.classList.remove('hidden');
        requestAnimationFrame(() => bd.classList.add('is-open'));
    } else {
        bd.classList.remove('is-open');
        setTimeout(() => { if (openSheets.length < 2) bd.classList.add('hidden'); }, 250);
    }
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
    layerSheets();
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
    // Something may have opened this on someone else's behalf and owe them a
    // way back — a saved map a note asked to see, for instance.
    document.dispatchEvent(new CustomEvent('sm:sheet-closed', { detail: { id } }));
    window.unregisterOverlay('sheet:' + id);
    el.classList.remove('is-open');
    el.classList.remove('sheet-above');
    const idx = openSheets.indexOf(el);
    if (idx >= 0) openSheets.splice(idx, 1);
    layerSheets();
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

/* ======================================================================
 * The rich-text engine, swapped under the old name.
 *
 * Quill fought every touchscreen it met — stolen focus, toolbar marks lost
 * on blur, doubled characters under Android keyboards — and the pile of
 * workarounds below (smQuillTouch) never fully won. The editor is now
 * SunEditor (MIT, no paid tier), whose editing surface is the browser's own
 * contenteditable, so the keyboard talks to the browser and not to a
 * document model.
 *
 * It wears Quill's name and the slice of Quill's API this app actually
 * uses, so every screen that says `new Quill(...)` gets the new engine
 * without knowing. The lazy CDN loaders all begin with `typeof Quill !==
 * 'undefined'`, which this global satisfies — so real Quill never loads.
 * ==================================================================== */
const SUN_CSS = 'https://cdn.jsdelivr.net/npm/suneditor@2/dist/css/suneditor.min.css';
const SUN_JS = 'https://cdn.jsdelivr.net/npm/suneditor@2/dist/suneditor.min.js';
let sunEditorReady = null;
function loadSunEditor() {
    if (sunEditorReady) return sunEditorReady;
    sunEditorReady = new Promise((resolve, reject) => {
        if (window.SUNEDITOR) return resolve();
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = SUN_CSS;
        document.head.appendChild(css);
        const s = document.createElement('script');
        s.src = SUN_JS;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Could not load the editor.'));
        document.head.appendChild(s);
    });
    return sunEditorReady;
}

window.Quill = class SmRichEditor {
    constructor(target, opts = {}) {
        this._host = typeof target === 'string' ? document.querySelector(target) : target;
        this._editor = null;
        this._live = null;            // the engine's editable div, once built
        this._placeholder = opts.placeholder || '';
        // A staging surface so reads and writes work from the first tick,
        // before the engine's script has arrived from the CDN. It starts
        // with whatever the mount already held — Quill adopted pre-seeded
        // markup, and screens that rely on that must not lose it.
        this._stage = document.createElement('div');
        if (this._host) this._stage.innerHTML = this._host.innerHTML;
        this._wantFocus = false;
        loadSunEditor().then(() => this._build()).catch(() => this._fallback());
    }

    _build() {
        if (!this._host) return this._fallback();
        const ta = document.createElement('textarea');
        this._host.innerHTML = '';
        this._host.appendChild(ta);
        this._editor = window.SUNEDITOR.create(ta, {
            // The same marks SM_RICH_TOOLBAR offered, in the new engine's words.
            buttonList: [
                ['formatBlock'],
                ['bold', 'italic', 'underline', 'strike'],
                ['list'],
                ['blockquote'],
                ['link'],
                ['removeFormat'],
            ],
            formats: ['p', 'h2', 'h3'],
            placeholder: this._placeholder,
            height: 'auto',
            minHeight: '130px',
            defaultStyle: 'font-family: inherit; font-size: .95rem;',
            resizingBar: false,
            showPathLabel: false,
        });
        this._live = this._editor.core.context.element.wysiwyg;
        if (this._stage.innerHTML.trim() !== '') this._editor.setContents(this._stage.innerHTML);
        if (this._wantFocus) { try { this._editor.core.focus(); } catch (_) { /* fine */ } }
    }

    /* The CDN failed: a plain contenteditable box, so words still work. */
    _fallback() {
        if (!this._host) return;
        this._host.innerHTML = '';
        this._stage.setAttribute('contenteditable', 'true');
        this._stage.className = 'sm-editor-fallback rich-text';
        this._host.appendChild(this._stage);
        this._live = this._stage;
    }

    /* Quill's `root` — the editable element. DOM-truthful in the new engine
       too: SunEditor reads its contents from this node, so callers that get
       or set root.innerHTML keep working. */
    get root() { return this._live || this._stage; }

    /* ---- the slice of Quill's API the app calls ---- */
    getModule() { return null; }                        // disables smQuillTouch's Quill surgery
    on() { /* the engine handles its own events */ }
    hasFocus() { return this.root.contains(document.activeElement) || document.activeElement === this.root; }
    /* The one method the shim was missing.
     *
     * Four composers ask "did they write anything?" with getText().trim(),
     * and every one of them threw — inside an async handler, so the failure
     * was silent and the button simply did nothing. Quill returns plain text
     * with a trailing newline; the trailing newline is why getLength adds
     * one, and why every caller trims. */
    getText() { return (this.root.textContent || '') + '\n'; }
    getLength() { return (this.root.textContent || '').length + 1; }
    getSelection() { return { index: 0, length: 0 }; }  // insertText works at the caret instead
    setSelection() { /* caret already where the user left it */ }
    setText(text) { this.root.innerHTML = text ? '<p>' + window.escapeHtml(text) + '</p>' : ''; }
    setContents() { if (this._editor) this._editor.setContents(''); else this.root.innerHTML = ''; }
    focus() {
        if (this._editor) { try { this._editor.core.focus(); } catch (_) { /* fine */ } }
        else { this._wantFocus = true; try { this.root.focus(); } catch (_) { /* fine */ } }
    }
    blur() {
        if (this._editor) { try { this._editor.core.blur(); } catch (_) { /* fine */ } }
        try { this.root.blur(); } catch (_) { /* fine */ }
    }
    /* Emoji and small strings land at the caret; the index the old callers
       computed is ignored because the caret is already the truth. */
    insertText(_index, text) {
        if (this._editor) {
            try { this._editor.core.focus(); } catch (_) { /* fine */ }
            this._editor.insertHTML(window.escapeHtml(text), true);
            return;
        }
        this.root.innerHTML += window.escapeHtml(text);
    }
    get clipboard() {
        const self = this;
        return {
            dangerouslyPasteHTML(html) {
                if (self._editor) self._editor.setContents(html || '');
                else self.root.innerHTML = html || '';
            },
        };
    }
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

/* ======================================================================
 * Long lists, read the way each screen is read.
 *
 * A desktop gets pages — you can see how much there is and jump. A phone
 * gets more of the list as it scrolls, because a row of numbered links
 * under a thumb is a poor target and "page 3 of 9" is not how anyone
 * reads a notebook on a phone.
 *
 * Any listing opts in by rendering partials/list-pager.blade.php after a
 * container: the pager carries the URL that returns cards alone, and this
 * appends them into whatever element sits above it.
 * ==================================================================== */
(function infiniteLists() {
    const PHONE = () => window.matchMedia('(max-width: 767px)').matches;

    function wire(pager) {
        if (pager.dataset.lpWired === '1') return;
        pager.dataset.lpWired = '1';

        const list = pager.previousElementSibling;
        const more = pager.querySelector('[data-lp-more]');
        const msg = pager.querySelector('[data-lp-msg]');
        const manual = pager.querySelector('[data-lp-manual]');
        if (!list) return;

        let next = parseInt(pager.getAttribute('data-next'), 10) || 2;
        const last = parseInt(pager.getAttribute('data-last'), 10) || 1;
        const base = pager.getAttribute('data-rows-url') || '';
        let busy = false;
        let failed = false;

        const done = () => {
            more.hidden = true;
            manual.classList.add('hidden');
            pager.classList.add('is-done');
        };

        async function load() {
            if (busy || next > last) return;
            busy = true;
            more.hidden = false;
            manual.classList.add('hidden');
            msg.textContent = 'Loading more…';
            pager.classList.remove('is-error');
            try {
                const url = base + (base.includes('?') ? '&' : '?') + 'page=' + next;
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Could not load more.');
                const html = await res.text();
                const box = document.createElement('div');
                box.innerHTML = html;
                // Whatever came back joins the list it belongs to.
                while (box.firstElementChild) list.appendChild(box.firstElementChild);
                next += 1;
                failed = false;
                // The page these rows joined may filter or fold them; it is
                // told, so a search or a filter covers the new arrivals too.
                list.dispatchEvent(new CustomEvent('lists:appended', { bubbles: true }));
                if (next > last) done();
                else more.hidden = true;
            } catch (_) {
                // A failed reach is not the end of the list — offer the
                // retry rather than silently stopping.
                failed = true;
                pager.classList.add('is-error');
                msg.textContent = 'Could not load more.';
                manual.classList.remove('hidden');
                manual.textContent = 'Try again';
            } finally {
                busy = false;
            }
            // A page whose rows all land hidden — arrived under a filter —
            // grows the list by nothing, so the sentinel never moves and no
            // second callback ever comes. Keep going while it is still in
            // view, or the shelf stops with most of itself unread.
            if (!failed && next <= last && PHONE() && stillWanted()) {
                requestAnimationFrame(load);
            }
        }

        const stillWanted = () => {
            const r = pager.getBoundingClientRect();
            return r.top < window.innerHeight + 600;
        };

        manual.addEventListener('click', load);

        // Only a phone loads by itself; a desktop keeps its page links.
        const watcher = new IntersectionObserver((entries) => {
            if (!PHONE() || failed) return;
            if (entries.some((en) => en.isIntersecting)) load();
        }, { rootMargin: '600px 0px' });
        watcher.observe(pager);

        const modeSwitch = () => {
            const phone = PHONE();
            pager.classList.toggle('is-phone', phone);
            if (!phone) { more.hidden = true; manual.classList.add('hidden'); }
            else if (next <= last && !failed) more.hidden = false;
        };
        modeSwitch();
        window.addEventListener('resize', modeSwitch);
    }

    /* A page link swaps the list in place instead of navigating.
     *
     * Inside the schedule shell these links point at a module's own URL, and
     * the shell answers any such link by tearing the module down and
     * re-fetching it — losing the search box, the folds and the scroll, and
     * leaving a history entry per page. Fetching the page ourselves keeps all
     * of that, and behaves the same on a standalone page.
     *
     * Capture phase: this has to be settled before the shell's own handler
     * further down the tree ever sees the click.
     */
    document.addEventListener('click', async (e) => {
        const link = e.target.closest && e.target.closest('.lp-links a.pg-btn[href]');
        if (!link) return;
        const pager = link.closest('[data-infinite-list]');
        const list = pager && pager.previousElementSibling;
        if (!pager || !list) return;
        e.preventDefault();
        e.stopPropagation();
        pager.classList.add('is-busy');
        try {
            const res = await fetch(link.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Could not open that page.');
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            // The same list and the same pager, one page further on.
            const freshList = list.id ? doc.getElementById(list.id) : null;
            const freshPager = doc.querySelector('[data-infinite-list]');
            if (!freshList) throw new Error('Could not open that page.');
            list.innerHTML = freshList.innerHTML;
            if (freshPager) pager.replaceWith(freshPager);
            list.dispatchEvent(new CustomEvent('lists:appended', { bubbles: true }));
            scan();
            list.scrollIntoView({ block: 'start', behavior: 'smooth' });
        } catch (err) {
            window.toast?.(err.message || 'Could not open that page.', 'error');
        } finally {
            pager.classList.remove('is-busy');
        }
    }, true);

    const scan = () => document.querySelectorAll('[data-infinite-list]').forEach(wire);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan);
    else scan();
    // Modules are injected into the schedule shell after load.
    document.addEventListener('sm:module-shown', scan);
    window.smScanLists = scan;
})();

/* ======================================================================
 * Speak instead of type — REMOVED (2026-09-08, by request).
 *
 * The floating dictation mic that followed every focused field is gone:
 * Web Speech transcription was not accurate enough in the field to be
 * worth the button. Voice stays where it works — recorded voice NOTES
 * (Quick Voice, the day menu, activity and chat attachments), which keep
 * the farmer's own words as sound instead of a bad transcript.
 * ==================================================================== */

/* ======================================================================
 * Living on the device.
 *
 * Installed, the app runs without the browser's chrome and can raise a
 * notification the way anything else on the phone does — which for a farm
 * is the difference between "someone messaged the team" and "someone
 * messaged the team four hours ago".
 *
 * The install button is offered only where the device will actually take
 * it, and disappears the moment it has been taken.
 * ==================================================================== */
(function installable() {
    const installed = () => window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    window.smInstalled = installed;

    // The worker is what makes both install and background notifications
    // possible. It caches nothing but the offline notice — see public/sw.js.
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => { /* not fatal */ });
        });
    }

    let prompt = null;
    const btn = () => document.getElementById('pwaInstallBtn');
    const show = (on) => { const b = btn(); if (b) b.hidden = !on; };

    window.addEventListener('beforeinstallprompt', (e) => {
        // Chrome's own mini-bar is refused so the offer lives in one place:
        // the account menu, where the rest of "this device" settings are.
        e.preventDefault();
        prompt = e;
        if (!installed()) show(true);
    });

    document.addEventListener('click', async (e) => {
        if (!e.target.closest('#pwaInstallBtn')) return;
        if (!prompt) {
            // iOS has no prompt to fire — it installs from the share sheet,
            // so say how rather than doing nothing.
            window.toast?.('On iPhone: tap Share, then "Add to Home Screen".');
            return;
        }
        prompt.prompt();
        const { outcome } = await prompt.userChoice;
        prompt = null;
        if (outcome === 'accepted') show(false);
    });

    window.addEventListener('appinstalled', () => { prompt = null; show(false); });

    // iOS gets the hint row too, but only where it could actually be used.
    document.addEventListener('DOMContentLoaded', () => {
        const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
        if (iOS && !installed()) show(true);
    });
})();

/* ======================================================================
 * Notifications that reach the phone.
 *
 * Realtime events land in the page; this is what turns one into something
 * you can see when the app is not the thing you are looking at. Asked for
 * once, gently, and only after the person has actually used the app.
 * ==================================================================== */
(function deviceNotices() {
    const canNotify = () => 'Notification' in window && window.isSecureContext;

    /** Ask once, and never again in this browser if the answer was no. */
    window.smAskToNotify = async function smAskToNotify() {
        if (!canNotify() || Notification.permission !== 'default') return Notification?.permission === 'granted';
        try {
            const answer = await Notification.requestPermission();
            return answer === 'granted';
        } catch (_) { return false; }
    };

    /**
     * Raise a notification for something that just happened.
     *
     * Skipped while the app is the thing on screen — the page has already
     * shown it, and a second copy in the corner is noise.
     */
    window.smNotify = async function smNotify({ title, body, url, tag }) {
        if (!canNotify() || Notification.permission !== 'granted') return false;
        if (document.visibilityState === 'visible') return false;
        const opts = {
            body: body || '',
            icon: '/images/pwa/icon-192.png',
            badge: '/images/pwa/icon-192.png',
            tag: tag || 'anisystem',
            renotify: true,
            data: { url: url || '/app' },
            vibrate: [90, 40, 90],
        };
        try {
            const reg = await navigator.serviceWorker?.getRegistration();
            // Through the worker where there is one: those survive the page
            // being backgrounded, and they are the ones a phone shows.
            if (reg) { await reg.showNotification(title, opts); return true; }
            const n = new Notification(title, opts);
            n.onclick = () => { window.focus(); location.href = opts.data.url; };
            return true;
        } catch (_) { return false; }
    };
})();

/**
 * Focus a field, unless a keyboard would jump up and cover the thing the
 * user just opened.
 *
 * On a desktop, landing in the first field is a courtesy. On a phone it is
 * an ambush: the sheet opens, the keyboard slides over two thirds of it,
 * and whatever was being read is gone before it was read. Every modal in
 * the app asks for focus through here, so the rule is one rule.
 *
 * Pass { always: true } for the rare field that IS the screen — a search
 * box opened by tapping a magnifier, say.
 */
window.smFocus = function smFocus(el, { delay = 60, always = false } = {}) {
    const target = typeof el === 'string' ? document.getElementById(el) : el;
    if (!target) return;
    if (!always && window.matchMedia('(pointer: coarse)').matches) return;
    setTimeout(() => { try { target.focus(); } catch (_) { /* gone already */ } }, delay);
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
        /* The same card every other wait in this app uses.
         *
         * This was the last turning ring left, and a ring says only "not
         * broken yet" — which is the least a wait can say. The layout carries
         * a wait card on every page for the navigation veil, so this borrows
         * that one rather than building a second: one set of scenes, one pool
         * of reminders, and no way for the two to drift apart.
         *
         * The spinner stays as the fallback for the few screens outside the
         * app shell — signing in, mainly — which have no card to copy. */
        const source = document.querySelector('#navLoader .bv-card, .bv-card');
        if (source) {
            el.classList.add('has-card');
            el.appendChild(source.cloneNode(true));
        } else {
            el.innerHTML = '<span class="spin" aria-hidden="true"></span>';
        }
        el.insertAdjacentHTML('beforeend', '<p data-loader-label></p>');
        document.body.appendChild(el);
    }
    // A different reminder every time it goes up, and its clock started, so
    // the card's own minimum-visible floor is measured from now.
    const card = el.querySelector('.bv-card');
    if (card) {
        try { window.rollWaitLine?.(card); } catch (_) { /* pool not loaded */ }
        try { window.waitCardShown?.(card); } catch (_) { /* older build */ }
    }
    // The label is the TASK — "Duplicating…" — and the card says the
    // reminder. Two different things, so they get two different places.
    el.querySelector('[data-loader-label]').textContent = label;
    el.classList.remove('hidden');
    /* And the page holds still.
     *
     * The overlay is fixed, which covers the viewport — but only while
     * nobody scrolls. This one is translucent, so scrolling under it drags
     * the page past behind the glass, and at the end of a long page the
     * reader arrives somewhere the overlay was never covering. */
    document.documentElement.classList.add('load-held');

    return {
        hide: () => {
            el.classList.add('hidden');
            document.documentElement.classList.remove('load-held');
        },
    };
};

/* Animate a button (or toolbar item) that would otherwise snap in/out via a
   display:none class: squeeze the width + fade with the house easing, then
   apply the class so the steady state stays plain CSS. The margin compensates
   the parent's flex gap so neighbours slide instead of jumping. */
window.animToggleHidden = function animToggleHidden(el, hide, cls = 'hidden') {
    if (!el) return;
    if (!!hide === el.classList.contains(cls) && !el.__animHideTimer) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || document.documentElement.classList.contains('sm-still')) {
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

/* Sideways rails pan natively under a finger, but a mouse has no native
   drag-to-scroll — grab-and-slide for desktop, delegated so strips injected
   later (SPA modules) work too. `.drag-scroll` marks any rail that wants
   this (the people-you-may-know rail, the post carousels); `.scroll-chips`
   had it first. A real drag swallows the click that follows it, so sliding
   never activates a chip or opens a profile. */
document.addEventListener('pointerdown', (e) => {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    const strip = e.target.closest('.scroll-chips, .drag-scroll');
    if (!strip || strip.scrollWidth <= strip.clientWidth) return;
    const startX = e.clientX;
    const startLeft = strip.scrollLeft;
    // Smooth-scrolling rails animate every scrollLeft write; the hand wants
    // the rail glued to it, so smoothness stands down for the drag.
    const smoothBefore = strip.style.scrollBehavior;
    strip.style.scrollBehavior = 'auto';
    let dragged = false;
    const move = (ev) => {
        const dx = ev.clientX - startX;
        if (!dragged && Math.abs(dx) > 4) dragged = true;
        if (dragged) strip.scrollLeft = startLeft - dx;
    };
    const up = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', up);
        strip.style.scrollBehavior = smoothBefore;
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

/* ======================================================================
 * The concertina: every fold in the app closes by max-height.
 *
 * These folds used to close by `grid-template-rows: 1fr → 0fr`, which is
 * the modern trick — and on at least one real phone the row simply never
 * collapsed: the chevron turned and the body stood still. max-height is
 * as old as CSS and closes everywhere, so the stylesheets now carry the
 * resting states (`max-height: 0` shut, nothing when open) and this
 * watcher supplies the animation: it sees a fold's state class flip,
 * measures the content, and slides an inline max-height between the two
 * real heights on the house curve. Engines that cannot animate still
 * fold — they just snap, which is also what reduced-motion asks for.
 *
 * A class flip alone cannot animate (none ↔ 0 has no midpoint), so
 * folds applied in bulk at load or on insert land silently instead of
 * rippling — the old permanent 1fr transition re-animated on every
 * relayout, and several screens grew `.is-folding` armings to fight it.
 * Those armings are now inert and can stay.
 * ==================================================================== */
(() => {
    // Every accordion in the app: who carries the state class, which class
    // it is, and where the sliding wrapper lives (null = the carrier
    // itself). `open: true` marks folds whose class means OPEN, not shut.
    const FOLDS = [
        { sel: '.note-card', cls: 'is-collapsed', fold: '.note-fold' },
        { sel: '.nh-card', cls: 'is-folded', fold: '.nh-fold' },
        { sel: '.mir-find', cls: 'is-shut', fold: '.mir-find-body' },
        { sel: '.adv-rest', cls: 'is-open', fold: null, open: true },
        { sel: '.gs-lot', cls: 'is-folded', fold: '.gs-fold' },
        { sel: '.date-group', cls: 'is-folded', fold: '.date-body' },
        { sel: '.ipp-fold', cls: 'is-shut', fold: null },
        { sel: '.ds-card', cls: 'is-folded', fold: '.ds-fold-wrap' },
        { sel: '.tk-group', cls: 'is-folded', fold: '.tk-group-body' },
        { sel: '.gr-card', cls: 'is-folded', fold: '.gr-fold' },
        { sel: '.qa-panel', cls: 'is-folded', fold: '.qa-panel-fold' },
        { sel: '.se-card', cls: 'is-folded', fold: '.se-fold-wrap' },
        { sel: '.set-log-detail', cls: 'is-open', fold: null, open: true },
        { sel: '.mod-say-wrap', cls: 'is-away', fold: null },
        { sel: '.cw-panel', cls: 'is-folded', fold: '.cw-fold' },
        { sel: '.wx-open', cls: 'is-on', fold: null, open: true },
        { sel: '.wtp-quote', cls: 'is-min', fold: '.q-body' },
    ];
    const ANY = FOLDS.map((f) => f.sel).join(',');

    // The stylesheet resumes after any ride or skip: 0 when shut, free when
    // open. Every path below ends here — a fold whose class says open but
    // whose inline pin says 0px is a card that LOOKS shut with its chevron
    // down, which is the ghost this function exists to make impossible.
    function settle(el) {
        clearTimeout(el.__concertina);
        el.__concertina = null;
        el.style.maxHeight = '';
        el.style.transition = '';
    }

    function slide(el, toShut) {
        // Whatever happens next, no previous ride's pin may outlive this
        // flip — the skip paths used to leave one behind.
        clearTimeout(el.__concertina);
        // Snap when motion is unwelcome, or before anyone has touched the
        // page — a page arranging itself at load is not a gesture answered.
        if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) { settle(el); return; }
        if (!(navigator.userActivation && navigator.userActivation.hasBeenActive)) { settle(el); return; }
        const full = el.scrollHeight;
        if (!full) { settle(el); return; }
        // Mid-flight the inline pin says where the fold really is; settled,
        // direction says it. scrollHeight ignores max-height, so `full` is
        // honest either way.
        const from = el.style.maxHeight ? el.getBoundingClientRect().height : (toShut ? full : 0);
        const to = toShut ? 0 : full;
        if (Math.abs(from - to) < 1) { settle(el); return; }
        el.style.transition = 'none';
        el.style.maxHeight = from + 'px';
        void el.offsetHeight;
        el.style.transition = '';
        el.style.maxHeight = to + 'px';
        // Two ways home: the transition's own end, and a timer for frames a
        // busy phone drops — whichever comes first clears the pin.
        const done = (ev) => {
            if (ev && (ev.target !== el || ev.propertyName !== 'max-height')) return;
            el.removeEventListener('transitionend', done);
            settle(el);
        };
        el.addEventListener('transitionend', done);
        el.__concertina = setTimeout(done, 420);
    }

    new MutationObserver((muts) => {
        for (const m of muts) {
            const el = m.target;
            if (el.nodeType !== 1 || !el.matches(ANY)) continue;
            const had = (m.oldValue || '').split(/\s+/);
            for (const f of FOLDS) {
                if (!el.matches(f.sel)) continue;
                const wasShut = f.open ? !had.includes(f.cls) : had.includes(f.cls);
                const isShut = f.open ? !el.classList.contains(f.cls) : el.classList.contains(f.cls);
                if (wasShut === isShut) continue;
                const wrap = f.fold ? el.querySelector(f.fold) : el;
                if (wrap) slide(wrap, isShut);
            }
        }
    }).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'], attributeOldValue: true });
})();

/* ======================================================================
 * Offline Mode, first tier.
 *
 * Off by default; a per-device choice, like the accessibility settings.
 * When on: the service worker keeps a copy of every page and file this
 * browser fetches, so screens already visited still open with no signal
 * (read-only), and the one write that works offline so far — ticking an
 * activity done — queues in an outbox here and replays itself the moment
 * the connection returns. Conflicts are last-write-wins: the offline tick
 * lands as a SET (?to=), and the toast says the server may have moved.
 * More offline actions ride the same outbox as they are added.
 *
 * The yellow bar above the top bar is the whole indicator: offline and
 * working, with the count of changes waiting to sync.
 * ==================================================================== */
(() => {
    const KEY = 'anee-offline-mode';
    const on = () => { try { return localStorage.getItem(KEY) === '1'; } catch (_) { return false; } };
    const tellSw = (v) => {
        try {
            navigator.serviceWorker?.ready?.then((reg) => reg.active?.postMessage({ type: 'anee-offline', on: !!v }));
        } catch (_) { /* no SW here */ }
    };

    /* ---- the outbox ---- */
    const dbp = () => new Promise((res, rej) => {
        const r = indexedDB.open('anee-offline', 1);
        r.onupgradeneeded = () => r.result.createObjectStore('outbox', { keyPath: 'id', autoIncrement: true });
        r.onsuccess = () => res(r.result);
        r.onerror = () => rej(r.error);
    });
    async function outboxAll() {
        try {
            const db = await dbp();
            return await new Promise((res, rej) => {
                const rq = db.transaction('outbox').objectStore('outbox').getAll();
                rq.onsuccess = () => res(rq.result || []);
                rq.onerror = () => rej(rq.error);
            });
        } catch (_) { return []; }
    }
    async function outboxDrop(id) {
        const db = await dbp();
        return new Promise((res) => {
            const tx = db.transaction('outbox', 'readwrite');
            tx.objectStore('outbox').delete(id);
            tx.oncomplete = res;
            tx.onerror = res;
        });
    }
    async function enqueue(action) {
        const db = await dbp();
        await new Promise((res, rej) => {
            const tx = db.transaction('outbox', 'readwrite');
            tx.objectStore('outbox').add(Object.assign({ ts: Date.now() }, action));
            tx.oncomplete = res;
            tx.onerror = () => rej(tx.error);
        });
        paintBar();
    }
    /* A whole capture, blobs and all: photos, voice notes and clips ride
       the outbox as their FormData taken apart — IndexedDB keeps Blobs
       whole — and the drain below puts the form back together and posts
       it exactly as the online path would have. */
    async function enqueueForm(url, fd) {
        const fields = [];
        const files = [];
        for (const [k, v] of fd.entries()) {
            if (v instanceof Blob) files.push({ field: k, name: v.name || 'file', type: v.type || '', blob: v });
            else fields.push([k, String(v)]);
        }
        await enqueue({ url, method: 'POST', fields, files });
    }

    /* ---- the drain: in order, stopping if the line drops again ---- */
    let draining = false;
    let retryTimer = null;
    async function drain() {
        if (draining || !navigator.onLine || !on()) return;
        draining = true;
        let leftover = 0;
        try {
            const rows = await outboxAll();
            if (!rows.length) return;
            let ok = 0;
            let done = 0;
            for (const row of rows.sort((a, b) => a.id - b.id)) {
                try {
                    // A queued capture carries its form taken apart; put it
                    // back together, blobs and all. A bare action posts as-is.
                    let body;
                    if ((row.fields && row.fields.length) || (row.files && row.files.length)) {
                        body = new FormData();
                        (row.fields || []).forEach(([k, v]) => body.append(k, v));
                        (row.files || []).forEach((f) => body.append(f.field, new File([f.blob], f.name, { type: f.type })));
                    }
                    const r = await fetch(row.url, {
                        method: row.method || 'POST',
                        credentials: 'same-origin',
                        body,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            Accept: 'application/json',
                        },
                    });
                    if (r.status >= 500) throw new Error('server busy');
                    // 2xx landed; 4xx means the thing is gone or refused —
                    // dropping it beats replaying it forever.
                    await outboxDrop(row.id);
                    done++;
                    if (r.ok) ok++;
                } catch (_) { break; }
            }
            leftover = rows.length - done;
            if (ok) {
                window.toast?.(ok + ' offline change' + (ok > 1 ? 's' : '') + ' synced. If the farm moved while you were away, your change won — refresh to see everything.', 'success', 5000);
                document.dispatchEvent(new CustomEvent('anee:offline-synced', { detail: { count: ok } }));
            }
        } finally {
            draining = false;
            paintBar();
            // The first try after "online" can land on a stack still waking
            // up — anything left behind gets another go shortly, and again
            // after that, until the box is empty.
            if (leftover > 0 && navigator.onLine) {
                clearTimeout(retryTimer);
                retryTimer = setTimeout(drain, 4000);
            }
        }
    }

    /* ---- the yellow bar above the top bar ---- */
    async function paintBar() {
        let bar = document.getElementById('aneeOfflineBar');
        const want = on() && !navigator.onLine;
        if (!want) {
            bar?.remove();
            document.body.classList.remove('has-offline-bar');
            return;
        }
        const pending = (await outboxAll()).length;
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'aneeOfflineBar';
            bar.setAttribute('role', 'status');
            document.body.prepend(bar);
        }
        document.body.classList.add('has-offline-bar');
        // The icon rides inline: the one picture that must render with no
        // signal cannot depend on any cache having been warmed first.
        bar.innerHTML = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgAAAAIACAYAAAD0eNT6AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAOxAAADsQBlSsOGwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAACAASURBVHic7d15nFUF/f/x9+fcmXuH2WDYFY1FcAlxnQVFS9xy11KprCx/lZUaDJjL95sVpi1oyuaStmiWpqC5RypbJsIsoCK4oiwugGwDM8PM3c7n9wfwjYyBWe49n3PueT8fjx7fx6NHMi/5MnPenHPuOQARERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERUWaIdQARdc6dtU39U05sKNJuH3Wkr7raW0R6A9obQG8I+kBRtvN/3g2Cgv/7hxWlACIAUhA0QtEIoAWCJkC2wdUWOGgW1a2AbIDqWhfygURkbcRJftBvReH6MWMk7f2/NRFlCgcAkY/9pl57R7T1cLiRoeLoUCiGAjgIiqEQFBumpQGsF+BDBT4UwZuui+UQebNHc/5bl42WVsM2ImoHDgAin5j8ivZAKl7uqJRDcawKygEMsu7qhDQUKxVYLoI3ReSNtOqSbRXRNyeKuNZxRLQDBwCRkckLWwaJEzkFoidDUQnBUOumLGsAsBDAIhV9ORGP1Vx3gjRaRxGFFQcAkUfuqNFeSScxWlw9FZBTQnDA35c0BMsBfVlVXorkRZ8fe4xssI4iCgsOAKIsml4fPzTt4iIILoDiaACOdZOPuQLUu4pZ6sjft5Xn1/OSAVH2cAAQZdjkutYhjuJcBS4GZJR1T4BtgmCuQGa7knx6fHnRWusgolzCAUCUAdPqWw9Sla+r4mIAw617clBaBXOg8te4k//49eWy1TqIKOg4AIg6aeJy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y9OF82qWCKDWUURhwwFA5AObTz22ez7yTnWgJ6niBAAjAESsuzIkDWCpCBaIi7ktMZnTa1bNNusoorDjACDyoU1nVpXG4hip4o4S4DhAjgTQ17qrndZD8JoCC0VlQUtr3qI+CxY0WkcR0X/iACAKiKZTqvpB3BFQOQKQEQo9WIDBsHss8VoFVoro21BZBtGl6uQvLXn+5U+MeoioAzgAiAJOTzqpoCmvdbDjpgYBzkCI9FWgF6C9sONRxb105yOLBegOgSOKfAWKd/53TSpIQuEqsHXnf7cJ//cf3SjAJqhsUNFVbsRdVZIoWSXz57ca/SsTERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERGRj/x/p7EXWe2iOHsAAAAASUVORK5CYII=" alt="" style="width:1.15rem;height:1.15rem;object-fit:contain;flex:none">'
            + '<span>Offline mode — anee.io keeps working. '
            + (pending ? '<b>' + pending + ' change' + (pending > 1 ? 's' : '') + ' waiting to sync.</b>' : 'Changes will sync when you\'re back.')
            + '</span>';
        // The bar wraps to two lines on a narrow phone, so the offsets that
        // clear it are measured, not guessed — a fixed rem left the top bar
        // glued to (or under) a taller bar.
        document.body.style.setProperty('--offbar-h', Math.ceil(bar.getBoundingClientRect().height) + 'px');
    }

    window.addEventListener('online', () => { paintBar(); drain(); });
    /* ---- warming the shelf ----
       The runtime cache only holds pages the reader has VISITED with the
       mode on, so walking to a not-yet-visited module offline hit the
       browser's own "no connection" page. While the mode is on and the
       line is up, the app asks the server which pages matter — the
       dashboard, each season's hub and shell, the notes hub — and fetches
       them through the service worker so their copies are on the shelf
       before the signal goes. At most once per half hour, and one page
       failing is not a reason to stop the rest. */
    async function warm(force) {
        if (!on() || !navigator.onLine || !('serviceWorker' in navigator)) return;
        try {
            if (!force) {
                const at = Number(sessionStorage.getItem('anee-offline-warmed') || 0);
                if (Date.now() - at < 1800000) return;
            }
            sessionStorage.setItem('anee-offline-warmed', String(Date.now()));
        } catch (_) { /* private mode: warm anyway */ }
        try {
            const res = await fetch('/app/offline-manifest', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const urls = (((await res.json()) || {}).data || {}).urls || [];
            for (const u of urls.slice(0, 16)) {
                try { await fetch(u, { credentials: 'same-origin' }); } catch (_) { /* next */ }
            }
        } catch (_) { /* the next load tries again */ }
    }

    window.addEventListener('offline', paintBar);
    window.addEventListener('online', () => setTimeout(() => warm(), 2000));
    paintBar();
    tellSw(on());
    drain();
    // After the service worker has been told the marker is on — a warm
    // fetch that beats the message would pass the SW uncopied.
    setTimeout(() => warm(), 1500);

    window.aneeOffline = {
        on,
        set(v) {
            try { localStorage.setItem(KEY, v ? '1' : '0'); } catch (_) { /* private mode */ }
            tellSw(v);
            paintBar();
            if (v) { drain(); setTimeout(() => warm(true), 800); }
            document.dispatchEvent(new CustomEvent('anee:offline-mode', { detail: { on: !!v } }));
        },
        enqueue,
        enqueueForm,
        drain,
        warm,
        pending: () => outboxAll().then((r) => r.length),
    };

    /* The account-menu switch, wherever the app layout rendered one. */
    const menuBtn = document.getElementById('offlineModeToggle');
    if (menuBtn) {
        const paint = () => {
            const v = on();
            menuBtn.setAttribute('aria-checked', v ? 'true' : 'false');
            menuBtn.classList.toggle('is-on', v);
        };
        menuBtn.addEventListener('click', () => {
            window.aneeOffline.set(!on());
            paint();
            window.toast?.(on()
                ? 'Offline mode is on — pages you visit are kept for the field.'
                : 'Offline mode is off.');
        });
        paint();
    }
})();

/* ------------------------------------------------------------------ */
/* Arriving on the exact thing a link named.                           */
/*                                                                     */
/* A tag shelf (and anything else that points into a module) sends the */
/* reader to one row of a long list. The module boots, fetches, paints */
/* — and only then does the row exist, so this hunts for a while       */
/* before giving up quietly. Found, the row is scrolled to the middle  */
/* of the screen and flashed, because "which of forty cards was meant" */
/* is the whole question.                                              */
/* ------------------------------------------------------------------ */
window.smSpot = function smSpot(selector, tries = 120) {
    const hunt = () => {
        const el = document.querySelector(selector);
        if (!el) { if (--tries > 0) setTimeout(hunt, 250); return; }
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('sm-spotted');
        setTimeout(() => el.classList.remove('sm-spotted'), 2600);
    };
    hunt();
};
