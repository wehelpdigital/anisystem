@extends('layouts.admin')

@section('title', 'Reports')
@section('subtitle', 'What the community flagged')

@section('content')
    <div class="sticky top-[6.4rem] z-30 -mx-1 px-1 pb-2 bg-gray-50 dark:bg-[#0c1108]">
        <div class="flex gap-1.5 overflow-x-auto" id="rpChips">
            <button type="button" class="chip is-selected" data-status="open">Open <span id="rpOpen"></span></button>
            <button type="button" class="chip" data-status="handled">Handled</button>
            <button type="button" class="chip" data-status="dismissed">Dismissed</button>
            <button type="button" class="chip" data-status="">All</button>
        </div>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div id="rpList"></div>
        <div id="rpEmpty" class="hidden text-center py-10">
            <p class="font-bold text-gray-900 dark:text-gray-100">Nothing flagged</p>
            <p class="text-sm text-gray-400">A quiet plaza is a healthy one.</p>
        </div>
    </div>
    <div class="ad-more" id="rpMore" hidden><span class="ad-spin"></span> Loading more…</div>
@endsection

@push('head')
<style>
    .rp-row { padding: .8rem .85rem; border-bottom: 1px solid var(--color-gray-100); }
    .rp-row:last-child { border-bottom: 0; }
    .rp-line { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .rp-type { display: inline-flex; font-size: .62rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .03em; padding: .12rem .45rem; border-radius: 999px;
        background: #eaf1fd; color: #1d4ed8; }
    .rp-snap { margin: .4rem 0; padding: .5rem .7rem; border-radius: .6rem; font-size: .8rem;
        background: var(--color-gray-50); border-left: 3px solid var(--color-gray-200);
        color: var(--color-gray-600); }
    .rp-who { font-size: .73rem; color: var(--color-gray-400); }
    .rp-acts { display: flex; gap: .4rem; margin-top: .5rem; flex-wrap: wrap; }
    html.dark .rp-row { border-color: #222b1a; }
    html.dark .rp-type { background: #16202f; color: #9fc0f5; }
    html.dark .rp-snap { background: #131a0e; border-color: #2b3a1c; color: #a8bd93; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const U = {
        list: '{{ route('admin.data.reports') }}',
        status: (id) => '{{ url('/admin/report') }}/' + id + '/status',
    };
    let status = 'open';

    const row = (r) => `
        <div class="rp-row" data-report="${r.id}">
            <div class="rp-line">
                <span class="rp-type">${esc(r.type)}</span>
                <b class="text-sm text-gray-900 dark:text-gray-100">${esc(r.reason)}</b>
                ${r.status !== 'open' ? `<span class="ad-badge is-closed">${esc(r.status)}</span>` : ''}
                <span class="rp-who ml-auto">${esc(r.at || '')}</span>
            </div>
            ${r.snapshot ? `<div class="rp-snap">“${esc(r.snapshot)}”</div>` : ''}
            ${r.details ? `<p class="text-xs text-gray-500 dark:text-gray-400">${esc(r.details)}</p>` : ''}
            <p class="rp-who">Reported by <b>${esc(r.reporter)}</b>${r.target ? ` · about <b>${esc(r.target)}</b>` : ''}</p>
            <div class="rp-acts">
                <a class="btn btn-white btn-sm" href="${esc(r.url)}" target="_blank" rel="noopener">Open where it lives ↗</a>
                ${r.targetUserId ? `<a class="btn btn-white btn-sm" href="{{ route('admin.clients') }}" title="Suspensions live in the client's own sheet">Client tools</a>` : ''}
                ${r.status === 'open' ? `
                    <button type="button" class="btn btn-primary btn-sm" data-rp-set="handled">Mark handled</button>
                    <button type="button" class="btn btn-white btn-sm" data-rp-set="dismissed">Dismiss</button>`
                : `<button type="button" class="btn btn-white btn-sm" data-rp-set="open">Reopen</button>`}
            </div>
        </div>`;

    const feed = adminFeed({
        url: U.list,
        listId: 'rpList',
        moreId: 'rpMore',
        params: () => ({ status }),
        render: (rows, data) => {
            $id('rpList').insertAdjacentHTML('beforeend', rows.map(row).join(''));
            $id('rpEmpty').classList.add('hidden');
            if (data.counts) $id('rpOpen').textContent = data.counts.open ? `(${data.counts.open})` : '';
        },
        empty: () => $id('rpEmpty').classList.remove('hidden'),
    });

    $id('rpChips').addEventListener('click', (e) => {
        const chip = e.target.closest('[data-status]');
        if (!chip) return;
        status = chip.getAttribute('data-status');
        document.querySelectorAll('#rpChips .chip').forEach((c) => c.classList.toggle('is-selected', c === chip));
        feed.reset();
    });

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-rp-set]');
        if (!b) return;
        b.disabled = true;
        try {
            const id = b.closest('[data-report]').getAttribute('data-report');
            const res = await api(U.status(id), { method: 'PUT', body: { status: b.getAttribute('data-rp-set') } });
            toast(res.message);
            feed.reset();
        } catch (err) { toast(err.message, 'error'); b.disabled = false; }
    });
})();
</script>
@endpush
