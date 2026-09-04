@extends('layouts.app')

@section('title', 'View as Protocol — ' . $schedule->title)
@section('page-title', 'View as Protocol')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== View as Protocol =========================================
       A lot's season written as a recipe: day-count badges down the
       left, the step and its materials beside them, the yield at the
       end as the payoff line. */
    .pt-wrap { max-width: 44rem; margin: 0 auto; }
    .pt-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .pt-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .pt-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .pt-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .pt-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    .pt-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .pt-hero h2 { font-size: 1.15rem; font-weight: 800; }
    .pt-hero .sub { font-size: .82rem; opacity: .92; margin-top: .3rem; }
    .pt-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .pt-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .pt-steps { margin-top: .9rem; display: grid; gap: .5rem; }
    .pt-step { display: flex; gap: .7rem; border: 1px solid var(--color-gray-200); border-radius: .9rem;
        background: var(--color-white); padding: .7rem .8rem; }
    .pt-day { flex: none; width: 4.6rem; text-align: center; align-self: flex-start;
        border-radius: .6rem; padding: .35rem .2rem; background: var(--color-brand-50);
        color: var(--color-brand-800); font-weight: 800; font-size: .78rem; }
    .pt-day.is-pre { background: #fef3c7; color: #92400e; }
    .pt-day.is-zero { background: var(--color-brand-600); color: #fff; }
    .pt-day small { display: block; font-weight: 600; font-size: .64rem; opacity: .8; }
    .pt-body { min-width: 0; flex: 1 1 auto; }
    .pt-body b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .pt-meta { display: flex; flex-wrap: wrap; gap: .25rem .45rem; margin-top: .25rem; }
    .pt-mats { margin-top: .35rem; display: grid; gap: .15rem; }
    .pt-mat { font-size: .78rem; color: var(--color-gray-600); }
    .pt-mat::before { content: '• '; color: var(--color-brand-600); font-weight: 800; }
    .pt-yield { border-radius: 1rem; border: 1px solid #cfe3b8; padding: 1rem 1.1rem;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); font-size: .88rem; color: #2d5016;
        line-height: 1.55; margin-top: .9rem; }
    .pt-yield b { display: block; font-size: .95rem; margin-bottom: .2rem; }
    .pt-note { font-size: .75rem; color: var(--color-gray-500); margin-top: .6rem; }
    .pt-acts { display: grid; grid-template-columns: 1fr; gap: .5rem; margin-top: .9rem; }
    @media (min-width: 640px) { .pt-acts { grid-template-columns: repeat(4, 1fr); } }
    html.dark .pt-step { background: #151b12; border-color: #2b3a1c; }
    html.dark .pt-body b { color: #e8efe1; }
    html.dark .pt-day { background: #22301a; color: #cfe6b8; }
    html.dark .pt-day.is-zero { background: #4a7c2a; color: #fff; }
    html.dark .pt-yield { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #a8bd93; }

    .pt-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .pt-saved-row:hover { background: var(--color-brand-50); }
    .pt-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .pt-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .pt-saved-row { border-color: #222b1a; }
    html.dark .pt-saved-row:hover { background: #161e10; }
    html.dark .pt-saved-row b { color: #e8efe1; }

    @media print {
        header, nav, .pt-tabs, .pt-acts, .pt-wizard, .bottom-nav, .tabbar, #aiFloat { display: none !important; }
        .pt-step { page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')
<div class="pt-wrap">
    <div class="pt-tabs" role="tablist">
        <button type="button" class="pt-tab is-on" id="ptTabGen">Generate</button>
        <button type="button" class="pt-tab" id="ptTabSaved">Saved</button>
    </div>

    <div id="ptGen">
        <div class="card p-4 mb-4 pt-wizard" id="ptWizard">
            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Which lot's season becomes the recipe?</p>
            <p class="text-xs text-gray-500 mt-1 mb-3">Only work that was ticked done goes in — this is the record of what you actually did, step by step on the lot's own day count. When a season turns out well, this is the page you keep.</p>
            <button type="button" class="crop-tag" id="ptLotBtn">
                <span class="crop-tag-e">🌾</span>
                <span class="crop-tag-t is-none" id="ptLotNow">Choose the lot</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <button type="button" class="btn btn-primary w-full mt-3" id="ptGenBtn" disabled>Write the protocol</button>
        </div>
        <div id="ptReport" hidden></div>
    </div>

    <div id="ptSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="ptSavedList"></div>
            <div id="ptSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900 dark:text-gray-100">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every protocol you write lands here by itself.</p>
            </div>
        </div>
        <div id="ptSavedReport" class="mt-4" hidden></div>
    </div>
</div>
@endsection

@push('sheets')
<div class="sheet hidden" id="ptLotSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lot?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="ptLotList">
        @forelse ($schedule->lots as $lot)
            <button type="button" class="dt-row" data-pt-lot="{{ $lot->id }}">
                <span class="dt-row-e">🌾</span>
                <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }}{{ $lot->variety ? ' · ' . $lot->variety : '' }} · counts in {{ $lot->dayType ?: $schedule->dayType }}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @empty
            <p class="text-sm text-gray-400 text-center py-6">This season has no lots yet.</p>
        @endforelse
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const ANEE = @json(\App\Models\AiSetting::current()->assistantName);
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const U = {
        gen: @json(route('sm.protocol.generate')),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=protocol'),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        ai: @json(route('ai.index')),
    };
    let LOT_ID = 0;
    let LAST = null;

    const showTab = (gen) => {
        $id('ptTabGen').classList.toggle('is-on', gen);
        $id('ptTabSaved').classList.toggle('is-on', !gen);
        $id('ptGen').classList.toggle('hidden', !gen);
        $id('ptSavedPane').classList.toggle('hidden', gen);
        if (!gen) loadSaved();
    };
    $id('ptTabGen').addEventListener('click', () => showTab(true));
    $id('ptTabSaved').addEventListener('click', () => showTab(false));

    $id('ptLotBtn').addEventListener('click', () => openSheet('ptLotSheet'));
    $id('ptLotList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-pt-lot]');
        if (!row) return;
        LOT_ID = Number(row.dataset.ptLot);
        document.querySelectorAll('#ptLotList [data-pt-lot]').forEach((r) => r.classList.toggle('is-on', r === row));
        const t = $id('ptLotNow');
        t.textContent = row.querySelector('b').textContent;
        t.classList.remove('is-none');
        $id('ptGenBtn').disabled = false;
        closeSheet('ptLotSheet');
    });

    $id('ptGenBtn').addEventListener('click', async (e) => {
        if (!LOT_ID) return;
        const btn = e.currentTarget;
        btn.disabled = true;
        btn.textContent = 'Writing…';
        try {
            const res = await api(U.gen, { method: 'POST', body: { scheduleId: @json($schedule->id), lotId: LOT_ID } });
            LAST = res.data;
            drawProtocol($id('ptReport'), LAST.report, LAST, 'fresh');
            $id('ptReport').hidden = false;
            $id('ptWizard').hidden = true;
            toast('Protocol written and saved to the shelf.');
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = 'Write the protocol'; }
    });

    function drawProtocol(host, r, meta, mode) {
        r = r || {};
        const chips = [];
        if (r.crop) chips.push(esc(r.crop));
        if (r.variety) chips.push(esc(r.variety));
        if (r.size) chips.push(esc(r.size));
        if (r.daySystem) chips.push('counts in ' + esc(r.daySystem));
        if (r.span) chips.push(esc(r.span));
        const steps = (r.steps || []).map((s2) => {
            const cls = s2.day === 0 ? ' is-zero' : (s2.day !== null && s2.day < 0 ? ' is-pre' : '');
            return `<div class="pt-step">
                <span class="pt-day${cls}">${s2.dayLabel ? esc(s2.dayLabel) : '—'}<small>${esc(s2.date || '')}${s2.endDate ? '→' + esc(s2.endDate) : ''}</small></span>
                <span class="pt-body">
                    <b>${esc(s2.title)}</b>
                    <span class="pt-meta">
                        ${s2.time ? `<span class="badge badge-gray">${esc(s2.time)}</span>` : ''}
                        ${s2.crew ? `<span class="badge badge-gray">${s2.crew} ${s2.crew === 1 ? 'worker' : 'workers'}</span>` : ''}
                        ${s2.wholeFarm ? '<span class="badge badge-yellow">whole farm</span>' : ''}
                    </span>
                    ${(s2.materials || []).length ? `<span class="pt-mats">${s2.materials.map((m) => `<span class="pt-mat">${esc(m)}</span>`).join('')}</span>` : ''}
                </span>
            </div>`;
        }).join('');
        host.innerHTML = `
            <div class="pt-hero">
                <h2>${esc(r.lot || '')} — the protocol</h2>
                <p class="sub">${esc(r.schedule || '')}${r.zeroDate ? ' · day zero ' + esc(r.zeroDate) : ''}</p>
                <div class="pt-chips">${chips.map((c) => `<span class="pt-chip">${c}</span>`).join('')}</div>
            </div>
            <div class="pt-steps">${steps || '<p class="text-sm text-gray-400 py-6 text-center">No ticked work touches this lot yet.</p>'}</div>
            ${(r.yields || []).length ? `<div class="pt-yield"><b>🌾 What this protocol produced</b>${r.yields.map(esc).join('; ')}</div>` : ''}
            ${r.skippedPlanned ? `<p class="pt-note">${r.skippedPlanned} planned but never-ticked ${r.skippedPlanned === 1 ? 'activity is' : 'activities are'} left out — this page is what was actually done.</p>` : ''}
            <div class="pt-acts">
                <a class="btn btn-primary w-full" href="${U.ai}?freport=${meta.id}">
                    <img src="${esc(FACE)}" alt="" style="width:1rem;height:1rem;border-radius:999px;object-fit:cover;margin-right:.35rem;">
                    Ask ${esc(ANEE)}
                </a>
                <button type="button" class="btn btn-white w-full" data-pt-copy>Copy as Text</button>
                <button type="button" class="btn btn-white w-full" onclick="window.print()">Print</button>
                ${mode === 'fresh'
                    ? '<button type="button" class="btn btn-white w-full" data-pt-again>Another lot</button>'
                    : `<button type="button" class="btn btn-white w-full" data-pt-del="${meta.id}">Delete</button>`}
            </div>`;
        host.querySelector('[data-pt-again]')?.addEventListener('click', () => {
            host.hidden = true;
            $id('ptWizard').hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        host.querySelector('[data-pt-copy]')?.addEventListener('click', async () => {
            try {
                const res = await api(U.one(meta.id));
                await navigator.clipboard.writeText(res.data.body || '');
                toast('Protocol copied to clipboard.');
            } catch (err) { toast('Copy failed on this browser.', 'error'); }
        });
        host.querySelector('[data-pt-del]')?.addEventListener('click', async (e) => {
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this protocol?', message: 'It leaves the shelf.', confirmText: 'Delete' }) : true;
            if (!ok) return;
            try {
                await api(U.del(e.currentTarget.getAttribute('data-pt-del')), { method: 'DELETE' });
                toast('Protocol removed.');
                host.hidden = true;
                loadSaved();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    async function loadSaved() {
        try {
            const res = await api(U.list);
            const rows = res.data.rows || [];
            $id('ptSavedEmpty').classList.toggle('hidden', rows.length > 0);
            $id('ptSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="pt-saved-row" data-pt-open="${r.id}">
                    <span style="font-size:1.2rem;flex:none;">📋</span>
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${esc(r.when || '')}</small></span>
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }
    $id('ptSavedList').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-pt-open]');
        if (!row) return;
        try {
            const res = await api(U.one(row.getAttribute('data-pt-open')));
            drawProtocol($id('ptSavedReport'), res.data.report, res.data, 'saved');
            $id('ptSavedReport').hidden = false;
            $id('ptSavedReport').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) { toast(err.message, 'error'); }
    });

    /* One quick jump: ?lot=ID pre-picks and generates. */
    const bootLot = new URLSearchParams(location.search).get('lot');
    if (bootLot && document.querySelector(`#ptLotList [data-pt-lot="${bootLot}"]`)) {
        document.querySelector(`#ptLotList [data-pt-lot="${bootLot}"]`).click();
    }
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
