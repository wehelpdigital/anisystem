@extends('layouts.app')

@section('title', 'Tags — ' . $schedule->title)
@section('page-title', 'Tags')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    /* ===== Tags ======================================================
       The words a farmer ties to the season's things, and everything
       each word is tied to. A word is a shelf; tap it and its shelf
       opens underneath. */
    .tg-wrap { max-width: 44rem; margin: 0 auto; }
    .tg-cloud { display: flex; flex-wrap: wrap; gap: .5rem; }
    .tg-tag { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .8rem;
        border-radius: 999px; font-size: .85rem; font-weight: 700; cursor: pointer;
        background: var(--color-white); color: var(--color-gray-700);
        border: 1px solid var(--color-gray-200); box-shadow: var(--shadow-card);
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1),
            color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .tg-tag:hover { transform: translateY(-1px); }
    .tg-tag.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .tg-tag .n { display: inline-flex; align-items: center; justify-content: center; min-width: 1.3rem;
        height: 1.3rem; padding: 0 .3rem; border-radius: 999px; font-size: .7rem;
        background: var(--color-gray-100); color: var(--color-gray-500); }
    .tg-tag.is-on .n { background: rgb(255 255 255 / .22); color: #fff; }
    html.dark .tg-tag { background: #151b12; border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .tg-tag.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .tg-tag .n { background: #222b1a; color: #93a684; }

    .tg-items { margin-top: 1rem; display: grid; gap: .5rem; }
    .tg-item { display: flex; align-items: center; gap: .65rem; padding: .7rem .8rem;
        border-radius: .9rem; border: 1px solid var(--color-gray-100); background: var(--color-white);
        box-shadow: var(--shadow-card); text-align: left; width: 100%;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .tg-item:hover { transform: translateY(-1px); }
    .tg-item .e { font-size: 1.15rem; flex: none; }
    .tg-item b { display: block; font-size: .86rem; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .tg-item i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-400); }
    html.dark .tg-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .tg-item b { color: #e8efe1; }

    .tg-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin: 1.1rem 0 .5rem; }
    .tg-head b { font-size: .92rem; color: var(--color-gray-900); }
    html.dark .tg-head b { color: #e8efe1; }
    .tg-none { text-align: center; color: var(--color-gray-400); font-size: .85rem; padding: 2.2rem 1rem; }
</style>
@endpush

@section('content')
<div class="tg-wrap">
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'tags'])

    <div class="card p-4 mb-4">
        <p class="text-sm font-bold text-gray-900">The season's tags</p>
        <p class="text-xs text-gray-500 mt-1">A tag is a word you tie to things as you add them — an activity, a note,
            an expense, a photo. Tap one to see everything it is tied to. New tags are coined right on the forms,
            under <b>Add tags</b>.</p>
    </div>

    <div class="tg-cloud" id="tgCloud"></div>
    <p class="tg-none" id="tgEmpty" hidden>No tags yet. Add one from any form in Activities — the picker sits at the bottom, marked optional.</p>

    <div id="tgShelf" hidden>
        <div class="tg-head">
            <b id="tgShelfSays"></b>
            <button type="button" class="btn btn-white btn-sm" id="tgDeleteBtn">Delete tag</button>
        </div>
        <div class="tg-items" id="tgItems"></div>
        <p class="tg-none" id="tgShelfEmpty" hidden>This tag is not tied to anything yet.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const U = {
        list: @json(route('sm.tags.list') . '?id=' . $schedule->id),
        items: @json(route('sm.tags.items') . '?id=' . $schedule->id),
        del: (id) => @json(route('sm.tags.destroy', ['id' => '__ID__'])).replace('__ID__', id) + '?scheduleId={{ $schedule->id }}',
    };
    let TAGS = [];
    let OPEN = null;

    async function load() {
        try {
            const res = await api(U.list);
            TAGS = res.data.tags || [];
            paint();
        } catch (err) { toast(err.message, 'error'); }
    }

    function paint() {
        $id('tgCloud').innerHTML = TAGS.map((t) => `
            <button type="button" class="tg-tag${OPEN === t.id ? ' is-on' : ''}" data-tg="${t.id}">
                🏷️ ${esc(t.name)} <span class="n">${t.count}</span>
            </button>`).join('');
        $id('tgEmpty').hidden = TAGS.length > 0;
        if (OPEN && !TAGS.some((t) => t.id === OPEN)) { OPEN = null; $id('tgShelf').hidden = true; }
    }

    $id('tgCloud').addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-tg]');
        if (!btn) return;
        const id = Number(btn.dataset.tg);
        if (OPEN === id) { OPEN = null; $id('tgShelf').hidden = true; paint(); return; }
        OPEN = id;
        paint();
        try {
            const res = await api(U.items + '&tagId=' + id);
            const items = res.data.items || [];
            $id('tgShelfSays').textContent = '🏷️ ' + res.data.tag.name + ' — ' + items.length + (items.length === 1 ? ' thing' : ' things');
            $id('tgItems').innerHTML = items.map((it) => `
                <a class="tg-item" href="${esc(it.url)}">
                    <span class="e">${it.icon}</span>
                    <span class="min-w-0 grow"><b>${esc(it.title)}</b><i>${esc(it.sub || '')}</i></span>
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>`).join('');
            $id('tgShelfEmpty').hidden = items.length > 0;
            $id('tgShelf').hidden = false;
        } catch (err) { toast(err.message, 'error'); }
    });

    $id('tgDeleteBtn').addEventListener('click', async () => {
        if (!OPEN) return;
        const t = TAGS.find((x) => x.id === OPEN);
        const delId = OPEN;
        const ok = window.confirmAction ? await window.confirmAction({
            title: `Delete the tag "${t ? t.name : ''}"?`,
            message: 'The tag and its ties go; the things themselves stay exactly where they are.',
            confirmText: 'Delete tag',
        }) : true;
        if (!ok) return;
        try {
            await api(U.del(delId), { method: 'DELETE' });
            toast('Tag removed.');
            OPEN = null;
            $id('tgShelf').hidden = true;
            window.smTags?.invalidate?.();
            load();
        } catch (err) { toast(err.message, 'error'); }
    });

    load();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
