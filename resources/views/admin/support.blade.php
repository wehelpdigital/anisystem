@extends('layouts.admin')

@section('title', 'Support')
@section('subtitle', 'Tickets, answered from this side')

@section('content')
    {{-- Two rooms on one page: the tickets, and the shelf of canned answers
         a reply can start from. --}}
    <div class="flex gap-1.5 mb-3">
        <button type="button" class="chip is-selected" id="tabBtnTickets">Tickets</button>
        <button type="button" class="chip" id="tabBtnCanned">Canned responses</button>
    </div>

    <div id="tabTickets">
        <div class="sticky top-[6.4rem] z-30 -mx-1 px-1 pb-2 bg-gray-50 dark:bg-[#0c1108] space-y-2">
            <input type="search" id="tkSearch" class="form-input" placeholder="Search subject, ticket # or client…" autocomplete="off">
            <div class="flex gap-1.5 overflow-x-auto items-center" id="tkChips">
                <button type="button" class="chip is-selected" data-status="">All</button>
                <button type="button" class="chip" data-status="open">Open <span id="cntOpen"></span></button>
                <button type="button" class="chip" data-status="answered">Answered <span id="cntAnswered"></span></button>
                <button type="button" class="chip" data-status="closed">Closed <span id="cntClosed"></span></button>
                <button type="button" class="chip hidden" id="tkClientChip" title="Show every client again">One client ✕</button>
            </div>
        </div>

        {{-- Grouped by the person: a client's row, their tickets folded under it. --}}
        <div class="card !p-0 overflow-hidden">
            <div id="tkList"></div>
            <div id="tkEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">No tickets here</p>
                <p class="text-sm text-gray-400">Quiet is good.</p>
            </div>
        </div>
        <div class="ad-more" id="tkMore" hidden><span class="ad-spin"></span> Loading more…</div>
    </div>

    <div id="tabCanned" class="hidden">
        <div class="card p-3.5 mb-3 space-y-2">
            <p class="font-bold text-sm text-gray-900">Merge fields</p>
            <p class="text-xs text-gray-400 -mt-1.5">Write these into a template (or any reply) and they become the client's own facts at send time. Tap one to copy it.</p>
            <div class="flex flex-wrap gap-1.5" id="mfChips">
                <button type="button" class="chip" data-mf="{first_name}">{first_name}</button>
                <button type="button" class="chip" data-mf="{last_name}">{last_name}</button>
                <button type="button" class="chip" data-mf="{email}">{email}</button>
                <button type="button" class="chip" data-mf="{ticket_no}">{ticket_no}</button>
                <button type="button" class="chip" data-mf="{subject}">{subject}</button>
                <button type="button" class="chip" data-mf="{admin_name}">{admin_name}</button>
            </div>
        </div>

        <div class="card p-3.5 mb-3 space-y-2">
            <p class="font-bold text-sm text-gray-900" id="cnFormTitle">New template</p>
            <input type="text" id="cnTitle" class="form-input" maxlength="120" placeholder="What this answer is for — e.g. Welcome & first steps">
            <div class="tk-toolbar" data-editor="cnBody">
                <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                <button type="button" data-cmd="insertUnorderedList" title="Bullet list">•≡</button>
                <button type="button" data-cmd="link" title="Link">🔗</button>
            </div>
            <div class="tk-editor" id="cnBody" contenteditable="true" data-placeholder="Hi {first_name}, …"></div>
            <input type="hidden" id="cnId" value="">
            <div class="flex gap-2">
                <button type="button" class="btn btn-white btn-sm hidden" id="cnCancel">Cancel edit</button>
                <button type="button" class="btn btn-primary btn-sm flex-1" id="cnSave">Save template</button>
            </div>
        </div>

        <div class="card !p-0 overflow-hidden">
            <div id="cnList"></div>
            <div id="cnEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">The shelf is empty</p>
                <p class="text-sm text-gray-400">Write the first answer above.</p>
            </div>
        </div>
    </div>
@endsection

@push('head')
<style>
    /* One client's tickets, folded under their name. */
    .tk-group { border-bottom: 1px solid var(--color-gray-100); }
    .tk-group:last-child { border-bottom: 0; }
    .tk-group-head .ad-face { font-size: .85rem; }
    .tk-group-body { display: grid; grid-template-rows: 1fr;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .tk-group.is-folded .tk-group-body { grid-template-rows: 0fr; }
    .tk-group-body > div { overflow: hidden; min-height: 0; }
    .tk-row { padding-left: 2rem; background: var(--color-gray-50); }
    .tk-row:hover { background: var(--color-gray-100); }
    .tk-chev { width: 1rem; height: 1rem; color: var(--color-gray-400);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .tk-group.is-folded .tk-chev { transform: rotate(-90deg); }
    html.dark .tk-group { border-color: #222b1a; }
    html.dark .tk-row { background: #131a0e; }
    html.dark .tk-row:hover { background: #161e10; }

    /* The ticket number is a thing you copy: it looks like a chip, it acts
       like a button, and it never opens the row under it. */
    .tk-copy { display: inline-flex; align-items: center; gap: .25rem; padding: 0 .4rem;
        border-radius: .4rem; font-weight: 700; font-family: var(--font-mono, monospace);
        font-size: .7rem; background: var(--color-gray-100); color: var(--color-gray-600); cursor: copy; }
    .tk-copy:hover { background: var(--color-brand-50); color: var(--color-brand-800); }
    html.dark .tk-copy { background: #1c2417; color: #a8bd93; }
    html.dark .tk-copy:hover { background: #22301a; color: #cfe6b8; }

    /* The reply's small editor: a toolbar and an editable sheet of paper. */
    .tk-toolbar { display: flex; gap: .25rem; flex-wrap: wrap; align-items: center; }
    .tk-toolbar button { min-width: 2rem; height: 2rem; padding: 0 .45rem; border-radius: .5rem;
        font-size: .85rem; color: var(--color-gray-600); background: var(--color-gray-100); }
    .tk-toolbar button:hover { background: var(--color-brand-50); color: var(--color-brand-800); }
    .tk-toolbar select { flex: 1 1 8rem; min-width: 0; height: 2rem; border-radius: .5rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .78rem; padding: 0 .4rem; color: var(--color-gray-700); }
    .tk-editor { min-height: 5.5rem; max-height: 14rem; overflow-y: auto; border-radius: .7rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: .6rem .75rem; font-size: .875rem; color: var(--color-gray-900); }
    .tk-editor:focus { outline: 2px solid var(--color-brand-300); outline-offset: 1px; }
    .tk-editor:empty::before { content: attr(data-placeholder); color: var(--color-gray-400); }
    .tk-editor img { max-width: 100%; border-radius: .5rem; margin: .25rem 0; }
    .tk-editor a { color: var(--color-brand-700); text-decoration: underline; }
    .tk-editor ul { list-style: disc; padding-left: 1.2rem; }
    html.dark .tk-toolbar button { background: #1c2417; color: #a8bd93; }
    html.dark .tk-toolbar button:hover { background: #22301a; color: #cfe6b8; }
    html.dark .tk-toolbar select { background: #131a0e; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .tk-editor { background: #131a0e; border-color: #2b3a1c; color: #e8efe1; }

    /* Rich message bodies inside the thread. */
    .tk-msg .tk-rich img { max-width: 100%; border-radius: .5rem; margin: .25rem 0; }
    .tk-msg .tk-rich a { text-decoration: underline; }
    .tk-msg .tk-rich ul { list-style: disc; padding-left: 1.2rem; }
    .tk-msg .tk-rich video { max-width: 100%; border-radius: .5rem; margin: .25rem 0; }
    /* A canned row's preview keeps to one quiet line. */
    .cn-prev { display: block; font-size: .73rem; color: var(--color-gray-400);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    @media (prefers-reduced-motion: reduce) { .tk-group-body, .tk-chev { transition: none; } }
</style>
@endpush

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
        <div class="tk-toolbar" data-editor="tkReply">
            <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
            <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
            <button type="button" data-cmd="insertUnorderedList" title="Bullet list">•≡</button>
            <button type="button" data-cmd="link" title="Link">🔗</button>
            <button type="button" id="tkAttachImg" title="Attach an image">🖼️</button>
            <button type="button" id="tkAttachVid" title="Attach a video">🎥</button>
            <select id="tkCanned" aria-label="Insert a canned response">
                <option value="">Canned response…</option>
            </select>
        </div>
        <div class="tk-editor" id="tkReply" contenteditable="true" data-placeholder="Write the reply — it lands in their app, their bell, and their inbox. Merge fields like {first_name} become their facts."></div>
        <input type="file" id="tkFileImg" accept="image/*" hidden>
        <input type="file" id="tkFileVid" accept="video/*" hidden>
        <button type="button" class="btn btn-primary w-full" id="tkSend">Send reply</button>
        <button type="button" class="btn btn-white w-full" id="tkToggle">Close ticket</button>
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
        canned: '{{ route('admin.data.canned') }}',
        cannedSave: '{{ route('admin.canned.save') }}',
        cannedDel: (id) => '{{ url('/admin/canned') }}/' + id,
        media: '{{ route('admin.ticket.media') }}',
    };
    const BADGE = { open: 'is-open', answered: 'is-answered', closed: 'is-closed' };
    let status = '';
    let clientFilter = new URLSearchParams(location.search).get('client') || '';

    /* ---------------- the grouped list ---------------- */
    const ticketRow = (t) => `
        <button type="button" class="ad-row tk-row" data-ticket="${t.id}">
            <span class="ad-mid">
                <span class="ad-name">${esc(t.subject)}</span>
                <span class="ad-meta"><span class="tk-copy" role="button" tabindex="0" data-copy="${esc(t.no)}" title="Copy the ticket number">${esc(t.no)} ⧉</span> · ${esc(t.category)} · ${esc(t.last || '')}</span>
            </span>
            <span class="ad-end">
                <span class="ad-badge ${BADGE[t.status] || 'is-closed'}">${esc(t.status)}</span>
                ${t.messages ? `<span class="block text-xs text-gray-400 mt-0.5">${t.messages} msg</span>` : ''}
            </span>
        </button>`;

    const groupRow = (g) => `
        <div class="tk-group" data-group="${g.clientId}">
            <button type="button" class="ad-row tk-group-head" data-toggle-group="${g.clientId}">
                <span class="ad-face">${esc((g.clientName || '?').slice(0, 1).toUpperCase())}</span>
                <span class="ad-mid">
                    <span class="ad-name">${esc(g.clientName)}</span>
                    <span class="ad-meta">${esc(g.clientEmail)}</span>
                </span>
                <span class="ad-end flex items-center gap-2">
                    <span class="badge badge-gray">${g.count} ticket${g.count === 1 ? '' : 's'}</span>
                    <svg class="tk-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </button>
            <div class="tk-group-body"><div>${g.tickets.map(ticketRow).join('')}</div></div>
        </div>`;

    const feed = adminFeed({
        url: U.list,
        listId: 'tkList',
        moreId: 'tkMore',
        params: () => ({ status, search: $id('tkSearch').value.trim(), client: clientFilter }),
        render: (rows, data) => {
            $id('tkList').insertAdjacentHTML('beforeend', rows.map(groupRow).join(''));
            $id('tkEmpty').classList.add('hidden');
            if (data.counts) {
                $id('cntOpen').textContent = data.counts.open ? `(${data.counts.open})` : '';
                $id('cntAnswered').textContent = data.counts.answered ? `(${data.counts.answered})` : '';
                $id('cntClosed').textContent = data.counts.closed ? `(${data.counts.closed})` : '';
            }
        },
        empty: () => $id('tkEmpty').classList.remove('hidden'),
    });

    $id('tkClientChip').classList.toggle('hidden', !clientFilter);
    $id('tkClientChip').addEventListener('click', () => {
        clientFilter = '';
        history.replaceState(null, '', location.pathname);
        $id('tkClientChip').classList.add('hidden');
        feed.reset();
    });

    let t = null;
    $id('tkSearch').addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => feed.reset(), 350); });
    $id('tkChips').addEventListener('click', (e) => {
        const chip = e.target.closest('[data-status]');
        if (!chip) return;
        status = chip.getAttribute('data-status');
        document.querySelectorAll('#tkChips .chip[data-status]').forEach((c) => c.classList.toggle('is-selected', c === chip));
        feed.reset();
    });

    document.addEventListener('click', (e) => {
        const head = e.target.closest('[data-toggle-group]');
        if (head) head.closest('.tk-group').classList.toggle('is-folded');
    });

    /* The ticket number copies itself and swallows the click under it. */
    document.addEventListener('click', async (e) => {
        const cp = e.target.closest('[data-copy]');
        if (!cp) return;
        e.preventDefault();
        e.stopPropagation();
        const no = cp.getAttribute('data-copy');
        try {
            await navigator.clipboard.writeText(no);
            toast('Copied ' + no + '.');
        } catch (_) {
            // A quieter clipboard: select-and-copy through a scratch input.
            const inp = document.createElement('input');
            inp.value = no;
            document.body.appendChild(inp);
            inp.select();
            document.execCommand('copy');
            inp.remove();
            toast('Copied ' + no + '.');
        }
    }, true);

    /* ---------------- the small editor, shared ---------------- */
    document.addEventListener('click', (e) => {
        const b = e.target.closest('.tk-toolbar [data-cmd]');
        if (!b) return;
        const ed = $id(b.closest('.tk-toolbar').getAttribute('data-editor'));
        ed.focus();
        const cmd = b.getAttribute('data-cmd');
        if (cmd === 'link') {
            const url = prompt('Link to where? (https://…)');
            if (url && /^https?:\/\//i.test(url)) document.execCommand('createLink', false, url);
            return;
        }
        document.execCommand(cmd, false, null);
    });

    /* Turns .mp4/.webm/.mov links into players — a reply's video rides as a
       link because the purifier speaks HTML4, and the thread dresses it. */
    function dressVideos(host) {
        host.querySelectorAll('a[href]').forEach((a) => {
            if (!/\.(mp4|webm|mov)(\?|$)/i.test(a.getAttribute('href'))) return;
            const v = document.createElement('video');
            v.src = a.getAttribute('href');
            v.controls = true;
            v.playsInline = true;
            v.preload = 'metadata';
            a.replaceWith(v);
        });
    }

    /* ---------------- one ticket ---------------- */
    let CUR = null;

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-ticket]');
        if (!b) return;
        openSheet('tkSheet');
        $id('tkTitle').textContent = 'Ticket';
        $id('tkMeta').textContent = '';
        $id('tkBody').innerHTML = '<div class="ad-skel h-16 w-3/4"></div><div class="ad-skel h-16 w-3/4 ml-auto mt-2"></div>';
        $id('tkReply').innerHTML = '';
        try {
            const res = await api(U.one(b.getAttribute('data-ticket')), { method: 'GET' });
            CUR = res.data;
            paint();
        } catch (err) { toast(err.message, 'error'); closeSheet('tkSheet'); }
    });

    function paint() {
        const tk = CUR;
        $id('tkTitle').textContent = tk.subject;
        $id('tkMeta').innerHTML = `<span class="tk-copy" role="button" tabindex="0" data-copy="${esc(tk.no)}" title="Copy the ticket number">${esc(tk.no)} ⧉</span> · ${esc(tk.clientName)} · ${esc(tk.clientEmail)} · <span class="ad-badge ${BADGE[tk.status]}">${esc(tk.status)}</span>`;
        $id('tkBody').innerHTML = tk.messages.map((m) => `
            <div class="tk-msg ${m.mine ? 'is-admin' : ''}">
                <span class="tk-who">${esc(m.author)}</span>
                ${m.format === 'html'
                    ? `<span class="tk-rich">${m.body}</span>`
                    : `<span style="white-space:pre-wrap">${esc(m.body)}</span>`}
                <span class="tk-at">${esc(m.at || '')}</span>
            </div>`).join('');
        dressVideos($id('tkBody'));
        $id('tkToggle').textContent = tk.status === 'closed' ? 'Reopen ticket' : 'Close ticket';
        const body = $id('tkBody');
        requestAnimationFrame(() => { body.scrollTop = body.scrollHeight; });
    }

    /* ---------------- attachments ---------------- */
    async function upload(file) {
        const fd = new FormData();
        fd.append('file', file);
        const res = await fetch(U.media, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        });
        const j = await res.json().catch(() => null);
        if (!res.ok || !j || !j.success) throw new Error((j && j.message) || 'The upload failed.');
        return j.data;
    }
    function wireAttach(btnId, fileId) {
        $id(btnId).addEventListener('click', () => $id(fileId).click());
        $id(fileId).addEventListener('change', async (e) => {
            const f = e.target.files[0];
            e.target.value = '';
            if (!f) return;
            toast('Uploading…');
            try {
                const d = await upload(f);
                const ed = $id('tkReply');
                ed.focus();
                if (d.kind === 'image') {
                    document.execCommand('insertHTML', false, `<img src="${d.url}" alt="">`);
                } else {
                    document.execCommand('insertHTML', false, `<p><a href="${d.url}">📹 Video attachment</a></p>`);
                }
                toast('Attached.');
            } catch (err) { toast(err.message, 'error'); }
        });
    }
    wireAttach('tkAttachImg', 'tkFileImg');
    wireAttach('tkAttachVid', 'tkFileVid');

    /* ---------------- canned, in the reply ---------------- */
    let CANNED = [];
    async function loadCanned() {
        try {
            const res = await api(U.canned, { method: 'GET' });
            CANNED = res.data.rows || [];
            $id('tkCanned').innerHTML = '<option value="">Canned response…</option>'
                + CANNED.map((c) => `<option value="${c.id}">${esc(c.title)}</option>`).join('');
            paintCanned();
        } catch (_) { /* the shelf can wait */ }
    }
    $id('tkCanned').addEventListener('change', (e) => {
        const c = CANNED.find((x) => String(x.id) === e.target.value);
        e.target.value = '';
        if (!c) return;
        const ed = $id('tkReply');
        ed.innerHTML = (ed.innerHTML.trim() ? ed.innerHTML + '<p><br></p>' : '') + c.body;
        ed.focus();
    });

    /* ---------------- send / close ---------------- */
    $id('tkSend').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const ed = $id('tkReply');
        if (!ed.textContent.trim() && !ed.querySelector('img,a')) { toast('Write the reply first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.reply(CUR.id), { method: 'POST', body: { body: ed.innerHTML, format: 'html' } });
            toast(res.message);
            ed.innerHTML = '';
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

    /* ---------------- the canned tab ---------------- */
    const showTab = (which) => {
        $id('tabTickets').classList.toggle('hidden', which !== 'tickets');
        $id('tabCanned').classList.toggle('hidden', which !== 'canned');
        $id('tabBtnTickets').classList.toggle('is-selected', which === 'tickets');
        $id('tabBtnCanned').classList.toggle('is-selected', which === 'canned');
    };
    $id('tabBtnTickets').addEventListener('click', () => showTab('tickets'));
    $id('tabBtnCanned').addEventListener('click', () => showTab('canned'));

    function paintCanned() {
        $id('cnList').innerHTML = CANNED.map((c) => `
            <div class="ad-row" style="cursor:default">
                <span class="ad-mid">
                    <span class="ad-name">${esc(c.title)}</span>
                    <span class="cn-prev">${esc(String(c.body).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim())}</span>
                </span>
                <span class="ad-end flex gap-1.5">
                    <button type="button" class="btn btn-white btn-sm" data-cn-edit="${c.id}">Edit</button>
                    <button type="button" class="btn btn-white btn-sm" data-cn-del="${c.id}">🗑</button>
                </span>
            </div>`).join('');
        $id('cnEmpty').classList.toggle('hidden', CANNED.length > 0);
    }

    $id('mfChips').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-mf]');
        if (!b) return;
        try { await navigator.clipboard.writeText(b.getAttribute('data-mf')); toast('Copied — paste it into the answer.'); }
        catch (_) { toast(b.getAttribute('data-mf'), 'success'); }
    });

    document.addEventListener('click', async (e) => {
        const ed = e.target.closest('[data-cn-edit]');
        if (ed) {
            const c = CANNED.find((x) => String(x.id) === ed.getAttribute('data-cn-edit'));
            if (!c) return;
            $id('cnId').value = c.id;
            $id('cnTitle').value = c.title;
            $id('cnBody').innerHTML = c.body;
            $id('cnFormTitle').textContent = 'Editing: ' + c.title;
            $id('cnCancel').classList.remove('hidden');
            $id('cnTitle').scrollIntoView({ block: 'center', behavior: 'smooth' });
            return;
        }
        const del = e.target.closest('[data-cn-del]');
        if (del) {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Remove this template?', message: 'Replies already sent keep their words.', confirmText: 'Remove', danger: true })
                : confirm('Remove this template?');
            if (!ok) return;
            try {
                const res = await api(U.cannedDel(del.getAttribute('data-cn-del')), { method: 'DELETE' });
                toast(res.message);
                loadCanned();
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    $id('cnCancel').addEventListener('click', () => {
        $id('cnId').value = '';
        $id('cnTitle').value = '';
        $id('cnBody').innerHTML = '';
        $id('cnFormTitle').textContent = 'New template';
        $id('cnCancel').classList.add('hidden');
    });

    $id('cnSave').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const title = $id('cnTitle').value.trim();
        const body = $id('cnBody').innerHTML;
        if (!title) { toast('Name the template first.', 'error'); return; }
        if (!$id('cnBody').textContent.trim()) { toast('Write the answer itself.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.cannedSave, { method: 'POST', body: { id: $id('cnId').value || null, title, body } });
            toast(res.message);
            $id('cnCancel').click();
            loadCanned();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    if (window.api) loadCanned();
    else window.addEventListener('load', loadCanned, { once: true });
})();
</script>
@endpush
