@extends('layouts.admin')

@section('title', 'Orders')
@section('subtitle', 'Payments by GCash, bank and PayPal')

@section('bar')
    <div class="ad-chips" id="orChips">
        <button type="button" class="chip is-selected" data-status="review">To review <span>{{ ($counts['review'] ?? 0) ? '(' . $counts['review'] . ')' : '' }}</span></button>
        <button type="button" class="chip" data-status="approved">Approved</button>
        <button type="button" class="chip" data-status="rejected">Rejected</button>
        <button type="button" class="chip" data-status="revoked">Revoked</button>
        <button type="button" class="chip" data-status="awaiting">Not paid yet</button>
        <button type="button" class="chip" data-status="all">All</button>
    </div>
    <input type="search" id="orSearch" class="form-input mt-2" placeholder="Order no., Ref No., name or email" autocomplete="off">
@endsection

@section('content')
    <div class="card !p-0 overflow-hidden">
        <div id="orList"></div>
        <div id="orEmpty" class="hidden text-center py-10">
            <p class="font-bold text-gray-900">Nothing here</p>
            <p class="text-sm text-gray-400">Payments land here the moment a buyer sends their proof.</p>
        </div>
    </div>
    <div class="ad-more" id="orMore" hidden><span class="ad-spin"></span> Loading more…</div>
@endsection

