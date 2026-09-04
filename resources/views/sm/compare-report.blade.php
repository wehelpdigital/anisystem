@extends('layouts.app')

@section('title', 'Compare Reports — ' . $schedule->title)
@section('page-title', 'Compare Reports')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Compare Reports ==========================================
       Two saved reports, one above the other (a phone reads down), and
       Anee's read of the difference between them when asked for. */
    .cp-wrap { max-width: 44rem; margin: 0 auto; }
    .cp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .cp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .cp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .cp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .cp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    .cp-ai { display: flex; gap: .6rem; align-items: flex-start; border-radius: .8rem;
        border: 1px solid #cfe3b8; background: linear-gradient(115deg, #f3f8ec, #e4efd4);
        padding: .7rem .8rem; margin-top: .8rem; cursor: pointer; }
    .cp-ai input { margin-top: .2rem; accent-color: #4a7c2a; width: 1.05rem; height: 1.05rem; flex: none; }
    .cp-ai b { color: #2d5016; font-size: .85rem; }
    .cp-ai i { display: block; font-style: normal; font-size: .76rem; color: #3d5226; margin-top: .15rem; line-height: 1.5; }
    html.dark .cp-ai { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .cp-ai b { color: #cfe6b8; }
    html.dark .cp-ai i { color: #a8bd93; }

    .cp-paper { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        margin-top: .9rem; overflow: hidden; }
    .cp-paper-h { display: flex; align-items: center; gap: .55rem; padding: .7rem .9rem;
        border-bottom: 1px solid var(--color-gray-100); }
    .cp-paper-h .tag { flex: none; width: 1.7rem; height: 1.7rem; border-radius: .55rem; display: inline-flex;
        align-items: center; justify-content: center; font-weight: 800; font-size: .82rem;
        background: var(--color-brand-600); color: #fff; }
    .cp-paper-h.is-b .tag { background: #b45309; }
    .cp-paper-h b { font-size: .85rem; color: var(--color-gray-900); min-width: 0; overflow-wrap: anywhere; }
    .cp-paper-body { padding: .8rem .9rem; font-size: .76rem; line-height: 1.6; color: var(--color-gray-700);
        white-space: pre-wrap; overflow-wrap: anywhere; font-variant-numeric: tabular-nums;
        max-height: 24rem; overflow-y: auto; }
    html.dark .cp-paper { background: #151b12; border-color: #2b3a1c; }
    html.dark .cp-paper-h { border-color: #222b1a; }
    html.dark .cp-paper-h b { color: #e8efe1; }
    html.dark .cp-paper-body { color: #b7c2ad; }

    .cp-anl { border-radius: 1rem; border: 1px solid #cfe3b8; padding: 1rem 1.1rem; margin-top: .9rem;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); }
    .cp-anl h3 { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: .92rem; color: #2d5016; }
    .cp-anl h3 img { width: 1.5rem; height: 1.5rem; border-radius: 999px; object-fit: cover; }
    .cp-anl .verdict { font-size: .84rem; color: #3d5226; line-height: 1.6; margin-top: .4rem; }
    .cp-anl h4 { font-size: .74rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: #4a7c2a; margin: .7rem 0 .25rem; }
    .cp-anl li { font-size: .8rem; color: #3d5226; line-height: 1.55; margin-left: 1rem; list-style: disc; }
    html.dark .cp-anl { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .cp-anl h3, html.dark .cp-anl h4 { color: #cfe6b8; }
    html.dark .cp-anl .verdict, html.dark .cp-anl li { color: #a8bd93; }

    .cp-acts { display: grid; grid-template-columns: 1fr; gap: .5rem; margin-top: .9rem; }
    @media (min-width: 640px) { .cp-acts { grid-template-columns: repeat(3, 1fr); } }

    /* The wait veil, while Anee reads the two. */
    .cp-wait { position: fixed; inset: 0; z-index: 110; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .6rem; padding: 2rem 1.2rem; text-align: center;
        background: rgb(250 250 248 / .98); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .cp-wait.is-on { opacity: 1; visibility: visible; pointer-events: auto; transition-delay: 0s; }
    .cp-wait .spin { width: 2.2rem; height: 2.2rem; border-radius: 999px; border: 3px solid var(--color-brand-200);
        border-top-color: var(--color-brand-600); animation: cpSpin .8s linear infinite; }
    @keyframes cpSpin { to { transform: rotate(360deg); } }
    .cp-wait p { font-size: .85rem; color: var(--color-gray-500); max-width: 24rem; }
    .cp-wait .stay { font-size: .8rem; font-weight: 700; color: #b45309; }
    html.dark .cp-wait { background: rgb(13 17 9 / .98); }
    html.dark .cp-wait .stay { color: #fbbf24; }
    @media (prefers-reduced-motion: reduce) { .cp-wait { transition: none; } }

    .cp-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .cp-saved-row:hover { background: var(--color-brand-50); }
    .cp-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .cp-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .cp-saved-row { border-color: #222b1a; }
    html.dark .cp-saved-row:hover { background: #161e10; }
    html.dark .cp-saved-row b { color: #e8efe1; }
</style>
@endpush

@section('content')
<div class="cp-wrap">
    <div class="cp-tabs" role="tablist">
        <button type="button" class="cp-tab is-on" id="cpTabGen">Compare</button>
        <button type="button" class="cp-tab" id="cpTabSaved">Saved</button>
    </div>

    <div id="cpGen">
        <div class="card p-4 mb-4" id="cpWizard">
            <p class="text-sm font-bold text-gray-900">Pick two saved reports</p>
            <p class="text-xs text-gray-500 mt-1 mb-3">Same kind against same kind — two protocols, two season reads, this year's profit against last year's. Pick the type first; they stack top and bottom, easy on a phone.</p>
            <div class="grid grid-cols-1 gap-2">
                <div>
                    <span class="form-label text-xs! mb-1!">What kind of report?</span>
                    <button type="button" class="crop-tag" id="cpKindBtn">
                        <span class="crop-tag-e" id="cpKindE">🗂️</span>
                        <span class="crop-tag-t is-none" id="cpKindNow">Choose the type</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
                <div>
                    <span class="form-label text-xs! mb-1!">Report A (top)</span>
                    <button type="button" class="crop-tag" id="cpABtn">
                        <span class="crop-tag-e">🅰️</span>
                        <span class="crop-tag-t is-none" id="cpANow">Choose a saved report</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
                <div>
                    <span class="form-label text-xs! mb-1!">Report B (below)</span>
                    <button type="button" class="crop-tag" id="cpBBtn">
                        <span class="crop-tag-e">🅱️</span>
                        <span class="crop-tag-t is-none" id="cpBNow">Choose a saved report</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
            </div>
            <label class="cp-ai" id="cpAiWrap" hidden>
                <input type="checkbox" id="cpWithAi">
                <span>
                    <b>Add {{ \App\Models\AiSetting::current()->assistantName }}'s analysis — <span id="cpPrice">30</span> credits</b>
                    <i>She reads both and says what is different, what is better in each, and what to carry forward. Leave it off and the comparison is free — just the two reports, stacked. You have <b id="cpBalance">…</b>.</i>
                </span>
            </label>
            <button type="button" class="btn btn-primary w-full mt-3" id="cpGenBtn" disabled>Compare them</button>
        </div>
        <div id="cpReport" hidden></div>
    </div>

    <div id="cpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="cpSavedList"></div>
            <div id="cpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every comparison lands here by itself.</p>
            </div>
        </div>
        <div id="cpSavedReport" class="mt-4" hidden></div>
    </div>

    <div class="cp-wait" id="cpWait" aria-live="polite">
        <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" style="width:3rem;height:3rem;border-radius:999px;object-fit:cover;">
        <span class="spin" aria-hidden="true"></span>
        <p style="font-weight:700;color:var(--color-gray-800)">Reading both reports…</p>
        <p class="stay">Please stay on this screen — leaving loses this run.</p>
    </div>
</div>
@endsection

@push('sheets')
<div class="sheet hidden" id="cpKindSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What kind of report?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="cpKindList"></div>
</div>
<div class="sheet hidden" id="cpPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="cpPickTitle">Choose a saved report</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="cpPickList"></div>
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
    const KIND_E = { labor: '👷', expenses: '🧾', profit: '📈', season: '🤖', sofar: '⚡', protocol: '📋' };
    const U = {
        options: @json(route('sm.compare.options') . '?id=' . $schedule->id),
        gen: @json(route('sm.compare.generate')),
        job: (id) => @json(route('sm.anee.job', ['id' => '__ID__'])).replace('__ID__', id),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=compare'),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        ai: @json(route('ai.index')),
    };
    const KIND_L = { labor: 'Labor Report', expenses: 'Expenses Report', profit: 'Profit Report', season: 'Anee Season Report', sofar: 'Analyze So Far', protocol: 'Protocol' };
    let OPTS = null;
    let KIND = null;
    let SEL = { a: null, b: null };
    let picking = 'a';

    const showTab = (gen) => {
        $id('cpTabGen').classList.toggle('is-on', gen);
        $id('cpTabSaved').classList.toggle('is-on', !gen);
        $id('cpGen').classList.toggle('hidden', !gen);
        $id('cpSavedPane').classList.toggle('hidden', gen);
        if (!gen) loadSaved();
    };
    $id('cpTabGen').addEventListener('click', () => showTab(true));
    $id('cpTabSaved').addEventListener('click', () => showTab(false));

    async function loadOptions() {
        try {
            const res = await api(U.options);
            OPTS = res.data;
            $id('cpPrice').textContent = OPTS.price;
            $id('cpBalance').textContent = OPTS.unlimited ? '∞' : Number(OPTS.balance).toLocaleString();
            $id('cpAiWrap').hidden = !OPTS.canUseAi;
        } catch (err) { toast(err.message, 'error'); }
    }

    /* Apples with apples: the type comes first, and the A/B shelves only
       ever show that type. Changing the type clears both picks. */
    function openKindSheet() {
        const counts = {};
        (OPTS?.reports || []).forEach((r) => { counts[r.kind] = (counts[r.kind] || 0) + 1; });
        $id('cpKindList').innerHTML = Object.keys(KIND_L).filter((k) => counts[k]).map((k) => `
            <button type="button" class="dt-row${KIND === k ? ' is-on' : ''}" data-cp-kind="${k}" ${counts[k] < 2 ? 'disabled style="opacity:.4"' : ''}>
                <span class="dt-row-e">${KIND_E[k] || '📄'}</span>
                <span class="dt-row-body"><b>${KIND_L[k]}</b><i>${counts[k]} saved${counts[k] < 2 ? ' — it takes two to compare' : ''}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('') || '<p class="text-sm text-gray-400 text-center py-6">Nothing on the shelf yet — generate some reports first.</p>';
        openSheet('cpKindSheet');
    }
    $id('cpKindBtn').addEventListener('click', openKindSheet);
    $id('cpKindList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-cp-kind]');
        if (!row || row.disabled) return;
        const k = row.dataset.cpKind;
        closeSheet('cpKindSheet');
        if (k === KIND) return;
        KIND = k;
        $id('cpKindE').textContent = KIND_E[k] || '📄';
        const t = $id('cpKindNow');
        t.textContent = KIND_L[k];
        t.classList.remove('is-none');
        SEL = { a: null, b: null };
        ['cpANow', 'cpBNow'].forEach((id) => {
            const el = $id(id);
            el.textContent = 'Choose a saved report';
            el.classList.add('is-none');
        });
        $id('cpGenBtn').disabled = true;
    });

    function openPick(which) {
        if (!KIND) { openKindSheet(); return; }
        picking = which;
        $id('cpPickTitle').textContent = (which === 'a' ? 'Report A (top)' : 'Report B (below)') + ' — ' + KIND_L[KIND];
        const other = which === 'a' ? SEL.b : SEL.a;
        $id('cpPickList').innerHTML = (OPTS?.reports || []).filter((r) => r.kind === KIND).map((r) => `
            <button type="button" class="dt-row${(SEL[which] && SEL[which].id === r.id) ? ' is-on' : ''}" data-cp-pick="${r.id}" ${other && other.id === r.id ? 'disabled style="opacity:.4"' : ''}>
                <span class="dt-row-e">${KIND_E[r.kind] || '📄'}</span>
                <span class="dt-row-body"><b>${esc(r.title)}</b><i>${esc(r.when || '')}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('') || `<p class="text-sm text-gray-400 text-center py-6">No saved ${esc(KIND_L[KIND])} yet.</p>`;
        openSheet('cpPickSheet');
    }
    $id('cpABtn').addEventListener('click', () => openPick('a'));
    $id('cpBBtn').addEventListener('click', () => openPick('b'));
    $id('cpPickList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-cp-pick]');
        if (!row || row.disabled) return;
        const id = Number(row.dataset.cpPick);
        SEL[picking] = (OPTS.reports || []).find((r) => r.id === id) || null;
        const t = $id(picking === 'a' ? 'cpANow' : 'cpBNow');
        t.textContent = SEL[picking].title;
        t.classList.remove('is-none');
        closeSheet('cpPickSheet');
        $id('cpGenBtn').disabled = !(SEL.a && SEL.b);
    });

    $id('cpGenBtn').addEventListener('click', async (e) => {
        if (!SEL.a || !SEL.b) return;
        const withAi = !!$id('cpWithAi')?.checked && !$id('cpAiWrap').hidden;
        const btn = e.currentTarget;
        btn.disabled = true;
        if (withAi) $id('cpWait').classList.add('is-on');
        try {
            const res = await api(U.gen, { method: 'POST', body: {
                scheduleId: @json($schedule->id), aId: SEL.a.id, bId: SEL.b.id, withAi: withAi ? 1 : 0,
            } });
            let data = res.data;
            if (data.pending) {
                for (let i = 0; i < 120 && (!data || !data.report); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id));
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || !data.report) throw new Error('Still working — check the Saved tab in a minute.');
            }
            drawCompare($id('cpReport'), data.report, data, 'fresh');
            $id('cpReport').hidden = false;
            $id('cpWizard').hidden = true;
            toast(withAi ? `Done — ${OPTS.price} credits used. Saved to the shelf.` : 'Comparison saved to the shelf.');
        } catch (err) { toast(err.message, 'error'); }
        finally {
            btn.disabled = !(SEL.a && SEL.b);
            $id('cpWait').classList.remove('is-on');
        }
    });

    function drawCompare(host, r, meta, mode) {
        r = r || {};
        const paper = (side, m) => `<div class="cp-paper">
            <div class="cp-paper-h${side === 'B' ? ' is-b' : ''}"><span class="tag">${side}</span><b>${esc(m?.title || '')}</b></div>
            <div class="cp-paper-body">${esc(m?.body || '')}</div>
        </div>`;
        const anl = r.analysis;
        const ul = (arr) => (arr || []).map((x) => `<li>${esc(x)}</li>`).join('');
        host.innerHTML = paper('A', r.a) + paper('B', r.b)
            + (anl ? `<div class="cp-anl">
                <h3><img src="${esc(FACE)}" alt="">${esc(ANEE)}'s read of the difference</h3>
                <p class="verdict"><b>${esc(anl.headline || '')}</b><br>${esc(anl.verdict || '')}</p>
                ${(anl.differences || []).length ? `<h4>What's different</h4><ul>${ul(anl.differences)}</ul>` : ''}
                ${(anl.betterInA || []).length ? `<h4>Better in A</h4><ul>${ul(anl.betterInA)}</ul>` : ''}
                ${(anl.betterInB || []).length ? `<h4>Better in B</h4><ul>${ul(anl.betterInB)}</ul>` : ''}
                ${(anl.advice || []).length ? `<h4>Carry forward</h4><ul>${ul(anl.advice)}</ul>` : ''}
            </div>` : '')
            + `<div class="cp-acts">
                <a class="btn btn-primary w-full" href="${U.ai}?freport=${meta.id}">
                    <img src="${esc(FACE)}" alt="" style="width:1rem;height:1rem;border-radius:999px;object-fit:cover;margin-right:.35rem;">
                    Ask ${esc(ANEE)}
                </a>
                ${mode === 'fresh' ? '<button type="button" class="btn btn-white w-full" data-cp-again>Compare others</button>' : ''}
                <button type="button" class="btn btn-white w-full" data-cp-del="${meta.id}">Delete</button>
            </div>`;
        host.querySelector('[data-cp-again]')?.addEventListener('click', () => {
            host.hidden = true;
            $id('cpWizard').hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        host.querySelector('[data-cp-del]')?.addEventListener('click', async (e) => {
            // currentTarget is gone after any await — take the id first.
            const delId = e.currentTarget.getAttribute('data-cp-del');
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this comparison?', message: 'It leaves the shelf.', confirmText: 'Delete' }) : true;
            if (!ok) return;
            try {
                await api(U.del(delId), { method: 'DELETE' });
                toast('Comparison removed.');
                host.hidden = true;
                $id('cpWizard').hidden = false;
                loadSaved();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    async function loadSaved() {
        try {
            const res = await api(U.list);
            const rows = res.data.rows || [];
            $id('cpSavedEmpty').classList.toggle('hidden', rows.length > 0);
            $id('cpSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="cp-saved-row" data-cp-open="${r.id}">
                    <span style="font-size:1.2rem;flex:none;">⚖️</span>
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${esc(r.when || '')}${r.credits > 0 ? ' · ' + r.credits + ' credits' : ''}</small></span>
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }
    $id('cpSavedList').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-cp-open]');
        if (!row) return;
        try {
            const res = await api(U.one(row.getAttribute('data-cp-open')));
            drawCompare($id('cpSavedReport'), res.data.report, res.data, 'saved');
            $id('cpSavedReport').hidden = false;
            $id('cpSavedReport').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) { toast(err.message, 'error'); }
    });

    loadOptions();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
