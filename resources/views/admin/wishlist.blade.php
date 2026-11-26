@extends('layouts.admin')

@section('title', 'Unang 50')
@section('subtitle', 'The farmers\' wishlist: who answered, and what they wish for')

@push('head')
<style>
    .wl-list { display: flex; flex-direction: column; gap: .5rem; }
    .wl-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .15rem .6rem; align-items: baseline; }
    .wl-name { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wl-num { font-size: .72rem; font-weight: 600; color: var(--color-gray-500); white-space: nowrap; }
    .wl-num b { color: var(--color-gray-900); font-weight: 800; }
    .wl-track { grid-column: 1 / -1; height: .45rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .wl-fill { display: block; height: 100%; width: 0; border-radius: 999px; background: var(--color-brand-500); transition: width .28s cubic-bezier(.22,1,.36,1); }
    .wl-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-bottom: .7rem; }
    .wl-h p:first-child { font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .wl-note { font-size: .72rem; color: var(--color-gray-500); }
    .wl-split { display: grid; gap: .75rem; }
    @media (min-width: 768px) { .wl-split { grid-template-columns: 1fr 1fr; } }
    .wl-status { display: inline-flex; padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; }
    .wl-status.s-new { background: #e0ecff; color: #1e40af; } .wl-status.s-contacted { background: #fef3c7; color: #92400e; }
    .wl-status.s-accepted { background: #e7f5dc; color: #2d5016; } .wl-status.s-notfit { background: var(--color-gray-100); color: var(--color-gray-500); }
    html.dark .wl-status.s-new { background: rgb(30 64 175 / .3); color: #bfdbfe; } html.dark .wl-status.s-contacted { background: rgb(146 64 14 / .3); color: #fde68a; }
    html.dark .wl-status.s-accepted { background: rgb(107 159 61 / .25); color: #c9e0ad; }
    .wl-wish { font-size: .78rem; color: var(--color-gray-600); font-style: italic; overflow-wrap: anywhere; }
    .wl-dl { display: grid; grid-template-columns: 8.5rem minmax(0, 1fr); gap: .35rem .7rem; font-size: .82rem; }
    .wl-dl dt { color: var(--color-gray-500); font-weight: 600; }
    .wl-dl dd { margin: 0; color: var(--color-gray-900); font-weight: 700; overflow-wrap: anywhere; }
    .wl-quote { padding: .8rem .9rem; border-radius: .9rem; background: var(--color-brand-50); color: var(--color-brand-800); font-weight: 700; font-style: italic; line-height: 1.5; }
    html.dark .wl-quote { background: rgb(107 159 61 / .15); color: #c9e0ad; }
    @media (prefers-reduced-motion: reduce) { .wl-fill { transition: none; } }
</style>
@endpush

@section('bar')
    <div class="ad-chips" id="wlFilter" role="group" aria-label="Show">
        <button type="button" class="chip is-selected" data-f="all">Everyone</button>
        @foreach ($statuses as $k => $label)
            <button type="button" class="chip" data-f="{{ $k }}">{{ $label }}</button>
        @endforeach
    </div>
@endsection

@section('content')
    <div id="wlBody">
        <div class="ad-stats">
            @for ($i = 0; $i < 4; $i++)
                <div class="card ad-stat"><div class="ad-skel h-6 w-16 mb-2"></div><div class="ad-skel h-3 w-24"></div></div>
            @endfor
        </div>
        <div class="card p-4 mt-3"><div class="ad-skel w-full h-40"></div></div>
    </div>
@endsection

@push('sheets')
<div class="sheet hidden" id="wlSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="wlTitle">Farmer</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4" id="wlSheetBody"></div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const URL_DATA = @json(route('admin.data.wishlist'));
    const URL_UPDATE = @json(url('/admin/wishlist')) + '/';
    const URL_EXPORT = @json(route('admin.wishlist.export'));
    const STATUSES = @json($statuses);
    let D = null, filter = 'all';
    const plural = (n, one, many) => Number(n).toLocaleString() + ' ' + (n === 1 ? one : (many || one + 's'));
    const bars = (items, empty) => items && items.length ? '<div class="wl-list">' + items.map((x) => `
        <div class="wl-item"><span class="wl-name">${esc(x.label)}</span><span class="wl-num"><b>${x.n}</b> · ${x.share}%</span>
        <span class="wl-track"><span class="wl-fill" data-w="${Math.max(2, x.share)}%"></span></span></div>`).join('') + '</div>' : `<p class="wl-note">${esc(empty)}</p>`;
    const card = (title, note, items) => `<div class="card p-4"><div class="wl-h"><p>${title}</p><p class="wl-note">${note}</p></div>${bars(items, 'No answers yet.')}</div>`;

    function paint() {
        const d = D, t = d.totals;
        const list = d.entries.filter((e) => filter === 'all' || e.status === filter);
        $id('wlBody').innerHTML = `
            <div class="ad-stats ad-rise">
                <div class="card ad-stat"><b>${d.count}</b><span>Farmers answered</span><small>${d.spots > 0 ? d.spots + ' of ' + d.limit + ' places left' : 'The first ' + d.limit + ' are full'}</small></div>
                <div class="card ad-stat"><b>${d.accepted}</b><span>In the First 50</span></div>
                <div class="card ad-stat"><b>${d.entries.filter((e) => e.status === 'new').length}</b><span>Not contacted yet</span></div>
                <div class="card ad-stat"><b>${d.entries.reduce((s, e) => s + e.friends, 0)}</b><span>Came through a friend's link</span></div>
            </div>
            <div class="card p-4 mt-3 flex flex-wrap gap-2 items-center">
                <span class="text-sm font-bold text-gray-900 grow">The page to share: <a class="underline break-all" href="${esc(d.link)}" target="_blank" rel="noopener">${esc(d.link)}</a></span>
                <button type="button" class="btn btn-white btn-sm" id="wlCopy">Copy link</button>
                <a class="btn btn-white btn-sm" href="${URL_EXPORT}">Download spreadsheet</a>
            </div>
            <div class="wl-split mt-3">
                ${card('What hurts most', 'Up to three each, by farmers', t.pains)}
                ${card('What they wish for', 'Their picks, by farmers', t.wishes)}
            </div>
            <div class="wl-split mt-3">
                ${card('How they would pay', 'One each', t.payWay)}
                ${card('Fair for a season', 'One each', t.payAmount)}
            </div>
            <div class="wl-split mt-3">
                ${card('What they grow', 'All they named', t.crops)}
                ${card('Who they are', 'One each', t.role)}
            </div>
            <div class="wl-split mt-3">
                ${card('Where', 'Top provinces', t.province)}
                ${card('How they heard', 'One each', t.heardFrom)}
            </div>
            <div class="wl-split mt-3">
                ${card('Farm size', 'One each', t.farmSize)}
                ${card('Signal on the farm', 'One each', t.signal)}
            </div>
            <div class="card !p-0 overflow-hidden mt-3">
                <div class="wl-h p-4 !mb-0"><p>The farmers</p><p class="wl-note">${plural(list.length, 'farmer')} · in line order is the place number</p></div>
                ${list.length ? list.map((e) => `
                    <button type="button" class="ad-row" data-entry="${e.id}">
                        <span class="ad-face">#${e.place}</span>
                        <span class="ad-mid">
                            <span class="ad-namerow"><span class="ad-name">${esc(e.name)}</span><span class="wl-status s-${esc(e.status)}">${esc(STATUSES[e.status] || e.status)}</span>${e.friends ? `<span class="ad-badge is-role">+${e.friends} friends</span>` : ''}</span>
                            <span class="ad-meta">${esc([e.role, e.where, e.crops.join(', ')].filter(Boolean).join(' · '))}</span>
                            ${e.wishText ? `<span class="wl-wish">“${esc(e.wishText.length > 120 ? e.wishText.slice(0, 118) + '…' : e.wishText)}”</span>` : ''}
                        </span>
                        <span class="ad-end text-xs text-gray-400">${esc(e.ago)}</span>
                    </button>`).join('') : '<p class="wl-note p-4">Nobody here yet.</p>'}
            </div>`;
        requestAnimationFrame(() => requestAnimationFrame(() => document.querySelectorAll('.wl-fill[data-w]').forEach((b) => { b.style.width = b.getAttribute('data-w'); })));
        window.adminRise?.($id('wlBody'));
    }

    function openEntry(id) {
        const e = D.entries.find((x) => String(x.id) === String(id));
        if (!e) return;
        $id('wlTitle').textContent = '#' + e.place + ' ' + e.name;
        const tel = (e.phone || '').replace(/[^\d+]/g, '');
        const row = (k, v) => v && (!Array.isArray(v) || v.length) ? `<dt>${k}</dt><dd>${esc(Array.isArray(v) ? v.join(', ') : v)}</dd>` : '';
        $id('wlSheetBody').innerHTML = `
            <div class="flex flex-wrap gap-1.5">
                <span class="wl-status s-${esc(e.status)}">${esc(STATUSES[e.status])}</span>
                <span class="badge badge-gray">answered ${esc(e.at)}</span>
                ${e.answers > 1 ? `<span class="badge badge-gray">sent ${e.answers} times</span>` : ''}
                <span class="badge badge-gray">${esc(e.lang)} · ${esc(e.device || '')}</span>
                ${e.referredBy ? `<span class="badge badge-blue">came through ${esc(e.referredBy)}</span>` : ''}
                ${e.friends ? `<span class="badge badge-green">brought ${e.friends}</span>` : ''}
            </div>
            <div class="card p-3.5 space-y-2">
                <p class="font-bold text-sm text-gray-900">Reach them</p>
                <dl class="wl-dl">${row('Phone', e.phone)}${row('Email', e.email)}${row('Prefers', e.contactVia)}${row('Best time', e.contactTime)}</dl>
                <div class="flex flex-wrap gap-2">
                    ${tel ? `<a class="btn btn-white btn-sm" href="tel:${esc(tel)}">Call</a><a class="btn btn-white btn-sm" href="sms:${esc(tel)}">Text</a><a class="btn btn-white btn-sm" href="viber://chat?number=${encodeURIComponent(tel)}">Viber</a>` : ''}
                    ${e.email ? `<a class="btn btn-white btn-sm" href="mailto:${esc(e.email)}">Email</a>` : ''}
                </div>
            </div>
            ${e.wishText ? `<div class="wl-quote">“${esc(e.wishText)}”</div>` : ''}
            <div class="card p-3.5">
                <dl class="wl-dl">
                    ${row('Who', e.role)}${row('Where', e.where)}${row('Grows', e.crops)}${row('Plants a year', e.seasons)}
                    ${row('Farm size', e.farmSize)}${row('Lots', e.lots)}${row('Helpers', e.helpers)}${row('Hardest', e.pains)}
                    ${row('Wishes', e.wishes)}${row('Last harvest', e.yield)}${row('Phone', e.phoneType)}${row('Signal', e.signal)}
                    ${row('Would pay', e.payWay)}${row('Fair a season', e.payAmount)}${row('Heard from', e.heardFrom)}
                    ${e.utm ? row('From an ad', Object.entries(e.utm).map(([k, v]) => k.replace('utm_', '') + ': ' + v).join(' · ')) : ''}
                </dl>
            </div>
            <div class="card p-3.5 space-y-2.5">
                <p class="font-bold text-sm text-gray-900">Where they stand</p>
                <div class="flex flex-wrap gap-1.5" id="wlStat">${Object.entries(STATUSES).map(([k, v]) => `<button type="button" class="chip ${k === e.status ? 'is-selected' : ''}" data-status="${k}">${esc(v)}</button>`).join('')}</div>
                <textarea id="wlNote" class="form-input" rows="3" placeholder="A note for the team: what they said on the call, what to do next">${esc(e.note || '')}</textarea>
                <button type="button" class="btn btn-primary btn-sm w-full" id="wlSave" data-id="${e.id}">Save the note</button>
            </div>`;
        window.openSheet('wlSheet');
    }

    async function save(id, body) {
        try {
            await api(URL_UPDATE + id, { method: 'PUT', body });
            const e = D.entries.find((x) => x.id === Number(id));
            if (body.status) e.status = body.status;
            if ('note' in body) e.note = body.note;
            toast('Saved');
            paint();
        } catch (err) { toast(err.message, 'error'); }
    }
    document.addEventListener('click', (ev) => {
        const r = ev.target.closest('[data-entry]');
        if (r) return openEntry(r.dataset.entry);
        const s = ev.target.closest('[data-status]');
        if (s) {
            s.parentElement.querySelectorAll('.chip').forEach((c) => c.classList.toggle('is-selected', c === s));
            return save($id('wlSave').dataset.id, { status: s.dataset.status });
        }
        if (ev.target.closest('#wlSave')) return save(ev.target.closest('#wlSave').dataset.id, { note: $id('wlNote').value });
        if (ev.target.closest('#wlCopy')) { navigator.clipboard?.writeText(D.link).then(() => toast('Link copied'), () => {}); }
    });
    $id('wlFilter').addEventListener('click', (ev) => {
        const b = ev.target.closest('[data-f]');
        if (!b || !D) return;
        $id('wlFilter').querySelectorAll('.chip').forEach((c) => c.classList.toggle('is-selected', c === b));
        filter = b.dataset.f;
        paint();
    });

    async function load() {
        try {
            const res = await api(URL_DATA, { method: 'GET' });
            D = res.data;
            paint();
            const want = new URLSearchParams(location.search).get('entry');
            if (want) openEntry(want);
        } catch (err) { toast(err.message, 'error'); }
    }
    if (window.api) load(); else window.addEventListener('load', load, { once: true });
})();
</script>
@endpush
