@extends('layouts.app')

@section('title', 'Tags')
@section('page-title', 'Tags')
@section('page-subtitle', 'Every tag, in every season and tool')
@section('back', route('app.dashboard'))

@include('partials.tag-sheet-css')

@push('head')
<style>
    /* ===== Global Tags ================================================
       Every word the member has tied to anything: the tags of all their
       seasons and the tags on things outside any season (contacts,
       protocols, saved analyses, global notes), one word however it was
       typed. Tap a word, its shelf opens underneath. */
    .gt-wrap { max-width: 44rem; margin: 0 auto; }
    .gt-ease { transition-timing-function: cubic-bezier(.22,1,.36,1); }

    .gt-search { position: relative; }
    .gt-search-ico { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem;
        color: var(--color-gray-400); pointer-events: none; }
    .gt-search-x { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%); width: 1.7rem; height: 1.7rem;
        border-radius: 999px; color: var(--color-gray-400); display: inline-flex; align-items: center; justify-content: center; font-size: .8rem;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .gt-search-x:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .gt-search input[type="search"]::-webkit-search-cancel-button { -webkit-appearance: none; }

    /* Where: All / In a season / Global — three doors, the lit one slides. */
    .gt-seg { position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; padding: .25rem;
        border-radius: 999px; background: var(--color-gray-100); border: 1px solid var(--color-gray-200); }
    .gt-seg button { position: relative; z-index: 1; padding: .45rem .3rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: var(--color-gray-500); transition: color .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
    .gt-seg button.is-on { color: #fff; }
    .gt-seg-pill { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 3);
        border-radius: 999px; background: var(--color-brand-600); box-shadow: 0 1px 3px rgb(0 0 0 / .15);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    html.dark .gt-seg { background: #151b12; border-color: #2b3a1c; }
    html.dark .gt-seg button { color: #93a684; }
    html.dark .gt-seg button.is-on { color: #fff; }
    html.dark .gt-seg-pill { background: #4a7c2a; }

    /* The narrower (one season / one tool), only when it has a choice to make. */
    .gt-narrow { display: grid; grid-template-rows: 0fr; opacity: 0; margin-top: 0;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), margin-top .28s cubic-bezier(.22,1,.36,1); }
    .gt-narrow > div { overflow: hidden; min-height: 0; }
    .gt-narrow.is-open { grid-template-rows: 1fr; opacity: 1; margin-top: .6rem; }
    .gt-narrow:not(.is-open) { pointer-events: none; }

    .gt-says { font-size: .78rem; color: var(--color-gray-500); margin: .9rem 0 .6rem; }
    .gt-note { font-size: .8rem; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        border-radius: .9rem; padding: .65rem .8rem; margin-top: .8rem; }
    html.dark .gt-note { background: #151b12; border-color: #2b3a1c; color: #b7c2ad; }

    .gt-cloud { display: flex; flex-wrap: wrap; gap: .5rem; }
    .gt-tag { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .55rem .45rem .8rem; max-width: 100%;
        border-radius: 999px; font-size: .85rem; font-weight: 700; cursor: pointer;
        background: var(--color-white); color: var(--color-gray-700);
        border: 1px solid var(--color-gray-200); box-shadow: var(--shadow-card);
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1),
            color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .gt-tag > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gt-tag:hover { transform: translateY(-1px); }
    .gt-tag.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .gt-tag .n { display: inline-flex; align-items: center; justify-content: center; min-width: 1.3rem;
        height: 1.3rem; padding: 0 .35rem; border-radius: 999px; font-size: .7rem; flex: none;
        background: var(--color-gray-100); color: var(--color-gray-500);
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .gt-tag.is-on .n { background: rgb(255 255 255 / .22); color: #fff; }
    /* Where the word lives, as two small marks: a season, and/or outside. */
    .gt-tag .src { display: inline-flex; gap: .15rem; flex: none; }
    .gt-tag .src i { width: .42rem; height: .42rem; border-radius: 999px; display: block; }
    .gt-tag .src .s { background: var(--color-brand-500); }
    .gt-tag .src .g { background: transparent; border: 1.5px solid var(--color-gray-400); }
    .gt-tag.is-on .src .s { background: #fff; }
    .gt-tag.is-on .src .g { border-color: #fff; }
    html.dark .gt-tag { background: #151b12; border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .gt-tag.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .gt-tag .n { background: #222b1a; color: #93a684; }
    .gt-legend { display: flex; flex-wrap: wrap; gap: .9rem; font-size: .72rem; color: var(--color-gray-500); margin-top: .7rem; }
    .gt-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .gt-legend i { width: .5rem; height: .5rem; border-radius: 999px; display: block; }
    .gt-legend .s { background: var(--color-brand-500); }
    .gt-legend .g { border: 1.5px solid var(--color-gray-400); }
    .gt-legend[hidden] { display: none; }

    @keyframes gtIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: none; } }
    .gt-in { animation: gtIn .28s cubic-bezier(.22,1,.36,1) both; }

    /* The shelf under the cloud: height eases open, never snaps. */
    .gt-shelf { display: grid; grid-template-rows: 0fr; opacity: 0; visibility: hidden;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .gt-shelf > .gt-shelf-in { overflow: hidden; min-height: 0; }
    .gt-shelf.is-open { grid-template-rows: 1fr; opacity: 1; visibility: visible;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), visibility 0s; }
    .gt-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; margin: 1.3rem 0 .6rem; flex-wrap: wrap; }
    .gt-head h2 { font-size: 1rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .gt-head p { font-size: .76rem; color: var(--color-gray-500); margin-top: .1rem; }
    .gt-acts { display: flex; gap: .4rem; flex: none; }
    html.dark .gt-head h2 { color: #e8efe1; }

    .gt-items { display: grid; gap: .5rem; }
    .gt-item { display: flex; align-items: center; gap: .65rem; padding: .7rem .8rem;
        border-radius: .9rem; border: 1px solid var(--color-gray-100); background: var(--color-white);
        box-shadow: var(--shadow-card); text-align: left; width: 100%; text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .gt-item:hover { transform: translateY(-1px); border-color: var(--color-brand-200); }
    .gt-item .e { font-size: 1.15rem; flex: none; align-self: flex-start; margin-top: .05rem; }
    .gt-item b { display: block; font-size: .86rem; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .gt-item i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-400); }
    .gt-item .chev { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-300); }
    html.dark .gt-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .gt-item b { color: #e8efe1; }
    .gt-where { display: flex; align-items: center; flex-wrap: wrap; gap: .3rem; margin-top: .35rem;
        font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .gt-schip { display: inline-flex; align-items: center; gap: .25rem; max-width: 100%; padding: .1rem .5rem; border-radius: 999px;
        font-size: .7rem; font-weight: 800; color: var(--color-brand-800); background: var(--color-brand-50); border: 1px solid var(--color-brand-200);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gt-where.is-out { color: var(--color-gray-500); font-weight: 600; }
    .gt-where.is-out b { display: inline; font-size: .72rem; color: var(--color-gray-700); font-weight: 800; }
    html.dark .gt-schip { background: #22301a; color: #cfe6b8; border-color: #3f5a2a; }
    html.dark .gt-where.is-out b { color: #cfd8c6; }

    .gt-none { text-align: center; color: var(--color-gray-400); font-size: .85rem; padding: 2.2rem 1rem; }
    .gt-wait { text-align: center; color: var(--color-gray-400); font-size: .82rem; padding: 1.4rem 0; }

    @media (prefers-reduced-motion: reduce) {
        .gt-in { animation: none; }
        .gt-seg-pill, .gt-shelf, .gt-narrow, .gt-tag, .gt-item { transition: none; }
    }
    html.sm-still .gt-in { animation: none; }
    html.sm-still .gt-seg-pill, html.sm-still .gt-shelf, html.sm-still .gt-narrow { transition: none; }
</style>
@endpush

@section('content')
<div class="gt-wrap">

    <div class="gt-search mb-3">
        <svg class="gt-search-ico" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        {{-- pl-10!, not pl-10: .form-input's own padding wins the tie otherwise. --}}
        <input type="search" id="gtSearch" class="form-input pl-10! pr-10!" placeholder="Search your tags" autocomplete="off" aria-label="Search your tags">
        <button type="button" id="gtSearchX" class="gt-search-x hidden" aria-label="Clear the search">✕</button>
    </div>

    <div class="gt-seg" role="tablist" aria-label="Where the tags live">
        <span class="gt-seg-pill" id="gtSegPill" aria-hidden="true"></span>
        <button type="button" class="is-on" data-gt-mode="all" role="tab" aria-selected="true">All</button>
        <button type="button" data-gt-mode="season" role="tab" aria-selected="false">In a season</button>
        <button type="button" data-gt-mode="global" role="tab" aria-selected="false">Global</button>
    </div>

    {{-- One season, or one tool — a chooser that opens only when there is a choice. --}}
    <div class="gt-narrow" id="gtNarrow">
        <div>
            <button type="button" class="crop-tag" id="gtNarrowBtn">
                <span class="crop-tag-e" id="gtNarrowE">🌾</span>
                <span class="crop-tag-t" id="gtNarrowSay">All seasons</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
    </div>

    @if ($inWorker)
        <p class="gt-note">You are working on another farm right now. These are <b>your own</b> tags — your seasons and your things. To open one of your seasons, switch to your own farm from the 🏡 menu first.</p>
    @endif

    <p class="gt-says" id="gtSays">Gathering your tags…</p>
    <div class="gt-cloud" id="gtCloud"></div>
    <div class="gt-legend" id="gtLegend" hidden>
        <span><i class="s"></i>in a season</span>
        <span><i class="g"></i>outside a season</span>
    </div>
    <p class="gt-none" id="gtEmpty" hidden>No tags yet. Tag things as you go — a season's activities, notes and lots, or your contacts, protocols and saved analyses — and every word gathers here.</p>
    <p class="gt-none" id="gtNoMatch" hidden>No tag matches that.</p>

    <div class="gt-shelf" id="gtShelf" aria-live="polite">
        <div class="gt-shelf-in">
            <div class="gt-head">
                <div class="min-w-0">
                    <h2 id="gtShelfName"></h2>
                    <p id="gtShelfSays"></p>
                </div>
                <div class="gt-acts">
                    <button type="button" class="btn btn-white btn-sm" id="gtRenameBtn" disabled>Rename</button>
                    <button type="button" class="btn btn-white btn-sm" id="gtDeleteBtn" disabled>Delete</button>
                </div>
            </div>
            <div class="gt-items" id="gtItems"></div>
            <p class="gt-none" id="gtShelfEmpty" hidden></p>
        </div>
    </div>
</div>

{{-- One season / one tool --}}
<div class="sheet hidden" id="gtNarrowSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gtNarrowTitle">Which season?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="gtNarrowList"></div>
</div>

{{-- Rename, everywhere --}}
<div class="sheet hidden" id="gtRenameSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Rename the tag</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="gtRenameInput">New name</label>
        <input type="text" class="form-input" id="gtRenameInput" maxlength="30" autocomplete="off" enterkeyhint="done">
        <p class="text-xs text-gray-500 mt-2" id="gtRenameSays">Every season and tool that wears this tag takes the new name.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="gtRenameSave">Rename</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const IN_WORKER = @json((bool) $inWorker);
    const U = {
        list: @json(route('tags.global.list')),
        items: @json(route('tags.global.items')),
        rename: @json(route('tags.global.rename')),
        del: @json(route('tags.global.delete')),
    };
    const still = () => document.documentElement.classList.contains('sm-still')
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const keyOf = (s) => String(s ?? '').replace(/\s+/g, ' ').trim().toLowerCase();
    const plural = (n, one, many) => `${n} ${n === 1 ? one : (many || one + 's')}`;

    let TAGS = [];          // [{name, key, season, global, count, seasons, bySeason, byPlace}]
    let SEASONS = [];       // [{id, title}]
    let MODE = 'all';       // all | season | global
    let NARROW = '';        // a season id (season mode) or a tool's name (global mode)
    let Q = '';
    let OPEN = null;        // the open word's key
    let ITEMS = [];
    let COUNTS = null;
    let SEQ = 0;            // a newer shelf request supersedes an older one
    let FIRST = true;

    // ---- what counts under the current filter ----------------------------
    function countOf(t) {
        if (MODE === 'season') return NARROW ? (t.bySeason[NARROW] ?? 0) : t.season;
        if (MODE === 'global') return NARROW ? (t.byPlace[NARROW] ?? 0) : t.global;
        return t.count;
    }
    function shows(t) {
        if (Q && !t.key.includes(Q)) return false;
        if (MODE === 'season') return NARROW ? (NARROW in t.bySeason) : Object.keys(t.bySeason).length > 0;
        if (MODE === 'global') return NARROW ? (NARROW in t.byPlace) : t.global > 0;
        return true;
    }
    function itemShows(it) {
        if (MODE === 'season') return it.where === 'season' && (!NARROW || String(it.seasonId) === String(NARROW));
        if (MODE === 'global') return it.where === 'global' && (!NARROW || it.place === NARROW);
        return true;
    }
    const places = () => [...new Set(TAGS.flatMap((t) => Object.keys(t.byPlace)))].sort();

    // ---- the cloud -------------------------------------------------------
    function paintCloud(animate) {
        const list = TAGS.filter(shows);
        $id('gtCloud').innerHTML = list.map((t, i) => {
            const s = Object.keys(t.bySeason).length > 0, g = t.global > 0;
            return `<button type="button" class="gt-tag${OPEN === t.key ? ' is-on' : ''}${animate ? ' gt-in' : ''}" data-gt="${esc(t.key)}"
                    ${animate ? `style="animation-delay:${Math.min(i, 24) * 14}ms"` : ''}
                    title="${esc(t.name)}${s ? ' · in ' + plural(t.seasons, 'season') : ''}${g ? ' · on ' + plural(t.global, 'thing') + ' outside a season' : ''}">
                <span>${esc(t.name)}</span>
                <span class="src">${s ? '<i class="s"></i>' : ''}${g ? '<i class="g"></i>' : ''}</span>
                <span class="n">${countOf(t)}</span>
            </button>`;
        }).join('');
        const any = TAGS.length > 0;
        $id('gtEmpty').hidden = any;
        $id('gtNoMatch').hidden = !any || list.length > 0;
        $id('gtLegend').hidden = !any || list.length === 0;
        const seasonsWith = new Set(TAGS.flatMap((t) => Object.keys(t.bySeason))).size;
        const outside = TAGS.some((t) => t.global > 0);
        const across = seasonsWith && outside ? `across ${plural(seasonsWith, 'season')} and your tools`
            : seasonsWith ? `across ${plural(seasonsWith, 'season')}` : 'on things outside a season';
        $id('gtSays').textContent = !any ? '' : (
            MODE === 'all' && !Q ? `${plural(TAGS.length, 'tag')} ${across}` : `${plural(list.length, 'tag')} shown`);
        $id('gtSays').hidden = !any;
    }

    function paintSeg() {
        const i = ['all', 'season', 'global'].indexOf(MODE);
        document.querySelectorAll('[data-gt-mode]').forEach((b) => {
            const on = b.dataset.gtMode === MODE;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $id('gtSegPill').style.transform = `translateX(${i * 100}%)`;
        // The narrower opens only when there is more than one thing to pick.
        const choices = MODE === 'season' ? SEASONS.filter((s) => TAGS.some((t) => String(s.id) in t.bySeason)).length
            : MODE === 'global' ? places().length : 0;
        $id('gtNarrow').classList.toggle('is-open', choices > 1);
        if (MODE === 'season') {
            const s = SEASONS.find((x) => String(x.id) === String(NARROW));
            $id('gtNarrowE').textContent = '🌾';
            $id('gtNarrowSay').textContent = s ? s.title : 'All seasons';
        } else if (MODE === 'global') {
            $id('gtNarrowE').textContent = '🧰';
            $id('gtNarrowSay').textContent = NARROW || 'All tools outside a season';
        }
    }

    async function load(reopen) {
        try {
            const res = await api(U.list);
            TAGS = (res.data.tags || []).map((t) => ({ ...t, bySeason: t.bySeason || {}, byPlace: t.byPlace || {} }));
            SEASONS = res.data.seasons || [];
            paintSeg();
            paintCloud(FIRST && !still());
            FIRST = false;
            if (reopen && TAGS.some((t) => t.key === reopen)) openShelf(reopen);
            else if (OPEN && !TAGS.some((t) => t.key === OPEN)) closeShelf();
        } catch (err) {
            $id('gtSays').textContent = 'Could not gather your tags — try again in a moment.';
            toast(err.message, 'error');
        }
    }

    // ---- the shelf -------------------------------------------------------
    function spread(c) {
        const bits = [];
        if (c.season) bits.push(`${plural(c.season, 'thing')} in ${plural(c.seasons, 'season')}`);
        if (c.global) bits.push(`${plural(c.global, 'thing')} outside a season`);
        return bits.join(' and ');
    }

    function paintItems(animate) {
        const shown = ITEMS.filter(itemShows);
        $id('gtItems').innerHTML = shown.map((it, i) => {
            const where = it.where === 'season'
                ? `<span class="gt-where">In <span class="gt-schip">🌾 ${esc(it.place)}</span></span>`
                : `<span class="gt-where is-out">Not in a cropping schedule — <b>${esc(it.place)}</b></span>`;
            return `<a class="gt-item${animate ? ' gt-in' : ''}" href="${esc(it.url)}" data-where="${it.where}"
                    ${animate ? `style="animation-delay:${Math.min(i, 20) * 18}ms"` : ''}>
                <span class="e">${it.icon || '🏷️'}</span>
                <span class="min-w-0 grow"><b>${esc(it.title)}</b><i>${esc(it.sub || '')}</i>${where}</span>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>`;
        }).join('');
        const empty = $id('gtShelfEmpty');
        if (shown.length) { empty.hidden = true; return; }
        empty.textContent = ITEMS.length
            ? 'Nothing here under this filter — try All.'
            : (COUNTS && COUNTS.seasonTags
                ? 'This tag is on your seasons\' tag lists but not tied to anything yet.'
                : 'This tag is not on anything yet.');
        empty.hidden = false;
    }

    function paintShelfHead(name) {
        $id('gtShelfName').textContent = '🏷️ ' + name;
        if (!COUNTS) { $id('gtShelfSays').textContent = 'Gathering what wears it…'; return; }
        const total = COUNTS.season + COUNTS.global;
        $id('gtShelfSays').textContent = total ? `${plural(total, 'thing')} — ${spread(COUNTS)}` : 'Not on anything yet';
    }

    async function openShelf(key) {
        const t = TAGS.find((x) => x.key === key);
        if (!t) return;
        OPEN = key;
        COUNTS = null; ITEMS = [];
        paintCloud(false);
        paintShelfHead(t.name);
        $id('gtRenameBtn').disabled = true;
        $id('gtDeleteBtn').disabled = true;
        $id('gtItems').innerHTML = '<p class="gt-wait">Gathering what wears it…</p>';
        $id('gtShelfEmpty').hidden = true;
        const shelf = $id('gtShelf');
        const wasOpen = shelf.classList.contains('is-open');
        shelf.classList.add('is-open');
        if (!wasOpen) setTimeout(() => shelf.scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'nearest' }), 300);
        const seq = ++SEQ;
        try {
            const res = await api(U.items + '?name=' + encodeURIComponent(t.name));
            if (seq !== SEQ || OPEN !== key) return;
            ITEMS = res.data.items || [];
            COUNTS = res.data.counts;
            // The cloud counted ties; the shelf counted the living things.
            // Where a tied thing has since been deleted they differ — the
            // shelf is the truth, so the cloud takes its numbers.
            const bySeason = {}, byPlace = {};
            ITEMS.forEach((it) => {
                if (it.where === 'season') bySeason[it.seasonId] = (bySeason[it.seasonId] || 0) + 1;
                else byPlace[it.place] = (byPlace[it.place] || 0) + 1;
            });
            Object.keys(t.bySeason).forEach((sid) => { if (!(sid in bySeason)) bySeason[sid] = 0; });
            Object.assign(t, { season: COUNTS.season, global: COUNTS.global, count: COUNTS.season + COUNTS.global, bySeason, byPlace });
            paintCloud(false);
            paintShelfHead(res.data.tag.name || t.name);
            paintItems(!still());
            $id('gtRenameBtn').disabled = false;
            $id('gtDeleteBtn').disabled = false;
        } catch (err) {
            if (seq !== SEQ) return;
            $id('gtItems').innerHTML = '';
            toast(err.message, 'error');
        }
    }

    function closeShelf() {
        OPEN = null; SEQ++;
        $id('gtShelf').classList.remove('is-open');
        paintCloud(false);
    }

    // ---- wiring ----------------------------------------------------------
    $id('gtCloud').addEventListener('click', (e) => {
        const b = e.target.closest('[data-gt]');
        if (!b) return;
        const key = b.dataset.gt;
        if (OPEN === key) closeShelf(); else openShelf(key);
    });

    document.querySelectorAll('[data-gt-mode]').forEach((b) => b.addEventListener('click', () => {
        if (MODE === b.dataset.gtMode) return;
        MODE = b.dataset.gtMode; NARROW = '';
        paintSeg(); paintCloud(!still());
        if (OPEN) paintItems(!still());
    }));

    $id('gtNarrowBtn').addEventListener('click', () => {
        let rows;
        if (MODE === 'season') {
            $id('gtNarrowTitle').textContent = 'Which season?';
            const withTags = SEASONS.filter((s) => TAGS.some((t) => String(s.id) in t.bySeason));
            rows = [{ v: '', e: '🌾', t: 'All seasons', i: plural(withTags.length, 'season') }]
                .concat(withTags.map((s) => ({ v: String(s.id), e: '🌾', t: s.title,
                    i: plural(TAGS.filter((t) => String(s.id) in t.bySeason).length, 'tag') })));
        } else {
            $id('gtNarrowTitle').textContent = 'Which tool?';
            rows = [{ v: '', e: '🧰', t: 'All tools outside a season', i: 'contacts, protocols, analyses, notes' }]
                .concat(places().map((p) => ({ v: p, e: '🧰', t: p,
                    i: plural(TAGS.filter((t) => p in t.byPlace).length, 'tag') })));
        }
        $id('gtNarrowList').innerHTML = rows.map((r) => `
            <button type="button" class="dt-row${String(NARROW) === r.v ? ' is-on' : ''}" data-gt-narrow="${esc(r.v)}">
                <span class="dt-row-e">${r.e}</span>
                <span class="dt-row-body"><b>${esc(r.t)}</b><i>${esc(r.i)}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        openSheet('gtNarrowSheet');
    });
    $id('gtNarrowList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-gt-narrow]');
        if (!r) return;
        NARROW = r.dataset.gtNarrow;
        closeSheet('gtNarrowSheet');
        paintSeg(); paintCloud(!still());
        if (OPEN) paintItems(!still());
    });

    let deb = null;
    $id('gtSearch').addEventListener('input', () => {
        $id('gtSearchX').classList.toggle('hidden', !$id('gtSearch').value);
        clearTimeout(deb);
        deb = setTimeout(() => { Q = keyOf($id('gtSearch').value); paintCloud(!still()); }, 150);
    });
    $id('gtSearchX').addEventListener('click', () => {
        $id('gtSearch').value = ''; Q = '';
        $id('gtSearchX').classList.add('hidden');
        paintCloud(!still());
        $id('gtSearch').focus();
    });

    // A season thing opens inside its season — which, for someone working on
    // another farm right now, is a farm switch away.
    $id('gtItems').addEventListener('click', (e) => {
        const a = e.target.closest('.gt-item');
        if (a && IN_WORKER && a.dataset.where === 'season') {
            e.preventDefault();
            toast('That season is on your own farm — switch to it from the 🏡 menu to open it.');
        }
    });

    // ---- rename, everywhere ----------------------------------------------
    $id('gtRenameBtn').addEventListener('click', () => {
        const t = TAGS.find((x) => x.key === OPEN);
        if (!t || !COUNTS) return;
        const inp = $id('gtRenameInput');
        inp.value = t.name.slice(0, 30);
        const total = COUNTS.season + COUNTS.global;
        $id('gtRenameSays').textContent = total
            ? `The new name reaches all ${plural(total, 'thing')} — ${spread(COUNTS)}.`
            : 'The new name reaches every season and tool that lists this tag.';
        openSheet('gtRenameSheet');
        window.smFocus?.(inp, { delay: 250 });
    });

    async function doRename() {
        const t = TAGS.find((x) => x.key === OPEN);
        if (!t || !COUNTS) return;
        const to = $id('gtRenameInput').value.replace(/\s+/g, ' ').trim().slice(0, 30);
        if (!to) { toast('Give the tag its new name.', 'error'); return; }
        if (to === t.name) { closeSheet('gtRenameSheet'); return; }
        const other = TAGS.find((x) => x.key === keyOf(to) && x.key !== t.key);
        const total = COUNTS.season + COUNTS.global;
        if (!window.confirmAction) { toast('Could not open the confirmation — try again.', 'error'); return; }
        const ok = await window.confirmAction({
            title: `Rename "${t.name}" to "${to}"?`,
            message: (total ? `This changes ${plural(total, 'thing')}: ${spread(COUNTS)}.` : 'Nothing wears it yet — only the name in your tag lists changes.')
                + (other ? ` You already have "${other.name}" — the two become one tag.` : ''),
            confirmText: 'Rename',
            confirmClass: 'btn-primary',
        });
        if (!ok) return;
        const btn = $id('gtRenameSave');
        btn.disabled = true;
        try {
            const res = await api(U.rename, { method: 'POST', body: { name: t.name, to } });
            closeSheet('gtRenameSheet');
            toast(`Renamed — ${total ? plural(total, 'thing') + ' now say' : 'it now says'} "${res.data.name}".`);
            OPEN = null;
            await load(res.data.key);
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    }
    $id('gtRenameSave').addEventListener('click', doRename);
    $id('gtRenameInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doRename(); } });

    // ---- delete, everywhere ----------------------------------------------
    $id('gtDeleteBtn').addEventListener('click', async () => {
        const t = TAGS.find((x) => x.key === OPEN);
        if (!t || !COUNTS) return;
        const total = COUNTS.season + COUNTS.global;
        // The question is not optional: with no sheet the answer is no.
        if (!window.confirmAction) { toast('Could not open the confirmation — try again.', 'error'); return; }
        const ok = await window.confirmAction({
            title: `Delete "${t.name}" everywhere?`,
            message: total
                ? `It comes off ${plural(total, 'thing')}: ${spread(COUNTS)}. The things themselves stay exactly where they are.`
                : 'Nothing wears it yet — it just leaves your tag lists.',
            confirmText: 'Delete tag',
        });
        if (!ok) return;
        const btn = $id('gtDeleteBtn');
        btn.disabled = true;
        try {
            await api(U.del, { method: 'POST', body: { name: t.name } });
            toast(total ? `Tag removed from ${plural(total, 'thing')}.` : 'Tag removed.');
            closeShelf();
            await load();
        } catch (err) { toast(err.message, 'error'); btn.disabled = false; }
    });

    // ?tag=word opens that word's shelf on arrival.
    const want = new URLSearchParams(location.search).get('tag');
    load(want ? keyOf(want) : null);
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
