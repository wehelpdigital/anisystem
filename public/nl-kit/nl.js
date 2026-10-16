/*
 * THE NEWSLETTER MODULE'S SCREEN (2026-10-17). One file, two homes: anee.io's
 * admin panel and the mother app's AniSystem > Newsletter, each mounting it on
 * an element and pointing it at its own door to the same API:
 *
 *   NLK.mount(el, { api: '/admin/newsletter/api/', csrf: '...', userEmail: '...' })
 *
 * The screen: today's plan (which group gets which email), the three lines by
 * priority with drag to order, the block builder with a live preview, the one
 * design every email wears, the people on each list, and the history of what
 * went. Nothing here sends to subscribers: the scheduler does that.
 */
(function () {
    'use strict';
    if (window.NLK) return;
    const NLK = (window.NLK = {});

    let C = {};
    let root = null;
    const S = { boot: null, tab: 'high', scope: 'line', q: '', rows: [], loading: false, seq: 0 };

    /* ================================================================ utils */

    const h = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const strip = (s) => String(s || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim();
    const $ = (sel, el) => (el || root).querySelector(sel);
    const $$ = (sel, el) => Array.from((el || root).querySelectorAll(sel));
    const uid = () => Math.random().toString(36).slice(2, 10);
    const clone = (o) => JSON.parse(JSON.stringify(o));
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    /* Each app's own dark switch: html.dark on anee.io, data-bs-theme on the mother app. */
    const appDark = () => document.documentElement.classList.contains('dark') || !!document.querySelector('[data-bs-theme="dark"]');
    function el(html) { const t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstElementChild; }

    async function call(path, opts = {}) {
        const init = { method: opts.method || (opts.body || opts.form ? 'POST' : 'GET'), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };
        if (C.csrf) init.headers['X-CSRF-TOKEN'] = C.csrf;
        if (opts.form) init.body = opts.form;
        else if (opts.body) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.body); }
        let res, data;
        try {
            res = await fetch(C.api + path, init);
        } catch (e) {
            throw new Error('The server could not be reached. Check the connection and try again.');
        }
        try { data = await res.json(); } catch (e) { data = null; }
        if (!res.ok || !data || data.success === false) {
            const msg = (data && (data.message || (data.errors && Object.values(data.errors)[0]))) || ('The server answered ' + res.status + '.');
            throw new Error(Array.isArray(msg) ? msg[0] : msg);
        }
        return data;
    }

    function toast(msg, bad) {
        let box = $('.nlk-toasts');
        if (!box) { box = el('<div class="nlk-toasts" aria-live="polite"></div>'); root.appendChild(box); }
        const t = el('<div class="nlk-toast' + (bad ? ' is-bad' : '') + '"></div>');
        t.textContent = msg;
        box.appendChild(t);
        requestAnimationFrame(() => t.classList.add('is-in'));
        setTimeout(() => { t.classList.remove('is-in'); setTimeout(() => t.remove(), 320); }, bad ? 6500 : 3600);
    }

    async function busy(btn, job) {
        if (btn) btn.classList.add('is-busy');
        try { return await job(); } catch (e) { toast(e.message, true); return null; } finally { if (btn) btn.classList.remove('is-busy'); }
    }

    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    function parseDay(s) { const m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; }
    function dayWords(s, withYear) { const d = parseDay(s); if (!d) return ''; return DAYS[d.getDay()] + ', ' + MONTHS[d.getMonth()] + ' ' + d.getDate() + (withYear ? ' ' + d.getFullYear() : ''); }
    function timeWords(s) { const m = String(s || '').match(/(\d{2}):(\d{2})/); if (!m) return ''; let hr = +m[1]; const ap = hr >= 12 ? 'PM' : 'AM'; hr = hr % 12 || 12; return hr + ':' + m[2] + ' ' + ap; }
    function whenWords(s) { return dayWords(s, true) + ' at ' + timeWords(String(s).slice(11)); }

    function loadSortable() {
        if (window.Sortable) return Promise.resolve(window.Sortable);
        if (NLK._sortable) return NLK._sortable;
        NLK._sortable = new Promise((ok, no) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js';
            s.onload = () => ok(window.Sortable);
            s.onerror = () => no(new Error('The drag helper did not load.'));
            document.head.appendChild(s);
        });
        return NLK._sortable;
    }

    /* ================================================================ icons */

    const I = {
        mail: '<path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        grip: '<path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01" stroke-width="3.2"/>',
        plus: '<path d="M12 5v14M5 12h14"/>',
        dots: '<path d="M5 12h.01M12 12h.01M19 12h.01" stroke-width="3.4"/>',
        send: '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
        eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/>',
        trash: '<path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4h6v3"/>',
        move: '<path d="M7 17L17 7M17 7H8M17 7v9"/>',
        back: '<path d="M15 19l-7-7 7-7"/>',
        close: '<path d="M6 6l12 12M18 6L6 18"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        palette: '<path d="M12 3a9 9 0 100 18c1.1 0 1.6-.9 1.6-1.7 0-.6-.3-1-.6-1.4-.3-.4-.5-.7-.5-1.2 0-.9.8-1.7 1.7-1.7H16a5 5 0 005-5C21 6.4 17 3 12 3z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10" cy="7" r="1.2"/><circle cx="15" cy="7" r="1.2"/>',
        people: '<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-1.33-7.77"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        gear: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>',
        more: '<path d="M4 6h16M4 12h16M4 18h10"/>',
        heading: '<path d="M6 4v16M18 4v16M6 12h12"/>',
        text: '<path d="M4 6h16M4 10h16M4 14h10M4 18h12"/>',
        image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/>',
        video: '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M10 9l5 3-5 3z"/>',
        button: '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M8 12h8"/>',
        callout: '<path d="M12 9v4M12 17h.01"/><rect x="3" y="4" width="18" height="16" rx="3"/>',
        anee: '<circle cx="12" cy="12" r="9"/><path d="M8.5 14.5s1.3 1.8 3.5 1.8 3.5-1.8 3.5-1.8M9 10h.01M15 10h.01" stroke-width="2.4"/>',
        story: '<rect x="3" y="3" width="18" height="10" rx="2"/><path d="M3 17h18M3 21h12"/>',
        pair: '<rect x="3" y="4" width="8" height="16" rx="2"/><rect x="13" y="4" width="8" height="16" rx="2"/>',
        quote: '<path d="M7 7h4v6H5v-2a4 4 0 012-4zM15 7h4v6h-6v-2a4 4 0 012-4zM7 13v4M15 13v4"/>',
        list: '<path d="M9 6h11M9 12h11M9 18h11M4.5 6l1 1 2-2M4.5 12l1 1 2-2M4.5 18l1 1 2-2"/>',
        divider: '<path d="M3 12h18"/><path d="M8 7h8M8 17h8" opacity=".4"/>',
        spacer: '<path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/>',
        social: '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.2 10.9l7.6-3.8M8.2 13.1l7.6 3.8"/>',
        html: '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4M14 5l-4 14"/>',
        phone: '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18h2"/>',
        desk: '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
        down: '<path d="M6 9l6 6 6-6"/>',
        link: '<path d="M10 13a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1.5 1.5M14 11a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1.5-1.5"/>',
        upload: '<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
        bolt: '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        news: '<path d="M4 5h13v14H6a2 2 0 01-2-2zM17 9h3v8a2 2 0 01-2 2M8 9h5M8 13h5"/>',
        cal: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        moon: '<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>',
    };
    const ic = (name, cls) => '<svg' + (cls ? ' class="' + cls + '"' : '') + ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (I[name] || '') + '</svg>';

    /* ============================================================ vocabulary */

    const PRI = {
        high: { name: 'High priority', short: 'High', line: 'Goes on the next daily send, first' },
        news: { name: 'Today\'s news', short: 'News', line: 'The farm news, only on its own day' },
        regular: { name: 'Regular', short: 'Regular', line: 'One a day, in the order you set' },
    };
    const STATUS = { draft: 'Draft', queued: 'In line', partial: 'Partly sent', sent: 'Sent', expired: 'Expired' };
    const SEG_COLOR = { all: '#64748b', nonusers: '#94a3b8', free: '#87B84C', libreplus: '#E8BE1C', solo: '#3d6b22', owner: '#2B3A1C', paid: '#7c3aed', credits: '#0891b2' };
    const TIERS = ['nonusers', 'free', 'libreplus', 'solo', 'owner'];
    const segName = (k) => ((S.boot && S.boot.segments.find((s) => s.key === k)) || { name: k }).name;
    const dot = (k) => '<span class="nlk-dot" style="background:' + (SEG_COLOR[k] || '#999') + '"></span>';

    /* ================================================================ mount */

    NLK.mount = function (target, cfg) {
        C = Object.assign({ api: '/admin/newsletter/api/', csrf: '', userEmail: '' }, cfg || {});
        if (!/\/$/.test(C.api)) C.api += '/';
        root = target;
        root.classList.add('nlk');
        if (C.framed) root.classList.add('nlk-framed');
        root.innerHTML = shell();
        wireShell();
        refresh(true);
        document.addEventListener('keydown', onKey);
        window.addEventListener('message', onFrameMessage);
    };

    function shell() {
        return '' +
            '<div class="nlk-top">' +
            '  <div class="nlk-brand"><span class="nlk-brand-ic">' + ic('mail') + '</span><div><b>Newsletter</b><small>One email a day, by priority and by group</small></div></div>' +
            '  <span class="nlk-state" data-state></span>' +
            '  <div class="nlk-top-acts">' +
            '    <button type="button" class="nlk-btn" data-open="theme" title="The design every email wears">' + ic('palette') + '<span>Design</span></button>' +
            '    <button type="button" class="nlk-btn" data-open="people" title="Who is on each list">' + ic('people') + '<span>People</span></button>' +
            '    <button type="button" class="nlk-btn" data-open="history" title="What went out">' + ic('clock') + '<span>History</span></button>' +
            '    <button type="button" class="nlk-btn is-icon" data-open="settings" title="Settings">' + ic('gear') + '</button>' +
            '  </div>' +
            '</div>' +
            '<section class="nlk-today" data-today><div class="nlk-skel" style="height:220px;border-radius:0"></div></section>' +
            '<nav class="nlk-tabs" role="tablist">' +
            '  <span class="nlk-tab-thumb"></span>' +
            ['high', 'news', 'regular'].map((p) => '<button type="button" role="tab" class="nlk-tab" data-p="' + p + '"><b>' + PRI[p].short + ' <span class="nlk-n" data-n="' + p + '">0</span></b><small>' + PRI[p].line + '</small></button>').join('') +
            '</nav>' +
            '<div class="nlk-bar">' +
            '  <div class="nlk-seg" data-scope><button type="button" data-v="line" class="is-on">In line and drafts</button><button type="button" data-v="done">Sent and expired</button></div>' +
            '  <label class="nlk-search">' + ic('search') + '<input type="search" placeholder="Find an email" data-q autocomplete="off"></label>' +
            '  <button type="button" class="nlk-btn is-gold" data-new>' + ic('plus') + '<span>New email</span></button>' +
            '  <button type="button" class="nlk-btn is-icon" data-more title="More">' + ic('dots') + '</button>' +
            '</div>' +
            '<p class="nlk-hint" data-hint></p>' +
            '<div class="nlk-list" data-list></div>';
    }

    function wireShell() {
        $$('.nlk-tab').forEach((b) => b.addEventListener('click', () => setTab(b.dataset.p)));
        $$('[data-scope] button').forEach((b) => b.addEventListener('click', () => {
            S.scope = b.dataset.v;
            $$('[data-scope] button').forEach((x) => x.classList.toggle('is-on', x === b));
            loadList();
        }));
        $('[data-q]').addEventListener('input', debounce((e) => { S.q = e.target.value.trim(); loadList(); }, 300));
        $('[data-new]').addEventListener('click', () => openBuilder(null, S.tab));
        $('[data-more]').addEventListener('click', (e) => moreMenu(e.currentTarget));
        $$('[data-open]').forEach((b) => b.addEventListener('click', () => ({ theme: openTheme, people: openPeople, history: openHistory, settings: openSettings })[b.dataset.open]()));
        placeThumb();
        window.addEventListener('resize', debounce(placeThumb, 100));
    }

    function setTab(p) {
        S.tab = p;
        placeThumb();
        loadList();
    }

    function placeThumb() {
        const tabs = $$('.nlk-tab');
        const on = tabs.find((t) => t.dataset.p === S.tab) || tabs[0];
        tabs.forEach((t) => t.classList.toggle('is-on', t === on));
        const th = $('.nlk-tab-thumb');
        if (!on || !th) return;
        th.style.width = on.offsetWidth + 'px';
        th.style.transform = 'translateX(' + (on.offsetLeft - 5) + 'px)';
    }

    async function refresh(withList) {
        try {
            const r = await call('boot');
            S.boot = r.data;
            paintTop();
            paintToday();
            if (withList) loadList();
        } catch (e) {
            $('[data-today]').innerHTML = '<div class="nlk-errline" style="margin:12px">' + h(e.message) + '</div>';
        }
    }

    function paintTop() {
        const b = S.boot;
        const st = $('[data-state]');
        const on = b.settings.enabled;
        st.className = 'nlk-state ' + (on ? 'is-on' : 'is-off');
        st.innerHTML = '<i></i>' + (on ? 'Sending is on' : 'Sending is off');
        st.title = on ? 'The scheduler makes real campaigns at the daily send.' : 'Each day is planned and shown, nothing is sent. Turn it on in Settings.';
        ['high', 'news', 'regular'].forEach((p) => { const n = $('[data-n="' + p + '"]'); if (n) n.textContent = b.tabs[p] ? b.tabs[p].queued : 0; });
    }

    /* ================================================================ today */

    function paintToday() {
        const P = S.boot.plan;
        const t = P.today;
        const run = P.run;
        let chip;
        if (run && run.status === 'done') chip = ['', 'Sent today · ' + (run.campaigns || []).filter((c) => c.status === 'created').length + ' campaigns'];
        else if (run && run.status === 'failed') chip = ['is-bad', 'Did not go: ' + (run.error || 'see History')];
        else if (run && run.status === 'running') chip = ['is-warn', 'Sending now'];
        else if (run && run.status === 'dry') chip = ['is-warn', 'Planned only: sending is off'];
        else if (!P.enabled) chip = ['is-warn', 'Sending is off: this is the plan'];
        else if (!P.sendPassed) chip = ['', 'Goes at ' + P.sendAt];
        else chip = ['is-warn', 'Going out shortly'];

        const cells = TIERS.map((k) => {
            const n = t.counts[k] || 0;
            const c = t.cells[k];
            let what;
            if (n <= 0) what = '<span class="nlk-none">No one on this list yet</span>';
            else if (!c) what = '<span class="nlk-none">' + (t.taken.indexOf(k) >= 0 ? 'Already had today\'s email' : 'Nothing in line for this group') + '</span>';
            else what = '<button type="button" class="nlk-link" data-edit="' + c.emailId + '"><span class="nlk-pill pr-' + c.priority + '">' + PRI[c.priority].short + '</span><span class="nlk-t">' + h(c.title) + '</span>' + (c.via ? '<span class="nlk-pill">via ' + h(segName(c.via)) + '</span>' : '') + '</button>';
            return '<div class="nlk-cell"><div class="nlk-cell-who">' + dot(k) + '<b>' + h(segName(k)) + '</b><small>' + n + (n === 1 ? ' person' : ' people') + '</small></div><div class="nlk-cell-what">' + what + '</div></div>';
        }).join('');

        const timed = (P.timed || []).map((x) => '<span class="nlk-pill pr-high" title="' + h(x.audienceWords) + '">' + ic('clock') + h(timeWords(x.at.slice(11))) + ' ' + h(dayWords(x.at)) + '</span><button type="button" class="nlk-btn is-sm is-ghost" data-edit="' + x.id + '">' + h(x.title) + (x.status === 'draft' ? ' (draft)' : '') + '</button>').join('');
        const ahead = (P.ahead || []).map((d) => '<div class="nlk-day"><b>' + h(dayWords(d.date)) + '</b>' + (d.picks.length ? d.picks.map((p) => '<div><span class="nlk-pill pr-' + p.priority + '">' + PRI[p.priority].short + '</span><span title="' + h(p.title) + '">' + h(p.title) + '</span></div>').join('') : '<em>Nothing in line yet</em>') + '</div>').join('');
        const waiting = (t.waiting || []).length ? '<details class="nlk-waiting"><summary>' + t.waiting.length + ' waiting for another day</summary><ul>' + t.waiting.map((w) => '<li><b>' + h(w.title) + '</b>: ' + h(w.why) + '</li>').join('') + '</ul></details>' : '';
        const errs = run && run.campaigns ? run.campaigns.filter((c) => c.status === 'failed').map((c) => '<div class="nlk-errline">' + h(c.error || 'A campaign failed.') + '</div>').join('') : '';

        $('[data-today]').innerHTML =
            '<div class="nlk-today-head"><div class="nlk-grow"><b>Today, ' + h(dayWords(P.date)) + '</b><br><small>The daily send is at ' + h(P.sendAt) + '. Each group gets one email at most.</small></div><span class="nlk-chip-state ' + chip[0] + '">' + h(chip[1]) + '</span></div>' +
            '<div class="nlk-cells">' + cells + '</div>' +
            '<div class="nlk-today-foot">' + errs +
            (timed ? '<div class="nlk-timed"><b>At their own time</b>' + timed + '</div>' : '') +
            waiting +
            '<div><div class="nlk-timed" style="margin-bottom:6px"><b>The next days, as things stand</b></div><div class="nlk-ahead">' + ahead + '</div></div>' +
            '</div>';
        $$('[data-edit]', $('[data-today]')).forEach((b) => b.addEventListener('click', () => openBuilder(+b.dataset.edit)));
    }

    /* ================================================================= list */

    async function loadList() {
        const my = ++S.seq;
        const list = $('[data-list]');
        list.innerHTML = '<div class="nlk-skel"></div><div class="nlk-skel"></div><div class="nlk-skel"></div>';
        const hint = $('[data-hint]');
        hint.textContent = S.scope === 'done' ? 'What already went, newest first.'
            : (S.q ? 'Showing what matches. Clear the search to drag the order.' : PRI[S.tab].line + '. Drag the grip to change the order; a draft keeps its place but is skipped.');
        try {
            const r = await call('emails?priority=' + S.tab + '&scope=' + S.scope + '&q=' + encodeURIComponent(S.q));
            if (my !== S.seq) return;
            S.rows = r.data.rows;
            paintList();
        } catch (e) {
            if (my === S.seq) list.innerHTML = '<div class="nlk-errline">' + h(e.message) + '</div>';
        }
    }

    function paintList() {
        const list = $('[data-list]');
        if (!S.rows.length) {
            const say = S.scope === 'done' ? ['Nothing sent yet', 'Emails land here once the daily send has taken them.']
                : S.q ? ['No email matches', 'Try other words.']
                : { high: ['No high priority email', 'An important email for the next send, for everyone or for some groups only.'],
                    news: ['No news email yet', 'Each morning\'s farm news page becomes an email here by itself.'],
                    regular: ['The regular line is empty', 'Bring in the Acumbamail sequence from the menu, or write a new one.'] }[S.tab];
            list.innerHTML = '<div class="nlk-empty nlk-rise"><b>' + h(say[0]) + '</b>' + h(say[1]) + (S.scope === 'line' && !S.q ? '<br><button type="button" class="nlk-btn is-gold" data-new2>' + ic('plus') + 'New email</button>' : '') + '</div>';
            const b = $('[data-new2]', list);
            if (b) b.addEventListener('click', () => openBuilder(null, S.tab));
            return;
        }
        let n = 0;
        list.innerHTML = S.rows.map((r) => rowHtml(r, ['queued', 'partial'].indexOf(r.status) >= 0 ? ++n : 0)).join('');
        list.classList.toggle('is-static', S.scope !== 'line' || !!S.q);
        $$('.nlk-row', list).forEach((row, i) => {
            row.style.animationDelay = Math.min(i, 12) * 22 + 'ms';
            row.classList.add('nlk-rise');
            wireRow(row);
        });
        if (S.scope === 'line' && !S.q) {
            loadSortable().then((Sortable) => {
                if (list._sortable) list._sortable.destroy();
                list._sortable = Sortable.create(list, {
                    handle: '.nlk-grip', animation: 220, easing: 'cubic-bezier(.22,1,.36,1)', ghostClass: 'nlk-ghost', chosenClass: 'nlk-chosen',
                    forceFallback: true, scroll: true, bubbleScroll: true,
                    onEnd: (ev) => { if (ev.oldIndex !== ev.newIndex) saveOrder(); },
                });
            }).catch((e) => toast(e.message, true));
        }
    }

    function rowHtml(r, order) {
        const live = ['queued', 'partial'].indexOf(r.status) >= 0;
        const meta = [];
        meta.push('<span class="nlk-pill st-' + r.status + '">' + STATUS[r.status] + '</span>');
        if (r.kind === 'acumba') meta.push('<span class="nlk-pill is-acumba" title="' + h(r.acumbaName || '') + '">Acumbamail template</span>');
        if (r.demo) meta.push('<span class="nlk-pill is-demo">Sample</span>');
        r.audience.forEach((k) => meta.push('<span class="nlk-pill">' + dot(k) + h(segName(k)) + '</span>'));
        if (r.sendAt) meta.push('<span class="nlk-pill is-date">' + ic('clock') + h(whenWords(r.sendAt)) + '</span>');
        else if (r.sendOn) meta.push('<span class="nlk-pill is-date">' + ic('cal') + 'Only on ' + h(dayWords(r.sendOn, true)) + '</span>');
        if (r.priority === 'news' && r.newsDate) meta.push('<span class="nlk-pill is-date">' + ic('news') + 'News of ' + h(dayWords(r.newsDate)) + '</span>');
        if (r.sentWords) meta.push('<span class="nlk-pill">Had it: ' + h(r.sentWords) + '</span>');
        if (r.lastSentAt && !live) meta.push('<span class="nlk-pill">' + h(dayWords(r.lastSentAt, true)) + '</span>');
        const canToggle = S.scope === 'line' && r.status !== 'sent';
        return '<div class="nlk-row' + (r.status === 'draft' ? ' is-draft' : '') + '" data-id="' + r.id + '">' +
            '<span class="nlk-grip" title="Drag to change the order">' + ic('grip') + '</span>' +
            '<span class="nlk-order' + (order ? '' : ' is-off') + '">' + (order ? order : '·') + '</span>' +
            '<div class="nlk-row-main" data-open-row>' +
            '  <div class="nlk-row-top"><span class="nlk-row-title">' + h(r.title) + '</span></div>' +
            '  <div class="nlk-row-sub">' + h(r.subject || 'No subject yet') + '</div>' +
            '  <div class="nlk-row-meta">' + meta.join('') + '</div>' +
            (r.note ? '<div class="nlk-row-note">' + h(r.note) + '</div>' : '') +
            '</div>' +
            '<div class="nlk-row-acts">' +
            (canToggle ? '<button type="button" class="nlk-switch' + (live ? ' is-on' : '') + '" data-toggle title="' + (live ? 'In line: tap to keep it as a draft' : 'Draft: tap to put it in the line') + '" aria-pressed="' + live + '"></button>' : '') +
            '<button type="button" class="nlk-btn is-sm" data-test title="Send a test to one address">' + ic('send') + '<span>Test</span></button>' +
            '<button type="button" class="nlk-btn is-sm is-icon" data-menu title="More">' + ic('dots') + '</button>' +
            '</div></div>';
    }

    function wireRow(row) {
        const id = +row.dataset.id;
        const r = () => S.rows.find((x) => x.id === id);
        $('[data-open-row]', row).addEventListener('click', () => openBuilder(id));
        const tg = $('[data-toggle]', row);
        if (tg) tg.addEventListener('click', async () => {
            const to = tg.classList.contains('is-on') ? 'draft' : 'queued';
            tg.classList.toggle('is-on', to === 'queued');
            try {
                const res = await call('email/' + id + '/status', { body: { status: to } });
                toast(res.message);
                await refresh(false);
                loadList();
            } catch (e) {
                tg.classList.toggle('is-on', to !== 'queued');
                toast(e.message, true);
            }
        });
        $('[data-test]', row).addEventListener('click', () => testDialog(r()));
        $('[data-menu]', row).addEventListener('click', (e) => rowMenu(e.currentTarget, r()));
    }

    async function saveOrder() {
        const ids = $$('.nlk-row', $('[data-list]')).map((x) => +x.dataset.id);
        try {
            const res = await call('reorder', { body: { priority: S.tab, ids } });
            S.boot.plan = res.data.plan;
            paintToday();
            let n = 0;
            $$('.nlk-row', $('[data-list]')).forEach((row) => {
                const r = S.rows.find((x) => x.id === +row.dataset.id);
                const o = $('.nlk-order', row);
                const live = r && ['queued', 'partial'].indexOf(r.status) >= 0;
                o.textContent = live ? ++n : '·';
                o.classList.toggle('is-off', !live);
            });
            toast('Order saved. Today\'s plan follows it.');
        } catch (e) {
            toast(e.message, true);
            loadList();
        }
    }

    /* ================================================================ menus */

    function menu(anchor, items) {
        closeMenu();
        const m = el('<div class="nlk-menu" role="menu"></div>');
        items.forEach((it) => {
            if (it === '-') { m.appendChild(el('<hr>')); return; }
            if (it.label) { m.appendChild(el('<small>' + h(it.label) + '</small>')); return; }
            const b = el('<button type="button" role="menuitem"' + (it.danger ? ' class="is-danger"' : '') + '>' + ic(it.icon || 'move') + '<span>' + h(it.text) + '</span></button>');
            b.addEventListener('click', () => { closeMenu(); it.run(); });
            m.appendChild(b);
        });
        root.appendChild(m);
        const r = anchor.getBoundingClientRect();
        const w = 240;
        m.style.top = Math.min(r.bottom + 6, window.innerHeight - m.offsetHeight - 10) + 'px';
        m.style.left = Math.max(10, Math.min(r.right - w, window.innerWidth - w - 10)) + 'px';
        m.style.width = w + 'px';
        requestAnimationFrame(() => m.classList.add('is-open'));
        setTimeout(() => document.addEventListener('click', closeMenu, { once: true }), 0);
        NLK._menu = m;
    }
    function closeMenu() { if (NLK._menu) { const m = NLK._menu; NLK._menu = null; m.classList.remove('is-open'); setTimeout(() => m.remove(), 200); } }

    function rowMenu(anchor, r) {
        const others = ['high', 'news', 'regular'].filter((p) => p !== r.priority);
        menu(anchor, [
            { text: 'Open in the builder', icon: 'eye', run: () => openBuilder(r.id) },
            { text: 'Send a test', icon: 'send', run: () => testDialog(r) },
            { text: 'Duplicate as a draft', icon: 'copy', run: () => act(r.id, 'duplicate') },
            '-',
            { label: 'Move to' },
            ...others.map((p) => ({ text: PRI[p].name, icon: 'move', run: () => act(r.id, 'move', { priority: p }) })),
            '-',
            { text: 'Remove', icon: 'trash', danger: true, run: async () => { if (await confirmBox('Remove this email?', '"' + r.title + '" leaves the line and the lists here. Campaigns already made at Acumbamail stay there.', 'Remove', true)) act(r.id, 'delete'); } },
        ]);
    }

    function moreMenu(anchor) {
        menu(anchor, [
            { text: 'Bring in the Acumbamail sequence', icon: 'upload', run: () => runAction('import', 'Bring the anee.io sequence from Acumbamail into the regular line? Emails already here are left as they are.') },
            { text: 'Make today\'s news email', icon: 'news', run: () => runAction('news-now') },
            { text: 'Add the sample emails', icon: 'plus', run: () => runAction('demo') },
        ]);
    }

    async function runAction(path, ask) {
        if (ask && !(await confirmBox('Are you sure?', ask, 'Go'))) return;
        try {
            const r = await call(path, { body: {} });
            toast(r.message);
            await refresh(false);
            loadList();
        } catch (e) { toast(e.message, true); }
    }

    async function act(id, what, body) {
        try {
            const r = await call('email/' + id + '/' + what, { body: body || {} });
            toast(r.message);
            await refresh(false);
            loadList();
        } catch (e) { toast(e.message, true); }
    }

    /* ============================================================== dialogs */

    function layer(cls, inner) {
        const L = el('<div class="nlk-layer ' + cls + '">' + inner + '</div>');
        root.appendChild(L);
        requestAnimationFrame(() => requestAnimationFrame(() => L.classList.add('is-open')));
        L._close = () => { L.classList.remove('is-open'); setTimeout(() => L.remove(), 300); };
        return L;
    }

    function confirmBox(title, text, ok, danger) {
        return new Promise((res) => {
            const L = layer('is-center', '<div class="nlk-dialog" role="dialog" aria-modal="true"><h3>' + h(title) + '</h3><p>' + h(text) + '</p><div class="nlk-dialog-acts"><button type="button" class="nlk-btn" data-no>Cancel</button><button type="button" class="nlk-btn ' + (danger ? 'is-dark is-danger' : 'is-gold') + '" data-yes>' + h(ok || 'OK') + '</button></div></div>');
            if (danger) $('[data-yes]', L).style.cssText = 'background:#b42318;border-color:#b42318;color:#fff';
            const done = (v) => { L._close(); res(v); };
            $('[data-no]', L).addEventListener('click', () => done(false));
            $('[data-yes]', L).addEventListener('click', () => done(true));
            L.addEventListener('click', (e) => { if (e.target === L) done(false); });
            L._esc = () => done(false);
            setTimeout(() => $('[data-yes]', L).focus(), 60);
        });
    }

    function askText(title, label, value, placeholder) {
        return new Promise((res) => {
            const L = layer('is-center', '<div class="nlk-dialog" role="dialog" aria-modal="true"><h3>' + h(title) + '</h3><label class="nlk-field"><span>' + h(label) + '</span><input class="nlk-in" data-v value="' + h(value || '') + '" placeholder="' + h(placeholder || '') + '"></label><div class="nlk-dialog-acts"><button type="button" class="nlk-btn" data-no>Cancel</button><button type="button" class="nlk-btn is-gold" data-yes>OK</button></div></div>');
            const inp = $('[data-v]', L);
            const done = (v) => { L._close(); res(v); };
            $('[data-no]', L).addEventListener('click', () => done(null));
            $('[data-yes]', L).addEventListener('click', () => done(inp.value.trim()));
            inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') done(inp.value.trim()); });
            L._esc = () => done(null);
            setTimeout(() => { inp.focus(); inp.select(); }, 60);
        });
    }

    function testDialog(r, getContent) {
        if (!r || !r.id) { toast('Save the email first, then send a test.', true); return; }
        const to = (S.boot && S.boot.settings.testTo) || C.userEmail || '';
        const viaAcumba = r.kind === 'acumba';
        const L = layer('is-center', '<div class="nlk-dialog" role="dialog" aria-modal="true"><h3>Send a test</h3>' +
            '<p>' + (viaAcumba ? 'This is an Acumbamail template, so the test goes through Acumbamail, to a test list that holds only this address.' : 'The email exactly as it would look, marked [Test], to one address.') + '</p>' +
            '<label class="nlk-field" style="margin-top:12px"><span>Send it to</span><input class="nlk-in" type="email" data-v value="' + h(to) + '" placeholder="you@example.com"></label>' +
            '<div class="nlk-dialog-acts"><button type="button" class="nlk-btn" data-no>Cancel</button><button type="button" class="nlk-btn is-gold" data-yes>' + ic('send') + 'Send the test</button></div></div>');
        const inp = $('[data-v]', L);
        $('[data-no]', L).addEventListener('click', () => L._close());
        L._esc = () => L._close();
        const go = async () => {
            const btn = $('[data-yes]', L);
            await busy(btn, async () => {
                const body = { to: inp.value.trim() };
                if (getContent) body.content = getContent();
                const res = await call('email/' + r.id + '/test', { body });
                toast(res.message);
                L._close();
            });
        };
        $('[data-yes]', L).addEventListener('click', go);
        inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(); });
        setTimeout(() => inp.focus(), 60);
    }

    function onKey(e) {
        const layers = $$('.nlk-layer.is-open');
        const top = layers[layers.length - 1];
        if (e.key === 'Escape' && top) {
            e.preventDefault();
            if (top._esc) top._esc(); else top._close();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && top && top._save) {
            e.preventDefault();
            top._save();
        }
    }

    function onFrameMessage(e) {
        const d = e.data || {};
        if (d.nlb && NLK._bld) NLK._bld.pick(d.nlb, true);
    }

    /* ============================================================== builder */

    const BLOCKS = {
        heading: { name: 'Heading', make: () => ({ text: 'Isang malinaw na pamagat', size: 'lg', align: 'left' }), sum: (b) => b.text },
        text: { name: 'Text', make: () => ({ html: '<p>Isulat dito ang gusto mong sabihin, kasama.</p>', align: 'left', size: 'md' }), sum: (b) => strip(b.html) },
        image: { name: 'Picture', make: () => ({ src: '', alt: '', link: '', caption: '', width: 'full' }), sum: (b) => b.caption || b.alt || (b.src ? 'A picture' : 'No picture yet') },
        video: { name: 'Video', make: () => ({ url: '', thumb: '', caption: '', label: 'Panoorin ang video' }), sum: (b) => b.url || 'No link yet' },
        button: { name: 'Button', make: () => ({ label: 'Buksan ang anee.io', url: 'https://anee.io/app', align: 'left', style: 'solid' }), sum: (b) => b.label },
        callout: { name: 'Callout', make: () => ({ title: 'Tip', html: '<p>Isang bagay na dapat tandaan.</p>', tone: 'gold' }), sum: (b) => b.title || strip(b.html) },
        anee: { name: 'Anee says', make: () => ({ face: 'happy', html: '<p>Nandito lang ako, kasama.</p>' }), sum: (b) => strip(b.html) },
        story: { name: 'Story card', make: () => ({ src: '', title: 'Pamagat ng kwento', html: '<p>Maikling kwento.</p>', source: '', url: '', label: 'Basahin' }), sum: (b) => b.title },
        pair: { name: 'Two cards', make: () => ({ items: [{ src: '', title: 'Una', html: '', url: '', label: '' }, { src: '', title: 'Pangalawa', html: '', url: '', label: '' }] }), sum: (b) => b.items.map((x) => x.title).filter(Boolean).join(' · ') },
        quote: { name: 'Quote', make: () => ({ html: 'Ang magaling na magsasaka, nagtatanong muna.', by: '' }), sum: (b) => strip(b.html) },
        list: { name: 'Checklist', make: () => ({ style: 'check', items: ['Unang hakbang', 'Pangalawang hakbang'] }), sum: (b) => b.items.length + ' lines' },
        divider: { name: 'Divider', make: () => ({ style: 'line' }), sum: (b) => b.style },
        spacer: { name: 'Space', make: () => ({ size: 20 }), sum: (b) => b.size + ' px' },
        social: { name: 'Social links', make: () => ({ title: '' }), sum: () => 'From the design' },
        html: { name: 'HTML', make: () => ({ html: '' }), sum: (b) => b.html ? strip(b.html).slice(0, 60) || 'HTML' : 'Empty' },
    };
    const ALIGN = [['left', 'Left'], ['center', 'Center']];
    const FORMS = {
        heading: [['text', 'text', 'Words'], ['seg', 'size', 'Size', [['lg', 'Big'], ['md', 'Medium']]], ['seg', 'align', 'Align', ALIGN]],
        text: [['rich', 'html', 'Words'], ['seg', 'size', 'Size', [['sm', 'Small'], ['md', 'Normal'], ['lg', 'Large']]], ['seg', 'align', 'Align', ALIGN]],
        image: [['img', 'src', 'Picture'], ['text', 'alt', 'Describe it', 'Shown when an inbox blocks pictures'], ['text', 'link', 'Opens this link when tapped', 'https://'], ['text', 'caption', 'Caption under it'], ['seg', 'width', 'Width', [['full', 'Full'], ['wide', 'Wide'], ['half', 'Half']]]],
        video: [['text', 'url', 'Video link', 'A YouTube or Facebook link, or any page'], ['img', 'thumb', 'Picture to show', 'A YouTube link fills it by itself'], ['text', 'label', 'Words on the bar'], ['text', 'caption', 'A line under them']],
        button: [['text', 'label', 'Words on the button'], ['text', 'url', 'Link', 'https://'], ['seg', 'style', 'Look', [['solid', 'Gold'], ['outline', 'Outline']]], ['seg', 'align', 'Align', ALIGN]],
        callout: [['seg', 'tone', 'Color', [['leaf', 'Green'], ['gold', 'Gold'], ['alert', 'Red']]], ['text', 'title', 'Title'], ['rich', 'html', 'Words']],
        anee: [['face', 'face', 'Her face'], ['rich', 'html', 'What she says']],
        story: [['img', 'src', 'Picture'], ['text', 'source', 'Small label on top', 'A source or a place'], ['text', 'title', 'Title'], ['rich', 'html', 'Words'], ['text', 'url', 'Link', 'https://'], ['text', 'label', 'Link words']],
        pair: [['pair']],
        quote: [['rich1', 'html', 'The quote'], ['text', 'by', 'Who said it']],
        list: [['seg', 'style', 'Marks', [['check', 'Checks'], ['number', 'Numbers'], ['dot', 'Dots']]], ['items', 'items', 'Lines']],
        divider: [['seg', 'style', 'Look', [['line', 'Line'], ['dots', 'Dots'], ['leaf', 'Leaf']]]],
        spacer: [['range', 'size', 'Height', 8, 64]],
        social: [['text', 'title', 'A line above them', 'Optional'], ['note', 'The links come from the Design: Facebook, YouTube, TikTok and the website.']],
        html: [['code', 'html', 'HTML', 'For experts. Scripts and forms are taken out.']],
    };

    function newContent() {
        return { kicker: '', headline: '', face: 'happy', hero: { src: '', alt: '', link: '' }, blocks: [Object.assign({ id: uid(), type: 'text' }, BLOCKS.text.make())] };
    }

    async function openBuilder(id, priority) {
        let email;
        if (id) {
            try { email = (await call('email/' + id)).data.email; } catch (e) { toast(e.message, true); return; }
        } else {
            const today = S.boot ? S.boot.today : '';
            email = { id: null, priority: priority || 'regular', kind: 'builder', title: '', subject: '', preheader: '', audience: ['all'], sendOn: null, sendAt: null, newsDate: priority === 'news' ? today : null, status: 'draft', note: '', content: newContent() };
        }
        Builder(email);
    }

    function Builder(email) {
        const E = clone(email);
        if (!E.content || !Array.isArray(E.content.blocks)) E.content = newContent();
        E.content.hero = E.content.hero || { src: '', alt: '', link: '' };
        E.content.blocks.forEach((b) => { if (!b.id) b.id = uid(); });
        let dirty = false;
        let sel = null;
        let side = 'blocks';
        let phone = false;
        let textView = false;
        let scheme = appDark() ? 'dark' : 'light';
        const isAcumba = E.kind === 'acumba';
        const locked = !!(E.sendAt && E.status === 'sent');

        const L = layer('is-full', '<div class="nlk-bld" role="dialog" aria-modal="true">' +
            '<div class="nlk-bld-top">' +
            '  <button type="button" class="nlk-btn is-icon" data-back title="Back">' + ic('back') + '</button>' +
            '  <div class="nlk-bld-title"><input data-title placeholder="Name this email (only you see it)" value="' + h(E.title) + '"><small data-sub></small></div>' +
            '  <div class="nlk-seg nlk-mob-switch" data-mob><button type="button" data-v="edit" class="is-on">Edit</button><button type="button" data-v="view">Preview</button></div>' +
            '  <label class="nlk-line" style="gap:8px;font-weight:800;font-size:13px" title="In line: the scheduler can send it. Draft: kept out."><button type="button" class="nlk-switch' + (['queued', 'partial'].indexOf(E.status) >= 0 ? ' is-on' : '') + '" data-inline></button><span class="nlk-hide-sm" data-inline-words></span></label>' +
            '  <button type="button" class="nlk-btn" data-test>' + ic('send') + '<span class="nlk-hide-sm">Test</span></button>' +
            '  <button type="button" class="nlk-btn is-gold" data-save>Save</button>' +
            '</div>' +
            '<div class="nlk-bld-body" data-show="edit">' +
            '  <aside class="nlk-bld-side">' +
            '    <div class="nlk-side-tabs"><button type="button" data-side="blocks" class="is-on">' + (isAcumba ? 'The email' : 'Blocks') + '</button><button type="button" data-side="settings">Who and when</button></div>' +
            '    <div class="nlk-side-pane" data-pane="blocks"></div>' +
            '    <div class="nlk-side-pane nlk-hide" data-pane="settings"></div>' +
            '  </aside>' +
            '  <main class="nlk-bld-view">' +
            '    <div class="nlk-view-bar"><span class="nlk-grow" data-viewnote>Live preview: tap a part of the email to edit it.</span>' +
            '      <div class="nlk-seg" data-dev><button type="button" data-v="desk" class="is-on" title="Desktop">' + ic('desk', '') + '</button><button type="button" data-v="phone" title="Phone">' + ic('phone') + '</button></div>' +
            '      <div class="nlk-seg" data-sch><button type="button" data-v="light"' + (scheme === 'light' ? ' class="is-on"' : '') + ' title="A light inbox">' + ic('sun') + '</button><button type="button" data-v="dark"' + (scheme === 'dark' ? ' class="is-on"' : '') + ' title="A dark inbox (phones in dark mode)">' + ic('moon') + '</button></div>' +
            '      <div class="nlk-seg" data-tv><button type="button" data-v="html" class="is-on">Email</button><button type="button" data-v="text">Text</button></div>' +
            '    </div>' +
            '    <div class="nlk-frame-wrap" data-wrap><iframe class="nlk-frame" title="Email preview" data-frame></iframe></div>' +
            '    <pre class="nlk-textv nlk-hide" data-textv></pre>' +
            '  </main>' +
            '</div></div>');

        $$('[data-dev] svg, [data-sch] svg', L).forEach((s) => { s.style.width = '16px'; s.style.height = '16px'; });
        const pane = $('[data-pane="blocks"]', L);
        const setPane = $('[data-pane="settings"]', L);
        const frame = $('[data-frame]', L);

        const mark = () => { dirty = true; $('[data-save]', L).textContent = 'Save'; $('[data-save]', L).classList.add('is-gold'); };
        const sub = () => {
            const s = $('[data-sub]', L);
            s.textContent = PRI[E.priority].name + ' · ' + STATUS[E.status] + (E.id ? '' : ' · not saved yet') + (locked ? ' · with Acumbamail for its time, locked' : '');
            const live = $('[data-inline]', L).classList.contains('is-on');
            $('[data-inline-words]', L).textContent = live ? 'In line' : 'Draft';
        };
        sub();

        /* ------------------------------------------------ preview */
        let pv = 0;
        const preview = debounce(async () => {
            if (isAcumba) return;
            const my = ++pv;
            try {
                const r = await call('preview', { body: { email: { subject: E.subject, preheader: E.preheader, content: E.content }, withText: textView, scheme } });
                if (my !== pv) return;
                if (textView) { $('[data-textv]', L).textContent = r.data.text || ''; return; }
                let y = 0;
                try { y = frame.contentWindow ? frame.contentWindow.scrollY : 0; } catch (e) { y = 0; }
                frame.onload = () => {
                    try { frame.contentWindow.scrollTo(0, y); } catch (e) { /* cross origin */ }
                    if (sel) frame.contentWindow.postMessage({ nlbOn: sel, scroll: false }, '*');
                };
                frame.srcdoc = r.data.html;
            } catch (e) {
                $('[data-viewnote]', L).textContent = e.message;
            }
        }, 450);

        /* ------------------------------------------------ blocks pane */
        function paintBlocks() {
            if (isAcumba) {
                pane.innerHTML = '<div class="nlk-acumba" style="margin:0"><b>An Acumbamail template</b><p>Its words were written and live in Acumbamail (' + h(E.acumbaName || ('template ' + E.acumbaTemplateId)) + '). Acumbamail sends it by its id; anee.io cannot read or change its words.</p><p>Change the words in Acumbamail. Here you set its subject, who gets it and when, and send a test.</p>' + (E.note ? '<div class="nlk-row-note">' + h(E.note) + '</div>' : '') + '</div>';
                $('[data-wrap]', L).innerHTML = '<div class="nlk-acumba"><b>No preview here</b><p>Acumbamail keeps this email\'s words. Send a test to see it in an inbox: the test goes through Acumbamail to a list that holds only your address.</p><button type="button" class="nlk-btn is-gold" data-test2>' + ic('send') + 'Send a test</button></div>';
                $('[data-test2]', L).addEventListener('click', () => testDialog(E));
                $('[data-dev]', L).classList.add('nlk-hide');
                $('[data-sch]', L).classList.add('nlk-hide');
                $('[data-tv]', L).classList.add('nlk-hide');
                $('[data-viewnote]', L).textContent = 'Acumbamail template #' + E.acumbaTemplateId;
                return;
            }
            const c = E.content;
            pane.innerHTML =
                '<div class="nlk-head-card" data-headcard><b>The top of the email</b>' +
                '  <label class="nlk-field"><span>Small line above the title</span><input class="nlk-in" data-h="kicker" value="' + h(c.kicker) + '" placeholder="Bago sa anee.io"></label>' +
                '  <label class="nlk-field"><span>Title</span><input class="nlk-in" data-h="headline" value="' + h(c.headline) + '" placeholder="Ang malaking balita"></label>' +
                '  <div class="nlk-field"><span>Anee\'s face beside the title</span><div class="nlk-faces" data-hface></div></div>' +
                '  <div class="nlk-field"><span>Wide photo under the top <em>(optional)</em></span><div data-hhero></div></div>' +
                '</div>' +
                '<div class="nlk-box" style="padding:10px"><h4>Add a block (tap, or drag it into place)</h4><div class="nlk-palette" data-palette>' +
                Object.keys(BLOCKS).map((t) => '<div class="nlk-pal" data-type="' + t + '" title="' + h(BLOCKS[t].name) + '">' + ic(t) + '<span>' + h(BLOCKS[t].name) + '</span></div>').join('') +
                '</div></div>' +
                '<div class="nlk-blocks" data-blocks></div>';
            $$('[data-h]', pane).forEach((i) => i.addEventListener('input', () => { c[i.dataset.h] = i.value; mark(); preview(); }));
            faces($('[data-hface]', pane), c.face, true, (f) => { c.face = f; mark(); preview(); });
            const heroBox = $('[data-hhero]', pane);
            heroBox.appendChild(imgPicker(c.hero.src, (u) => { c.hero.src = u; mark(); preview(); }));
            const hx = el('<div class="nlk-row2" style="margin-top:6px"><input class="nlk-in" placeholder="Describe the photo" value="' + h(c.hero.alt) + '"><input class="nlk-in" placeholder="Link when tapped (optional)" value="' + h(c.hero.link) + '"></div>');
            hx.children[0].addEventListener('input', (e) => { c.hero.alt = e.target.value; mark(); preview(); });
            hx.children[1].addEventListener('input', (e) => { c.hero.link = e.target.value; mark(); preview(); });
            heroBox.appendChild(hx);

            $$('.nlk-pal', pane).forEach((p) => p.addEventListener('click', () => addBlock(p.dataset.type)));
            paintList2();
            loadSortable().then((Sortable) => {
                Sortable.create($('[data-palette]', pane), { group: { name: 'nlb', pull: 'clone', put: false }, sort: false, animation: 180, forceFallback: true, fallbackTolerance: 4 });
                Sortable.create($('[data-blocks]', pane), {
                    group: { name: 'nlb', pull: false, put: true }, handle: '.nlk-grip', animation: 220, easing: 'cubic-bezier(.22,1,.36,1)', ghostClass: 'nlk-ghost',
                    forceFallback: true, scroll: true, bubbleScroll: true,
                    onAdd: (ev) => { const t = ev.item.dataset.type; const at = ev.newIndex; ev.item.remove(); addBlock(t, at); },
                    onUpdate: () => {
                        const order = $$('.nlk-b', $('[data-blocks]', pane)).map((x) => x.dataset.bid);
                        c.blocks.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
                        mark(); preview();
                    },
                });
            }).catch((e) => toast(e.message, true));
        }

        function paintList2() {
            const box = $('[data-blocks]', pane);
            box.innerHTML = '';
            E.content.blocks.forEach((b) => box.appendChild(blockCard(b)));
        }

        function addBlock(type, at) {
            const b = Object.assign({ id: uid(), type }, BLOCKS[type].make());
            const list = E.content.blocks;
            let idx = typeof at === 'number' ? at : list.length;
            if (typeof at !== 'number' && sel) {
                const i = list.findIndex((x) => x.id === sel);
                if (i >= 0) idx = i + 1;
            }
            list.splice(idx, 0, b);
            paintList2();
            mark();
            pick(b.id, false, true);
            preview();
        }

        function blockCard(b) {
            const card = el('<div class="nlk-b" data-bid="' + b.id + '">' +
                '<div class="nlk-b-head"><span class="nlk-grip" title="Drag to move">' + ic('grip') + '</span><span class="nlk-b-ic">' + ic(b.type) + '</span>' +
                '<span class="nlk-b-name"><b>' + h(BLOCKS[b.type].name) + '</b><small data-sum>' + h(BLOCKS[b.type].sum(b) || '') + '</small></span>' +
                '<button type="button" class="nlk-btn is-sm is-icon is-ghost" data-dup title="Duplicate">' + ic('copy') + '</button>' +
                '<button type="button" class="nlk-btn is-sm is-icon is-ghost is-danger" data-del title="Remove">' + ic('trash') + '</button></div>' +
                '<div class="nlk-b-form"><div><div data-form></div></div></div></div>');
            const refreshSum = () => { $('[data-sum]', card).textContent = BLOCKS[b.type].sum(b) || ''; };
            const changed = () => { refreshSum(); mark(); preview(); };
            buildForm($('[data-form]', card), b, changed);
            $('.nlk-b-head', card).addEventListener('click', (e) => { if (e.target.closest('button') || e.target.closest('.nlk-grip')) return; pick(b.id, false, true, true); });
            $('[data-dup]', card).addEventListener('click', () => {
                const copy = Object.assign(clone(b), { id: uid() });
                const list = E.content.blocks;
                list.splice(list.indexOf(b) + 1, 0, copy);
                paintList2(); mark(); pick(copy.id, false, true); preview();
            });
            $('[data-del]', card).addEventListener('click', () => {
                const list = E.content.blocks;
                const i = list.indexOf(b);
                card.style.transition = 'opacity .2s, transform .2s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-8px)';
                setTimeout(() => {
                    list.splice(i, 1);
                    if (sel === b.id) sel = null;
                    paintList2(); mark(); preview();
                    toast('Block removed.');
                }, 180);
            });
            return card;
        }

        function pick(id, fromFrame, open, toggle) {
            if (id === 'head' || id === 'hero') {
                sel = null;
                $$('.nlk-b', pane).forEach((x) => x.classList.remove('is-on', 'is-open'));
                const hc = $('[data-headcard]', pane);
                if (hc) hc.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (side !== 'blocks') showSide('blocks');
                showMob('edit');
                return;
            }
            const card = $('.nlk-b[data-bid="' + id + '"]', pane);
            if (!card) return;
            const wasOpen = card.classList.contains('is-open');
            $$('.nlk-b', pane).forEach((x) => { if (x !== card) x.classList.remove('is-on', 'is-open'); });
            card.classList.add('is-on');
            if (toggle && wasOpen && sel === id) card.classList.remove('is-open'); else if (open !== false) card.classList.add('is-open');
            sel = id;
            try { frame.contentWindow.postMessage({ nlbOn: id, scroll: !fromFrame }, '*'); } catch (e) { /* not loaded */ }
            if (fromFrame) {
                if (side !== 'blocks') showSide('blocks');
                showMob('edit');
                setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 60);
            }
        }

        /* ------------------------------------------------ forms */
        function buildForm(box, b, changed) {
            const grid = el('<div style="display:grid;gap:10px"></div>');
            (FORMS[b.type] || []).forEach((f) => {
                const [kind, key, label, extra] = f;
                if (kind === 'text') {
                    const w = el('<label class="nlk-field"><span>' + h(label) + '</span><input class="nlk-in" value="' + h(b[key] || '') + '" placeholder="' + h(extra || '') + '"></label>');
                    $('input', w).addEventListener('input', (e) => { b[key] = e.target.value; changed(); });
                    grid.appendChild(w);
                } else if (kind === 'seg') {
                    const w = el('<div class="nlk-field"><span>' + h(label) + '</span><div class="nlk-seg">' + extra.map(([v, t]) => '<button type="button" data-v="' + v + '"' + (b[key] === v ? ' class="is-on"' : '') + '>' + h(t) + '</button>').join('') + '</div></div>');
                    $$('button', w).forEach((x) => x.addEventListener('click', () => { b[key] = x.dataset.v; $$('button', w).forEach((y) => y.classList.toggle('is-on', y === x)); changed(); }));
                    grid.appendChild(w);
                } else if (kind === 'rich' || kind === 'rich1') {
                    const w = el('<div class="nlk-field"><span>' + h(label) + '</span></div>');
                    w.appendChild(richEditor(b[key] || '', kind === 'rich1', (v) => { b[key] = v; changed(); }));
                    grid.appendChild(w);
                } else if (kind === 'img') {
                    const w = el('<div class="nlk-field"><span>' + h(label) + (extra ? ' <em>(' + h(extra) + ')</em>' : '') + '</span></div>');
                    w.appendChild(imgPicker(b[key] || '', (u) => { b[key] = u; changed(); }));
                    grid.appendChild(w);
                } else if (kind === 'face') {
                    const w = el('<div class="nlk-field"><span>' + h(label) + '</span><div class="nlk-faces"></div></div>');
                    faces($('.nlk-faces', w), b[key], false, (v) => { b[key] = v; changed(); });
                    grid.appendChild(w);
                } else if (kind === 'range') {
                    const w = el('<label class="nlk-field"><span>' + h(label) + ': <em data-rv>' + b[key] + ' px</em></span><input type="range" min="' + extra + '" max="' + f[4] + '" value="' + b[key] + '"></label>');
                    $('input', w).addEventListener('input', (e) => { b[key] = +e.target.value; $('[data-rv]', w).textContent = b[key] + ' px'; changed(); });
                    grid.appendChild(w);
                } else if (kind === 'code') {
                    const w = el('<label class="nlk-field"><span>' + h(label) + '</span><textarea class="nlk-in" rows="8" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12.5px" placeholder="' + h(extra || '') + '"></textarea></label>');
                    $('textarea', w).value = b[key] || '';
                    $('textarea', w).addEventListener('input', (e) => { b[key] = e.target.value; changed(); });
                    grid.appendChild(w);
                } else if (kind === 'note') {
                    grid.appendChild(el('<p class="nlk-hint" style="margin:0">' + h(key) + '</p>'));
                } else if (kind === 'items') {
                    grid.appendChild(itemsEditor(b, changed));
                } else if (kind === 'pair') {
                    b.items.forEach((it, i) => {
                        const w = el('<div class="nlk-box" style="padding:10px;gap:8px"><h4>' + (i ? 'Right card' : 'Left card') + '</h4></div>');
                        w.appendChild(imgPicker(it.src || '', (u) => { it.src = u; changed(); }));
                        const t = el('<input class="nlk-in" placeholder="Title" value="' + h(it.title || '') + '">');
                        t.addEventListener('input', (e) => { it.title = e.target.value; changed(); });
                        w.appendChild(t);
                        w.appendChild(richEditor(it.html || '', true, (v) => { it.html = v; changed(); }));
                        const l = el('<div class="nlk-row2"><input class="nlk-in" placeholder="Link" value="' + h(it.url || '') + '"><input class="nlk-in" placeholder="Link words" value="' + h(it.label || '') + '"></div>');
                        l.children[0].addEventListener('input', (e) => { it.url = e.target.value; changed(); });
                        l.children[1].addEventListener('input', (e) => { it.label = e.target.value; changed(); });
                        w.appendChild(l);
                        grid.appendChild(w);
                    });
                }
            });
            box.appendChild(grid);
        }

        function itemsEditor(b, changed) {
            const w = el('<div class="nlk-field"><span>Lines</span><div class="nlk-items"></div><button type="button" class="nlk-btn is-sm" style="justify-self:start">' + ic('plus') + 'Add a line</button></div>');
            const box = $('.nlk-items', w);
            const paint = (focusAt) => {
                box.innerHTML = '';
                b.items.forEach((txt, i) => {
                    const row = el('<div class="nlk-line"><div class="nlk-grow"></div><button type="button" class="nlk-btn is-sm is-icon is-ghost is-danger" title="Remove">' + ic('close') + '</button></div>');
                    const ed = richEditor(txt, true, (v) => { b.items[i] = v; changed(); }, () => { b.items.splice(i + 1, 0, ''); paint(i + 1); changed(); });
                    $('.nlk-grow', row).appendChild(ed);
                    $('button', row).addEventListener('click', () => { b.items.splice(i, 1); paint(); changed(); });
                    box.appendChild(row);
                    if (focusAt === i) setTimeout(() => $('.nlk-rt-ed', ed).focus(), 30);
                });
            };
            paint();
            w.lastElementChild.addEventListener('click', () => { b.items.push(''); paint(b.items.length - 1); changed(); });
            return w;
        }

        /* ------------------------------------------------ settings pane */
        function paintSettings() {
            const segs = S.boot.segments;
            const tiers = segs.filter((s) => s.group === 'tier');
            const flags = segs.filter((s) => s.group === 'flag');
            const aud = E.audience || ['all'];
            const isAll = aud.indexOf('all') >= 0;
            const chip = (s, on) => '<button type="button" class="nlk-chipb' + (on ? ' is-on' : '') + '" data-seg="' + s.key + '" title="' + h(s.line) + '">' + dot(s.key) + h(s.name) + ' <small>' + s.count + '</small></button>';
            setPane.innerHTML =
                '<div class="nlk-box"><h4>The inbox line</h4>' +
                '  <label class="nlk-field"><span>Subject <em data-cnt></em></span><input class="nlk-in" data-f="subject" value="' + h(E.subject) + '" placeholder="What the inbox shows first"></label>' +
                '  <label class="nlk-field"><span>Preview line <em>(the grey words after the subject)</em></span><input class="nlk-in" data-f="preheader" value="' + h(E.preheader || '') + '" placeholder="One more sentence to make them open it"></label>' +
                '</div>' +
                '<div class="nlk-box"><h4>Priority</h4><div class="nlk-seg" data-pri style="justify-self:start">' + ['high', 'news', 'regular'].map((p) => '<button type="button" data-v="' + p + '"' + (E.priority === p ? ' class="is-on"' : '') + '>' + PRI[p].short + '</button>').join('') + '</div><small class="nlk-hint" style="margin:0" data-prihint></small></div>' +
                '<div class="nlk-box"><h4>Who gets it</h4>' +
                '  <div class="nlk-chips">' + chip(segs.find((s) => s.key === 'all'), isAll) + '</div>' +
                '  <div class="nlk-field"><span>Or by plan</span><div class="nlk-chips">' + tiers.map((s) => chip(s, !isAll && aud.indexOf(s.key) >= 0)).join('') + '</div></div>' +
                '  <div class="nlk-field"><span>Or by what they bought</span><div class="nlk-chips">' + flags.map((s) => chip(s, !isAll && aud.indexOf(s.key) >= 0)).join('') + '</div><small>An email for these goes on a day when every plan group is free, since a buyer can be on any plan.</small></div>' +
                '</div>' +
                '<div class="nlk-box" data-when><h4>When</h4></div>' +
                '<div class="nlk-box"><h4>A note for the team</h4><textarea class="nlk-in" data-f="note" rows="3" placeholder="Only you see this">' + h(E.note || '') + '</textarea></div>' +
                (E.id && E.campaigns && E.campaigns.length ? '<div class="nlk-box"><h4>Campaigns made</h4><div class="nlk-camps">' + E.campaigns.map((c) => '<div><span class="nlk-pill ' + (c.status === 'created' ? 'st-queued' : 'st-expired') + '">' + (c.status === 'created' ? 'Made' : 'Failed') + '</span><b>' + h(dayWords(c.at, true)) + '</b> ' + h(c.cells) + (c.campaignId ? ' · Acumbamail #' + c.campaignId : '') + (c.error ? ' · ' + h(c.error) : '') + '</div>').join('') + '</div></div>' : '') +
                (E.id ? '<div class="nlk-box"><h4>Remove</h4><button type="button" class="nlk-btn is-danger" data-del style="justify-self:start">' + ic('trash') + 'Remove this email</button></div>' : '');

            const cnt = () => { $('[data-cnt]', setPane).textContent = '(' + (E.subject || '').length + ' letters' + ((E.subject || '').length > 60 ? ', long for a phone' : '') + ')'; };
            cnt();
            $$('[data-f]', setPane).forEach((i) => i.addEventListener('input', () => { E[i.dataset.f] = i.value; if (i.dataset.f === 'subject') cnt(); mark(); if (i.dataset.f !== 'note') preview(); }));
            const priHint = () => { $('[data-prihint]', setPane).textContent = PRI[E.priority].line + '.'; };
            priHint();
            $$('[data-pri] button', setPane).forEach((b) => b.addEventListener('click', () => {
                E.priority = b.dataset.v;
                $$('[data-pri] button', setPane).forEach((x) => x.classList.toggle('is-on', x === b));
                if (E.priority === 'news' && !E.newsDate) E.newsDate = S.boot.today;
                priHint(); paintWhen(); sub(); mark();
            }));
            $$('[data-seg]', setPane).forEach((b) => b.addEventListener('click', () => {
                const k = b.dataset.seg;
                let a = (E.audience || []).filter((x) => x !== 'all');
                if (k === 'all') a = ['all'];
                else if (k === 'paid' || k === 'credits') a = a.indexOf(k) >= 0 ? [] : [k];
                else { a = a.filter((x) => x !== 'paid' && x !== 'credits'); a = a.indexOf(k) >= 0 ? a.filter((x) => x !== k) : a.concat(k); }
                if (!a.length || TIERS.every((t) => a.indexOf(t) >= 0)) a = ['all'];
                E.audience = a;
                $$('[data-seg]', setPane).forEach((x) => x.classList.toggle('is-on', a.indexOf(x.dataset.seg) >= 0));
                mark();
            }));
            if ($('[data-del]', setPane)) $('[data-del]', setPane).addEventListener('click', async () => {
                if (!(await confirmBox('Remove this email?', 'It leaves the line. Campaigns already made at Acumbamail stay there.', 'Remove', true))) return;
                try { await call('email/' + E.id + '/delete', { body: {} }); dirty = false; toast('Removed.'); close(true); } catch (e) { toast(e.message, true); }
            });
            paintWhen();
        }

        function paintWhen() {
            const box = $('[data-when]', setPane);
            const today = S.boot.today;
            let html = '<h4>When</h4>';
            if (E.priority === 'news') {
                html += '<label class="nlk-field"><span>The news of</span><input type="date" class="nlk-in" data-w="newsDate" value="' + h(E.newsDate || today) + '"></label><small>News goes only on its own day and is gone after it, even if a high priority email took the day.</small>';
            } else if (E.priority === 'regular') {
                html += '<label class="nlk-line" style="font-weight:800;font-size:13.5px"><button type="button" class="nlk-switch' + (E.sendOn ? ' is-on' : '') + '" data-day></button><span>Only on a certain day</span></label>' +
                    '<label class="nlk-field' + (E.sendOn ? '' : ' nlk-hide') + '" data-dayf><span>The day</span><input type="date" class="nlk-in" data-w="sendOn" min="' + today + '" value="' + h(E.sendOn || '') + '"></label>' +
                    '<small>Without a day, it waits its turn in the line. With one, it goes that day ahead of the line (a holiday greeting), and is gone if the day passes.</small>';
            } else {
                const mode = E.sendAt ? 'at' : (E.sendOn ? 'on' : 'next');
                html += '<div class="nlk-seg" data-mode style="justify-self:start;flex-wrap:wrap">' +
                    [['next', 'Next daily send'], ['on', 'On a day'], ['at', 'At an exact time']].map(([v, t]) => '<button type="button" data-v="' + v + '"' + (mode === v ? ' class="is-on"' : '') + '>' + t + '</button>').join('') + '</div>' +
                    '<label class="nlk-field' + (mode === 'on' ? '' : ' nlk-hide') + '" data-mf="on"><span>The day</span><input type="date" class="nlk-in" data-w="sendOn" min="' + today + '" value="' + h(E.sendOn || '') + '"></label>' +
                    '<label class="nlk-field' + (mode === 'at' ? '' : ' nlk-hide') + '" data-mf="at"><span>Date and time (Philippine time)</span><input type="datetime-local" class="nlk-in" data-w="sendAt" value="' + h(E.sendAt ? E.sendAt.replace(' ', 'T').slice(0, 16) : '') + '"></label>' +
                    '<small data-modehint></small>';
            }
            box.innerHTML = html;
            $$('[data-w]', box).forEach((i) => i.addEventListener('change', () => { E[i.dataset.w] = i.value ? i.value.replace('T', ' ') : null; mark(); }));
            const day = $('[data-day]', box);
            if (day) day.addEventListener('click', () => {
                const on = !day.classList.contains('is-on');
                day.classList.toggle('is-on', on);
                $('[data-dayf]', box).classList.toggle('nlk-hide', !on);
                if (!on) E.sendOn = null;
                mark();
            });
            const hint = $('[data-modehint]', box);
            const say = (m) => {
                if (!hint) return;
                hint.textContent = { next: 'Goes on the next daily send (' + S.boot.plan.sendAt + '), ahead of everything else.', on: 'Goes with that day\'s daily send, ahead of everything else, and is gone if the day passes.', at: 'Goes at that exact time as its own campaign, beside the day\'s email: the one case where someone gets two emails in a day.' }[m];
            };
            say(E.sendAt ? 'at' : (E.sendOn ? 'on' : 'next'));
            $$('[data-mode] button', box).forEach((b) => b.addEventListener('click', () => {
                const m = b.dataset.v;
                $$('[data-mode] button', box).forEach((x) => x.classList.toggle('is-on', x === b));
                $$('[data-mf]', box).forEach((f) => f.classList.toggle('nlk-hide', f.dataset.mf !== m));
                if (m !== 'on') E.sendOn = null;
                if (m !== 'at') E.sendAt = null;
                if (m === 'on') E.sendOn = $('[data-mf="on"] input', box).value || null;
                if (m === 'at') { const v = $('[data-mf="at"] input', box).value; E.sendAt = v ? v.replace('T', ' ') : null; }
                say(m);
                mark();
            }));
        }

        /* ------------------------------------------------ chrome */
        function showSide(which) {
            side = which;
            $$('[data-side]', L).forEach((b) => b.classList.toggle('is-on', b.dataset.side === which));
            pane.classList.toggle('nlk-hide', which !== 'blocks');
            setPane.classList.toggle('nlk-hide', which !== 'settings');
        }
        function showMob(which) {
            $('.nlk-bld-body', L).dataset.show = which;
            $$('[data-mob] button', L).forEach((b) => b.classList.toggle('is-on', b.dataset.v === which));
        }
        $$('[data-side]', L).forEach((b) => b.addEventListener('click', () => showSide(b.dataset.side)));
        $$('[data-mob] button', L).forEach((b) => b.addEventListener('click', () => showMob(b.dataset.v)));
        $$('[data-dev] button', L).forEach((b) => b.addEventListener('click', () => {
            phone = b.dataset.v === 'phone';
            $$('[data-dev] button', L).forEach((x) => x.classList.toggle('is-on', x === b));
            $('[data-wrap]', L).classList.toggle('is-phone', phone);
        }));
        $$('[data-sch] button', L).forEach((b) => b.addEventListener('click', () => {
            scheme = b.dataset.v;
            $$('[data-sch] button', L).forEach((x) => x.classList.toggle('is-on', x === b));
            preview();
        }));
        $$('[data-tv] button', L).forEach((b) => b.addEventListener('click', () => {
            textView = b.dataset.v === 'text';
            $$('[data-tv] button', L).forEach((x) => x.classList.toggle('is-on', x === b));
            $('[data-wrap]', L).classList.toggle('nlk-hide', textView);
            $('[data-textv]', L).classList.toggle('nlk-hide', !textView);
            preview();
        }));
        $('[data-title]', L).addEventListener('input', (e) => { E.title = e.target.value; mark(); });
        $('[data-inline]', L).addEventListener('click', (e) => { e.currentTarget.classList.toggle('is-on'); sub(); mark(); });

        async function save(silent) {
            if (locked) { toast('This one is already with Acumbamail for its time, so it can no longer change.', true); return false; }
            const btn = $('[data-save]', L);
            const status = $('[data-inline]', L).classList.contains('is-on') ? 'queued' : 'draft';
            const body = { email: Object.assign({}, E, { status, content: isAcumba ? undefined : E.content, campaigns: undefined }) };
            const ok = await busy(btn, async () => {
                const r = await call('email', { body });
                const fresh = r.data.email;
                E.id = fresh.id; E.status = fresh.status; E.position = fresh.position; E.sentCells = fresh.sentCells;
                dirty = false;
                btn.textContent = 'Saved';
                btn.classList.remove('is-gold');
                sub();
                if (!silent) toast(r.message);
                refresh(false);
                if (S.tab === E.priority || !silent) loadList();
                return true;
            });
            return !!ok;
        }
        L._save = () => save(false);
        $('[data-save]', L).addEventListener('click', () => save(false));
        $('[data-test]', L).addEventListener('click', async () => {
            if (!E.id || dirty) { if (!(await save(true))) return; }
            testDialog(E, isAcumba ? null : () => E.content);
        });

        async function close(force) {
            if (!force && dirty && !(await confirmBox('Leave without saving?', 'Your changes to this email will be lost.', 'Leave', true))) return;
            NLK._bld = null;
            L._close();
        }
        $('[data-back]', L).addEventListener('click', () => close(false));
        L._esc = () => close(false);

        NLK._bld = { pick: (id, fromFrame) => pick(id, fromFrame, true) };
        paintBlocks();
        paintSettings();
        if (isAcumba) showSide('settings');
        if (locked) toast('This one is already with Acumbamail for its time: you can look, not change.', true);
        preview();
    }

    /* ------------------------------------------------------- shared inputs */

    function richEditor(html, inline, onChange, onEnter) {
        const w = el('<div class="nlk-rt"><div class="nlk-rt-bar">' +
            '<button type="button" data-c="bold" title="Bold"><b>B</b></button>' +
            '<button type="button" data-c="italic" title="Italic"><i>I</i></button>' +
            '<button type="button" data-c="underline" title="Underline"><u>U</u></button>' +
            '<button type="button" data-c="link" title="Link">' + ic('link') + '</button>' +
            (inline ? '' : '<button type="button" data-c="insertUnorderedList" title="Bullets">&bull;</button><button type="button" data-c="insertOrderedList" title="Numbers">1.</button>') +
            '<button type="button" data-c="removeFormat" title="Plain">&times;</button>' +
            '</div><div class="nlk-rt-ed' + (inline ? ' is-one' : '') + '" contenteditable="true" data-ph="Type here"></div></div>');
        const ed = $('.nlk-rt-ed', w);
        ed.innerHTML = html || '';
        const emit = () => {
            let v = ed.innerHTML.replace(/<span[^>]*>|<\/span>/gi, '').replace(/<(\/?)div>/gi, '<$1p>').replace(/&nbsp;/g, ' ');
            if (inline) v = v.replace(/<\/?p>/gi, ' ').replace(/<br\s*\/?>\s*$/i, '').trim();
            if (!strip(v)) v = '';
            onChange(v);
        };
        ed.addEventListener('input', emit);
        ed.addEventListener('paste', (e) => {
            e.preventDefault();
            const t = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, t);
        });
        ed.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && inline) { e.preventDefault(); if (onEnter) onEnter(); }
        });
        $$('[data-c]', w).forEach((b) => {
            b.addEventListener('mousedown', (e) => e.preventDefault());
            b.addEventListener('click', async () => {
                ed.focus();
                const c = b.dataset.c;
                if (c === 'link') {
                    const range = window.getSelection().rangeCount ? window.getSelection().getRangeAt(0).cloneRange() : null;
                    const url = await askText('Add a link', 'The address', 'https://', 'https://anee.io/...');
                    ed.focus();
                    if (range) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(range); }
                    if (url) {
                        if (range && range.collapsed) document.execCommand('insertHTML', false, '<a href="' + h(url) + '">' + h(url) + '</a>');
                        else document.execCommand('createLink', false, url);
                    }
                } else {
                    document.execCommand(c, false, null);
                }
                emit();
            });
        });
        return w;
    }

    function imgPicker(value, onChange) {
        const w = el('<div class="nlk-imgpick"><span class="nlk-thumb"></span><input class="nlk-in" placeholder="Paste a picture link, or upload" style="flex:1;min-width:0"><button type="button" class="nlk-btn is-sm" title="Upload a picture">' + ic('upload') + '<span>Upload</span></button><input type="file" accept="image/jpeg,image/png,image/gif" hidden></div>');
        const thumb = $('.nlk-thumb', w);
        const inp = $('.nlk-in', w);
        const file = $('input[type=file]', w);
        const set = (u, emit) => { inp.value = u || ''; thumb.style.backgroundImage = u ? 'url("' + String(u).replace(/"/g, '%22') + '")' : ''; if (emit) onChange(u || ''); };
        set(value, false);
        inp.addEventListener('input', debounce(() => set(inp.value.trim(), true), 350));
        $('button', w).addEventListener('click', () => file.click());
        file.addEventListener('change', async () => {
            const f = file.files[0];
            if (!f) return;
            const fd = new FormData();
            fd.append('file', f);
            await busy($('button', w), async () => {
                const r = await call('upload', { form: fd });
                set(r.data.url, true);
                toast('Picture uploaded.');
            });
            file.value = '';
        });
        return w;
    }

    function faces(box, value, allowNone, onPick) {
        const list = (S.boot && S.boot.faces) || ['happy'];
        const base = (S.boot && S.boot.faceBase) || 'https://anee.io/images/anee/emoji/';
        box.innerHTML = (allowNone ? '<button type="button" class="nlk-face is-none' + (!value ? ' is-on' : '') + '" data-f="">None</button>' : '') +
            list.map((f) => '<button type="button" class="nlk-face' + (value === f ? ' is-on' : '') + '" data-f="' + f + '" title="' + f + '"><img src="' + base + f + '.png" alt="' + f + '" loading="lazy"></button>').join('');
        $$('.nlk-face', box).forEach((b) => b.addEventListener('click', () => {
            $$('.nlk-face', box).forEach((x) => x.classList.toggle('is-on', x === b));
            onPick(b.dataset.f);
        }));
    }

    /* ================================================================ theme */

    const SAMPLE = {
        kicker: 'Kwentong bukid', headline: 'Ganito ang itsura ng bawat email', face: 'happy', hero: { src: 'https://anee.io/images/site/fields-aerial.jpg', alt: 'Palayan', link: '' },
        blocks: [
            { id: 's1', type: 'text', html: '<p>Magandang araw, kasama! Ang kulay, ang letra at ang hugis dito ay para sa <b>lahat</b> ng email na hindi pa naipapadala.</p>' },
            { id: 's2', type: 'heading', text: 'Isang pamagat sa gitna', size: 'md' },
            { id: 's3', type: 'list', style: 'check', items: ['Isang hakbang', 'Isa pang hakbang'] },
            { id: 's4', type: 'callout', tone: 'leaf', title: 'Tip', html: '<p>Ang panel na ito ay sumusunod sa kulay ng panel.</p>' },
            { id: 's5', type: 'anee', face: 'idea', html: '<p>Ako ito, si Anee.</p>' },
            { id: 's6', type: 'button', label: 'Buksan ang anee.io', url: 'https://anee.io', align: 'left', style: 'solid' },
            { id: 's7', type: 'divider', style: 'leaf' },
            { id: 's8', type: 'social', title: 'Sumama sa amin' },
        ],
    };
    const PRESETS = [
        ['anee.io', null],
        ['Palay gold', { header: '#5a4410', headerTo: '#8a6a1a', accent: '#f2c230', accentTo: '#e8a317', buttonText: '#3b2c06', link: '#8a5a00', panel: '#fdf6dc', paper: '#f7f2e4' }],
        ['Sky and field', { header: '#16425b', headerTo: '#2f6690', accent: '#9fd356', accentTo: '#3a7d44', buttonText: '#0f2a3a', link: '#2f6690', panel: '#eaf4ee', paper: '#eef3f6' }],
        ['Night field', { header: '#0f150b', headerTo: '#2B3A1C', accent: '#87B84C', accentTo: '#E8BE1C', buttonText: '#0f150b', link: '#3d6b22', panel: '#eef5e4', paper: '#e6ebe0' }],
    ];
    const COLORS = [['header', 'Top band'], ['headerTo', 'Top band fades to'], ['accent', 'Buttons and stripe'], ['accentTo', 'Stripe fades to'], ['buttonText', 'Button words'], ['link', 'Links'], ['text', 'Words'], ['muted', 'Small words'], ['paper', 'Around the email'], ['card', 'The email'], ['panel', 'Panels'], ['line', 'Lines']];

    function openTheme() {
        const T = clone(S.boot.theme);
        let dirty = false;
        const L = layer('is-full', '<div class="nlk-bld" role="dialog" aria-modal="true">' +
            '<div class="nlk-bld-top"><button type="button" class="nlk-btn is-icon" data-back title="Back">' + ic('back') + '</button>' +
            '<div class="nlk-bld-title"><input value="The design" readonly tabindex="-1"><small>One design for every email not sent yet: colors, letters, logo and footer.</small></div>' +
            '<div class="nlk-seg nlk-mob-switch" data-mob><button type="button" data-v="edit" class="is-on">Edit</button><button type="button" data-v="view">Preview</button></div>' +
            '<button type="button" class="nlk-btn" data-reset>Back to the anee.io look</button><button type="button" class="nlk-btn is-gold" data-save>Save the design</button></div>' +
            '<div class="nlk-bld-body" data-show="edit"><aside class="nlk-bld-side"><div class="nlk-side-pane" data-form></div></aside>' +
            '<main class="nlk-bld-view"><div class="nlk-view-bar"><span class="nlk-grow">A sample email in this design</span><div class="nlk-seg" data-sch><button type="button" data-v="light"' + (appDark() ? '' : ' class="is-on"') + '>Light inbox</button><button type="button" data-v="dark"' + (appDark() ? ' class="is-on"' : '') + '>Dark inbox</button></div><div class="nlk-seg" data-dev><button type="button" data-v="desk" class="is-on">Desktop</button><button type="button" data-v="phone">Phone</button></div></div>' +
            '<div class="nlk-frame-wrap" data-wrap><iframe class="nlk-frame" title="Design preview" data-frame></iframe></div></main></div></div>');
        const form = $('[data-form]', L);
        const frame = $('[data-frame]', L);
        const mark = () => { dirty = true; };
        let pv = 0;
        let scheme = appDark() ? 'dark' : 'light';
        const preview = debounce(async () => {
            const my = ++pv;
            try {
                const r = await call('preview', { body: { email: { subject: 'Sample', preheader: '', content: SAMPLE }, theme: T, scheme } });
                if (my === pv) frame.srcdoc = r.data.html;
            } catch (e) { toast(e.message, true); }
        }, 300);

        function paint() {
            const fonts = S.boot.fonts;
            form.innerHTML =
                '<div class="nlk-box"><h4>Ready made looks</h4><div class="nlk-presets">' + PRESETS.map(([n, p], i) => {
                    const c = Object.assign({}, S.boot.themeDefaults, p || {});
                    return '<button type="button" class="nlk-preset" data-preset="' + i + '"><i><span style="background:' + c.header + '"></span><span style="background:' + c.accent + '"></span><span style="background:' + c.accentTo + '"></span></i>' + h(n) + '</button>';
                }).join('') + '</div></div>' +
                '<div class="nlk-box"><h4>Colors</h4><div class="nlk-colors">' + COLORS.map(([k, n]) => '<label class="nlk-color"><input type="color" data-c="' + k + '" value="' + h(T[k] || '#ffffff') + '"><div><b>' + h(n) + '</b><input type="text" data-ct="' + k + '" value="' + h(T[k] || '') + '" maxlength="7"></div></label>').join('') + '</div>' +
                '<label class="nlk-line" style="font-size:13px;font-weight:700"><button type="button" class="nlk-switch' + (T.headerTo ? ' is-on' : '') + '" data-grad></button>The top band fades from one color to the other</label></div>' +
                '<div class="nlk-box"><h4>Letters and shape</h4>' +
                '<label class="nlk-field"><span>Letters</span><select class="nlk-in" data-t="font">' + Object.keys(fonts).map((k) => '<option value="' + k + '"' + (T.font === k ? ' selected' : '') + '>' + h(fonts[k]) + '</option>').join('') + '</select></label>' +
                '<label class="nlk-field"><span>Corner roundness: <em data-rv="radius">' + T.radius + ' px</em></span><input type="range" min="0" max="32" data-t="radius" value="' + T.radius + '"></label>' +
                '<label class="nlk-field"><span>Button roundness: <em data-rv="buttonRadius">' + (T.buttonRadius >= 99 ? 'pill' : T.buttonRadius + ' px') + '</em></span><input type="range" min="0" max="40" data-t="buttonRadius" value="' + Math.min(40, T.buttonRadius) + '"></label></div>' +
                '<div class="nlk-box"><h4>Brand</h4><div class="nlk-field"><span>Logo on the top band <em>(white on dark works best)</em></span><div data-logo></div></div>' +
                '<label class="nlk-field"><span>Logo width: <em data-rv="logoWidth">' + T.logoWidth + ' px</em></span><input type="range" min="60" max="260" data-t="logoWidth" value="' + T.logoWidth + '"></label>' +
                '<label class="nlk-field"><span>The line beside anee.io at the bottom</span><input class="nlk-in" data-t="tagline" value="' + h(T.tagline) + '"></label>' +
                '<label class="nlk-field"><span>Sign off under every email <em>(optional)</em></span><textarea class="nlk-in" rows="2" data-t="signature" placeholder="Kasama mo sa bukid,&#10;John">' + h(T.signature) + '</textarea></label>' +
                '<label class="nlk-field"><span>Why they get it (footer)</span><textarea class="nlk-in" rows="2" data-t="footer">' + h(T.footer) + '</textarea></label></div>' +
                '<div class="nlk-box"><h4>Links in the footer and the Social block</h4>' +
                ['website', 'facebook', 'youtube', 'tiktok'].map((k) => '<label class="nlk-field"><span>' + ({ website: 'Website', facebook: 'Facebook page', youtube: 'YouTube channel', tiktok: 'TikTok' })[k] + '</span><input class="nlk-in" data-t="' + k + '" value="' + h(T[k]) + '" placeholder="https://"></label>').join('') + '</div>';

            $$('[data-c]', form).forEach((i) => i.addEventListener('input', () => { T[i.dataset.c] = i.value; $('[data-ct="' + i.dataset.c + '"]', form).value = i.value; if (i.dataset.c === 'headerTo') $('[data-grad]', form).classList.add('is-on'); mark(); preview(); }));
            $$('[data-ct]', form).forEach((i) => i.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(i.value)) { T[i.dataset.ct] = i.value.toLowerCase(); $('[data-c="' + i.dataset.ct + '"]', form).value = i.value; mark(); preview(); } }));
            $('[data-grad]', form).addEventListener('click', (e) => {
                const on = !e.currentTarget.classList.contains('is-on');
                e.currentTarget.classList.toggle('is-on', on);
                T.headerTo = on ? ($('[data-c="headerTo"]', form).value || '#44632a') : '';
                mark(); preview();
            });
            $$('[data-t]', form).forEach((i) => i.addEventListener('input', () => {
                const k = i.dataset.t;
                T[k] = i.type === 'range' ? +i.value : i.value;
                if (k === 'buttonRadius' && +i.value >= 40) T[k] = 999;
                const rv = $('[data-rv="' + k + '"]', form);
                if (rv) rv.textContent = k === 'buttonRadius' && T[k] >= 99 ? 'pill' : T[k] + ' px';
                mark(); preview();
            }));
            $('[data-logo]', form).appendChild(imgPicker(T.logo, (u) => { T.logo = u; mark(); preview(); }));
            $$('[data-preset]', form).forEach((b) => b.addEventListener('click', () => {
                const p = PRESETS[+b.dataset.preset][1];
                Object.assign(T, S.boot.themeDefaults && p === null ? pick(S.boot.themeDefaults, COLORS.map((c) => c[0])) : p);
                paint(); mark(); preview();
            }));
        }
        const pick = (o, keys) => keys.reduce((a, k) => { a[k] = o[k]; return a; }, {});
        paint();
        preview();

        $$('[data-mob] button', L).forEach((b) => b.addEventListener('click', () => { $('.nlk-bld-body', L).dataset.show = b.dataset.v; $$('[data-mob] button', L).forEach((x) => x.classList.toggle('is-on', x === b)); }));
        $$('[data-dev] button', L).forEach((b) => b.addEventListener('click', () => { $$('[data-dev] button', L).forEach((x) => x.classList.toggle('is-on', x === b)); $('[data-wrap]', L).classList.toggle('is-phone', b.dataset.v === 'phone'); }));
        $$('[data-sch] button', L).forEach((b) => b.addEventListener('click', () => { scheme = b.dataset.v; $$('[data-sch] button', L).forEach((x) => x.classList.toggle('is-on', x === b)); preview(); }));
        const save = async () => {
            await busy($('[data-save]', L), async () => {
                const r = await call('theme', { body: { theme: T } });
                S.boot.theme = r.data.theme;
                dirty = false;
                toast(r.message);
            });
        };
        L._save = save;
        $('[data-save]', L).addEventListener('click', save);
        $('[data-reset]', L).addEventListener('click', async () => {
            if (!(await confirmBox('Back to the anee.io look?', 'Every color, the letters, the logo and the footer go back to how anee.io ships them. It applies when you save.', 'Use it'))) return;
            Object.assign(T, clone(S.boot.themeDefaults));
            paint(); mark(); preview();
        });
        const close = async () => {
            if (dirty && !(await confirmBox('Leave without saving?', 'Your design changes will be lost.', 'Leave', true))) return;
            L._close();
        };
        $('[data-back]', L).addEventListener('click', close);
        L._esc = close;
    }

    /* ============================================================== sheets */

    function sheet(title, body, foot) {
        const L = layer('', '<div class="nlk-sheet" role="dialog" aria-modal="true"><div class="nlk-sheet-head"><h3>' + h(title) + '</h3><button type="button" class="nlk-btn is-icon is-ghost" data-x title="Close">' + ic('close') + '</button></div><div class="nlk-sheet-body">' + body + '</div>' + (foot ? '<div class="nlk-sheet-foot">' + foot + '</div>' : '') + '</div>');
        $('[data-x]', L).addEventListener('click', () => L._close());
        L.addEventListener('click', (e) => { if (e.target === L) L._close(); });
        return L;
    }

    function openSettings() {
        const st = clone(S.boot.settings);
        const b = S.boot;
        const tickWords = b.tick ? (b.tickAlive ? 'Running (last pass ' + h(b.tick.slice(11, 16)) + ')' : 'Not seen since ' + h(b.tick)) : 'Never seen yet';
        const L = sheet('Settings',
            '<div class="nlk-box"><div class="nlk-line"><div class="nlk-grow"><b style="font-size:15px">Sending</b><br><small class="nlk-hint" style="margin:0">' + (st.enabled ? 'On: the scheduler makes real Acumbamail campaigns at the daily send.' : 'Off: every day is planned and shown, nothing goes to anyone.') + '</small></div><button type="button" class="nlk-switch is-big' + (st.enabled ? ' is-on' : '') + '" data-en></button></div></div>' +
            '<div class="nlk-box"><h4>The daily send</h4><div class="nlk-row2"><label class="nlk-field"><span>Time (Philippine time)</span><input type="time" class="nlk-in" data-s="sendTime" value="' + h(st.sendTime) + '"></label>' +
            '<label class="nlk-field"><span>Today\'s news email</span><span class="nlk-line" style="min-height:40px"><button type="button" class="nlk-switch' + (st.newsAuto ? ' is-on' : '') + '" data-news></button><small>Joins the line by itself</small></span></label></div>' +
            '<small>The send waits for the morning\'s farm news (written at 7:00 AM) so the news can go the same day.</small></div>' +
            '<div class="nlk-box"><h4>Who it is from</h4><div class="nlk-row2"><label class="nlk-field"><span>Name</span><input class="nlk-in" data-s="fromName" value="' + h(st.fromName) + '"></label>' +
            '<label class="nlk-field"><span>Email</span><input type="email" class="nlk-in" data-s="fromEmail" value="' + h(st.fromEmail) + '"></label></div><small>The address must be one your Acumbamail account accepts as a sender.</small></div>' +
            '<div class="nlk-box"><h4>Tests</h4><label class="nlk-field"><span>Send tests to</span><input type="email" class="nlk-in" data-s="testTo" value="' + h(st.testTo) + '" placeholder="' + h(C.userEmail || 'you@example.com') + '"></label></div>' +
            '<details class="nlk-box"><summary style="font-weight:800;cursor:pointer">Acumbamail list numbers</summary><div style="display:grid;gap:8px;margin-top:10px">' +
            b.segments.map((s) => '<label class="nlk-line"><span class="nlk-grow" style="font-size:13px;font-weight:700">' + dot(s.key) + ' ' + h(s.name) + '</span><input class="nlk-in" style="width:130px" data-l="' + s.key + '" value="' + h(s.listId || '') + '"></label>').join('') + '</div></details>' +
            '<div class="nlk-box"><h4>Health</h4><div style="display:grid;gap:4px;font-size:13px"><div>Scheduler: <b>' + tickWords + '</b></div><div>Acumbamail key: <b>' + (b.acumbamail ? 'present' : 'missing') + '</b></div><div>Lists last checked: <b>' + h(b.syncedAt || 'not yet') + '</b></div></div></div>',
            '<button type="button" class="nlk-btn" data-x2>Close</button><button type="button" class="nlk-btn is-gold" data-save>Save settings</button>');
        $('[data-x2]', L).addEventListener('click', () => L._close());
        const en = $('[data-en]', L);
        en.addEventListener('click', async () => {
            const on = !en.classList.contains('is-on');
            if (on) {
                const people = b.segments.find((s) => s.key === 'all');
                if (!(await confirmBox('Turn sending on?', 'From the next daily send at ' + st.sendTime + ', real campaigns go to the lists (' + (people ? people.count : 0) + ' people on "Everyone" right now). Save the settings to make it happen.', 'Turn it on'))) return;
            }
            en.classList.toggle('is-on', on);
            st.enabled = on;
        });
        $('[data-news]', L).addEventListener('click', (e) => { e.currentTarget.classList.toggle('is-on'); st.newsAuto = e.currentTarget.classList.contains('is-on'); });
        const save = async () => {
            $$('[data-s]', L).forEach((i) => { st[i.dataset.s] = i.value.trim(); });
            st.lists = {};
            $$('[data-l]', L).forEach((i) => { st.lists[i.dataset.l] = i.value.trim(); });
            await busy($('[data-save]', L), async () => {
                const r = await call('settings', { body: { settings: st } });
                toast(r.message);
                L._close();
                refresh(false);
            });
        };
        L._save = save;
        $('[data-save]', L).addEventListener('click', save);
    }

    function openPeople() {
        const L = sheet('People on the lists', '<div class="nlk-segs" data-counts></div><div class="nlk-line"><label class="nlk-search nlk-grow">' + ic('search') + '<input type="search" placeholder="Find a person" data-pq></label><button type="button" class="nlk-btn" data-sync>Check the lists now</button></div><small class="nlk-hint" style="margin:0" data-pnote></small><div style="display:grid;gap:6px" data-people><div class="nlk-skel"></div></div>');
        let seg = '';
        const load = async () => {
            try {
                const r = await call('audience?q=' + encodeURIComponent($('[data-pq]', L).value.trim()) + '&seg=' + seg);
                const d = r.data;
                $('[data-counts]', L).innerHTML = S.boot.segments.map((s) => '<button type="button" class="nlk-segc' + (seg === s.key ? ' is-on' : '') + '" data-sg="' + s.key + '" style="text-align:left;' + (seg === s.key ? 'border-color:#87B84C;box-shadow:0 0 0 3px rgba(135,184,76,.18)' : '') + '"><span>' + dot(s.key) + h(s.name) + '</span><b>' + (d.counts[s.key] || 0) + '</b><small>' + h(s.line) + '</small></button>').join('');
                $$('[data-sg]', L).forEach((b) => b.addEventListener('click', () => { seg = seg === b.dataset.sg ? '' : b.dataset.sg; load(); }));
                $('[data-pnote]', L).textContent = d.total + ' addresses known, ' + d.unsubscribed + ' unsubscribed' + (d.withErrors ? ', ' + d.withErrors + ' with a problem' : '') + '. Lists last checked ' + (d.syncedAt || 'never') + '. The lists follow plans and payments by themselves; unsubscribes are read back from Acumbamail.';
                $('[data-people]', L).innerHTML = d.rows.length ? d.rows.map((p) => '<div class="nlk-person nlk-rise"><div><b>' + h(p.name || p.email) + '</b><small>' + h(p.email) + (p.member ? '' : ' · not a member') + '</small><small>' + (p.unsubscribed ? '<span class="nlk-bad">Unsubscribed</span>' : h(p.cells || 'On no list yet')) + '</small>' + (p.error && !p.unsubscribed ? '<small class="nlk-bad">' + h(p.error) + '</small>' : '') + '</div></div>').join('') : '<div class="nlk-empty"><b>Nobody here yet</b>People appear once the lists are checked.</div>';
            } catch (e) { toast(e.message, true); }
        };
        $('[data-pq]', L).addEventListener('input', debounce(load, 300));
        $('[data-sync]', L).addEventListener('click', (e) => busy(e.currentTarget, async () => { const r = await call('audience/sync', { body: {} }); toast(r.message); load(); refresh(false); }));
        load();
    }

    function openHistory() {
        const L = sheet('What went out', '<div class="nlk-history" data-runs><div class="nlk-skel"></div><div class="nlk-skel"></div></div><button type="button" class="nlk-btn nlk-hide" data-moreruns>Show older</button>');
        let cursor = 0;
        const box = $('[data-runs]', L);
        const KIND = { daily: 'Daily send', timed: 'At its own time' };
        const ST = { done: ['st-sent', 'Done'], dry: ['st-draft', 'Planned only'], failed: ['st-expired', 'Failed'], running: ['st-partial', 'Running'] };
        const load = async (more) => {
            try {
                const r = await call('runs' + (cursor ? '?cursor=' + cursor : ''));
                if (!more) box.innerHTML = '';
                r.data.rows.forEach((x) => {
                    const st = ST[x.status] || ['', x.status];
                    const list = x.campaigns.length ? x.campaigns.map((c) => '<div><span class="nlk-pill ' + (c.status === 'created' ? 'st-queued' : 'st-expired') + '">' + (c.status === 'created' ? 'Made' : 'Failed') + '</span><b>' + h(c.title) + '</b> to ' + h(c.cells) + (c.campaignId ? ' · #' + c.campaignId : '') + (c.error ? '<span style="color:var(--red)"> · ' + h(c.error) + '</span>' : '') + '</div>').join('')
                        : (x.picks.length ? x.picks.map((p) => '<div><span class="nlk-pill pr-' + (p.priority || 'regular') + '">' + h((PRI[p.priority] || PRI.regular).short) + '</span><b>' + h(p.title) + '</b> to ' + h(p.cells) + (p.people != null ? ' (' + p.people + ')' : '') + '</div>').join('') : '<div style="color:var(--faint)">Nothing was in line.</div>');
                    box.appendChild(el('<div class="nlk-box nlk-rise"><div class="nlk-line"><b class="nlk-grow">' + h(dayWords(x.date, true)) + ' · ' + h(KIND[x.kind] || x.kind) + '</b><span class="nlk-pill ' + st[0] + '">' + h(st[1]) + '</span></div><div class="nlk-camps">' + list + '</div>' + (x.error ? '<small class="nlk-hint" style="margin:0">' + h(x.error) + '</small>' : '') + '</div>'));
                });
                if (!box.children.length) box.innerHTML = '<div class="nlk-empty"><b>Nothing yet</b>Each day\'s send is written here, planned or made.</div>';
                cursor = r.data.nextCursor || 0;
                $('[data-moreruns]', L).classList.toggle('nlk-hide', !cursor);
            } catch (e) { toast(e.message, true); }
        };
        $('[data-moreruns]', L).addEventListener('click', () => load(true));
        load(false);
    }
})();
