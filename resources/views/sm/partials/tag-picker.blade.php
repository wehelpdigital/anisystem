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

    .tp-new { display: flex; gap: .5rem; margin-bottom: .8rem; }
    .tp-new input { flex: 1 1 auto; min-width: 0; }
    #tagPickEmpty { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1.2rem 0; }
</style>
@push('sheets')
<div class="sheet hidden" id="tagPickSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tags</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="tp-new">
            <input type="text" class="form-input" id="tagPickNew" maxlength="60"
                   placeholder="New tag — e.g. typhoon damage" autocomplete="off" enterkeyhint="done">
            <button type="button" class="btn btn-primary" id="tagPickAdd">Add</button>
        </div>
        <div class="dt-rows" id="tagPickList"></div>
        <p id="tagPickEmpty" hidden>No tags yet — the first one starts the list.</p>
    </div>
</div>
@endpush
@push('scripts')
<script>
(() => {
    const U = {
        list: @json(route('sm.tags.list') . '?id=' . $schedule->id),
        store: @json(route('sm.tags.store')),
        of: @json(route('sm.tags.of') . '?id=' . $schedule->id),
    };
    const SCHED = {{ (int) $schedule->id }};
    const esc = (s) => (window.escapeHtml ? window.escapeHtml(String(s ?? '')) : String(s ?? ''));
    let ALL = null;          // the schedule's tags, fetched once and kept fresh on writes
    let HOST = null;         // the mount the sheet is currently editing

    async function ensureAll() {
        if (ALL) return ALL;
        try {
            const res = await api(U.list);
            ALL = (res.data.tags || []);
        } catch (err) { ALL = []; }
        return ALL;
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
            <button type="button" class="dt-row${chosen.has(t.id) ? ' is-on' : ''}" data-tp-pick="${t.id}">
                <span class="dt-row-e">🏷️</span>
                <span class="dt-row-body"><b>${esc(t.name)}</b>${t.count ? `<i>tied to ${t.count} ${t.count === 1 ? 'thing' : 'things'}</i>` : ''}</span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        document.getElementById('tagPickEmpty').hidden = (ALL || []).length > 0;
    }

    window.smTags = {
        mount(el, tags) {
            if (!el || el._tpMounted) { if (el && tags) this.set(el, tags); return; }
            el._tpMounted = true;
            el._tags = tags || [];
            paintRow(el);
            el.addEventListener('click', async (e) => {
                const off = e.target.closest('[data-tp-off]');
                if (off) {
                    el._tags = (el._tags || []).filter((t) => t.id !== Number(off.dataset.tpOff));
                    paintRow(el);
                    return;
                }
                if (e.target.closest('[data-tp-open]')) {
                    HOST = el;
                    await ensureAll();
                    paintSheet();
                    openSheet('tagPickSheet');
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
                const res = await api(U.of + '&kind=' + encodeURIComponent(kind) + '&refId=' + (refId || 0) + (extra || ''));
                this.set(el, res.data.tags || []);
            } catch (err) { /* an untagged sheet is a fine fallback */ }
        },
        value(el) { return (el?._tags || []).map((t) => t.id); },
        // The board asks for the whole dictionary (filters, chips).
        all: ensureAll,
        invalidate() { ALL = null; },
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

    async function createTag() {
        const inp = document.getElementById('tagPickNew');
        const name = (inp.value || '').trim();
        if (!name) return;
        const btn = document.getElementById('tagPickAdd');
        btn.disabled = true;
        try {
            const res = await api(U.store, { method: 'POST', body: { scheduleId: SCHED, name } });
            const t = res.data.tag;
            await ensureAll();
            if (!ALL.some((x) => x.id === t.id)) ALL.push(t);
            if (HOST && !(HOST._tags || []).some((x) => x.id === t.id)) {
                HOST._tags = [...(HOST._tags || []), { id: t.id, name: t.name }];
                paintRow(HOST);
            }
            inp.value = '';
            paintSheet();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    }
    document.getElementById('tagPickAdd')?.addEventListener('click', createTag);
    document.getElementById('tagPickNew')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); createTag(); }
    });
})();
</script>
@endpush
@endonce
