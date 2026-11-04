/* ------------------------------------------------------------------ */
/* What the public site reports to Google Analytics                     */
/* ------------------------------------------------------------------ */
/*
 * 2026-11-05 (App\Support\SiteAnalytics, partials.site-analytics). The container
 * already sends page views, scrolling to the end, outbound clicks, downloads,
 * form starts and ?q= searches, so none of those are repeated here. This adds
 * only what it cannot see:
 *
 *   cta_click          a link or form going to a door that matters
 *   video_start,       our own videos (the container only follows YouTube)
 *   video_complete
 *   search             the search boxes that filter a list without changing the address
 *   content_gate_view  the guide wall came into view
 *
 * Anything to report when it comes into view marks itself: data-track-view="event_name",
 * with data-track-<param>="value" for its details, and data-track-section names a part
 * of the page. A page's own script calls window.aneeTrack(name, params).
 *
 * It has to be gtag('event'), not a bare dataLayer event: nothing in the container
 * listens for those, so they never leave the page. No page without the tag defines
 * gtag, so all of this does nothing behind the login. Never send a name, an email
 * or a phone number: Google's terms forbid it.
 */

const tagged = () => typeof window.gtag === 'function';

window.aneeTrack = (name, params) => {
    if (tagged()) window.gtag('event', name, params || {});
};

/** The doors. The address's own first word is what gets reported, whatever the query says. */
const DOORS = /^\/(?:[a-z]{2}\/)?(signup|login|checkout|ask-anee|pricing|start)(?:\/|$)/;

const words = (el) => (el?.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80);

/** Where on the page: a part that names itself, the part's own name, or its heading. */
function sectionOf(el) {
    const box = el.closest('[data-track-section], section, header, footer, nav, aside, dialog');
    if (!box) return 'page';
    if (box.dataset.trackSection) return box.dataset.trackSection;
    if (box.tagName !== 'SECTION' && box.tagName !== 'DIALOG') return box.tagName.toLowerCase();
    return box.id || words(box.querySelector('h1, h2, h3')).slice(0, 60) || 'section';
}

/** data-track-plan="libre" becomes { plan: 'libre' }. */
function paramsOf(el) {
    const out = {};
    for (const a of el.attributes) {
        if (a.name.startsWith('data-track-') && a.name !== 'data-track-view' && a.name !== 'data-track-section') {
            out[a.name.slice(11)] = a.value;
        }
    }
    return out;
}

function reportDoor(target, el, text) {
    let path = '';
    try { path = new URL(target, location.href); } catch (_) { return; }
    if (path.origin !== location.origin) return;
    const door = path.pathname.match(DOORS);
    if (!door) return;
    window.aneeTrack('cta_click', { cta_target: door[1], cta_text: text, cta_section: sectionOf(el) });
}

function clicks() {
    document.addEventListener('click', (e) => {
        const a = e.target.closest?.('a[href]');
        if (a) reportDoor(a.getAttribute('href'), a, words(a));
    }, true);

    // The landing page's email box goes to the signup form as a GET.
    document.addEventListener('submit', (e) => {
        const f = e.target;
        if (f instanceof HTMLFormElement && f.getAttribute('action') && f.method.toLowerCase() === 'get') {
            reportDoor(f.getAttribute('action'), f, words(e.submitter || f.querySelector('[type=submit]')));
        }
    }, true);
}

/** Decoration plays by itself, muted and with no controls. Only a video someone chose to watch counts. */
function videos() {
    const name = (v) => {
        const file = (v.currentSrc || v.src || '').split('?')[0].split('/').pop();
        return v.getAttribute('aria-label') || v.title || words(v.closest('.vid-card')?.querySelector('.vid-tag')) || file;
    };
    const report = (event, v) => window.aneeTrack(event, {
        video_title: name(v), video_url: (v.currentSrc || v.src || '').split('?')[0], video_provider: 'anee.io',
    });
    // Neither event bubbles, so both are caught on the way down.
    document.addEventListener('play', (e) => {
        const v = e.target;
        if (!(v instanceof HTMLVideoElement) || (v.muted && !v.controls) || v.dataset.trackedStart) return;
        v.dataset.trackedStart = '1';
        report('video_start', v);
    }, true);
    document.addEventListener('ended', (e) => {
        const v = e.target;
        if (!(v instanceof HTMLVideoElement) || !v.dataset.trackedStart || v.dataset.trackedEnd) return;
        v.dataset.trackedEnd = '1';
        report('video_complete', v);
    }, true);
}

/** A pause in the typing is one search. An address or a phone number typed by mistake is never sent. */
function searches() {
    let timer = null, last = '';
    document.addEventListener('input', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLInputElement) || el.type !== 'search') return;
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = el.value.trim().toLowerCase();
            if (q.length < 3 || q === last || /@|\d{7,}/.test(q)) return;
            last = q;
            window.aneeTrack('search', { search_term: q.slice(0, 80), search_box: el.id || el.name || sectionOf(el) });
        }, 1500);
    }, true);
}

/** Once each, the first time it is really on screen. */
function views() {
    const marked = document.querySelectorAll('[data-track-view]');
    if (!marked.length || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((seen) => seen.forEach((en) => {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        window.aneeTrack(en.target.getAttribute('data-track-view'), paramsOf(en.target));
    }), { threshold: 0.4 });
    marked.forEach((el) => io.observe(el));
}

if (tagged()) {
    clicks();
    videos();
    searches();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', views, { once: true });
    else views();
}
