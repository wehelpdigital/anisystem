{{-- Palay prices lately near the farmer (2026-10-27, the owner: "check if any other modules can use these new
     data"): a small card under a calculator's price a kilo, wet and dry, from the dated reports of what buyers
     paid farmers in the last 75 days (App\Support\MarketBook::palayNow; program buying prices left out). Each
     price has a Use button that puts it in the calculator's own box; nothing is filled by itself. The place is
     the farmer's newest farm lot's province, changed on the place tag (remembered per visitor, localStorage
     key anee-palay-place). With the dry palay's sell plan from the Market Price Forecast: whether keeping it dry
     pays. Hidden when nothing is kept.

     $id   = the card's id;
     $url  = the calculator's own route to MarketPriceController::palay (bilad.palay, hatian.palay), so its
             worker gate holds;
     $fill = ['wet' => input id, 'dry' => input id]: where each Use puts its price (the same box twice is fine). --}}
@php
    $pnFill = $fill ?? [];
@endphp
<div class="pn" id="{{ $id }}" data-pn-url="{{ $url }}" data-pn-wet="{{ $pnFill['wet'] ?? '' }}" data-pn-dry="{{ $pnFill['dry'] ?? '' }}" hidden aria-live="polite">
    <div class="pn-head">
        <span class="pn-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l5-5 4 4 8-8"/><path d="M15 8h5v5"/></svg></span>
        <span class="pn-t"><b>Palay prices lately</b><small>What buyers paid farmers, a kilo</small></span>
        <button type="button" class="pn-place" data-pn-place><span>Near you</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
    </div>
    <div class="pn-rows" data-pn-rows>
        <div class="pn-skel"><i></i><i></i></div>
    </div>
    <p class="pn-foot" data-pn-foot hidden></p>
    <p class="pn-tip" data-pn-tip hidden></p>
</div>

@once
@push('head')
<style>
    .pn { --pn-bg: #fbf8ee; --pn-line: #ece2c2; --pn-ink: #14210c; --pn-text: #4b5563; --pn-soft: #6b7280; --pn-mark: #8a6a00; --pn-mark-bg: #fdf1c4; --pn-row: #ffffff; --pn-go: #2f6f12;
        margin-top: .9rem; padding: .85rem .9rem .9rem; border-radius: 1.1rem; background: var(--pn-bg); border: 1px solid var(--pn-line); animation: pnIn .28s cubic-bezier(.22,1,.36,1) both; }
    html.dark .pn { --pn-bg: #17190f; --pn-line: #343117; --pn-ink: #ece6cf; --pn-text: #c9c3ab; --pn-soft: #a8a28c; --pn-mark: #e7c869; --pn-mark-bg: #2f2b12; --pn-row: #1d2014; --pn-go: #a6dc7e; }
    @keyframes pnIn { from { opacity: 0; transform: translateY(6px); } }
    .pn[hidden] { display: none !important; }
    .pn-head { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
    .pn-mark { flex: none; display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: .7rem; color: var(--pn-mark); background: var(--pn-mark-bg); }
    .pn-mark svg { width: 1.05rem; height: 1.05rem; }
    .pn-t { flex: 1 1 10rem; min-width: 0; }
    .pn-t b { display: block; font-size: .9rem; font-weight: 800; line-height: 1.2; color: var(--pn-ink); }
    .pn-t small { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 700; color: var(--pn-soft); }
    /* The place sits beside the title on a wide card and under it on a phone, never cut short. */
    .pn-place { flex: none; max-width: 100%; display: inline-flex; align-items: center; gap: .3rem; min-height: 2.25rem; padding: .3rem .7rem; border-radius: 999px; border: 1.5px solid var(--pn-line);
        background: var(--pn-row); font-size: .78rem; font-weight: 800; color: var(--pn-ink); transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .pn-place span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pn-place svg { flex: none; width: .9rem; height: .9rem; color: var(--pn-mark); }
    .pn-place:hover { border-color: var(--pn-mark); transform: translateY(-1px); }
    .pn-place:focus-visible, .pn-use:focus-visible, .pn-tip a:focus-visible { outline: 3px solid rgb(134 181 86 / .55); outline-offset: 2px; }
    .pn-rows { display: grid; gap: .45rem; margin-top: .7rem; }
    .pn-row { display: flex; align-items: center; gap: .7rem; padding: .6rem .7rem; border-radius: .85rem; background: var(--pn-row); border: 1px solid var(--pn-line); animation: pnIn .28s cubic-bezier(.22,1,.36,1) both; }
    .pn-row:nth-child(2) { animation-delay: .06s; }
    .pn-row > span { flex: 1 1 auto; min-width: 0; }
    .pn-row em { display: block; font-style: normal; font-size: .74rem; font-weight: 800; color: var(--pn-soft); }
    .pn-row b { display: block; font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; line-height: 1.15; color: var(--pn-ink); font-variant-numeric: tabular-nums; }
    .pn-row small { display: block; margin-top: .1rem; font-size: .72rem; line-height: 1.35; color: var(--pn-text); }
    .pn-u { font-family: var(--font-body, inherit); font-size: .74rem; font-weight: 700; color: var(--pn-soft); }
    .pn-use { flex: none; min-height: 2.25rem; padding: .35rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #1f2937; background: #f5c518;
        box-shadow: 0 6px 14px -10px rgb(138 106 0 / .9); transition: transform .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .pn-use:hover { transform: translateY(-1px); background: #ffd23d; }
    .pn-use.is-done { background: #dff0cf; color: #2d5016; box-shadow: none; }
    html.dark .pn-use.is-done { background: #2b3a1c; color: #cfe3bd; }
    .pn-foot { margin-top: .6rem; font-size: .74rem; line-height: 1.5; color: var(--pn-text); }
    .pn-tip { margin-top: .55rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .8rem; line-height: 1.5; color: var(--pn-ink); background: var(--pn-mark-bg); }
    .pn-tip a { font-weight: 800; color: var(--pn-go); text-decoration: underline; text-underline-offset: 2px; }
    .pn-skel { display: grid; gap: .45rem; }
    .pn-skel i { display: block; height: 3.1rem; border-radius: .85rem; background: linear-gradient(90deg, var(--pn-row) 0%, var(--pn-line) 50%, var(--pn-row) 100%); background-size: 200% 100%; animation: pnShine 1.2s linear infinite; }
    @keyframes pnShine { to { background-position: -200% 0; } }
    /* The box a Use filled glows once, so the eye finds it. */
    .pn-flash { animation: pnFlash .9s cubic-bezier(.22,1,.36,1); }
    @keyframes pnFlash { 0% { box-shadow: 0 0 0 0 rgb(245 197 24 / .75); } 100% { box-shadow: 0 0 0 10px rgb(245 197 24 / 0); } }
    .pn-opts { display: grid; gap: .4rem; }
    .pn-find { width: 100%; margin-bottom: .6rem; }
    .pn-opt { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .7rem .85rem; border-radius: .9rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .pn-opt:hover { border-color: #e2c45a; }
    .pn-opt.is-on { border-color: #c9a227; background: #fdf6dc; }
    html.dark .pn-opt.is-on { background: #2f2b12; border-color: #8a6a00; }
    .pn-opt b { flex: 1; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .pn-opt small { font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }
    .pn-none { padding: .8rem .2rem; font-size: .84rem; color: var(--color-gray-500); }
    @media (prefers-reduced-motion: reduce) { .pn, .pn-row, .pn-skel i, .pn-flash { animation: none !important; } .pn-place, .pn-use, .pn-opt { transition: none !important; } }
    html.sm-still .pn, html.sm-still .pn-row, html.sm-still .pn-skel i, html.sm-still .pn-flash { animation: none !important; }
</style>
@endpush

@push('sheets')
<div class="sheet hidden" id="pnSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Palay prices where?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="search" class="form-input pn-find" id="pnFind" placeholder="Find a province" autocomplete="off" aria-label="Find a province">
        <div class="pn-opts" id="pnOpts"></div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const LS = 'anee-palay-place';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const peso = (v) => '₱' + Number(v).toFixed(2);
    const day = (iso) => new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    const month = (ym) => new Date(ym + '-01T00:00:00').toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const saved = () => { try { return localStorage.getItem(LS); } catch (e) { return null; } };
    const keep = (p) => { try { localStorage.setItem(LS, p); } catch (e) {} };
    let open = null; // the card whose place sheet is open

    const mount = (box) => {
        const st = { data: null, asked: saved() };
        const rows = box.querySelector('[data-pn-rows]'), foot = box.querySelector('[data-pn-foot]'), tip = box.querySelector('[data-pn-tip]');
        const row = (form, label, r) => {
            if (!r) return '';
            const home = st.data.province || st.data.asked;
            const src = r.level === 'province' ? 'In ' + r.where + '.'
                : 'In ' + (r.level === 'region' ? r.where : 'the whole country') + (home ? ', as ' + home + ' has too few reports lately' : '') + '.';
            const range = r.lo !== r.hi ? ' Most were ' + peso(r.lo) + ' to ' + peso(r.hi) + '.' : '';
            return '<div class="pn-row"><span><em>' + label + '</em><b>' + peso(r.p) + '<span class="pn-u"> a kg</span></b><small>' + esc(src + range) + '</small></span>'
                + (box.dataset['pn' + form] ? '<button type="button" class="pn-use" data-pn-use="' + form + '" data-p="' + r.p + '" aria-label="Use ' + peso(r.p) + ' for ' + label + '">Use</button>' : '') + '</div>';
        };
        const paint = () => {
            const d = st.data;
            box.querySelector('[data-pn-place] span').textContent = d.province || (d.asked ? d.asked : 'The whole country');
            rows.innerHTML = row('Wet', 'Wet (basa)', d.wet) + row('Dry', 'Dry (tuyo)', d.dry);
            const n = [d.wet && d.wet.n + ' wet', d.dry && d.dry.n + ' dry'].filter(Boolean).join(' and ');
            const from = [d.wet && d.wet.from, d.dry && d.dry.from].filter(Boolean).sort()[0], to = [d.wet && d.wet.to, d.dry && d.dry.to].filter(Boolean).sort().pop();
            foot.textContent = 'The middle price of ' + n + ' reports, ' + day(from) + ' to ' + day(to) + '. Government and town buying programs are left out. Your own buyer may pay more or less.';
            foot.hidden = false;
            const t = d.tip;
            if (t && t.gain >= 0.03) {
                tip.innerHTML = 'Kept dry, palay ' + (t.where === 'the Philippines' ? 'in the country' : 'in ' + esc(t.where)) + ' is likely to pay about <b>' + Math.round(t.gain * 100) + '% more</b> by ' + esc(month(t.best))
                    + ', after what it loses in storage. <a href="' + esc(d.url) + '">See the forecast</a>';
                tip.hidden = false;
            } else if (t) {
                tip.innerHTML = 'Keeping dry palay to sell later does not look worth it in the coming months: the forecast sees no rise above what storage costs. <a href="' + esc(d.url) + '">See the forecast</a>';
                tip.hidden = false;
            } else tip.hidden = true;
        };
        const load = async () => {
            const q = st.asked !== null ? '?province=' + encodeURIComponent(st.asked) : '';
            try {
                const res = await window.api(box.dataset.pnUrl + q, { method: 'GET' });
                st.data = res.data; box.hidden = false; paint();
            } catch (e) { box.hidden = true; }
        };
        box.addEventListener('click', (e) => {
            const u = e.target.closest('[data-pn-use]');
            if (u) {
                const input = document.getElementById(box.dataset['pn' + u.dataset.pnUse]);
                if (!input) return;
                input.value = u.dataset.p;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                u.textContent = 'Used'; u.classList.add('is-done');
                setTimeout(() => { u.textContent = 'Use'; u.classList.remove('is-done'); }, 1600);
                if (!still()) { input.classList.remove('pn-flash'); void input.offsetWidth; input.classList.add('pn-flash'); }
                return;
            }
            if (e.target.closest('[data-pn-place]') && st.data) {
                open = { st, load };
                const list = [...new Set([...(st.data.places || []), ...(st.data.asked && !st.data.province ? [st.data.asked] : [])])].sort();
                const cur = st.data.province || st.data.asked || '';
                const opts = [{ k: '', t: 'The whole country' }, ...list.map((p) => ({ k: p, t: p }))];
                document.getElementById('pnOpts').innerHTML = opts.map((o) => '<button type="button" class="pn-opt' + (o.k === cur ? ' is-on' : '') + '" data-k="' + esc(o.k) + '"><b>' + esc(o.t) + '</b>' + (o.k === cur ? '<small>Showing</small>' : '') + '</button>').join('');
                const f = document.getElementById('pnFind'); f.value = '';
                window.openSheet('pnSheet');
            }
        });
        load();
    };

    const wire = () => {
        document.querySelectorAll('.pn[data-pn-url]').forEach(mount);
        const opts = document.getElementById('pnOpts');
        if (!opts) return;
        opts.addEventListener('click', (e) => {
            const b = e.target.closest('.pn-opt');
            if (!b || !open) return;
            opts.querySelectorAll('.pn-opt').forEach((x) => x.classList.toggle('is-on', x === b));
            open.st.asked = b.dataset.k; keep(b.dataset.k);
            const o = open;
            setTimeout(() => { window.closeSheet('pnSheet'); o.load(); }, 160);
        });
        document.getElementById('pnFind').addEventListener('input', (e) => {
            const q = e.target.value.trim().toLowerCase();
            let any = false;
            opts.querySelectorAll('.pn-opt').forEach((b) => { const on = !q || b.textContent.toLowerCase().includes(q); b.hidden = !on; any = any || on; });
            let none = opts.querySelector('.pn-none');
            if (!any && !none) { none = document.createElement('p'); none.className = 'pn-none'; none.textContent = 'No reports from a province by that name lately.'; opts.appendChild(none); }
            if (none) none.hidden = any;
        });
    };
    if (window.api) wire(); else document.addEventListener('DOMContentLoaded', wire, { once: true });
})();
</script>
@endpush
@endonce
