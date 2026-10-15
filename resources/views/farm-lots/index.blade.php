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
    .gl-item .fl-card { border-color: transparent; animation: none; }
    .gl-item .fl-card .fl-ck { display: none; }
    @keyframes glRise { from { opacity: 0; transform: translateY(8px); } }
    .gl-meta { padding: 0 .35rem; font-size: .74rem; color: var(--color-gray-500); }
    .gl-acts { display: flex; flex-wrap: wrap; gap: .4rem; padding: 0 .25rem .2rem; }
    .gl-acts .btn { padding: .4rem .8rem; font-size: .8rem; }
    .gl-acts .gl-del { margin-left: auto; color: #b91c1c; }
    .gl-empty { padding: 2rem 1rem; text-align: center; border-radius: 1.2rem; border: 1px dashed var(--color-gray-300); color: var(--color-gray-500); font-size: .9rem; line-height: 1.55; }
    .gl-empty b { display: block; margin-bottom: .2rem; color: var(--color-gray-800); }
    html.dark .gl-old { color: #fde68a; background: #2a2010; border-color: #5c4413; }
    html.dark .gl-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .gl-item.is-old { border-color: #6b4f12; }
    @media (prefers-reduced-motion: reduce) { .gl-hero img, .gl-item, .gl-old { animation: none; } .gl-item { transition: none; } }
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
        $('glGrid').innerHTML = list.map((l, i) => '<div class="gl-item' + (l.stale ? ' is-old' : '') + '" style="--i:' + Math.min(i, 10) + '" data-id="' + l.id + '">'
            + L().card(l, false, 0)
            + '<p class="gl-meta">' + (l.stale ? 'Last checked ' + esc(l.updated) + '. Due for a check.' : 'Last checked ' + esc(l.updated) + '. Next check by ' + esc(l.staleOn) + '.') + '</p>'
            + '<div class="gl-acts"><button type="button" class="btn btn-white" data-act="edit">Edit</button>'
            + (l.stale ? '<button type="button" class="btn btn-white" data-act="fine">Still correct</button>' : '')
            + '<button type="button" class="btn btn-ghost gl-del" data-act="del">Delete</button></div></div>').join('');
    }
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
        const act = e.target.closest('[data-act]')?.dataset.act || (e.target.closest('.fl-card') ? 'edit' : null);
        try {
            if (act === 'edit') await L().edit(lot, { ask: false });
            else if (act === 'fine') await L().confirm(lot);
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
