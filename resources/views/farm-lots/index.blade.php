@extends('layouts.app')
@section('title', 'Global Lots')
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Global Lots')
@section('page-subtitle', 'Your lots, answered once for every analysis')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

{{-- Global Lots (2026-10-16, the owner): every lot of the farm with where it
     is, its size and its soil, water and last season answers, kept here and
     picked by every analysis (partials/farm-lots, App\Support\FarmLots).
     A lot's answers are to be checked every six months: an old lot wears a
     clock, the bell rings for it (lots:remind), and "Still correct" starts
     the six months again. ?lot=ID opens that lot (the bell's link). --}}
@push('head')
<style>
    .gl-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .gl-hero img { flex: none; width: 3.2rem; height: 3.2rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); animation: glBob 4s ease-in-out infinite; }
    @keyframes glBob { 50% { transform: translateY(-3px) rotate(-2deg); } }
    .gl-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
    .gl-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
    .gl-old { display: flex; align-items: flex-start; gap: .6rem; margin-top: 1rem; padding: .8rem .95rem; border-radius: 1.1rem; font-size: .86rem; line-height: 1.5;
        color: #78350f; background: #fffbeb; border: 1px solid #fde68a; animation: glRise .34s cubic-bezier(.22,1,.36,1) both; }
    .gl-old[hidden] { display: none; }
    .gl-old svg { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .1rem; color: #d97706; }
    .gl-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin: 1rem 0; }
    .gl-search { flex: 1 1 14rem; position: relative; }
    .gl-search input { width: 100%; padding-left: 2.3rem; }
    .gl-search svg { position: absolute; left: .75rem; top: 50%; translate: 0 -50%; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .gl-grid { display: grid; gap: .8rem; }
    @media (min-width: 768px) { .gl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .gl-item { display: grid; gap: .55rem; padding: .55rem; border-radius: 1.3rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: glRise .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 40ms); transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .gl-item.is-old { border-color: #fcd34d; }
    /* A copy just made: it rises in with a short yellow glow, so the eye finds it. */
    .gl-item.is-new { animation: glRise .34s cubic-bezier(.22,1,.36,1) both, glNew 1.6s cubic-bezier(.22,1,.36,1) .2s both; }
    @keyframes glNew { 0% { box-shadow: 0 0 0 0 rgb(245 197 24 / 0); border-color: #f5c518; } 30% { box-shadow: 0 0 0 5px rgb(245 197 24 / .35); border-color: #f5c518; } 100% { box-shadow: 0 0 0 0 rgb(245 197 24 / 0); } }
    .gl-acts .gl-dup { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
    .gl-acts .gl-dup svg { width: .95rem; height: .95rem; flex: none; }
    .gl-acts .gl-dup.is-busy { opacity: .55; pointer-events: none; }
    .gl-item .fl-card { border-color: transparent; animation: none; }
    .gl-item .fl-card .fl-ck { display: none; }
    .gl-item .fl-ans { padding: 0 .1rem; }
    @keyframes glRise { from { opacity: 0; transform: translateY(8px); } }
    .gl-meta { padding: 0 .35rem; font-size: .74rem; color: var(--color-gray-500); }
    .gl-acts { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; padding: 0 0 .2rem; }
    @media (min-width: 420px) { .gl-acts { gap: .4rem; padding: 0 .25rem .2rem; } }
    .gl-acts .btn { padding: .4rem .62rem; font-size: .78rem; }
    @media (min-width: 420px) { .gl-acts .btn { padding: .4rem .8rem; font-size: .8rem; } }
    /* Delete is an icon beside the map buttons, and the labels are short so a row holds them (2026-10-16, the owner). */
    .gl-acts .gl-del { flex: none; width: 2.1rem; height: 2.1rem; padding: 0; display: inline-grid; place-items: center; border-radius: .8rem; color: #b91c1c; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .gl-acts .gl-del:hover { background: #fee2e2; }
    .gl-acts .gl-del svg { width: 1.05rem; height: 1.05rem; }
    html.dark .gl-acts .gl-del { color: #fca5a5; }
    html.dark .gl-acts .gl-del:hover { background: #3a1414; }
    /* The lot on the map (2026-10-16, the owner): its pin and its boundary. */
    .gl-acts .gl-map { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
    .gl-acts .gl-map svg { width: .95rem; height: .95rem; flex: none; }
    .gl-acts .gl-map.is-set { color: var(--color-brand-700); border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .gl-onmap { display: flex; flex-wrap: wrap; gap: .35rem; padding: 0 .35rem; }
    .gl-onmap span { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #3d5226; background: #eef5e6; }
    .gl-onmap a { font-size: .72rem; font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; align-self: center; }
    html.dark .gl-onmap span { color: #cfe6b8; background: #22301a; }
    .gl-empty { padding: 2rem 1rem; text-align: center; border-radius: 1.2rem; border: 1px dashed var(--color-gray-300); color: var(--color-gray-500); font-size: .9rem; line-height: 1.55; }
    .gl-empty b { display: block; margin-bottom: .2rem; color: var(--color-gray-800); }
    html.dark .gl-old { color: #fde68a; background: #2a2010; border-color: #5c4413; }
    html.dark .gl-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .gl-item.is-old { border-color: #6b4f12; }
    @media (prefers-reduced-motion: reduce) { .gl-hero img, .gl-item, .gl-item.is-new, .gl-old { animation: none; } .gl-item { transition: none; } }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="gl-hero">
        <img src="{{ asset('images/treasure-map.png') }}" alt="">
        <div><h2>Global Lots</h2><p>Each of your lots once: where it is, its size, and its soil, water and last season. Every analysis then only asks which lot, and reads the rest from here. Check each lot every six months.</p></div>
    </div>
    <div class="gl-old" id="glOld" hidden role="status">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7.5V12l3 2"/></svg>
        <span id="glOldSay"></span>
    </div>
    <div class="gl-bar">
        <label class="gl-search"><span class="sr-only">Find a lot</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <input type="search" id="glQ" class="form-input" placeholder="Find a lot or a place" autocomplete="off">
        </label>
        <button type="button" class="btn btn-primary" id="glAdd">+ Add a lot</button>
    </div>
    <div class="gl-grid" id="glGrid"><div class="gl-empty">Loading your lots…</div></div>
</div>

@include('partials.farm-lots')
@include('partials.lot-map')

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const DEL = @json(url('/app/farm-lots'));
    let ALL = [];
    const L = () => window.aneeLots;

    function paint() {
        const q = $('glQ').value.trim().toLowerCase();
        const list = ALL.filter((l) => !q || (l.name + ' ' + (l.place || '') + ' ' + (l.cropLabel || '')).toLowerCase().includes(q));
        const old = ALL.filter((l) => l.stale);
        $('glOld').hidden = !old.length;
        $('glOldSay').innerHTML = old.length ? '<b>' + (old.length === 1 ? '1 lot is' : old.length + ' lots are') + ' due for a check.</b> '
            + (old.length === 1 ? 'Its answers are' : 'Their answers are') + ' over six months old. Open each one, change what is different now, or tap Still correct.' : '';
        if (!ALL.length) {
            $('glGrid').innerHTML = '<div class="gl-empty"><b>No lots yet</b>Add your first lot: where it is, its size, and its soil and water. You answer it once, and every analysis reads it.</div>';
            return;
        }
        if (!list.length) { $('glGrid').innerHTML = '<div class="gl-empty">No lot by that name or place.</div>'; return; }
        $('glGrid').innerHTML = list.map((l, i) => '<div class="gl-item' + (l.stale ? ' is-old' : '') + (l.id === NEW_ID ? ' is-new' : '') + '" style="--i:' + Math.min(i, 10) + '" data-id="' + l.id + '">'
            + L().card(l, false, 0)
            // Preview answers and Edit answers (2026-10-19, the owner): each opens a sheet, no card opens here.
            + L().ansButtons('data-act="prev"', 'data-act="answers"')
            + '<p class="gl-meta">' + (l.stale ? 'Last checked ' + esc(l.updated) + '. Due for a check.' : 'Last checked ' + esc(l.updated) + '. Next check by ' + esc(l.staleOn) + '.') + '</p>'
            + onMap(l)
            + '<div class="gl-acts"><button type="button" class="btn btn-white" data-act="edit">Edit</button>'
            + '<button type="button" class="btn btn-white gl-dup" data-act="dup" aria-label="Duplicate ' + esc(l.name) + '">' + COPY + 'Duplicate</button>'
            + (l.stale ? '<button type="button" class="btn btn-white" data-act="fine">Still correct</button>' : '')
            + '<button type="button" class="btn btn-white gl-map' + (l.pin ? ' is-set' : '') + '" data-act="pin">' + PIN + (l.pin ? 'Move pin' : 'Set pin') + '</button>'
            + '<button type="button" class="btn btn-white gl-map' + (l.boundary ? ' is-set' : '') + '" data-act="draw">' + SHAPE + (l.boundary ? 'Edit boundary' : 'Draw boundary') + '</button>'
            + '<button type="button" class="btn btn-ghost gl-del" data-act="del" aria-label="Delete ' + esc(l.name) + '" title="Delete this lot">' + TRASH + '</button></div></div>').join('');
    }
    const COPY = '<svg fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" aria-hidden="true"><rect x="8.5" y="8.5" width="11" height="11" rx="2.4"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5V6.6a2.1 2.1 0 00-2.1-2.1H6.6a2.1 2.1 0 00-2.1 2.1v6.8a2.1 2.1 0 002.1 2.1h1.9"/></svg>';
    let NEW_ID = 0;
    const TRASH = '<svg fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7h15M10 11v6m4-6v6M6.5 7l.9 12a2 2 0 002 1.8h5.2a2 2 0 002-1.8l.9-12M9.5 7V4.8c0-.5.4-.8.8-.8h3.4c.4 0 .8.3.8.8V7"/></svg>';
    const PIN = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M12 21s-6.5-6.2-6.5-11.2a6.5 6.5 0 0113 0C18.5 14.8 12 21 12 21z"/><circle cx="12" cy="9.8" r="2.3"/></svg>';
    const SHAPE = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M5 7l8-3 6 6-3 9-10-2z"/><circle cx="5" cy="7" r="1.6" fill="currentColor"/><circle cx="13" cy="4" r="1.6" fill="currentColor"/><circle cx="19" cy="10" r="1.6" fill="currentColor"/><circle cx="16" cy="19" r="1.6" fill="currentColor"/><circle cx="6" cy="17" r="1.6" fill="currentColor"/></svg>';
    // What the lot has on the map: its pin (with a link to Google Maps) and the size its boundary encloses.
    const onMap = (l) => {
        const bits = [];
        if (l.pin) bits.push('<span>📍 Pinned</span><a href="https://www.google.com/maps/search/?api=1&query=' + l.pin.lat + ',' + l.pin.lng + '" target="_blank" rel="noopener">Open in Google Maps</a>');
        if (l.boundary) bits.push('<span>⬠ Boundary, ' + esc(Number(l.boundaryHa || 0).toLocaleString(undefined, { maximumFractionDigits: (l.boundaryHa || 0) < 10 ? 2 : 1 })) + ' ha drawn</span>');
        return bits.length ? '<div class="gl-onmap">' + bits.join('') + '</div>' : '';
    };
    async function load(fresh) {
        ALL = await L().rows(fresh);
        paint();
    }
    const lotOf = (el) => ALL.find((l) => l.id === Number((el.closest('[data-id]') || {}).dataset?.id));

    $('glGrid').addEventListener('click', async (e) => {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        const lot = lotOf(item);
        if (!lot) return;
        // A tap on the lot's card previews its answers, like the Preview answers button.
        const act = e.target.closest('[data-act]')?.dataset.act || (e.target.closest('.fl-card') ? 'prev' : null);
        try {
            if (act === 'prev') { await L().review(lot, {}); return; }
            if (act === 'answers') { await L().editAnswers(lot, {}); return; }
            if (act === 'edit') await L().edit(lot, { ask: false });
            else if (act === 'pin' || act === 'draw') {
                if (!window.aneeLotMap) return;
                const row = await (act === 'pin' ? window.aneeLotMap.pin(lot) : window.aneeLotMap.draw(lot));
                if (row) await load(true);
            }
            else if (act === 'fine') await L().confirm(lot);
            else if (act === 'dup') {
                const b = e.target.closest('[data-act]');
                b.classList.add('is-busy');
                try {
                    const r = await window.api(DEL + '/' + lot.id + '/duplicate', { method: 'POST' });
                    NEW_ID = Number((r.data && r.data.row && r.data.row.id) || 0);
                    await load(true);
                    const el = NEW_ID && $('glGrid').querySelector('[data-id="' + NEW_ID + '"]');
                    if (el) el.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                    window.toast?.(r.message, 'success');
                    setTimeout(() => { NEW_ID = 0; }, 2500);
                } finally { b.classList.remove('is-busy'); }
            }
            else if (act === 'del') {
                const ok = await window.confirmAction({ title: 'Delete ' + lot.name + '?', message: 'Its place, size and answers go. Analyses you already ran keep what they read.', confirmText: 'Delete the lot' });
                if (!ok) return;
                const r = await window.api(DEL + '/' + lot.id, { method: 'DELETE' });
                window.toast?.(r.message, 'success');
                await load(true);
            }
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
    $('glAdd').addEventListener('click', () => L().edit(null, {}));
    $('glQ').addEventListener('input', paint);
    window.addEventListener('aneelots:changed', () => load(true));

    const start = async () => {
        await load();
        // The bell's link: open that lot.
        const want = Number(new URLSearchParams(location.search).get('lot'));
        const lot = want && ALL.find((l) => l.id === want);
        if (lot) setTimeout(() => L().edit(lot, { ask: false }), 300);
    };
    if (window.api && window.aneeLots) start(); else window.addEventListener('load', start, { once: true });
})();
</script>
@endpush
@endsection
