@extends('layouts.app')

@section('title', 'Contact List')
@section('page-title', 'Contact List')
@section('page-subtitle', 'Your farm\'s phonebook')
@section('back', route('sm.index'))

@push('head')
<style>
    /* The phonebook's own clothes. Everything animates on the house easing
       and inverts with the app's dark tokens (utility classes carry those). */
    .ct-row { display: flex; align-items: flex-start; gap: .8rem; padding: .95rem 1rem;
        border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        opacity: 0; transform: translateY(8px);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ct-row.is-in { opacity: 1; transform: none; }
    .ct-row:hover { box-shadow: 0 10px 26px -18px rgb(16 22 12 / .35); }
    .ct-face { flex: none; width: 2.9rem; height: 2.9rem; border-radius: 999px; display: flex;
        align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 1rem;
        letter-spacing: .02em; user-select: none; }
    .ct-main { min-width: 0; flex: 1 1 auto; cursor: pointer; }
    .ct-name { font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .ct-sub { margin-top: .1rem; font-size: .8rem; color: var(--color-gray-500);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ct-tags { margin-top: .4rem; display: flex; flex-wrap: wrap; gap: .3rem; }
    .ct-tag { font-size: .68rem; font-weight: 800; color: var(--color-brand-700);
        background: var(--color-brand-50); border: 1px solid var(--color-brand-100);
        border-radius: 999px; padding: .14rem .55rem; }
    .ct-acts { flex: none; display: flex; gap: .35rem; }
    .ct-act { width: 2.25rem; height: 2.25rem; border-radius: 999px; display: flex; align-items: center;
        justify-content: center; color: var(--color-brand-700); background: var(--color-brand-50);
        border: 1px solid var(--color-brand-100);
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ct-act:hover { transform: translateY(-2px); background: var(--color-brand-100); }
    .ct-act svg { width: 1.05rem; height: 1.05rem; }

    .ct-chip { flex: none; font-size: .78rem; font-weight: 800; border-radius: 999px;
        padding: .38rem .85rem; border: 1px solid var(--color-gray-200); color: var(--color-gray-600);
        background: var(--color-white); cursor: pointer; white-space: nowrap;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1),
            border-color .28s cubic-bezier(.22,1,.36,1); }
    .ct-chip.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .ct-chip small { font-weight: 700; opacity: .75; }

    /* Tag editor inside the sheet: chips + a plain input that grows them. */
    .ctf-tags { display: flex; flex-wrap: wrap; gap: .35rem; padding: .45rem;
        border: 1px solid var(--color-gray-300); border-radius: .8rem; }
    .ctf-tag { display: inline-flex; align-items: center; gap: .3rem; font-size: .75rem; font-weight: 800;
        color: var(--color-brand-700); background: var(--color-brand-50);
        border: 1px solid var(--color-brand-100); border-radius: 999px; padding: .2rem .35rem .2rem .6rem; }
    .ctf-tag button { width: 1.05rem; height: 1.05rem; border-radius: 999px; display: flex;
        align-items: center; justify-content: center; color: var(--color-brand-700); }
    .ctf-tag button:hover { background: var(--color-brand-100); }
    .ctf-tags input { flex: 1 1 7rem; min-width: 7rem; border: 0; outline: none; background: transparent;
        font-size: .85rem; padding: .2rem .3rem; color: var(--color-gray-900); }
    .ctf-sug { font-size: .72rem; font-weight: 800; border-radius: 999px; padding: .28rem .7rem;
        border: 1px dashed var(--color-gray-300); color: var(--color-gray-500); cursor: pointer;
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ctf-sug:hover, .ctf-sug.is-on { color: var(--color-brand-700); border-color: var(--color-brand-400); border-style: solid; }

    @media (prefers-reduced-motion: reduce) {
        .ct-row { transition: none; opacity: 1; transform: none; }
        .ct-act, .ct-chip, .ctf-sug { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Toolbar: search on the left, the new-contact door on the right. --}}
    <div class="card mb-3">
        <div class="card-body flex items-center gap-3">
            <div class="relative grow">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" id="ctSearch" class="form-input pl-10" placeholder="Search name, number, company, tag…" autocomplete="off">
            </div>
            <button type="button" id="ctAddBtn" class="btn btn-primary shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                <span class="hidden sm:inline">Add contact</span><span class="sm:hidden">Add</span>
            </button>
        </div>
    </div>

    {{-- The chips: every tag this member uses, counted. Painted by JS. --}}
    <div id="ctChips" class="flex gap-2 overflow-x-auto pb-2 mb-2 scroll-chips" hidden></div>

    <div id="ctList" class="space-y-2.5"></div>

    {{-- The quiet states: still loading, or truly empty. --}}
    <div id="ctLoading" class="text-center py-10 text-sm text-gray-400">Opening the phonebook…</div>
    <div id="ctEmpty" class="card" hidden>
        <div class="card-body text-center py-10">
            <img src="{{ asset('images/list.png') }}" alt="" class="w-12 h-12 mx-auto mb-3 opacity-70">
            <p class="font-bold text-gray-800">No contacts yet</p>
            <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                Save the people your farm runs on — workers, tractor rentals, harvesters,
                suppliers, buyers — tagged, searchable, and one tap from a call.
            </p>
            <button type="button" class="btn btn-primary mt-4" data-ct-add>Add your first contact</button>
        </div>
    </div>
    <div id="ctMore" class="py-6" hidden aria-hidden="true"></div>

</div>

{{-- The add/edit sheet — one form, two moods; the title says which. --}}
<div class="sheet hidden" id="ctSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ctSheetTitle">New contact</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="ctfName">Name <span class="text-red-500">*</span></label>
            <input type="text" id="ctfName" class="form-input" placeholder="Mang Tonyo" maxlength="150">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label" for="ctfPhone">Mobile number</label>
                <input type="tel" id="ctfPhone" class="form-input" placeholder="09XXXXXXXXX" maxlength="40">
            </div>
            <div>
                <label class="form-label" for="ctfEmail">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="email" id="ctfEmail" class="form-input" placeholder="name@example.com" maxlength="150">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label" for="ctfCompany">Business / role</label>
                <input type="text" id="ctfCompany" class="form-input" placeholder="Tractor rental, harvester crew…" maxlength="150">
            </div>
            <div>
                <label class="form-label" for="ctfAddress">Address / area</label>
                <input type="text" id="ctfAddress" class="form-input" placeholder="Brgy., town" maxlength="255">
            </div>
        </div>
        <div>
            <label class="form-label">Tags</label>
            <div class="ctf-tags" id="ctfTags">
                <input type="text" id="ctfTagInput" placeholder="Type a tag, press Enter" maxlength="30" autocomplete="off">
            </div>
            {{-- The usual suspects, one tap each. --}}
            <div class="flex flex-wrap gap-1.5 mt-2" id="ctfSugs"></div>
        </div>
        <div>
            <label class="form-label" for="ctfNotes">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ctfNotes" class="form-input" rows="2" placeholder="Rates, landmarks, who referred them…" maxlength="2000"></textarea>
        </div>
        <button type="button" id="ctfDelete" class="btn w-full text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" hidden>Remove this contact</button>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="ctfSave">Save Contact</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URLS = {
        list: @json(route('contacts.list')),
        store: @json(route('contacts.store')),
        one: (id) => @json(url('/app/contacts')) + '/' + id,
    };
    const SUGGESTIONS = ['Worker', 'Tractor Rental', 'Harvester', 'Seed Supplier', 'Fertilizer Dealer', 'Buyer', 'Technician', 'Driver', 'Irrigation', 'Landlord'];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const $ = (id) => document.getElementById(id);
    const list = $('ctList'), chips = $('ctChips'), empty = $('ctEmpty'), loading = $('ctLoading'), more = $('ctMore');

    const state = { q: '', tag: '', page: 1, hasMore: false, busy: false, editing: null, tags: [] };

    /* A face from a name: the same name always wears the same colour. */
    const hueOf = (name) => {
        let h = 0;
        for (const ch of String(name)) h = (h * 31 + ch.codePointAt(0)) % 360;
        return h;
    };
    const initials = (name) => String(name).trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function rowEl(c) {
        const el = document.createElement('div');
        el.className = 'ct-row';
        el.dataset.id = c.id;
        const subBits = [c.company, c.address].filter(Boolean).join(' · ');
        el.innerHTML = `
            <span class="ct-face" style="background:hsl(${hueOf(c.name)} 45% 42%)">${esc(initials(c.name))}</span>
            <div class="ct-main" role="button" tabindex="0" aria-label="Edit ${esc(c.name)}">
                <p class="ct-name">${esc(c.name)}</p>
                ${subBits ? `<p class="ct-sub">${esc(subBits)}</p>` : ''}
                ${c.phone ? `<p class="ct-sub">${esc(c.phone)}</p>` : ''}
                ${(c.tags || []).length ? `<div class="ct-tags">${c.tags.map((t) => `<span class="ct-tag">${esc(t)}</span>`).join('')}</div>` : ''}
            </div>
            <div class="ct-acts">
                ${c.phone ? `<a class="ct-act" href="tel:${esc(c.phone)}" title="Call ${esc(c.name)}" aria-label="Call"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></a>` : ''}
                ${c.phone ? `<a class="ct-act" href="sms:${esc(c.phone)}" title="Text ${esc(c.name)}" aria-label="Text"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></a>` : ''}
                ${c.email ? `<a class="ct-act" href="mailto:${esc(c.email)}" title="Email ${esc(c.name)}" aria-label="Email"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></a>` : ''}
            </div>`;
        el.querySelector('.ct-main').addEventListener('click', () => openSheetFor(c));
        el.querySelector('.ct-main').addEventListener('keydown', (e) => { if (e.key === 'Enter') openSheetFor(c); });
        return el;
    }

    function paintChips(tagCounts) {
        state.tags = Object.keys(tagCounts);
        if (!state.tags.length) { chips.hidden = true; return; }
        chips.hidden = false;
        chips.innerHTML = '';
        const all = document.createElement('button');
        all.type = 'button';
        all.className = 'ct-chip' + (state.tag === '' ? ' is-on' : '');
        all.textContent = 'All';
        all.addEventListener('click', () => { state.tag = ''; reload(); });
        chips.appendChild(all);
        for (const [tag, n] of Object.entries(tagCounts)) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ct-chip' + (state.tag === tag ? ' is-on' : '');
            b.innerHTML = `${esc(tag)} <small>${n}</small>`;
            b.addEventListener('click', () => { state.tag = state.tag === tag ? '' : tag; reload(); });
            chips.appendChild(b);
        }
    }

    async function fetchPage(page) {
        const qs = new URLSearchParams({ q: state.q, tag: state.tag, page });
        const res = await window.api(URLS.list + '?' + qs.toString());
        return res.data;
    }

    function appendRows(items) {
        for (const c of items) {
            const el = rowEl(c);
            list.appendChild(el);
            if (reduceMotion) el.classList.add('is-in');
            else requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-in')));
        }
    }

    async function reload() {
        state.page = 1;
        state.busy = true;
        list.innerHTML = '';
        empty.hidden = true;
        loading.hidden = false;
        try {
            const data = await fetchPage(1);
            loading.hidden = true;
            paintChips(data.tags || {});
            appendRows(data.items || []);
            state.hasMore = !!data.hasMore;
            more.hidden = !state.hasMore;
            // Empty means EMPTY BOOK when nothing narrows the view; a search
            // with no hits keeps the toolbar and just shows nothing to scroll.
            empty.hidden = !(data.total === 0 && state.q === '' && state.tag === '');
        } catch (e) {
            loading.hidden = true;
            window.toast?.(e.message || 'Could not load contacts.', 'error');
        } finally {
            state.busy = false;
        }
    }

    // The next page walks in when the sentinel shows.
    new IntersectionObserver(async (entries) => {
        if (!entries[0].isIntersecting || !state.hasMore || state.busy) return;
        state.busy = true;
        try {
            const data = await fetchPage(++state.page);
            appendRows(data.items || []);
            state.hasMore = !!data.hasMore;
            more.hidden = !state.hasMore;
        } finally { state.busy = false; }
    }).observe(more);

    /* ------------------------------ the sheet ------------------------------ */
    let formTags = [];

    function paintFormTags() {
        const box = $('ctfTags'), input = $('ctfTagInput');
        box.querySelectorAll('.ctf-tag').forEach((el) => el.remove());
        for (const t of formTags) {
            const chip = document.createElement('span');
            chip.className = 'ctf-tag';
            chip.innerHTML = `${esc(t)}<button type="button" aria-label="Remove ${esc(t)}"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:.7rem;height:.7rem"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>`;
            chip.querySelector('button').addEventListener('click', () => { formTags = formTags.filter((x) => x !== t); paintFormTags(); });
            box.insertBefore(chip, input);
        }
        const sugs = $('ctfSugs');
        sugs.innerHTML = '';
        // The user's own vocabulary first, the starter list after — no repeats.
        const pool = [...new Set([...state.tags, ...SUGGESTIONS])];
        for (const s of pool) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ctf-sug' + (formTags.some((t) => t.toLowerCase() === s.toLowerCase()) ? ' is-on' : '');
            b.textContent = s;
            b.addEventListener('click', () => {
                const i = formTags.findIndex((t) => t.toLowerCase() === s.toLowerCase());
                if (i >= 0) formTags.splice(i, 1); else if (formTags.length < 10) formTags.push(s);
                paintFormTags();
            });
            sugs.appendChild(b);
        }
    }

    function addTagFromInput() {
        const input = $('ctfTagInput');
        const t = input.value.trim().replace(/,+$/, '');
        if (t && formTags.length < 10 && !formTags.some((x) => x.toLowerCase() === t.toLowerCase())) {
            formTags.push(t);
            paintFormTags();
        }
        input.value = '';
    }

    function openSheetFor(contact) {
        state.editing = contact || null;
        $('ctSheetTitle').textContent = contact ? 'Edit contact' : 'New contact';
        $('ctfName').value = contact?.name || '';
        $('ctfPhone').value = contact?.phone || '';
        $('ctfEmail').value = contact?.email || '';
        $('ctfCompany').value = contact?.company || '';
        $('ctfAddress').value = contact?.address || '';
        $('ctfNotes').value = contact?.notes || '';
        $('ctfDelete').hidden = !contact;
        formTags = [...(contact?.tags || [])];
        paintFormTags();
        window.openSheet('ctSheet');
        if (!contact) setTimeout(() => $('ctfName').focus(), 250);
    }

    $('ctfTagInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTagFromInput(); }
    });
    $('ctfTagInput').addEventListener('blur', addTagFromInput);

    $('ctfSave').addEventListener('click', async () => {
        const name = $('ctfName').value.trim();
        if (!name) { window.toast?.('A contact needs at least a name.', 'error'); $('ctfName').focus(); return; }
        addTagFromInput();
        const body = {
            name,
            phone: $('ctfPhone').value.trim() || null,
            email: $('ctfEmail').value.trim() || null,
            company: $('ctfCompany').value.trim() || null,
            address: $('ctfAddress').value.trim() || null,
            notes: $('ctfNotes').value.trim() || null,
            tags: formTags,
        };
        const btn = $('ctfSave');
        btn.disabled = true;
        try {
            const url = state.editing ? URLS.one(state.editing.id) : URLS.store;
            const res = await window.api(url, { method: 'POST', body });
            window.toast?.(res.message || 'Saved.');
            document.getElementById('ctSheet').querySelector('[data-sheet-close]').click();
            reload();
        } catch (e) {
            window.toast?.(e.message || 'Could not save.', 'error');
        } finally { btn.disabled = false; }
    });

    $('ctfDelete').addEventListener('click', async () => {
        if (!state.editing) return;
        const sure = await window.confirmAction?.({
            title: 'Remove ' + state.editing.name + '?',
            message: 'They leave the phonebook. You can always add them again.',
            confirmText: 'Remove',
        });
        if (!sure) return;
        try {
            const res = await window.api(URLS.one(state.editing.id) + '/delete', { method: 'POST' });
            window.toast?.(res.message || 'Removed.');
            document.getElementById('ctSheet').querySelector('[data-sheet-close]').click();
            reload();
        } catch (e) {
            window.toast?.(e.message || 'Could not remove.', 'error');
        }
    });

    $('ctAddBtn').addEventListener('click', () => openSheetFor(null));
    document.addEventListener('click', (e) => {
        if (e.target.closest?.('[data-ct-add]')) openSheetFor(null);
    });

    let deb;
    $('ctSearch').addEventListener('input', () => {
        clearTimeout(deb);
        deb = setTimeout(() => { state.q = $('ctSearch').value.trim(); reload(); }, 250);
    });

    reload();
})();
</script>
@endpush
