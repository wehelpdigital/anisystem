@extends('layouts.app')

@section('title', 'Trash')
{{-- A tool page: no bottom tab bar on a phone and no footer, as the other Global tools. --}}
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Trash')
@section('page-subtitle', 'Deleted things wait here for 30 days')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@push('head')
<style>
    /* The Trash (2026-10-23). Every row arrives and leaves on the house easing,
       and the colours come from the app's tokens so dark mode inverts them. */
    .tr-head { position: relative; overflow: hidden; border-radius: 1.4rem; padding: 1.2rem 1.25rem;
        background: linear-gradient(135deg, #1f3d2b 0%, #2c5a3c 60%, #3f7a4f 100%); color: #fff; }
    .tr-head::after { content: ""; position: absolute; right: -2.5rem; bottom: -3rem; width: 11rem; height: 11rem;
        border-radius: 999px; background: rgb(255 255 255 / .07); }
    .tr-head-row { position: relative; z-index: 1; display: flex; align-items: center; gap: .9rem; }
    .tr-bin { flex: none; width: 3.1rem; height: 3.1rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center;
        background: rgb(255 255 255 / .14); }
    .tr-bin svg { width: 1.6rem; height: 1.6rem; }
    .tr-head h2 { font-weight: 900; font-size: 1.15rem; line-height: 1.2; }
    .tr-head p { font-size: .84rem; color: rgb(255 255 255 / .82); margin-top: .15rem; line-height: 1.4; }
    .tr-count { position: relative; z-index: 1; margin-top: .85rem; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .tr-pill { font-size: .74rem; font-weight: 800; border-radius: 999px; padding: .3rem .7rem; background: rgb(255 255 255 / .16); }
    .tr-empty-btn { margin-left: auto; font-size: .78rem; font-weight: 800; border-radius: 999px; padding: .42rem .9rem;
        color: #fff; background: rgb(220 38 38 / .85); border: 1px solid rgb(255 255 255 / .18);
        transition: background .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .tr-empty-btn:hover { background: #dc2626; transform: translateY(-1px); }
    .tr-empty-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

    .tr-chips { display: flex; gap: .45rem; overflow-x: auto; padding-bottom: .35rem; scrollbar-width: none; }
    .tr-chips::-webkit-scrollbar { display: none; }
    .tr-chip { flex: none; font-size: .78rem; font-weight: 800; border-radius: 999px; padding: .4rem .85rem; cursor: pointer; white-space: nowrap;
        border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white);
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .tr-chip.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .tr-chip small { font-weight: 700; opacity: .75; margin-left: .2rem; }

    .tr-day { font-size: .72rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500);
        margin: 1.1rem .3rem .5rem; }
    .tr-row { display: flex; align-items: flex-start; gap: .8rem; padding: .9rem 1rem; border-radius: 1.1rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); margin-bottom: .55rem;
        opacity: 0; transform: translateY(8px);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1), max-height .28s cubic-bezier(.22,1,.36,1), margin .28s cubic-bezier(.22,1,.36,1),
            padding .28s cubic-bezier(.22,1,.36,1); }
    .tr-row.is-in { opacity: 1; transform: none; }
    .tr-row.is-out { opacity: 0; transform: translateX(18px); }
    .tr-row:hover { box-shadow: 0 10px 26px -18px rgb(16 22 12 / .35); }
    .tr-ico { flex: none; width: 2.7rem; height: 2.7rem; border-radius: .9rem; display: flex; align-items: center; justify-content: center;
        background: var(--tr-bg, #eef6f0); color: var(--tr-fg, #2c5a3c); overflow: hidden; }
    .tr-ico svg { width: 1.3rem; height: 1.3rem; }
    .tr-ico img { width: 100%; height: 100%; object-fit: cover; }
    /* Basis 0: a long title shrinks beside the icon instead of wrapping under it. */
    .tr-main { min-width: 0; flex: 1 1 0; }
    .tr-kind { font-size: .68rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--tr-fg, #2c5a3c); }
    .tr-title { font-weight: 800; color: var(--color-gray-900); line-height: 1.3; margin-top: .05rem; overflow-wrap: anywhere; }
    .tr-meta { margin-top: .2rem; font-size: .78rem; color: var(--color-gray-500); line-height: 1.45; }
    .tr-meta b { font-weight: 700; color: var(--color-gray-600); }
    .tr-with { margin-top: .45rem; display: flex; flex-wrap: wrap; gap: .3rem; }
    .tr-with span { font-size: .68rem; font-weight: 800; border-radius: 999px; padding: .14rem .55rem;
        color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    /* Days left, Restore and Delete: one line under the words, three pills of one size (the owner, 2026-10-23). */
    .tr-row { flex-wrap: wrap; }
    .tr-side { flex: 1 0 100%; display: grid; grid-template-columns: repeat(3, 7.4rem); justify-content: end; gap: .45rem; }
    .tr-left, .tr-restore, .tr-kill { height: 2.25rem; padding: 0 .6rem; border-radius: 999px; border: 1px solid; display: inline-flex; align-items: center;
        justify-content: center; gap: .35rem; font-size: .76rem; font-weight: 800; line-height: 1; white-space: nowrap; min-width: 0; }
    .tr-left svg, .tr-restore svg, .tr-kill svg { flex: none; width: .9rem; height: .9rem; }
    .tr-left { color: #92400e; background: #fef3c7; border-color: #fde68a; }
    .tr-left.is-soon { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
    .tr-restore { background: #f5c518; border-color: #f5c518; color: #3b2f00; box-shadow: 0 6px 16px -10px rgb(180 140 0 / .9);
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .tr-restore:hover { background: #ffd23f; border-color: #ffd23f; transform: translateY(-1px); }
    .tr-kill { color: var(--color-gray-600); background: var(--color-gray-50); border-color: var(--color-gray-200);
        transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .tr-kill:hover { color: #dc2626; background: #fef2f2; border-color: #fecaca; transform: translateY(-1px); }
    .tr-row button:disabled { opacity: .55; cursor: wait; }
    /* Dark: the group's colour as a tint behind, and lifted toward white in front. */
    html.dark .tr-ico { background: color-mix(in srgb, var(--tr-fg) 24%, transparent); color: color-mix(in srgb, var(--tr-fg) 45%, white); }
    html.dark .tr-kind { color: color-mix(in srgb, var(--tr-fg) 45%, white); }
    html.dark .tr-left { color: #fcd34d; background: rgb(146 64 14 / .28); border-color: rgb(253 230 138 / .25); }
    html.dark .tr-left.is-soon { color: #fca5a5; background: rgb(185 28 28 / .25); border-color: rgb(254 202 202 / .2); }
    html.dark .tr-kill:hover { background: rgb(220 38 38 / .18); border-color: rgb(254 202 202 / .25); }

    .tr-quiet { text-align: center; padding: 2.6rem 1rem; border-radius: 1.3rem; background: var(--color-white); border: 1px dashed var(--color-gray-300); }
    .tr-quiet svg { width: 2.8rem; height: 2.8rem; margin: 0 auto .7rem; color: var(--color-brand-500); }

    /* A phone: the three share the card's width in equal thirds. */
    @media (max-width: 520px) {
        .tr-side { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; }
        .tr-left, .tr-restore, .tr-kill { padding: 0 .35rem; gap: .25rem; font-size: .74rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .tr-row { transition: none; opacity: 1; transform: none; }
        .tr-chip, .tr-restore, .tr-kill, .tr-empty-btn { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">
    <section class="tr-head mb-4">
        <div class="tr-head-row">
            <span class="tr-bin" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5.5 7l1 12a2 2 0 002 1.8h7a2 2 0 002-1.8l1-12M9 7V4.8A.8.8 0 019.8 4h4.4a.8.8 0 01.8.8V7"/></svg>
            </span>
            <div class="min-w-0">
                <h2>Your Trash</h2>
                <p>What you or your workers delete stays here for 30 days. Put it back any time before then. After 30 days it is deleted for good.</p>
            </div>
        </div>
        <div class="tr-count">
            <span class="tr-pill" id="trCount">Looking…</span>
            <button type="button" class="tr-empty-btn" id="trEmpty" hidden>Empty the Trash</button>
        </div>
    </section>

    <div id="trChips" class="tr-chips mb-1" hidden></div>
    <div id="trList"></div>

    <div id="trLoading" class="text-center py-10 text-sm text-gray-400">Opening the Trash…</div>
    <div id="trQuiet" class="tr-quiet" hidden>
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M5.5 7l1 12a2 2 0 002 1.8h7a2 2 0 002-1.8l1-12M9 7V4.8A.8.8 0 019.8 4h4.4a.8.8 0 01.8.8V7M9.5 13.5l2 2 3.5-4"/></svg>
        <p class="font-bold text-gray-800">The Trash is empty</p>
        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">When you delete a season, an activity, a note, a photo or anything else, it waits here for 30 days in case you change your mind.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URLS = {
        list: @json(route('trash.list')),
        restore: @json(route('trash.restore')),
        purge: @json(route('trash.purge')),
        empty: @json(route('trash.empty')),
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const state = { rows: [], groups: [], group: 'all' };

    // One drawing and one colour per group, so a glance tells a season from a photo.
    const P = (d) => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="' + d + '"/></svg>';
    const LOOK = {
        seasons: ['#e7f4ea', '#2c6b3f', P('M12 21c-4-3-7-6.5-7-10.5A7 7 0 0112 3.5a7 7 0 017 7c0 4-3 7.5-7 10.5zM12 21v-9m0 0l3-3m-3 3l-3-3')],
        board: ['#e8f0fe', '#1d4ed8', P('M7 3.5v3M17 3.5v3M4.5 9h15M5.5 5.5h13a1 1 0 011 1v12a1 1 0 01-1 1h-13a1 1 0 01-1-1v-12a1 1 0 011-1zM8 13h3m-3 3h6')],
        lots: ['#f1f5e6', '#4d7c0f', P('M3.5 7l5.5-2.5 6 2.5 5.5-2.5v12.5L15 19.5l-6-2.5-5.5 2.5zM9 4.5V17m6-10.5v13')],
        notes: ['#fef9e7', '#a16207', P('M6 3.5h9l3.5 3.5v13a.5.5 0 01-.5.5H6a.5.5 0 01-.5-.5V4a.5.5 0 01.5-.5zM9 11h6m-6 3.5h6m-6-7h3')],
        photos: ['#f3e8ff', '#7e22ce', P('M4.5 6.5h15a1 1 0 011 1v10a1 1 0 01-1 1h-15a1 1 0 01-1-1v-10a1 1 0 011-1zM8 11a1.5 1.5 0 100-.01M20.5 15.5l-5-4.5-7 7')],
        supplies: ['#e6f6f6', '#0f766e', P('M4 8l8-4.5L20 8v8l-8 4.5L4 16zM4 8l8 4.5L20 8M12 12.5v8')],
        money: ['#fdf2e3', '#b45309', P('M12 3.5v17M16 7.5c0-1.7-1.8-2.5-4-2.5s-4 .9-4 2.6c0 3.8 8 2 8 6.1 0 1.7-1.8 2.8-4 2.8s-4-1-4-2.7')],
        people: ['#fdeef4', '#be185d', P('M9 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM2.5 20a6.5 6.5 0 0113 0M16 4.5a3.5 3.5 0 010 6.5M21.5 20a6.5 6.5 0 00-4-6')],
        tools: ['#eef2ff', '#4338ca', P('M14.5 6.5l3 3M4 20l6.5-6.5M13 4l7 7-3 3-7-7zM10.5 13.5l-1 1')],
        maps: ['#e0f2fe', '#0369a1', P('M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0113 0c0 5.4-6.5 11-6.5 11zM12 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z')],
        chats: ['#ecfdf5', '#047857', P('M4.5 5.5h15a1 1 0 011 1v9a1 1 0 01-1 1H10l-4.5 3.5V16.5h-1a1 1 0 01-1-1v-9a1 1 0 011-1z')],
        other: ['#f3f4f6', '#4b5563', P('M4 12h16M12 4v16')],
    };

    const ago = (iso) => {
        const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
        if (s < 60) return 'just now';
        if (s < 3600) { const m = Math.round(s / 60); return m + (m === 1 ? ' minute' : ' minutes') + ' ago'; }
        if (s < 86400) { const h = Math.round(s / 3600); return h + (h === 1 ? ' hour' : ' hours') + ' ago'; }
        const d = Math.round(s / 86400); return d + (d === 1 ? ' day' : ' days') + ' ago';
    };
    const dayWord = (day) => {
        const d = new Date(day + 'T00:00:00'), t = new Date(); t.setHours(0, 0, 0, 0);
        const n = Math.round((t - d) / 86400000);
        if (n <= 0) return 'Today';
        if (n === 1) return 'Yesterday';
        if (n < 7) return d.toLocaleDateString(undefined, { weekday: 'long' });
        return d.toLocaleDateString(undefined, { month: 'long', day: 'numeric' });
    };
    const plural = (n, one) => n + ' ' + (n === 1 ? one.toLowerCase() : (one.toLowerCase().endsWith('s') ? one.toLowerCase() : one.toLowerCase() + 's'));

    function rowHtml(r) {
        const [bg, fg, ico] = LOOK[r.group] || LOOK.other;
        const style = '--tr-bg:' + bg + ';--tr-fg:' + fg;
        const face = r.thumb ? '<img src="' + esc(r.thumb) + '" alt="" loading="lazy">' : ico;
        const meta = [];
        if (r.sub) meta.push(esc(r.sub));
        if (r.season) meta.push('in <b>' + esc(r.season.title) + '</b>');
        meta.push('deleted ' + ago(r.at) + (r.by ? ' by <b>' + esc(r.by) + '</b>' : ''));
        const withs = (r.with || []).map((w) => '<span>with ' + esc(plural(w.n, w.label)) + '</span>').join('');
        const left = r.left <= 0 ? 'Goes today' : (r.left === 1 ? '1 day left' : r.left + ' days left');
        return '<article class="tr-row" data-id="' + r.id + '" style="' + style + '">'
            + '<span class="tr-ico" aria-hidden="true">' + face + '</span>'
            + '<div class="tr-main"><div class="tr-kind">' + esc(r.label) + '</div>'
            + '<div class="tr-title">' + esc(r.title || 'Untitled') + '</div>'
            + '<div class="tr-meta">' + meta.join(' · ') + '</div>'
            + (withs ? '<div class="tr-with">' + withs + '</div>' : '') + '</div>'
            + '<div class="tr-side"><span class="tr-left' + (r.left <= 3 ? ' is-soon' : '') + '">' + P('M12 7v5l3 2M12 21a9 9 0 100-18 9 9 0 000 18z') + left + '</span>'
            + '<button type="button" class="tr-restore" data-restore="' + r.id + '">'
            + P('M4 10h11a5 5 0 010 10h-3M4 10l4-4M4 10l4 4') + 'Restore</button>'
            + '<button type="button" class="tr-kill" data-kill="' + r.id + '" aria-label="Delete for good" title="Delete for good">'
            + P('M4 7h16M10 11v6M14 11v6M5.5 7l1 12a2 2 0 002 1.8h7a2 2 0 002-1.8l1-12M9 7V4.8A.8.8 0 019.8 4h4.4a.8.8 0 01.8.8V7') + 'Delete</button></div>'
            + '</article>';
    }

    function paint() {
        const rows = state.group === 'all' ? state.rows : state.rows.filter((r) => r.group === state.group);
        const total = state.rows.length;
        $('trCount').textContent = total ? plural(total, 'thing') + ' in the Trash' : 'Nothing in the Trash';
        $('trEmpty').hidden = !total;
        $('trQuiet').hidden = !!total;
        // The chips: every group in the Trash, counted; only worth showing with two or more.
        const chips = $('trChips');
        chips.hidden = state.groups.length < 2;
        chips.innerHTML = '<button type="button" class="tr-chip' + (state.group === 'all' ? ' is-on' : '') + '" data-group="all">All<small>' + total + '</small></button>'
            + state.groups.map((g) => '<button type="button" class="tr-chip' + (state.group === g.key ? ' is-on' : '') + '" data-group="' + g.key + '">' + esc(g.label) + '<small>' + g.n + '</small></button>').join('');
        let html = '', day = null;
        rows.forEach((r) => {
            if (r.day !== day) { day = r.day; html += '<div class="tr-day">' + esc(dayWord(day)) + '</div>'; }
            html += rowHtml(r);
        });
        $('trList').innerHTML = html;
        requestAnimationFrame(() => $('trList').querySelectorAll('.tr-row').forEach((el, i) => setTimeout(() => el.classList.add('is-in'), Math.min(i, 12) * 35)));
    }

    async function load() {
        try {
            const res = await window.api(URLS.list);
            state.rows = res.data.rows || [];
            state.groups = res.data.groups || [];
            if (state.group !== 'all' && !state.groups.some((g) => g.key === state.group)) state.group = 'all';
            $('trLoading').hidden = true;
            paint();
        } catch (e) {
            $('trLoading').textContent = (e && e.message) || 'Could not open the Trash. Please try again.';
        }
    }

    // A row leaves the list sliding out, then the list repaints from the server's answer.
    const leave = (id) => new Promise((done) => {
        const el = $('trList').querySelector('.tr-row[data-id="' + id + '"]');
        if (!el) return done();
        el.style.maxHeight = el.offsetHeight + 'px';
        el.classList.add('is-out');
        setTimeout(() => { el.style.maxHeight = '0px'; el.style.paddingTop = el.style.paddingBottom = '0px'; el.style.marginBottom = '0px'; }, 140);
        setTimeout(done, 420);
    });

    document.addEventListener('click', async (e) => {
        const chip = e.target.closest('[data-group]');
        if (chip && chip.closest('#trChips')) { state.group = chip.dataset.group; paint(); return; }

        const rb = e.target.closest('[data-restore]');
        if (rb) {
            rb.disabled = true;
            try {
                const res = await window.api(URLS.restore, { method: 'POST', body: { id: Number(rb.dataset.restore) } });
                await leave(rb.dataset.restore);
                window.toast?.(res.message || 'Restored.');
                load();
            } catch (err) {
                rb.disabled = false;
                window.toast?.(err.message || 'Could not restore it.', 'error');
            }
            return;
        }

        const kb = e.target.closest('[data-kill]');
        if (kb) {
            const r = state.rows.find((x) => String(x.id) === kb.dataset.kill);
            const more = r && r.with && r.with.length ? ' Everything deleted with it goes too.' : '';
            const sure = await window.confirmAction?.({
                title: 'Delete for good?',
                message: '"' + ((r && r.title) || 'This') + '" will be deleted for good and cannot be brought back.' + more,
                confirmText: 'Delete for good',
            });
            if (!sure) return;
            kb.disabled = true;
            try {
                const res = await window.api(URLS.purge, { method: 'POST', body: { id: Number(kb.dataset.kill) } });
                await leave(kb.dataset.kill);
                window.toast?.(res.message || 'Deleted for good.');
                load();
            } catch (err) {
                kb.disabled = false;
                window.toast?.(err.message || 'Could not delete it.', 'error');
            }
        }
    });

    $('trEmpty').addEventListener('click', async () => {
        const sure = await window.confirmAction?.({
            title: 'Empty the Trash?',
            message: 'Everything in the Trash will be deleted for good. This cannot be undone.',
            confirmText: 'Empty the Trash',
        });
        if (!sure) return;
        const btn = $('trEmpty');
        btn.disabled = true;
        try {
            const ids = state.rows.map((r) => r.id);
            const res = await window.api(URLS.empty, { method: 'POST', body: {} });
            await Promise.all(ids.slice(0, 15).map(leave));
            window.toast?.(res.message || 'The Trash is empty.');
            load();
        } catch (err) {
            window.toast?.(err.message || 'Could not empty the Trash.', 'error');
        } finally { btn.disabled = false; }
    });

    // window.api lives in the deferred module bundle, which evaluates after inline scripts.
    if (document.readyState === 'complete') load();
    else window.addEventListener('load', () => load(), { once: true });
})();
</script>
@endpush
