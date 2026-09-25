{{-- ================================================================
     The tag picker — one shared sheet, many little chip rows.

     Any form that wants tags renders:
         <div class="tp-mount" data-tags data-tags-kind="activity"></div>
     and its JS calls:
         smTags.mount(el)            — paint the row (once; safe to repeat)
         smTags.set(el, [{id,name}]) — edit flows
         smTags.value(el)            — [ids] to ride the save payload
         smTags.clear(el)            — fresh add forms

     The sheet lists the schedule's existing tags (multi-select) and takes
     a new name at the top; a new tag is created the moment it is added,
     so a form only ever submits ids. Needs $schedule in scope.

     Under the season's own list it offers the member's OTHER words — the
     tags of their other seasons and of the things outside any season
     (GET /app/my-tags, the one vocabulary). Tapping one coins it in THIS
     season (the same find-or-revive store) and ticks it.
     ================================================================ --}}
@once
@include('partials.tag-sheet-css')
<style>
    .tp-row { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .tp-chip { display: inline-flex; align-items: center; gap: .3rem; max-width: 100%;
        padding: .28rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-brand-50); color: var(--color-brand-800);
        border: 1px solid var(--color-brand-200); }
    .tp-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tp-chip button { display: inline-flex; padding: .1rem; border-radius: 999px; color: inherit; opacity: .6; }
    .tp-chip button:hover { opacity: 1; }
    .tp-chip svg { width: .7rem; height: .7rem; }
    .tp-add { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .65rem;
        border-radius: 999px; font-size: .74rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-500); background: var(--color-white);
        border: 1px dashed var(--color-gray-300);
        transition: color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .tp-add:hover { color: var(--color-brand-700); border-color: var(--color-brand-400); }
    .tp-add svg { width: .8rem; height: .8rem; }
    html.dark .tp-chip { background: #22301a; color: #cfe6b8; border-color: #2b3a1c; }
    html.dark .tp-add { background: #151b12; border-color: #3a414c; color: #93a684; }
    html.dark .tp-add:hover { color: #cfe6b8; border-color: #4a7c2a; }

    #tagPickEmpty { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1.2rem 0; }

    /* The member's other words, under the season's own list. */
    .tp-more { margin-top: 1rem; padding-top: .85rem; border-top: 1px dashed var(--color-gray-200); }
    .tp-more-h { font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: var(--color-gray-500); margin: 0 0 .5rem; }
    .tp-more .dt-row-e { color: var(--color-brand-600); font-weight: 800; }
    .tp-more .dt-row.is-busy { opacity: .55; pointer-events: none; }
    html.dark .tp-more { border-top-color: #2b3a1c; }
    /* A row arriving (a word just taken into this season, or the section
       itself) eases in rather than snapping into place. */
    @keyframes tpIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    .tp-in { animation: tpIn .28s cubic-bezier(.22,1,.36,1) both; }
    @media (prefers-reduced-motion: reduce) { .tp-in { animation: none; } }
    html.sm-still .tp-in { animation: none; }
</style>
@push('sheets')
<div class="sheet hidden" id="tagPickSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tags</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        {{-- Just a door: the name is asked for in its own sheet, so this
             one stays a clean list to pick from. --}}
        <button type="button" class="btn btn-primary w-full mb-3" id="tagPickNewBtn">Add a New Tag</button>
        <div class="dt-rows" id="tagPickList"></div>
        <p id="tagPickEmpty" hidden>No tags yet — the first one starts the list.</p>
        {{-- Words from the member's other seasons and tools; a tap brings one here. --}}
        <div class="tp-more" id="tagPickMore" hidden>
            <p class="tp-more-h">From your other seasons and tools</p>
            <div class="dt-rows" id="tagPickMoreList"></div>
        </div>
    </div>
</div>

{{-- The second sheet: one field, one save. Opens over the picker and
     drops back onto it with the new tag already ticked. --}}
<div class="sheet hidden" id="tagNewSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a New Tag</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="tagPickNew">Tag name</label>
        <input type="text" class="form-input" id="tagPickNew" maxlength="60"
               placeholder="Tag name — e.g. pest problem" autocomplete="off" enterkeyhint="done">
        <button type="button" class="btn btn-primary w-full mt-3" id="tagPickAdd">Save tag</button>
    </div>
</div>
@endpush
@push('scripts')
<script>
(() => {
    /* The activities shell renders module panes as their own documents and
       injects them, so this partial can arrive TWICE on one page — the
       known double-render trap. Two copies meant two sheets sharing one
       set of listeners: the sheet a finger saw could be the dead twin,
       with an Add button that answers nothing. One copy rules: the first
       script claims its sheet, every later arrival sweeps its own markup
       away and leaves, and the sweep re-runs at every use in case a pane
       injected markup without running its script. */
    const claimSheet = () => {
        ['tagPickSheet', 'tagNewSheet'].forEach((id) => {
            const s = document.getElementById(id);
            if (s && !s.dataset.tpAlive) s.dataset.tpAlive = '1';
        });
    };
    const sweepDupes = () => {
        document.querySelectorAll('[id=tagPickSheet]:not([data-tp-alive]), [id=tagNewSheet]:not([data-tp-alive])')
            .forEach((el) => el.remove());
    };
    if (window.__smTagsAlive) {
        // Only the owner claims; a later copy just clears its own markup.
        // (Claiming here would bless the dead twin the moment it arrived.)
        sweepDupes();
        return;
    }
    window.__smTagsAlive = true;
    claimSheet();
    sweepDupes();

    // The schedule can change under the picker (the dashboard's quick
    // tools ask which season a capture belongs to), so the urls are read
    // when used and setSchedule() throws the dictionary away.
    const LIST_URL = @json(route('sm.tags.list'));
    const OF_URL = @json(route('sm.tags.of'));
    let SCHED = {{ (int) optional($schedule ?? null)->id }};
    const U = {
        list: () => LIST_URL + '?id=' + SCHED,
        store: @json(route('sm.tags.store')),
        of: () => OF_URL + '?id=' + SCHED,
    };
    const MINE_URL = @json(route('tags.mine'));
    const MOUNTS = [];
    const esc = (s) => (window.escapeHtml ? window.escapeHtml(String(s ?? '')) : String(s ?? ''));
    let MORE = null;         // the member's whole vocabulary (other seasons + outside things)
    let MOREP = null;
    let MOREVIEW = [];       // the words the sheet is showing right now, by row index
    let FRESH = 0;           // the tag just brought in, so its row eases in
    let ALL = null;          // the schedule's tags, fetched once and kept fresh on writes
    let ALLP = null;         // the fetch in flight, so two callers share one trip
    let HOST = null;         // the mount the sheet is currently editing

    function ensureAll() {
        if (ALL) return Promise.resolve(ALL);
        if (!SCHED) { ALL = []; return Promise.resolve(ALL); }
        if (!ALLP) {
            ALLP = api(U.list())
                .then((res) => { ALL = (res.data.tags || []); return ALL; })
                .catch(() => { ALL = []; return ALL; })
                .finally(() => { ALLP = null; });
        }
        return ALLP;
    }

    function ensureMore() {
        if (MORE) return Promise.resolve(MORE);
        if (!MOREP) {
            MOREP = api(MINE_URL)
                .then((res) => { MORE = (res.data.tags || []); return MORE; })
                .catch(() => { MORE = []; return MORE; })
                .finally(() => { MOREP = null; });
        }
        return MOREP;
    }

    // The words this season does not have yet, from everywhere else the
    // member has tagged something.
    function paintMore() {
        const box = document.getElementById('tagPickMore');
        const list = document.getElementById('tagPickMoreList');
        if (!box || !list) return;
        const here = new Set((ALL || []).map((t) => String(t.name).toLowerCase()));
        MOREVIEW = SCHED ? (MORE || []).filter((w) => w && w.name && !here.has(String(w.name).toLowerCase())) : [];
        const wasHidden = box.hidden;
        list.innerHTML = MOREVIEW.map((w, i) => {
            const bits = [];
            if (w.seasons) bits.push(`${w.seasons} in your other seasons`);
            if (w.things) bits.push(`on ${w.things} ${w.things === 1 ? 'thing' : 'things'} outside a season`);
            return `<button type="button" class="dt-row" data-tp-word="${i}">
                <span class="dt-row-e">+</span>
                <span class="dt-row-body"><b>${esc(w.name)}</b><i>${esc(bits.length ? bits.join(' · ') : 'from your other seasons')} · tap to use it here</i></span>
            </button>`;
        }).join('');
        box.hidden = MOREVIEW.length === 0;
        if (wasHidden && !box.hidden) { box.classList.remove('tp-in'); void box.offsetWidth; box.classList.add('tp-in'); }
    }

    function paintRow(el) {
        const tags = el._tags || [];
        el.innerHTML = `<div class="tp-row">`
            + tags.map((t) => `<span class="tp-chip" data-tp-id="${t.id}"><span>${esc(t.name)}</span>`
                + `<button type="button" data-tp-off="${t.id}" aria-label="Remove tag ${esc(t.name)}">`
                + `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>`
                + `</button></span>`).join('')
            + `<button type="button" class="tp-add" data-tp-open>`
            + `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>`
            + `${tags.length ? 'Tags' : 'Add tags'} <i style="font-style:normal;opacity:.7">(optional)</i></button></div>`;
    }

    function paintSheet() {
        const list = document.getElementById('tagPickList');
        const chosen = new Set((HOST?._tags || []).map((t) => t.id));
        list.innerHTML = (ALL || []).map((t) => `
            <button type="button" class="dt-row${chosen.has(t.id) ? ' is-on' : ''}${t.id === FRESH ? ' tp-in' : ''}" data-tp-pick="${t.id}">
                <span class="dt-row-e">🏷️</span>
                <span class="dt-row-body"><b>${esc(t.name)}</b>${t.count ? `<i>tied to ${t.count} ${t.count === 1 ? 'thing' : 'things'}</i>` : ''}</span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        document.getElementById('tagPickEmpty').hidden = (ALL || []).length > 0;
        FRESH = 0;
        paintMore();
    }

    window.smTags = {
        mount(el, tags) {
            if (!el || el._tpMounted) { if (el && tags) this.set(el, tags); return; }
            sweepDupes();   // a pane may have injected a dead twin since load
            el._tpMounted = true;
            el._tags = tags || [];
            MOUNTS.push(el);
            paintRow(el);
            // Warm the dictionary now, quietly — by the time a finger finds
            // the chip, the list is usually already home. Over a slow line
            // this is the difference between "instant" and "is it broken?".
            ensureAll();
            el.addEventListener('click', async (e) => {
                const off = e.target.closest('[data-tp-off]');
                if (off) {
                    el._tags = (el._tags || []).filter((t) => t.id !== Number(off.dataset.tpOff));
                    paintRow(el);
                    return;
                }
                if (e.target.closest('[data-tp-open]')) {
                    sweepDupes();
                    HOST = el;
                    // The sheet opens THE MOMENT the chip is tapped — a tap
                    // that answers seconds later reads as a hang. If the
                    // dictionary is still travelling, the sheet says so and
                    // fills in when it lands.
                    if (ALL) {
                        paintSheet();
                    } else {
                        document.getElementById('tagPickList').innerHTML =
                            '<p id="tagPickLoading" style="font-size:.8rem;color:var(--color-gray-400);text-align:center;padding:1.2rem 0">Getting your tags…</p>';
                        document.getElementById('tagPickEmpty').hidden = true;
                    }
                    openSheet('tagPickSheet');
                    // The member's other words travel alongside and fill in
                    // under the list when they land.
                    ensureMore().then(() => { if (HOST === el) paintMore(); });
                    await ensureAll();
                    paintSheet();
                }
            });
        },
        set(el, tags) { if (!el) return; el._tags = tags || []; if (el._tpMounted) paintRow(el); else this.mount(el, el._tags); },
        clear(el) { this.set(el, []); },
        // Edit flows: fetch what this thing already wears. Fire-and-forget —
        // the row paints when the answer lands.
        async load(el, kind, refId, extra) {
            this.clear(el);
            if (!refId && !extra) return;
            try {
                const res = await api(U.of() + '&kind=' + encodeURIComponent(kind) + '&refId=' + (refId || 0) + (extra || ''));
                this.set(el, res.data.tags || []);
            } catch (err) { /* an untagged sheet is a fine fallback */ }
        },
        value(el) { return (el?._tags || []).map((t) => t.id); },
        // The board asks for the whole dictionary (filters, chips).
        all: ensureAll,
        invalidate() { ALL = null; },
        // Another season: forget its words, and the words every mount wore.
        setSchedule(id) {
            id = Number(id) || 0;
            if (id === SCHED) return;
            SCHED = id; ALL = null; ALLP = null;
            MOUNTS.forEach((el) => { el._tags = []; if (el._tpMounted) paintRow(el); });
            if (SCHED) ensureAll();
        },
        schedule: () => SCHED,
    };

    document.getElementById('tagPickList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-tp-pick]');
        if (!row || !HOST) return;
        const id = Number(row.dataset.tpPick);
        const t = (ALL || []).find((x) => x.id === id);
        if (!t) return;
        const has = (HOST._tags || []).some((x) => x.id === id);
        HOST._tags = has ? HOST._tags.filter((x) => x.id !== id) : [...(HOST._tags || []), { id: t.id, name: t.name }];
        row.classList.toggle('is-on', !has);
        paintRow(HOST);
    });

    // The door to the name sheet: it opens over the picker and drops back
    // onto it, the new tag already ticked.
    document.getElementById('tagPickNewBtn')?.addEventListener('click', () => {
        const inp = document.getElementById('tagPickNew');
        if (inp) inp.value = '';
        openSheet('tagNewSheet');
        window.smFocus?.(inp, { delay: 250 });
    });

    // Coin (or find) a tag by name in this season and tick it on the form.
    async function bring(name) {
        const res = await api(U.store, { method: 'POST', body: { scheduleId: SCHED, name } });
        const t = res.data.tag;
        // The server's answer IS the fresh fact — no second round-trip.
        if (!ALL) ALL = [];
        if (!ALL.some((x) => x.id === t.id)) { ALL.push(t); FRESH = t.id; }
        if (HOST && !(HOST._tags || []).some((x) => x.id === t.id)) {
            HOST._tags = [...(HOST._tags || []), { id: t.id, name: t.name }];
            paintRow(HOST);
        }
        paintSheet();
        return t;
    }

    async function createTag() {
        const inp = document.getElementById('tagPickNew');
        const name = (inp.value || '').trim();
        if (!name) { toast('Give the tag a name first.', 'error'); return; }
        const btn = document.getElementById('tagPickAdd');
        btn.disabled = true;
        try {
            await bring(name);
            inp.value = '';
            closeSheet('tagNewSheet');
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    }

    // A word from another season or tool: it becomes this season's tag.
    document.getElementById('tagPickMoreList')?.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-tp-word]');
        if (!row || !HOST) return;
        const w = MOREVIEW[Number(row.dataset.tpWord)];
        if (!w) return;
        row.classList.add('is-busy');
        try { await bring(w.name); }
        catch (err) { row.classList.remove('is-busy'); toast(err.message, 'error'); }
    });
    document.getElementById('tagPickAdd')?.addEventListener('click', createTag);
    document.getElementById('tagPickNew')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); createTag(); }
    });
})();
</script>
@endpush
@endonce
