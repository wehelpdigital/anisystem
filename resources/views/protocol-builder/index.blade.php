@extends('layouts.app')

@section('title', 'Protocol Builder')
@section('page-title', 'Protocol Builder')
@section('page-subtitle', 'Your own protocols, task by task')
@section('back', route('app.dashboard'))

@push('head')
<style>
    .rx-about { display: flex; gap: .85rem; align-items: flex-start; padding: 1rem 1.05rem; margin-bottom: 1rem; border-radius: 1.1rem; position: relative; overflow: hidden;
        background: linear-gradient(135deg, #f4f9ee 0%, #fdfaf0 100%); border: 1px solid #d9e8c8; }
    .rx-about::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: linear-gradient(180deg, #5c8f34, #b7862b); }
    .rx-about-e { flex: none; font-size: 1.5rem; line-height: 1.1; }
    .rx-about-t { min-width: 0; flex: 1 1 auto; }
    .rx-about-t b { display: block; font-family: var(--font-heading); font-size: 1rem; color: #2f5219; margin-bottom: .25rem; }
    .rx-about-t p { font-size: .82rem; line-height: 1.55; color: #3f4a37; }
    .rx-about-t ul { margin: .45rem 0 0; padding: 0; list-style: none; display: grid; gap: .25rem; }
    .rx-about-t li { font-size: .8rem; line-height: 1.45; color: #3f4a37; padding-left: 1.1rem; position: relative; }
    .rx-about-t li::before { content: '✓'; position: absolute; left: 0; top: 0; color: #4a7c2a; font-weight: 800; }
    html.dark .rx-about { background: linear-gradient(135deg, #17200f 0%, #221d10 100%); border-color: #2f3f1f; }
    html.dark .rx-about-t b { color: #cfe6b8; }
    html.dark .rx-about-t p, html.dark .rx-about-t li { color: #b7c2ad; }
    .rx-empty { text-align: center; padding: 2.4rem 1.5rem; }
    .rx-empty-e { display: inline-flex; width: 3.4rem; height: 3.4rem; border-radius: 1rem; background: var(--color-brand-50); color: var(--color-brand-700); align-items: center; justify-content: center; margin-bottom: .7rem; }
    .rx-empty-e svg { width: 1.6rem; height: 1.6rem; }
    .rx-empty-t { font-weight: 800; color: var(--color-gray-900); }
    .rx-empty-p { font-size: .84rem; color: var(--color-gray-500); max-width: 20rem; margin: .3rem auto 0; line-height: 1.5; }
    html.dark .rx-empty-e { background: #22301a; color: #a5c97e; }
    html.dark .rx-empty-t { color: #e8efe1; }

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
<div class="max-w-2xl mx-auto">
    <div class="rx-about">
        <span class="rx-about-e">📋</span>
        <div class="rx-about-t">
            <b>Write the protocol you actually follow</b>
            <p>A protocol is your season on paper: every task pinned to a day of the count — DAS, DAT or DAP — with what to apply, how much per knapsack, who it needs and how much it matters.</p>
            <ul>
                <li>Drag tasks into order; the day count keeps them honest.</li>
                <li>Every change saves itself, with undo and redo.</li>
                <li>Port a finished protocol into a real cropping schedule from a start date.</li>
                <li>Ask Anee to review it — what is strong, what is missing, what could go wrong.</li>
            </ul>
        </div>
    </div>

    <button type="button" class="pb-new mb-4" id="pbNewBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        New protocol
    </button>

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

    function paint() {
        const q = ($id('pbSearch').value || '').trim().toLowerCase();
        const shown = ROWS.filter((r) => !q || (r.title + ' ' + (r.cropLabel || '') + ' ' + (r.variety || '')).toLowerCase().includes(q));
        $id('pbRows').innerHTML = shown.map((r) => `
            <div class="pb-row" data-id="${r.id}" role="button" tabindex="0">
                <span class="pb-row-e">${esc(r.cropIcon || '🌱')}</span>
                <span class="pb-row-t">
                    <b>${esc(r.title)}</b>
                    <small>${esc(r.cropLabel || 'No crop yet')}${r.variety ? ' · ' + esc(r.variety) : ''} · ${esc((DAY_TYPES[r.dayType] || {}).label || r.dayType)} · ${r.count} ${r.count === 1 ? 'task' : 'tasks'}</small>
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

    const boot = () => load();
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
