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
    el.dispatchEvent(new CustomEvent('sheet:open'));
};

window.closeSheet = function closeSheet(id) {
    const el = document.getElementById(id);
    if (!el) return;
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
