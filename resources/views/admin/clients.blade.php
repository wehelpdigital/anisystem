@extends('layouts.admin')

@section('title', 'Clients')
@section('subtitle', 'Every account, and what can be done for it')

@section('content')
    <div class="sticky top-[6.4rem] z-30 -mx-1 px-1 pb-2 bg-gray-50 dark:bg-[#0c1108]">
        <input type="search" id="clSearch" class="form-input" placeholder="Search by name or email…" autocomplete="off">
    </div>

    <div class="card !p-0 overflow-hidden">
        <div id="clList"></div>
        <div id="clEmpty" class="hidden text-center py-10">
            <p class="font-bold text-gray-900 dark:text-gray-100">Nobody matches</p>
            <p class="text-sm text-gray-400">Try fewer letters.</p>
        </div>
    </div>
    <div class="ad-more" id="clMore" hidden><span class="ad-spin"></span> Loading more…</div>
@endsection

@push('sheets')
{{-- ONE CLIENT, EVERYTHING AN ADMIN DOES FOR THEM. One sheet, sections in the
     order support calls actually go: who they are, then the thing they rang
     about. --}}
<div class="sheet hidden" id="clSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="clTitle">Client</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4" id="clBody">
        <div class="ad-skel h-24 w-full"></div>
        <div class="ad-skel h-40 w-full"></div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const U = {
        list: '{{ route('admin.data.clients') }}',
        one: (id) => '{{ url('/admin/data/client') }}/' + id,
        info: (id) => '{{ url('/admin/client') }}/' + id + '/info',
        pwLink: (id) => '{{ url('/admin/client') }}/' + id + '/password-link',
        pwSet: (id) => '{{ url('/admin/client') }}/' + id + '/password',
        suspend: (id) => '{{ url('/admin/client') }}/' + id + '/community-suspend',
        credits: (id) => '{{ url('/admin/client') }}/' + id + '/credits',
        loginAs: (id) => '{{ url('/admin/client') }}/' + id + '/impersonate',
        admin: (id) => '{{ url('/admin/client') }}/' + id + '/admin',
    };

    /* ---------------- the list ---------------- */
    const row = (c) => `
        <button type="button" class="ad-row" data-client="${c.id}">
            <span class="ad-face">${esc((c.name || '?').slice(0, 1).toUpperCase())}</span>
            <span class="ad-mid">
                <span class="ad-name">${esc(c.name)}
                    ${c.isAdmin ? '<span class="ad-badge is-admin ml-1">admin</span>' : ''}
                    ${c.role ? `<span class="ad-badge is-role ml-1">${esc(c.role)}</span>` : ''}
                    ${c.suspendedSays ? `<span class="ad-badge is-susp ml-1">suspended</span>` : ''}
                </span>
                <span class="ad-meta">${esc(c.email)} · joined ${esc(c.registered || '')}</span>
            </span>
            <span class="ad-end">${c.online ? '<span class="ad-dot" title="Online now"></span>' : ''}</span>
        </button>`;

    const feed = adminFeed({
        url: U.list,
        listId: 'clList',
        moreId: 'clMore',
        params: () => ({ search: $id('clSearch').value.trim() }),
        render: (rows) => { $id('clList').insertAdjacentHTML('beforeend', rows.map(row).join('')); $id('clEmpty').classList.add('hidden'); },
        empty: () => $id('clEmpty').classList.remove('hidden'),
    });

    let t = null;
    $id('clSearch').addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => feed.reset(), 350); });

    /* ---------------- the sheet ---------------- */
    let CUR = null;

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-client]');
        if (!b) return;
        openSheet('clSheet');
        $id('clTitle').textContent = 'Client';
        $id('clBody').innerHTML = '<div class="ad-skel h-24 w-full"></div><div class="ad-skel h-40 w-full mt-3"></div>';
        try {
            const res = await api(U.one(b.getAttribute('data-client')), { method: 'GET' });
            CUR = res.data;
            paint();
        } catch (err) { toast(err.message, 'error'); closeSheet('clSheet'); }
    });

    function paint() {
        const c = CUR;
        $id('clTitle').textContent = c.name;
        $id('clBody').innerHTML = `
            <div class="flex flex-wrap gap-1.5 text-xs">
                ${c.isAdmin ? '<span class="ad-badge is-admin">admin</span>' : ''}
                ${c.role ? `<span class="ad-badge is-role">${esc(c.role)}</span>` : ''}
                ${c.suspendedSays ? `<span class="ad-badge is-susp">community suspended · ${esc(c.suspendedSays)}</span>` : ''}
                <span class="badge badge-gray">joined ${esc(c.registered || '—')}</span>
                <span class="badge badge-gray">${c.schedules} season${c.schedules === 1 ? '' : 's'}</span>
                <a class="badge badge-gray" href="{{ route('admin.support') }}?client=${c.id}" title="Open this client's tickets">${c.tickets} ticket${c.tickets === 1 ? '' : 's'} ↗</a>
                <span class="badge badge-green">${Number(c.creditBalance).toLocaleString()} credits</span>
            </div>

            <div class="card p-3.5 space-y-3">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">Who they are</p>
                <div class="grid grid-cols-2 gap-2.5">
                    <div><label class="form-label !mb-1 text-xs!">First name</label><input id="ceFirst" class="form-input" value="${esc(c.firstName || '')}"></div>
                    <div><label class="form-label !mb-1 text-xs!">Last name</label><input id="ceLast" class="form-input" value="${esc(c.lastName || '')}"></div>
                </div>
                <div><label class="form-label !mb-1 text-xs!">Email</label><input id="ceEmail" type="email" class="form-input" value="${esc(c.email || '')}"></div>
                <div><label class="form-label !mb-1 text-xs!">Phone</label><input id="cePhone" class="form-input" value="${esc(c.phone || '')}" placeholder="—"></div>
                <button type="button" class="btn btn-primary btn-sm w-full" id="ceSave">Save details</button>
            </div>

            <div class="card p-3.5 space-y-2.5">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">Password</p>
                <p class="text-xs text-gray-400 -mt-1.5">Two ways: the polite one emails them a link, the direct one sets it here and now.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" class="btn btn-white btn-sm" id="cePwLink">✉️ Email a reset link</button>
                    <button type="button" class="btn btn-white btn-sm" id="cePwManualBtn">⌨️ Set it manually</button>
                </div>
                <div id="cePwManual" class="hidden space-y-2 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-3">
                    <div class="flex gap-2">
                        <input id="cePwInput" class="form-input font-mono" placeholder="New password (min 8)" autocomplete="off">
                        <button type="button" class="btn btn-white btn-sm shrink-0" id="cePwGen" title="Generate a strong one">🎲</button>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-full" id="cePwSet">Change the password</button>
                    <p class="text-xs text-amber-700 dark:text-amber-400">It is not emailed — read it to the client before closing this.</p>
                </div>
            </div>

            <div class="card p-3.5 space-y-2">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">Subscription</p>
                ${c.subscription ? `
                    <p class="text-sm text-gray-700 dark:text-gray-300">${esc(c.subscription.planName || 'Plan')}
                        <span class="ad-badge ${c.subscription.status === 'active' ? 'is-answered' : 'is-closed'} ml-1">${esc(c.subscription.status)}</span></p>
                    <p class="text-xs text-gray-400">${esc(c.subscription.startsAt || '—')} → ${esc(c.subscription.expiresAt || '—')} · ₱${Number(c.subscription.price).toLocaleString()}</p>`
                    : '<p class="text-sm text-gray-400">No subscription yet.</p>'}
                {{-- Blank on purpose: plan changes will live here, and saying so
                     beats a button that does nothing. --}}
                <p class="text-xs text-gray-400 italic">Changing the plan from here is coming — for now subscriptions move with payments.</p>
            </div>

            <div class="card p-3.5 space-y-2.5">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">Community</p>
                ${c.suspendedSays ? `
                    <p class="text-sm text-red-700 dark:text-red-400">Suspended until <b>${esc(c.suspendedSays)}</b> — the Community module will not open for them.</p>
                    <button type="button" class="btn btn-white btn-sm w-full" id="ceLift">Lift the suspension now</button>`
                : `
                    <p class="text-xs text-gray-400 -mt-1.5">Bars them from the whole Community until the day you pick. The rest of the app keeps working.</p>
                    <div class="flex gap-2">
                        <input type="date" id="ceSuspDate" class="form-input" min="${new Date(Date.now() + 86400000).toISOString().slice(0, 10)}">
                        <button type="button" class="btn btn-danger btn-sm shrink-0" id="ceSuspend">Suspend</button>
                    </div>`}
            </div>

            <div class="card p-3.5 space-y-2.5">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">AI credits</p>
                <p class="text-xs text-gray-400 -mt-1.5">Balance: <b>${Number(c.creditBalance).toLocaleString()}</b>. Positive adds, negative takes back; the client sees the reason in their own credits log.</p>
                ${(c.workerFarms && c.workerFarms.length) ? `
                    <div>
                        <label class="form-label !mb-1 text-xs!">Whose balance receives it</label>
                        <select id="ceCredTarget" class="form-select">
                            <option value="${c.id}">Their own account</option>
                            ${c.workerFarms.map((f) => `<option value="${f.bossId}">The farm they work at — ${esc(f.bossName)} (owner's balance)</option>`).join('')}
                        </select>
                        <p class="text-xs text-gray-400 mt-1">A worker's questions bill the farm owner — pick where this grant should land.</p>
                    </div>` : ''}
                <div class="grid grid-cols-[6.5rem_1fr] gap-2">
                    <input type="number" step="any" id="ceCredAmt" class="form-input" placeholder="+10">
                    <input type="text" id="ceCredWhy" class="form-input" maxlength="150" placeholder="Reason — e.g. Goodwill for the outage">
                </div>
                <button type="button" class="btn btn-primary btn-sm w-full" id="ceCredGo">Apply to their balance</button>
            </div>

            <div class="card p-3.5 space-y-2">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">See what they see</p>
                <p class="text-xs text-gray-400 -mt-1">Opens the client panel signed in as them. A bar up top brings you back here.</p>
                <button type="button" class="btn btn-white btn-sm w-full" id="ceLoginAs">👁 Log in as ${esc(c.firstName || c.name)}</button>
            </div>

            <div class="card p-3.5 space-y-2">
                <p class="font-bold text-sm text-gray-900 dark:text-gray-100">Admin access</p>
                ${c.isAdmin ? `
                    <p class="text-xs text-gray-400 -mt-1">This account can open the admin panel — everything you can do here, they can.</p>
                    <button type="button" class="btn btn-white btn-sm w-full" id="ceAdminOff">Remove admin access</button>`
                : `
                    <p class="text-xs text-gray-400 -mt-1">Grants the whole panel: clients, credits, support, impersonation. Not a small key.</p>
                    <button type="button" class="btn btn-white btn-sm w-full" id="ceAdminOn">🛡 Make ${esc(c.firstName || c.name)} an admin</button>`}
            </div>`;
        wire();
    }

    function wire() {
        const c = CUR;
        const busyable = (btn, fn) => async () => {
            btn.disabled = true;
            try { await fn(); } catch (err) { toast(err.message, 'error'); }
            finally { btn.disabled = false; }
        };

        $id('ceSave').onclick = busyable($id('ceSave'), async () => {
            const res = await api(U.info(c.id), { method: 'PUT', body: {
                firstName: $id('ceFirst').value.trim(),
                lastName: $id('ceLast').value.trim(),
                email: $id('ceEmail').value.trim(),
                phone: $id('cePhone').value.trim(),
            } });
            toast(res.message);
            feed.reset();
        });

        $id('cePwLink').onclick = busyable($id('cePwLink'), async () => {
            const res = await api(U.pwLink(c.id), { method: 'POST', body: {} });
            toast(res.message);
        });

        $id('cePwManualBtn').onclick = () => $id('cePwManual').classList.toggle('hidden');
        $id('cePwGen').onclick = () => {
            /* Pronounceable-ish and unambiguous: no 0/O, no 1/l/I — a password
               read over the phone must survive the phone. */
            const abc = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
            const buf = new Uint32Array(12);
            crypto.getRandomValues(buf);
            $id('cePwInput').value = [...buf].map((n) => abc[n % abc.length]).join('');
        };
        $id('cePwSet').onclick = busyable($id('cePwSet'), async () => {
            const pw = $id('cePwInput').value;
            if (pw.length < 8) { toast('At least 8 characters.', 'error'); return; }
            const res = await api(U.pwSet(c.id), { method: 'PUT', body: { password: pw } });
            toast(res.message);
        });

        const lift = $id('ceLift');
        if (lift) lift.onclick = busyable(lift, async () => {
            const res = await api(U.suspend(c.id), { method: 'PUT', body: { until: null } });
            toast(res.message);
            CUR.suspendedSays = null; CUR.suspendedUntil = null;
            paint(); feed.reset();
        });
        const susp = $id('ceSuspend');
        if (susp) susp.onclick = busyable(susp, async () => {
            const until = $id('ceSuspDate').value;
            if (!until) { toast('Pick the day it ends.', 'error'); return; }
            const res = await api(U.suspend(c.id), { method: 'PUT', body: { until } });
            toast(res.message);
            CUR.suspendedSays = res.data.suspendedSays; CUR.suspendedUntil = res.data.suspendedUntil;
            paint(); feed.reset();
        });

        $id('ceCredGo').onclick = busyable($id('ceCredGo'), async () => {
            const amt = Number($id('ceCredAmt').value || 0);
            if (!amt) { toast('How many credits? Positive adds, negative takes back.', 'error'); return; }
            const target = $id('ceCredTarget') ? Number($id('ceCredTarget').value) : c.id;
            const res = await api(U.credits(c.id), { method: 'POST', body: {
                credits: amt, reason: $id('ceCredWhy').value.trim(), target,
            } });
            toast(res.message);
            // The sheet's own number only moves when the grant landed HERE.
            if (target === c.id) CUR.creditBalance = Number(CUR.creditBalance) + amt;
            paint();
        });

        const admOn = $id('ceAdminOn');
        if (admOn) admOn.onclick = busyable(admOn, async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Make them an admin?', message: 'The whole panel — clients, credits, impersonation — opens to this account.', confirmText: 'Make admin', danger: true })
                : confirm('Make this account an admin?');
            if (!ok) return;
            const res = await api(U.admin(c.id), { method: 'PUT', body: { admin: true } });
            toast(res.message);
            CUR.isAdmin = true;
            paint(); feed.reset();
        });
        const admOff = $id('ceAdminOff');
        if (admOff) admOff.onclick = busyable(admOff, async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Remove admin access?', message: 'They keep their account; the panel closes to them.', confirmText: 'Remove', danger: true })
                : confirm('Remove admin access?');
            if (!ok) return;
            const res = await api(U.admin(c.id), { method: 'PUT', body: { admin: false } });
            toast(res.message);
            CUR.isAdmin = false;
            paint(); feed.reset();
        });

        $id('ceLoginAs').onclick = busyable($id('ceLoginAs'), async () => {
            const res = await api(U.loginAs(c.id), { method: 'POST', body: {} });
            toast(res.message);
            window.location.href = res.data.redirect;
        });
    }
})();
</script>
@endpush
