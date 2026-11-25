/* The Coverage Planner (sm/partials/range-effect.blade.php), in a file the phone keeps since 2026-11-26. Loaded as a plain
   script (Vite::asset), not a module: no import and no export in it. What the server knows is window.__RFX, written there. */
(() => {
    // What the server says (sm/partials/range-effect.blade.php).
    const SRV = window.__RFX;
    const M = document.getElementById('rfxModal');
    if (!M) return;
    // The board's shell can move this under a transformed parent; the modal belongs to the body.
    document.body.appendChild(M);
    const U = JSON.parse(M.dataset.u);
    const SID = M.dataset.sid;
    // The Protocol Builder's planner: its lots are the farm's lots, its days picked here, its products put in the protocol.
    const PB = !!U.pb;
    const addDays = (s, n) => { const d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
    const $ = (s, r = M) => r.querySelector(s);
    const $$ = (s, r = M) => [...r.querySelectorAll(s)];
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const D = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const say = (s) => { const d = D(s); return MON[d.getMonth()] + ' ' + d.getDate(); };
    const sayY = (s) => { const d = D(s); return MON[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear(); };
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const STATE = { ok: ['Covered', '#4a7c2a'], thin: ['Thin', '#f59e0b'], gap: ['Gap: critical, not covered', '#dc2626'], late: ['Late: the crop no longer takes it', '#8b5cf6'],
        extra: ['Covered when it matters little', '#8b5cf6'], watch: ['Worth watching', '#86c25a'], pre: ['Before the crop', '#86c25a'], none: ['Nothing needed', '#d1d5db'] };
    const CRIT = ['Not needed now', 'Low', 'Matters', 'Critical'];
    // The same states said for water: covered is well watered, a gap is a stress day.
    const WSTATE = { ok: 'Well watered', thin: 'Drying: water soon', gap: 'Stress: too dry when it matters', watch: 'Dry, worth watching', extra: 'Wet when it matters little',
        pre: 'Before the crop', none: 'Nothing needed', late: 'Wet when it matters little' };
    const stName = (s, kind) => kind === 'water' ? (WSTATE[s] || '') : (STATE[s] ? STATE[s][0] : '');
    // What an application is doing, in its order, and each phase's colour where CSS cannot reach (the chart's SVG).
    const PH_ORDER = ['soil', 'absorb', 'strong', 'easing', 'fading', 'wet', 'moist', 'drying', 'dry'];
    const PHC = { soil: '#a8723f', absorb: '#22b09a', strong: '#3b7a1f', easing: '#86c25a', fading: '#cfe8b8', wet: '#2b86d1', moist: '#62aedd', drying: '#b9d9ee', dry: '#e0b264' };
    const EL = { P2O5: 'P₂O₅', K2O: 'K₂O' };
    const tabOf = (key) => key.startsWith('w:') ? 'water' : key.startsWith('t:') ? 'prot' : key.startsWith('p:') ? 'pest' : ((S.opts?.nutrients || []).find((n) => n.key === key) || {}).micro ? 'micro' : 'nut';
    // What the reading counts besides coverage (RangeEffectLogic): every part on until the farmer switches it off.
    const LOGIC_DEF = { amt: true, soil: true, links: true, health: true };
    const S = { opts: null, lot: null, check: 'n:N', checks: ['n:N'], tab: 'nut', tries: [], res: null, saved: null, picked: null, q: '', busy: false,
        logic: { ...LOGIC_DEF }, savedId: null, savedTitle: '', recounted: null, hide: [], custom: null, useCustom: false, pb: null,
        // The plan's own lines changed or taken out in this reading only, by key: {drop} or {date, qty, unit, method}, each
        // with what it was (o) so one taken out can still be listed and brought back.
        plan: {} };
    const PEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.8 2.8 0 00-4-4L4 16v4z"/></svg>';
    const TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>';
    const kgSay = (v) => { v = Number(v) || 0; return v >= 10 ? String(Math.round(v)) : v >= 1 ? String(Math.round(v * 10) / 10) : String(Math.round(v * 100) / 100); };
    // Kilos said with their unit; under a tenth of a kilo in grams (cobalt, molybdenum).
    const massSay = (v) => { v = Number(v) || 0; return v > 0 && v < 0.1 ? kgSay(v * 1000) + ' g' : kgSay(v) + ' kg'; };

    // The app's theme flipped while the sheet is open: the drawings carry colours, so paint them again.
    let wasDark = document.documentElement.classList.contains('dark');
    new MutationObserver(() => {
        const d = document.documentElement.classList.contains('dark');
        if (d === wasDark) return;
        wasDark = d;
        if (!M.hidden && S.res) { paint(); paintSel(); }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    /* ---------------- open and close ---------------- */
    /* Where the farmer left off (the owner, 2026-10-30: "if i click the close, and click open again ... i'll just
       continue where i left off"): the lot, the checks and their tab, the tries, the logic switches, the saved reading
       being edited and how far down the sheet was, kept per season on this device; closed and opened again on the same
       page, nothing is even counted again. */
    const KEEP = 'rfx-left-' + SID;
    const scroller = () => $('[data-rfx-scroll]');
    const keep = () => {
        if (!S.opts) return;
        try {
            localStorage.setItem(KEEP, JSON.stringify({ lot: S.lot, check: S.check, checks: S.checks, tab: S.tab, tries: S.tries, plan: S.plan, logic: S.logic, hide: S.hide, custom: S.custom, useCustom: S.useCustom, pb: S.pb,
                // A hidden sheet reads as scrolled to the top: keep where it was when it closed.
                savedId: S.savedId, savedTitle: S.savedTitle, top: Math.round(M.hidden || !M.classList.contains('is-on') ? (S.lastTop || 0) : scroller().scrollTop), at: Date.now() }));
        } catch (_) {}
    };
    const leftOff = () => {
        try {
            const st = JSON.parse(localStorage.getItem(KEEP) || 'null');
            // A month later the season has moved on: start fresh.
            return st && Date.now() - (st.at || 0) < 30 * 864e5 ? st : null;
        } catch (_) { return null; }
    };
    let keepTimer = 0;
    scroller().addEventListener('scroll', () => { clearTimeout(keepTimer); keepTimer = setTimeout(keep, 400); }, { passive: true });
    window.addEventListener('pagehide', keep);
    const open = async () => {
        M.hidden = false;
        document.documentElement.classList.add('rfx-lock');
        requestAnimationFrame(() => requestAnimationFrame(() => M.classList.add('is-on')));
        if (!S.opts) await boot();
        else if (!S.res) run();
        else if (S.lastTop) requestAnimationFrame(() => { scroller().scrollTop = S.lastTop; });
    };
    const close = () => {
        S.lastTop = scroller().scrollTop;
        keep();
        M.classList.remove('is-on');
        document.documentElement.classList.remove('rfx-lock');
        setTimeout(() => { M.hidden = true; }, calm ? 0 : 320);
        hideDay();
        // Tasks were put on the plan from here: the board behind is read again.
        if (S.boardStale) setTimeout(() => location.reload(), calm ? 0 : 340);
    };
    document.addEventListener('click', (e) => {
        if (e.target.closest('#rangeEffectBtn, #rangeEffectMobileBtn, [data-range-effect-open]')) { e.preventDefault(); open(); }
    });
    M.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-close]')) close();
        if (e.target.closest('[data-rfx-saved-open]')) openSaved();
        if (e.target.closest('[data-rfx-saved-close]')) $('[data-rfx-drawer]').classList.remove('is-on');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || M.hidden) return;
        if (document.querySelector('dialog[open]')) return;   // the field questions close themselves
        if (!lotBox.hidden) closeLots();
        else if (!$('[data-rfx-stg]').hidden) closeStage();
        else if (!whatBox.hidden) closeWhat();
        else if (!watBox.hidden) closeWat();
        else if (!applyBox.hidden) closeApply();
        else if (!$('[data-rfx-edit]').hidden) closeEdit();
        else if (!tryBox.hidden) closePick();
        else if (!$('[data-rfx-day]').hidden) hideDay();
        else if ($('[data-rfx-drawer]').classList.contains('is-on')) $('[data-rfx-drawer]').classList.remove('is-on');
        else close();
    });

    /* ---------------- the controls ---------------- */
    const boot = async () => {
        try {
            const r = await window.api(U.options);
            S.opts = r.data;
            S.lot = (S.opts.lots[0] || {}).id ?? null;
            if (PB) {
                // A protocol: the first of the farm's lots that has answers, else none; its days from today.
                S.lot = ((S.opts.lots || []).find((l) => l.id && Object.keys(l.answers || {}).length) || (S.opts.lots || [])[0] || {}).id ?? 0;
                S.pb = { start: iso(new Date()), transplant: addDays(iso(new Date()), 21), ha: 1 };
            }
            // Pick up where the farmer left off, as far as it still fits this season.
            const st = leftOff();
            const lot = st ? (S.opts.lots || []).find((l) => l.id === st.lot) : null;
            if (lot) {
                const fits = (k) => typeof k === 'string' && k !== '' && (!k.startsWith('p:') || (lot.threats || []).some((t) => 'p:' + t.slug === k));
                S.lot = lot.id;
                S.checks = (Array.isArray(st.checks) ? st.checks : []).filter(fits);
                if (!S.checks.length) S.checks = [fits(st.check) ? st.check : 'n:N'];
                S.check = S.checks.includes(st.check) ? st.check : S.checks[0];
                S.tab = ['nut', 'micro', 'prot', 'pest', 'water'].includes(st.tab) ? st.tab : tabOf(S.check);
                S.tries = (Array.isArray(st.tries) ? st.tries : []).filter((t) => t && t.date && (t.productId || t.ai || t.water));
                S.plan = planIn(st.plan);
                S.logic = { ...LOGIC_DEF, ...(st.logic || {}) };
                S.hide = (Array.isArray(st.hide) ? st.hide : []).filter((k) => S.checks.includes(k));
                if (st.custom && typeof st.custom === 'object') { S.custom = st.custom; S.useCustom = !!st.useCustom && S.custom.base === S.lot; }
                if (PB && st.pb && st.pb.start) S.pb = { start: st.pb.start, transplant: st.pb.transplant || addDays(st.pb.start, 21), ha: Number(st.pb.ha) > 0 ? Number(st.pb.ha) : 1 };
                if (st.savedId && (S.opts.saved || []).some((x) => x.id === st.savedId)) { S.savedId = st.savedId; S.savedTitle = st.savedTitle || ''; }
                S.restoreTop = Number(st.top) || 0;
            }
            paintSavedCount();
            paintLots();
            paintPicks();
            paintTries();
            paintLogic();
            paintPbDays();
            paintFoot();
            if (!S.lot) { body(empty('Add a lot first', 'The calendar reads one lot at a time: its crop, its planting day and the products on its tasks.')); return; }
            run();
        } catch (err) {
            if (err.tierLock) { close(); return; }
            body(empty('The calendar could not open', err.message || 'Please try again.'));
        }
    };
    const lotOf = () => (S.opts?.lots || []).find((l) => l.id === S.lot) || null;
    // A lot in a line: its crop (or, in a protocol, its answers), its place, its days, its size.
    const lotSmall = (l) => (PB ? (l.id ? (whatCount(l.answers) ? whatCount(l.answers) + ' answers' : 'no answers yet') : 'soil not counted') : l.crop.split(',')[0]);
    const lotMeta = (l) => (PB
        ? [lotSmall(l), l.place, l.ha ? Math.round(l.ha * 100) / 100 + ' ha' : '']
        : [l.crop.split(',')[0], l.variety, l.hasDay ? (l.anchor ? `${l.counter} 0 on ${say(l.anchor)}` : '') : 'no planting day yet', l.ha ? Math.round(l.ha * 100) / 100 + ' ha' : '']).filter(Boolean).join(' · ');
    const CHEV = '<svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const TICK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    const paintLots = () => {
        const lots = S.opts.lots || [];
        const base = S.custom ? lots.find((l) => l.id === S.custom.base) : null;
        const l = lotOf();
        const what = S.useCustom && S.custom;
        const n = lots.filter((x) => !PB || x.id).length;
        $('[data-rfx-lots]').innerHTML = `<button type="button" class="rfx-lottag ${what ? 'is-what' : ''}" data-rfx-lot-open aria-haspopup="dialog" title="${lots.length > 1 || PB ? 'Pick another lot' : 'This lot'}">
            <em aria-hidden="true">${what ? '🧪' : esc(l?.icon || '🗺️')}</em><span><b>${esc(what ? S.custom.name : (l?.name || 'Pick a lot'))}</b><small>${esc(what ? 'What if' + (base ? ' on ' + base.name : '') + ' · ' + whatCount(S.custom.answers) + ' answers' : (l ? lotSmall(l) : '') + (n > 1 ? ` · ${n} lots` : ''))}</small></span>${CHEV}</button>`
            + (what ? `<button type="button" class="rfx-chip is-what" data-lot-what-edit>${PEN_I} Change the what-if lot</button>` : '');
        if (!lotBox.hidden) paintLotList();
    };
    /* The lots, in their own window: a search once there are many, each with its crop, days and size, the one read
       ticked, then the what-if lot. */
    const lotBox = $('[data-rfx-lotm]');
    let lotQ = '';
    const paintLotList = () => {
        const lots = S.opts.lots || [];
        const q = lotQ.trim().toLowerCase();
        const shown = lots.filter((l) => !q || [l.name, l.crop, l.place, l.variety].filter(Boolean).join(' ').toLowerCase().includes(q));
        $('[data-rfx-lotl]').innerHTML = shown.map((l, k) => `<button type="button" class="rfx-lotr ${!S.useCustom && l.id === S.lot ? 'is-on' : ''}" data-lot="${l.id}" style="--k:${Math.min(k, 16)}" aria-pressed="${!S.useCustom && l.id === S.lot}"><em aria-hidden="true">${esc(l.icon)}</em><span><b>${esc(l.name)}</b><small>${esc(lotMeta(l))}</small></span><i>${TICK}</i></button>`).join('')
            || '<p class="rfx-pick-hint">No lot by that name. Try part of it, its crop or its place.</p>';
        const base = S.custom ? lots.find((l) => l.id === S.custom.base) : null;
        $('[data-rfx-lotw]').innerHTML = S.custom
            ? `<button type="button" class="rfx-lotr ${S.useCustom ? 'is-on' : ''}" data-lot-what-use aria-pressed="${!!S.useCustom}"><em aria-hidden="true">🧪</em><span><b>${esc(S.custom.name)}</b><small>${esc(['A what-if lot', base ? 'on ' + base.name : '', whatCount(S.custom.answers) + ' answers'].filter(Boolean).join(' · '))}</small></span><i>${TICK}</i></button><button type="button" class="rfx-btn is-ghost" data-lot-what-edit>${PEN_I} Change it</button>`
            : `<button type="button" class="rfx-lotr" data-lot-what-new><em aria-hidden="true">🧪</em><span><b>What if</b><small>Another soil and field on the same crop, days and tasks. Never saved as a lot.</small></span></button>`;
    };
    const openLots = () => {
        lotQ = '';
        const many = (S.opts?.lots || []).length > 6;
        $('[data-rfx-lot-s]').hidden = !many;
        $('[data-rfx-lot-q]').value = '';
        paintLotList();
        $('[data-rfx-lot-open]')?.classList.add('is-pop');
        lotBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => lotBox.classList.add('is-on')));
        setTimeout(() => {
            if (many && matchMedia('(pointer: fine)').matches) $('[data-rfx-lot-q]').focus({ preventScroll: true });
            else (lotBox.querySelector('.rfx-lotr.is-on') || lotBox.querySelector('.rfx-lotr'))?.focus({ preventScroll: true });
            lotBox.querySelector('.rfx-lotr.is-on')?.scrollIntoView({ block: 'nearest' });
        }, calm ? 0 : 200);
    };
    const closeLots = () => {
        lotBox.classList.remove('is-on');
        $('[data-rfx-lot-open]')?.classList.remove('is-pop');
        setTimeout(() => { if (!lotBox.classList.contains('is-on')) lotBox.hidden = true; }, calm ? 0 : 260);
    };
    $('[data-rfx-lot-q]').addEventListener('input', (e) => { lotQ = e.target.value; paintLotList(); });
    $('[data-rfx-lots]').addEventListener('click', (e) => {
        if (e.target.closest('[data-lot-what-edit]')) { openWhat(); return; }
        if (e.target.closest('[data-rfx-lot-open]')) openLots();
    });
    lotBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-lotm-x]')) { closeLots(); return; }
        if (e.target.closest('[data-lot-what-edit]') || e.target.closest('[data-lot-what-new]')) { closeLots(); setTimeout(openWhat, calm ? 0 : 200); return; }
        if (e.target.closest('[data-lot-what-use]')) {
            closeLots();
            if (!S.useCustom) { S.useCustom = true; S.lot = S.custom.base; paintLots(); paintPicks(); run(); keep(); }
            return;
        }
        const b = e.target.closest('[data-lot]');
        if (!b) return;
        closeLots();
        if (!S.useCustom && Number(b.dataset.lot) === S.lot) return;
        pickLot(Number(b.dataset.lot));
    });
    const pickLot = (id) => {
        S.useCustom = false;
        if (!PB && id !== S.lot) S.plan = {};
        S.lot = id;
        // A named pest of one crop means nothing on another.
        const mine = (k) => !k.startsWith('p:') || (lotOf()?.threats || []).some((t) => 'p:' + t.slug === k);
        S.checks = S.checks.filter(mine);
        if (!S.checks.length) S.checks = ['t:insect'];
        if (!S.checks.includes(S.check)) S.check = S.checks[0];
        paintLots();
        paintPicks();
        run();
    };
    const paintPicks = () => {
        const o = S.opts;
        $$('[data-rfx-tabs] button').forEach((b) => b.classList.toggle('is-on', b.dataset.tab === S.tab));
        let html = '';
        if (S.tab === 'nut' || S.tab === 'micro') {
            html = o.nutrients.filter((n) => (S.tab === 'micro') === n.micro).map((n, k) => `<button type="button" class="rfx-chip ${on(n.key)}" data-check="${n.key}" aria-pressed="${S.checks.includes(n.key)}" style="--k:${k}"><b>${esc(n.short)}</b> <small>${esc(n.name)}</small></button>`).join('');
        } else if (S.tab === 'water') {
            // Water: a usual year, a dry year, or the rain alone; compare them side by side to see what the waterings add.
            html = (o.water || []).map((w, k) => `<button type="button" class="rfx-chip ${on(w.key)}" data-check="${w.key}" aria-pressed="${S.checks.includes(w.key)}" style="--k:${k}" title="${esc(w.about)}">${w.icon} ${esc(w.name)} <small>${esc(w.about)}</small></button>`).join('');
        } else if (S.tab === 'prot') {
            html = o.threats.map((t, k) => `<button type="button" class="rfx-chip ${on(t.key)}" data-check="${t.key}" aria-pressed="${S.checks.includes(t.key)}" style="--k:${k}">${t.icon} ${esc(t.name)}</button>`).join('');
        } else {
            const list = lotOf()?.threats || [];
            html = list.length
                ? `<input type="search" data-rfx-pest-q placeholder="Find a pest or disease" value="${esc(S.q)}">` + list.filter((t) => !S.q || (t.name + ' ' + t.local).toLowerCase().includes(S.q.toLowerCase()))
                    .map((t, k) => `<button type="button" class="rfx-chip ${on('p:' + t.slug)}" data-check="p:${esc(t.slug)}" aria-pressed="${S.checks.includes('p:' + t.slug)}" style="--k:${Math.min(k, 20)}" title="${esc(t.local)}">${esc(t.name)} <small>${esc(t.kind)}</small></button>`).join('')
                : '<small class="rfx-muted">This lot\'s crop has no pests or diseases in our book yet. Use Protection instead.</small>';
        }
        if (S.checks.length === 1) html += '<small class="rfx-hint">Tap more to compare them</small>';
        $('[data-rfx-picks]').innerHTML = html;
        paintSel();
    };
    // Picked checks: each keeps the colour of its place in the pick, the one the calendar shows is filled.
    // Lighter on the dark sheet so each line and ring stays clear of the background.
    const SERIES = ['#3b7a1f', '#2563eb', '#c2650a', '#9333ea', '#db2777', '#0e7490'];
    const SERIES_DARK = ['#8fd163', '#60a5fa', '#fbbf24', '#c084fc', '#f472b6', '#22d3ee'];
    const isDark = () => document.documentElement.classList.contains('dark');
    // No limit on how many are compared (the owner, 2026-10-30: "compare should have no limits. we want a deep planning"):
    // past the fourteen named colours each one takes a hue of its own, a golden angle from the last.
    const SERIES_MORE = ['#b91c1c', '#a16207', '#4d7c0f', '#4338ca', '#be185d', '#0369a1', '#7c2d12', '#6d28d9'];
    const SERIES_MORE_DARK = ['#f87171', '#facc15', '#a3e635', '#a5b4fc', '#fb7185', '#7dd3fc', '#fdba74', '#ddd6fe'];
    const seriesColor = (i) => {
        const pal = isDark() ? SERIES_DARK.concat(SERIES_MORE_DARK) : SERIES.concat(SERIES_MORE);
        if (i < pal.length) return pal[i];
        const h = Math.round((i * 137.508 + 18) % 360);
        return isDark() ? `hsl(${h} 72% 70%)` : `hsl(${h} 64% 36%)`;
    };
    const colorOf = (key) => seriesColor(Math.max(0, S.checks.indexOf(key)));
    // A colour token of the sheet, for the SVG drawings CSS cannot reach.
    const tok = (name) => getComputedStyle(M).getPropertyValue(name).trim();
    const on = (key) => S.checks.includes(key) ? 'is-on' : '';
    const labelOf = (key) => {
        const o = S.opts || {};
        const n = (o.nutrients || []).find((x) => x.key === key);
        if (n) return n.short === n.name ? n.name : `${n.short} ${n.name}`;
        const t = (o.threats || []).find((x) => x.key === key);
        if (t) return `${t.icon} ${t.name}`;
        const w = (o.water || []).find((x) => x.key === key);
        if (w) return `${w.icon} ${w.short}`;
        const p = (lotOf()?.threats || []).find((x) => 'p:' + x.slug === key);
        return p ? p.name : key;
    };
    const paintSel = () => {
        paintWrow();
        const row = $('[data-rfx-sel-row]');
        row.hidden = S.checks.length < 2;
        if (row.hidden) return;
        $('[data-rfx-sel]').innerHTML = S.checks.map((k, i) => `<span class="rfx-chip ${k === S.check ? 'is-on' : ''}" style="--sc:${colorOf(k)};--k:${i}" role="button" tabindex="0" data-focus="${esc(k)}" title="Show it in the calendar"><i></i>${esc(labelOf(k))}<button type="button" data-unpick="${esc(k)}" aria-label="Remove from the comparison"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
    };
    const focusCheck = (key) => {
        if (key === S.check || !S.checks.includes(key)) return;
        S.check = key;
        paintSel();
        run();
    };
    // A tap adds a check to the comparison (and shows it), or takes it out; one always stays.
    // keepMain: added beside the one the calendar shows (the season chart's switches), not in its place.
    const togglePick = (key, keepMain = false) => {
        if (S.checks.includes(key)) {
            if (S.checks.length === 1) return;
            S.checks = S.checks.filter((k) => k !== key);
            S.hide = S.hide.filter((k) => k !== key);
            if (S.check === key) S.check = S.checks[S.checks.length - 1];
        } else {
            S.checks = [...S.checks, key];
            if (!keepMain) S.check = key;
        }
        // The chips stay where they are; only their marks change.
        $$('[data-rfx-picks] [data-check]').forEach((x) => { const k = x.dataset.check; x.classList.toggle('is-on', S.checks.includes(k)); x.setAttribute('aria-pressed', String(S.checks.includes(k))); });
        const hint = $('[data-rfx-picks] .rfx-hint');
        if (hint) hint.hidden = S.checks.length > 1;
        paintSel();
        run();
    };
    $('[data-rfx-sel]').addEventListener('click', (e) => {
        const x = e.target.closest('[data-unpick]');
        if (x) { togglePick(x.dataset.unpick); return; }
        const f = e.target.closest('[data-focus]');
        if (f) focusCheck(f.dataset.focus);
    });
    $('[data-rfx-tabs]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-tab]');
        if (!b || b.dataset.tab === S.tab) return;
        S.tab = b.dataset.tab;
        S.q = '';
        paintPicks();
    });
    $('[data-rfx-picks]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-check]');
        if (b) togglePick(b.dataset.check);
    });
    $('[data-rfx-picks]').addEventListener('input', (e) => {
        if (!e.target.matches('[data-rfx-pest-q]')) return;
        S.q = e.target.value;
        const at = e.target.selectionStart;
        paintPicks();
        const q = $('[data-rfx-pest-q]');
        q.focus();
        try { q.setSelectionRange(at, at); } catch (_) {}
    });

    const paintLogic = () => $$('[data-logic]').forEach((b) => { const v = !!S.logic[b.dataset.logic]; b.classList.toggle('is-on', v); b.setAttribute('aria-pressed', String(v)); });
    $('[data-rfx-logic]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-logic]');
        if (!b) return;
        S.logic[b.dataset.logic] = !S.logic[b.dataset.logic];
        paintLogic();
        run();
    });

    /* ---------------- Apply on: a tag that opens the date picker ---------------- */
    const paintDate = (input) => { const t = input.parentElement.querySelector('[data-date-say]'); if (t) t.textContent = input.value ? sayY(input.value) : 'Pick a day'; };
    const setDate = (input, v) => { input.value = v || ''; paintDate(input); };
    $$('[data-date-for]').forEach((btn) => {
        const input = btn.parentElement.querySelector('.rfx-date-in');
        btn.addEventListener('click', () => {
            btn.classList.add('is-pop');
            try { if (typeof input.showPicker === 'function') input.showPicker(); else { input.focus(); input.click(); } }
            catch (_) { input.focus(); input.click(); }
        });
        input.addEventListener('change', () => {
            paintDate(input);
            btn.classList.remove('is-pop');
            if (!calm) { btn.classList.remove('is-flash'); void btn.offsetWidth; btn.classList.add('is-flash'); }
        });
        input.addEventListener('blur', () => btn.classList.remove('is-pop'));
    });

    /* The protocol's days (the Protocol Builder): its day zero, the transplant and the area its amounts are for; a change
       is read again at once. Moving day zero moves the transplant with it, the seedbed keeping its length. */
    const pbIns = PB ? { start: $('[data-rfx-pb-start]'), tr: $('[data-rfx-pb-tr]'), ha: $('[data-rfx-pb-ha]') } : null;
    const paintPbDays = () => {
        if (!PB || !S.pb) return;
        setDate(pbIns.start, S.pb.start);
        if (pbIns.tr) setDate(pbIns.tr, S.pb.transplant);
        pbIns.ha.value = S.pb.ha;
    };
    if (PB) {
        pbIns.start.addEventListener('change', () => {
            const v = pbIns.start.value;
            if (!v || !S.pb) return;
            const shift = Math.round((new Date(v + 'T00:00:00') - new Date(S.pb.start + 'T00:00:00')) / 864e5);
            S.pb.start = v;
            S.pb.transplant = addDays(S.pb.transplant, shift);
            paintPbDays();
            run();
            keep();
        });
        pbIns.tr?.addEventListener('change', () => {
            if (!pbIns.tr.value || !S.pb) return;
            S.pb.transplant = pbIns.tr.value < S.pb.start ? S.pb.start : pbIns.tr.value;
            paintPbDays();
            run();
            keep();
        });
        let haT = 0;
        pbIns.ha.addEventListener('input', () => {
            clearTimeout(haT);
            haT = setTimeout(() => { const v = Number(pbIns.ha.value); if (v > 0 && S.pb && v !== S.pb.ha) { S.pb.ha = v; run(); keep(); } }, 500);
        });
    }

    /* ---------------- fertilizers: the products you plan to apply ---------------- */
    const tryBox = $('[data-rfx-try]');
    // Each product's kind, as the picker's tabs and the groups read it (the book's types).
    const PICK_TABS = [['all', '✨', 'All'], ['fert', '🟤', 'Granular fertilizers'], ['foliar', '💧', 'Foliar and soluble'], ['bio', '🌿', 'Organic and bio'],
        ['insect', '🐛', 'Insecticides'], ['fungus', '🍄', 'Fungicides'], ['weed', '🌱', 'Herbicides'], ['other', '📦', 'Others']];
    const TAB_OF = { granular: 'fert', amendment: 'fert', foliar: 'foliar', soluble: 'foliar', biological: 'bio', biostimulant: 'bio', insecticide: 'insect', acaricide: 'insect', fungicide: 'fungus', herbicide: 'weed' };
    const TYPE_WORD = { 'granular fertilizer': 'granular', 'soil conditioner': 'amendment', 'foliar fertilizer': 'foliar', 'soluble fertilizer': 'soluble', biological: 'biological', biostimulant: 'biostimulant',
        insecticide: 'insecticide', miticide: 'acaricide', fungicide: 'fungicide', herbicide: 'herbicide' };
    // A product added before the kinds were kept reads its kind from its line ("Granular fertilizer · 46-0-0").
    const kindOf = (t) => t.t || TYPE_WORD[String(t.line || '').split(' · ')[0].toLowerCase()] || '';
    const groupOfTry = (t) => TAB_OF[kindOf(t)] || 'other';
    const PEN_I = '<svg class="rfx-pen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.8 2.8 0 00-4-4L4 16v4z"/></svg>';
    const COPY_I = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="8.5" y="8.5" width="11" height="11" rx="2.4"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5V6.6a2.1 2.1 0 00-2.1-2.1H6.6a2.1 2.1 0 00-2.1 2.1v6.8a2.1 2.1 0 002.1 2.1h1.9"/></svg>';
    // A name on a chip breaks between its words, never inside a grade ("46-0-0").
    const nmHtml = (n) => esc(n).split(' ').map((w) => (w.includes('-') ? `<span class="rfx-nw">${w}</span>` : w)).join(' ');
    // A chip leaves out what its group already says: the day under a day, the name under a fertilizer.
    const tryChip = (t, i, drop = '') => `<span class="rfx-chip is-try" data-try="${i}" role="button" tabindex="0" title="Tap to change it">${t.method === 'foliar' ? '💧' : '🟤'} <span class="rfx-ct">${drop === 'name' ? `<b>${esc(say(t.date))}</b>` : nmHtml(t.name)} <small>${esc([drop === 'date' || drop === 'name' ? '' : say(t.date), t.method || '', t.qty ? t.qty + ' ' + t.unit : ''].filter(Boolean).join(' · '))}</small></span>${PEN_I}<button type="button" data-dupe="${i}" aria-label="Make a copy of ${esc(t.name)}" title="Make a copy">${COPY_I}</button><button type="button" data-untry="${i}" aria-label="Remove ${esc(t.name)}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`;
    // Grouped by kind (the owner, 2026-10-30: "group it by type while arranging per date"), each kind by its dates; or by
    // the day it goes on, or by the fertilizer itself ("group by date of application ... or group by fertilizer").
    let GBY = 'type';
    try { const g = localStorage.getItem('rfx-gby'); if (['date', 'type', 'name'].includes(g)) GBY = g; } catch (_) {}
    const tryKey = (t) => t.productId ? 'p' + t.productId : 'n' + String(t.ai || t.name || '').trim().toLowerCase();
    // One grouping for every list of products here: the ones added, and the plan's own. Each item is { t, i, g: its kind
    // tab, k: the product }; chip() draws it, told what its group already says.
    const groupsHtml = (items, chip) => {
        const nm = (x) => String(x.t.name || '');
        const byDay = (a, b) => String(a.t.date || '').localeCompare(String(b.t.date || '')) || nm(a).localeCompare(nm(b));
        const tg = (head, list, drop, k) => `<div class="rfx-tg" style="--k:${k}"><span class="rfx-tg-h">${head}<small>${list.length}</small></span><div class="rfx-chips is-wrap">${list.map((x) => chip(x, drop)).join('')}</div></div>`;
        if (GBY === 'date') {
            // A day each, in order, with the count the board uses that day when the reading has it.
            const by = {};
            items.forEach((x) => { (by[x.t.date || ''] ||= []).push(x); });
            return Object.keys(by).sort().map((d, k) => {
                const dl = d && S.res ? (S.res.days.find((y) => y.d === d) || {}).dl : '';
                const head = d ? `📅 ${esc(DOW[D(d).getDay()] + ', ' + sayY(d))}${dl ? `<em>${esc(dl)}</em>` : ''}` : '📅 No day yet';
                return tg(head, by[d].sort((a, b) => a.g.localeCompare(b.g) || nm(a).localeCompare(nm(b))), 'date', k);
            }).join('');
        }
        if (GBY === 'name') {
            // A fertilizer each, by name, its days in order under it.
            const by = {};
            items.forEach((x) => { (by[x.k] ||= []).push(x); });
            return Object.values(by).sort((a, b) => nm(a[0]).localeCompare(nm(b[0]))).map((list, k) => {
                const tab = PICK_TABS.find(([g]) => g === list[0].g) || PICK_TABS[0];
                return tg(`${tab[1]} ${esc(list[0].t.name)}`, list.sort(byDay), 'name', k);
            }).join('');
        }
        const by = {};
        items.forEach((x) => { (by[x.g] ||= []).push(x); });
        return PICK_TABS.filter(([g]) => by[g]).map(([g, ic, label], k) => tg(`${ic} ${esc(label)}`, by[g].sort(byDay), '', k)).join('');
    };
    const tryItems = () => S.tries.map((t, i) => ({ t, i, g: groupOfTry(t), k: tryKey(t) })).filter((x) => !x.t.water);
    const triesGroupsHtml = () => groupsHtml(tryItems(), (x, drop) => tryChip(x.t, x.i, drop));
    /* What the plan puts on already: the protocol's own tasks, or the season's, as the reading on screen counts them
       (a saved reading's own, as it was saved). Not the ones added here, not the waterings. */
    // Its kind as the book says it; a fertilizer the book does not know goes by how it goes on, anything else under Others.
    const planKind = (a) => {
        const w = String(a.kindWord || '').toLowerCase();
        if (TYPE_WORD[w]) return TYPE_WORD[w];
        return /fertili/.test(w) || (!w && (Array.isArray(a.carries) ? a.carries.length : a.carries)) ? (a.form === 'foliar' ? 'foliar' : 'granular') : '';
    };
    // Each line of the plan once, by its key; then those taken out here, from what they were, so they can come back.
    const planItems = () => {
        const seen = new Set();
        const out = [];
        const item = (t, pk, ch) => ({ t, pk, ch, g: TAB_OF[planKind(t)] || 'other', k: 'n' + String(t.name || '').trim().toLowerCase() });
        ((S.res && S.res.apps) || []).forEach((a) => {
            if (a.try || a.water || a.form === 'water') return;
            const pk = a.pk || 'x' + a.date + '|' + a.name + '|' + (a.amount || '');
            if (seen.has(pk)) return;
            seen.add(pk);
            const ch = a.pk ? S.plan[a.pk] || null : null;
            out.push(item(ch && ch.drop && ch.o ? { ...a, ...ch.o } : a, pk, ch));
        });
        Object.entries(S.plan).forEach(([pk, ch]) => { if (ch && ch.drop && ch.o && !seen.has(pk)) { seen.add(pk); out.push(item({ ...ch.o }, pk, ch)); } });
        return out;
    };
    const UNDO_I = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14L4 9l5-5M4 9h10.5a5.5 5.5 0 010 11H11"/></svg>';
    const X_I = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    // A line of the plan: tapped, it is changed in this reading; x takes it out; the arrow puts it back as the plan has it.
    // An old saved reading's lines have no key and are only listed.
    const planChip = (x, drop) => {
        const t = x.t, ch = x.ch, out = !!(ch && ch.drop), can = !!t.pk || !!(ch && ch.o), nm = esc(t.name);
        const head = drop === 'name' ? `<b>${esc(say(t.date))}</b>` : (out ? `<s>${nmHtml(t.name)}</s>` : nmHtml(t.name));
        const tag = out ? '<em class="rfx-pl-tag">taken out</em>' : ch ? '<em class="rfx-pl-tag">changed</em>' : (t.done ? '<em class="rfx-pl-done">✓ done</em>' : '');
        const pk = esc(x.pk);
        return `<span class="rfx-chip is-plan${out ? ' is-out' : ''}${ch && !out ? ' is-changed' : ''}${can && !out ? ' is-edit' : ''}"${can && !out ? ` data-plan="${pk}" role="button" tabindex="0" title="Tap to change it in this reading"` : ''}>${t.form === 'foliar' ? '💧' : '🟤'} <span class="rfx-ct">${head} <small>${esc([drop ? '' : say(t.date), t.amount || ''].filter(Boolean).join(' · '))}</small>${tag}</span>`
            + (can && !out ? PEN_I : '')
            + (can && ch ? `<button type="button" data-plan-back="${pk}" aria-label="${out ? 'Bring back' : 'Put back as it was'} ${nm}" title="${out ? 'Bring it back' : 'Back as it was'}">${UNDO_I}</button>` : '')
            + (can && !out ? `<button type="button" data-plan-out="${pk}" aria-label="Take ${nm} out of this reading" title="Take it out of this reading">${X_I}</button>` : '')
            + '</span>';
    };
    // What a line was before it was changed here: kept with the change, so it is listed (and compared) as it was.
    const planOrig = (pk) => {
        if (S.plan[pk] && S.plan[pk].o) return S.plan[pk].o;
        const a = ((S.res && S.res.apps) || []).find((x) => x.pk === pk);
        if (!a) return null;
        const share = Number(a.share) > 0 ? Number(a.share) : 1;
        return { name: a.name, date: a.date, amount: a.amount || '', qty: Math.round((Number(a.qty) || 0) * share * 1000) / 1000, unit: a.unit || '', form: a.form || '', kindWord: a.kindWord || '',
            carries: !!(a.carries || []).length, done: !!a.done };
    };
    // The area the amounts are for: the lot's on a season; on a protocol, the hectares its amounts are written for.
    const areaHa = () => (PB ? Number(S.pb?.ha) || 1 : lotOf()?.ha);
    // Kept plan changes from a saved reading or this device: an object of keys, never a list.
    function planIn(v) { return v && typeof v === 'object' && !Array.isArray(v) ? v : {}; }
    const planChanged = () => { paintPlan(); run(); };
    let planOpen = false;
    try { planOpen = localStorage.getItem('rfx-plan-open') === '1'; } catch (_) {}
    // Painted again with every reading; the same list is left alone, so its chips do not pop at each count.
    const paintPlan = () => {
        const box = $('[data-rfx-plan]');
        const items = planItems();
        const names = [...new Set(items.map((x) => x.t.name))];
        const peek = names.slice(0, 3).join(', ') + (names.length > 3 ? ` and ${names.length - 3} more` : '');
        const nCh = items.filter((x) => x.ch).length;
        const html = items.length ? `<button type="button" class="rfx-pa-h" data-rfx-plan-t aria-expanded="false"><b>${PB ? 'Already in the protocol' : 'Already on the plan'}</b><small>${items.length}</small>${nCh ? `<small class="is-ch">${nCh} changed here</small>` : ''}<span class="rfx-pa-peek">${esc(peek)}</span>${CHEV}</button>
            <div class="rfx-pa-b"><div class="rfx-pa-in"><p class="rfx-pl-note">${PB ? 'Tasks of the protocol, counted in the reading. Tap one to change its day or amount, or take it out, and see what it does: the protocol itself stays as it is.' : 'Tasks of this lot, counted in the reading. Tap one to change its day or amount, or take it out, and see what it does: your tasks in Activities stay as they are.'}</p><div class="rfx-pa-sc">${groupsHtml(items, planChip)}</div></div></div>` : '';
        box.classList.toggle('is-open', planOpen);
        if (box.__html !== html) { box.innerHTML = html; box.__html = html; }
        box.querySelector('[data-rfx-plan-t]')?.setAttribute('aria-expanded', String(planOpen));
        // Grouping is offered once there are two to group, added or the plan's own.
        gbyBox.hidden = tryItems().length + items.length < 2;
        if (!gbyBox.hidden) requestAnimationFrame(paintGby);
    };
    const gbyBox = $('[data-rfx-gby]');
    // The pill slides under the one picked; measured, since the three words differ in width.
    const paintGby = () => {
        gbyBox.dataset.on = GBY;
        $$('[data-gby]', gbyBox).forEach((b) => b.setAttribute('aria-checked', String(b.dataset.gby === GBY)));
        const b = $(`[data-gby="${GBY}"]`, gbyBox);
        const pill = $('.rfx-gby-pill', gbyBox);
        if (b && b.offsetWidth) { pill.style.width = b.offsetWidth + 'px'; pill.style.transform = `translateX(${b.offsetLeft}px)`; }
    };
    // Measured again when the sheet opens or its width changes (the words change width with the font and the screen).
    if (typeof ResizeObserver === 'function') new ResizeObserver(() => { if (!gbyBox.hidden) paintGby(); }).observe(gbyBox);
    gbyBox.addEventListener('click', (e) => {
        const b = e.target.closest('[data-gby]');
        if (!b || b.dataset.gby === GBY) return;
        GBY = b.dataset.gby;
        try { localStorage.setItem('rfx-gby', GBY); } catch (_) {}
        paintGby();
        const boxes = [$('[data-rfx-tries]'), $('[data-rfx-plan]')];
        boxes.forEach((box) => box.classList.remove('is-regroup'));
        paintTries();
        boxes.forEach((box) => { void box.offsetWidth; box.classList.add('is-regroup'); });
    });
    $('[data-rfx-plan]').addEventListener('click', (e) => {
        const back = e.target.closest('[data-plan-back]');
        if (back) { delete S.plan[back.dataset.planBack]; planChanged(); return; }
        const out = e.target.closest('[data-plan-out]');
        if (out) {
            const pk = out.dataset.planOut;
            const o = planOrig(pk);
            if (o) { S.plan[pk] = { drop: true, o }; planChanged(); }
            return;
        }
        const chip = e.target.closest('[data-plan]');
        if (chip) { openPlanEdit(chip.dataset.plan); return; }
        const t = e.target.closest('[data-rfx-plan-t]');
        if (!t) return;
        planOpen = !planOpen;
        try { localStorage.setItem('rfx-plan-open', planOpen ? '1' : '0'); } catch (_) {}
        $('[data-rfx-plan]').classList.toggle('is-open', planOpen);
        t.setAttribute('aria-expanded', String(planOpen));
    });
    $('[data-rfx-plan]').addEventListener('keydown', (e) => {
        const c = e.target.closest('[data-plan]');
        if (c && e.target === c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openPlanEdit(c.dataset.plan); }
    });
    // A watering planned here, as its chip says it.
    const watName = (t) => 'Watering' + (Number(t.qty) > 0 ? `, ${t.qty} ${t.unit === 'mm' ? 'mm' : 'cm'}` : '') + (t.method && t.method !== 'flood' && !isPaddy() ? ` by ${t.method}` : '');
    const watChip = (t, i) => `<span class="rfx-chip is-try is-wat" data-try="${i}" role="button" tabindex="0" title="Tap to change it">💧 ${esc(watName(t))} <small>${esc(say(t.date))}</small>${PEN_I}<button type="button" data-dupe="${i}" aria-label="Make a copy of this watering" title="Make a copy">${COPY_I}</button><button type="button" data-untry="${i}" aria-label="Remove this watering"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`;
    // The Watering row shows while water is checked, or while waterings are planned.
    const paintWrow = () => { $('[data-rfx-wrow]').hidden = !(S.checks.some((k) => k.startsWith('w:')) || S.tries.some((t) => t.water)); };
    const paintTries = () => {
        $('[data-rfx-tries]').innerHTML = triesGroupsHtml();
        paintPlan();
        $('[data-rfx-wtries]').innerHTML = S.tries.map((t, i) => [t, i]).filter(([t]) => t.water).sort((a, b) => String(a[0].date).localeCompare(String(b[0].date))).map(([t, i]) => watChip(t, i)).join('');
        $('[data-rfx-apply]').hidden = !S.tries.length || !(U.apply || (PB && window.pbCoverage));
        paintWrow();
        paintAdded();
    };
    // Added so far, folded to a line by default so the picker stays short; opened or closed, it is remembered here.
    let addedOpen = false;
    try { addedOpen = localStorage.getItem('rfx-added-open') === '1'; } catch (_) {}
    const paintAdded = () => {
        const box = $('[data-rfx-pick-added]');
        const list = S.tries.filter((t) => !t.water);
        const n = list.length;
        const names = [...new Set(list.map((t) => t.name))];
        const peek = names.slice(0, 3).join(', ') + (names.length > 3 ? ` and ${names.length - 3} more` : '');
        box.classList.toggle('is-open', addedOpen);
        box.innerHTML = n ? `<button type="button" class="rfx-pa-h" data-rfx-pa-t aria-expanded="${addedOpen}"><b>Added so far</b><small>${n}</small><span class="rfx-pa-peek">${esc(peek)}</span><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
            <div class="rfx-pa-b"><div class="rfx-pa-in"><div class="rfx-pa-sc">${triesGroupsHtml()}</div></div></div>` : '';
        $('[data-rfx-pick-n]').textContent = n ? `${n} added` : 'Nothing added yet';
    };
    $('[data-rfx-pick-added]').addEventListener('click', (e) => {
        const t = e.target.closest('[data-rfx-pa-t]');
        if (!t) return;
        addedOpen = !addedOpen;
        try { localStorage.setItem('rfx-added-open', addedOpen ? '1' : '0'); } catch (_) {}
        $('[data-rfx-pick-added]').classList.toggle('is-open', addedOpen);
        t.setAttribute('aria-expanded', String(addedOpen));
    });
    // A copy, right after the one it copies, opened to change its day (the owner, 2026-10-30: "have a duplicate button").
    const dupeTry = (i) => {
        const t = S.tries[i];
        if (!t) return;
        S.tries.splice(i + 1, 0, { ...t });
        paintTries();
        if (tryBox.hidden) run(); else pickDirty = true;
        if (t.water) openWat(i + 1); else openEdit(i + 1);
        window.toast?.('A copy is made. Change its day or amount.');
    };
    const onTries = (e) => {
        const b = e.target.closest('[data-untry]');
        if (b) {
            S.tries.splice(Number(b.dataset.untry), 1);
            paintTries();
            if (tryBox.hidden) run(); else pickDirty = true;
            return;
        }
        const d = e.target.closest('[data-dupe]');
        if (d) { dupeTry(Number(d.dataset.dupe)); return; }
        const c = e.target.closest('[data-try]');
        if (c) openTry(Number(c.dataset.try));
    };
    const openTry = (i) => { if (S.tries[i]?.water) openWat(i); else openEdit(i); };
    const onTriesKey = (e) => {
        const c = e.target.closest('[data-try]');
        if (c && e.target === c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openTry(Number(c.dataset.try)); }
    };
    ['[data-rfx-tries]', '[data-rfx-pick-added]', '[data-rfx-wtries]'].forEach((sel) => { $(sel).addEventListener('click', onTries); $(sel).addEventListener('keydown', onTriesKey); });
    /* A try, opened to change it. */
    const editBox = $('[data-rfx-edit]');
    const editHow = $('[data-rfx-edit-how]');
    let editing = -1;
    // The window says what is being changed: a product added here, or a line of the plan changed in this reading only.
    const editFor = (plan) => {
        $('#rfxEditT', editBox).textContent = plan ? 'Change it in this reading' : 'Change this fertilizer';
        $('[data-rfx-edit-sub]').textContent = plan ? (PB ? 'Its day, how it goes on and how much. The protocol itself stays as it is.' : 'Its day, how it goes on and how much. Your task in Activities stays as it is.') : 'Its day, how it goes on, and how much';
        $('[data-rfx-edit-dupe]').hidden = !!plan;
        $('[data-rfx-edit-del-t]').textContent = plan ? 'Take it out of this reading' : 'Take it out';
    };
    // An amount's unit as the window offers it; one it does not offer is added for this line.
    const UNIT_OF = { bag: 'bags', bags: 'bags', sack: 'bags', sacks: 'bags', kg: 'kg', kgs: 'kg', kilo: 'kg', kilos: 'kg', l: 'L', liter: 'L', liters: 'L', litre: 'L', ml: 'mL', g: 'g', gram: 'g', grams: 'g' };
    const setUnit = (sel, u) => {
        const v = UNIT_OF[String(u || '').toLowerCase()] || String(u || '').trim() || 'kg';
        $$('option[data-extra]', sel).forEach((o) => { if (o.value !== v) o.remove(); });
        if (![...sel.options].some((o) => o.value === v)) { const o = document.createElement('option'); o.value = v; o.textContent = v; o.dataset.extra = '1'; sel.appendChild(o); }
        sel.value = v;
    };
    const openPlanEdit = (pk) => {
        const a = ((S.res && S.res.apps) || []).find((x) => x.pk === pk);
        const o = planOrig(pk);
        if (!a || !o) return;
        const ch = S.plan[pk] && !S.plan[pk].drop ? S.plan[pk] : null;
        editing = 'p:' + pk;
        editFor(true);
        $('[data-rfx-edit-p]').innerHTML = `<em>${a.form === 'foliar' ? '💧' : '🟤'}</em><span><b>${esc(a.name)}</b><small>${esc((PB ? 'In the protocol' : 'On the plan') + ': ' + [o.date ? say(o.date) : '', o.amount].filter(Boolean).join(' · ') + (ch ? ' · changed here' : ''))}</small></span>`;
        setDate($('[data-rfx-edit-date]'), ch ? ch.date : o.date);
        $('[data-rfx-edit-qty]').value = ch ? (ch.qty || '') : (o.qty || '');
        setUnit($('[data-rfx-edit-unit]'), ch ? ch.unit : o.unit);
        const ha = areaHa();
        $('[data-rfx-edit-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
        setHow(ch && ch.method ? ch.method : (o.form === 'foliar' ? 'foliar' : 'granular'), false, editHow, $('[data-rfx-edit-unit]'));
        editBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => editBox.classList.add('is-on')));
        setTimeout(() => $('[data-date-for="data-rfx-edit-date"]').focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const openEdit = (i) => {
        const t = S.tries[i];
        if (!t) return;
        editing = i;
        editFor(false);
        setUnit($('[data-rfx-edit-unit]'), t.unit || 'kg');
        $('[data-rfx-edit-p]').innerHTML = `<em>${t.method === 'foliar' ? '💧' : '🟤'}</em><span><b>${esc(t.name)}</b><small>${esc(t.line || (t.ai && !t.productId ? 'The active ingredient on its own' : 'Added by you'))}</small></span>`;
        setDate($('[data-rfx-edit-date]'), t.date);
        $('[data-rfx-edit-qty]').value = t.qty || '';
        const ha = areaHa();
        $('[data-rfx-edit-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
        setHow(t.method || 'granular', false, editHow, $('[data-rfx-edit-unit]'));
        editBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => editBox.classList.add('is-on')));
        setTimeout(() => $('[data-date-for="data-rfx-edit-date"]').focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const closeEdit = () => {
        editing = -1;
        editBox.classList.remove('is-on');
        setTimeout(() => { if (editing < 0) editBox.hidden = true; }, calm ? 0 : 260);
    };
    // A change made with the picker open is counted once the picker closes.
    const changedTries = () => { paintTries(); if (tryBox.hidden) run(); else pickDirty = true; };
    editHow.addEventListener('click', (e) => { const b = e.target.closest('[data-how]'); if (b) setHow(b.dataset.how, true, editHow, $('[data-rfx-edit-unit]')); });
    editBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-edit-x]')) { closeEdit(); return; }
        // A line of the plan: taken out, or changed, in this reading only. Changed back to what it was, it is the plan's again.
        if (typeof editing === 'string') {
            const pk = editing.slice(2);
            const o = planOrig(pk);
            if (!o) { closeEdit(); return; }
            if (e.target.closest('[data-rfx-edit-del]')) { S.plan[pk] = { drop: true, o }; closeEdit(); planChanged(); return; }
            if (e.target.closest('[data-rfx-edit-save]')) {
                const date = $('[data-rfx-edit-date]').value;
                if (!date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
                const ch = { date, qty: Number($('[data-rfx-edit-qty]').value) || 0, unit: $('[data-rfx-edit-unit]').value, method: editHow.dataset.on || 'granular' };
                const same = ch.date === o.date && Math.abs(ch.qty - (Number(o.qty) || 0)) < 1e-9 && (UNIT_OF[String(o.unit).toLowerCase()] || o.unit) === ch.unit && ch.method === (o.form === 'foliar' ? 'foliar' : 'granular');
                if (same) delete S.plan[pk]; else S.plan[pk] = { ...ch, o };
                closeEdit();
                planChanged();
            }
            return;
        }
        if (e.target.closest('[data-rfx-edit-del]') && editing >= 0) {
            S.tries.splice(editing, 1);
            closeEdit();
            changedTries();
            return;
        }
        if (e.target.closest('[data-rfx-edit-dupe]') && editing >= 0) {
            const i = editing;
            closeEdit();
            setTimeout(() => dupeTry(i), calm ? 0 : 280);
            return;
        }
        if (e.target.closest('[data-rfx-edit-save]') && editing >= 0) {
            const date = $('[data-rfx-edit-date]').value;
            if (!date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
            S.tries[editing] = { ...S.tries[editing], date, qty: Number($('[data-rfx-edit-qty]').value) || 0, unit: $('[data-rfx-edit-unit]').value, method: editHow.dataset.on || 'granular' };
            closeEdit();
            changedTries();
        }
    });
    /* ---------------- waterings: the ones you plan ---------------- */
    const watBox = $('[data-rfx-wat]');
    const watHow = $('[data-rfx-wat-how]');
    let watEditing = -1;
    const METH = [['furrow', 'Furrows', 'Water down the rows'], ['flood', 'Flooding', 'Basins or the whole field'], ['sprinkler', 'Sprinklers', 'Rain from above'],
        ['drip', 'Drip lines', 'A little at each plant'], ['hose', 'Hose or can', 'By hand, plant by plant']];
    const isPaddy = () => (S.res?.lot?.water || '') === 'flooded';
    const paintMeth = () => { $$('[data-meth]', watHow).forEach((b) => { const on = b.dataset.meth === watHow.dataset.on; b.classList.toggle('is-on', on); b.setAttribute('aria-checked', String(on)); }); };
    const openWat = (i = -1) => {
        watEditing = i;
        const last = S.tries.filter((t) => t.water).map((t) => t.date).sort().pop();
        const t = i >= 0 && S.tries[i] ? S.tries[i] : { date: last ? addDays(last, 7) : (S.res?.today || iso(new Date())), qty: 0, unit: 'cm', method: null };
        const paddy = isPaddy();
        $('[data-rfx-wat-t]').textContent = i >= 0 ? 'Change this watering' : 'A watering you plan';
        setDate($('[data-rfx-wat-date]'), t.date);
        $('[data-rfx-wat-qty]').value = Number(t.qty) > 0 ? t.qty : '';
        $('[data-rfx-wat-unit]').value = t.unit === 'mm' ? 'mm' : 'cm';
        $('[data-rfx-wat-ql]').innerHTML = paddy ? 'Standing water it leaves <i>(optional)</i>' : 'How much water <i>(optional)</i>';
        $('[data-rfx-wat-hint]').textContent = paddy
            ? 'A paddy is flooded. Leave it empty for about 5 cm of standing water.'
            : 'Leave it empty to fill the root zone, as far as one watering of that kind can. Given, it counts the share that reaches the roots.';
        watHow.hidden = paddy;
        watHow.dataset.on = t.method && t.method !== 'flood' ? t.method : (t.method || 'furrow');
        watHow.innerHTML = METH.map(([k, n, w]) => `<button type="button" role="radio" data-meth="${k}" aria-checked="false"><b>${esc(n)}</b><small>${esc(w)}</small></button>`).join('');
        paintMeth();
        $('[data-rfx-wat-del]').hidden = i < 0;
        $('[data-rfx-wat-dupe]').hidden = i < 0;
        $('[data-rfx-wat-save]').textContent = i >= 0 ? 'Save changes' : 'Add it';
        watBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => watBox.classList.add('is-on')));
        setTimeout(() => $('[data-date-for="data-rfx-wat-date"]').focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const closeWat = () => {
        watEditing = -1;
        watBox.classList.remove('is-on');
        setTimeout(() => { if (!watBox.classList.contains('is-on')) watBox.hidden = true; }, calm ? 0 : 260);
    };
    $('[data-rfx-wat-open]').addEventListener('click', () => openWat(-1));
    watBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-wat-x]')) { closeWat(); return; }
        const m = e.target.closest('[data-meth]');
        if (m) { watHow.dataset.on = m.dataset.meth; paintMeth(); return; }
        if (e.target.closest('[data-rfx-wat-del]') && watEditing >= 0) {
            S.tries.splice(watEditing, 1);
            closeWat();
            changedTries();
            return;
        }
        if (e.target.closest('[data-rfx-wat-dupe]') && watEditing >= 0) {
            const i = watEditing;
            closeWat();
            setTimeout(() => dupeTry(i), calm ? 0 : 280);
            return;
        }
        if (e.target.closest('[data-rfx-wat-save]')) {
            const date = $('[data-rfx-wat-date]').value;
            if (!date) { window.toast?.('Pick the day you would water.', 'error'); return; }
            const t = { water: 'irrigate', name: 'Watering', t: 'water', date, qty: Number($('[data-rfx-wat-qty]').value) || 0, unit: $('[data-rfx-wat-unit]').value === 'mm' ? 'mm' : 'cm',
                method: isPaddy() ? 'flood' : (watHow.dataset.on || 'furrow') };
            if (watEditing >= 0 && S.tries[watEditing]) S.tries[watEditing] = { ...S.tries[watEditing], ...t }; else S.tries.push(t);
            closeWat();
            changedTries();
        }
    });
    /* The picker: open, close (counted again only when something changed), its tabs. */
    let pickDirty = false;
    const openPick = () => {
        resetTry();
        paintAdded();
        tryBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => tryBox.classList.add('is-on')));
        findProducts();
        if (matchMedia('(pointer: fine)').matches) setTimeout(() => $('[data-rfx-q]').focus({ preventScroll: true }), calm ? 0 : 220);
    };
    const closePick = () => {
        tryBox.classList.remove('is-on');
        setTimeout(() => { if (!tryBox.classList.contains('is-on')) tryBox.hidden = true; }, calm ? 0 : 260);
        if (pickDirty) { pickDirty = false; run(); }
    };
    const paintPickTabs = () => {
        $('[data-rfx-ptabs]').innerHTML = PICK_TABS.map(([k, ic, label]) => `<button type="button" role="tab" data-ptab="${k}" class="${S.ptab === k ? 'is-on' : ''}" aria-selected="${S.ptab === k}">${ic} ${esc(label)}</button>`).join('');
    };
    $('[data-rfx-try-open]').addEventListener('click', openPick);
    tryBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-try-x]')) { closePick(); return; }
        const tb = e.target.closest('[data-ptab]');
        if (tb && tb.dataset.ptab !== S.ptab) {
            S.ptab = tb.dataset.ptab;
            paintPickTabs();
            $('[data-rfx-picked]').hidden = true;
            findProducts();
        }
    });
    const resetTry = () => {
        S.picked = null;
        S.ptab ||= 'fert';
        paintPickTabs();
        $('[data-rfx-q]').value = '';
        $('[data-rfx-results]').innerHTML = '';
        $('[data-rfx-results]').hidden = false;
        $('[data-rfx-picked]').hidden = true;
        $('[data-rfx-look]').hidden = true;
        setDate($('[data-rfx-date]'), iso(new Date()));
        $('[data-rfx-qty]').value = '';
        const ha = areaHa();
        $('[data-rfx-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
    };
    // Granular or foliar: the pill slides to the pick; a unit that cannot go with it changes too.
    const setHow = (m, fixUnit = true, box = $('[data-rfx-how]'), u = $('[data-rfx-unit]')) => {
        if (box.hasAttribute('data-rfx-how') && S.picked) S.picked.method = m;
        box.dataset.on = m;
        $$('[data-how]', box).forEach((b) => b.setAttribute('aria-checked', String(b.dataset.how === m)));
        if (!fixUnit) return;
        if (m === 'granular' && ['L', 'mL'].includes(u.value)) u.value = 'kg';
        if (m === 'foliar' && u.value === 'bags') u.value = 'kg';
    };
    $('[data-rfx-how]').addEventListener('click', (e) => { const b = e.target.closest('[data-how]'); if (b) setHow(b.dataset.how); });
    const typeIcon = (t) => ({ insecticide: '🐛', fungicide: '🍄', herbicide: '🌿', molluscicide: '🐌', rodenticide: '🐀', nematicide: '🪱', acaricide: '🕷️', foliar: '💧', soluble: '💧', granular: '🧪', biostimulant: '🌱', biological: '🦠', amendment: '🪨' }[t] || '🧴');
    let qTimer = 0, findSeq = 0;
    // The products of the tab, or those matching what is typed within it; each one already added says so.
    const findProducts = () => {
        clearTimeout(qTimer);
        const q = $('[data-rfx-q]').value.trim();
        const tab = S.ptab || 'all';
        const res = $('[data-rfx-results]');
        $('[data-rfx-picked]').hidden = true;
        res.hidden = false;
        if (q.length < 2 && tab === 'all') { res.innerHTML = '<p class="rfx-pick-hint">Type a product, its company or its active ingredient, or pick a kind above to look along its shelf.</p>'; $('[data-rfx-look]').hidden = true; return; }
        const seq = ++findSeq;
        qTimer = setTimeout(async () => {
            try {
                const lot = lotOf();
                const r = await window.api(U.products + '&q=' + encodeURIComponent(q) + '&tab=' + encodeURIComponent(tab === 'all' ? '' : tab) + '&crop=' + encodeURIComponent(lot?.cropKey || ''));
                if (seq !== findSeq) return;
                const items = r.data.items || [];
                const ing = r.data.ingredient;
                const n = (pid) => S.tries.filter((t) => String(t.productId) === String(pid)).length;
                let html = items.map((p, k) => { const c = n(p.id); return `<button type="button" class="rfx-res" data-pid="${p.id}" style="--k:${Math.min(k, 20)}"><em>${typeIcon(p.t)}</em><span><b>${esc(p.b)}</b><small>${esc([p.type, p.grade, (p.ai || []).join(' + '), p.c].filter(Boolean).join(' · '))}</small></span>${c ? `<i class="rfx-res-n">Added${c > 1 ? ' ×' + c : ''}</i>` : ''}</button>`; }).join('');
                if (ing) html = `<button type="button" class="rfx-res" data-ai="${esc(ing.name)}"><em>⚗️</em><span><b>${esc(ing.name)}</b><small>The active ingredient on its own${ing.known ? '' : ' · days estimated from its group'}</small></span></button>` + html;
                res.innerHTML = html || `<p class="rfx-pick-hint">${q.length >= 2 ? 'Nothing in our book by that name' + (tab !== 'all' ? ' under this kind. Try All.' : '.') : 'Nothing on this shelf yet.'}</p>`;
                S.found = { items, ing };
                const look = $('[data-rfx-look]');
                look.hidden = q.length < 2;
                $('[data-rfx-look-name]').value = items.length ? '' : q;
                paintLookWhy();
            } catch (err) { res.innerHTML = `<small class="rfx-muted" style="color:var(--rx-gap-ink)">${esc(err.message)}</small>`; }
        }, q.length >= 2 ? 260 : 0);
    };
    $('[data-rfx-q]').addEventListener('input', findProducts);
    $('[data-rfx-results]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-pid], [data-ai]');
        if (!b) return;
        if (b.dataset.pid) {
            const p = S.found.items.find((x) => String(x.id) === b.dataset.pid);
            S.picked = { productId: p.id, name: p.b, line: [p.type, p.grade, (p.ai || []).join(' + ')].filter(Boolean).join(' · '), t: p.t, granular: !!p.fit?.granular && !p.fit?.spray };
        } else {
            S.picked = { ai: b.dataset.ai, name: b.dataset.ai, line: 'The active ingredient on its own', t: '' };
        }
        showPicked();
    });
    const showPicked = () => {
        const p = S.picked;
        $('[data-rfx-picked-p]').innerHTML = `<em>${typeIcon(p.t)}</em><span><b>${esc(p.name)}</b><small>${esc(p.line)}</small></span>`;
        $('[data-rfx-unit]').value = p.granular || ['granular', 'amendment'].includes(p.t) ? 'bags' : (['insecticide', 'fungicide', 'herbicide', 'foliar', 'soluble'].includes(p.t) ? 'mL' : 'kg');
        // A spray or a leaf feed starts as foliar; fertilizer, salts, baits and anything else start as granular.
        setHow(p.method || (['insecticide', 'fungicide', 'herbicide', 'acaricide', 'nematicide', 'foliar', 'soluble', 'biostimulant', 'biological'].includes(p.t) && !p.granular ? 'foliar' : 'granular'), false);
        $('[data-rfx-results]').hidden = true;
        $('[data-rfx-look]').hidden = true;
        $('[data-rfx-picked]').hidden = false;
        $('[data-rfx-picked]').scrollIntoView({ block: 'nearest', behavior: calm ? 'auto' : 'smooth' });
    };
    // Back to the list, the search and the tab as they were.
    $('[data-rfx-try-cancel]').addEventListener('click', () => { S.picked = null; findProducts(); });
    $('[data-rfx-try-add]').addEventListener('click', () => {
        const p = S.picked;
        const date = $('[data-rfx-date]').value;
        if (!p || !date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
        S.tries.push({ productId: p.productId || null, ai: p.ai || null, name: p.name, line: p.line || '', t: p.t || '', date, qty: Number($('[data-rfx-qty]').value) || 0, unit: $('[data-rfx-unit]').value, method: p.method || 'granular' });
        pickDirty = true;
        S.picked = null;
        paintTries();
        findProducts();
        const box = $('[data-rfx-pick-added]');
        if (!calm) { box.classList.remove('is-flash'); void box.offsetWidth; box.classList.add('is-flash'); }
        box.scrollIntoView({ block: 'nearest', behavior: calm ? 'auto' : 'smooth' });
        window.toast?.(`${p.name} is added. Pick another, or tap Done.`);
    });

    /* ---------------- a what-if lot ---------------- */
    /* Its own name, place and every field question, on the crop, days and tasks of a lot of the season; read like any
       lot, saved with the reading only (the owner, 2026-10-30: "this one will not be saved as a lot, only for
       experimentation reference that can be saved in that range effect modal reading"). */
    const whatBox = $('[data-rfx-what]');
    let W = null;
    const whatCount = (a) => (window.aneeFieldProfile ? window.aneeFieldProfile.count(a || {}) : Object.keys(a || {}).length);
    const whatHtml = () => {
        const lots = S.opts.lots || [];
        const base = lots.find((l) => l.id === W.base) || lots[0];
        const n = whatCount(W.answers);
        const from = base && whatCount(base.answers);
        const words = n && window.aneeFieldProfile?.words ? window.aneeFieldProfile.words(W.answers) : '';
        return `<div class="rfx-day-h"><div><b id="rfxWhatT">🧪 A what-if lot</b><small>Another soil and field on the same crop, days and tasks, to see what changes. It is never saved as a lot: it stays with this reading.</small></div><button type="button" class="rfx-x" data-rfx-what-x aria-label="Close">${'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'}</button></div>
            <label class="rfx-w-f"><span>Its name</span><input type="text" maxlength="80" data-w-name value="${esc(W.name)}" placeholder="Like Sandy and acid"></label>
            <div class="rfx-w-f"><span>On the crop, days and tasks of</span><div class="rfx-chips is-wrap">${lots.map((l) => `<button type="button" class="rfx-chip ${l.id === W.base ? 'is-on' : ''}" data-w-base="${l.id}">${esc(l.icon)} ${esc(l.name)} <small>${esc(l.crop.split(',')[0])}</small></button>`).join('')}</div></div>
            <label class="rfx-w-f"><span>Where it is <i>(the area's soil and rain fill what is not answered)</i></span><input type="text" maxlength="160" data-w-place value="${esc(W.place)}" placeholder="Town, province"></label>
            <div class="rfx-w-q"><div><b>${n ? `${n} field answers` : 'No field answers yet'}</b><small>${words ? esc(words).slice(0, 180) : 'The soil, the water, the acidity, the land and the last season: every question a lot is asked.'}</small></div>
                <div class="rfx-w-qb"><button type="button" class="rfx-btn is-go" data-w-ask>${n ? 'Change the answers' : 'Answer the questions'}</button>${from ? `<button type="button" class="rfx-btn is-ghost" data-w-copy>Start from ${esc(base.name)}'s answers</button>` : ''}${n ? '<button type="button" class="rfx-btn is-ghost" data-w-clear>Clear them</button>' : ''}</div></div>
            <div class="rfx-pick-foot"><span>${S.custom && S.useCustom ? '<button type="button" class="rfx-btn is-ghost is-del" data-w-off>Back to the lot itself</button>' : ''}</span><span style="display:flex;gap:.4rem"><button type="button" class="rfx-btn is-ghost" data-rfx-what-x>Cancel</button><button type="button" class="rfx-btn is-go" data-w-use>Use this lot</button></span></div>`;
    };
    const paintWhat = () => { $('[data-rfx-what-c]').innerHTML = whatHtml(); };
    const readWhat = () => {
        const n = $('[data-w-name]'), p = $('[data-w-place]');
        if (n) W.name = n.value.trim() || 'What if';
        if (p) W.place = p.value.trim();
    };
    const openWhat = () => {
        const lot = lotOf() || (S.opts.lots || [])[0];
        W = S.custom ? JSON.parse(JSON.stringify(S.custom)) : { name: 'What if', base: lot?.id, place: lot?.place || '', answers: {} };
        if (!(S.opts.lots || []).some((l) => l.id === W.base)) W.base = lot?.id;
        paintWhat();
        whatBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => whatBox.classList.add('is-on')));
    };
    const closeWhat = () => {
        whatBox.classList.remove('is-on');
        setTimeout(() => { if (!whatBox.classList.contains('is-on')) whatBox.hidden = true; }, calm ? 0 : 260);
    };
    whatBox.addEventListener('click', async (e) => {
        if (e.target.closest('[data-rfx-what-x]')) { closeWhat(); return; }
        const bb = e.target.closest('[data-w-base]');
        if (bb) { readWhat(); W.base = Number(bb.dataset.wBase); const l = (S.opts.lots || []).find((x) => x.id === W.base); if (l && !W.place) W.place = l.place || ''; paintWhat(); return; }
        if (e.target.closest('[data-w-copy]')) { readWhat(); const l = (S.opts.lots || []).find((x) => x.id === W.base); W.answers = JSON.parse(JSON.stringify(l?.answers || {})); paintWhat(); window.toast?.(`${l?.name || 'The lot'}'s answers are copied. Change what differs.`); return; }
        if (e.target.closest('[data-w-clear]')) { readWhat(); W.answers = {}; paintWhat(); return; }
        if (e.target.closest('[data-w-ask]')) {
            readWhat();
            const FP = window.aneeFieldProfile;
            if (!FP || !FP.ask) { window.toast?.('The questions could not open. Please reload the page.', 'error'); return; }
            const lot = (S.opts.lots || []).find((x) => x.id === W.base);
            const got = await FP.ask({ all: true, crop: lot?.cropKey || null, value: W.answers || {}, title: W.name, sub: 'The what-if lot: change any answer, then save' });
            if (got) { W.answers = got; paintWhat(); }
            return;
        }
        if (e.target.closest('[data-w-off]')) { S.useCustom = false; closeWhat(); paintLots(); run(); keep(); return; }
        if (e.target.closest('[data-w-use]')) {
            readWhat();
            S.custom = W;
            S.useCustom = true;
            S.lot = W.base;
            closeWhat();
            paintLots();
            paintPicks();
            run();
            keep();
        }
    });

    /* ---------------- onto the plan ---------------- */
    /* The products planned here, put on the season's plan (the owner, 2026-10-30: "apply the product you plan to apply
       listed into the current activity plan (do not remove the exising items in the activities, and do not duplicate
       the fertilizer in the activity day if its already there"). First what each one would do, then done; those on
       the plan leave the list (they are tasks now) and the board is read again when the planner closes. */
    const applyBox = $('[data-rfx-apply-box]');
    let applyRows = null, applyBusy = false;
    const AP_SAY = { new: 'New task', join: 'Joins', there: 'Already on the plan', twice: 'Twice in your list', bad: 'Cannot go on' };
    const applyHead = () => `<div class="rfx-day-h"><div><b id="rfxApplyT">${PB ? 'Put them in the protocol' : 'Put them on the plan'}</b><small>${PB ? 'Each one as a task on its day of the count. Nothing in the protocol is removed or changed, and a product already there that day is not added again.' : `For ${esc(lotOf()?.name || 'this lot')}. Nothing on the board is removed or changed, and a product already there that day is not added again.`}</small></div><button type="button" class="rfx-x" data-rfx-apply-x aria-label="Close">${'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'}</button></div>`;
    const UNITS_OPT = ['bags', 'kg', 'L', 'mL', 'g'];
    const paintApply = () => {
        const rows = (applyRows || []).slice().sort((a, b) => String(a.date).localeCompare(String(b.date)));
        const go = rows.filter((r) => r.status === 'new' || r.status === 'join').length;
        const amt = (r) => {
            const t = S.tries[r.i];
            if (t && t.water) return `<span class="rfx-ap-q">${Number(t.qty) > 0 ? esc(String(t.qty)) + ' ' + esc(t.unit || 'cm') : (isPaddy() ? 'about 5 cm' : 'fills the root zone')}</span>`;
            if (!t || !['new', 'join'].includes(r.status)) return r.qty ? `<span class="rfx-ap-q">${esc(String(r.qty))} ${esc(r.unit)}</span>` : '';
            if (Number(t.qty) > 0) return `<span class="rfx-ap-q">${esc(String(t.qty))} ${esc(t.unit)}</span>`;
            const u = UNITS_OPT.includes(t.unit) ? t.unit : 'kg';
            return `<span class="rfx-ap-q"><input type="number" min="0" step="any" inputmode="decimal" placeholder="Amount" data-ap-qty="${r.i}" aria-label="Amount of ${esc(r.name)}"> <select data-ap-unit="${r.i}" aria-label="Unit">${UNITS_OPT.map((x) => `<option${x === u ? ' selected' : ''}>${x}</option>`).join('')}</select></span>`;
        };
        $('[data-rfx-apply-c]').innerHTML = applyHead()
            + `<ul class="rfx-ap-list">${rows.map((r, k) => `<li class="rfx-ap-row is-${r.status}" style="--k:${Math.min(k, 20)}"><span class="rfx-ap-d">${r.date ? esc(say(r.date)) : ''}</span>
                <span class="rfx-ap-n"><b>${esc(r.name)}</b><small><span class="rfx-ap-tag">${AP_SAY[r.status] || ''}</span>${esc(r.task || '')}${r.note ? '. ' + esc(r.note) : ''}</small></span>${amt(r)}</li>`).join('')}</ul>`
            + (rows.some((r) => ['new', 'join'].includes(r.status) && !S.tries[r.i]?.water && !(Number(S.tries[r.i]?.qty) > 0)) ? '<p class="rfx-pick-hint">A product with no amount goes on as one usual dose, as its label says; type the amount to put it in now.</p>' : '')
            + `<div class="rfx-pick-foot"><span>${go ? `${go} to put ${PB ? 'in the protocol' : 'on the plan'}` : 'Nothing new to put in'}</span><span style="display:flex;gap:.4rem"><button type="button" class="rfx-btn is-ghost" data-rfx-apply-x>Cancel</button><button type="button" class="rfx-btn is-go" data-rfx-apply-go ${go ? '' : 'disabled'}>Put ${go || ''} ${PB ? 'in the protocol' : 'on the plan'}</button></span></div>`;
    };
    const openApply = async () => {
        if (!S.tries.length || applyBusy) return;
        applyRows = null;
        $('[data-rfx-apply-c]').innerHTML = applyHead() + '<div class="rfx-loading"><span></span><span></span><span></span></div>';
        applyBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => applyBox.classList.add('is-on')));
        try {
            const r = PB ? { data: window.pbCoverage.check(S.tries, S.pb) } : await window.api(U.applyCheck, { method: 'POST', body: { lotId: S.lot, tries: S.tries } });
            applyRows = r.data.rows || [];
            paintApply();
        } catch (err) {
            $('[data-rfx-apply-c]').innerHTML = applyHead() + `<p class="rfx-pick-hint" style="color:var(--rx-gap-ink)">${esc(err.message || 'It could not be read. Please try again.')}</p>`;
        }
    };
    const closeApply = () => {
        applyBox.classList.remove('is-on');
        setTimeout(() => { if (!applyBox.classList.contains('is-on')) applyBox.hidden = true; }, calm ? 0 : 260);
    };
    $('[data-rfx-apply]').addEventListener('click', openApply);
    applyBox.addEventListener('input', (e) => {
        const q = e.target.closest('[data-ap-qty]');
        if (q && S.tries[q.dataset.apQty]) { S.tries[q.dataset.apQty].qty = Number(q.value) || 0; pickDirty = true; }
        const u = e.target.closest('[data-ap-unit]');
        if (u && S.tries[u.dataset.apUnit]) S.tries[u.dataset.apUnit].unit = u.value;
    });
    applyBox.addEventListener('change', (e) => {
        const u = e.target.closest('[data-ap-unit]');
        if (u && S.tries[u.dataset.apUnit]) S.tries[u.dataset.apUnit].unit = u.value;
    });
    applyBox.addEventListener('click', async (e) => {
        if (e.target.closest('[data-rfx-apply-x]')) { closeApply(); return; }
        if (e.target.closest('[data-rfx-apply-board]')) { closeApply(); close(); return; }
        const go = e.target.closest('[data-rfx-apply-go]');
        if (!go || applyBusy) return;
        applyBusy = true;
        go.disabled = true;
        go.textContent = 'Putting them on…';
        try {
            const r = PB ? window.pbCoverage.add(S.tries, S.pb) : await window.api(U.apply, { method: 'POST', body: { lotId: S.lot, tries: S.tries } });
            const rows = r.data.rows || [];
            const m = r.data.made || {};
            // On the plan now (or already there): they leave the list, the plan's own lines count them from here.
            const gone = new Set(rows.filter((x) => ['new', 'join', 'there', 'twice'].includes(x.status)).map((x) => x.i));
            S.tries = S.tries.filter((t, i) => !gone.has(i));
            S.boardStale = !PB && (S.boardStale || (m.lines || 0) > 0);
            paintTries();
            keep();
            run();
            $('[data-rfx-apply-c]').innerHTML = applyHead() + `<div class="rfx-ap-done"><img src="${SRV.x_happy}" alt=""><b>${esc(r.message || 'Done.')}</b>
                <p>${m.join ? `${m.join} went into tasks already on that day. ` : ''}${m.there ? `${m.there} ${m.there === 1 ? 'was' : 'were'} on the plan already, left as ${m.there === 1 ? 'it is' : 'they are'}. ` : ''}${PB ? 'They are tasks of the protocol now: change them there, and Undo takes them out.' : 'Give them to your workers, link them to the shed or change the amounts in Activities.'}</p></div>
                <div class="rfx-pick-foot"><span></span><span style="display:flex;gap:.4rem"><button type="button" class="rfx-btn is-ghost" data-rfx-apply-x>Keep planning</button>${m.lines && !PB ? '<button type="button" class="rfx-btn is-go" data-rfx-apply-board>See them on the board</button>' : ''}</span></div>`;
            window.toast?.(r.message, 'success');
        } catch (err) {
            go.disabled = false;
            go.textContent = 'Try again';
            window.toast?.(err.message || 'They could not be put on the plan. Please try again.', 'error');
        } finally { applyBusy = false; }
    });

    /* ---------------- Anee looks it up ---------------- */
    const paintLookWhy = () => {
        const L = S.opts.lookup || {};
        const btn = $('[data-rfx-look-go]');
        btn.disabled = !L.canUse;
        btn.textContent = L.canUse ? `Look it up · ${L.price} credits` : 'Look it up';
        $('[data-rfx-look-why]').textContent = L.canUse ? (L.unlimited ? '' : `You have ${Math.floor(L.balance)} credits. Nothing is charged if she cannot finish.`) : (L.whyNot || '');
    };
    $('[data-rfx-look-go]').addEventListener('click', async () => {
        const name = $('[data-rfx-look-name]').value.trim(), ai = $('[data-rfx-look-ai]').value.trim();
        if (!name && !ai) { window.toast?.('Type the product\'s name, its active ingredient, or both.', 'error'); return; }
        const L = S.opts.lookup;
        window.aneeWait?.show({ title: 'Anee is looking it up…', sub: 'The label, the maker and how long it keeps working. About a minute.', lines: ['Reading the label…', 'Finding its active ingredients…', 'Checking how long one spray lasts in our heat and rain…'] });
        try {
            let r = await window.api(U.lookup, { method: 'POST', body: PB ? { name, ai, quote: L.price } : { scheduleId: SID, name, ai, quote: L.price } });
            if (r.data && r.data.known) {
                window.aneeWait?.fail();
                takeFound(r.data);
                window.toast?.('That one is already in our book. Nothing was charged.');
                return;
            }
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: (id) => U.job + id, phases: window.aneeWait.phases.research });
            await window.aneeWait.done({ title: 'Found it.', line: 'It is in the book now, for you and every farmer after you.' });
            if (typeof d.balance === 'number') L.balance = d.balance;
            const rep = d.report || {};
            takeFound({ product: rep.product, ingredient: (rep.ingredients || [])[0] });
            if (!rep.product && !(rep.ingredients || []).length) window.noticeSheet?.({ title: 'Anee could not confirm it', message: rep.summary || 'She could not find this product. Check the spelling on the label, or try its active ingredient.' });
        } catch (err) {
            window.aneeWait?.fail();
            if (err.data && err.data.quote) { L.price = err.data.quote; paintLookWhy(); }
            window.toast?.(err.message || 'The look up did not finish. Nothing was charged.', 'error');
        }
    });
    const takeFound = (d) => {
        if (d.product) {
            const p = d.product;
            S.picked = { productId: p.id, name: p.b, line: [p.type, p.grade, (p.ai || []).join(' + ')].filter(Boolean).join(' · '), t: p.t };
            showPicked();
        } else if (d.ingredient) {
            S.picked = { ai: d.ingredient.name, name: d.ingredient.name, line: 'The active ingredient on its own', t: '' };
            showPicked();
        }
    };

    /* ---------------- the reading ---------------- */
    const body = (html) => { $('[data-rfx-body]').innerHTML = html; };
    const empty = (h, p) => `<div class="rfx-card rfx-empty"><img src="${SRV.x_thinking}" alt=""><b>${esc(h)}</b><p>${esc(p)}</p></div>`;
    let runSeq = 0;
    // What a reading is counted from: the lot (or a protocol's tasks, days and basis lot), the checks, the tries, the logic.
    const readingBody = () => {
        const base = { check: S.check, also: S.checks.filter((k) => k !== S.check), tries: S.tries, plan: S.plan, logic: S.logic, custom: S.useCustom ? S.custom : null };
        if (!PB) return { scheduleId: SID, lotId: S.lot, ...base };
        const src = window.pbCoverage ? window.pbCoverage.source() : null;
        return { basis: S.lot, start: S.pb.start, transplant: S.pb.transplant, ha: S.pb.ha, tasks: src ? src.tasks : undefined, materials: src ? src.materials : undefined, ...base };
    };
    const run = async (keepNote = false) => {
        if (S.lot === null || S.lot === undefined) return;
        const seq = ++runSeq;
        S.saved = null;
        if (!keepNote) S.recounted = null;
        $('[data-rfx-body]').style.opacity = S.res ? '.55' : '1';
        if (!S.res) body('<div class="rfx-loading"><span></span><span></span><span></span></div>');
        try {
            const r = await window.api(U.run, { method: 'POST', body: readingBody() });
            if (seq !== runSeq) return;
            S.res = r.data;
            paint();
            keep();
            // Back to where the sheet was scrolled, once, after the first reading is drawn.
            if (S.restoreTop) { const t = S.restoreTop; S.restoreTop = 0; requestAnimationFrame(() => requestAnimationFrame(() => { scroller().scrollTop = t; })); }
        } catch (err) {
            if (seq !== runSeq) return;
            body(empty('The calendar could not be read', err.message || 'Please try again.'));
        } finally {
            if (seq === runSeq) $('[data-rfx-body]').style.opacity = '1';
        }
    };

    const paint = () => {
        // A day opened on the reading being replaced would speak of the old one.
        hideDay();
        paintPlan();
        const R = S.res;
        const sm = R.summary;
        const ck = R.check;
        const isNut = ck.kind === 'nutrient';
        const isW = ck.kind === 'water';
        const WF = (R.water && R.water.facts) || {};
        const parts = [];
        // A saved reading shows its numbers as they were when it was saved (the owner, 2026-10-30: "i don't get the
        // idea of the read it with today's plan button"): the button counts it again with the tasks on the board now.
        if (S.recounted) parts.push(`<div class="rfx-banner is-saved"><span>📌 <b>${esc(S.recounted.title)}</b>, saved ${esc(S.recounted.at)}, was counted again just now: the way the Coverage Planner counts has improved since, and your tasks may have changed. Your lot, checks and fertilizers are as you saved them. Tap <b>Update saved reading</b> to keep these numbers.</span></div>`);
        if (S.saved) parts.push(`<div class="rfx-banner is-saved"><span>📌 <b>${esc(S.saved.title)}</b>, saved ${esc(S.saved.at)}. These are its numbers from that day: tasks you added or changed since, and app updates, are not in them.</span><button type="button" class="rfx-btn is-ghost" data-rfx-rerun>Recount with my tasks now</button></div>`);
        if (!R.lot.hasDay) parts.push(`<div class="rfx-banner">📅 This lot has no planting day yet, so the stages and what matters when cannot be shown. Set its day zero in Lots, then open this again.</div>`);
        // A guide only (the owner, 2026-10-30): said every time, above the numbers.
        parts.push(`<div class="rfx-banner is-guide"><span>🧭 <b>A guide only.</b> It reads our books, your answers about this lot and your tasks. Your field and the weather can differ: check with a soil test, the label and your own eyes.</span></div>`);
        // Every section folds; these open or close them all.
        parts.push('<div class="rfx-foldbar"><button type="button" data-fold-all="1">Open all</button><button type="button" data-fold-all="0">Close all</button></div>');
        // The tries in one folding card: those that miss what is checked say what they act on, one tap away.
        parts.push(triesHtml(R, ck));
        // The summary.
        const pct = sm.pct;
        const ringCol = pct === null ? '#9ca3af' : pct >= 80 ? (isDark() ? '#7cc24f' : '#4a7c2a') : pct >= 50 ? '#f59e0b' : (isDark() ? '#f87171' : '#dc2626');
        const head = pct === null ? `Nothing in this season is critical for ${esc(ck.word)} yet.`
            : isW ? (pct >= 80 ? 'The crop has water when it matters.' : pct >= 50 ? 'The crop has water on most critical days, with dry spells.' : 'The crop runs dry when it needs water most.')
            : pct >= 80 ? `${esc(ck.short)} is well covered when it matters.`
            : pct >= 50 ? `${esc(ck.short)} is covered on most critical days, with gaps.`
            : (isNut ? `${esc(ck.short)} runs short when the crop needs it most.` : `The crop is unguarded against ${esc(ck.word)} on most critical days.`);
        const sub = isW
            ? (ck.mode === 'rain'
                ? `What the rain alone gives this lot, with no watering: about ${WF.rain ?? 0} mm of rain in the season against ${WF.use ?? 0} mm the crop drinks.`
                : `${WF.waterings ? `${WF.waterings === 1 ? 'One watering' : WF.waterings + ' waterings'} on this lot${WF.tries ? `, ${WF.tries} of them added by you` : ''}` : 'No watering on this lot yet'}, and about ${WF.rain ?? 0} mm of rain in the season against ${WF.use ?? 0} mm the crop drinks. Each watering fills the soil; the crop and the heat empty it until the next one, or a good rain.`)
            : isNut
            ? `${sm.apps} application${sm.apps === 1 ? '' : 's'} carry ${esc(ck.word)} on this lot${sm.tries ? `, ${sm.tries} of them added by you` : ''}. Each keeps feeding the crop for a while, then fades.`
            : `${sm.apps} application${sm.apps === 1 ? '' : 's'} act on ${esc(ck.word)}${sm.tries ? `, ${sm.tries} of them added by you` : ''}. Protection is only needed when the pest shows up: on a gap, scout first and spray only when you find it.`;
        const gapWord = isW ? 'stress day' : 'gap day';
        parts.push(`<div class="rfx-card" data-fold="sum" style="--d:0s"><div class="rfx-card-h">At a glance <small>${pct === null ? 'nothing critical yet' : pct + (isW ? '% of critical days well watered' : '% of critical days covered')}${sm.gapDays ? ' · ' + sm.gapDays + ' ' + gapWord + (sm.gapDays === 1 ? '' : 's') : ''}</small></div><div class="rfx-sum">
            <div class="rfx-ring"><svg viewBox="0 0 120 120"><circle class="t" cx="60" cy="60" r="52"/><circle class="v" cx="60" cy="60" r="52" stroke="${ringCol}" data-pct="${pct ?? 0}"/></svg>
              <div class="rfx-ring-n"><b data-count="${pct ?? 0}">${pct === null ? '–' : '0'}</b><small>${pct === null ? 'no critical days' : isW ? '% of critical days well watered' : '% of critical days covered'}</small></div></div>
            <div class="rfx-say"><h3>${head}</h3><p>${sub}</p>
              <div class="rfx-counts">
                <span><i style="background:${isW ? '#2b86d1' : '#4a7c2a'}"></i>${sm.coveredDays} ${isW ? 'well watered' : 'covered'}</span>
                <span><i style="background:#f59e0b"></i>${sm.thinDays} ${isW ? 'drying' : 'thin'}</span>
                <span><i style="background:#dc2626"></i>${sm.gapDays} ${isW ? 'stress' : 'gap'}</span>
                ${isW && WF.stress > sm.gapDays ? `<span><i style="background:#e0b264"></i>${WF.stress - sm.gapDays} dry, less critical</span>` : ''}
                ${isNut ? `<span><i style="background:#8b5cf6"></i>${(sm.late || []).reduce((a, r) => a + r.n, 0)} late</span>` : ''}
              </div>${amountLine(R)}${soilLine(R)}</div></div>
            ${runsHtml(sm, isNut, ck.kind)}</div>`);
        if ((R.series || []).length) parts.push(compareHtml(R));
        parts.push(logicHtml(R));
        parts.push(amountsHtml(R));
        parts.push(chartHtml(R));
        parts.push(calHtml(R));
        parts.push(appsHtml(R));
        body(parts.join(''));
        wrapFolds();
        // Bring it in: the ring, the count, the cells.
        requestAnimationFrame(() => {
            const v = $('.rfx-ring .v');
            if (v) v.style.strokeDasharray = `${(Number(v.dataset.pct) / 100) * 326.7} 400`;
            $$('.rfx-mini .v').forEach((m) => { m.style.strokeDasharray = `${(Number(m.dataset.pct) / 100) * 113.1} 200`; });
            $$('.rfx-amtbar i').forEach((m) => { m.style.width = m.dataset.w + '%'; });
            const n = $('[data-count]');
            if (n && pct !== null) countUp(n, pct);
            const cal = $('.rfx-cal');
            if (cal) requestAnimationFrame(() => cal.classList.add('is-in'));
        });
    };
    /* The tries, one card: what each misses or gives too little of. */
    const triesHtml = (R, ck) => {
        const tries = R.apps.filter((a) => a.try && !a.water);
        const miss = tries.filter((a) => !a.relevant);
        const trace = tries.filter((a) => a.relevant && a.effect && a.effect.dose === 'trace');
        if (!miss.length && !trace.length) return '';
        const working = tries.length - miss.length - trace.length;
        const meta = [miss.length ? `${miss.length} ${miss.length === 1 ? 'does' : 'do'} not act on ${esc(ck.word)}` : '', trace.length ? `${trace.length} ${trace.length === 1 ? 'gives' : 'give'} very little` : '', working ? `${working} working` : ''].filter(Boolean).join(' · ');
        const rows = miss.map((a) => {
            const acts = a.acts || [];
            return `<div class="rfx-trow"><span>✨ <b>${esc(a.as)}</b> does not act on ${esc(ck.word)}.${acts.length ? ' It acts on:' : ' We could not read what it carries or guards against. Try its active ingredient, or let Anee look it up.'}</span>${acts.length ? `<span class="rfx-acts">${acts.map((x) => `<button type="button" class="rfx-chip" data-goto="${esc(x.key)}">${esc(x.label)}</button>`).join('')}</span>` : ''}</div>`;
        }).concat(trace.map((a) => `<div class="rfx-trow"><span>✨ <b>${esc(a.as)}</b>${a.amount ? ' at ' + esc(a.amount) : ''} gives very little ${esc(ck.word)}: about ${massSay(a.effect.kgHa)} of ${EL[a.effect.el] || esc(a.effect.el)} a hectare, where one usual ${a.form === 'foliar' ? 'leaf spray gives' : 'dose on the soil gives'} about ${massSay(a.effect.ref)}. Its days still show, faintly.</span></div>`));
        return `<div class="rfx-card" data-fold="tries" style="--d:0s"><div class="rfx-card-h">Your fertilizers <small>${meta}</small></div><div class="rfx-trows">${rows.join('')}</div></div>`;
    };
    /* Folding sections (the owner, 2026-10-30: "make that part expandable contractable including the other sections below
       it for easier reading and not info overload"): the answer and the calendar open, the rest a line each until opened.
       What a farmer opens or closes is remembered on this device. */
    const FOLD_DEF = { sum: true, tries: false, cmp: true, logic: false, amt: false, chart: false, cal: true, apps: false };
    let foldPref = {};
    try { foldPref = JSON.parse(localStorage.getItem('rfx-folds') || '{}') || {}; } catch (_) { foldPref = {}; }
    const setFold = (card, open, remember = true) => {
        if (!card) return;
        card.classList.toggle('is-open', open);
        const h = card.querySelector(':scope > .rfx-fold-h');
        if (h) h.setAttribute('aria-expanded', String(open));
        if (remember) {
            foldPref[card.dataset.fold] = open;
            try { localStorage.setItem('rfx-folds', JSON.stringify(foldPref)); } catch (_) {}
        }
    };
    const wrapFolds = () => {
        $$('[data-rfx-body] .rfx-card[data-fold]').forEach((card) => {
            const h = card.querySelector(':scope > .rfx-card-h');
            if (!h || card.querySelector(':scope > .rfx-fold-b')) return;
            const b = document.createElement('div');
            b.className = 'rfx-fold-b';
            const inner = document.createElement('div');
            inner.className = 'rfx-fold-in';
            while (h.nextSibling) inner.appendChild(h.nextSibling);
            b.appendChild(inner);
            card.appendChild(b);
            card.classList.add('rfx-fold');
            h.classList.add('rfx-fold-h');
            h.setAttribute('role', 'button');
            h.tabIndex = 0;
            h.insertAdjacentHTML('beforeend', '<svg class="rfx-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>');
            const key = card.dataset.fold;
            setFold(card, key in foldPref ? !!foldPref[key] : !!FOLD_DEF[key], false);
        });
    };
    /* The soil the reading counts, in the summary, so a lot read by the area's typical soil says so up front. */
    const soilLine = (R) => {
        const L = R.logic;
        if (!L || !L.opts || !L.opts.soil || !L.soil) return '';
        const note = (L.notes || []).find((x) => x.k === 'soil' && (x.acts || []).length);
        const from = L.soil.from;
        const how = from === 'answers' || from === 'both' ? 'from your answers' : from === 'area' ? "the area's typical soil: this lot is not answered yet" : 'not known yet';
        const act = note && note.acts[0] && (from === 'area' || from === 'none') ? `<a class="rfx-chip" href="${esc(note.acts[0].href)}">${esc(note.acts[0].label)}</a>` : '';
        return `<div class="rfx-amtline rfx-soilline"><span>🪨 <b>Soil</b>: ${esc(L.soil.say)} (${esc(how)})</span>${act}</div>`;
    };
    /* How much over the season, in the summary: given against needed from the bag. */
    const amountLine = (R) => {
        const A = R.logic?.amt;
        if (!A) return '';
        if (!A.known) return '<div class="rfx-amtline"><span>⚖️ This crop has no numbers in NPK Plus yet, so the amounts cannot be weighed.</span></div>';
        if (A.soilGives) return `<div class="rfx-amtline"><span>⚖️ The soil likely gives all the ${esc(A.word)} this crop needs, so none has to come from the bag${A.applied ? `. The season puts on about ${kgSay(A.applied)} ${A.unit || 'kg'} ${esc(A.say)} a hectare anyway` : ''}.</span></div>`;
        const pct = A.pct ?? 0;
        const col = pct >= 90 ? '#3f9a26' : pct >= 60 ? '#f59e0b' : '#dc2626';
        return `<div class="rfx-amtline"><span>⚖️ Over the season your products give about <b>${kgSay(A.given)} of the ${kgSay(A.need)} ${A.unit || 'kg'} ${esc(A.say)}</b> a hectare this field needs from the bag (<b>${pct}%</b>).</span>
            <div class="rfx-amtbar" title="The line is the need"><i data-w="${Math.min(100, pct / 1.5).toFixed(1)}" style="background:${col}"></i><em style="left:${(100 / 1.5).toFixed(1)}%"></em></div></div>`;
    };
    /* How much, day by day: what each day gets against what it takes, and the two added up. */
    const amountsHtml = (R) => {
        const A = R.logic?.amt;
        if (!A || !A.known || !A.n || A.soilGives) return '';
        const days = R.days, n = days.length, W = 600, H = 170, top = 14, base = 140, bw = W / n;
        const maxD = Math.max(0.0001, ...A.g, ...A.n) * 1.12;
        const yD = (v) => base - v / maxD * (base - top);
        let bg = '', bars = '', k = 0;
        days.forEach((d, i) => {
            const x = i * bw;
            if (d.c >= 2 && (A.s[i] === 'short' || A.s[i] === 'low')) bg += `<rect x="${x.toFixed(1)}" y="${top}" width="${(bw + 0.4).toFixed(1)}" height="${base - top}" fill="${A.s[i] === 'short' ? 'rgb(220 38 38 / .13)' : 'rgb(245 158 11 / .12)'}"/>`;
            const g = A.g[i];
            if (g > 0.0005) bars += `<rect class="bar" style="--k:${k++}" x="${(x + bw * 0.1).toFixed(2)}" y="${yD(g).toFixed(1)}" width="${(bw * 0.8).toFixed(2)}" height="${(base - yD(g)).toFixed(1)}" fill="${PHC[d.p] || PHC.strong}"><title>${say(d.d)}: gives ${kgSay(g)} ${A.unit || 'kg'}, the crop needs ${kgSay(A.n[i])}</title></rect>`;
        });
        const line = (arr, y) => arr.map((v, i) => (i ? 'L' : 'M') + ((i + 0.5) * bw).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ');
        let mo = '';
        days.forEach((d, i) => { if (d.d.endsWith('-01')) mo += `<text class="ax" x="${(i * bw + 2).toFixed(1)}" y="${base + 18}">${MON[D(d.d).getMonth()]}</text><line x1="${(i * bw).toFixed(1)}" x2="${(i * bw).toFixed(1)}" y1="${base}" y2="${base + 5}" stroke="${tok('--rx-line-2')}"/>`; });
        const ti = days.findIndex((d) => d.d === R.today);
        const todayL = ti >= 0 ? `<line x1="${((ti + 0.5) * bw).toFixed(1)}" x2="${((ti + 0.5) * bw).toFixed(1)}" y1="${top}" y2="${base}" stroke="${tok('--rx-today')}" stroke-width="1.5" stroke-dasharray="3 3"/>` : '';
        // Added up over the crop's own days.
        const CG = [], CN = [];
        let cg = 0, cn = 0;
        days.forEach((d, i) => { if (!A.in || A.in[i]) { cg += A.g[i]; cn += A.n[i]; } CG.push(cg); CN.push(cn); });
        const maxC = Math.max(0.0001, cg, cn) * 1.1;
        const yC = (v) => base - v / maxC * (base - top);
        const area = `M${(0.5 * bw).toFixed(1)} ${base} ` + CG.map((v, i) => 'L' + ((i + 0.5) * bw).toFixed(1) + ' ' + yC(v).toFixed(1)).join(' ') + ` L${((n - 0.5) * bw).toFixed(1)} ${base} Z`;
        const nowSay = ti >= 0 ? `<text class="ax" x="${Math.min(W - 4, (ti + 0.5) * bw + 4).toFixed(1)}" y="${top + 12}" text-anchor="${ti > n * 0.7 ? 'end' : 'start'}" fill="${tok('--rx-today')}">By today ${kgSay(CG[ti])} of ${kgSay(CN[ti])}</text>` : '';
        const axis = `<line x1="0" x2="${W}" y1="${base}" y2="${base}" stroke="${tok('--rx-line-2')}"/>${mo}`;
        const runs = (A.short || []).map((r, i) => `<div class="rfx-run" data-jump="${r.from}" style="--d:${0.1 + i * 0.05}s"><em>⚖️</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>: the products give under half of what the crop takes these days.</span></div>`).join('')
            + (A.low || []).map((r, i) => `<div class="rfx-run is-thin" data-jump="${r.from}" style="--d:${0.25 + i * 0.05}s"><em>⚖️</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>: a little under what the crop takes.</span></div>`).join('');
        return `<div class="rfx-card" data-fold="amt" style="--d:.1s"><div class="rfx-card-h">How much, day by day <small>${A.pct !== null && A.pct !== undefined ? `${kgSay(A.given)} of ${kgSay(A.need)} ${A.unit || 'kg'} ${esc(A.say)} a hectare (${A.pct}%)` : `${A.unit === 'g' ? 'grams' : 'kg'} of ${esc(A.say)} a hectare`}</small></div>
            <div class="rfx-amt-g">
              <div><h4>Each day <small>bars: what your products give · dashes: what the crop takes</small></h4><div class="rfx-chart-w"><svg viewBox="0 -4 ${W} ${H + 8}" style="min-width:28rem" aria-label="What each day gets against what it takes">${bg}${bars}<path class="needl" d="${line(A.n, yD)}"/>${todayL}${axis}<text class="ax" x="2" y="${top - 2}">${kgSay(maxD / 1.12)} ${A.unit || 'kg'} a day</text></svg></div></div>
              <div><h4>Adding up <small>given so far against needed so far</small></h4><div class="rfx-chart-w"><svg viewBox="0 -4 ${W} ${H + 8}" style="min-width:28rem" aria-label="Given and needed, added up"><path class="givea" d="${area}"/><path class="givel" d="${line(CG, yC)}"/><path class="needl" d="${line(CN, yC)}"/>${todayL}${nowSay}${axis}<text class="ax" x="2" y="${top - 2}">${kgSay(maxC / 1.1)} ${A.unit || 'kg'}</text></svg></div></div>
            </div>
            ${runs ? `<div class="rfx-runs">${runs}</div>` : ''}
            <div class="rfx-legend"><span><i style="background:${PHC.strong}"></i>Given, coloured by what it is doing</span><span><i style="background:none;border-top:2.5px dashed ${tok('--rx-ink')};height:0;border-radius:0"></i>Needed</span><span><i style="background:rgb(220 38 38 / .25)"></i>Short on a day that matters</span><span><i style="background:rgb(245 158 11 / .25)"></i>A little short</span></div></div>`;
    };
    /* How it connects: the soil, the links between products and nutrients, amounts and the crop's health, in words. */
    const GROUPS = [['soil', '🪨', 'Your soil'], ['water', '💧', 'Water'], ['links', '🔗', 'How your products connect'], ['amt', '⚖️', 'Amounts'], ['health', '🛡️', 'Crop health']];
    const logicHtml = (R) => {
        const L = R.logic;
        if (!L) return '';
        const notes = L.notes || [];
        const others = L.others || [];
        if (!notes.length && !others.length) return '';
        let html = '', k = 0;
        GROUPS.forEach(([key, icon, title]) => {
            const list = notes.filter((x) => x.k === key);
            if (!list.length) return;
            html += `<section><h4>${icon} ${title}</h4>${list.map((x) => `<div class="rfx-note is-${esc(x.tone || 'info')}" ${x.from ? `data-jump="${esc(x.from)}"` : ''} style="--k:${k++}"><em>${x.icon || '•'}</em><div><b>${esc(x.title)}</b><p>${esc(x.text)}</p>${(x.acts || []).length ? `<span class="rfx-note-acts">${x.acts.map((a) => a.href ? `<a class="rfx-chip" href="${esc(a.href)}">${esc(a.label)}</a>` : `<button type="button" class="rfx-chip is-on" data-copy-ans data-to="${a.copy.to}" data-from="${a.copy.from}">${esc(a.label)}</button>`).join('')}</span>` : ''}${x.from ? `<small>${say(x.from)}${x.to && x.to !== x.from ? ' to ' + say(x.to) : ''} · show it in the calendar</small>` : ''}</div></div>`).join('')}</section>`;
        });
        if (others.length) html += `<section><h4>🔁 What your products do to other nutrients</h4><ul class="rfx-others">${others.map((x) => `<li><b>${esc(x.name)}</b><span class="rfx-way is-${x.way === 'more' ? 'more' : 'less'}">${x.way === 'more' ? 'freed' : 'held back'}</span><span>${esc(x.why)}</span>${x.key ? `<button type="button" class="rfx-chip" data-goto="${esc(x.key)}">Check it</button>` : ''}</li>`).join('')}</ul></section>`;
        const on = Object.entries(L.opts || {}).filter(([, v]) => v).map(([kk]) => ({ amt: 'amounts', soil: 'soil', links: 'links', health: 'health' }[kk])).join(' · ');
        const warns = notes.filter((x) => x.tone === 'warn').length;
        return `<div class="rfx-card" data-fold="logic" style="--d:.06s"><div class="rfx-card-h">How it connects <small>${notes.length + others.length} note${notes.length + others.length === 1 ? '' : 's'}${warns ? ' · ' + warns + ' to watch' : ''}</small></div><div class="rfx-notes">${html}</div></div>`;
    };
    const countUp = (el, to) => {
        if (calm) { el.textContent = to; return; }
        const t0 = performance.now();
        const step = (t) => { const k = Math.min(1, (t - t0) / 1100); el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); };
        requestAnimationFrame(step);
    };
    const runsHtml = (sm, isNut, kind = '') => {
        const rows = [];
        const isW = kind === 'water';
        (sm.gaps || []).slice(0, 4).forEach((r, k) => rows.push(`<div class="rfx-run" data-jump="${r.from}" style="--d:${.1 + k * .05}s"><em>🔴</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: ${isW ? 'too dry' : isNut ? 'not covered' : 'not protected'} while it is ${r.c >= 3 ? 'critical' : 'needed'}. ${esc(r.why)}${isW ? ' Water a day or two before.' : ''}</span></div>`));
        (sm.thin || []).slice(0, 2).forEach((r, k) => rows.push(`<div class="rfx-run is-thin" data-jump="${r.from}" style="--d:${.25 + k * .05}s"><em>🟠</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: ${isW ? 'drying, water soon.' : 'only partly covered.'}</span></div>`));
        if (isNut) (sm.late || []).slice(0, 2).forEach((r, k) => rows.push(`<div class="rfx-run is-late" data-jump="${r.from}" style="--d:${.35 + k * .05}s"><em>🟣</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: still working when the crop no longer needs it. ${esc(sm.lateNote || '')}</span></div>`));
        return rows.length ? `<div class="rfx-runs">${rows.join('')}</div>` : '';
    };

    /* Every picked check, in the order picked: a ring each, then one lane each over the same days. */
    const allChecks = (R) => [{ key: R.check.key, kind: R.check.kind, label: R.check.label, short: R.check.short, pct: R.summary.pct, gap: R.summary.gapDays, words: R.phaseWords || {}, main: true,
            v: R.days.map((d) => d.v), s: R.days.map((d) => d.s), p: R.days.map((d) => d.p), c: R.days.map((d) => d.c) }]
        .concat((R.series || []).map((x) => ({ key: x.check.key, kind: x.check.kind, label: x.check.label, short: x.check.short, pct: x.pct, gap: x.gapDays, words: x.words || {}, v: x.v, s: x.s, p: x.p, c: x.c })))
        .sort((a, b) => S.checks.indexOf(a.key) - S.checks.indexOf(b.key));
    const compareHtml = (R) => {
        const all = allChecks(R);
        const n = R.days.length;
        const ti = R.days.findIndex((d) => d.d === R.today);
        const hi = R.lot.harvest ? R.days.findIndex((d) => d.d === R.lot.harvest) : -1;
        const rings = all.map((x, k) => `<button type="button" class="rfx-mini ${x.main ? 'is-on' : ''}" data-focus="${esc(x.key)}" style="--sc:${colorOf(x.key)};--k:${k}" ${x.main ? 'aria-current="true"' : ''}>
            <svg viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="18" class="t"/><circle cx="22" cy="22" r="18" class="v" stroke="${colorOf(x.key)}" data-pct="${x.pct ?? 0}"/></svg>
            <span><b>${x.pct === null ? '–' : x.pct + '%'}</b><span>${esc(x.label)}</span><small>${x.pct === null ? 'nothing critical yet' : x.gap ? x.gap + (x.kind === 'water' ? ' stress day' : ' gap day') + (x.gap === 1 ? '' : 's') : (x.kind === 'water' ? 'no stress' : 'no gaps')}</small></span></button>`).join('');
        // One lane: a tick on top where the stage needs it, pink where it is a gap, and each day filled to its
        // coverage in the colour of what the product is doing.
        const gapFill = tok('--rx-gap-line') || '#fecaca';
        const lane = (x) => {
            let r = '';
            for (let i = 0; i < n; i++) {
                if (x.c[i] >= 2) r += `<rect x="${i}" y="0" width="1.02" height="1.4" fill="${x.c[i] >= 3 ? '#dc2626' : '#f97316'}"/>`;
                if (x.s[i] === 'gap') r += `<rect x="${i}" y="1.8" width="1.02" height="8.2" fill="${gapFill}"/>`;
                const p = x.p[i];
                if (p && x.v[i] > 0.01) { const h = Math.max(1.2, Math.min(1, x.v[i]) * 8.2); r += `<rect x="${i}" y="${(10 - h).toFixed(2)}" width="1.02" height="${h.toFixed(2)}" fill="${PHC[p]}"/>`; }
                else if (p === 'soil') r += `<rect x="${i}" y="7.4" width="1.02" height="2.6" fill="${PHC.soil}"/>`;
            }
            if (ti >= 0) r += `<line x1="${ti + .5}" x2="${ti + .5}" y1="0" y2="10" stroke="${tok('--rx-today')}" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            if (hi >= 0) r += `<line x1="${hi + .5}" x2="${hi + .5}" y1="0" y2="10" stroke="#d97706" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            return r;
        };
        const lanes = all.map((x, k) => `<div class="rfx-lane ${x.main ? 'is-on' : ''}" style="--sc:${colorOf(x.key)};--k:${k}">
            <button type="button" class="rfx-lane-n" data-focus="${esc(x.key)}" title="${esc(x.label)}"><i></i>${esc(x.short)}</button>
            <svg class="rfx-lane-s" viewBox="0 0 ${n} 10" preserveAspectRatio="none" data-lane role="img" aria-label="${esc(x.label)} over the season">${lane(x)}</svg></div>`).join('');
        const stg = R.stages.map((g, gi) => {
            const i0 = R.days.findIndex((d) => d.d === g.from), i1 = R.days.findIndex((d) => d.d === g.to);
            return i0 < 0 ? '' : stageMark(g, gi, R, `width:${((i1 - i0 + 1) / n * 100).toFixed(3)}%`, true);
        }).join('');
        let mo = '';
        R.days.forEach((d, i) => { if (d.d.endsWith('-01')) mo += `<span style="left:${(i / n * 100).toFixed(2)}%">${MON[D(d.d).getMonth()]}</span>`; });
        return `<div class="rfx-card rfx-cmp" data-fold="cmp" style="--d:.04s"><div class="rfx-card-h">Side by side <small>${all.length} checks · tap one to open it below</small></div>
            <div class="rfx-minis">${rings}</div>
            <div class="rfx-lanes"><div class="rfx-lane is-head"><span></span><div class="rfx-stg">${stg}</div></div>${lanes}<div class="rfx-lane is-head"><span></span><div class="rfx-mo">${mo}</div></div></div>
            ${phaseLegend(R, all)}
            <div class="rfx-legend"><span><i style="background:linear-gradient(180deg,#dc2626 0 22%,var(--rx-soft) 22%);box-shadow:inset 0 0 0 1px var(--rx-line)"></i>A line on top: that one is critical now</span><span><i style="background:var(--rx-gap-line)"></i>A gap</span><span><i style="background:var(--rx-today);width:3px"></i>Today</span></div></div>`;
    };

    /* Quick switches over the season chart (the owner, 2026-10-30: "the season have a quick toggle buttons to show and
       hide the nutrients"). A picked check shows or hides its line at a tap, nothing counted again. With a nutrient
       shown, N, P, K and every nutrient the season's products carry are offered too: one not picked yet is added to
       the comparison (counted then) and shown, beside the shaded one. One always stays on the chart. */
    const shownOn = (key) => S.checks.includes(key) && !S.hide.includes(key);
    const seasonKeys = (R) => {
        const nuts = ((S.opts || {}).nutrients || []).map((n) => n.key);
        const carried = new Set(['n:N', 'n:P', 'n:K']);
        (R.apps || []).forEach((a) => (a.carries || []).forEach((c) => carried.add('n:' + String(c).split(' ')[0])));
        const offer = R.check.kind === 'nutrient' ? nuts.filter((k) => carried.has(k) && !S.checks.includes(k)) : [];
        return [...S.checks, ...offer];
    };
    const lnRowHtml = (R) => {
        const keys = seasonKeys(R);
        if (keys.length < 2) return '';
        const nuts = (S.opts || {}).nutrients || [];
        const name = (k) => { const n = nuts.find((x) => x.key === k); return n ? n.short : labelOf(k); };
        const add = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
        return `<div class="rfx-lnrow" role="group" aria-label="Show or hide on the chart">${keys.map((k, i) => {
            const picked = S.checks.includes(k), on = shownOn(k);
            return `<button type="button" class="rfx-ln${on ? ' is-on' : ''}${picked ? '' : ' is-add'}" data-ln-t="${esc(k)}" aria-pressed="${on}" style="--sc:${picked ? colorOf(k) : 'var(--rx-faint)'};--k:${i}" title="${esc(labelOf(k))}${picked ? (on ? ': tap to hide it' : ': tap to show it') : ': tap to add it to the chart'}">${picked ? '<i></i>' : add}${esc(name(k))}${k === R.check.key && S.checks.length > 1 ? '<small>shaded</small>' : ''}</button>`;
        }).join('')}</div>`;
    };
    const toggleLine = (key) => {
        if (!S.checks.includes(key)) {
            const b = $(`[data-ln-t="${key}"]`);
            if (b) b.classList.add('is-busy');
            togglePick(key, true);
            return;
        }
        const off = !S.hide.includes(key);
        if (off && S.checks.filter((k) => !S.hide.includes(k)).length <= 1) { window.toast?.('One stays on the chart.'); return; }
        S.hide = off ? [...S.hide, key] : S.hide.filter((k) => k !== key);
        $$(`.rfx-chart [data-ln="${key}"]`).forEach((el) => el.classList.toggle('is-off', off));
        const b = $(`[data-ln-t="${key}"]`);
        if (b) {
            b.classList.toggle('is-on', !off);
            b.setAttribute('aria-pressed', String(!off));
            b.title = labelOf(key) + (off ? ': tap to show it' : ': tap to hide it');
        }
        keep();
    };

    /* The growth stages said short (the owner, 2026-10-30: "in the season, just use symbols and acronym for the growth
       stages"): a symbol and two letters each, the same on the chart and side by side; a tap opens the stage's sheet.
       Every stage name of the crop book is here; one it lacks gets its initials. */
    const STAGE_MARK = {
        'Before planting': ['🚜', 'BP'], 'No planting day yet': ['❔', 'ND'], 'After harvest': ['🧺', 'AH'],
        'Germination': ['🫘', 'GR'], 'Germination & emergence': ['🌱', 'GE'], 'Emergence': ['🌱', 'EM'], 'Seedling': ['🌱', 'SD'],
        'Seedling establishment': ['🪴', 'SE'], 'Establishment': ['🪴', 'ES'], 'Recovery': ['🪴', 'RC'], 'Root initiation': ['🪴', 'RI'],
        'Early vegetative': ['🌿', 'EV'], 'Vegetative': ['🌿', 'VG'], 'Vegetative growth': ['🌿', 'VG'], 'Late vegetative': ['🌿', 'LV'],
        'Rapid growth': ['🌿', 'RG'], 'Grand growth': ['🌿', 'GG'], 'Juvenile growth': ['🌱', 'JG'], 'Leaf growth': ['🍃', 'LG'], 'Vine growth': ['🍃', 'VN'],
        'Tillering': ['🌿', 'TL'], 'Early tillering': ['🌿', 'ET'], 'Active tillering': ['🌿', 'AT'],
        'Panicle initiation': ['🎯', 'PI'], 'Booting & heading': ['🎋', 'BH'], 'Heading & filling': ['🎋', 'HF'], 'Shooting': ['🎋', 'SH'], 'Tasselling': ['🎋', 'TS'],
        'Reproductive': ['🌼', 'RP'], 'Flowering': ['🌼', 'FL'], 'Silking & pollination': ['🌼', 'SP'], 'Fruit set & filling': ['🍅', 'FS'],
        'Pod filling': ['🫛', 'PF'], 'Grain filling': ['🍚', 'GF'], 'Milk stage': ['🥛', 'MK'], 'Bunch filling': ['🍌', 'BF'], 'Bulking': ['🥔', 'BK'], 'Bulbing': ['🧅', 'BB'],
        'Maturation': ['🌕', 'MT'], 'Maturity & curing': ['🌕', 'MC'], 'Maturity & harvest': ['🧺', 'MH'], 'Ripening & harvest': ['🧺', 'RH'], 'Harvest': ['🧺', 'HV'],
        'First bearing': ['🍊', 'FB'], 'Mature bearing': ['🌳', 'MB'], 'Old and declining': ['🍂', 'OD'],
    };
    const markOf = (label) => {
        if (STAGE_MARK[label]) return STAGE_MARK[label];
        const w = String(label || '?').split(/[\s&,/]+/).filter((x) => x && !['and', 'of', 'the'].includes(x.toLowerCase()));
        const ab = w.length > 1 ? w[0][0] + w[1][0] : (w[0] || '?')[0] + (((w[0] || '').slice(1).match(/[^aeiou]/i) || [''])[0]);
        return ['🌿', ab.toUpperCase()];
    };
    const stageMark = (g, gi, R, style, small = false) => {
        const [ic, ab] = markOf(g.label);
        const now = R.today >= g.from && R.today <= g.to;
        return `<button type="button" class="rfx-sb${small ? ' is-sm' : ''} c${g.crit || 0}${now ? ' is-now' : ''}" data-stage="${gi}" style="${style}" title="${esc(g.label)}: ${say(g.from)} to ${say(g.to)}" aria-label="${esc(g.label)}, ${say(g.from)} to ${say(g.to)}"><span><i aria-hidden="true">${ic}</i><b>${esc(ab)}</b></span></button>`;
    };
    const CRIT_SAY = ['Not needed now', 'Matters a little', 'Needed', 'Critical'];
    const plural = (n, w) => n + ' ' + w + (n === 1 ? '' : 's');
    // "DAS 15" and "DAS 36" as "DAS 15 to 36"; a count the delay counter moves, as the board writes it.
    const countRange = (a, b, minus = 0) => {
        const m0 = /^([A-Z]+) (-?\d+)$/.exec(a || ''), m1 = /^([A-Z]+) (-?\d+)$/.exec(b || '');
        if (m0 && m1 && m0[1] === m1[1]) { const f = Number(m0[2]) - minus, t = Number(m1[2]) - minus; return `${m0[1]} ${f}${t !== f ? ' to ' + t : ''}`; }
        return minus ? '' : [a, b].filter(Boolean).filter((v, i, x) => x.indexOf(v) === i).join(' to ');
    };
    const stageBox = $('[data-rfx-stg]');
    let stageAt = -1;
    const stageHtml = (R, gi) => {
        const g = R.stages[gi];
        const [ic, ab] = markOf(g.label);
        const ds = R.days.filter((d) => d.d >= g.from && d.d <= g.to);
        const len = ds.length;
        const cnt = ds.length ? countRange(ds[0].dl, ds[ds.length - 1].dl) : '';
        let when;
        if (R.today < g.from) when = `Starts in ${plural(Math.round((D(g.from) - D(R.today)) / 864e5), 'day')}`;
        else if (R.today > g.to) when = `Ended ${plural(Math.round((D(R.today) - D(g.to)) / 864e5), 'day')} ago`;
        else { const k = Math.round((D(R.today) - D(g.from)) / 864e5) + 1; when = `Today is day ${k} of ${len}`; }
        const nowPct = R.today >= g.from && R.today <= g.to ? Math.round((Math.round((D(R.today) - D(g.from)) / 864e5) + 1) / Math.max(1, len) * 100) : (R.today > g.to ? 100 : 0);
        let h = `<div class="rfx-day-h"><div><b id="rfxStgT"><span class="rfx-stg-ic" aria-hidden="true">${ic}</span>${esc(g.label)}<em class="rfx-stg-ab">${esc(ab)}</em></b>
            <small>${g.index != null && g.count ? `Stage ${g.index + 1} of ${g.count} · ` : ''}${esc(R.lot.name)}, ${esc(R.lot.crop)}</small></div>
            <button type="button" class="rfx-x" data-rfx-stg-x aria-label="Close">${'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'}</button></div>`;
        // Its days: the dates, how long, the count, where today is.
        h += `<div class="rfx-stg-when"><div><b>${say(g.from)}${g.to !== g.from ? ' to ' + say(g.to) : ''}</b><small>${plural(len, 'day')}${cnt ? ' · ' + esc(cnt) : ''}</small></div><span>${esc(when)}</span></div>
            <div class="rfx-meter" aria-hidden="true"><i style="width:${nowPct}%;background:var(--rx-accent)"></i></div>`;
        // Anee's realign: these dates follow it; the calendar's own beside them.
        const ra = R.lot.realign;
        if (ra && g.stage) {
            const sh = Number(ra.shift) || 0, sure = ra.confidence ? `, ${ra.confidence}% sure` : '';
            h += `<div class="rfx-stg-ra"><b>🎯 Anee's realign, ${say(ra.at)}${sure}</b>`;
            if (!sh) h += `<p>She found the crop on time with the calendar, so these are the calendar's own dates.</p>`;
            else {
                h += `<p>She found the crop <b>${plural(Math.abs(sh), 'day')} ${sh < 0 ? 'behind' : 'ahead of'}</b> the calendar, so the dates above follow her reading.</p>`;
                if (g.cal && g.cal.from) {
                    h += `<div class="rfx-stg-cmp"><span><em>📅 The calendar alone</em><b>${say(g.cal.from)}${g.cal.to && g.cal.to !== g.cal.from ? ' to ' + say(g.cal.to) : ''}</b></span>
                        <span class="is-ra"><em>🎯 With the realign</em><b>${say(g.from)}${g.to !== g.from ? ' to ' + say(g.to) : ''}</b></span></div>`;
                }
            }
            if (ra.summary) h += `<details class="rfx-stg-saw"><summary>What Anee saw</summary><p>${esc(ra.summary)}</p></details>`;
            h += '</div>';
        }
        // The farmer's own delay counter, as the board counts it beside the calendar's.
        const dl = Number(R.lot.delay) || 0;
        if (dl > 0 && g.stage && ds.length) {
            const dc = countRange(ds[0].dl, ds[ds.length - 1].dl, dl);
            h += `<div class="rfx-stg-ra is-delay"><b>⏳ Your delay counter: ${plural(dl, 'day')} behind</b><p>${dc ? `By its count this stage is <b>DELAY ${esc(dc)}</b>. ` : ''}The board shows both counts on each task.</p></div>`;
        }
        // How much the check matters here, and how well it is covered.
        if (g.stage) {
            const by = {};
            ds.forEach((d) => { by[d.s] = (by[d.s] || 0) + 1; });
            const bits = [];
            const isW = R.check.kind === 'water';
            if (by.ok) bits.push(`<span class="is-ok">${plural(by.ok, 'day')} ${isW ? 'well watered' : 'covered'}</span>`);
            if (by.thin) bits.push(`<span class="is-thin">${by.thin} ${isW ? 'drying' : 'partly'}</span>`);
            if (by.gap) bits.push(`<span class="is-gap">${plural(by.gap, isW ? 'stress day' : 'gap day')}</span>`);
            if (by.late) bits.push(`<span>${by.late} still working, not needed</span>`);
            h += `<div class="rfx-stg-crit c${g.crit || 0}"><b>${CRIT_SAY[g.crit || 0]} for ${esc(R.check.word)}</b>${g.why ? `<p>${esc(g.why)}</p>` : ''}${bits.length ? `<div class="rfx-stg-cov">${bits.join('')}</div>` : ''}</div>`;
        }
        if (g.what || g.needs) h += `<div class="rfx-stg-do">${g.what ? `<p><b>The crop:</b> ${esc(g.what)}</p>` : ''}${g.needs ? `<p><b>It needs:</b> ${esc(g.needs)}</p>` : ''}</div>`;
        // What the plan puts on in these days: where the stage sits in the protocol.
        const apps = (R.apps || []).filter((a) => a.date >= g.from && a.date <= g.to);
        h += `<div class="rfx-stg-apps"><b>On the plan in these days</b>${apps.length ? `<ul>${apps.map((a) => {
            const at = R.days.find((d) => d.d === a.date);
            return `<li class="${a.relevant ? 'is-rel' : ''}"><span>${say(a.date)}${at && at.dl ? `<small>${esc(at.dl)}</small>` : ''}</span><em>${esc(a.as)}</em><i>${a.try ? 'Added by you' : a.done ? 'Done' : 'Planned'}</i></li>`;
        }).join('')}</ul>` : '<p>Nothing on the tasks in these days.</p>'}</div>`;
        // Step to the stage before or after.
        const prev = R.stages[gi - 1], next = R.stages[gi + 1];
        h += `<div class="rfx-edit-b">${prev ? `<button type="button" class="rfx-btn is-ghost" data-stg-go="${gi - 1}">‹ ${markOf(prev.label)[0]} ${esc(markOf(prev.label)[1])}</button>` : ''}<span style="flex:1"></span>${next ? `<button type="button" class="rfx-btn is-ghost" data-stg-go="${gi + 1}">${markOf(next.label)[0]} ${esc(markOf(next.label)[1])} ›</button>` : ''}</div>`;
        return h;
    };
    const openStage = (gi) => {
        const R = S.res;
        if (!R || !R.stages || !R.stages[gi]) return;
        const was = stageAt;
        stageAt = gi;
        const c = $('[data-rfx-stg-b]');
        c.innerHTML = stageHtml(R, gi);
        c.scrollTop = 0;
        if (was >= 0 && !stageBox.hidden) { if (!calm) { c.classList.remove('is-step'); void c.offsetWidth; c.classList.add('is-step'); } return; }
        stageBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => stageBox.classList.add('is-on')));
        setTimeout(() => c.querySelector('[data-rfx-stg-x]')?.focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const closeStage = () => {
        stageAt = -1;
        stageBox.classList.remove('is-on');
        setTimeout(() => { if (stageAt < 0) stageBox.hidden = true; }, calm ? 0 : 260);
    };
    stageBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-stg-x]')) { closeStage(); return; }
        const go = e.target.closest('[data-stg-go]');
        if (go) openStage(Number(go.dataset.stgGo));
    });

    /* The season as a chart: coverage per day over the stages, each stage tinted by how critical it is. */
    const chartHtml = (R) => {
        const days = R.days;
        if (!days.length) return '';
        // Every line hidden (the last shown one taken out above): all of them come back.
        if (S.checks.every((k) => S.hide.includes(k))) S.hide = [];
        const mk = esc(R.check.key), off = (k) => (shownOn(k) ? '' : ' is-off'), offMain = off(R.check.key);
        const W = 1000, H = 176, top = 8, base = 136, n = days.length;
        const x = (i) => (i / Math.max(1, n - 1)) * W;
        const y = (v) => base - Math.min(1.6, v) / 1.6 * (base - top);
        const critCol = ['rgba(0,0,0,0)', 'rgba(245,158,11,.10)', 'rgba(249,115,22,.16)', 'rgba(220,38,38,.18)'];
        let bands = '', marks = '';
        R.stages.forEach((g, gi) => {
            const i0 = days.findIndex((d) => d.d === g.from), i1 = days.findIndex((d) => d.d === g.to);
            if (i0 < 0) return;
            const x0 = x(i0) - (W / n) / 2, x1 = x(i1) + (W / n) / 2;
            bands += `<rect x="${x0.toFixed(1)}" y="${top}" width="${Math.max(0, x1 - x0).toFixed(1)}" height="${base - top}" fill="${critCol[g.crit] || critCol[0]}"/>`;
            // Each stage a symbol and two letters over its band, never words cut short; a tap opens the stage.
            const l0 = Math.max(0, x0), l1 = Math.min(W, x1);
            marks += stageMark(g, gi, R, `left:${(l0 / W * 100).toFixed(3)}%;width:${((l1 - l0) / W * 100).toFixed(3)}%`);
            bands += `<line class="grid" x1="${x0.toFixed(1)}" x2="${x0.toFixed(1)}" y1="${top}" y2="${base}"/>`;
        });
        let line = '', area = `M0 ${base}`, tryLine = '';
        days.forEach((d, i) => { const p = `${x(i).toFixed(1)} ${y(d.v).toFixed(1)}`; line += (i ? ' L' : 'M') + p; area += ' L' + p; });
        area += ` L${W} ${base} Z`;
        if (days.some((d) => d.t)) days.forEach((d, i) => { tryLine += (i ? ' L' : 'M') + `${x(i).toFixed(1)} ${y(d.t).toFixed(1)}`; });
        // The other checks being compared, a line each in its own colour.
        const cmp = (R.series || []).length;
        const others = (R.series || []).map((sr) => `<path class="ln${off(sr.check.key)}" data-ln="${esc(sr.check.key)}" d="${sr.v.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ')}" fill="none" stroke="${colorOf(sr.check.key)}" stroke-width="1.8" stroke-linejoin="round" opacity=".9"/>`).join('');
        // The coverage takes the colour of what the strongest product is doing that day, and a ribbon under the
        // baseline shows every phase, the days in the soil too (nothing covers yet, so the area has no height there).
        let stops = '', prev = PHC.strong;
        days.forEach((d, i) => { const c = PHC[d.p] || prev; prev = c; stops += `<stop offset="${(i / Math.max(1, n - 1)).toFixed(4)}" stop-color="${c}"/>`; });
        let ribbon = '', run0 = 0, rk = 0;
        days.forEach((d, i) => {
            const nx = days[i + 1];
            if (nx && nx.p === d.p) return;
            if (d.p) {
                const x0 = x(run0) - (W / n) / 2, x1 = x(i) + (W / n) / 2;
                ribbon += `<rect class="rib" style="--k:${rk++}" x="${Math.max(0, x0).toFixed(1)}" y="${base + 3}" width="${Math.max(1.5, Math.min(W, x1) - Math.max(0, x0)).toFixed(1)}" height="6" rx="3" fill="${PHC[d.p]}"><title>${esc((R.phaseWords || {})[d.p] || d.p)} · ${say(days[run0].d)}${i > run0 ? ' to ' + say(d.d) : ''}</title></rect>`;
            }
            run0 = i + 1;
        });
        let months = '';
        days.forEach((d, i) => { if (d.d.endsWith('-01') || i === 0) months += `<text class="mo" x="${x(i).toFixed(1)}" y="${base + 21}">${MON[D(d.d).getMonth()]}</text><line class="grid" x1="${x(i).toFixed(1)}" x2="${x(i).toFixed(1)}" y1="${base + 10}" y2="${base + 13}"/>`; });
        const ti = days.findIndex((d) => d.d === R.today);
        const today = ti >= 0 ? `<line class="today" x1="${x(ti)}" x2="${x(ti)}" y1="${top - 2}" y2="${base}"/><text class="mo t-today" x="${x(ti) + 4}" y="${base - 5}">Today</text>` : '';
        const hi = R.lot.harvest ? days.findIndex((d) => d.d === R.lot.harvest) : -1;
        const harv = hi >= 0 ? `<line class="harv" x1="${x(hi)}" x2="${x(hi)}" y1="${top}" y2="${base}"/><text class="mo t-harv" x="${x(hi) - 4}" y="${top + 12}" text-anchor="end">Harvest</text>` : '';
        const full = y(1);
        // Water: the rain of each day hangs from the top, the deepest day about a third of the way down.
        let rain = '';
        const isW = R.check.kind === 'water' && R.water;
        if (isW) {
            const mx = Math.max(30, ...R.water.r);
            let rk = 0;
            R.water.r.forEach((r, i) => {
                if (r < 0.5) return;
                const h = Math.max(1.5, r / mx * (base - top) * 0.34);
                rain += `<rect class="rain" style="--k:${rk++}" x="${(x(i) - (W / n) * 0.42).toFixed(1)}" y="${top}" width="${Math.max(1, (W / n) * 0.84).toFixed(1)}" height="${h.toFixed(1)}"><title>${say(days[i].d)}: rain ${Math.round(r)} mm${R.water.k[i] ? ', as it fell' : ''}</title></rect>`;
            });
        }
        let dots = '';
        R.apps.filter((a) => a.relevant).forEach((a) => {
            const i = days.findIndex((d) => d.d === a.date);
            if (i < 0) return;
            dots += `<circle class="app ${a.try ? 'is-try' : a.done ? 'is-done' : 'is-plan'}" cx="${x(i).toFixed(1)}" cy="${base + 32}" r="5.5" data-jump="${a.date}"><title>${esc(a.as)} · ${say(a.date)}</title></circle>`;
        });
        return `<div class="rfx-card" data-fold="chart" style="--d:.08s"><div class="rfx-card-h">The season <small>${isW ? 'water for the roots, day by day' : `coverage of ${esc(R.check.word)}, day by day`}</small></div>${lnRowHtml(R)}<div class="rfx-chart"><div class="rfx-chart-w"><div class="rfx-chart-in">
            <div class="rfx-sbar" role="group" aria-label="Growth stages: tap one to see its dates">${marks}</div>
            <svg viewBox="0 -4 ${W} ${H + 26}" aria-label="Coverage over the season">
              <defs><linearGradient id="rfxPhG" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="${W}" y2="0">${stops}</linearGradient>
                <linearGradient id="rfxCovV" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".9"/><stop offset="1" stop-color="#fff" stop-opacity=".12"/></linearGradient>
                <mask id="rfxCovM" maskUnits="userSpaceOnUse" x="0" y="0" width="${W}" height="${base + 1}"><rect x="0" y="0" width="${W}" height="${base + 1}" fill="url(#rfxCovV)"/></mask></defs>
              ${bands}${rain}
              <line class="dose" x1="0" x2="${W}" y1="${full.toFixed(1)}" y2="${full.toFixed(1)}"/><text class="mo" x="2" y="${(full - 4).toFixed(1)}">${isW ? (R.water.facts.paddy ? 'standing water' : 'a full root zone') : 'one full dose'}</text>
              <g class="reveal"><path class="ln${offMain}" data-ln="${mk}" d="${area}" fill="url(#rfxPhG)" mask="url(#rfxCovM)"/>${others}<path class="cov-l ln${offMain}" data-ln="${mk}" d="${line}" ${cmp ? `style="stroke:${colorOf(R.check.key)};stroke-width:2.6"` : ''}/>${tryLine ? `<path class="try-l ln${offMain}" data-ln="${mk}" d="${tryLine}"/>` : ''}</g>
              <line class="base" x1="0" x2="${W}" y1="${base}" y2="${base}"/>
              <g class="ln${offMain}" data-ln="${mk}">${ribbon}</g>${months}${today}${harv}${dots}
            </svg></div></div></div>
            ${phaseLegend(R)}
            <div class="rfx-legend"><span><i class="rfx-leg-band is-crit"></i>Critical stage</span><span><i class="rfx-leg-band is-mid"></i>Matters</span>${isW ? '<span><i style="background:var(--rx-rain);opacity:.6"></i>Rain, from the top</span>' : ''}${R.days.some((d) => d.t) ? '<span><i style="background:#f5c518"></i>Added by you</span>' : ''}<span><i class="rfx-leg-dot is-done"></i>Done</span><span><i class="rfx-leg-dot is-plan"></i>Planned</span></div></div>`;
    };
    /* The phases this reading shows, in the order one application lives them. */
    const phaseLegend = (R, all = null) => {
        const used = new Set();
        R.days.forEach((d) => { if (d.p) used.add(d.p); if (d.sl) used.add('soil'); });
        (all || []).forEach((x) => x.p.forEach((p) => { if (p) used.add(p); }));
        // Side by side, the checks may be nutrients and sprays at once: plain names that fit both.
        const words = all ? { soil: 'In the soil, or taking hold', absorb: 'Being taken in', strong: 'Strongest', easing: 'Easing', fading: 'Fading away', wet: 'Wet', moist: 'Moist', drying: 'Drying', dry: 'Too dry' } : (R.phaseWords || {});
        const items = PH_ORDER.filter((p) => used.has(p));
        if (!items.length) return '';
        const onlyW = items.every((p) => ['wet', 'moist', 'drying', 'dry'].includes(p));
        return `<div class="rfx-legend rfx-flow"><b class="rfx-leg-h">${onlyW ? 'The water in the soil' : 'What each product is doing'}</b>${items.map((p, k) => `${k ? '<em aria-hidden="true">›</em>' : ''}<span style="--k:${k}"><i class="rfx-sw is-${p}"></i>${esc(words[p] || p)}</span>`).join('')}</div>`;
    };

    /* The calendar: month by month, each day filled to its coverage, with its criticality as dots. */
    const calHtml = (R) => {
        const byDay = Object.fromEntries(R.days.map((d) => [d.d, d]));
        // Every product on the lot's tasks and every one added, those acting on the check first on their day.
        const appsOn = {};
        R.apps.forEach((a) => { (appsOn[a.date] ||= []).push(a); });
        Object.values(appsOn).forEach((l) => l.sort((a, b) => Number(b.relevant) - Number(a.relevant)));
        const word = R.check.word;
        const isW = R.check.kind === 'water' && R.water;
        const ixOf = isW ? Object.fromEntries(R.days.map((d, i) => [d.d, i])) : {};
        const first = D(R.days[0].d), last = D(R.days[R.days.length - 1].d);
        let html = '', k = 0;
        for (let m = new Date(first.getFullYear(), first.getMonth(), 1); m <= last; m = new Date(m.getFullYear(), m.getMonth() + 1, 1)) {
            const cells = [];
            const lead = m.getDay();
            for (let i = 0; i < lead; i++) cells.push('<span class="rfx-cell is-out"></span>');
            const dim = new Date(m.getFullYear(), m.getMonth() + 1, 0).getDate();
            let crit = 0;
            for (let dd = 1; dd <= dim; dd++) {
                const ds = iso(new Date(m.getFullYear(), m.getMonth(), dd));
                const d = byDay[ds];
                if (!d) { cells.push(`<span class="rfx-cell is-out"><span class="rfx-n" style="opacity:.35">${dd}</span></span>`); continue; }
                crit += d.c >= 2 ? 1 : 0;
                const here = appsOn[ds] || [];
                const ap = here.slice(0, 2).map((a) => `<i class="${a.relevant ? 'is-rel' : 'is-other'}${a.try ? ' is-try' : a.done ? '' : ' is-plan'}" title="${esc(a.as)}${a.try ? ', added by you' : a.done ? ', done' : ', planned'}, ${a.water ? (a.relevant ? 'a watering' : 'left out here') : (a.relevant ? 'acts on ' : 'not on ') + esc(word)}"><svg><use href="#rfxI-${apMark(a)}"/></svg></i>`).join('')
                    + (here.length > 2 ? `<b>+${here.length - 2}</b>` : '');
                // Text over the fill: the number when it is nearly full, the day count from about half; white on the
                // deep fills, near black on the pale ones.
                const full = d.v >= 0.7 && d.s !== 'gap';
                const half = !full && d.v >= 0.42 && d.s !== 'gap';
                const pale = (full || half) && ['easing', 'fading', 'drying', 'dry'].includes(d.p);
                // Water: the day's rain at its foot, from 5 mm.
                const rv = isW ? R.water.r[ixOf[ds]] : 0;
                const rn = rv >= 5 ? `<span class="rfx-rn" title="Rain ${Math.round(rv)} mm${R.water.k[ixOf[ds]] ? ', as it fell' : ', from the year counted'}"><svg aria-hidden="true"><use href="#rfxI-rain"/></svg>${Math.round(rv)}</span>` : '';
                cells.push(`<button type="button" class="rfx-cell ${ds === R.today ? 'is-today' : ''} ${full ? 'is-full' : ''} ${half ? 'is-half' : ''} ${pale ? 'is-lt' : ''}" data-day="${ds}" data-s="${d.s}" data-p="${d.p || ''}" data-t="${d.t ? 1 : 0}" style="--k:${k++};--h:${Math.round(Math.min(1, d.v) * 100)}%">
                    <span class="rfx-fill"></span>${d.sl ? '<span class="rfx-soil"></span>' : ''}<span class="rfx-n">${dd}</span>${d.dl ? `<span class="rfx-dl">${esc(d.dl)}</span>` : ''}
                    ${d.c ? `<span class="rfx-crit c${d.c}" aria-label="${CRIT[d.c]}"><i></i><i></i><i></i></span>` : ''}${ap ? `<span class="rfx-ap">${ap}</span>` : ''}${rn}${d.h ? `<span class="rfx-hm is-${d.h}" title="${d.h === 'n' ? 'Nitrogen is lush: scout more often' : 'Potassium or silicon is helping the crop hold out'}">${d.h === 'n' ? 'N' : 'K'}</span>` : ''}</button>`);
            }
            html += `<div class="rfx-month"><div class="rfx-month-h"><b>${MON[m.getMonth()]} ${m.getFullYear()}</b>${crit ? `<small>${crit} critical day${crit === 1 ? '' : 's'}</small>` : ''}</div>
                <div class="rfx-wk">${DOW.map((w) => `<span class="rfx-wd">${w}</span>`).join('')}${cells.join('')}</div></div>`;
        }
        const late = R.check.kind === 'nutrient' ? 'late' : 'extra';
        return `<div class="rfx-card" data-fold="cal" style="--d:.14s;--sc:${colorOf(R.check.key)}"><div class="rfx-card-h">The calendar <small>tap a day</small></div><div class="rfx-cal">${html}</div>
            ${phaseLegend(R)}
            <div class="rfx-legend"><b class="rfx-leg-h">What it means for the crop</b>
              <span><i style="background:repeating-linear-gradient(135deg,var(--rx-gap-a) 0 3px,var(--rx-gap-edge) 3px 5px);box-shadow:inset 0 0 0 1.5px var(--rx-gap-edge)"></i>${stName('gap', R.check.kind)}</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #f59e0b"></i>${isW ? stName('thin', 'water') : STATE.thin[0] + ', only partly covered'}</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #a78bfa;background:repeating-linear-gradient(135deg,rgb(124 58 237 / .45) 0 2px,#fff 2px 5px)"></i>${stName(late, R.check.kind)}</span>
              <span><span class="rfx-crit c3" style="position:static"><i></i><i></i><i></i></span>&nbsp;How critical the stage is</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px var(--rx-today)"></i>Today</span>
              ${R.days.some((x) => x.h === 'n') ? '<span><span class="rfx-hm is-n" style="position:static">N</span>Nitrogen lush: pests and disease like it</span>' : ''}
              ${R.days.some((x) => x.h === 'k') ? '<span><span class="rfx-hm is-k" style="position:static">K</span>Potassium or silicon helping the crop hold out</span>' : ''}</div>
            <div class="rfx-legend rfx-leg-ap"><b class="rfx-leg-h">What went on</b>
              ${isW ? `<span><span class="rfx-ap"><i class="is-rel"><svg><use href="#rfxI-wave"/></svg></i></span>A watering or a drain, in this colour</span>
              <span><span class="rfx-ap"><i class="is-other"><svg><use href="#rfxI-gran"/></svg></i></span>Grey: a product, not water</span>
              <span><span class="rfx-rn" style="position:static"><svg aria-hidden="true"><use href="#rfxI-rain"/></svg>12</span>Rain that day, mm</span>
              <span><span class="rfx-ap"><i class="is-rel is-try"><svg><use href="#rfxI-wave"/></svg></i></span>Added by you</span>
              <span><span class="rfx-ap"><i class="is-rel is-plan"><svg><use href="#rfxI-wave"/></svg></i></span>Outlined: still planned</span>` : `
              <span><span class="rfx-ap"><i class="is-rel"><svg><use href="#rfxI-gran"/></svg></i></span>In this colour: acts on ${esc(word)}</span>
              <span><span class="rfx-ap"><i class="is-other"><svg><use href="#rfxI-gran"/></svg></i></span>Grey: not on ${esc(word)}</span>
              <span><span class="rfx-ap"><i class="is-rel"><svg><use href="#rfxI-drop"/></svg></i></span>Sprayed or poured</span>
              <span><span class="rfx-ap"><i class="is-rel"><svg><use href="#rfxI-leaf"/></svg></i></span>Organic or biological</span>
              <span><span class="rfx-ap"><i class="is-rel is-try"><svg><use href="#rfxI-star"/></svg></i></span>Added by you</span>
              <span><span class="rfx-ap"><i class="is-rel is-plan"><svg><use href="#rfxI-gran"/></svg></i></span>Outlined: still planned</span>`}</div></div>`;
    };
    // How a product went on, as its mark: a try's star, a spray's drop, organic's leaf, else granules.
    const apMark = (a) => a.water ? 'wave' : a.try ? 'star' : ['foliar', 'drench'].includes(a.form) ? 'drop' : ['organic', 'bio'].includes(a.form) ? 'leaf' : 'gran';

    /* The products, each with how long it keeps working. */
    const appsHtml = (R) => {
        const isW = R.check.kind === 'water';
        if (isW) return watersHtml(R);
        const rel = R.apps.filter((a) => a.relevant);
        // The waterings act on water only: they stay out of a product's list.
        const other = R.apps.filter((a) => !a.relevant && !a.water);
        const span = R.days.length;
        const first = D(R.days[0].d).getTime();
        const pos = (s) => Math.max(0, Math.min(100, ((D(s).getTime() - first) / 864e5) / Math.max(1, span - 1) * 100));
        const rows = rel.map((a, k) => {
            const e = a.effect;
            const d = D(a.date);
            const doseWord = e.dose === 'unknown' ? 'amount not read, counted as one usual dose' : e.dose === 'trace' ? 'very light dose' : e.dose ? e.dose + ' dose' : '';
            const by = e.by && R.check.kind !== 'nutrient' ? ` by ${esc(e.by)}` : '';
            // The bar in its phases: in the soil, taken up, strongest, easing, fading (one plain bar for an old saved reading).
            const dayW = 100 / Math.max(1, span - 1);
            const phs = e.phases || [];
            const segs = phs.length ? phs.map((ph) => `<i class="is-${ph.p}" style="left:${pos(ph.from)}%;width:${Math.max(.6, pos(ph.to) - pos(ph.from) + dayW).toFixed(2)}%"></i>`).join('')
                : `<i class="is-strong" style="left:${pos(e.from)}%;width:${Math.max(1.5, pos(e.until) - pos(e.from))}%"></i>`;
            const phRow = phs.length ? `<div class="rfx-phs">${phs.map((ph) => `<span><i class="rfx-sw is-${ph.p}"></i>${esc(ph.w)} <small>${ph.n} day${ph.n === 1 ? '' : 's'}</small></span>`).join('')}</div>` : '';
            const trace = e.dose === 'trace' && e.kgHa !== null ? `<small class="rfx-trace">Very little at this amount: about ${massSay(e.kgHa)} of ${EL[e.el] || esc(e.el)} a hectare, where one usual ${a.form === 'foliar' ? 'leaf spray' : 'dose on the soil'} gives about ${massSay(e.ref)}.</small>` : '';
            return `<div class="rfx-app ${a.try ? 'is-try' : ''}" style="--d:${.18 + Math.min(k, 10) * .04}s">
                <span class="rfx-app-d"><small>${MON[d.getMonth()]}</small><b>${d.getDate()}</b></span>
                <div class="rfx-app-t"><b>${esc(a.as)}</b>${a.try ? '<span class="rfx-tag is-try">Added by you</span>' : a.done ? '<span class="rfx-tag is-done">Done</span>' : '<span class="rfx-tag">Planned</span>'}${e.conf === 'estimate' || e.conf === 'low' ? '<span class="rfx-tag is-est">Estimated</span>' : ''}
                  <small>${esc([a.title !== 'If you apply it' ? a.title : '', a.amount, a.formWord, doseWord].filter(Boolean).join(' · '))}</small>
                  <small>Works${by} from <b>${say(e.from)}</b> to about <b>${say(e.full)}</b>, fading out by ${say(e.until)} (${e.days[1]} days, ${e.days[0]} to ${e.days[2]}).</small>
                  <div class="rfx-bar"><span class="rfx-segs">${segs}</span></div>${phRow}${trace}
                  ${e.note ? `<div class="rfx-app-n">${esc(e.note)}</div>` : ''}</div></div>`;
        }).join('');
        const none = !rel.length ? `<div class="rfx-empty" style="padding:1.2rem"><p>Nothing on this lot's tasks acts on ${esc(R.check.word)} yet. Add it to a task, or add a product under Fertilizers above to see what it would do.</p></div>` : '';
        const oth = other.length ? `<details class="rfx-other"><summary>${other.length} other product${other.length === 1 ? '' : 's'} on this lot do not act on ${esc(R.check.word)}</summary><ul>${other.map((a) => `<li>${say(a.date)} · ${esc(a.as)}${a.carries.length ? ' (' + esc(a.carries.join(', ')) + ')' : a.ai.length ? ' (' + esc(a.ai.join(' + ')) + ')' : ''}</li>`).join('')}</ul></details>` : '';
        return `<div class="rfx-card" data-fold="apps" style="--d:.2s"><div class="rfx-card-h">What is working <small>${rel.length} product${rel.length === 1 ? '' : 's'}</small></div><div class="rfx-apps">${rows}${none}</div>${oth}</div>`;
    };

    /* The waterings, each with how long it keeps the crop out of stress (a water check). */
    const watersHtml = (R) => {
        const rel = R.apps.filter((a) => a.relevant && a.water).sort((a, b) => a.date.localeCompare(b.date));
        const out = R.apps.filter((a) => !a.relevant && a.water);
        const span = R.days.length;
        const first = D(R.days[0].d).getTime();
        const pos = (s) => Math.max(0, Math.min(100, ((D(s).getTime() - first) / 864e5) / Math.max(1, span - 1) * 100));
        const dayW = 100 / Math.max(1, span - 1);
        const rows = rel.map((a, k) => {
            const e = a.effect || {};
            const w = e.water || {};
            const d = D(a.date);
            const phs = e.phases || [];
            const segs = phs.map((ph) => `<i class="is-${ph.p}" style="left:${pos(ph.from)}%;width:${Math.max(.6, pos(ph.to) - pos(ph.from) + dayW).toFixed(2)}%"></i>`).join('');
            const phRow = phs.length ? `<div class="rfx-phs">${phs.map((ph) => `<span><i class="rfx-sw is-${ph.p}"></i>${esc(ph.w)} <small>${ph.n} day${ph.n === 1 ? '' : 's'}</small></span>`).join('')}</div>` : '';
            const line = w.task === 'drain'
                ? (w.stress ? `The soil dries from <b>${say(a.date)}</b>: short of water from <b>${say(w.stress)}</b> unless rain or a watering comes.` : 'The soil stays safe until the next watering or rain.')
                : (e.days ? `Keeps the crop out of stress until about <b>${say(e.full)}</b> (${e.days[1]} day${e.days[1] === 1 ? '' : 's'})${w.stress ? `, short of water from <b>${say(w.stress)}</b>` : ''}.` : '');
            return `<div class="rfx-app ${a.try ? 'is-try' : ''}" style="--d:${.18 + Math.min(k, 10) * .04}s">
                <span class="rfx-app-d"><small>${MON[d.getMonth()]}</small><b>${d.getDate()}</b></span>
                <div class="rfx-app-t"><b>${esc(a.as)}</b>${a.try ? '<span class="rfx-tag is-try">Added by you</span>' : a.done ? '<span class="rfx-tag is-done">Done</span>' : '<span class="rfx-tag">Planned</span>'}
                  <small>${esc([a.title !== 'If you water' ? a.title : '', w.net ? `about ${w.net} mm into the ${R.water && R.water.facts.paddy ? 'paddy' : 'root zone'}` : ''].filter(Boolean).join(' · '))}</small>
                  ${line ? `<small>${line}</small>` : ''}
                  <div class="rfx-bar"><span class="rfx-segs">${segs}</span></div>${phRow}
                  ${e.note ? `<div class="rfx-app-n">${esc(e.note)}</div>` : ''}</div></div>`;
        }).join('');
        const none = !rel.length ? `<div class="rfx-empty" style="padding:1.2rem"><p>${R.check.mode === 'rain' ? 'Rain only: the waterings are left out of this reading, so you see what the rain gives by itself.' : 'No watering on this lot\'s tasks yet. Add one under Watering above to see how long it keeps the crop out of stress.'}</p></div>` : '';
        const oth = out.length ? `<details class="rfx-other"><summary>${out.length} watering${out.length === 1 ? '' : 's'} left out of this reading</summary><ul>${out.map((a) => `<li>${say(a.date)} · ${esc(a.as)}</li>`).join('')}</ul></details>` : '';
        return `<div class="rfx-card" data-fold="apps" style="--d:.2s"><div class="rfx-card-h">Your waterings <small>${rel.length} on this reading</small></div><div class="rfx-apps">${rows}${none}</div>${oth}</div>`;
    };

    /* One day, opened. */
    const dayBox = $('[data-rfx-day]');
    const hideDay = () => { dayBox.hidden = true; };
    $('[data-rfx-body]').addEventListener('keydown', (e) => {
        const fh = e.target.closest('.rfx-fold-h');
        if (fh && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); const c = fh.closest('[data-fold]'); setFold(c, !c.classList.contains('is-open')); }
    });
    $('[data-rfx-body]').addEventListener('click', (e) => {
        const fa = e.target.closest('[data-fold-all]');
        if (fa) {
            const v = fa.dataset.foldAll === '1';
            $$('[data-rfx-body] .rfx-card[data-fold]').forEach((c) => setFold(c, v));
            return;
        }
        const fh = e.target.closest('.rfx-fold-h');
        if (fh) {
            const c = fh.closest('[data-fold]');
            setFold(c, !c.classList.contains('is-open'));
            return;
        }
        const lt = e.target.closest('[data-ln-t]');
        if (lt) { toggleLine(lt.dataset.lnT); return; }
        const sb = e.target.closest('[data-stage]');
        if (sb) { openStage(Number(sb.dataset.stage)); return; }
        const cp = e.target.closest('[data-copy-ans]');
        if (cp) {
            cp.disabled = true;
            window.api(U.copyAnswers, { method: 'POST', body: { to: Number(cp.dataset.to), from: Number(cp.dataset.from) } })
                .then((r) => { window.toast?.(r.message); run(); })
                .catch((err) => { window.toast?.(err.message, 'error'); cp.disabled = false; });
            return;
        }
        const go = e.target.closest('[data-goto]');
        if (go) {
            const k = go.dataset.goto;
            S.tab = tabOf(k);
            paintPicks();
            if (S.checks.includes(k)) focusCheck(k); else togglePick(k);
            $('[data-rfx-scroll]').scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' });
            return;
        }
        const jump = e.target.closest('[data-jump]');
        if (jump) {
            const cell = $(`.rfx-cell[data-day="${jump.dataset.jump}"]`);
            if (cell) {
                // A closed calendar opens first, then the day comes into view.
                const f = cell.closest('[data-fold]');
                const wait = f && !f.classList.contains('is-open') ? (calm ? 0 : 340) : 0;
                if (wait || (f && !f.classList.contains('is-open'))) setFold(f, true);
                setTimeout(() => { cell.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'center' }); setTimeout(() => showDay(jump.dataset.jump), calm ? 0 : 450); }, wait);
            }
            return;
        }
        const f = e.target.closest('[data-focus]');
        if (f) { focusCheck(f.dataset.focus); return; }
        // A lane of the side by side view: the day under the tap.
        const lane = e.target.closest('[data-lane]');
        if (lane && S.res) {
            const r = lane.getBoundingClientRect();
            const i = Math.max(0, Math.min(S.res.days.length - 1, Math.floor((e.clientX - r.left) / r.width * S.res.days.length)));
            showDay(S.res.days[i].d);
            return;
        }
        const c = e.target.closest('[data-day]');
        if (c) showDay(c.dataset.day);
        if (e.target.closest('[data-rfx-rerun]')) { S.saved = null; run(); }
    });
    const showDay = (ds) => {
        const R = S.res;
        const d = R.days.find((x) => x.d === ds);
        if (!d) return;
        const g = R.stages[d.g] || {};
        // Every product at work that day, and those still in the soil, each with what it is doing.
        const phaseOn = (a) => (a.effect?.phases || []).find((ph) => ph.from <= ds && ds <= ph.to) || null;
        const apps = d.a.map((i) => R.apps.find((a) => a.i === i)).filter(Boolean)
            .concat(R.apps.filter((a) => a.relevant && !d.a.includes(a.i) && phaseOn(a)?.p === 'soil'));
        const pct = Math.round(d.v * 100);
        const col = d.p ? `var(--ph-${d.p})` : (STATE[d.s] ? STATE[d.s][1] : '#86c25a');
        const cmpOn = (R.series || []).length > 0;
        if (R.check.kind === 'water' && R.water) { waterDay(R, d, g, apps, phaseOn); return; }
        dayBox.innerHTML = `<div class="rfx-day-h"><div><b>${DOW[D(ds).getDay()]}, ${sayY(ds)}</b><small>${esc([d.dl, g.label].filter(Boolean).join(' · '))}</small></div><button type="button" class="rfx-x" data-rfx-day-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-meter"><i style="width:0;background:${col}" data-w="${Math.min(100, pct)}"></i></div>
            <p>${cmpOn ? `<b style="color:${colorOf(R.check.key)}">${esc(R.check.label)}</b>: ` : ''}<b>${STATE[d.s] ? STATE[d.s][0] : ''}</b>. Coverage ${pct}% of one full dose. ${d.c ? 'This stage: ' + CRIT[d.c].toLowerCase() + '.' : ''} ${esc(g.why || '')}</p>
            ${apps.length ? `<ul>${apps.map((a) => { const ph = phaseOn(a); return `<li><i class="rfx-sw is-${ph ? ph.p : 'strong'}"></i> <b>${esc(a.as)}</b> (${say(a.date)}${a.try ? ', added by you' : ''}): ${ph ? esc(ph.w.charAt(0).toLowerCase() + ph.w.slice(1)) + (ph.p === 'soil' ? ', taken up from ' + say(a.effect.from) : ph.p === 'fading' ? ', gone by about ' + say(a.effect.until) : ', working until about ' + say(a.effect.full)) : 'working until about ' + say(a.effect.full)}</li>`; }).join('')}</ul>` : '<p style="color:#6b7280">Nothing applied is working on this day.</p>'}`;
        // How much that day and so far; the crop's health that day.
        const A = R.logic?.amt;
        const ix = R.days.indexOf(d);
        let more = '';
        if (A && A.known && A.n && !A.soilGives) {
            let cg = 0, cn = 0;
            for (let j = 0; j <= ix; j++) { if (!A.in || A.in[j]) { cg += A.g[j]; cn += A.n[j]; } }
            const g = A.g[ix], nd = A.n[ix];
            more += `<div class="rfx-day-amt">⚖️ <b>That day</b> your products give about ${kgSay(g)} ${A.unit || 'kg'} ${esc(A.say)} a hectare; the crop takes about ${kgSay(nd)}${nd > 0.0005 ? ` (${Math.round(g / nd * 100)}%)` : ''}. <b>So far</b>: ${kgSay(cg)} of ${kgSay(cn)} ${A.unit || 'kg'}.</div>`;
        }
        if (d.h) more += `<div class="rfx-day-amt">${d.h === 'n' ? '🌿 <b>Nitrogen is lush</b> on this day: soft leaves draw pests and disease. Scout more often.' : '🛡️ <b>Potassium or silicon</b> is working on this day, helping the crop hold out.'}</div>`;
        if (more) dayBox.insertAdjacentHTML('beforeend', more);
        // The other checks on the same day.
        if (cmpOn) {
            const i = R.days.indexOf(d);
            dayBox.insertAdjacentHTML('beforeend', `<div class="rfx-day-cmp">${R.series.map((sr) => {
                const p = sr.p[i], st = sr.s[i];
                return `<div style="--sc:${colorOf(sr.check.key)}"><i></i><b>${esc(sr.check.label)}</b><span class="rfx-meter"><i style="width:${Math.min(100, Math.round(sr.v[i] * 100))}%;background:${p ? `var(--ph-${p})` : 'var(--rx-dot)'}"></i></span><small>${Math.round(sr.v[i] * 100)}% · ${esc(stName(st, sr.check.kind).split(':')[0])}${p ? ' · ' + esc((sr.words || {})[p] || '') : ''}</small></div>`;
            }).join('')}</div>`);
        }
        dayBox.hidden = false;
        requestAnimationFrame(() => { const m = dayBox.querySelector('[data-w]'); if (m) m.style.width = m.dataset.w + '%'; });
    };
    dayBox.addEventListener('click', (e) => { if (e.target.closest('[data-rfx-day-x]')) hideDay(); });
    /* A day of a water check: the water left for the roots, the rain, what the crop drank, the watering carrying it. */
    const waterDay = (R, d, g, apps, phaseOn) => {
        const W = R.water, F = W.facts || {};
        const i = R.days.indexOf(d);
        const lv = W.lv[i], r = W.r[i], e = W.e[i], gi = W.g[i];
        const col = d.p ? `var(--ph-${d.p})` : '#86c25a';
        const level = F.paddy
            ? (lv >= 5 ? `About ${Math.round(lv / 10 * 10) / 10} cm of standing water.` : lv > -3 ? 'Soaked, with no standing water.' : `The water is about ${Math.max(1, Math.round(-lv * 0.6))} cm below the surface; rice is safe down to about 15 cm.`)
            : `About ${Math.round(lv)} of ${Math.round(W.top[i])} mm left for the roots; the crop suffers below ${Math.round(W.line[i])} mm.`;
        const cmpOn = (R.series || []).length > 0;
        const ws = apps.filter((a) => a.water);
        dayBox.innerHTML = `<div class="rfx-day-h"><div><b>${DOW[D(d.d).getDay()]}, ${sayY(d.d)}</b><small>${esc([d.dl, g.label].filter(Boolean).join(' · '))}</small></div><button type="button" class="rfx-x" data-rfx-day-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-meter"><i style="width:0;background:${col}" data-w="${Math.min(100, Math.round(d.v * 100))}"></i></div>
            <p>${cmpOn ? `<b style="color:${colorOf(R.check.key)}">${esc(R.check.label)}</b>: ` : ''}<b>${esc(stName(d.s, 'water'))}</b>. ${esc(level)} ${d.c ? 'This stage: ' + CRIT[d.c].toLowerCase() + '.' : ''} ${esc(g.why || '')}</p>
            <div class="rfx-day-amt">🌧️ <b>Rain</b> ${r >= 0.5 ? `about ${Math.round(r)} mm${W.k[i] ? ', as it fell' : ', from the year counted'}` : 'none'} · 🌱 <b>The crop drank</b> about ${Math.round(e * 10) / 10} mm${gi >= 0.5 ? ` · 🚿 <b>Watering</b> about ${Math.round(gi)} mm` : ''}</div>
            ${ws.length ? `<ul>${ws.map((a) => { const ph = phaseOn(a), st = a.effect?.water?.stress; return `<li><i class="rfx-sw is-${ph ? ph.p : 'moist'}"></i> <b>${esc(a.as)}</b> (${say(a.date)}${a.try ? ', added by you' : ''}): ${st ? 'short of water from ' + say(st) : 'enough until about ' + say(a.effect?.full || a.date)}</li>`; }).join('')}</ul>` : `<p style="color:var(--rx-muted)">${r >= 10 ? 'The rain carries the crop on this day.' : 'No recent watering carries this day.'}</p>`}`;
        if (cmpOn) {
            dayBox.insertAdjacentHTML('beforeend', `<div class="rfx-day-cmp">${R.series.map((sr) => {
                const p = sr.p[i], st = sr.s[i];
                return `<div style="--sc:${colorOf(sr.check.key)}"><i></i><b>${esc(sr.check.label)}</b><span class="rfx-meter"><i style="width:${Math.min(100, Math.round(sr.v[i] * 100))}%;background:${p ? `var(--ph-${p})` : 'var(--rx-dot)'}"></i></span><small>${Math.round(sr.v[i] * 100)}% · ${esc(stName(st, sr.check.kind).split(':')[0])}${p ? ' · ' + esc((sr.words || {})[p] || '') : ''}</small></div>`;
            }).join('')}</div>`);
        }
        dayBox.hidden = false;
        requestAnimationFrame(() => { const m = dayBox.querySelector('[data-w]'); if (m) m.style.width = m.dataset.w + '%'; });
    };

    /* ---------------- save, and saved ---------------- */
    const paintSavedCount = () => {
        const n = (S.opts?.saved || []).length;
        const b = $('[data-rfx-saved-n]');
        b.hidden = !n;
        b.textContent = n;
    };
    // A saved reading opened (or just saved) is the one Save puts the changes over (the owner, 2026-10-30).
    const defaultTitle = () => [S.res.check.label, ...(S.res.series || []).map((x) => x.check.label)].join(', ') + ` · ${PB ? (lotOf()?.name && S.lot ? lotOf().name : 'no lot') + ', from ' + say(S.pb.start) : S.res.lot.name}` + (S.tries.length ? ` · with ${S.tries.map((t) => t.name).join(', ')}` : '');
    const paintFoot = () => {
        const ed = !!S.savedId;
        $('[data-rfx-editing]').hidden = !ed;
        $('[data-rfx-note]').hidden = ed;
        $('[data-rfx-save-new]').hidden = !ed;
        $('[data-rfx-save-t]').textContent = ed ? 'Update saved reading' : 'Save this reading';
        $('[data-rfx-editing-t]').textContent = S.savedTitle || '';
    };
    const doSave = async (asNew) => {
        if (!S.res || S.busy) return;
        S.busy = true;
        const btns = [$('[data-rfx-save]'), $('[data-rfx-save-new]')];
        btns.forEach((b) => { b.disabled = true; });
        try {
            const id = asNew ? null : S.savedId;
            const r = await window.api(U.save, { method: 'POST', body: { ...readingBody(), id, title: id ? S.savedTitle : defaultTitle() } });
            S.opts.saved = r.data.saved;
            S.savedId = r.data.id;
            S.savedTitle = r.data.title;
            paintSavedCount();
            paintFoot();
            window.toast?.(r.data.updated ? 'Updated. The saved reading now holds these changes.' : 'Saved. Rename it below, or open it any time from Saved.');
        } catch (err) { window.toast?.(err.message || 'It could not be saved.', 'error'); }
        finally { S.busy = false; btns.forEach((b) => { b.disabled = false; }); }
    };
    $('[data-rfx-save]').addEventListener('click', () => doSave(false));
    $('[data-rfx-save-new]').addEventListener('click', () => doSave(true));
    $('[data-rfx-stop-edit]').addEventListener('click', () => { S.savedId = null; S.savedTitle = ''; S.saved = null; paintFoot(); if (S.res) paint(); });
    const renameSaved = async (id, title) => {
        const t = String(title || '').trim();
        if (!t) return false;
        try {
            const r = await window.api(U.saved + id + '/name', { method: 'POST', body: { scheduleId: SID, title: t } });
            S.opts.saved = r.data.saved;
            if (S.savedId === id) { S.savedTitle = r.data.title; paintFoot(); }
            return true;
        } catch (err) { window.toast?.(err.message || 'It could not be renamed.', 'error'); return false; }
    };
    // Renaming the one being edited, in the foot.
    const curIn = $('[data-rfx-editing-in]');
    $('[data-rfx-rename-cur]').addEventListener('click', () => {
        curIn.value = S.savedTitle;
        curIn.hidden = false;
        $('[data-rfx-editing-t]').hidden = true;
        curIn.focus();
        curIn.select();
    });
    const endCur = async (keep) => {
        if (curIn.hidden) return;
        const v = curIn.value.trim();
        curIn.hidden = true;
        $('[data-rfx-editing-t]').hidden = false;
        if (keep && v && v !== S.savedTitle && await renameSaved(S.savedId, v)) window.toast?.('Renamed.');
    };
    curIn.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); endCur(true); } else if (e.key === 'Escape') { e.stopPropagation(); endCur(false); } });
    curIn.addEventListener('blur', () => endCur(true));
    const openSaved = () => {
        const list = S.opts?.saved || [];
        const lots = Object.fromEntries((S.opts?.lots || []).map((l) => [l.id, l.name]));
        $('[data-rfx-saved]').innerHTML = list.length ? list.map((s) => `<div class="rfx-sv ${s.id === S.savedId ? 'is-cur' : ''}"><button type="button" class="rfx-sv-open" data-open-saved="${s.id}"><b>${esc(s.title)}</b><small>${esc(s.at)}${s.edited ? ' · changed ' + esc(s.edited) : ''}${lots[s.lotId] ? ' · ' + esc(lots[s.lotId]) : ''}${s.id === S.savedId ? ' · open now' : ''}</small></button><button type="button" class="rfx-sv-x" data-ren-saved="${s.id}" aria-label="Rename">${PEN}</button><button type="button" class="rfx-sv-x" data-del-saved="${s.id}" aria-label="Remove">${TRASH}</button></div>`).join('')
            : '<p class="rfx-muted" style="padding:.5rem">Nothing saved yet. Save a reading to keep it as it stands today.</p>';
        $('[data-rfx-drawer]').hidden = false;
        requestAnimationFrame(() => $('[data-rfx-drawer]').classList.add('is-on'));
    };
    $('[data-rfx-saved]').addEventListener('click', async (e) => {
        const ren = e.target.closest('[data-ren-saved]');
        if (ren) {
            // Its name, changed in place.
            const row = ren.closest('.rfx-sv');
            const id = Number(ren.dataset.renSaved);
            const cur = (S.opts.saved || []).find((x) => x.id === id);
            const openB = row.querySelector('.rfx-sv-open');
            openB.hidden = true;
            const inp = document.createElement('input');
            inp.className = 'rfx-sv-in';
            inp.maxLength = 190;
            inp.value = cur?.title || '';
            inp.setAttribute('aria-label', 'Name of this saved reading');
            row.insertBefore(inp, openB);
            inp.focus();
            inp.select();
            let done = false;
            const end = async (keep) => {
                if (done) return;
                done = true;
                if (keep && inp.value.trim() && inp.value.trim() !== cur?.title) await renameSaved(id, inp.value);
                openSaved();
            };
            inp.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') { ev.preventDefault(); end(true); } else if (ev.key === 'Escape') { ev.stopPropagation(); end(false); } });
            inp.addEventListener('blur', () => end(true));
            return;
        }
        const del = e.target.closest('[data-del-saved]');
        if (del) {
            e.stopPropagation();
            try {
                const r = await window.api(U.saved + del.dataset.delSaved + '?scheduleId=' + SID, { method: 'DELETE' });
                S.opts.saved = r.data.saved;
                if (Number(del.dataset.delSaved) === S.savedId) { S.savedId = null; S.savedTitle = ''; paintFoot(); }
                paintSavedCount();
                openSaved();
            } catch (err) { window.toast?.(err.message, 'error'); }
            return;
        }
        const b = e.target.closest('[data-open-saved]');
        if (!b) return;
        try {
            const r = await window.api(U.saved + b.dataset.openSaved + '?scheduleId=' + SID);
            const d = r.data;
            S.res = d.result;
            S.lot = PB ? Number((d.params.pb || {}).basis ?? 0) : d.result.lot.id;
            if (PB && d.params.pb && d.params.pb.start) { S.pb = { start: d.params.pb.start, transplant: d.params.pb.transplant || addDays(d.params.pb.start, 21), ha: Number(d.params.pb.ha) > 0 ? Number(d.params.pb.ha) : 1 }; paintPbDays(); }
            S.check = d.params.check || d.result.check.key;
            S.checks = [S.check, ...(d.params.also || [])];
            S.tries = (d.params.tries || []).map((t) => ({ ...t, name: t.name || t.ai || 'Product' }));
            S.plan = planIn(d.params.plan);
            // A reading kept with a what-if lot opens with it.
            if (d.params.custom) { S.custom = { ...d.params.custom, base: S.lot }; S.useCustom = true; } else S.useCustom = false;
            S.tab = tabOf(S.check);
            S.logic = { ...LOGIC_DEF, ...(d.params.logic || {}) };
            paintLots(); paintPicks(); paintTries(); paintLogic();
            if (PB && !(S.opts.lots || []).some((l) => l.id === S.lot)) S.lot = 0;
            S.savedId = d.id;
            S.savedTitle = d.title;
            paintFoot();
            $('[data-rfx-drawer]').classList.remove('is-on');
            if ((d.result.version || '') !== d.current) {
                // Saved with an older way of counting: counted again now, with the lot, checks and tries as saved.
                S.recounted = { title: d.title, at: d.at };
                run(true);
            } else {
                S.saved = { title: d.title, at: d.at };
                paint();
            }
            $('[data-rfx-scroll]').scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' });
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
})();
