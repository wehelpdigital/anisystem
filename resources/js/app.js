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
