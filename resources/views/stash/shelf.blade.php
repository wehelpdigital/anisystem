@extends('layouts.app')
@section('title', $type['label'] . ' · ' . $partner['name'])
@section('page-title', $type['label'])
@section('page-subtitle', 'The Stash · ' . $partner['name'])
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
<style>
    /* ---- A Stash shelf (2026-10-07): covers in a grid that grows as you
       scroll, searched by words and year. House curve; still when asked. */
    .ss-wrap { max-width: 64rem; margin: 0 auto; }
    .ss-hero { display: flex; gap: 1rem; align-items: center; padding: 1rem 1.1rem; border-radius: 1.3rem; color: #eaf3df;
        background: radial-gradient(120% 140% at 100% 0%, #3f8a3a 0%, #1f4d1c 55%, #0f2a0d 100%); }
    .ss-hero img { flex: none; width: 4rem; height: 4rem; border-radius: 1rem; box-shadow: 0 10px 24px -12px rgb(0 0 0 / .7); }
    .ss-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
    .ss-hero p { margin-top: .25rem; font-size: .82rem; line-height: 1.5; color: #cfe4c4; }
    .ss-hero a { color: #fde68a; font-weight: 700; }
    .ss-tools { position: sticky; top: var(--app-head, 3.6rem); z-index: 5; margin: .9rem -1rem 0; padding: .6rem 1rem; display: grid; gap: .5rem;
        background: rgb(249 250 251 / .9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
    html.dark .ss-tools { background: rgb(13 17 10 / .9); }
    @media (min-width: 640px) { .ss-tools { margin: .9rem 0 0; padding: .6rem 0; } }
    .ss-search { position: relative; }
    .ss-search svg { position: absolute; left: .85rem; top: 50%; width: 1.05rem; height: 1.05rem; transform: translateY(-50%); color: var(--color-gray-400); pointer-events: none; }
    .ss-search input { padding-left: 2.5rem; }
    .ss-row { display: flex; gap: .35rem; align-items: center; overflow-x: auto; scrollbar-width: none; }
    .ss-row::-webkit-scrollbar { display: none; }
    .ss-chip { flex: none; padding: .38rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 800; cursor: pointer; color: var(--color-gray-600); background: var(--color-white);
        border: 1px solid var(--color-gray-200); transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ss-chip.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .ss-sort { margin-left: auto; flex: none; }
    .ss-count { font-size: .76rem; color: var(--color-gray-500); margin: .7rem 0 .2rem; }
    .ss-grid { display: grid; gap: .9rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 640px) { .ss-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 960px) { .ss-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ss-item { display: flex; flex-direction: column; min-width: 0; text-decoration: none; border-radius: 1rem; overflow: hidden; background: var(--color-white);
        border: 1px solid var(--color-gray-200); animation: ssIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 30ms);
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ss-item:hover { transform: translateY(-3px); box-shadow: 0 18px 30px -22px rgb(0 0 0 / .6); }
    @keyframes ssIn { from { opacity: 0; transform: translateY(10px); } }
    .ss-cover { position: relative; aspect-ratio: 3 / 4; background: var(--color-gray-100); overflow: hidden; }
    .ss-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .ss-item:hover .ss-cover img { transform: scale(1.04); }
    .ss-cover em { position: absolute; left: .45rem; bottom: .45rem; padding: .18rem .5rem; border-radius: 999px; font-style: normal; font-size: .64rem; font-weight: 800; color: #fff; background: rgb(15 29 8 / .78); }
    .ss-cover em.is-wait { background: rgb(180 83 9 / .9); }
    .ss-body { padding: .6rem .65rem .7rem; }
    .ss-body b { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .86rem; line-height: 1.3; font-weight: 800; color: var(--color-gray-900); }
    .ss-body small { display: block; margin-top: .2rem; font-size: .72rem; color: var(--color-gray-500); }
    .ss-item.is-wait { cursor: default; }
    .ss-item.is-wait:hover { transform: none; box-shadow: none; }
    .ss-skel { border-radius: 1rem; aspect-ratio: 3 / 5; background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-50) 50%, var(--color-gray-100) 60%) 0 0 / 200% 100%;
        animation: ssShine 1.2s linear infinite; }
    @keyframes ssShine { to { background-position: -200% 0; } }
    .ss-empty { padding: 2.5rem 1rem; text-align: center; font-size: .86rem; color: var(--color-gray-500); }
    .ss-note { margin-top: 1.2rem; font-size: .72rem; line-height: 1.5; color: var(--color-gray-400); text-align: center; }
    @media (prefers-reduced-motion: reduce) { .ss-item, .ss-skel { animation: none; } .ss-item, .ss-cover img, .ss-chip { transition: none; } }
</style>

<div class="ss-wrap">
    <div class="ss-hero">
        <img src="{{ asset($partner['logo']) }}" alt="{{ $partner['name'] }} logo" width="64" height="64">
        <div>
            <h2>{{ $partner['name'] }} {{ $type['label'] }}</h2>
            <p>{{ $type['about'] }} Shared with anee.io farmers by <a href="{{ $partner['url'] }}" target="_blank" rel="noopener">{{ $partner['full'] }}</a>.</p>
        </div>
    </div>
    <div class="ss-tools">
        <label class="ss-search">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input type="search" id="ssQ" class="form-input w-full" placeholder="Search titles and topics" autocomplete="off" enterkeyhint="search">
        </label>
        <div class="ss-row" id="ssYears">
            <button type="button" class="ss-chip is-on" data-year="0">All years</button>
            @foreach ($years as $y)<button type="button" class="ss-chip" data-year="{{ $y }}">{{ $y }}</button>@endforeach
            <button type="button" class="ss-chip ss-sort" id="ssSort" data-order="new">Newest first</button>
        </div>
    </div>
    <p class="ss-count" id="ssCount" aria-live="polite"></p>
    <div class="ss-grid" id="ssGrid"></div>
    <div id="ssMore" aria-hidden="true" style="height:1px"></div>
    <p class="ss-note">The magazines belong to {{ $partner['full'] }} and are shared here for reading. Dates are when each issue was posted.</p>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const U = @json(route('stash.items', [$partnerKey, $typeKey]));
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const st = { q: '', year: 0, order: 'new', page: 1, more: true, busy: false, seq: 0, shown: 0 };
    const skel = (n) => Array.from({ length: n }, () => '<div class="ss-skel"></div>').join('');
    const card = (x, i) => (x.ready ? '<a class="ss-item" href="' + esc(x.url) + '"' : '<div class="ss-item is-wait"') + ' style="--i:' + (i % 18) + '" title="' + esc(x.blurb || x.title) + '">'
        + '<span class="ss-cover">' + (x.cover ? '<img src="' + esc(x.cover) + '" alt="Cover of ' + esc(x.title) + '" loading="lazy">' : '')
        + (x.ready ? (x.mb ? '<em>' + esc(x.mb) + ' MB</em>' : '') : '<em class="is-wait">Getting ready</em>') + '</span>'
        + '<span class="ss-body"><b>' + esc(x.title) + '</b><small>' + esc(x.date || 'Undated') + '</small></span>' + (x.ready ? '</a>' : '</div>');
    const load = async (reset) => {
        if (!window.api || (st.busy && !reset)) return;
        if (reset) { st.page = 1; st.more = true; st.shown = 0; $('ssGrid').innerHTML = skel(6); }
        if (!st.more) return;
        const seq = ++st.seq;
        st.busy = true;
        try {
            const r = await window.api(U + '?page=' + st.page + '&q=' + encodeURIComponent(st.q) + '&year=' + st.year + '&order=' + (st.order === 'old' ? 'old' : 'new'));
            if (seq !== st.seq) return;
            const rows = r.data.rows || [];
            if (st.page === 1) $('ssGrid').innerHTML = '';
            $('ssGrid').insertAdjacentHTML('beforeend', rows.map(card).join(''));
            st.shown += rows.length;
            st.more = !!r.data.hasMore;
            st.page++;
            $('ssCount').textContent = st.shown ? (st.more ? 'Showing ' + st.shown + ', scroll for more' : st.shown + (st.shown === 1 ? ' issue' : ' issues')) : '';
            if (!st.shown) $('ssGrid').innerHTML = '<p class="ss-empty" style="grid-column:1/-1">Nothing matches that. Try other words or all years.</p>';
        } catch (err) { if (seq === st.seq) window.toast?.(err.message, 'error'); }
        finally { if (seq === st.seq) st.busy = false; }
    };
    let t = null;
    $('ssQ').addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { st.q = $('ssQ').value.trim(); load(true); }, 280); });
    $('ssYears').addEventListener('click', (e) => {
        const b = e.target.closest('.ss-chip');
        if (!b) return;
        if (b.id === 'ssSort') { st.order = st.order === 'new' ? 'old' : 'new'; b.textContent = st.order === 'new' ? 'Newest first' : 'Oldest first'; load(true); return; }
        st.year = Number(b.dataset.year);
        document.querySelectorAll('#ssYears [data-year]').forEach((x) => x.classList.toggle('is-on', x === b));
        load(true);
    });
    new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting) && st.more && !st.busy) load(false); }, { rootMargin: '600px 0px' }).observe($('ssMore'));
    const boot = () => load(true);
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
