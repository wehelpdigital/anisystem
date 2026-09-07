@extends('layouts.admin')

@section('title', 'Sales Analysis')
@section('subtitle', 'What a peso of advertising actually bought')

@section('content')
    {{-- Which analysis room — one for now, worn as the house tag so the
         next room slides in without redrawing the page. --}}
    <button type="button" class="ad-navtag" id="saTabBtn" aria-haspopup="dialog" title="Which analysis?">
        <span>📈</span>
        <span id="saTabNow">Acquisition Analysis</span>
        <svg class="ad-navtag-c" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" style="width:1rem;height:1rem"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
    </button>

    {{-- ---------------- Acquisition Analysis ---------------- --}}
    <div class="mt-3">
        <div class="card p-4 mb-4">
            <p class="font-bold text-gray-900">New analysis</p>
            <p class="text-xs text-gray-500 mt-0.5 mb-3">Name the campaign, mark the window it ran, and say what the ads cost. Everything else is read from the platform's own records.</p>

            <div class="sa-form">
                <input type="text" id="saName" class="form-input" maxlength="191" placeholder="e.g. Facebook Ads — September push">

                <div class="sa-row">
                    {{-- The two dates wear tags; the native pickers stand behind them. --}}
                    <input type="date" id="saFrom" class="sa-date-native" tabindex="-1" aria-hidden="true">
                    <button type="button" class="crop-tag" id="saFromBtn">
                        <span class="crop-tag-e">📅</span>
                        <span class="crop-tag-t is-none" id="saFromNow">From date</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <input type="date" id="saTo" class="sa-date-native" tabindex="-1" aria-hidden="true">
                    <button type="button" class="crop-tag" id="saToBtn">
                        <span class="crop-tag-e">📅</span>
                        <span class="crop-tag-t is-none" id="saToNow">To date</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <div class="sa-row">
                    <div class="relative grow min-w-0">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">₱</span>
                        <input type="number" id="saCost" min="0" step="0.01" class="form-input pl-8! w-full" placeholder="Ad cost" inputmode="decimal">
                    </div>
                    <input type="hidden" id="saCadence" value="daily">
                    <button type="button" class="crop-tag" id="saCadenceBtn">
                        <span class="crop-tag-e">⏱️</span>
                        <span class="crop-tag-t" id="saCadenceNow">Daily cost</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <button type="button" class="btn btn-primary w-full" id="saCreate">Create the analysis</button>
            </div>
        </div>

        <div id="saList" class="grid gap-3"></div>
        <p id="saEmpty" class="text-sm text-gray-400 text-center py-8" hidden>No analyses yet. Name your first campaign above.</p>

        {{-- The computed read of one batch. --}}
        <div id="saReport" class="mt-4" hidden></div>
    </div>
@endsection

@push('sheets')
<div class="sheet hidden" id="saTabSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which analysis?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows">
        <button type="button" class="dt-row is-on" data-sheet-close>
            <span class="dt-row-e">📈</span>
            <span class="dt-row-body"><b>Acquisition Analysis</b><i>Ad spend against registrations, conversions and revenue.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <p class="text-xs text-gray-400 px-1 pt-2">More analyses will take rows here as they are built.</p>
    </div>
</div>

<div class="sheet hidden" id="saCadenceSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">How was the cost counted?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="saCadenceList">
        <button type="button" class="dt-row is-on" data-sa-cadence="daily">
            <span class="dt-row-e">☀️</span>
            <span class="dt-row-body"><b>Daily cost</b><i>The amount is what one day of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-sa-cadence="weekly">
            <span class="dt-row-e">🗓️</span>
            <span class="dt-row-body"><b>Weekly cost</b><i>The amount is what one week of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-sa-cadence="monthly">
            <span class="dt-row-e">📆</span>
            <span class="dt-row-body"><b>Monthly cost</b><i>The amount is what one month of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </div>
</div>
@endpush

