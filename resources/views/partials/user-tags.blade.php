{{-- A MEMBER'S OWN TAGS — the picker for the things that are theirs and not a
     season's (analyses, protocols, contacts). A mount is a div.ut-mount; the
     JS paints chips and an Add button into it, and the sheet below offers the
     words already used across the tools plus a way to add one.

       window.userTags.mount(el, tags)   paint (safe to repeat)
       window.userTags.set(el, tags)     replace the words
       window.userTags.value(el)         -> ['word', ...] for the save payload
       window.userTags.chips(tags)       -> HTML of read-only chips for a row

     @once, so a page that collects it twice pays once. --}}
@once
<style>
    .ut-mount { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
    .ut-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .3rem .22rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid #cfe3bd; }
    .ut-chip button { width: 1.15rem; height: 1.15rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: var(--color-brand-700); }
    .ut-chip button:hover { background: rgb(0 0 0 / .08); }
    .ut-chip button svg { width: .7rem; height: .7rem; }
    .ut-add { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); border: 1.5px dashed var(--color-gray-300); background: transparent; }
    .ut-add:hover { color: var(--color-brand-700); border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .ut-add svg { width: .8rem; height: .8rem; }
    .ut-tags { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .3rem; }
    .ut-tag { display: inline-flex; align-items: center; padding: .12rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 800; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid #cfe3bd; }
    html.dark .ut-chip, html.dark .ut-tag { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .ut-chip button { color: #cfe6b8; }
    html.dark .ut-add { border-color: #3f5a2a; color: #a5b89a; }
    html.dark .ut-add:hover { background: #22301a; color: #cfe6b8; }
</style>
<div class="sheet hidden" id="utSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tags</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="flex gap-2 mb-3">
            <input type="text" class="form-input grow" id="utNew" maxlength="30" placeholder="Tag name — e.g. wet season" autocomplete="off" enterkeyhint="done">
            <button type="button" class="btn btn-primary shrink-0" id="utAdd">Add</button>
        </div>
        <div class="dt-rows" id="utList"></div>
        <p class="text-xs text-gray-400 mt-2" id="utEmpty" hidden>No tags yet — type one above.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Done</button>
    </div>
</div>
<script>
(() => {
    if (window.userTags) return;
    const URL_MINE = @json(route('tags.mine'));
    const SUGGEST = ['Rice', 'Corn', 'Vegetables', 'Wet season', 'Dry season', 'Trial', 'Proven', 'To review', 'Favorite'];
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const $id = (i) => document.getElementById(i);
    let POOL = null;      // [{name, count}] from the member's own things
    let OPEN = null;      // the mount the sheet is editing
    const same = (a, b) => String(a).toLowerCase() === String(b).toLowerCase();
    async function pool() {
        try { const r = await window.api(URL_MINE, { method: 'GET' }); POOL = r.data.tags || []; }
        catch (_) { POOL = POOL || []; }
        return POOL;
    }
    function paintMount(el) {
        const tags = el._tags || [];
        el.innerHTML = tags.map((t) => `<span class="ut-chip" data-ut="${esc(t)}"><span>${esc(t)}</span><button type="button" aria-label="Remove ${esc(t)}"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('')
            + `<button type="button" class="ut-add"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>${tags.length ? 'Tags' : 'Add tags'}</button>`;
    }
    function paintList() {
        if (!OPEN) return;
        const mine = OPEN._tags || [];
        const names = [];
        const push = (n) => { if (!names.some((x) => same(x, n))) names.push(n); };
        mine.forEach(push); (POOL || []).forEach((t) => push(t.name)); SUGGEST.forEach(push);
        const count = (n) => { const p = (POOL || []).find((t) => same(t.name, n)); return p ? p.count : 0; };
        $id('utList').innerHTML = names.map((n) => `
            <button type="button" class="dt-row${mine.some((x) => same(x, n)) ? ' is-on' : ''}" data-ut-pick="${esc(n)}">
                <span class="dt-row-e">🏷️</span>
                <span class="dt-row-body"><b>${esc(n)}</b>${count(n) ? `<i>on ${count(n)} ${count(n) === 1 ? 'thing' : 'things'}</i>` : ''}</span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        $id('utEmpty').hidden = names.length > 0;
    }
    function toggle(name) {
        if (!OPEN) return;
        const has = (OPEN._tags || []).some((x) => same(x, name));
        OPEN._tags = has ? OPEN._tags.filter((x) => !same(x, name)) : [...(OPEN._tags || []), name].slice(0, 10);
        if (!has && OPEN._tags.length === 10 && !OPEN._tags.some((x) => same(x, name))) window.toast?.('Up to ten tags on one thing.', 'error');
        paintList(); paintMount(OPEN);
    }
    document.addEventListener('click', (e) => {
        const chipX = e.target.closest('.ut-mount .ut-chip button');
        if (chipX) { const el = chipX.closest('.ut-mount'); const t = chipX.closest('.ut-chip').getAttribute('data-ut'); el._tags = (el._tags || []).filter((x) => x !== t); paintMount(el); e.preventDefault(); return; }
        const add = e.target.closest('.ut-mount .ut-add');
        if (add) { e.preventDefault(); OPEN = add.closest('.ut-mount'); $id('utNew').value = ''; paintList(); window.openSheet('utSheet'); pool().then(paintList); return; }
        const row = e.target.closest('#utList [data-ut-pick]');
        if (row) { toggle(row.getAttribute('data-ut-pick')); return; }
        if (e.target.closest('#utAdd')) {
            const v = $id('utNew').value.trim().replace(/\s+/g, ' ').slice(0, 30);
            if (!v) { $id('utNew').focus(); return; }
            if (!(OPEN._tags || []).some((x) => same(x, v))) toggle(v);
            $id('utNew').value = '';
        }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target && e.target.id === 'utNew') { e.preventDefault(); $id('utAdd').click(); } });
    window.userTags = {
        mount(el, tags) { if (!el) return; el._tags = Array.isArray(tags) ? tags.slice(0, 10) : (el._tags || []); el.classList.add('ut-mount'); paintMount(el); },
        set(el, tags) { this.mount(el, tags || []); },
        value(el) { return (el && el._tags) ? el._tags.slice() : []; },
        chips(tags) { const t = Array.isArray(tags) ? tags : []; return t.length ? `<span class="ut-tags">${t.map((x) => `<span class="ut-tag">${esc(x)}</span>`).join('')}</span>` : ''; },
        invalidate() { POOL = null; },
    };
})();
</script>
@endonce
