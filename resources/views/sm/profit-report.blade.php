@extends('layouts.app')

@section('title', 'Profit Report — ' . $schedule->title)
@section('page-title', 'Profit Report')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Profit Report ============================================
       The harvest against the spend. One hero verdict, a cost anatomy
       bar, then a card per lot — margins, cost per unit, warnings. */
    .pr-wrap { max-width: 64rem; margin: 0 auto; }
    .pr-hero { border-radius: 1.25rem; padding: 1.2rem 1.3rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .pr-hero.is-loss { background: linear-gradient(130deg, #b45309, #7c2d12 70%); }
    .pr-hero-label { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; opacity: .85; }
    .pr-hero-value { font-weight: 800; font-size: 2.4rem; line-height: 1.1; }
    .pr-hero-sub { font-size: .85rem; opacity: .92; margin-top: .3rem; }
    .pr-vs { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: .9rem; }
    .pr-vs > div { border-radius: .9rem; background: rgb(255 255 255 / .14); padding: .6rem .8rem; }
    .pr-vs .k { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; opacity: .85; }
    .pr-vs .v { font-weight: 800; font-size: 1.15rem; font-variant-numeric: tabular-nums; }

    .pr-card { border-radius: 1rem; border: 1px solid var(--color-gray-100); background: var(--color-white);
        box-shadow: var(--shadow-card); padding: 1rem 1.1rem; margin-top: .9rem; }
    .pr-card h3 { font-weight: 700; font-size: 1.02rem; color: var(--color-gray-900); }
    .pr-card .sub { font-size: .8rem; color: var(--color-gray-500); }

    /* Warnings & blockers */
    .pr-warn { border-radius: .8rem; border: 1px solid #f3e3b7; background: #fdf8ec; padding: .65rem .8rem;
        font-size: .8rem; color: #92610e; line-height: 1.5; }
    .pr-warn + .pr-warn { margin-top: .45rem; }
    .pr-block { border-radius: .8rem; border: 1px solid #f0caca; background: #fdf1f1; padding: .8rem .9rem;
        font-size: .85rem; color: #8a2626; line-height: 1.55; }
    html.dark .pr-warn { background: #241f10; border-color: #43391b; color: #e0b95c; }
    html.dark .pr-block { background: #271414; border-color: #4c2222; color: #e79c9c; }

    /* Cost anatomy: one stacked bar + legend rows. */
    .pr-stack { display: flex; height: 16px; border-radius: 999px; overflow: hidden; margin-top: .8rem; }
    .pr-stack span { min-width: 3px; }
    .pr-anatomy { display: grid; gap: .35rem; margin-top: .7rem; }
    .pr-anat { display: flex; align-items: center; gap: .5rem; font-size: .8rem; color: var(--color-gray-700); }
    .pr-anat i { width: .7rem; height: .7rem; border-radius: .2rem; flex: none; }
    .pr-anat b { margin-left: auto; font-variant-numeric: tabular-nums; color: var(--color-gray-900); }

    /* Lot cards */
    .pr-lots { display: grid; gap: .6rem; margin-top: .8rem; }
    .pr-lot { border: 1px solid var(--color-gray-100); border-radius: .9rem; padding: .8rem .9rem; background: var(--color-white); }
    .pr-lot-top { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; }
    .pr-lot-top b { font-size: .95rem; color: var(--color-gray-900); }
    .pr-lot-profit { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .pr-lot-profit.is-up { color: #15803d; }
    .pr-lot-profit.is-down { color: #b91c1c; }
    .pr-lot-meta { display: flex; flex-wrap: wrap; gap: .3rem .5rem; margin-top: .35rem; }
    .pr-lot-bars { margin-top: .55rem; display: grid; gap: 4px; }
    .pr-lot-bar { display: grid; grid-template-columns: 4.4rem 1fr auto; gap: .5rem; align-items: center; font-size: .72rem; color: var(--color-gray-500); }
    .pr-lot-bar span.bar { height: 8px; border-radius: 999px; min-width: 2px; transition: width .5s cubic-bezier(.22,1,.36,1); }
    .pr-lot-bar b { font-variant-numeric: tabular-nums; color: var(--color-gray-800); font-size: .74rem; }
    html.dark .pr-card, html.dark .pr-lot { background: #151b12; border-color: #2b3a1c; }
    html.dark .pr-card h3, html.dark .pr-lot-top b, html.dark .pr-anat b { color: #e8efe1; }

    @media print {
        header, nav, .pr-actions, .bottom-nav, .tabbar, #aiFloat { display: none !important; }
        .pr-card { box-shadow: none; page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')
<div class="pr-wrap">
    <div class="card p-4 mb-4 pr-actions">
        <p class="text-xs text-gray-500 mb-2">The whole plan's costs against the recorded harvest. Numbers refresh every time this page opens.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <button type="button" id="prCopyBtn" class="btn btn-white w-full">Copy as Text</button>
            <button type="button" id="prPrintBtn" class="btn btn-white w-full">Print</button>
            <button type="button" id="prAttachBtn" class="btn btn-white w-full">
                <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" class="w-4 h-4 rounded-full object-cover mr-1" style="width:1rem;height:1rem;">
                Attach to {{ \App\Models\AiSetting::current()->assistantName }}
            </button>
        </div>
    </div>

    <div class="text-center text-gray-400 py-16 text-sm" id="prLoading">Weighing the season…</div>

    <div id="prBlocked" class="hidden">
        <div class="pr-block" id="prBlockedText"></div>
        <a href="{{ route('sm.activities', ['id' => $schedule->id, 'module' => 'post-harvest']) }}" class="btn btn-primary w-full mt-3">Open Post-harvest</a>
    </div>

    <div id="prContent" class="hidden">
        <div class="pr-hero" id="prHero">
            <div class="pr-hero-label">Net profit</div>
            <div class="pr-hero-value" id="prProfit">₱0</div>
            <div class="pr-hero-sub" id="prVerdict"></div>
            <div class="pr-vs">
                <div><div class="k">Money in</div><div class="v" id="prRevenue">₱0</div></div>
                <div><div class="k">Money out</div><div class="v" id="prCost">₱0</div></div>
            </div>
        </div>

        <div class="pr-card" id="prWarnCard" hidden>
            <h3>Worth knowing</h3>
            <p class="sub">The numbers below carry these footnotes.</p>
            <div class="mt-3" id="prWarnings"></div>
        </div>

        <div class="pr-card">
            <h3>Where the money went</h3>
            <p class="sub" id="prAnatomySub"></p>
            <div class="pr-stack" id="prStack"></div>
            <div class="pr-anatomy" id="prAnatomy"></div>
        </div>

        <div class="pr-card">
            <h3>Lot by lot</h3>
            <p class="sub">Activity costs are shared across the lots they touch; season-wide costs and the day book sit under General.</p>
            <div class="pr-lots" id="prLots"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s));
    const DATA_URL = @json(route('sm.profit.report.data') . '?id=' . $schedule->id);
    const fmtPeso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtPeso0 = (n) => '₱' + Math.round(Number(n || 0)).toLocaleString('en-PH');
    const CATS = [
        ['materials', 'Materials', '#15803d'],
        ['labor', 'Labor', '#d97706'],
        ['services', 'Services', '#2563eb'],
        ['expense', 'Day expenses', '#b91c1c'],
        ['purchase', 'Stock buys', '#7c3aed'],
    ];
    let DATA = null;

    async function load() {
        try {
            const res = await api(DATA_URL);
            DATA = res.data;
            $id('prLoading').classList.add('hidden');
            if (DATA.blocked) {
                $id('prBlockedText').innerHTML = '<b>This report is not ready yet.</b><br>' + DATA.blockers.map(esc).join('<br>');
                $id('prBlocked').classList.remove('hidden');
                return;
            }
            render();
            $id('prContent').classList.remove('hidden');
        } catch (err) { toast(err.message, 'error'); }
    }

    function render() {
        const d = DATA;
        const up = d.profit >= 0;
        $id('prHero').classList.toggle('is-loss', !up);
        $id('prProfit').textContent = (up ? '' : '−') + fmtPeso(Math.abs(d.profit));
        $id('prVerdict').textContent = up
            ? `The season is ahead${d.margin !== null ? ` — a ${d.margin}% margin on what it earned` : ''}.`
            : `The season spent more than it earned so far${d.margin !== null ? ` (${d.margin}% margin)` : ''}.`;
        $id('prRevenue').textContent = fmtPeso(d.revenue);
        $id('prCost').textContent = fmtPeso(d.cost);

        // Warnings
        const w = $id('prWarnings');
        if ((d.warnings || []).length) {
            w.innerHTML = d.warnings.map((t) => `<div class="pr-warn">⚠️ ${esc(t)}</div>`).join('');
            $id('prWarnCard').hidden = false;
        } else {
            $id('prWarnCard').hidden = true;
        }

        // Cost anatomy
        const total = Math.max(0.01, d.cost);
        $id('prAnatomySub').textContent = `Every peso of the ${fmtPeso0(d.cost)} spent, by kind.`;
        $id('prStack').innerHTML = CATS.filter(([k]) => d.costCats[k] > 0)
            .map(([k, , c]) => `<span style="background:${c};flex:${d.costCats[k]} ${d.costCats[k]} 0"></span>`).join('');
        $id('prAnatomy').innerHTML = CATS.filter(([k]) => d.costCats[k] > 0)
            .map(([k, label, c]) => `<div class="pr-anat"><i style="background:${c}"></i>${label}
                <span class="text-gray-400">· ${Math.round((d.costCats[k] / total) * 100)}%</span><b>${fmtPeso(d.costCats[k])}</b></div>`).join('');

        // Lots
        const maxAmt = Math.max(1, ...d.lots.map((l) => Math.max(l.revenue, l.cost)));
        $id('prLots').innerHTML = d.lots.map((l) => {
            const lup = l.profit >= 0;
            return `<div class="pr-lot">
                <div class="pr-lot-top">
                    <b>${esc(l.name)}</b>
                    <span class="pr-lot-profit ${lup ? 'is-up' : 'is-down'}">${lup ? '+' : '−'}${fmtPeso(Math.abs(l.profit))}</span>
                </div>
                <div class="pr-lot-meta">
                    ${l.crop ? `<span class="badge badge-gray">${esc(l.crop)}</span>` : ''}
                    ${l.size ? `<span class="badge badge-gray">${esc(l.size)}</span>` : ''}
                    ${(l.yield || []).map((y) => `<span class="badge badge-green">🌾 ${esc(y)}</span>`).join('')}
                    ${l.margin !== null ? `<span class="badge ${lup ? 'badge-green' : 'badge-red'}">${l.margin}% margin</span>` : ''}
                    ${l.costPerUnit !== null ? `<span class="badge badge-yellow">${fmtPeso(l.costPerUnit)} / ${esc(l.unit || 'unit')}</span>` : ''}
                </div>
                <div class="pr-lot-bars">
                    <div class="pr-lot-bar"><span>Earned</span><span class="bar" style="background:#f0b429;width:${Math.max(2, (l.revenue / maxAmt) * 100)}%"></span><b>${fmtPeso0(l.revenue)}</b></div>
                    <div class="pr-lot-bar"><span>Spent</span><span class="bar" style="background:var(--color-brand-600);width:${Math.max(2, (l.cost / maxAmt) * 100)}%"></span><b>${fmtPeso0(l.cost)}</b></div>
                </div>
            </div>`;
        }).join('');
    }

    function buildText() {
        const d = DATA;
        const lines = [];
        lines.push(`PROFIT REPORT — ${d.scheduleTitle || ''}`);
        lines.push('='.repeat(50));
        lines.push(`Generated: ${new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })}`);
        lines.push('');
        lines.push(`NET PROFIT: ${fmtPeso(d.profit)}${d.margin !== null ? ` (${d.margin}% margin)` : ''}`);
        lines.push(`Money in: ${fmtPeso(d.revenue)} (day-book income ${fmtPeso(d.dayIncome)})`);
        lines.push(`Money out: ${fmtPeso(d.cost)}`);
        CATS.forEach(([k, label]) => { if (d.costCats[k] > 0) lines.push(`  ${label}: ${fmtPeso(d.costCats[k])}`); });
        if ((d.warnings || []).length) {
            lines.push('');
            lines.push('WORTH KNOWING');
            d.warnings.forEach((t) => lines.push('  ! ' + t));
        }
        lines.push('');
        lines.push('LOT BY LOT');
        lines.push('-'.repeat(50));
        d.lots.forEach((l) => {
            lines.push(`${l.name}${l.crop ? ' (' + l.crop + ')' : ''}: earned ${fmtPeso(l.revenue)} · spent ${fmtPeso(l.cost)} · profit ${fmtPeso(l.profit)}`
                + `${l.margin !== null ? ` · margin ${l.margin}%` : ''}${l.costPerUnit !== null ? ` · cost ${fmtPeso(l.costPerUnit)}/${l.unit}` : ''}`
                + `${(l.yield || []).length ? ` · yield ${l.yield.join(', ')}` : ''}`);
        });
        return lines.join('\n');
    }
    $id('prCopyBtn').addEventListener('click', () => {
        if (!DATA || DATA.blocked) { toast('The report has nothing to copy yet.', 'info'); return; }
        (navigator.clipboard?.writeText(buildText()) || Promise.reject(new Error('no')))
            .then(() => toast('Profit report copied to clipboard.'))
            .catch(() => toast('Copy failed on this browser.', 'error'));
    });
    $id('prPrintBtn').addEventListener('click', () => window.print());
    $id('prAttachBtn').addEventListener('click', async (e) => {
        if (!DATA || DATA.blocked) { toast('The report is not ready to attach yet.', 'info'); return; }
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.report.snapshot')), { method: 'POST', body: {
                scheduleId: @json($schedule->id),
                kind: 'profit',
                title: @json('Profit Report — ' . $schedule->title),
                body: buildText(),
            } });
            window.location.href = @json(route('ai.index')) + '?freport=' + res.data.id;
        } catch (err) { toast(err.message, 'error'); btn.disabled = false; }
    });

    load();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
