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
 * Speak instead of type.
 *
 * Every text field in the app can be dictated into, without any field
 * having to know about it: one floating microphone follows whatever is
 * focused. Wrapping thousands of inputs would have meant touching every
 * layout in the system and breaking some of them; anchoring one button to
 * the focused field's corner touches none.
 *
 * Browsers that have no speech recognition simply never see the button.
 * ==================================================================== */
(function voiceToText() {
    const Recog = window.SpeechRecognition || window.webkitSpeechRecognition;
    // Chrome hands out the constructor on plain http too, where start() can
    // only ever fail — and this app is reached over the farm's own LAN as
    // often as over https. A button that cannot work should not appear.
    if (!Recog || !window.isSecureContext) return;

    // Fields worth talking into. Passwords and the typed ones a keyboard
    // does better (numbers, dates) are left alone.
    const SKIP_TYPES = ['password', 'number', 'date', 'datetime-local', 'time', 'month', 'week',
        'file', 'checkbox', 'radio', 'range', 'color', 'hidden', 'submit', 'button', 'image', 'reset'];
    const LANGS = [
        { code: 'en-PH', label: 'EN' },
        { code: 'fil-PH', label: 'FIL' },
    ];
    const LANG_KEY = 'smVoiceLang';

    let target = null;          // the field being dictated into
    let recog = null;
    let listening = false;
    let langIndex = 0;
    try {
        const saved = localStorage.getItem(LANG_KEY);
        const at = LANGS.findIndex((l) => l.code === saved);
        if (at >= 0) langIndex = at;
    } catch (_) { /* private mode */ }

    // A search box already keeps something in its right-hand corner — a
    // clear ✕, a match count — and the mic would sit on top of it. Dictating
    // a filter is not worth hiding the control that undoes it.
    const isSearchish = (el) => {
        const t = (el.getAttribute('type') || '').toLowerCase();
        if (t === 'search' || el.getAttribute('role') === 'searchbox') return true;
        return /search|filter/i.test(`${el.id} ${el.name || ''} ${el.className || ''}`);
    };

    function dictatable(el) {
        if (!el || el.disabled || el.readOnly) return false;
        // Any field, or anything around it, can opt out by name.
        if (el.closest('[data-no-dictate]')) return false;
        if (el.tagName === 'TEXTAREA') return true;
        if (el.tagName === 'INPUT') {
            const t = (el.getAttribute('type') || 'text').toLowerCase();
            return !SKIP_TYPES.includes(t) && !isSearchish(el);
        }
        // Rich editors — the note body, an activity description, a topic.
        return el.isContentEditable;
    }

    /* ---- the button ---- */
    const mic = document.createElement('button');
    mic.type = 'button';
    mic.className = 'sm-mic';
    mic.setAttribute('aria-label', 'Dictate into this field');
    mic.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 00-6 0v6a3 3 0 003 3z"/>'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0M12 18v3"/></svg>'
        + '<span class="sm-mic-lang"></span>';
    mic.hidden = true;
    document.body.appendChild(mic);
    const langChip = mic.querySelector('.sm-mic-lang');
    const sayLang = () => { langChip.textContent = LANGS[langIndex].label; };
    sayLang();

    // A word about what is happening, while it happens.
    const pill = document.createElement('div');
    pill.className = 'sm-mic-pill';
    pill.hidden = true;
    pill.innerHTML = '<span class="sm-mic-wave"><i></i><i></i><i></i></span><span class="sm-mic-said">Listening…</span>';
    document.body.appendChild(pill);
    const said = pill.querySelector('.sm-mic-said');

    /* Is the field still really there to be written into?
     *
     * A closed sheet is the case a zero rectangle misses: closeSheet leaves
     * the node in the document and merely slides it away behind
     * `visibility: hidden`, which still measures a full-size box. So ask the
     * browser whether the element is actually visible, and fall back to the
     * sheet's own open flag where checkVisibility is not available. */
    function gone() {
        if (!target || !target.isConnected) return true;
        if (typeof target.checkVisibility === 'function') {
            if (!target.checkVisibility({ visibilityProperty: true, contentVisibilityAuto: true })) return true;
        }
        const sheet = target.closest('.sheet');
        if (sheet && !sheet.classList.contains('is-open')) return true;
        const r = target.getBoundingClientRect();
        return r.width === 0 && r.height === 0;
    }

    /* ---- where it sits ---- */

    /* Some boxes keep their own tenant in that corner: a reply's ✕ sits
       there, pinned to the field it closes. The mic stands to its left
       instead of on top of it — and asks for a wide enough lane that the
       words stop before both. Returns the room to leave, 0 if the corner is
       free. */
    function cornerTaken(el) {
        const shell = el && el.closest && el.closest('.reply-shell');
        const held = shell && shell.querySelector(':scope > .js-reply-cancel');
        // In the flow it is a tool in the row, not a tenant of the corner.
        if (!held || getComputedStyle(held).position !== 'absolute') return 0;
        return held.getBoundingClientRect().width + 4;
    }

    function place() {
        if (!target || mic.hidden) return;
        // The field can go while it is being dictated into — a sheet closes,
        // a module is re-fetched. Listening on into a node nobody can see is
        // worse than losing the sentence, so that ends the dictation rather
        // than being refused by hide()'s own guard.
        if (gone()) { stop(); mic.hidden = true; closeGutter(); target = null; return; }
        const r = target.getBoundingClientRect();
        const size = 32, gap = 6;
        /* Inside the field's right edge, in the gutter opened for it, so the
         * words stop before the button rather than running underneath it. On
         * a tall box it sits at the bottom of that column, where the caret is
         * least likely to be. */
        const tall = r.height > 60;
        const border = parseFloat(getComputedStyle(target).borderRightWidth) || 0;
        let top = tall ? r.bottom - size - gap : r.top + (r.height - size) / 2;
        let left = r.right - size - gap - border - cornerTaken(target);
        // Never off-screen, and never under the keyboard's own bar.
        top = Math.max(gap, Math.min(top, window.innerHeight - size - gap));
        left = Math.max(gap, Math.min(left, window.innerWidth - size - gap));
        mic.style.top = top + 'px';
        mic.style.left = left + 'px';
    }

    /* The room the mic stands in, given back when it leaves.
     *
     * Measured against what the field already has: a composer that keeps a
     * right-hand column for its own send button needs nothing from us, and
     * shrinking its padding to ours would be worse than doing nothing. */
    /* Wide enough that the words stop short of the button rather than up
     * against it: the button is 32, its gap 6, and the language chip juts
     * another 6 past its corner. 52 leaves daylight. */
    const GUTTER = 52;
    let gutterHeld = null;      // { el, had } while a field is holding one

    function openGutter(el) {
        closeGutter();
        if (!el) return;
        const want = GUTTER + cornerTaken(el);
        const now = parseFloat(getComputedStyle(el).paddingRight) || 0;
        if (now >= want) return;                   // it already keeps the room
        gutterHeld = { el, had: el.style.getPropertyValue('padding-right'), prio: el.style.getPropertyPriority('padding-right') };
        /* Set with priority: a field styled by a class that itself shouts
         * (`pr-16!` and friends are all over this codebase) would otherwise
         * beat a plain inline style, and the words would go right back under
         * the button on exactly the screens nobody thought to check. */
        el.style.setProperty('padding-right', want + 'px', 'important');
    }
    function closeGutter() {
        if (!gutterHeld) return;
        // Back to exactly what the field said before, priority and all.
        gutterHeld.el.style.removeProperty('padding-right');
        if (gutterHeld.had) {
            gutterHeld.el.style.setProperty('padding-right', gutterHeld.had, gutterHeld.prio || '');
        }
        gutterHeld = null;
    }

    function show(el) {
        target = el;
        mic.hidden = false;
        openGutter(el);
        place();
        // A sheet is still sliding when the field inside it takes focus, so
        // the first measurement is of a rectangle on its way somewhere. A few
        // more passes catch where it settles; place() is cheap and idempotent.
        [60, 180, 340].forEach((ms) => setTimeout(place, ms));
    }
    function hide() {
        if (listening) return;              // never vanish mid-sentence
        mic.hidden = true;
        closeGutter();
        target = null;
    }

    document.addEventListener('focusin', (e) => {
        if (dictatable(e.target)) show(e.target);
        else if (!mic.contains(e.target)) hide();
    });
    document.addEventListener('focusout', (e) => {
        // The mic takes focus for an instant when tapped; let that settle
        // before deciding the field is really gone.
        setTimeout(() => {
            if (listening) return;
            // The tap that reached the mic counts as staying with the field.
            if (mic.contains(document.activeElement)) return;
            if (document.activeElement && dictatable(document.activeElement)) return;
            hide();
        }, 120);
    });
    window.addEventListener('scroll', place, true);
    window.addEventListener('resize', place);
    /* A sheet closing is the common way a dictated field leaves without any
       scroll, resize or focus change to notice it by — closeSheet says so,
       and that is the moment to check. The delay lets the close settle. */
    document.addEventListener('sm:sheet-closed', () => setTimeout(place, 60));
    // Modules are swapped in the schedule shell without a sheet in sight.
    document.addEventListener('sm:module-shown', () => setTimeout(place, 60));
    // Anything else that pulls the ground out — a re-render, a removal —
    // is caught the next time the page draws.
    if (window.ResizeObserver) {
        const watch = new ResizeObserver(() => place());
        watch.observe(document.body);
    }

    /* Where the caret was in a rich editor, remembered.
     *
     * A tap on the mic takes focus off the field for an instant, and a
     * browser does not always hand the selection back on the way in. Without
     * this the dictated sentence could be inserted wherever the selection
     * had drifted to — including outside the editor entirely. Inputs need no
     * such care: selectionStart survives a blur by itself. */
    let caret = null;
    document.addEventListener('selectionchange', () => {
        if (!target || !target.isContentEditable) return;
        const sel = window.getSelection();
        if (!sel || !sel.rangeCount) return;
        const r = sel.getRangeAt(0);
        if (target.contains(r.commonAncestorContainer)) caret = r.cloneRange();
    });

    /* ---- putting words in ---- */
    function insert(text) {
        if (!target || !text) return;
        const el = target;
        if (el.isContentEditable) {
            // Whether the editor already had the caret decides which selection
            // to trust: focusing an unfocused editor parks a caret at its very
            // start, and that range IS inside the editor — trusting it would
            // put every dictated phrase at the top of the note.
            const wasFocused = document.activeElement === el;
            el.focus();
            const sel = window.getSelection();
            // Only a range genuinely inside this editor may be written to;
            // otherwise fall back to where the caret last was in it.
            let range = null;
            if (wasFocused && sel && sel.rangeCount) {
                const live = sel.getRangeAt(0);
                if (el.contains(live.commonAncestorContainer)) range = live;
            }
            if (!range && caret && el.contains(caret.commonAncestorContainer)) range = caret;
            if (range) {
                range.deleteContents();
                const node = document.createTextNode(text);
                range.insertNode(node);
                range.setStartAfter(node);
                range.collapse(true);
                if (sel) { sel.removeAllRanges(); sel.addRange(range); }
                // Where the next phrase continues from.
                caret = range.cloneRange();
            } else {
                el.appendChild(document.createTextNode(text));
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
        // email/url/tel report null here rather than a number, and `?? 0`
        // put every phrase at the very front of the field while polish()
        // thought the field was empty.
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? start;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        const at = start + text.length;
        try { el.setSelectionRange(at, at); } catch (_) { /* not all inputs allow it */ }
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /* A dictated sentence should read like one: speech comes back bare, so
       it is spaced against what is already there and given a capital when it
       starts something. */
    /** The words already written BEFORE the caret — not the whole field. */
    function textBeforeCaret() {
        if (!target) return '';
        if (!target.isContentEditable) {
            const v = String(target.value || '');
            return v.slice(0, target.selectionStart ?? v.length);
        }
        const sel = window.getSelection();
        let at = sel && sel.rangeCount ? sel.getRangeAt(0) : null;
        if (!at || !target.contains(at.startContainer)) at = caret;
        if (!at || !target.contains(at.startContainer)) return target.textContent || '';
        // Measuring to the caret rather than reading the whole note: a
        // sentence dictated into the middle of one was being capitalised
        // from the note's last character, wherever that happened to be.
        const upto = document.createRange();
        upto.selectNodeContents(target);
        try { upto.setEnd(at.startContainer, at.startOffset); } catch (_) { return target.textContent || ''; }
        return upto.toString();
    }

    function polish(text) {
        let out = String(text || '').trim();
        if (!out) return '';
        const before = textBeforeCaret();
        const tail = before.replace(/\s+$/, '').slice(-1);
        if (tail === '' || '.!?'.includes(tail)) out = out.charAt(0).toUpperCase() + out.slice(1);
        const needsSpace = before !== '' && !/\s$/.test(before);
        return (needsSpace ? ' ' : '') + out;
    }

    function stop() {
        listening = false;
        mic.classList.remove('is-live');
        pill.hidden = true;
        said.textContent = 'Listening…';
        // Deaf before it is told to stop: the engine delivers one last final
        // result after stop(), and that handler would resurrect a target the
        // caller has just put down — writing into a hidden or covered field.
        if (recog) {
            recog.onresult = null;
            recog.onend = null;
            recog.onerror = null;
            try { recog.stop(); } catch (_) { /* already stopped */ }
        }
        recog = null;
    }

    function start() {
        if (!target) return;
        const el = target;
        recog = new Recog();
        recog.lang = LANGS[langIndex].code;
        recog.interimResults = true;
        recog.continuous = true;
        // Which finalised phrases have already been written. The results list
        // is cumulative and a revised result can arrive with an index that
        // was final once already — without this the sentence lands twice.
        let writtenTo = -1;
        recog.onresult = (e) => {
            let interim = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                const res = e.results[i];
                if (res.isFinal) {
                    if (i <= writtenTo) continue;
                    writtenTo = i;
                    target = el;
                    insert(polish(res[0].transcript));
                } else interim += res[0].transcript;
            }
            said.textContent = interim.trim() || 'Listening…';
        };
        recog.onerror = (e) => {
            stop();
            if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
                window.toast?.('Microphone blocked. Allow it for this site to dictate.', 'error');
            } else if (e.error !== 'aborted' && e.error !== 'no-speech') {
                window.toast?.('Could not hear that. Try again.', 'error');
            }
        };
        recog.onend = () => { if (listening) stop(); };
        try {
            recog.start();
            listening = true;
            mic.classList.add('is-live');
            pill.hidden = false;
        } catch (_) { stop(); }
    }

    // Keep the field's caret on a mouse, where cancelling mousedown is safe.
    // NOT on touch: preventing touchstart cancels the whole compatibility
    // sequence the browser sends afterwards — mousedown, mouseup AND click —
    // which left the button doing nothing at all on every phone. A tap may
    // take focus off the field for an instant; the caret survives it, and
    // insert() re-focuses a rich editor before writing.
    mic.addEventListener('mousedown', (e) => e.preventDefault());
    mic.addEventListener('click', (e) => {
        // The language chip cycles instead of starting; a farm dictates in
        // two languages and switching should not be a settings trip.
        if (e.target.closest('.sm-mic-lang')) {
            langIndex = (langIndex + 1) % LANGS.length;
            sayLang();
            try { localStorage.setItem(LANG_KEY, LANGS[langIndex].code); } catch (_) { /* fine */ }
            if (listening) { stop(); start(); }
            return;
        }
        if (listening) stop(); else start();
    });
    // Anywhere else ends the dictation, so a forgotten mic never listens on.
    // "Elsewhere" means outside the field entirely — a tap inside a rich note
    // lands on one of its paragraphs, and that is moving the caret, not
    // walking away.
    document.addEventListener('click', (e) => {
        if (!listening) return;
        if (mic.contains(e.target)) return;
        if (target && (e.target === target || target.contains(e.target))) return;
        stop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && listening) stop(); });

    /* A lightbox or the drawing pad opens over the field without ever taking
       focus off it — an image is not focusable — so nothing told the mic its
       field had been covered, and it painted at z-500 in the middle of them.
       Every full-screen overlay in the app announces itself here. */
    const realRegister = window.registerOverlay;
    window.registerOverlay = function (...args) {
        stop();
        mic.hidden = true;
        target = null;
        return typeof realRegister === 'function' ? realRegister.apply(this, args) : undefined;
    };

    window.smVoice = { stop, isListening: () => listening };
})();

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
