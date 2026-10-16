{{-- THE VARIETY, TWO WAYS (2026-10-15, the owner): one sheet every analysis
     shares. Search every variety on record for the crop (rice from the
     registry, the rest from the crop records, through VarietyPickController
     and App\Support\VarietyBook), with the ones suited to the area first
     where the place is known; or type a name that is not on record, as sold.
     Since 2026-10-18 (learn once) the varieties Anee looked up before are on
     the list too, and a typed name the records or an earlier lookup know
     comes back with its record, so nothing is looked up (or paid for) again.
     Include it once on a page: @include('partials.variety-pick').

     JS (window.aneeVarietyPick):
       aneeVarietyPick.open({ crop, cropLabel, country, location, season, water, elevation, problems,
                              value: { name, record }, ownNote })
         -> Promise<{ name, record } | null>
            record: { name, alias, type (hybrid|inbred|other), days, daysUnit, avg, top, unit, year, where }
            a typed name comes back with record null, "No variety" as name '', a closed sheet as null.
       aneeVarietyPick.facts(record) -> "Hybrid · 114 days · released 2019"
       aneeVarietyPick.yieldSay(record) -> "6.4 t/ha at best in trials" --}}
@once
@push('head')
<style>
    .vp-ways { position: relative; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: .8rem; padding: .25rem; border-radius: .95rem; background: var(--color-gray-100); }
    .vp-way { position: relative; z-index: 1; padding: .55rem .3rem; border: 0; background: none; border-radius: .75rem; font-size: .84rem; font-weight: 800; color: var(--color-gray-500); cursor: pointer;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .vp-way.is-on { color: #fff; }
    .vp-ind { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 2); border-radius: .75rem; background: var(--color-brand-600, #4a7c2a);
        transform: translateX(calc(var(--i, 0) * 100%)); transition: transform .28s cubic-bezier(.22,1,.36,1); box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); }
    .vp-pane { animation: vpIn .3s cubic-bezier(.22,1,.36,1) both; }
    .vp-pane[hidden] { display: none; }
    @keyframes vpIn { from { opacity: 0; transform: translateY(6px); } }
    .vp-search { position: relative; margin-bottom: .6rem; }
    .vp-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .vp-search .form-input { padding-left: 2.4rem; }
    .vp-filt { display: flex; gap: .35rem; }
    .vp-filt button { padding: .3rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .vp-filt button[aria-pressed="true"] { color: #fff; background: #4a7c2a; }
    .vp-n { margin: .6rem 0 .3rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .vp-gh { margin: .7rem 0 .35rem; font-size: .68rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: #3d6823; }
    .vp-gh small { display: block; margin-top: .1rem; font-size: .7rem; font-weight: 600; letter-spacing: 0; text-transform: none; color: var(--color-gray-500); }
    .vp-list { display: grid; gap: .35rem; padding-bottom: .5rem; }
    .vp-r { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .55rem .65rem; border-radius: .85rem; text-align: left;
        background: var(--color-white); border: 1px solid var(--color-gray-200); cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .vp-r:hover { border-color: #a8cc7e; }
    .vp-r[aria-pressed="true"] { border-color: #4a7c2a; background: #f1f8e9; }
    .vp-r.is-fit { border-color: #cfe3b8; background: linear-gradient(135deg, #f6faf0, #fff); }
    .vp-r .t { flex: 1 1 auto; min-width: 0; display: grid; gap: .08rem; }
    .vp-r .t b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .vp-r .t small { font-size: .7rem; color: #8a6a00; font-weight: 700; overflow-wrap: anywhere; }
    .vp-r .t span { font-size: .7rem; color: var(--color-gray-500); }
    .vp-r .y { flex: none; display: grid; justify-items: end; gap: .05rem; font-size: .68rem; color: var(--color-gray-500); text-align: right; }
    .vp-r .y b { font-size: .92rem; font-weight: 800; color: #2d5016; }
    .vp-more { justify-self: center; margin-top: .3rem; padding: .45rem 1rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-brand-700, #3d6823); background: var(--color-brand-50, #f3f8ec); }
    .vp-none { margin-top: .6rem; padding: .8rem .9rem; border-radius: .9rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    .vp-none[hidden] { display: none; }
    .vp-none button { margin-top: .5rem; font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; text-underline-offset: 3px; }
    .vp-own-l { display: block; margin-bottom: .35rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .vp-own-n { margin-top: .5rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-500); }
    .vp-foot { display: flex; justify-content: flex-end; gap: .6rem; }
    .vp-foot #vpClear { margin-right: auto; }
    html.dark .vp-ways { background: #121a0d; }
    html.dark .vp-r { background: #151b12; border-color: #2b3a1c; }
    html.dark .vp-r.is-fit { background: linear-gradient(135deg, #1a2414, #151b12); border-color: #3d5a24; }
    html.dark .vp-r[aria-pressed="true"] { background: #1f2b15; border-color: #6b9f3d; }
    html.dark .vp-r .t b { color: #e8efe1; }
    html.dark .vp-r .t small { color: #e0c26a; }
    html.dark .vp-r .y b { color: #a5c97e; }
    html.dark .vp-gh { color: #a5c97e; }
    html.dark .vp-filt button { background: #1f2a17; color: #b9caa8; }
    html.dark .vp-filt button[aria-pressed="true"] { background: #4a7c2a; color: #fff; }
    html.dark .vp-none { background: #121a0d; border-color: #2b3a1c; color: #b9caa8; }
    html.dark .vp-own-l { color: #d5e3c5; }
    @media (prefers-reduced-motion: reduce) {
        .vp-pane { animation: none; }
        .vp-way, .vp-ind, .vp-filt button, .vp-r { transition: none; }
    }
</style>
@endpush

@push('sheets')
<div class="sheet hidden" id="vpSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="vpTitle">Which variety?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="vp-ways" role="tablist" id="vpWays" style="--i:0">
            <span class="vp-ind" aria-hidden="true"></span>
            <button type="button" class="vp-way is-on" role="tab" aria-selected="true" data-w="rec">Search the records</button>
            <button type="button" class="vp-way" role="tab" aria-selected="false" data-w="own">Type my own</button>
        </div>
        <div class="vp-pane" id="vpRec">
            <div class="vp-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" id="vpQ" class="form-input" autocomplete="off" placeholder="Search a name, local or trade name" aria-label="Search the varieties on record">
            </div>
            <div class="vp-filt" id="vpFilt" role="group" aria-label="Kind">
                <button type="button" data-ft="" aria-pressed="true">All</button>
                <button type="button" data-ft="hybrid" aria-pressed="false">Hybrid</button>
                <button type="button" data-ft="inbred" aria-pressed="false">Not hybrid</button>
            </div>
            <p class="vp-n" id="vpN"></p>
            <div class="vp-list" id="vpList"></div>
            <div class="vp-none" id="vpNone" hidden></div>
        </div>
        <div class="vp-pane" id="vpOwn" hidden>
            <label class="vp-own-l" for="vpOwnIn">Its name, as written on the seed bag</label>
            <input type="text" id="vpOwnIn" class="form-input w-full" maxlength="80" autocomplete="off" placeholder="Like a new hybrid from your seller">
            <p class="vp-own-n" id="vpOwnNote"></p>
            <button type="button" class="btn btn-primary w-full mt-3" id="vpOwnOk">Use this name</button>
        </div>
    </div>
    <div class="sheet-footer vp-foot">
        <button type="button" class="btn btn-ghost" id="vpClear">No variety</button>
        <button type="button" class="btn btn-white" data-sheet-close>Close</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const URL_LIST = @json(route('varietypick.list'));
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fine = () => matchMedia('(pointer: fine)').matches;
    const norm = (s) => String(s || '').toLowerCase().replace(/[\s\-._]+/g, '');
    const fmt = (v) => String(Math.round(Number(v) * 10) / 10);
    const CACHE = {};
    // One open sheet at a time: its options, its rows, its answer.
    const S = { o: null, key: '', rows: null, suited: [], q: '', type: '', shown: 60, resolve: null, answer: undefined };

    const record = (r) => ({ name: r[0], alias: r[1] || null, type: r[2], days: r[3] || null, daysUnit: r[4] || 'days', avg: r[5] || null, top: r[6] || null, unit: r[7] || 't/ha', year: r[8] || null, where: r[10] || '', learned: !!r[11] });
    const facts = (rec) => !rec ? '' : [rec.type === 'hybrid' ? 'Hybrid' : 'Not hybrid', rec.days ? Math.round(rec.days) + ' ' + (rec.daysUnit || 'days') : '', rec.year ? 'released ' + rec.year : ''].filter(Boolean).join(' · ');
    const yieldSay = (rec) => !rec ? '' : rec.top ? fmt(rec.top) + ' ' + rec.unit + ' at best in trials' + (rec.avg ? ', ' + fmt(rec.avg) + ' on average' : '') : rec.avg ? fmt(rec.avg) + ' ' + rec.unit + ' on average in trials' : '';

    function way(w) {
        $('vpWays').style.setProperty('--i', w === 'own' ? 1 : 0);
        $('vpWays').querySelectorAll('.vp-way').forEach((b) => { const on = b.dataset.w === w; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', String(on)); });
        $('vpRec').hidden = w !== 'rec'; $('vpOwn').hidden = w !== 'own';
        if (fine()) setTimeout(() => (w === 'own' ? $('vpOwnIn') : (S.rows && S.rows.length ? $('vpQ') : null))?.focus(), 220);
    }
    function row(r, i, fit) {
        const cur = S.o && S.o.value && S.o.value.record ? S.o.value.record.name : null;
        const y = r[6] || r[5];
        const sub = [r[2] === 'hybrid' ? 'Hybrid' : '', r[3] ? Math.round(r[3]) + ' ' + (r[4] || 'days') : '', r[8] ? 'Released ' + r[8] : '', r[11] ? 'Anee looked it up before' : ''].filter(Boolean).join(' · ');
        return '<button type="button" class="vp-r' + (fit ? ' is-fit' : '') + '" data-i="' + i + '" aria-pressed="' + (cur === r[0]) + '"><span class="t"><b>' + esc(r[0]) + '</b>' + (r[1] ? '<small>' + esc(r[1]) + '</small>' : '')
            + (sub ? '<span>' + esc(sub) + '</span>' : '') + '</span>' + (y ? '<span class="y"><b>' + esc(fmt(y)) + '</b>' + esc(r[7] || 't/ha') + '<br>' + (r[6] ? 'at best' : 'average') + '</span>' : '') + '</button>';
    }
    function paint() {
        const none = $('vpNone');
        if (!S.rows) { $('vpList').innerHTML = '<p class="vp-n">Loading the records…</p>'; $('vpN').textContent = ''; none.hidden = true; return; }
        if (!S.rows.length) {
            $('vpList').innerHTML = ''; $('vpN').textContent = '';
            none.hidden = false;
            none.innerHTML = esc(S.o && S.o.crop ? (S.o.country && S.o.country !== 'PH' ? 'The variety records are the Philippines\' registry, so there are none for a field abroad.' : 'No varieties on record for this crop yet.') : 'Pick the crop first to search its varieties.')
                + '<br><button type="button" data-own>Type its name instead</button>';
            return;
        }
        const q = norm(S.q);
        const ok = (r) => (!S.type || (S.type === 'hybrid' ? r[2] === 'hybrid' : r[2] !== 'hybrid')) && (!q || norm(r[0] + ' ' + (r[1] || '') + ' ' + (r[9] || '')).includes(q));
        const fits = !q && S.suited.length ? S.rows.map((r, i) => [r, i]).filter(([r]) => S.suited.includes(r[0]) && ok(r)) : [];
        const fitNames = new Set(fits.map(([r]) => r[0]));
        const hits = S.rows.map((r, i) => [r, i]).filter(([r]) => ok(r) && !fitNames.has(r[0]));
        $('vpN').textContent = (hits.length + fits.length) + ' of ' + S.rows.length + (S.q ? ' match' : ' on record') + (hits.length > S.shown ? ', showing ' + (S.shown + fits.length) : '');
        none.hidden = hits.length + fits.length > 0;
        if (none.hidden === false) none.innerHTML = 'Nothing on record by that name.<br><button type="button" data-own>Type it as your own variety</button>';
        $('vpList').innerHTML = (fits.length ? '<p class="vp-gh">Suited to your area<small>The records that fit ' + esc(S.island || 'your place') + ' best, by trial results and where they were tested.</small></p>' + fits.map(([r, i]) => row(r, i, true)).join('')
                + (hits.length ? '<p class="vp-gh">Every variety on record</p>' : '') : '')
            + hits.slice(0, S.shown).map(([r, i]) => row(r, i, false)).join('')
            + (hits.length > S.shown ? '<button type="button" class="vp-more" data-more>Show ' + Math.min(60, hits.length - S.shown) + ' more</button>' : '');
    }
    async function load() {
        const o = S.o, key = [o.crop || '', o.country || '', o.location || '', o.season || '', o.water || '', o.elevation || '', (o.problems || []).join(',')].join('|');
        S.key = key;
        if (CACHE[key]) { Object.assign(S, CACHE[key]); paint(); return; }
        if (!o.crop) { S.rows = []; S.suited = []; paint(); return; }
        S.rows = null; paint();
        try {
            if (!window.api) await new Promise((r) => window.addEventListener('load', r, { once: true }));
            const p = new URLSearchParams({ crop: o.crop, country: o.country || '', location: o.location || '', season: o.season || '', water: o.water || '', elevation: o.elevation || '' });
            (o.problems || []).forEach((x) => p.append('problems[]', x));
            const r = await window.api(URL_LIST + '?' + p.toString());
            if (S.key !== key) return;
            CACHE[key] = { rows: (r.data && r.data.all) || [], suited: (r.data && r.data.suited) || [], island: (r.data && r.data.island) || null };
            Object.assign(S, CACHE[key]);
        } catch (_) { if (S.key === key) { S.rows = []; S.suited = []; } }
        paint();
        if (!S.rows.length && !(S.o.value && S.o.value.record)) way('own');
    }
    function finish(answer) {
        S.answer = answer;
        window.closeSheet('vpSheet');
    }

    function open(o) {
        o = o || {};
        if (S.resolve) { S.resolve(null); S.resolve = null; }
        S.o = o; S.q = ''; S.type = ''; S.shown = 60; S.answer = undefined;
        $('vpTitle').textContent = o.cropLabel ? o.cropLabel + ' variety' : 'Which variety?';
        $('vpQ').value = '';
        $('vpFilt').querySelectorAll('[data-ft]').forEach((x) => x.setAttribute('aria-pressed', String(x.dataset.ft === '')));
        const v = o.value || {};
        $('vpOwnIn').value = v.name && !v.record ? v.name : '';
        $('vpOwnNote').textContent = o.ownNote || 'Not on record, so its facts are looked up from what is published, and the report says plainly if nothing is found.';
        $('vpClear').hidden = !v.name;
        way(v.name && !v.record ? 'own' : 'rec');
        return new Promise((resolve) => {
            S.resolve = resolve;
            window.openSheet('vpSheet');
            load();
        });
    }

    /* Bound once the sheet is in the page: at DOMContentLoaded, or at once for
       a copy a page shell injects after load (the season Lots module, 2026-10-16). */
    const bindSheet = () => {
        const sheet = $('vpSheet');
        if (!sheet || sheet.dataset.vpBound) return;
        sheet.dataset.vpBound = '1';
        sheet.addEventListener('sheet:close', () => {
            const r = S.resolve; S.resolve = null;
            if (r) r(S.answer === undefined ? null : S.answer);
        });
        $('vpWays').addEventListener('click', (e) => { const b = e.target.closest('.vp-way'); if (b) way(b.dataset.w); });
        let t = 0;
        $('vpQ').addEventListener('input', (e) => { clearTimeout(t); t = setTimeout(() => { S.q = e.target.value.trim(); S.shown = 60; paint(); }, 120); });
        $('vpFilt').addEventListener('click', (e) => {
            const b = e.target.closest('[data-ft]');
            if (!b) return;
            S.type = b.dataset.ft; S.shown = 60;
            $('vpFilt').querySelectorAll('[data-ft]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
            paint();
        });
        $('vpRec').addEventListener('click', (e) => {
            if (e.target.closest('[data-more]')) { S.shown += 60; paint(); return; }
            if (e.target.closest('[data-own]')) { $('vpOwnIn').value = S.q || ''; way('own'); return; }
            const b = e.target.closest('[data-i]');
            if (!b || !S.rows) return;
            const r = S.rows[Number(b.dataset.i)];
            if (!r) return;
            $('vpList').querySelectorAll('.vp-r').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
            const name = r[1] && (r[0] + ' (' + r[1] + ')').length <= 80 ? r[0] + ' (' + r[1] + ')' : r[0];
            setTimeout(() => finish({ name, record: record(r) }), 160);
        });
        const own = async () => {
            const v = $('vpOwnIn').value.trim().replace(/\s{2,}/g, ' ');
            if (!v) { window.toast?.('Type the variety\'s name.', 'error'); return; }
            // A name the records or an earlier lookup know is that record (asked of the server, which decides the price the same way).
            const o = S.o || {};
            let rec = null;
            if (o.crop && (!o.country || o.country === 'PH') && window.api) {
                $('vpOwnOk').disabled = true;
                try {
                    const r = await window.api(URL_LIST + '?' + new URLSearchParams({ only: 'find', crop: o.crop, name: v, country: o.country || '' }).toString());
                    if (r.data && r.data.found) rec = record(r.data.found);
                } catch (_) { /* kept as typed */ }
                $('vpOwnOk').disabled = false;
            }
            if (rec) window.toast?.(rec.learned ? 'Anee looked up ' + rec.name + ' before, so she uses what she found.' : v + ' is on record as ' + rec.name + '.');
            finish({ name: v, record: rec });
        };
        $('vpOwnOk').addEventListener('click', own);
        $('vpOwnIn').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); own(); } });
        $('vpClear').addEventListener('click', () => finish({ name: '', record: null }));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindSheet, { once: true }); else bindSheet();

    window.aneeVarietyPick = { open, facts, yieldSay };
})();
</script>
@endpush
@endonce
