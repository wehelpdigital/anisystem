@extends('layouts.app')

@section('title', 'Protocol Builder')
@section('page-title', 'Protocol Builder')
@section('page-subtitle', 'Your own protocols, task by task')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

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
    .pb-port { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .8rem 1rem; border-radius: .9rem; font-weight: 800; font-size: .92rem;
        background: var(--color-white); color: #2f5219; border: 1.5px solid #cfe3bd; transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-port:hover { background: #f1f8ea; transform: translateY(-1px); }
    .pb-port svg { width: 1.05rem; height: 1.05rem; }
    html.dark .pb-port { background: #151b12; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pb-port:hover { background: #22301a; }
    /* The port sheet's lots: one card each, one field to a row. */
    .pp-lot { border: 1px solid var(--color-gray-200); border-radius: 1rem; padding: .75rem .8rem .8rem; margin-bottom: .6rem; background: var(--color-gray-50); display: grid; gap: .55rem; }
    .pp-lot-h { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .pp-lot-h b { font-size: .8rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
    .pp-x { width: 1.9rem; height: 1.9rem; border-radius: .55rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400); }
    .pp-x:hover { background: #fdecec; color: #b91c1c; }
    .pp-x svg { width: 1rem; height: 1rem; }
    .pp-lot .form-label { margin-bottom: .2rem; font-size: .74rem; }
    .pp-lot .date-tag { width: 100%; justify-content: flex-start; }
    .pp-hint { font-size: .72rem; color: var(--color-gray-500); line-height: 1.45; }
    .pp-add { display: flex; align-items: center; justify-content: center; gap: .4rem; width: 100%; padding: .65rem; border-radius: .9rem; border: 1.5px dashed #b9c6a8; color: #3d6823; font-weight: 800; font-size: .84rem; background: transparent; }
    .pp-add:hover { background: #f1f8ea; }
    .pp-add svg { width: 1rem; height: 1rem; }
    html.dark .pp-lot { background: #1c2416; border-color: #2b3a1c; }
    html.dark .pp-lot-h b { color: #a5b89a; }
    html.dark .pp-add { border-color: #3f5a2a; color: #a5c97e; }
    html.dark .pp-add:hover { background: #1c2416; }
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
                <div class="q-card"><b>Your season, written by you.</b>A protocol is the plan that you actually follow for your crop from the specific task pinned to when to apply, what to apply, why. Write it based on your experience, Anee's recommendations, or your knowledge of agronomy. You can ask Anee to review it.</div>
                <div class="q-card" id="pbAboutCost"></div>
            </div>
        </div>
    </div>

    <button type="button" class="pb-new mb-3" id="pbNewBtn">New protocol</button>
    <button type="button" class="pb-port mb-4" id="pbPortOpen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9h18M8 2v4M16 2v4M12 12v6M9 15h6"/></svg>
        Port to cropping schedule
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

@include('partials.user-tags')
<div hidden>@include('partials.date-tag', ['id' => 'ppDateSeed', 'empty' => 'Pick a date'])</div>

{{-- Port to a cropping schedule: several lots, each on its own protocol. --}}
<div class="sheet hidden sheet-full" id="ppSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Port to a cropping schedule</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="pp-hint mb-3">A new season built from your protocols: one lot per protocol, each from its own start date. Every task lands on the board on its computed day.</p>
        <div>
            <label class="form-label" for="ppTitle">Name of the cropping schedule</label>
            <input type="text" id="ppTitle" class="form-input" maxlength="255" placeholder="e.g. Wet season 2026 — Apartado">
        </div>
        <div class="mt-3">
            <label class="form-label" for="ppDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ppDesc" class="form-textarea" rows="2" maxlength="5000" placeholder="What this season is about…"></textarea>
        </div>
        <div class="mt-4">
            <span class="form-label">Lots</span>
            <div id="ppLots"></div>
            <button type="button" class="pp-add" id="ppLotAdd">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a lot
            </button>
        </div>
        <div class="mt-4">
            <label class="form-label" for="ppWorkers">Number of workers</label>
            <input type="number" id="ppWorkers" class="form-input" inputmode="numeric" min="1" max="200" step="1" value="1">
            <p class="pp-hint mt-1">How many hands work on a day. With the first choice below, no day is given more activities than this.</p>
        </div>
        <div class="mt-4">
            <span class="form-label">When two lots ask for the same day</span>
            <div class="dt-rows" id="ppAdjust">
                <button type="button" class="dt-row is-on" data-pp-adjust="spread"><span class="dt-row-e">🧮</span><span class="dt-row-body"><b>Auto-adjust the conflicts</b><i>One activity per worker per day — the overflow slides to the next free day. Day zero and the transplant stay put.</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>
                <button type="button" class="dt-row" data-pp-adjust="allow"><span class="dt-row-e">🗓️</span><span class="dt-row-body"><b>Several activities on a day is fine</b><i>Every task keeps the day its protocol says.</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="ppGo">Port</button>
    </div>
</div>

{{-- Which protocol runs a lot --}}
<div class="sheet hidden" id="ppProtoSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which protocol?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="ppProtoList"></div>
</div>

{{-- Which version of the protocol runs the lot --}}
<div class="sheet hidden" id="ppVerSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ppVerTitle">Which version?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="pp-hint mb-2" id="ppVerSay"></p>
        <div class="dt-rows" id="ppVerList"></div>
    </div>
</div>

{{-- Where a lot comes from: typed here, or one of your seasons' lots --}}
<div class="sheet hidden" id="ppLotSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lot?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="ppLotList"></div>
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
        <button type="button" class="dt-row" data-row-act="open"><span class="dt-row-e">📖</span><span class="dt-row-body"><b>Open</b><i>Add, change and reorder the tasks, or ask Anee.</i></span></button>
        <button type="button" class="dt-row" data-row-act="copy"><span class="dt-row-e">📑</span><span class="dt-row-body"><b>Duplicate</b><i>A copy to change without touching this one.</i></span></button>
        <button type="button" class="dt-row" data-row-act="delete"><span class="dt-row-e">🗑️</span><span class="dt-row-body"><b>Delete</b><i>Remove it from your list.</i></span></button>
    </div>
</div>

<script>
(() => {
    const PB_FROM = @json(\App\Support\BackTo::key());
    const PB_FROM_Q = PB_FROM ? '?from=' + PB_FROM : '';
    const U = {
        list: @json(route('pb.list')),
        store: @json(route('pb.store')),
        open: (id) => @json(url('/app/protocol-builder')) + '/' + id,
        // The editor as a page to go to: the list's own origin rides along
        // (?from=, BackTo), so editor -> Back -> Back still ends where the
        // list was opened from. open() stays bare for the API calls.
        page: (id) => @json(url('/app/protocol-builder')) + '/' + id + PB_FROM_Q,
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
        if (o.aiLocked) { cost.innerHTML = "Building and porting cost nothing. Anee's review comes with <b class=\"is-inline\">" + @json(\App\Support\Tier::planName(\App\Support\Tier::farmUnlocksAt('aiAnalyses'))) + "</b> and every plan above it."; return; }
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
                    ${(r.versions || []).length > 1 ? `<small>🗂️ ${r.versions.length} versions · in use: ${esc(r.versionName || '')}</small>` : ''}
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
        if (row) window.location.href = U.page(row.getAttribute('data-id'));
    });
    $id('pbRows').addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const row = e.target.closest('.pb-row[data-id]');
        if (row && e.target === row) { e.preventDefault(); window.location.href = U.page(row.getAttribute('data-id')); }
    });
    $id('pbRowMenu').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-row-act]');
        if (!b || MENU_ID === null) return;
        const act = b.getAttribute('data-row-act');
        const r = ROWS.find((x) => x.id === MENU_ID);
        closeSheet('pbRowMenu');
        if (act === 'open') { window.location.href = U.page(MENU_ID); return; }
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

    /* ------------------------------------------------------------ port to a cropping schedule */
    const PORT_URL = @json(route('pb.port'));
    const LOTS_URL = @json(route('pb.lots'));
    const DATE_TAG = (id, empty) => `<label class="date-tag" data-empty="${esc(empty)}"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="dt-text">${esc(empty)}</span><input type="date" id="${id}"></label>`;
    let PLOTS = [];          // the lots being set up
    let MYLOTS = null;       // the member's lots across seasons, fetched once
    let PICK_FOR = null;     // which lot a chooser is answering
    const uidp = () => 'L' + Math.random().toString(36).slice(2, 8);
    // A date as the calendar says it here — toISOString would give yesterday east of Greenwich.
    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const protoOf = (id) => ROWS.find((r) => r.id === id) || null;
    const isDat = (l) => { const p = protoOf(l.protocolId); return !!p && p.dayType === 'DAT'; };
    const isTree = (l) => { const p = protoOf(l.protocolId); return !!p && p.dayType === 'TREE'; };
    function blankLot() { return { key: uidp(), name: '', size: '', unit: 'hectare', sourceLotId: null, sourceSay: '', sourcePlanted: null, protocolId: null, versionId: null, start: '', transplant: '', treeYears: '', treeMonths: '' }; }
    // A protocol with several versions asks which one runs the lot; one version is taken as it is.
    const versionsOf = (p) => (p && Array.isArray(p.versions)) ? p.versions : [];
    const versionOf = (l) => { const p = protoOf(l.protocolId); return versionsOf(p).find((v) => v.id === l.versionId) || null; };
    const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    function openVersionPick(l) {
        const p = protoOf(l.protocolId); if (!p) return;
        PICK_FOR = l;
        $id('ppVerTitle').textContent = 'Which version?';
        $id('ppVerSay').textContent = `"${p.title}" has ${versionsOf(p).length} versions. Choose the one this lot runs — its tasks, materials and rules go to the season.`;
        $id('ppVerList').innerHTML = versionsOf(p).map((v) => `
            <button type="button" class="dt-row${l.versionId === v.id ? ' is-on' : ''}" data-pp-pick-ver="${v.id}"><span class="dt-row-e">🗂️</span><span class="dt-row-body"><b>${esc(v.name)}</b><i>${v.count} ${v.count === 1 ? 'task' : 'tasks'} · ${v.materials} ${v.materials === 1 ? 'material' : 'materials'}${v.id === p.versionId ? ' · the one in use' : ''}</i></span>${TICK}</button>`).join('');
        openSheet('ppVerSheet');
    }
    /* The trees' age, typed as years + months, stamped as the date they were
       planted — the same turn the Lots form makes (stampTreeDate), so the
       age never goes stale. */
    function treePlantedOf(l) {
        if (l.sourceLotId && l.sourcePlanted) return l.sourcePlanted;
        if (l.treeYears === '' && l.treeMonths === '') return null;
        const months = (Number(l.treeYears || 0) * 12) + Number(l.treeMonths || 0);
        const d = new Date();
        d.setMonth(d.getMonth() - months);
        return ymd(d);
    }
    function sayAge(dateStr) {
        const d = new Date(dateStr + 'T00:00:00'), now = new Date();
        let m = (now.getFullYear() - d.getFullYear()) * 12 + (now.getMonth() - d.getMonth());
        if (now.getDate() < d.getDate()) m--;
        m = Math.max(0, m);
        const y = Math.floor(m / 12), r = m % 12;
        if (y < 1) return `${m} ${m === 1 ? 'month' : 'months'}`;
        return `${y} ${y === 1 ? 'year' : 'years'}${r ? ` ${r} ${r === 1 ? 'month' : 'months'}` : ''}`;
    }
    function lotHtml(l, i) {
        const p = protoOf(l.protocolId);
        const dat = isDat(l);
        const tree = isTree(l);
        const fromLot = tree && l.sourceLotId && l.sourcePlanted;
        const ver = versionOf(l);
        return `
            <div class="pp-lot" data-key="${l.key}">
                <div class="pp-lot-h"><b>Lot ${i + 1}</b>${PLOTS.length > 1 ? `<button type="button" class="pp-x" data-pp-x aria-label="Remove this lot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>` : ''}</div>
                <div>
                    <span class="form-label">From</span>
                    <button type="button" class="crop-tag" data-pp-src><span class="crop-tag-e">${l.sourceLotId ? '🌾' : '✏️'}</span><span class="crop-tag-t${l.sourceLotId ? '' : ' is-none'}">${l.sourceLotId ? esc(l.sourceSay) : 'A new lot, typed here'}</span><svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                </div>
                <div>
                    <label class="form-label" for="ppName_${l.key}">Lot name</label>
                    <input type="text" id="ppName_${l.key}" class="form-input" maxlength="255" placeholder="e.g. Apartado 1" value="${esc(l.name)}" data-pp-name>
                </div>
                <div>
                    <span class="form-label">Protocol</span>
                    <button type="button" class="crop-tag" data-pp-proto><span class="crop-tag-e">${p ? '📋' : '📋'}</span><span class="crop-tag-t${p ? '' : ' is-none'}">${p ? esc(p.title) : 'Choose a protocol'}</span><svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                    ${p ? `<p class="pp-hint mt-1">${esc(p.cropLabel || 'No crop')} · ${esc((DAY_TYPES[p.dayType] || {}).label || p.dayType)} · ${ver ? ver.count : p.count} ${(ver ? ver.count : p.count) === 1 ? 'task' : 'tasks'}</p>` : ''}
                </div>
                ${p && versionsOf(p).length > 1 ? `<div>
                    <span class="form-label">Version</span>
                    <button type="button" class="crop-tag" data-pp-ver><span class="crop-tag-e">🗂️</span><span class="crop-tag-t${ver ? '' : ' is-none'}">${ver ? esc(ver.name) : 'Choose a version'}</span><svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                </div>` : ''}
                <div>
                    <span class="form-label">${dat ? 'Sowing date (DAS 0)' : (p && p.dayType === 'DAP' ? 'Planting date (DAP 0)' : (tree ? 'Program start (DOS 0)' : 'Start date (day 0)'))}</span>
                    ${DATE_TAG('ppStart_' + l.key, 'Pick the start date')}
                    ${tree ? '<p class="pp-hint mt-1">The day the program starts on the trees. Every task counts its days from here.</p>' : ''}
                </div>
                ${tree ? (fromLot ? `<div>
                    <span class="form-label">The trees' age</span>
                    <p class="pp-hint">${esc(sayAge(l.sourcePlanted))} old — taken from the lot (planted ${esc(l.sourcePlanted)}).</p>
                </div>` : `<div>
                    <span class="form-label">How old are the trees?</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block"><span class="pp-hint">Years</span><input type="number" class="form-input" inputmode="numeric" min="0" max="150" step="1" placeholder="e.g. 6" value="${esc(l.treeYears)}" data-pp-years></label>
                        <label class="block"><span class="pp-hint">Months</span><input type="number" class="form-input" inputmode="numeric" min="0" max="11" step="1" placeholder="0" value="${esc(l.treeMonths)}" data-pp-months></label>
                    </div>
                    <p class="pp-hint mt-1">The lot reads its growth stages by the trees' age, as the Lots module does.</p>
                </div>`) : ''}
                ${dat ? `<div>
                    <span class="form-label">Transplant date (DAT 0)</span>
                    ${DATE_TAG('ppTrans_' + l.key, 'Pick the transplant date')}
                    <p class="pp-hint mt-1">The seedbed tasks count from the sowing; everything from the transplant counts from here.</p>
                </div>` : ''}
            </div>`;
    }
    function paintLots() {
        $id('ppLots').innerHTML = PLOTS.map(lotHtml).join('');
        PLOTS.forEach((l) => {
            const st = $id('ppStart_' + l.key); if (st) st.value = l.start || '';
            const tr = $id('ppTrans_' + l.key); if (tr) tr.value = l.transplant || '';
        });
        if (window.smDateTags) window.smDateTags($id('ppLots'));
    }
    const lotOf = (el) => { const k = el.closest('.pp-lot')?.getAttribute('data-key'); return PLOTS.find((l) => l.key === k) || null; };
    $id('ppLots').addEventListener('input', (e) => {
        const l = lotOf(e.target); if (!l) return;
        if (e.target.hasAttribute('data-pp-name')) l.name = e.target.value;
        if (e.target.hasAttribute('data-pp-years')) l.treeYears = e.target.value;
        if (e.target.hasAttribute('data-pp-months')) l.treeMonths = e.target.value;
    });
    $id('ppLots').addEventListener('change', (e) => {
        const l = lotOf(e.target); if (!l) return;
        if (e.target.id === 'ppStart_' + l.key) {
            l.start = e.target.value;
            // A transplant three weeks on is the usual for rice; the farmer can move it.
            if (isDat(l) && !l.transplant && l.start) { const d = new Date(l.start + 'T00:00:00'); d.setDate(d.getDate() + 21); l.transplant = ymd(d); const tr = $id('ppTrans_' + l.key); if (tr) { tr.value = l.transplant; if (window.smDateTags) window.smDateTags($id('ppLots')); } }
        }
        if (e.target.id === 'ppTrans_' + l.key) l.transplant = e.target.value;
    });
    $id('ppLots').addEventListener('click', async (e) => {
        const l = lotOf(e.target); if (!l) return;
        if (e.target.closest('[data-pp-x]')) { PLOTS = PLOTS.filter((x) => x !== l); paintLots(); return; }
        if (e.target.closest('[data-pp-proto]')) {
            PICK_FOR = l;
            $id('ppProtoList').innerHTML = ROWS.length ? ROWS.map((r) => `
                <button type="button" class="dt-row${l.protocolId === r.id ? ' is-on' : ''}" data-pp-pick-proto="${r.id}"><span class="dt-row-e">${esc(r.cropIcon || '📋')}</span><span class="dt-row-body"><b>${esc(r.title)}</b><i>${esc(r.cropLabel || 'No crop')} · ${esc((DAY_TYPES[r.dayType] || {}).label || r.dayType)} · ${r.count} ${r.count === 1 ? 'task' : 'tasks'}${versionsOf(r).length > 1 ? ` · ${versionsOf(r).length} versions` : ''}</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('')
                : '<p class="text-sm text-gray-400 py-4 text-center">No protocol yet — write one first.</p>';
            openSheet('ppProtoSheet');
            return;
        }
        if (e.target.closest('[data-pp-ver]')) { openVersionPick(l); return; }
        if (e.target.closest('[data-pp-src]')) {
            PICK_FOR = l;
            $id('ppLotList').innerHTML = '<p class="text-sm text-gray-400 py-4 text-center">Getting your lots…</p>';
            openSheet('ppLotSheet');
            if (!MYLOTS) { try { MYLOTS = (await api(LOTS_URL, { method: 'GET' })).data.lots || []; } catch (err) { MYLOTS = []; toast(err.message, 'error'); } }
            $id('ppLotList').innerHTML = `
                <button type="button" class="dt-row${l.sourceLotId ? '' : ' is-on'}" data-pp-pick-lot="0"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>A new lot, typed here</b><i>Name it above; 1 hectare unless you change it later in the Lots module.</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`
                + MYLOTS.map((m) => `
                <button type="button" class="dt-row${l.sourceLotId === m.id ? ' is-on' : ''}" data-pp-pick-lot="${m.id}"><span class="dt-row-e">${esc(m.cropIcon)}</span><span class="dt-row-body"><b>${esc(m.name)}</b><i>${esc(m.schedule)} · ${esc(m.cropLabel || 'No crop')}${m.variety ? ' · ' + esc(m.variety) : ''} · ${m.size} ${esc(m.unit)}</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('');
        }
    });
    $id('ppProtoList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-pp-pick-proto]'); if (!r || !PICK_FOR) return;
        const was = PICK_FOR.protocolId;
        PICK_FOR.protocolId = +r.getAttribute('data-pp-pick-proto');
        if (!isDat(PICK_FOR)) PICK_FOR.transplant = '';
        const p = protoOf(PICK_FOR.protocolId);
        const vs = versionsOf(p);
        if (was !== PICK_FOR.protocolId) PICK_FOR.versionId = null;
        // One version: taken without asking. Several: the lot waits for the answer.
        if (vs.length <= 1) PICK_FOR.versionId = vs.length ? vs[0].id : null;
        closeSheet('ppProtoSheet'); paintLots();
        if (vs.length > 1 && !PICK_FOR.versionId) { const l = PICK_FOR; setTimeout(() => openVersionPick(l), 260); }
    });
    $id('ppVerList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-pp-pick-ver]'); if (!r || !PICK_FOR) return;
        PICK_FOR.versionId = +r.getAttribute('data-pp-pick-ver');
        closeSheet('ppVerSheet'); paintLots();
        const card = $id('ppLots').querySelector(`.pp-lot[data-key="${PICK_FOR.key}"] [data-pp-ver]`);
        if (card) { card.style.transition = 'box-shadow .28s cubic-bezier(.22,1,.36,1)'; card.style.boxShadow = '0 0 0 3px rgba(107,159,61,.35)'; setTimeout(() => { card.style.boxShadow = ''; }, 900); }
    });
    $id('ppLotList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-pp-pick-lot]'); if (!r || !PICK_FOR) return;
        const id = +r.getAttribute('data-pp-pick-lot');
        if (!id) { PICK_FOR.sourceLotId = null; PICK_FOR.sourceSay = ''; PICK_FOR.sourcePlanted = null; }
        else { const m = (MYLOTS || []).find((x) => x.id === id); if (m) { PICK_FOR.sourceLotId = m.id; PICK_FOR.sourceSay = `${m.name} · ${m.schedule}`; PICK_FOR.sourcePlanted = m.treePlantedAt || null; PICK_FOR.name = m.name; PICK_FOR.size = m.size; PICK_FOR.unit = m.unit; } }
        closeSheet('ppLotSheet'); paintLots();
    });
    $id('ppLotAdd').addEventListener('click', () => { PLOTS.push(blankLot()); paintLots(); $id('ppLots').lastElementChild?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); });
    $id('ppAdjust').addEventListener('click', (e) => { const r = e.target.closest('[data-pp-adjust]'); if (!r) return; $id('ppAdjust').querySelectorAll('[data-pp-adjust]').forEach((x) => x.classList.toggle('is-on', x === r)); });
    $id('pbPortOpen').addEventListener('click', () => {
        if (!ROWS.length) { toast('Write a protocol first — the port builds a season from one.', 'error'); return; }
        if (!PLOTS.length) PLOTS = [blankLot()];
        paintLots();
        openSheet('ppSheet');
    });
    $id('ppGo').addEventListener('click', async () => {
        const title = $id('ppTitle').value.trim();
        if (!title) { toast('Name the cropping schedule.', 'error'); $id('ppTitle').focus(); return; }
        for (const [i, l] of PLOTS.entries()) {
            if (!l.name.trim()) { toast(`Name lot ${i + 1}.`, 'error'); $id('ppName_' + l.key)?.focus(); return; }
            if (!l.protocolId) { toast(`Choose a protocol for lot ${i + 1}.`, 'error'); return; }
            if (versionsOf(protoOf(l.protocolId)).length > 1 && !versionOf(l)) { toast(`Choose which version lot ${i + 1} runs.`, 'error'); openVersionPick(l); return; }
            if (!l.start) { toast(`Pick the start date for lot ${i + 1}.`, 'error'); return; }
            if (isDat(l) && !l.transplant) { toast(`Pick the transplant date for lot ${i + 1}.`, 'error'); return; }
            if (isTree(l) && !treePlantedOf(l)) { toast(`Say how old the trees are on lot ${i + 1}.`, 'error'); return; }
        }
        const btn = $id('ppGo'); btn.disabled = true; const was = btn.textContent; btn.textContent = 'Porting… this takes a moment';
        try {
            const res = await api(PORT_URL, { method: 'POST', body: {
                title, description: $id('ppDesc').value.trim(),
                workers: Math.max(1, parseInt($id('ppWorkers').value, 10) || 1),
                adjust: $id('ppAdjust').querySelector('.is-on')?.getAttribute('data-pp-adjust') || 'spread',
                lots: PLOTS.map((l) => ({ name: l.name.trim(), size: l.size === '' ? null : l.size, unit: l.unit || 'hectare', sourceLotId: l.sourceLotId, protocolId: l.protocolId, versionId: l.versionId || null, startDate: l.start, transplantDate: isDat(l) ? l.transplant : null, treePlantedAt: isTree(l) ? treePlantedOf(l) : null })),
            } });
            toast(res.message);
            window.location.href = res.data.redirect;
        } catch (err) { if (!err.tierLock) toast(err.message, 'error'); btn.disabled = false; btn.textContent = was; }
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
            window.location.href = res.data.url + (PB_FROM ? (String(res.data.url).includes('?') ? '&' : '?') + 'from=' + PB_FROM : '');
        } catch (err) { toast(err.message, 'error'); btn.disabled = false; }
    });

    const boot = () => { paintCost(); load(); };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