@push('head')
@include('partials.tag-sheet-css')
<style>
    .sa-form { display: grid; gap: .6rem; }
    .sa-row { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
    .sa-row .crop-tag { flex: 1 1 10rem; min-width: 0; }
    /* The native pickers stand invisibly behind their tags — showPicker()
       needs them rendered, not display:none. */
    .sa-date-native { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }

    .sa-card { text-align: left; width: 100%; display: flex; align-items: center; gap: .7rem;
        padding: .85rem .9rem; border-radius: .9rem; border: 1px solid var(--color-gray-200);
        background: var(--color-white); cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .sa-card:hover { border-color: #a8cc7e; box-shadow: 0 10px 24px -18px rgb(0 0 0 / .4); }
    .sa-card.is-open { border-color: var(--color-brand-500); }
    .sa-card b { display: block; font-size: .9rem; color: var(--color-gray-900); }
    .sa-card i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; }
    .sa-del { flex: none; width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-300); }
    .sa-del:hover { color: #dc2626; background: #fef2f2; }
    .sa-del svg { width: 1rem; height: 1rem; }
    html.dark .sa-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .sa-card b { color: #e8efe1; }

    /* The read: hero, funnel, unit economics, plan mix. */
    .sa-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .sa-hero.is-loss { background: linear-gradient(130deg, #b45309, #92400e 70%); }
    .sa-hero h2 { font-size: 1.1rem; font-weight: 800; }
    .sa-hero .sub { font-size: .8rem; opacity: .92; margin-top: .25rem; }
    .sa-hero .net { font-size: 1.6rem; font-weight: 800; margin-top: .6rem; font-variant-numeric: tabular-nums; }
    .sa-hero .netword { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }

    .sa-funnel { margin-top: .9rem; display: grid; gap: .55rem; }
    .sa-step { display: grid; gap: .25rem; }
    .sa-step .lab { display: flex; justify-content: space-between; font-size: .76rem; font-weight: 700;
        color: var(--color-gray-600); }
    .sa-step .lab b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .sa-step .bar { height: 1.5rem; border-radius: .5rem; background: var(--color-gray-100); overflow: hidden; }
    .sa-step .bar i { display: block; height: 100%; border-radius: .5rem; width: 0;
        transition: width .7s cubic-bezier(.22,1,.36,1); }
    .sa-step:nth-child(1) .bar i { background: linear-gradient(90deg, #6b9f3d, #4a7c2a); }
    .sa-step:nth-child(2) .bar i { background: linear-gradient(90deg, #f0b429, #d98214); }
    html.dark .sa-step .bar { background: rgb(255 255 255 / .07); }

    .sa-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .55rem; margin-top: .9rem; }
    @media (min-width: 640px) { .sa-grid { grid-template-columns: repeat(4, 1fr); } }
    .sa-stat { border-radius: .85rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: .65rem .75rem; }
    .sa-stat i { display: block; font-style: normal; font-size: .64rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .sa-stat b { display: block; font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900);
        font-variant-numeric: tabular-nums; margin-top: .15rem; overflow: hidden; text-overflow: ellipsis; }
    .sa-stat small { display: block; font-size: .66rem; color: var(--color-gray-400); margin-top: .1rem; }
    html.dark .sa-stat { background: #151b12; border-color: #2b3a1c; }
    html.dark .sa-stat b { color: #e8efe1; }

    .sa-mix { margin-top: .9rem; border-radius: .9rem; border: 1px solid var(--color-gray-200);
        background: var(--color-white); padding: .8rem .9rem; }
    .sa-mix h3 { font-size: .8rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .4rem; }
    .sa-mix-row { display: flex; justify-content: space-between; gap: .6rem; font-size: .8rem;
        color: var(--color-gray-600); padding: .22rem 0; }
    .sa-mix-row b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    html.dark .sa-mix { background: #151b12; border-color: #2b3a1c; }
    html.dark .sa-mix h3, html.dark .sa-mix-row b { color: #e8efe1; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc || ((s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const U = {
        list: '{{ route('admin.data.sales') }}',
        one: (id) => '{{ url('/admin/data/sales') }}/' + id,
        store: '{{ route('admin.sales.store') }}',
        del: (id) => '{{ url('/admin/sales') }}/' + id,
    };
    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const ask = async (url, opts) => {
        const res = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }, opts || {}));
        const j = await res.json();
        if (!res.ok || !j.success) throw new Error(j.message || 'Something went wrong.');
        return j;
    };
    const say = (m, k) => (window.toast ? toast(m, k) : alert(m));

    /* ---- the chooser tags ---- */
    $id('saTabBtn').addEventListener('click', () => window.openSheet && openSheet('saTabSheet'));
    $id('saCadenceBtn').addEventListener('click', () => window.openSheet && openSheet('saCadenceSheet'));
    const CADENCE_SAYS = { daily: 'Daily cost', weekly: 'Weekly cost', monthly: 'Monthly cost' };
    $id('saCadenceList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-sa-cadence]');
        if (!row) return;
        $id('saCadence').value = row.getAttribute('data-sa-cadence');
        $id('saCadenceNow').textContent = CADENCE_SAYS[$id('saCadence').value];
        document.querySelectorAll('#saCadenceList .dt-row').forEach((r) => r.classList.toggle('is-on', r === row));
        window.closeSheet && closeSheet('saCadenceSheet');
    });

    /* ---- the date tags drive the native pickers ---- */
    const wireDate = (btnId, inputId, nowId, word) => {
        const btn = $id(btnId), input = $id(inputId), now = $id(nowId);
        btn.addEventListener('click', () => {
            if (input.showPicker) { try { input.showPicker(); return; } catch (_) { } }
            input.focus();
            input.click();
        });
        input.addEventListener('change', () => {
            now.textContent = input.value
                ? new Date(input.value + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : word;
            now.classList.toggle('is-none', !input.value);
        });
    };
    wireDate('saFromBtn', 'saFrom', 'saFromNow', 'From date');
    wireDate('saToBtn', 'saTo', 'saToNow', 'To date');

    /* ---- the shelf ---- */
    async function load() {
        const j = await ask(U.list).catch((e) => { say(e.message, 'error'); return null; });
        if (!j) return;
        const rows = j.data.rows || [];
        $id('saEmpty').hidden = rows.length > 0;
        $id('saList').innerHTML = rows.map((r) => `
            <div class="sa-card" data-sa-open="${r.id}">
                <span class="min-w-0 grow">
                    <b>${esc(r.name)}</b>
                    <i>${esc(r.fromSays)} → ${esc(r.toSays)} · ${peso(r.adCost)} ${esc(r.adCadence)}</i>
                </span>
                <span role="button" tabindex="0" class="sa-del" data-sa-del="${r.id}" aria-label="Delete ${esc(r.name)}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                </span>
                <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>`).join('');
    }

    /* ---- the read of one batch ---- */
    async function open(id) {
        document.querySelectorAll('.sa-card').forEach((c) => c.classList.toggle('is-open', c.getAttribute('data-sa-open') === String(id)));
        const host = $id('saReport');
        host.hidden = false;
        host.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Reading the records…</p>';
        let d;
        try { d = (await ask(U.one(id))).data; } catch (e) { say(e.message, 'error'); host.hidden = true; return; }
        const convPct = d.registrations > 0 ? Math.max(2, Math.round(d.converted / d.registrations * 100)) : 0;
        host.innerHTML = `
            <div class="sa-hero ${d.net < 0 ? 'is-loss' : ''}">
                <h2>${esc(d.name)}</h2>
                <p class="sub">${esc(d.from)} → ${esc(d.to)} · ${d.days} days · ${peso(d.adCost)} ${esc(d.adCadence)} × ${d.units} = <b>${peso(d.spend)}</b> spent</p>
                <p class="netword">${d.net < 0 ? 'Net so far' : 'Net return'}</p>
                <p class="net">${d.net < 0 ? '−' : '+'}${peso(Math.abs(d.net))}</p>
            </div>
            <div class="sa-funnel card p-4">
                <div class="sa-step">
                    <span class="lab"><span>Registered <small>(workers excluded)</small></span><b>${d.registrations}</b></span>
                    <span class="bar"><i data-w="${d.registrations > 0 ? 100 : 0}"></i></span>
                </div>
                <div class="sa-step">
                    <span class="lab"><span>Became paying clients</span><b>${d.converted} · ${d.conversionRate}%</b></span>
                    <span class="bar"><i data-w="${convPct}"></i></span>
                </div>
            </div>
            <div class="sa-grid">
                <div class="sa-stat"><i>Ad spend</i><b>${peso(d.spend)}</b><small>${peso(d.adCost)} ${esc(d.adCadence)} × ${d.units}</small></div>
                <div class="sa-stat"><i>Registrations</i><b>${d.registrations}</b><small>${d.costPerRegistration !== null ? peso(d.costPerRegistration) + ' each' : 'no arrivals yet'}</small></div>
                <div class="sa-stat"><i>Clients won</i><b>${d.converted}</b><small>${d.costPerClient !== null ? peso(d.costPerClient) + ' to acquire one' : 'none converted yet'}</small></div>
                <div class="sa-stat"><i>Revenue</i><b>${peso(d.revenue)}</b><small>${d.avgRevenuePerClient !== null ? peso(d.avgRevenuePerClient) + ' per client' : '—'}</small></div>
                <div class="sa-stat"><i>Conversion rate</i><b>${d.conversionRate}%</b><small>of registrations paid</small></div>
                <div class="sa-stat"><i>Cost per registration</i><b>${d.costPerRegistration !== null ? peso(d.costPerRegistration) : '—'}</b><small>spend ÷ registrations</small></div>
                <div class="sa-stat"><i>Acquisition cost</i><b>${d.costPerClient !== null ? peso(d.costPerClient) : '—'}</b><small>spend ÷ clients</small></div>
                <div class="sa-stat"><i>Return on ad spend</i><b>${d.roas !== null ? d.roas + '×' : '—'}</b><small>revenue ÷ spend</small></div>
            </div>
            ${(d.planMix && d.planMix.length) ? `
            <div class="sa-mix">
                <h3>What they bought</h3>
                ${d.planMix.map((m) => `<div class="sa-mix-row"><span>${esc(m.plan)} × ${m.count}</span><b>${peso(m.revenue)}</b></div>`).join('')}
            </div>` : ''}`;
        requestAnimationFrame(() => host.querySelectorAll('.sa-step .bar i').forEach((i) => { i.style.width = i.getAttribute('data-w') + '%'; }));
        host.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    $id('saList').addEventListener('click', async (e) => {
        const del = e.target.closest('[data-sa-del]');
        if (del) {
            e.stopPropagation();
            if (!confirm('Delete this analysis? The registrations and sales it reads stay untouched.')) return;
            try {
                await ask(U.del(del.getAttribute('data-sa-del')), { method: 'DELETE' });
                $id('saReport').hidden = true;
                load();
            } catch (err) { say(err.message, 'error'); }
            return;
        }
        const card = e.target.closest('[data-sa-open]');
        if (card) open(card.getAttribute('data-sa-open'));
    });

    $id('saCreate').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const j = await ask(U.store, { method: 'POST', body: JSON.stringify({
                name: $id('saName').value.trim(),
                dateFrom: $id('saFrom').value,
                dateTo: $id('saTo').value,
                adCost: $id('saCost').value,
                adCadence: $id('saCadence').value,
            }) });
            say(j.message);
            $id('saName').value = '';
            await load();
            open(j.data.id);
        } catch (err) { say(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    load();
})();
</script>
@endpush
