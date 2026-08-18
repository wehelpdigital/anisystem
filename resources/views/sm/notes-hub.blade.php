@extends('layouts.app')

@section('title', 'Global Notes')
@section('page-title', 'Global Notes')
@section('page-subtitle', 'Everything you\'ve jotted down')
@section('back', route('sm.index'))

@push('head')
<style>
    /* ---- the head of the page: what this is, and the one thing to do --- */
    .nh-top { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        padding: .9rem 1rem; margin-bottom: .8rem; border-radius: 1.1rem; position: relative; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .nh-top::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, #6d28d9, #b8a6ef 55%, transparent); }
    .nh-top p { font-size: .8rem; color: var(--color-gray-500); line-height: 1.5; min-width: 0; flex: 1 1 12rem; }
    html.dark .nh-top { background: #151b12; border-color: #2b3a1c; }
    html.dark .nh-top p { color: #9fb08e; }

    /* Which notes to look at — the shelf holds three kinds. */
    .nh-filters { display: flex; gap: .35rem; flex-wrap: wrap; margin-bottom: .8rem; }
    .nh-filter { padding: .35rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 700;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-600);
        cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .nh-filter:hover { border-color: #a8cc7e; }
    .nh-filter.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .nh-filter { background: #151b12; border-color: #2b3a1c; color: #cdd8c0; }

    /* ---- a note, folded to its name ---- */
    .nh-list { display: flex; flex-direction: column; gap: .5rem; }
    .nh-card { border: 1px solid var(--color-gray-200); border-radius: .9rem; overflow: hidden;
        background: var(--color-white); }
    .nh-card.is-hidden { display: none; }
    .nh-head { display: flex; align-items: flex-start; gap: .55rem; width: 100%; text-align: left;
        padding: .7rem .8rem; cursor: pointer; }
    .nh-chev { width: .95rem; height: .95rem; flex-shrink: 0; margin-top: .15rem; color: var(--color-gray-400);
        transition: transform .18s ease; }
    .nh-card:not(.is-folded) .nh-chev { transform: rotate(90deg); }
    /* A note with nothing under its name has nothing to open. */
    .nh-card.is-bare .nh-chev { visibility: hidden; }
    .nh-card.is-bare .nh-head { cursor: default; }
    .nh-headmain { min-width: 0; flex: 1 1 auto; }
    .nh-title { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.35;
        overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .nh-meta { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; margin-top: .25rem; }
    .nh-where, .nh-when { font-size: .68rem; color: var(--color-gray-400); }
    .nh-where { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 12rem; }
    .nh-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .nh-fold-inner { overflow: hidden; min-height: 0; }
    .nh-card.is-folded .nh-fold { grid-template-rows: 0fr; }
    .nh-body { padding: 0 .8rem .8rem; }
    #nhList.no-fold-anim .nh-fold, #nhList.no-fold-anim .nh-chev { transition: none; }
    html.dark .nh-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .nh-title { color: #e8efe1; }
    html.dark .nh-body .text-gray-700 { color: #cdd8c0; }

    /* What you can do with an open note, said in words. */
    .nh-acts { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem;
        padding-top: .6rem; border-top: 1px dashed var(--color-gray-200); }
    .nh-act { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .65rem; border-radius: 999px;
        font-size: .72rem; font-weight: 700; color: var(--color-gray-600);
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); cursor: pointer; }
    .nh-act svg { width: .85rem; height: .85rem; }
    .nh-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .nh-act.is-danger { color: #b91c1c; }
    .nh-act.is-danger:hover { background: #fee2e2; }
    html.dark .nh-acts { border-color: #2b3a1c; }
    html.dark .nh-act { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #cdd8c0; }

    .nh-tag { font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; padding:.1rem .4rem; border-radius:.35rem; flex-shrink: 0; }
    .nh-tag.global { background:#ede9fe; color:#6d28d9; }
    .nh-tag.schedule { background:#dcfce7; color:#15803d; }
    .nh-tag.day { background:#fef3c7; color:#b45309; }
    html.dark .nh-tag.global { background:rgb(109 40 217 / .2); color:#c4b5fd; }
    html.dark .nh-tag.schedule { background:rgb(21 128 61 / .2); color:#86efac; }
    html.dark .nh-tag.day { background:rgb(180 83 9 / .2); color:#eec155; }
    .rich-text img { max-width:100%; max-height:12rem; border-radius:.5rem; }

    @media (max-width: 640px) {
        /* On a phone the shelf is a list of names — the words and the
           pictures come when a note is opened, not all at once. */
        .nh-top { padding: .8rem .9rem; }
        .nh-where { max-width: 8rem; }
        .nh-newbtn { width: 100%; justify-content: center; }
    }
    @media (prefers-reduced-motion: reduce) {
        .nh-fold, .nh-chev, .nh-filter { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="nh-top">
    <p>Every note you have anywhere — the free-standing ones, each schedule's notebook, and the notes pinned to a day. Tap a note to open it.</p>
    <button type="button" id="addNoteBtn" class="btn btn-primary btn-sm nh-newbtn shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
        New note
    </button>
</div>

@if ($notes->total() > 0)
    <div class="nh-filters" id="nhFilters">
        <button type="button" class="nh-filter is-on" data-kind="all">All</button>
        <button type="button" class="nh-filter" data-kind="global">Global</button>
        <button type="button" class="nh-filter" data-kind="schedule">Schedules</button>
        <button type="button" class="nh-filter" data-kind="day">Days</button>
        <button type="button" class="nh-filter" data-fold="1" id="nhFoldAll">Collapse all</button>
    </div>
@endif

@if ($notes->total() === 0)
    <div class="card p-8 text-center">
        <div class="empty-tile">🗒️</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No notes yet</p>
        <p class="text-sm text-gray-500 mt-1">Tap “New note” to write, draw, add photos &amp; videos, or drop an emoji.</p>
    </div>
@endif

<div id="nhList" class="nh-list">
    @include('sm.partials.notes-hub-rows', ['notes' => $notes])
</div>
@include('partials.list-pager', [
    'noun' => 'note','paginator' => $notes, 'rowsUrl' => route('notes.hub') . '?rows=1'])

@include('sm.partials.draw-canvas')
@include('sm.partials.note-editor')
@include('sm.partials.note-lightbox')
@include('community.partials.video-js')
{{-- The post-recording sheet: a clip filmed inside the editor is named the
     moment it stops, before it joins the note. --}}
@include('sm.partials.recording-save')
@endsection

@push('scripts')
<script>
(function notesHub() {
    const $ = (id) => document.getElementById(id);
    const list = $('nhList');

    $('addNoteBtn').addEventListener('click', () => {
        if (typeof window.openNoteEditor !== 'function') { window.toast && toast('Editor unavailable.', 'error'); return; }
        window.openNoteEditor({
            title: 'New note',
            bodyHtml: '',
            media: [],
            imageUploadUrl: @json(route('notes.hub.image-upload')),
            videoUploadUrl: @json(route('notes.hub.video-upload')),
            drawUploadUrl: @json(route('notes.hub.draw')),
            onSave: async ({ body, media }) => {
                try {
                    await window.api(@json(route('notes.hub.store')), { method: 'POST', body: { body, media } });
                    window.toast && toast('Note saved.');
                    window.location.reload();
                } catch (err) { window.toast && toast(err.message, 'error'); }
            },
        });
    });

    /* ---- folding ---------------------------------------------------
       Notes start folded to their names: the shelf is for finding one,
       and a wall of opened notes is the thing it replaces. Which ones
       you opened is remembered, so a reload does not undo the reading. */
    const OPEN_KEY = 'nhOpen';
    const openSet = (() => {
        try { return new Set(JSON.parse(localStorage.getItem(OPEN_KEY) || '[]')); } catch (_) { return new Set(); }
    })();
    const saveOpen = () => { try { localStorage.setItem(OPEN_KEY, JSON.stringify([...openSet].slice(-80))); } catch (_) { /* private mode */ } };
    const keyOf = (card) => card.getAttribute('data-note-type') + ':' + card.getAttribute('data-note-id');
    const cards = () => Array.from(list.querySelectorAll('.nh-card'));

    function applyFolds(scope) {
        (scope || list).querySelectorAll('.nh-card').forEach((c) => {
            if (c.classList.contains('is-bare')) { c.classList.remove('is-folded'); return; }
            c.classList.toggle('is-folded', !openSet.has(keyOf(c)));
        });
    }
    list.classList.add('no-fold-anim');
    applyFolds();
    void list.offsetWidth;
    requestAnimationFrame(() => list.classList.remove('no-fold-anim'));

    function sayFoldBtn() {
        const btn = $('nhFoldAll');
        if (!btn) return;
        const anyOpen = cards().some((c) => !c.classList.contains('is-folded') && !c.classList.contains('is-bare'));
        btn.textContent = anyOpen ? 'Collapse all' : 'Expand all';
    }
    sayFoldBtn();

    list.addEventListener('click', (e) => {
        // The actions inside an open note are not the fold.
        if (e.target.closest('.nh-act, a, .na, .nm')) return;
        const head = e.target.closest('.nh-head');
        if (!head) return;
        const card = head.closest('.nh-card');
        if (!card || card.classList.contains('is-bare')) return;
        if (card.classList.toggle('is-folded')) openSet.delete(keyOf(card));
        else openSet.add(keyOf(card));
        saveOpen();
        sayFoldBtn();
    });

    /* ---- what kind of notes to show ---- */
    const filters = $('nhFilters');
    let kind = 'all';
    function applyFilter() {
        cards().forEach((c) => {
            c.classList.toggle('is-hidden', kind !== 'all' && c.getAttribute('data-note-type') !== kind);
        });
    }
    filters?.addEventListener('click', (e) => {
        const btn = e.target.closest('.nh-filter');
        if (!btn) return;
        if (btn.hasAttribute('data-fold')) {
            const fold = cards().some((c) => !c.classList.contains('is-folded') && !c.classList.contains('is-bare'));
            cards().forEach((c) => {
                if (c.classList.contains('is-bare')) return;
                c.classList.toggle('is-folded', fold);
                if (fold) openSet.delete(keyOf(c)); else openSet.add(keyOf(c));
            });
            saveOpen();
            sayFoldBtn();
            return;
        }
        kind = btn.getAttribute('data-kind') || 'all';
        filters.querySelectorAll('.nh-filter[data-kind]').forEach((b) => b.classList.toggle('is-on', b === btn));
        applyFilter();
    });

    // Cards arriving from the scroller start folded and filtered like the rest.
    new MutationObserver((muts) => {
        const added = muts.some((m) => m.addedNodes.length);
        if (!added) return;
        applyFolds();
        applyFilter();
        sayFoldBtn();
    }).observe(list, { childList: true });

    /* ---- delete a global note ---- */
    list.addEventListener('click', async (e) => {
        const del = e.target.closest('.nh-del');
        if (!del) return;
        e.stopPropagation();
        const ok = (typeof confirmAction === 'function')
            ? await confirmAction({ title: 'Delete this note?', message: 'The note and everything attached to it will be permanently removed.', confirmText: 'Delete', danger: true })
            : confirm('Delete this note?');
        if (!ok) return;
        try {
            await window.api(@json(route('notes.hub.destroy')) + '?id=' + del.getAttribute('data-id'), { method: 'DELETE' });
            del.closest('[data-note-id]')?.remove();
            window.toast && toast('Note deleted.');
        } catch (err) { window.toast && toast(err.message, 'error'); }
    });
})();
</script>
@endpush
