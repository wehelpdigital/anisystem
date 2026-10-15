{{--
    A month and its year, picked from a calendar (2026-10-16, the owner: a
    tag that opens a year and its twelve months, nothing else on it). Used
    by Variety Ranking, What to Plant and Crop Protocol.

    window.aneeMonthPick.open({ months, value, title }) -> Promise<"YYYY-MM" or null>
        months: the months on offer, as [{ key: "2026-10" }] or ["2026-10"];
        the others show, greyed. The sheet opens on the year of the value.
    window.aneeMonthPick.label("2026-10") -> "October 2026"
    Picking a month closes the sheet and resolves it; nothing moves on by itself.
--}}
@once
<style>
    .mp-y { display: flex; align-items: center; gap: .6rem; margin-bottom: .9rem; }
    .mp-y b { flex: 1; text-align: center; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); }
    .mp-y button { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700);
        background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .mp-y button:hover:not(:disabled) { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .mp-y button:disabled { opacity: .3; cursor: default; }
    .mp-y svg { width: 1.1rem; height: 1.1rem; }
    .mp-g { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .mp-g.is-l { animation: mpL .3s cubic-bezier(.22,1,.36,1) both; }
    .mp-g.is-r { animation: mpR .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes mpL { from { opacity: 0; transform: translateX(-18px); } }
    @keyframes mpR { from { opacity: 0; transform: translateX(18px); } }
    .mp-m { position: relative; min-height: 3.5rem; border-radius: 1rem; font-size: .95rem; font-weight: 800; color: var(--color-gray-800);
        background: var(--color-white); border: 1.5px solid var(--color-gray-200); cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .mp-m:hover:not(:disabled) { border-color: var(--color-brand-300); background: var(--color-brand-50); transform: translateY(-1px); }
    .mp-m.is-now::after { content: ''; position: absolute; left: 50%; bottom: .45rem; width: .36rem; height: .36rem; margin-left: -.18rem; border-radius: 999px; background: var(--color-brand-500); }
    .mp-m.is-on { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .mp-m.is-on.is-now::after { background: #fff; }
    .mp-m:disabled { opacity: .35; cursor: not-allowed; transform: none; }
    .mp-key { display: flex; align-items: center; justify-content: center; gap: .4rem; margin-top: .9rem; font-size: .76rem; color: var(--color-gray-500); }
    .mp-key i { width: .36rem; height: .36rem; border-radius: 999px; background: var(--color-brand-500); }
    @media (prefers-reduced-motion: reduce) { .mp-g.is-l, .mp-g.is-r { animation: none; } .mp-m, .mp-y button { transition: none; } }
    html.sm-still .mp-g.is-l, html.sm-still .mp-g.is-r { animation: none; }
</style>
<div class="sheet hidden" id="mpSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="mpTitle">Which month?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="mp-y">
            <button type="button" id="mpPrev" aria-label="The year before"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg></button>
            <b id="mpYear" aria-live="polite"></b>
            <button type="button" id="mpNext" aria-label="The next year"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
        </div>
        <div class="mp-g" id="mpGrid"></div>
        <p class="mp-key"><i aria-hidden="true"></i>This month</p>
    </div>
</div>
<script>
(() => {
    if (window.aneeMonthPick) return;
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const $ = (id) => document.getElementById(id);
    const label = (key) => { const [y, m] = String(key || '').split('-').map(Number); return y && m ? LONG[m - 1] + ' ' + y : ''; };
    const now = (() => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); })();
    let S = null;   // { keys, years, year, value, resolve }
    function paint(dir) {
        if (!S) return;
        $('mpYear').textContent = S.year;
        $('mpPrev').disabled = S.year <= S.years[0];
        $('mpNext').disabled = S.year >= S.years[S.years.length - 1];
        const g = $('mpGrid');
        g.innerHTML = MON.map((m, i) => {
            const k = S.year + '-' + String(i + 1).padStart(2, '0'), ok = S.keys.includes(k), on = S.value === k;
            return '<button type="button" class="mp-m' + (on ? ' is-on' : '') + (k === now ? ' is-now' : '') + '" data-mk="' + k + '" aria-pressed="' + on + '" aria-label="' + label(k) + '"' + (ok ? '' : ' disabled') + '>' + m + '</button>';
        }).join('');
        g.classList.remove('is-l', 'is-r');
        if (dir) { void g.offsetWidth; g.classList.add(dir < 0 ? 'is-l' : 'is-r'); }
    }
    function open(o) {
        o = o || {};
        return new Promise((resolve) => {
            if (S && S.resolve) S.resolve(null);
            const keys = (o.months || []).map((m) => (typeof m === 'string' ? m : m.key)).filter(Boolean);
            const years = [...new Set(keys.map((k) => Number(k.slice(0, 4))))].sort((a, b) => a - b);
            if (!years.length) { resolve(null); return; }
            const want = o.value ? Number(String(o.value).slice(0, 4)) : years[0];
            S = { keys, years, year: years.includes(want) ? want : years[0], value: o.value || null, resolve };
            $('mpTitle').textContent = o.title || 'Which month?';
            paint(0);
            window.openSheet('mpSheet');
        });
    }
    $('mpPrev').addEventListener('click', () => { if (S && S.year > S.years[0]) { S.year--; paint(-1); } });
    $('mpNext').addEventListener('click', () => { if (S && S.year < S.years[S.years.length - 1]) { S.year++; paint(1); } });
    $('mpGrid').addEventListener('click', (e) => {
        const b = e.target.closest('[data-mk]');
        if (!b || b.disabled || !S) return;
        S.value = b.dataset.mk;
        $('mpGrid').querySelectorAll('[data-mk]').forEach((x) => { const on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', String(on)); });
        const r = S.resolve; S.resolve = null;
        window.closeSheet('mpSheet');
        if (r) r(S.value);
    });
    // Closed without a pick: nothing changes.
    document.addEventListener('sm:sheet-closed', (e) => { if (e.detail && e.detail.id === 'mpSheet' && S && S.resolve) { const r = S.resolve; S.resolve = null; r(null); } });
    window.aneeMonthPick = { open, label };
})();
</script>
@endonce
