@extends('layouts.app')

@section('title', 'Contact List')
@section('page-title', 'Contact List')
@section('page-subtitle', 'Your farm\'s phonebook')
@section('back', route('sm.index'))
@section('help-key', 'contacts')

@include('partials.tag-sheet-css')

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

    /* The search door: an icon beside the full-width Add. */
    .ct-searchbtn { flex: none; width: 3rem; height: 3rem; border-radius: .9rem; display: flex;
        align-items: center; justify-content: center; color: var(--color-gray-600);
        background: var(--color-white); border: 1px solid var(--color-gray-300);
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ct-searchbtn:hover { color: var(--color-brand-700); border-color: var(--color-brand-400); }
    .ct-searchbtn svg { width: 1.2rem; height: 1.2rem; }
    .ct-filterpill { display: inline-flex; align-items: center; gap: .45rem; font-size: .78rem;
        font-weight: 800; color: var(--color-brand-700); background: var(--color-brand-50);
        border: 1px solid var(--color-brand-100); border-radius: 999px; padding: .35rem .5rem .35rem .8rem; }
    .ct-filterpill button { width: 1.2rem; height: 1.2rem; border-radius: 999px; display: flex;
        align-items: center; justify-content: center; }
    .ct-filterpill button:hover { background: var(--color-brand-100); }

    /* The tag mount inside the form — the main tags picker's clothes. */
    .ctf-mount { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .ctf-tag { display: inline-flex; align-items: center; gap: .3rem; max-width: 100%;
        padding: .28rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-brand-50); color: var(--color-brand-800);
        border: 1px solid var(--color-brand-200); }
    .ctf-tag button { display: inline-flex; padding: .1rem; border-radius: 999px; color: inherit; opacity: .6; }
    .ctf-tag button:hover { opacity: 1; }
    .ctf-tag svg { width: .7rem; height: .7rem; }
    .ctf-add { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .65rem;
        border-radius: 999px; font-size: .74rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-500); background: var(--color-white);
        border: 1px dashed var(--color-gray-300);
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ctf-add:hover { color: var(--color-brand-700); border-color: var(--color-brand-400); }
    .ctf-add svg { width: .8rem; height: .8rem; }

    /* A repeatable field: the input, and the way to take it back out. Rows
       arrive and leave animated, so adding a second number reads as the form
       growing rather than as the page jumping. */
    .ct-lines { display: flex; flex-direction: column; gap: .5rem; }
    .ct-line { display: flex; align-items: center; gap: .5rem;
        opacity: 0; transform: translateY(-6px);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .ct-line.is-in { opacity: 1; transform: none; }
    .ct-line.is-out { opacity: 0; transform: translateY(-6px); }
    .ct-line .form-input { flex: 1 1 auto; min-width: 0; }
    .ct-line-drop { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        color: var(--color-gray-400); background: var(--color-gray-50);
        border: 1px solid var(--color-gray-200);
        transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ct-line-drop:hover { color: #dc2626; background: #fef2f2; border-color: #fecaca; }
    .ct-line-drop svg { width: .9rem; height: .9rem; }

    /* The place buttons wear the tag chips' clothes: the same pill the tags
       row above them uses, so "pick one of these" looks like one gesture
       wherever the form asks it. Filled, they go brand-coloured. */
    .ctf-place { display: inline-flex; align-items: center; gap: .4rem; padding: .42rem .8rem;
        border-radius: 999px; font-size: .78rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-500); background: var(--color-white);
        border: 1px dashed var(--color-gray-300); max-width: 100%;
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            background .28s cubic-bezier(.22,1,.36,1); }
    .ctf-place svg { width: .9rem; height: .9rem; flex: none; }
    .ctf-place span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ctf-place:hover:not(:disabled) { color: var(--color-brand-700); border-color: var(--color-brand-400); }
    .ctf-place.is-set { color: var(--color-brand-800); background: var(--color-brand-50);
        border-style: solid; border-color: var(--color-brand-200); }
    .ctf-place:disabled { opacity: .5; cursor: not-allowed; }
    .ct-placelist { max-height: 22rem; overflow-y: auto; }

    @media (prefers-reduced-motion: reduce) {
        .ct-row { transition: none; opacity: 1; transform: none; }
        .ct-act, .ct-chip, .ct-searchbtn, .ctf-add, .ctf-place { transition: none; }
        .ct-line { transition: none; opacity: 1; transform: none; }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Toolbar: the search behind one icon, and one wide door for adding. --}}
    <div class="flex items-center gap-2.5 mb-3">
        <button type="button" id="ctSearchBtn" class="ct-searchbtn" title="Search contacts" aria-label="Search contacts">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        </button>
        <button type="button" id="ctAddBtn" class="btn btn-primary grow justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
            Add a New Contact
        </button>
    </div>

    {{-- What the list is currently narrowed by, with its way off. --}}
    <div id="ctFilterRow" class="mb-2" hidden>
        <span class="ct-filterpill">
            <span id="ctFilterSay"></span>
            <button type="button" id="ctFilterClear" aria-label="Clear the search">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </span>
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

{{-- The search, behind its own small sheet. Typing filters the list live
     underneath; closing keeps the filter (the pill above shows the way off). --}}
<div class="sheet hidden" id="ctSearchSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Search contacts</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            {{-- pl-10!, not pl-10: .form-input sets its padding through
                 @apply px-4, which lands later in the sheet than the plain
                 utility and wins the tie — the field kept its 1rem inset and
                 the magnifier sat on top of the placeholder's first letter. --}}
            <input type="search" id="ctSearch" class="form-input pl-10!" placeholder="Name, number, company, tag…" autocomplete="off">
        </div>
        <p class="form-hint">The list behind updates as you type.</p>
    </div>
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
        {{-- A person rarely has one number. Rows are added and taken away
             here; the first of each list is the one a contact card's Call
             and Email buttons use. --}}
        <div>
            <label class="form-label">Mobile numbers</label>
            <div class="ct-lines" id="ctfPhones"></div>
            <button type="button" class="ctf-add mt-2" data-ct-more="phone">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                Add another number
            </button>
        </div>
        <div>
            <label class="form-label">Emails <span class="text-gray-400 font-normal">(optional)</span></label>
            <div class="ct-lines" id="ctfEmails"></div>
            <button type="button" class="ctf-add mt-2" data-ct-more="email">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                Add another email
            </button>
        </div>
        <div>
            <label class="form-label" for="ctfCompany">Business / role</label>
            <textarea id="ctfCompany" class="form-input" rows="2" style="padding-top:.7rem;padding-bottom:.7rem"
                      placeholder="Tractor rental, harvester crew — what they do for the farm" maxlength="500"></textarea>
        </div>
        {{-- Where they are, in the order a farmer actually knows it: the
             street and the sitio in their own words, then the province and
             the town picked from the list, because nobody should have to
             spell "Zamboanga Sibugay" to find themselves in a phonebook. --}}
        <div>
            <label class="form-label" for="ctfAddress">Address line 1</label>
            <textarea id="ctfAddress" class="form-input" rows="2" style="padding-top:.7rem;padding-bottom:.7rem"
                      placeholder="House no., street, purok or sitio" maxlength="255"></textarea>
        </div>
        <div>
            <label class="form-label" for="ctfAddress2">Address line 2 <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ctfAddress2" class="form-input" rows="2" style="padding-top:.7rem;padding-bottom:.7rem"
                      placeholder="Barangay, landmark, anything that helps you find it again" maxlength="255"></textarea>
        </div>
        <div>
            <label class="form-label">Province and town</label>
            <div class="ctf-mount">
                <button type="button" class="ctf-place" id="ctfProvinceBtn">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="2.5"/></svg>
                    <span id="ctfProvinceSay">Pick a province</span>
                </button>
                {{-- The town button waits for a province: an unnarrowed list
                     of 1,647 municipalities is not a choice, it is a search. --}}
                <button type="button" class="ctf-place" id="ctfTownBtn" disabled>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/></svg>
                    <span id="ctfTownSay">Pick a town</span>
                </button>
            </div>
        </div>
        <div>
            <label class="form-label">Tags</label>
            {{-- The same tag experience the whole app uses: chips on the
                 form, a sheet to pick or coin them. --}}
            <div class="ctf-mount" id="ctfTagsMount"></div>
        </div>
        <div>
            <label class="form-label" for="ctfNotes">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ctfNotes" class="form-input" rows="4" style="padding-top:.7rem;padding-bottom:.7rem"
                      placeholder="Rates, landmarks, who referred them…" maxlength="2000"></textarea>
        </div>
        <button type="button" id="ctfDelete" class="btn w-full text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" hidden>Remove this contact</button>
    </div>
    <div class="sheet-footer">
        {{-- No Cancel: the ✕ in the header already is one. --}}
        <button type="button" class="btn btn-primary w-full" id="ctfSave">Save Contact</button>
    </div>
</div>

{{-- The tag sheet — the main tags module's shape, on the phonebook's own
     (personal) tag vocabulary. --}}
<div class="sheet hidden" id="ctTagSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tags</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="flex gap-2 mb-3">
            <input type="text" class="form-input grow" id="ctTagNew" maxlength="30"
                   placeholder="Tag name — e.g. pest problem" autocomplete="off" enterkeyhint="done">
            <button type="button" class="btn btn-primary shrink-0" id="ctTagAdd">Add</button>
        </div>
        <div class="dt-rows" id="ctTagList"></div>
    </div>
</div>

{{-- One sheet, two errands: the 87 provinces, or the towns of whichever
     province was picked. A filter box on top because 1,647 municipalities
     is a long thumb-scroll even once narrowed to one province. --}}
<div class="sheet hidden" id="ctPlaceSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ctPlaceTitle">Province</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="search" class="form-input mb-3" id="ctPlaceFilter" placeholder="Type to narrow the list…" autocomplete="off">
        <div class="dt-rows ct-placelist" id="ctPlaceList"></div>
        <p class="form-hint" id="ctPlaceNone" hidden>Nothing by that name.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URLS = {
        list: @json(route('contacts.list')),
        store: @json(route('contacts.store')),
        places: @json(route('contacts.places')),
        one: (id) => @json(url('/app/contacts')) + '/' + id,
    };
    const SUGGESTIONS = ['Worker', 'Tractor Rental', 'Harvester', 'Seed Supplier', 'Fertilizer Dealer', 'Buyer', 'Technician', 'Driver', 'Irrigation', 'Landlord'];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const $ = (id) => document.getElementById(id);
    const list = $('ctList'), chips = $('ctChips'), empty = $('ctEmpty'), loading = $('ctLoading'), more = $('ctMore');

    const state = { q: '', tag: '', page: 1, hasMore: false, busy: false, editing: null, tags: [], tagCounts: {} };

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
        // What they do, then where they are — the town and province first,
        // because that is what a farmer scanning the book is matching on.
        const where = [c.town, c.province].filter(Boolean).join(', ') || c.address || '';
        const subBits = [c.company, where].filter(Boolean).join(' · ');
        const moreNums = Math.max(0, (c.phones || []).length - 1);
        el.innerHTML = `
            <span class="ct-face" style="background:hsl(${hueOf(c.name)} 45% 42%)">${esc(initials(c.name))}</span>
            <div class="ct-main" role="button" tabindex="0" aria-label="Edit ${esc(c.name)}">
                <p class="ct-name">${esc(c.name)}</p>
                ${subBits ? `<p class="ct-sub">${esc(subBits)}</p>` : ''}
                ${c.phone ? `<p class="ct-sub">${esc(c.phone)}${moreNums ? ` <span style="opacity:.7">+${moreNums} more</span>` : ''}</p>` : ''}
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
        state.tagCounts = tagCounts || {};
        state.tags = Object.keys(state.tagCounts);
        if (!state.tags.length) { chips.hidden = true; return; }
        chips.hidden = false;
        chips.innerHTML = '';
        const all = document.createElement('button');
        all.type = 'button';
        all.className = 'ct-chip' + (state.tag === '' ? ' is-on' : '');
        all.textContent = 'All';
        all.addEventListener('click', () => { state.tag = ''; reload(); });
        chips.appendChild(all);
        for (const [tag, n] of Object.entries(state.tagCounts)) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ct-chip' + (state.tag === tag ? ' is-on' : '');
            b.innerHTML = `${esc(tag)} <small>${n}</small>`;
            b.addEventListener('click', () => { state.tag = state.tag === tag ? '' : tag; reload(); });
            chips.appendChild(b);
        }
    }

    function paintFilterPill() {
        const row = $('ctFilterRow');
        row.hidden = state.q === '';
        if (state.q !== '') $('ctFilterSay').textContent = 'Searching: "' + state.q + '"';
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
        paintFilterPill();
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

    /* ------------------------- the tag mount + sheet ------------------------ */
    let formTags = [];

    function paintTagMount() {
        const mount = $('ctfTagsMount');
        mount.innerHTML = formTags.map((t) => `
            <span class="ctf-tag" data-ct-tag="${esc(t)}"><span>${esc(t)}</span>
                <button type="button" aria-label="Remove tag ${esc(t)}"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            </span>`).join('')
            + `<button type="button" class="ctf-add" id="ctfTagOpen">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                ${formTags.length ? 'Tags' : 'Add tags'} <i style="font-style:normal;opacity:.7">(optional)</i></button>`;
        mount.querySelectorAll('.ctf-tag button').forEach((b) => {
            b.addEventListener('click', () => {
                const t = b.closest('.ctf-tag').dataset.ctTag;
                formTags = formTags.filter((x) => x !== t);
                paintTagMount();
            });
        });
        mount.querySelector('#ctfTagOpen').addEventListener('click', () => {
            paintTagSheet();
            window.openSheet('ctTagSheet');
        });
    }

    function tagPool() {
        // The member's own vocabulary first, the starter list after — no repeats.
        return [...new Set([...state.tags, ...SUGGESTIONS, ...formTags])];
    }

    function paintTagSheet() {
        const box = $('ctTagList');
        box.innerHTML = tagPool().map((t) => `
            <button type="button" class="dt-row${formTags.some((x) => x.toLowerCase() === t.toLowerCase()) ? ' is-on' : ''}" data-ct-pick="${esc(t)}">
                <span class="dt-row-e">🏷️</span>
                <span class="dt-row-body"><b>${esc(t)}</b>${state.tagCounts[t] ? `<i>on ${state.tagCounts[t]} ${state.tagCounts[t] === 1 ? 'contact' : 'contacts'}</i>` : ''}</span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
    }

    $('ctTagList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-ct-pick]');
        if (!row) return;
        const t = row.dataset.ctPick;
        const i = formTags.findIndex((x) => x.toLowerCase() === t.toLowerCase());
        if (i >= 0) formTags.splice(i, 1);
        else if (formTags.length < 10) formTags.push(t);
        row.classList.toggle('is-on', i < 0 && formTags.length <= 10);
        paintTagMount();
    });

    function addTagFromInput() {
        const inp = $('ctTagNew');
        const t = inp.value.trim().replace(/,+$/, '');
        if (t && formTags.length < 10 && !formTags.some((x) => x.toLowerCase() === t.toLowerCase())) {
            formTags.push(t);
            paintTagMount();
            paintTagSheet();
        }
        inp.value = '';
    }
    $('ctTagAdd').addEventListener('click', addTagFromInput);
    $('ctTagNew').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTagFromInput(); }
    });

    /* --------------------- the repeatable phone/email rows ------------------ */
    const LINE_KINDS = {
        phone: { mount: 'ctfPhones', type: 'tel', max: 40, hint: '09XXXXXXXXX', drop: 'Remove this number' },
        email: { mount: 'ctfEmails', type: 'email', max: 150, hint: 'name@example.com', drop: 'Remove this email' },
    };

    function addLine(kind, value = '', animate = true) {
        const cfg = LINE_KINDS[kind];
        const mount = $(cfg.mount);
        if (mount.children.length >= 10) return;
        const row = document.createElement('div');
        row.className = 'ct-line';
        row.innerHTML = `
            <input type="${cfg.type}" class="form-input" placeholder="${cfg.hint}" maxlength="${cfg.max}" autocomplete="off">
            <button type="button" class="ct-line-drop" aria-label="${cfg.drop}" title="${cfg.drop}">
                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>`;
        row.querySelector('input').value = value;
        // The last row keeps its ✕ hidden: a field with no way back is
        // clearer than one whose only row can be deleted into nothing.
        row.querySelector('.ct-line-drop').addEventListener('click', () => dropLine(kind, row));
        mount.appendChild(row);
        if (reduceMotion || !animate) row.classList.add('is-in');
        else requestAnimationFrame(() => requestAnimationFrame(() => row.classList.add('is-in')));
        paintLineDrops(kind);
    }

    function dropLine(kind, row) {
        const mount = $(LINE_KINDS[kind].mount);
        if (mount.children.length <= 1) { row.querySelector('input').value = ''; return; }
        if (reduceMotion) { row.remove(); paintLineDrops(kind); return; }
        row.classList.remove('is-in');
        row.classList.add('is-out');
        setTimeout(() => { row.remove(); paintLineDrops(kind); }, 260);
    }

    function paintLineDrops(kind) {
        const mount = $(LINE_KINDS[kind].mount);
        const only = mount.querySelectorAll('.ct-line').length <= 1;
        mount.querySelectorAll('.ct-line-drop').forEach((b) => { b.style.visibility = only ? 'hidden' : 'visible'; });
    }

    function readLines(kind) {
        return [...$(LINE_KINDS[kind].mount).querySelectorAll('input')]
            .map((i) => i.value.trim())
            .filter(Boolean);
    }

    function setLines(kind, values) {
        $(LINE_KINDS[kind].mount).innerHTML = '';
        const list = (values || []).filter(Boolean);
        if (!list.length) addLine(kind, '', false);
        else list.forEach((v) => addLine(kind, v, false));
    }

    document.addEventListener('click', (e) => {
        const b = e.target.closest?.('[data-ct-more]');
        if (b) addLine(b.dataset.ctMore);
    });

    /* ----------------------------- the place pickers ----------------------- */
    const place = { province: '', town: '', mode: 'province', items: [] };

    function paintPlaceButtons() {
        const pb = $('ctfProvinceBtn'), tb = $('ctfTownBtn');
        $('ctfProvinceSay').textContent = place.province || 'Pick a province';
        pb.classList.toggle('is-set', !!place.province);
        $('ctfTownSay').textContent = place.town || (place.province ? 'Pick a town' : 'Province first');
        tb.classList.toggle('is-set', !!place.town);
        tb.disabled = !place.province;
    }

    async function openPlaces(mode) {
        place.mode = mode;
        $('ctPlaceTitle').textContent = mode === 'province' ? 'Province' : 'Town or city';
        $('ctPlaceFilter').value = '';
        $('ctPlaceList').innerHTML = '<p class="text-sm text-gray-400 py-3">Loading…</p>';
        window.openSheet('ctPlaceSheet');
        try {
            const qs = mode === 'town' ? '?province=' + encodeURIComponent(place.province) : '';
            const res = await window.api(URLS.places + qs);
            place.items = res.data.items || [];
            paintPlaceList();
        } catch (err) {
            $('ctPlaceList').innerHTML = '';
            window.toast?.(err.message || 'Could not load places.', 'error');
        }
    }

    function paintPlaceList() {
        const needle = $('ctPlaceFilter').value.trim().toLowerCase();
        const shown = needle ? place.items.filter((n) => n.toLowerCase().includes(needle)) : place.items;
        const chosen = place.mode === 'province' ? place.province : place.town;
        $('ctPlaceNone').hidden = shown.length > 0;
        $('ctPlaceList').innerHTML = shown.map((n) => `
            <button type="button" class="dt-row${n === chosen ? ' is-on' : ''}" data-ct-place="${esc(n)}">
                <span class="dt-row-body"><b>${esc(n)}</b></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
    }

    $('ctPlaceFilter').addEventListener('input', paintPlaceList);
    $('ctPlaceList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-ct-place]');
        if (!row) return;
        const picked = row.dataset.ctPlace;
        if (place.mode === 'province') {
            // A new province cannot keep the old province's town.
            if (picked !== place.province) place.town = '';
            place.province = picked;
        } else {
            place.town = picked;
        }
        paintPlaceButtons();
        $('ctPlaceSheet').querySelector('[data-sheet-close]').click();
    });
    $('ctfProvinceBtn').addEventListener('click', () => openPlaces('province'));
    $('ctfTownBtn').addEventListener('click', () => { if (place.province) openPlaces('town'); });

    /* ------------------------------ the sheet ------------------------------ */
    function openSheetFor(contact) {
        state.editing = contact || null;
        $('ctSheetTitle').textContent = contact ? 'Edit contact' : 'New contact';
        $('ctfName').value = contact?.name || '';
        setLines('phone', contact?.phones?.length ? contact.phones : (contact?.phone ? [contact.phone] : []));
        setLines('email', contact?.emails?.length ? contact.emails : (contact?.email ? [contact.email] : []));
        $('ctfCompany').value = contact?.company || '';
        $('ctfAddress').value = contact?.address || '';
        $('ctfAddress2').value = contact?.address2 || '';
        place.province = contact?.province || '';
        place.town = contact?.town || '';
        paintPlaceButtons();
        $('ctfNotes').value = contact?.notes || '';
        $('ctfDelete').hidden = !contact;
        formTags = [...(contact?.tags || [])];
        paintTagMount();
        window.openSheet('ctSheet');
        /* NO FOCUS. Focusing the name threw the phone's keypad up over half
           the form before the farmer had seen any of it — and the first
           thing they do is often pick a tag, not type. The field is one tap
           away for anyone who did come here to type. */
    }

    $('ctfSave').addEventListener('click', async () => {
        const name = $('ctfName').value.trim();
        if (!name) { window.toast?.('A contact needs at least a name.', 'error'); $('ctfName').focus(); return; }
        const body = {
            name,
            phones: readLines('phone'),
            emails: readLines('email'),
            company: $('ctfCompany').value.trim() || null,
            address: $('ctfAddress').value.trim() || null,
            address2: $('ctfAddress2').value.trim() || null,
            province: place.province || null,
            town: place.town || null,
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

    /* ------------------------------ the search ----------------------------- */
    $('ctSearchBtn').addEventListener('click', () => {
        window.openSheet('ctSearchSheet');
        setTimeout(() => $('ctSearch').focus(), 250);
    });
    let deb;
    $('ctSearch').addEventListener('input', () => {
        clearTimeout(deb);
        deb = setTimeout(() => { state.q = $('ctSearch').value.trim(); reload(); }, 250);
    });
    $('ctFilterClear').addEventListener('click', () => {
        state.q = '';
        $('ctSearch').value = '';
        reload();
    });

    // window.api lives in the deferred module bundle, which evaluates after
    // inline scripts — booting on `load` is what makes the first paint
    // reliable instead of a race the page sometimes lost.
    if (document.readyState === 'complete') reload();
    else window.addEventListener('load', () => reload(), { once: true });
})();
</script>
@endpush
