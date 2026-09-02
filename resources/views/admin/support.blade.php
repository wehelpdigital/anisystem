@extends('layouts.admin')

@section('title', 'Support')
@section('subtitle', 'Tickets, answered from this side')

@section('content')
    <div class="sticky top-[6.4rem] z-30 -mx-1 px-1 pb-2 bg-gray-50 dark:bg-[#0c1108] space-y-2">
        <input type="search" id="tkSearch" class="form-input" placeholder="Search subject, ticket # or client…" autocomplete="off">
        <div class="flex gap-1.5 overflow-x-auto" id="tkChips">
            <button type="button" class="chip is-selected" data-status="">All</button>
            <button type="button" class="chip" data-status="open">Open <span id="cntOpen"></span></button>
            <button type="button" class="chip" data-status="answered">Answered <span id="cntAnswered"></span></button>
            <button type="button" class="chip" data-status="closed">Closed <span id="cntClosed"></span></button>
        </div>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div id="tkList"></div>
        <div id="tkEmpty" class="hidden text-center py-10">
            <p class="font-bold text-gray-900 dark:text-gray-100">No tickets here</p>
            <p class="text-sm text-gray-400">Quiet is good.</p>
        </div>
    </div>
    <div class="ad-more" id="tkMore" hidden><span class="ad-spin"></span> Loading more…</div>
@endsection

@push('sheets')
<div class="sheet hidden" id="tkSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title truncate" id="tkTitle">Ticket</h3>
            <p class="text-xs text-gray-400" id="tkMeta"></p>
        </div>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full shrink-0" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3" id="tkBody"></div>
    <div class="sheet-footer !flex-col !items-stretch gap-2">
        <textarea id="tkReply" rows="3" maxlength="8000" class="form-textarea" placeholder="Write the reply — it lands in their app, their bell, and their inbox."></textarea>
        <div class="flex gap-2">
            <button type="button" class="btn btn-white btn-sm" id="tkToggle">Close ticket</button>
            <button type="button" class="btn btn-primary flex-1" id="tkSend">Send reply</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const U = {
        list: '{{ route('admin.data.tickets') }}',
        one: (id) => '{{ url('/admin/data/ticket') }}/' + id,
        reply: (id) => '{{ url('/admin/ticket') }}/' + id + '/reply',
        status: (id) => '{{ url('/admin/ticket') }}/' + id + '/status',
    };
    const BADGE = { open: 'is-open', answered: 'is-answered', closed: 'is-closed' };
    let status = '';

    const row = (t) => `
        <button type="button" class="ad-row" data-ticket="${t.id}">
            <span class="ad-mid">
                <span class="ad-name">${esc(t.subject)}</span>
                <span class="ad-meta"><b>${esc(t.no)}</b> · ${esc(t.clientName)} · ${esc(t.category)} · ${esc(t.last || '')}</span>
            </span>
            <span class="ad-end">
                <span class="ad-badge ${BADGE[t.status] || 'is-closed'}">${esc(t.status)}</span>
                ${t.messages ? `<span class="block text-xs text-gray-400 mt-0.5">${t.messages} msg</span>` : ''}
            </span>
        </button>`;

    const feed = adminFeed({
        url: U.list,
        listId: 'tkList',
        moreId: 'tkMore',
        params: () => ({ status, search: $id('tkSearch').value.trim() }),
        render: (rows, data) => {
            $id('tkList').insertAdjacentHTML('beforeend', rows.map(row).join(''));
            $id('tkEmpty').classList.add('hidden');
            if (data.counts) {
                $id('cntOpen').textContent = data.counts.open ? `(${data.counts.open})` : '';
                $id('cntAnswered').textContent = data.counts.answered ? `(${data.counts.answered})` : '';
                $id('cntClosed').textContent = data.counts.closed ? `(${data.counts.closed})` : '';
            }
        },
        empty: () => $id('tkEmpty').classList.remove('hidden'),
    });

    let t = null;
    $id('tkSearch').addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => feed.reset(), 350); });
    $id('tkChips').addEventListener('click', (e) => {
        const chip = e.target.closest('[data-status]');
        if (!chip) return;
        status = chip.getAttribute('data-status');
        document.querySelectorAll('#tkChips .chip').forEach((c) => c.classList.toggle('is-selected', c === chip));
        feed.reset();
    });

    /* ---------------- one ticket ---------------- */
    let CUR = null;

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-ticket]');
        if (!b) return;
        openSheet('tkSheet');
        $id('tkTitle').textContent = 'Ticket';
        $id('tkMeta').textContent = '';
        $id('tkBody').innerHTML = '<div class="ad-skel h-16 w-3/4"></div><div class="ad-skel h-16 w-3/4 ml-auto mt-2"></div>';
        $id('tkReply').value = '';
        try {
            const res = await api(U.one(b.getAttribute('data-ticket')), { method: 'GET' });
            CUR = res.data;
            paint();
        } catch (err) { toast(err.message, 'error'); closeSheet('tkSheet'); }
    });

    function paint() {
        const tk = CUR;
        $id('tkTitle').textContent = tk.subject;
        $id('tkMeta').innerHTML = `<b>${esc(tk.no)}</b> · ${esc(tk.clientName)} · ${esc(tk.clientEmail)} · <span class="ad-badge ${BADGE[tk.status]}">${esc(tk.status)}</span>`;
        $id('tkBody').innerHTML = tk.messages.map((m) => `
            <div class="tk-msg ${m.mine ? 'is-admin' : ''}">
                <span class="tk-who">${esc(m.author)}</span>
                <span style="white-space:pre-wrap">${esc(m.body)}</span>
                <span class="tk-at">${esc(m.at || '')}</span>
            </div>`).join('');
        $id('tkToggle').textContent = tk.status === 'closed' ? 'Reopen ticket' : 'Close ticket';
        // The newest message is what the admin came to read.
        const body = $id('tkBody');
        requestAnimationFrame(() => { body.scrollTop = body.scrollHeight; });
    }

    $id('tkSend').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const body = $id('tkReply').value.trim();
        if (!body) { toast('Write the reply first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.reply(CUR.id), { method: 'POST', body: { body } });
            toast(res.message);
            $id('tkReply').value = '';
            const one = await api(U.one(CUR.id), { method: 'GET' });
            CUR = one.data;
            paint();
            feed.reset();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    $id('tkToggle').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const to = CUR.status === 'closed' ? 'open' : 'closed';
            const res = await api(U.status(CUR.id), { method: 'PUT', body: { status: to } });
            toast(res.message);
            CUR.status = to;
            paint();
            feed.reset();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });
})();
</script>
@endpush
