@extends('layouts.app')
@section('title', 'Global Workers')
@section('page-title', 'Global Workers')
@section('page-subtitle', 'Your crew, for every season')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

{{-- Global Workers (2026-10-12): the farm's own roster above the seasons.
     Each person once (name, contact, usual pay, skills, weekdays off), added
     to any season in a tap, where they become that season's card. See
     App\Http\Controllers\GlobalWorkerController. --}}
@push('head')
<style>
    .gw-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .gw-hero img { flex: none; width: 3.2rem; height: 3.2rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); animation: gwBob 4s ease-in-out infinite; }
    @keyframes gwBob { 50% { transform: translateY(-3px) rotate(-2deg); } }
    .gw-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
    .gw-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
    .gw-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin: 1rem 0; }
    .gw-search { flex: 1 1 14rem; position: relative; }
    .gw-search input { width: 100%; padding-left: 2.3rem; }
    .gw-search svg { position: absolute; left: .75rem; top: 50%; translate: 0 -50%; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .gw-grid { display: grid; gap: .8rem; }
    @media (min-width: 768px) { .gw-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .gw-card { padding: .95rem 1rem; border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: gwRise .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 40ms); transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .gw-card:hover { border-color: var(--color-brand-300, #a8cc7e); box-shadow: 0 16px 32px -26px rgb(15 23 42 / .5); }
    @keyframes gwRise { from { opacity: 0; transform: translateY(8px); } }
    .gw-top { display: flex; align-items: center; gap: .7rem; }
    .gw-av { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800; color: #fff; background: hsl(var(--h) 45% 38%); }
    .gw-who { flex: 1; min-width: 0; }
    .gw-who b { display: block; font-weight: 800; color: var(--color-gray-900); }
    .gw-who small { display: block; font-size: .78rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gw-pay { flex: none; text-align: right; font-size: .8rem; font-weight: 800; color: var(--color-brand-700); }
    .gw-pay small { display: block; font-size: .66rem; font-weight: 700; color: var(--color-gray-500); }
    .gw-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
    .gw-chip { padding: .16rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-gray-100); }
    .gw-chip.is-off { color: #8a5a00; background: #fff4d6; }
    .gw-chip.is-season { color: var(--color-brand-700); background: #eaf4dc; text-decoration: none; }
    .gw-acts { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .75rem; }
    .gw-acts .btn { padding: .35rem .7rem; font-size: .78rem; }
    .gw-empty { padding: 2rem 1rem; text-align: center; border-radius: 1.2rem; border: 1px dashed var(--color-gray-300); color: var(--color-gray-500); }
    .gw-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .gw-pill { padding: .4rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .gw-pill[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); }
    .gw-seasons { display: grid; gap: .4rem; }
    .gw-srow { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .6rem .7rem; border-radius: .9rem; text-align: left; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .gw-srow b { flex: 1; font-weight: 700; color: var(--color-gray-900); }
    .gw-srow em { font-style: normal; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); }
    .gw-srow[disabled] { opacity: .6; cursor: default; }
    html.dark .gw-card, html.dark .gw-srow { background: #1a1d23; border-color: #2a2f38; }
    html.dark .gw-who b, html.dark .gw-srow b { color: #e5e7eb; }
    html.dark .gw-chip { background: #20242c; color: #cbd5e1; } html.dark .gw-chip.is-season { background: #1c2616; color: #c5e09f; } html.dark .gw-chip.is-off { background: #2a2210; color: #f3d9a4; }
    @media (prefers-reduced-motion: reduce) { .gw-hero img, .gw-card { animation: none; } .gw-card, .gw-pill { transition: none; } }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="gw-hero">
        <img src="{{ asset('images/friends.png') }}" alt="">
        <div><h2>Global Workers</h2><p>Your crew in one list. Add someone once, with their pay, skills and the weekdays they do not work, then put them on any season in a tap. Each season keeps its own record of their work and pay.</p></div>
    </div>
    <div class="gw-bar">
        <label class="gw-search"><span class="sr-only">Find a worker</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <input type="search" id="gwQ" class="form-input" placeholder="Find a worker" autocomplete="off">
        </label>
        <button type="button" class="btn btn-primary" id="gwAdd">+ Add a worker</button>
    </div>
    <div class="gw-grid" id="gwGrid"><div class="gw-empty">Loading your workers…</div></div>
</div>

{{-- Add or edit a worker. --}}
<div class="sheet hidden" id="gwSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gwSheetTitle">Add a worker</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div><label class="form-label" for="gwName">Name <span class="text-red-500">*</span></label><input type="text" id="gwName" maxlength="255" class="form-input" placeholder="e.g. Juan Dela Cruz"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label" for="gwEmail">Email <span class="text-gray-400 font-normal">(optional)</span></label><input type="email" id="gwEmail" maxlength="255" class="form-input"></div>
            <div><label class="form-label" for="gwPhone">Phone <span class="text-gray-400 font-normal">(optional)</span></label><input type="tel" id="gwPhone" maxlength="32" class="form-input"></div>
        </div>
        <div><label class="form-label" for="gwCost">Usual pay per half day</label>
            <div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">{{ \App\Support\Region::symbol() }}</span>
                <input type="number" id="gwCost" min="0" step="0.01" class="form-input pl-9!" placeholder="0.00"></div>
            <p class="form-hint">A season starts from this; each season can pay its own.</p></div>
        <div><span class="form-label">Skills</span><div class="gw-pills" id="gwSkills">
            @foreach ($skills as $k => $label)<button type="button" class="gw-pill" data-skill="{{ $k }}" aria-pressed="false">{{ $label }}</button>@endforeach
        </div></div>
        <div><span class="form-label">Weekdays they do not work</span><div class="gw-pills" id="gwOff">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $d)<button type="button" class="gw-pill" data-dow="{{ $i }}" aria-pressed="false">{{ $d }}</button>@endforeach
        </div><p class="form-hint">A change here reaches every season they are in, so the plan does not give them work on these days.</p></div>
        <div><label class="form-label" for="gwNotes">Notes</label><textarea id="gwNotes" rows="2" maxlength="2000" class="form-input"></textarea></div>
    </div>
    <div class="sheet-footer"><button type="button" class="btn btn-primary w-full" id="gwSave">Save worker</button></div>
</div>

{{-- Onto a season. --}}
<div class="sheet hidden" id="gwSeasonSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gwSeasonTitle">Add to a season</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body"><div class="gw-seasons" id="gwSeasonList"></div></div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const U = {
        list: @json(route('workers.global.list')), store: @json(route('workers.global.store')),
        one: (id) => @json(url('/app/workers')) + '/' + id, season: (id) => @json(url('/app/workers')) + '/' + id + '/season',
        seasonWorkers: (id) => @json(route('sm.workers')) + '?id=' + id,
    };
    const SEASONS = @json($seasons->map(fn ($s) => ['id' => (int) $s->id, 'title' => $s->title])->values());
    const SKILLS = @json($skills);
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MONEY = @json(\App\Support\Region::symbol());
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = (id) => document.getElementById(id);
    let ROWS = [], editing = null, seasonFor = null;

    const card = (g, i) => `<div class="gw-card" style="--i:${i}">
        <div class="gw-top"><span class="gw-av" style="--h:${(g.id * 137) % 360}">${esc((g.workerName || '?').trim().charAt(0).toUpperCase())}</span>
            <span class="gw-who"><b>${esc(g.workerName)}</b><small>${esc([g.phone, g.email].filter(Boolean).join(' · ') || 'No contact yet')}</small></span>
            <span class="gw-pay">${g.costPerHalfDay ? esc(MONEY + Number(g.costPerHalfDay).toLocaleString()) : 'No pay set'}<small>a half day</small></span></div>
        <div class="gw-chips">${(g.skills || []).map((k) => `<span class="gw-chip">${esc(SKILLS[k] || k)}</span>`).join('')}${(g.offWeekdays || []).map((d) => `<span class="gw-chip is-off">Off ${DOW[d]}</span>`).join('')}</div>
        <div class="gw-chips">${(g.seasons || []).length ? g.seasons.map((s) => `<a class="gw-chip is-season" href="${U.seasonWorkers(s.id)}">${esc(s.title)}</a>`).join('') : '<span class="gw-chip">Not on a season yet</span>'}</div>
        <div class="gw-acts"><button type="button" class="btn btn-primary" data-act="season" data-id="${g.id}">Add to a season</button>
            <button type="button" class="btn btn-white" data-act="edit" data-id="${g.id}">Edit</button>
            <button type="button" class="btn btn-white" data-act="remove" data-id="${g.id}">Remove</button></div></div>`;
    const paint = () => {
        const q = $('gwQ').value.trim().toLowerCase();
        const rows = ROWS.filter((g) => !q || (g.workerName + ' ' + (g.phone || '') + ' ' + (g.email || '')).toLowerCase().includes(q));
        $('gwGrid').innerHTML = rows.length ? rows.map(card).join('') : `<div class="gw-empty">${ROWS.length ? 'No worker by that name.' : 'No workers yet. Add your crew once and put them on any season.'}</div>`;
    };
    const load = async () => { try { ROWS = (await window.api(U.list)).data || []; } catch (e) { ROWS = []; } paint(); };
    $('gwQ').addEventListener('input', paint);

    const pills = (box, attr, on) => box.querySelectorAll('[' + attr + ']').forEach((b) => b.setAttribute('aria-pressed', String(on.includes(isNaN(b.getAttribute(attr)) ? b.getAttribute(attr) : Number(b.getAttribute(attr))))));
    ['gwSkills', 'gwOff'].forEach((id) => $(id).addEventListener('click', (e) => { const b = e.target.closest('.gw-pill'); if (b) b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true')); }));
    const openEdit = (g) => {
        editing = g;
        $('gwSheetTitle').textContent = g ? 'Edit ' + g.workerName : 'Add a worker';
        $('gwName').value = g?.workerName || ''; $('gwEmail').value = g?.email || ''; $('gwPhone').value = g?.phone || '';
        $('gwCost').value = g?.costPerHalfDay || ''; $('gwNotes').value = g?.notes || '';
        pills($('gwSkills'), 'data-skill', g?.skills || []); pills($('gwOff'), 'data-dow', g?.offWeekdays || []);
        window.openSheet('gwSheet');
    };
    $('gwAdd').addEventListener('click', () => openEdit(null));
    $('gwSave').addEventListener('click', async () => {
        const body = { workerName: $('gwName').value.trim(), email: $('gwEmail').value.trim(), phone: $('gwPhone').value.trim(), costPerHalfDay: $('gwCost').value, notes: $('gwNotes').value,
            skills: [...$('gwSkills').querySelectorAll('[aria-pressed="true"]')].map((b) => b.dataset.skill), offWeekdays: [...$('gwOff').querySelectorAll('[aria-pressed="true"]')].map((b) => Number(b.dataset.dow)) };
        if (!body.workerName) return window.toast?.('Give the worker a name.', 'error');
        $('gwSave').disabled = true;
        try {
            const r = await window.api(editing ? U.one(editing.id) : U.store, { method: editing ? 'PUT' : 'POST', body });
            window.closeSheet('gwSheet'); window.toast?.(r.message, 'success'); await load();
        } catch (e) { window.toast?.(e.message, 'error'); } finally { $('gwSave').disabled = false; }
    });
    const openSeasons = (g) => {
        seasonFor = g;
        $('gwSeasonTitle').textContent = 'Add ' + g.workerName + ' to a season';
        const inIds = (g.seasons || []).map((s) => s.id);
        $('gwSeasonList').innerHTML = SEASONS.length ? SEASONS.map((s) => `<button type="button" class="gw-srow" data-season="${s.id}" ${inIds.includes(s.id) ? 'disabled' : ''}><b>${esc(s.title)}</b><em>${inIds.includes(s.id) ? 'Already in' : 'Add'}</em></button>`).join('')
            : '<p class="text-sm text-gray-500">No seasons yet. Make a season first, then add your workers to it.</p>';
        window.openSheet('gwSeasonSheet');
    };
    $('gwSeasonList').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-season]');
        if (!b || b.disabled || !seasonFor) return;
        b.disabled = true;
        try {
            const r = await window.api(U.season(seasonFor.id), { method: 'POST', body: { scheduleId: Number(b.dataset.season) } });
            window.closeSheet('gwSeasonSheet'); window.toast?.(r.message, 'success'); await load();
        } catch (err) { window.toast?.(err.message, 'error'); b.disabled = false; }
    });
    $('gwGrid').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-act]');
        if (!b) return;
        const g = ROWS.find((x) => String(x.id) === b.dataset.id);
        if (!g) return;
        if (b.dataset.act === 'edit') openEdit(g);
        else if (b.dataset.act === 'season') openSeasons(g);
        else if (b.dataset.act === 'remove') {
            if (!confirm('Take ' + g.workerName + ' off your roster? The seasons they worked keep their record.')) return;
            try { const r = await window.api(U.one(g.id), { method: 'DELETE' }); window.toast?.(r.message, 'success'); await load(); } catch (err) { window.toast?.(err.message, 'error'); }
        }
    });
    load();
})();
</script>
@endpush