@push('head')
<style>
    .or-row { display: flex; align-items: center; gap: .75rem; padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .or-row:last-child { border-bottom: 0; }
    .or-row:hover { background: var(--color-gray-50); }
    .or-meth { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; font-weight: 900; color: #fff; }
    .or-meth.gcash { background: #0a58f5; } .or-meth.bank { background: #2d5016; } .or-meth.paypal { background: #003087; }
    .or-main { min-width: 0; flex: 1; }
    .or-main b { display: block; font-size: .92rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .or-main small { display: block; font-size: .74rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .or-side { text-align: right; flex: none; }
    .or-side b { display: block; font-size: .92rem; color: var(--color-gray-900); }
    .or-pill { display: inline-flex; align-items: center; gap: .25rem; font-size: .66rem; font-weight: 800; padding: .12rem .5rem; border-radius: 999px; text-transform: uppercase; letter-spacing: .03em; }
    .or-pill.review { background: #fff4d6; color: #8a5a00; } .or-pill.approved { background: #e6f4da; color: #2f6b1d; }
    .or-pill.rejected, .or-pill.revoked { background: #fde8e8; color: #b42318; } .or-pill.awaiting, .or-pill.cancelled { background: #f1f5f9; color: #64748b; }
    .or-ai { font-size: .66rem; font-weight: 800; padding: .12rem .45rem; border-radius: 999px; }
    .or-ai.pass { background: #e6f4da; color: #2f6b1d; } .or-ai.fail { background: #fde8e8; color: #b42318; } .or-ai.unsure, .or-ai.error { background: #fff4d6; color: #8a5a00; } .or-ai.skipped { background: #f1f5f9; color: #64748b; }
    /* The detail sheet */
    .od-sec { margin-top: 1rem; }
    .od-sec h4 { font-size: .72rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); margin-bottom: .45rem; }
    .od-facts { border-radius: .9rem; background: var(--color-gray-50); padding: .6rem .85rem; font-size: .86rem; }
    .od-facts div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; }
    .od-facts span { color: var(--color-gray-500); }
    .od-facts b { color: var(--color-gray-900); text-align: right; word-break: break-word; }
    .od-proof { border-radius: .9rem; overflow: hidden; border: 1px solid var(--color-gray-200); background: var(--color-gray-50); }
    .od-proof img { display: block; width: 100%; max-height: 32rem; object-fit: contain; cursor: zoom-in; }
    .od-proof .pdf { display: flex; align-items: center; gap: .7rem; padding: .9rem; }
    .od-verdict { display: block; padding: .7rem .85rem; border-radius: .9rem; font-size: .88rem; line-height: 1.45; }
    .od-verdict.pass { background: #e6f4da; color: #24521a; } .od-verdict.fail { background: #fde8e8; color: #8c1d18; }
    .od-verdict.unsure, .od-verdict.error { background: #fff4d6; color: #6b4600; } .od-verdict.skipped { background: #f1f5f9; color: #475569; }
    .od-checks { margin-top: .5rem; display: grid; gap: .35rem; }
    .od-check { display: flex; gap: .55rem; align-items: flex-start; font-size: .84rem; line-height: 1.4; }
    .od-check i { flex: none; width: 1.25rem; height: 1.25rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .72rem; font-weight: 900; margin-top: .05rem; }
    .od-check i.y { background: #e6f4da; color: #2f6b1d; } .od-check i.n { background: #fde8e8; color: #b42318; } .od-check i.q { background: #fff4d6; color: #8a5a00; }
    .od-check small { display: block; color: var(--color-gray-500); font-size: .78rem; }
    .od-time { display: grid; gap: .35rem; font-size: .82rem; }
    .od-time div { display: flex; gap: .6rem; } .od-time span { color: var(--color-gray-500); white-space: nowrap; }
    .od-reason { display: none; margin-top: .6rem; }
    .od-reason.is-on { display: block; animation: odIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes odIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    .od-acts { display: grid; gap: .5rem; grid-template-columns: 1fr 1fr; }
    .od-acts .btn { justify-content: center; }
    .od-acts .wide { grid-column: 1 / -1; }
    .od-box { position: fixed; inset: 0; z-index: 95; background: rgb(0 0 0 / .85); display: grid; place-items: center; padding: 1rem; opacity: 0; pointer-events: none; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .od-box.is-on { opacity: 1; pointer-events: auto; }
    .od-box img { max-width: 100%; max-height: 92vh; border-radius: .8rem; background: #fff; }
    html.dark .or-row:hover { background: #1b2616; }
    html.dark .od-facts b, html.dark .or-main b, html.dark .or-side b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .or-row, .od-box { transition: none; } .od-reason.is-on { animation: none; } }
</style>
@endpush

@push('sheets')
    <div class="sheet hidden" id="orSheet" style="--sheet-width:36rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div class="min-w-0">
                <h3 class="sheet-title truncate" id="orTitle">Order</h3>
                <p class="text-xs text-gray-500" id="orSubtitle"></p>
            </div>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body" id="orBody"></div>
        <div class="sheet-footer" id="orFoot"></div>
    </div>
    <div class="od-box" id="odBox"><img alt=""></div>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const U = {
        list: @json(route('admin.data.orders')),
        one: (id) => @json(url('/admin/data/orders')) + '/' + id,
        act: (id, a) => @json(url('/admin/orders')) + '/' + id + '/' + a,
    };
    let status = 'review';
    let q = '';
    let current = null;
    const peso = (o, n) => (o.currency === 'PHP' ? '₱' : '$') + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const aiWord = { pass: 'AI: passed', fail: 'AI: failed', unsure: 'AI: unsure', error: 'AI: could not read', skipped: 'AI: nothing to read' };

    const row = (o) => `
        <div class="or-row" data-order="${o.id}">
            <span class="or-meth ${esc(o.method)}" aria-hidden="true">${{ gcash: 'G', bank: '🏦', paypal: 'P' }[o.method] || '₱'}</span>
            <div class="or-main">
                <b>${esc(o.buyer)} · ${esc(o.itemName)}</b>
                <small>${esc(o.number)} · ${esc(o.at || '')}</small>
                <div class="mt-1 flex flex-wrap gap-1">
                    <span class="or-pill ${esc(o.status)}">${esc(o.statusLabel)}</span>
                    ${o.aiStatus ? `<span class="or-ai ${esc(o.aiStatus)}">${esc(aiWord[o.aiStatus] || o.aiStatus)}</span>` : ''}
                    ${o.decidedByAi ? '<span class="or-ai pass">Approved by Anee</span>' : ''}
                </div>
            </div>
            <div class="or-side"><b>${peso(o, o.total)}</b><small class="text-xs text-gray-500">${esc(o.methodLabel)}</small></div>
        </div>`;

    const feed = adminFeed({
        url: U.list,
        listId: 'orList',
        moreId: 'orMore',
        params: () => ({ status, q }),
        render: (rows) => {
            $id('orList').insertAdjacentHTML('beforeend', rows.map(row).join(''));
            $id('orEmpty').classList.add('hidden');
        },
        empty: () => $id('orEmpty').classList.remove('hidden'),
    });

    $id('orChips').addEventListener('click', (e) => {
        const chip = e.target.closest('[data-status]');
        if (!chip) return;
        status = chip.dataset.status;
        document.querySelectorAll('#orChips .chip').forEach((c) => c.classList.toggle('is-selected', c === chip));
        feed.reset();
    });
    let tq;
    $id('orSearch').addEventListener('input', (e) => { clearTimeout(tq); tq = setTimeout(() => { q = e.target.value.trim(); feed.reset(); }, 350); });

    // ---- one order
    function facts(rows) {
        return '<div class="od-facts">' + rows.filter((r) => r[1] !== null && r[1] !== undefined && r[1] !== '').map((r) => `<div><span>${esc(r[0])}</span><b>${esc(r[1])}</b></div>`).join('') + '</div>';
    }
    function aiBlock(o) {
        const ai = o.ai;
        if (!ai) return o.method === 'gcash' ? '<p class="text-sm text-gray-500">Not read yet.</p>' : '<p class="text-sm text-gray-500">Anee reads GCash receipts only. This one is checked by hand.</p>';
        const icon = (ok) => ok === true ? '<i class="y">✓</i>' : ok === false ? '<i class="n">✕</i>' : '<i class="q">?</i>';
        const read = ai.read || {};
        return `<div class="od-verdict ${esc(ai.verdict)}"><b>${esc(aiWord[ai.verdict] || ai.verdict)}.</b>&nbsp;${esc(ai.summary || '')}</div>
            ${(ai.checks || []).length ? '<div class="od-checks">' + ai.checks.map((c) => `<div class="od-check">${icon(c.ok)}<div><b>${esc(c.label)}</b><small>${esc(c.detail)}</small></div></div>`).join('') + '</div>' : ''}
            ${read.app ? `<div class="od-sec"><h4>What Anee read</h4>${facts([
                ['App', read.app + (read.kind ? ' · ' + read.kind.replace(/_/g, ' ') : '')],
                ['Amount', read.amount != null ? peso(o, read.amount) : '—'],
                ['To', [read.recipientName, read.recipientNumber].filter(Boolean).join(' · ')],
                ['From', read.senderName],
                ['Ref No.', read.ref],
                ['When', read.dateTime],
                ['Sure it is genuine', read.authenticity != null ? read.authenticity + ' / 100' : ''],
                ['Her note', read.notes],
            ])}</div>` : ''}`;
    }
    function paint(o) {
        current = o;
        $id('orTitle').textContent = o.number + ' · ' + o.statusLabel;
        $id('orSubtitle').textContent = o.itemName;
        const proof = o.file
            ? (o.file.mime === 'application/pdf'
                ? `<div class="od-proof"><div class="pdf"><span class="or-meth" style="background:#b91c1c">PDF</span><div class="min-w-0"><b class="block truncate">${esc(o.file.name || 'receipt.pdf')}</b><a class="text-sm text-brand-700 underline" href="${esc(o.file.url)}" target="_blank" rel="noopener">Open the PDF</a></div></div></div>`
                : `<div class="od-proof"><img src="${esc(o.file.url)}" alt="The receipt" id="odImg"></div>`)
            : '<p class="text-sm text-gray-500">No picture or PDF: only the reference number.</p>';
        $id('orBody').innerHTML = `
            ${facts([['Buyer', o.user ? o.user.name + ' (' + o.user.email + ')' : o.buyer], ['Their plan now', o.user?.tier], ['Bought', o.itemName],
                ['Price', peso(o, o.price)], ['Fee', o.fee ? peso(o, o.fee) : ''], ['Total to pay', peso(o, o.total)], ['Paid by', o.methodLabel],
                ['Ref No. typed', o.refNumber], ['Their note', o.buyerNote]])}
            <div class="od-sec"><h4>The proof</h4>${proof}</div>
            <div class="od-sec"><h4>Anee's check</h4>${aiBlock(o)}</div>
            ${o.method === 'gcash' ? `<div class="od-sec"><h4>What a good receipt must show</h4>${facts([['Total sent', peso(o, o.expected.total)], ['To', o.expected.number + ' · ' + o.expected.name], ['Paid', 'after ' + (o.timeline[0]?.at || 'the order opened')]])}</div>` : ''}
            <div class="od-sec"><h4>What happened</h4><div class="od-time">${o.timeline.map((t) => `<div><span>${esc(t.at)}</span><b>${esc(t.say)}</b></div>`).join('')}</div></div>
            <div class="od-reason" id="odReason"><label class="form-label" for="odReasonText" id="odReasonLabel">Why?</label>
                <textarea id="odReasonText" class="form-input" rows="2" maxlength="500" placeholder="Told to the buyer, e.g. the amount did not arrive"></textarea></div>`;
        const pending = o.status === 'review' || o.status === 'awaiting';
        $id('orFoot').innerHTML = `<div class="od-acts w-full">
            ${pending ? '<button type="button" class="btn btn-primary" data-od="approve">Approve</button><button type="button" class="btn btn-white" data-od="reject">Reject</button>' : ''}
            ${o.status === 'approved' ? '<button type="button" class="btn btn-danger wide" data-od="revoke">Revoke</button>' : ''}
            ${pending && o.file && o.method === 'gcash' ? '<button type="button" class="btn btn-white wide" data-od="recheck">Ask Anee to read it again</button>' : ''}
            <button type="button" class="btn btn-primary wide hidden" data-od="confirm">Confirm</button>
        </div>`;
        $id('odImg')?.addEventListener('click', () => { $id('odBox').querySelector('img').src = o.file.url; $id('odBox').classList.add('is-on'); });
    }
    async function open(id) {
        $id('orTitle').textContent = 'Loading…';
        $id('orSubtitle').textContent = '';
        $id('orBody').innerHTML = '<div class="py-10 text-center"><span class="ad-spin"></span></div>';
        $id('orFoot').innerHTML = '';
        window.openSheet('orSheet');
        try { paint((await api(U.one(id))).data); } catch (e) { toast(e.message, 'error'); }
    }
    document.addEventListener('click', (e) => { const r = e.target.closest('.or-row[data-order]'); if (r) open(r.dataset.order); });
    $id('odBox').addEventListener('click', () => $id('odBox').classList.remove('is-on'));

    let pendingAct = null;
    $id('orFoot').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-od]');
        if (!b || !current) return;
        let act = b.dataset.od;
        if (act === 'reject' || act === 'revoke') {
            pendingAct = act;
            $id('odReasonLabel').textContent = act === 'revoke' ? 'Why is it revoked? (the buyer is told)' : 'Why is it rejected? (the buyer is told)';
            $id('odReason').classList.add('is-on');
            const c = $id('orFoot').querySelector('[data-od="confirm"]');
            c.textContent = act === 'revoke' ? 'Revoke: take back what it gave' : 'Reject this payment';
            c.className = 'btn wide ' + (act === 'revoke' ? 'btn-danger' : 'btn-primary');
            c.dataset.od = 'confirm';
            $id('odReasonText').focus();
            return;
        }
        if (act === 'confirm') act = pendingAct;
        if (act === 'approve' && !(await window.confirmAction({ title: 'Approve this payment?', message: current.kind === 'plan' ? 'The plan starts (or is lined up) and the buyer is told.' : 'The credits land in the account and the buyer is told.', confirmText: 'Approve' }))) return;
        const body = { reason: $id('odReasonText')?.value || '' };
        b.disabled = true;
        const was = b.textContent;
        b.textContent = act === 'recheck' ? 'Anee is reading…' : 'Working…';
        try {
            const res = await api(U.act(current.id, act), { method: 'POST', body });
            toast(res.message);
            pendingAct = null;
            paint((await api(U.one(current.id))).data);
            feed.reset();
        } catch (err) {
            toast(err.message, 'error');
            b.disabled = false;
            b.textContent = was;
        }
    });

    // Opened from the bell: ?open=<id>
    const openId = new URLSearchParams(location.search).get('open');
    if (openId) setTimeout(() => open(openId), 300);
})();
</script>
@endpush
