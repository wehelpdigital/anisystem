@extends('layouts.app')

@section('title', 'Post-Harvest Report — ' . $schedule->title)
@section('page-title', 'Post-Harvest Report')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
<style>
    /* ===== Post-Harvest Revenue Report ==============================
       All colours ride the theme vars, so dark mode is automatic.
       House easing on every reveal per the animation standard. */
    .rr-wrap { max-width: 52rem; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
    .rr-card {
        border-radius: 1rem; border: 1px solid var(--color-gray-100); background: var(--color-white);
        box-shadow: var(--shadow-card); padding: 1.05rem 1.15rem;
        animation: rrIn .28s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes rrIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .rr-card { animation: none; } }
    .rr-card h3 { font-family: var(--font-heading); font-weight: 700; font-size: 1.02rem; color: var(--color-gray-900); }
    .rr-card .rr-sub { font-size: .8rem; color: var(--color-gray-500); margin-top: .1rem; }

    /* Cost breakdown rows */
    .rr-line { display: flex; align-items: center; gap: .6rem; padding: .55rem 0; border-top: 1px solid var(--color-gray-100); font-size: .92rem; }
    .rr-line:first-of-type { border-top: 0; }
    .rr-line .rr-dot { width: .6rem; height: .6rem; border-radius: .2rem; flex-shrink: 0; }
    .rr-line .rr-key { color: var(--color-gray-600); font-weight: 600; }
    .rr-line .rr-val { margin-left: auto; font-weight: 700; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .rr-line.rr-total { border-top: 2px solid var(--color-gray-200); margin-top: .2rem; padding-top: .7rem; }
    .rr-line.rr-total .rr-key { color: var(--color-gray-900); font-weight: 800; }
    .rr-line.rr-total .rr-val { font-size: 1.1rem; }

    /* Hero net-profit band */
    .rr-hero { border-radius: 1.25rem; padding: 1.15rem 1.25rem; border: 1px solid var(--color-brand-100);
        background: linear-gradient(115deg, var(--color-brand-50) 0%, var(--color-white) 72%); }
    .rr-hero-label { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); }
    .rr-hero-value { font-family: var(--font-sans); font-weight: 800; font-size: 2.4rem; line-height: 1.1; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .rr-hero-value.is-loss { color: #dc2626; }
    .rr-hero-value.is-profit { color: #15803d; }
    html.dark .rr-hero-value.is-loss { color: #f47c7c; }
    html.dark .rr-hero-value.is-profit { color: #5fce97; }
    .rr-hero-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
    .rr-chip { border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-100); padding: .45rem .7rem; min-width: 8rem; }
    .rr-chip .k { font-size: .68rem; font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: .04em; }
    .rr-chip .v { font-weight: 700; font-size: 1.05rem; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }

    .rr-field { display: flex; flex-direction: column; gap: .3rem; }
    .rr-field label { font-size: .78rem; font-weight: 700; color: var(--color-gray-600); }
    .rr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
    @media (max-width: 30rem) { .rr-grid { grid-template-columns: 1fr; } }

    /* Saved copies */
    .rr-saved-card { border: 1px solid var(--color-gray-100); border-radius: .85rem; padding: .75rem .85rem; background: var(--color-white);
        animation: rrIn .28s cubic-bezier(.22,1,.36,1) both; }
    .rr-saved-card + .rr-saved-card { margin-top: .55rem; }
    .rr-saved-head { display: flex; align-items: center; gap: .5rem; }
    .rr-saved-title { font-weight: 700; color: var(--color-gray-900); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rr-saved-when { font-size: .72rem; color: var(--color-gray-400); margin-left: auto; white-space: nowrap; }
    .rr-saved-nums { display: flex; flex-wrap: wrap; gap: .45rem .9rem; margin-top: .5rem; font-size: .8rem; }
    .rr-saved-nums span b { font-variant-numeric: tabular-nums; }
    .rr-net-pos { color: #15803d; } html.dark .rr-net-pos { color: #5fce97; }
    .rr-net-neg { color: #dc2626; } html.dark .rr-net-neg { color: #f47c7c; }
    .rr-del { color: var(--color-gray-400); width: 1.9rem; height: 1.9rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .5rem; transition: background .15s ease, color .15s ease; }
    .rr-del:hover { background: #fee2e2; color: #dc2626; }
    html.dark .rr-del:hover { background: #3d1c1f; color: #f47c7c; }
</style>
@endpush

@section('content')
@php
    $C = ['materials' => '#2563eb', 'services' => '#7c3aed', 'labor' => '#d97706', 'expenses' => '#c2410c'];
@endphp
<div class="rr-wrap">

    {{-- ===== Net profit hero ===== --}}
    <div class="rr-hero rr-card" style="animation-delay:.02s">
        <div class="rr-hero-label">Estimated net profit</div>
        <div class="rr-hero-value" id="rrNet">₱0.00</div>
        <div class="rr-hero-meta">
            <div class="rr-chip"><div class="k">Gross revenue</div><div class="v" id="rrGross">₱0.00</div></div>
            <div class="rr-chip"><div class="k">Total cost</div><div class="v" id="rrTotalCostChip">₱{{ number_format($costs['total'], 2) }}</div></div>
            <div class="rr-chip"><div class="k">Margin</div><div class="v" id="rrMargin">—</div></div>
            <div class="rr-chip"><div class="k">Return on cost</div><div class="v" id="rrRoi">—</div></div>
        </div>
    </div>

    {{-- ===== Harvest inputs ===== --}}
    <div class="rr-card" style="animation-delay:.06s">
        <h3>Harvest &amp; revenue</h3>
        <p class="rr-sub">Enter what you harvested and the price you got — revenue updates as you type.</p>
        <div class="rr-grid" style="margin-top:.8rem">
            <div class="rr-field">
                <label for="rrYield">Yield harvested</label>
                <input type="number" id="rrYield" class="form-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="rr-field">
                <label for="rrUnit">Unit</label>
                <input type="text" id="rrUnit" class="form-input" maxlength="24" placeholder="e.g. cavan, kg, sack" value="cavan">
            </div>
            <div class="rr-field">
                <label for="rrPrice">Price per unit (₱)</label>
                <input type="number" id="rrPrice" class="form-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="rr-field">
                <label>Gross revenue</label>
                <div class="form-input" style="background:var(--color-gray-50); display:flex; align-items:center; font-weight:700;" id="rrGrossMirror">₱0.00</div>
            </div>
        </div>
    </div>

    {{-- ===== Cost breakdown ===== --}}
    <div class="rr-card" style="animation-delay:.10s">
        <div class="flex items-center gap-2">
            <h3 class="grow">Costs this season</h3>
            <button type="button" id="rrRefreshCosts" class="btn btn-white btn-sm" title="Recompute from the latest schedule data">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>
        <p class="rr-sub">Pulled automatically from your activities, workers and per-day expenses.</p>
        <div style="margin-top:.7rem" id="rrCostList">
            <div class="rr-line"><span class="rr-dot" style="background:{{ $C['materials'] }}"></span><span class="rr-key">Materials</span><span class="rr-val" data-cost="materials">₱{{ number_format($costs['materials'], 2) }}</span></div>
            <div class="rr-line"><span class="rr-dot" style="background:{{ $C['services'] }}"></span><span class="rr-key">Services</span><span class="rr-val" data-cost="services">₱{{ number_format($costs['services'], 2) }}</span></div>
            <div class="rr-line"><span class="rr-dot" style="background:{{ $C['labor'] }}"></span><span class="rr-key">Labour</span><span class="rr-val" data-cost="labor">₱{{ number_format($costs['labor'], 2) }}</span></div>
            <div class="rr-line"><span class="rr-dot" style="background:{{ $C['expenses'] }}"></span><span class="rr-key">Extra expenses</span><span class="rr-val" data-cost="expenses">₱{{ number_format($costs['expenses'], 2) }}</span></div>
            <div class="rr-line rr-total"><span class="rr-key">Total cost</span><span class="rr-val" data-cost="total">₱{{ number_format($costs['total'], 2) }}</span></div>
        </div>
    </div>

    {{-- ===== Save this calculation ===== --}}
    <div class="rr-card" style="animation-delay:.14s">
        <h3>Save this calculation</h3>
        <p class="rr-sub">Freeze the numbers above as a copy you can look back on — later edits to the schedule won't change it.</p>
        <div class="rr-field" style="margin-top:.8rem">
            <label for="rrTitle">Name this report</label>
            <input type="text" id="rrTitle" class="form-input" maxlength="191" placeholder="e.g. Wet season 2026 — Lot A">
        </div>
        <div class="rr-field" style="margin-top:.7rem">
            <label for="rrNotes">Notes (optional)</label>
            <textarea id="rrNotes" class="form-textarea" rows="2" maxlength="5000" placeholder="Anything worth remembering about this harvest…"></textarea>
        </div>
        <div class="flex justify-end mt-3">
            <button type="button" id="rrSaveBtn" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Save report
            </button>
        </div>
    </div>

    {{-- ===== Saved copies ===== --}}
    <div class="rr-card" style="animation-delay:.18s">
        <h3>Saved reports</h3>
        <p class="rr-sub" id="rrSavedEmpty" @if($saved->count()) style="display:none" @endif>No saved reports yet.</p>
        <div id="rrSavedList" style="margin-top:.7rem">
            @foreach ($saved as $r)
                @include('sm.partials.revenue-saved-card', ['r' => $r])
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function revenueReport() {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        store:  @json(route('sm.revenue-report.store', ['id' => $schedule->id])),
        del:    @json(route('sm.revenue-report.destroy', ['scheduleId' => $schedule->id])),
        compute:@json(route('sm.revenue-report.compute', ['id' => $schedule->id])),
    };
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const $ = (id) => document.getElementById(id);

    let COSTS = @json($costs);

    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct = (n) => (Number(n) >= 0 ? '' : '') + Number(n).toFixed(1) + '%';

    function recalc() {
        const y = parseFloat($('rrYield').value) || 0;
        const p = parseFloat($('rrPrice').value) || 0;
        const gross = y * p;
        const total = Number(COSTS.total || 0);
        const net = gross - total;

        $('rrGross').textContent = peso(gross);
        $('rrGrossMirror').textContent = peso(gross);
        $('rrTotalCostChip').textContent = peso(total);

        const netEl = $('rrNet');
        netEl.textContent = peso(net);
        netEl.classList.toggle('is-profit', net > 0);
        netEl.classList.toggle('is-loss', net < 0);

        $('rrMargin').textContent = gross > 0 ? pct((net / gross) * 100) : '—';
        $('rrRoi').textContent = total > 0 ? pct((net / total) * 100) : '—';
    }

    ['rrYield', 'rrPrice'].forEach((id) => $(id).addEventListener('input', recalc));
    recalc();

    // --- Refresh costs from the server (picks up newly-logged expenses etc.)
    $('rrRefreshCosts').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await window.api(URLS.compute);
            COSTS = res.data || COSTS;
            document.querySelectorAll('#rrCostList [data-cost]').forEach((el) => {
                const k = el.getAttribute('data-cost');
                if (COSTS[k] != null) el.textContent = peso(COSTS[k]);
            });
            recalc();
            window.toast('Costs refreshed.');
        } catch (err) {
            window.toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // --- Save a copy
    function savedCardHtml(r) {
        const netClass = Number(r.netProfit) >= 0 ? 'rr-net-pos' : 'rr-net-neg';
        const yieldLine = (r.yieldAmount != null && r.yieldAmount !== '')
            ? `<span>Yield: <b>${esc(Number(r.yieldAmount).toLocaleString('en-PH'))} ${esc(r.yieldUnit || '')}</b></span>` : '';
        return `<div class="rr-saved-card" data-id="${r.id}">
            <div class="rr-saved-head">
                <span class="rr-saved-title">${esc(r.title)}</span>
                <span class="rr-saved-when">${esc(r.savedAt || '')}</span>
                <button type="button" class="rr-del" data-del="${r.id}" title="Delete report">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                </button>
            </div>
            <div class="rr-saved-nums">
                ${yieldLine}
                <span>Revenue: <b>${peso(r.grossRevenue)}</b></span>
                <span>Cost: <b>${peso(r.totalCost)}</b></span>
                <span class="${netClass}">Net: <b>${peso(r.netProfit)}</b></span>
            </div>
            ${r.notes ? `<p style="font-size:.8rem;color:var(--color-gray-500);margin-top:.4rem">${esc(r.notes)}</p>` : ''}
        </div>`;
    }

    $('rrSaveBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const title = $('rrTitle').value.trim();
        if (!title) { window.toast('Give the report a name first.', 'error'); $('rrTitle').focus(); return; }
        btn.disabled = true;
        try {
            const res = await window.api(URLS.store, {
                method: 'POST',
                body: {
                    title,
                    yieldAmount: $('rrYield').value || null,
                    yieldUnit: $('rrUnit').value.trim() || null,
                    pricePerUnit: $('rrPrice').value || null,
                    notes: $('rrNotes').value.trim() || null,
                },
            });
            const list = $('rrSavedList');
            list.insertAdjacentHTML('afterbegin', savedCardHtml(res.data));
            $('rrSavedEmpty').style.display = 'none';
            $('rrTitle').value = '';
            $('rrNotes').value = '';
            window.toast('Report saved.');
        } catch (err) {
            window.toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // --- Delete a saved copy (delegated)
    $('rrSavedList').addEventListener('click', async (e) => {
        const del = e.target.closest('[data-del]');
        if (!del) return;
        const id = del.getAttribute('data-del');
        const ok = await window.confirmAction({
            title: 'Delete report?',
            message: 'This saved calculation will be removed.',
            confirmText: 'Delete',
            danger: true,
        });
        if (!ok) return;
        try {
            await window.api(URLS.del + '&id=' + id, { method: 'DELETE' });
            const card = $('rrSavedList').querySelector(`.rr-saved-card[data-id="${id}"]`);
            if (card) card.remove();
            if (!$('rrSavedList').querySelector('.rr-saved-card')) $('rrSavedEmpty').style.display = '';
            window.toast('Report deleted.');
        } catch (err) {
            window.toast(err.message, 'error');
        }
    });
})();
</script>
@endpush
