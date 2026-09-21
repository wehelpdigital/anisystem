@extends('layouts.app')

@section('title', 'Protocol Builder')
@section('page-title', 'Protocol Builder')
@section('page-subtitle', 'Your own protocols, task by task')
@section('back', route('app.dashboard'))

@push('head')
<style>
    .rx-empty { text-align: center; padding: 2.4rem 1.5rem; }
    .rx-empty-e { display: inline-flex; width: 3.4rem; height: 3.4rem; border-radius: 1rem; background: var(--color-brand-50); color: var(--color-brand-700); align-items: center; justify-content: center; margin-bottom: .7rem; }
    .rx-empty-e svg { width: 1.6rem; height: 1.6rem; }
    .rx-empty-t { font-weight: 800; color: var(--color-gray-900); }
    .rx-empty-p { font-size: .84rem; color: var(--color-gray-500); max-width: 20rem; margin: .3rem auto 0; line-height: 1.5; }
    html.dark .rx-empty-e { background: #22301a; color: #a5c97e; }
    html.dark .rx-empty-t { color: #e8efe1; }

    /* The note above the shelf — the same foldable card the analyses open
       with. It folds to its title, the choice is remembered, and the fold
       animates on grid rows rather than snapping. */
    .wtp-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .wtp-quote b { color: #2d5016; }
    .q-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left; padding: .7rem .9rem; cursor: pointer; }
    .q-head .q-ico { font-size: 1.15rem; flex: none; }
    .q-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .q-hint { flex: none; font-size: .74rem; font-weight: 700; color: #3d5226; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-hint { opacity: .8; }
    .q-c { flex: none; width: 1rem; height: 1rem; color: #3d5226; opacity: .6; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-c { transform: rotate(-90deg); }
    .q-body { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { grid-template-rows: 0fr; opacity: 0; }
    .q-body-in { min-height: 0; overflow: hidden; display: grid; gap: .5rem; padding: 0 .9rem; }
    .q-body-in::after { content: ''; height: .4rem; }
    .q-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226; line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }
    .q-card b { display: block; margin-bottom: .1rem; }
    .q-card b.is-inline { display: inline; margin: 0; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b, html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(0 0 0 / .18); border-color: #2f3f1f; color: #b7c2ad; }
    @media (prefers-reduced-motion: reduce) { .q-body, .q-c, .q-hint { transition: none; } }
    .pb-new { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .8rem 1rem; border-radius: .9rem; font-weight: 800; font-size: .92rem;
        background: #3d6823; color: #fff; border: 1px solid #3d6823; transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-new:hover { background: #2f5219; transform: translateY(-1px); }
    .pb-new svg { width: 1.1rem; height: 1.1rem; }
    .pb-row { display: flex; align-items: center; gap: .75rem; width: 100%; text-align: left; padding: .85rem 1rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pb-row:last-child { border-bottom: 0; }
    .pb-row:hover { background: var(--color-brand-50); }
    .pb-row-e { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; background: #eef6e6; }
    .pb-row-t { flex: 1 1 auto; min-width: 0; }
    .pb-row-t b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pb-row-t small { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; }
    .pb-row-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .pb-tag { display: inline-flex; align-items: center; gap: .25rem; padding: .16rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); }
    .pb-tag.is-score { border-color: #cfe3bd; color: #2f5219; background: #f1f8ea; }
    .pb-tag.is-ported { border-color: #c7d2f5; color: #3546a8; background: #eef2fd; }
    .pb-row-more { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400); }
    .pb-row-more:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .pb-row-more svg { width: 1.15rem; height: 1.15rem; }
    html.dark .pb-row { border-color: #2b3a1c; }
    html.dark .pb-row:hover { background: #1c2416; }
    html.dark .pb-row-e { background: #22301a; }
    html.dark .pb-row-t b { color: #e8efe1; }
    html.dark .pb-tag { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-tag.is-score { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pb-tag.is-ported { background: #1d2440; border-color: #33417a; color: #b9c6f5; }
    .pb-search { position: relative; margin: 0 0 .75rem; }
    .pb-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .pb-search .form-input { padding-left: 2.4rem !important; }
    @media (prefers-reduced-motion: reduce) { .pb-new, .pb-row { transition: none; } }
</style>
@endpush

@section('content')
@php $aboutOpt = ['quote' => $options['quote'], 'balance' => $options['balance'], 'unlimited' => $options['unlimited'], 'canAnalyze' => $options['canAnalyze'], 'aiLocked' => $options['aiLocked']]; @endphp
<div class="max-w-2xl mx-auto">
    <div class="wtp-quote" id="pbAbout">
        <button type="button" class="q-head" id="pbAboutHead" aria-expanded="true">
            <span class="q-ico">📋</span>
            <span class="q-title">What the Protocol Builder is</span>
            <span class="q-hint" id="pbAboutHint">Tap to read</span>
            <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="q-body">
            <div class="q-body-in">
                <div class="q-card"><b>Your season, written by you.</b>A protocol is the plan you actually follow: every task pinned to a day of the count — DAS, DAT or DAP — with what to apply, who it needs and how much it matters. Write it from your own experience or an agronomist's sheet, keep it season after season, port it into a cropping schedule when the day comes, or have Anee review it.</div>
                <div class="q-card" id="pbAboutCost"></div>
            </div>
        </div>
    </div>

    <button type="button" class="pb-new mb-4" id="pbNewBtn">New protocol</button>

    <div class="pb-search hidden" id="pbSearchWrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" id="pbSearch" class="form-input" placeholder="Find a protocol…" autocomplete="off">
    </div>

    <div class="card !p-0 overflow-hidden">
        <div id="pbRows"></div>
        <div class="rx-empty hidden" id="pbEmpty">
            <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h6m-6 4h4"/></svg></span>
            <p class="rx-empty-t">No protocol yet</p>
            <p class="rx-empty-p">Start one above: name it, choose the crop and how its days are counted, then add the tasks one by one.</p>
        </div>
        <div class="rx-empty hidden" id="pbNone">
            <p class="rx-empty-t">Nothing matches</p>
            <p class="rx-empty-p">Try another word.</p>
        </div>
    </div>
</div>

@include('partials.user-tags')

{{-- New protocol --}}
<div class="sheet hidden" id="pbNewSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">New protocol</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        @include('protocol-builder.partials.head-form', ['pfx' => 'pbn'])
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbNewSave">Start the protocol</button>
    </div>
</div>

{{-- A row's menu --}}
<div class="sheet hidden" id="pbRowMenu" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbRowMenuTitle">Protocol</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows">
        <button type="button" class="dt-row" data-row-act="open"><span class="dt-row-e">📖</span><span class="dt-row-body"><b>Open</b><i>Read it, port it, or ask Anee.</i></span></button>
        <button type="button" class="dt-row" data-row-act="edit"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Edit</b><i>Add, change and reorder the tasks.</i></span></button>
        <button type="button" class="dt-row" data-row-act="copy"><span class="dt-row-e">📑</span><span class="dt-row-body"><b>Duplicate</b><i>A copy to change without touching this one.</i></span></button>
        <button type="button" class="dt-row" data-row-act="delete"><span class="dt-row-e">🗑️</span><span class="dt-row-body"><b>Delete</b><i>Remove it from your list.</i></span></button>
    </div>
</div>

<script>
(() => {
    const U = {
        list: @json(route('pb.list')),
        store: @json(route('pb.store')),
        open: (id) => @json(url('/app/protocol-builder')) + '/' + id,
    };
    const $id = (i) => document.getElementById(i);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const DAY_TYPES = @json($options['dayTypes']);
    let ROWS = [];
    let MENU_ID = null;

    // The note folds to its title and remembers the choice, like the analyses' cards.
    const ABOUT_KEY = 'anee-pb-about-min';
    let aboutMin = false;
    try { aboutMin = localStorage.getItem(ABOUT_KEY) === '1'; } catch (_) { /* opens full */ }
    function paintAbout() {
        $id('pbAbout').classList.toggle('is-min', aboutMin);
        $id('pbAboutHead').setAttribute('aria-expanded', aboutMin ? 'false' : 'true');
    }
    $id('pbAboutHead').addEventListener('click', () => {
        aboutMin = !aboutMin;
        try { localStorage.setItem(ABOUT_KEY, aboutMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintAbout();
    });
    paintAbout();
    // The coin is the bundle's; paint the cost line once it is there.
    function paintCost() {
        const o = @json($aboutOpt);
        const cost = $id('pbAboutCost');
        if (o.aiLocked) { cost.innerHTML = "Building and porting cost nothing. Anee's review comes with <b class=\"is-inline\">Libre + Anee</b> and every plan above it."; return; }
        if (!o.canAnalyze) { cost.innerHTML = "Building and porting cost nothing. Anee's review is not available right now."; return; }
        cost.innerHTML = `Building and porting cost nothing. Anee's review spends <b class="is-inline">${o.quote} credits</b>, and you have ${window.creditCoin(o.unlimited ? '∞' : Number(o.balance).toLocaleString())}. Nothing is charged until you ask for one.`;
    }

    function paint() {
        const q = ($id('pbSearch').value || '').trim().toLowerCase();
        const shown = ROWS.filter((r) => !q || (r.title + ' ' + (r.cropLabel || '') + ' ' + (r.variety || '') + ' ' + (r.tags || []).join(' ')).toLowerCase().includes(q));
        $id('pbRows').innerHTML = shown.map((r) => `
            <div class="pb-row" data-id="${r.id}" role="button" tabindex="0">
                <span class="pb-row-e">${esc(r.cropIcon || '🌱')}</span>
                <span class="pb-row-t">
                    <b>${esc(r.title)}</b>
                    <small>${esc(r.cropLabel || 'No crop yet')}${r.variety ? ' · ' + esc(r.variety) : ''} · ${esc((DAY_TYPES[r.dayType] || {}).label || r.dayType)} · ${r.count} ${r.count === 1 ? 'task' : 'tasks'}${r.notes ? ` · ${r.notes} ${r.notes === 1 ? 'note' : 'notes'}` : ''}</small>
                    ${window.userTags ? window.userTags.chips(r.tags) : ''}
                    <span class="pb-row-tags">
                        ${r.score !== null ? `<span class="pb-tag is-score">Anee: ${r.score}/100</span>` : ''}
                        ${r.ported ? `<span class="pb-tag is-ported">Ported ${esc(r.ported.at || '')}</span>` : ''}
                        <span class="pb-tag">Updated ${esc(r.updated || '')}</span>
                    </span>
                </span>
                <span class="pb-row-more" data-more="${r.id}" role="button" tabindex="0" aria-label="More">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                </span>
            </div>`).join('');
        $id('pbEmpty').classList.toggle('hidden', ROWS.length > 0);
        $id('pbNone').classList.toggle('hidden', !(ROWS.length > 0 && shown.length === 0));
        $id('pbSearchWrap').classList.toggle('hidden', ROWS.length < 6);
    }
    async function load() {
        try {
            const res = await api(U.list, { method: 'GET' });
            ROWS = res.data.rows || [];
            paint();
        } catch (err) { toast(err.message, 'error'); }
    }
    $id('pbSearch').addEventListener('input', paint);
    $id('pbRows').addEventListener('click', (e) => {
        const more = e.target.closest('[data-more]');
        if (more) {
            e.stopPropagation();
            MENU_ID = +more.getAttribute('data-more');
            const r = ROWS.find((x) => x.id === MENU_ID);
            $id('pbRowMenuTitle').textContent = r ? r.title : 'Protocol';
            openSheet('pbRowMenu');
            return;
        }
        const row = e.target.closest('.pb-row[data-id]');
        if (row) window.location.href = U.open(row.getAttribute('data-id'));
    });
    $id('pbRows').addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const row = e.target.closest('.pb-row[data-id]');
        if (row && e.target === row) { e.preventDefault(); window.location.href = U.open(row.getAttribute('data-id')); }
    });
    $id('pbRowMenu').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-row-act]');
        if (!b || MENU_ID === null) return;
        const act = b.getAttribute('data-row-act');
        const r = ROWS.find((x) => x.id === MENU_ID);
        closeSheet('pbRowMenu');
        if (act === 'open') { window.location.href = U.open(MENU_ID); return; }
        if (act === 'edit') { window.location.href = U.open(MENU_ID) + '?edit=1'; return; }
        if (act === 'copy') {
            try {
                const res = await api(U.open(MENU_ID) + '/duplicate', { method: 'POST', body: {} });
                ROWS.unshift(res.data.row);
                paint();
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
            return;
        }
        if (act === 'delete') {
            const ok = await window.confirmAction({ title: 'Delete this protocol?', message: `"${r ? r.title : 'This protocol'}" and its tasks will be removed from your list.`, confirmText: 'Delete', danger: true });
            if (!ok) return;
            try {
                const res = await api(U.open(MENU_ID) + '/delete', { method: 'POST', body: {} });
                ROWS = ROWS.filter((x) => x.id !== MENU_ID);
                paint();
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    // New protocol
    const form = window.pbHeadForm('pbn');
    $id('pbNewBtn').addEventListener('click', () => {
        form.fill({ title: '', crop: null, variety: '', dayType: 'DAS', description: '' });
        openSheet('pbNewSheet');
    });
    $id('pbNewSave').addEventListener('click', async () => {
        const why = form.check();
        if (why) { toast(why, 'error'); return; }
        const btn = $id('pbNewSave');
        btn.disabled = true;
        try {
            const res = await api(U.store, { method: 'POST', body: form.read() });
            window.location.href = res.data.url;
        } catch (err) { toast(err.message, 'error'); btn.disabled = false; }
    });

    const boot = () => { paintCost(); load(); };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
