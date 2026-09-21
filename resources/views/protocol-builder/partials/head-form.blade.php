{{-- THE HEAD OF A PROTOCOL — title, crop, variety, day count, description.
     One form for the New sheet and the editor's Name sheet; `$pfx` keeps
     the ids apart. The crop sheet, its CSS and the wiring print once. --}}
@php $pfx = $pfx ?? 'pbh'; @endphp
@include('partials.tag-sheet-css')
@once
<style>
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.4rem !important; padding-right: 2.2rem !important; }
    .crop-search-x { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%); width: 1.6rem; height: 1.6rem; border-radius: 999px; color: var(--color-gray-400); }
    .crop-search-x:hover { background: var(--color-gray-100); }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .8rem 0 .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left; padding: .5rem .6rem; border-radius: .7rem; cursor: pointer; }
    .crop-row:hover { background: var(--color-brand-50); }
    .crop-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .crop-row-t { min-width: 0; }
    .crop-row-t b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .crop-row-t small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .crop-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .crop-row:hover { background: #22301a; }
    html.dark .crop-row-t b { color: #e8efe1; }
    .pbh-hint { font-size: .74rem; color: var(--color-gray-500); margin-top: .3rem; line-height: 1.45; }
</style>
{{-- The crop sheet: inside the content so the script can wire it by id. --}}
<div class="sheet hidden" id="pbCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="pbCropSearch" class="form-input" autocomplete="off" placeholder="{{ \App\Support\Region::t('cropSearch') }}">
            <button type="button" class="crop-search-x hidden" id="pbCropSearchX" aria-label="Clear">✕</button>
        </div>
        <div id="pbCropList"></div>
        <p class="crop-none hidden" id="pbCropNone">{{ \App\Support\Region::t('cropNone') }}</p>
    </div>
</div>
<script>
(() => {
    const CROPS = @json($options['crops']);
    const DAY_TYPES = @json($options['dayTypes']);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const $id = (i) => document.getElementById(i);
    const FLAT = {};
    Object.values(CROPS).forEach((list) => list.forEach((c) => { FLAT[c.value] = c; }));
    let onPick = null;
    // The crop list, painted once.
    $id('pbCropList').innerHTML = Object.entries(CROPS).map(([g, list]) => `
        <div class="crop-group" data-crop-group>
            <p class="crop-group-h">${esc(g)}</p>
            ${list.map((c) => `
                <button type="button" class="crop-row" data-crop="${esc(c.value)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                    <span class="crop-row-e">${esc(c.icon)}</span>
                    <span class="crop-row-t"><b>${esc(c.label)}</b><small>${c.perennial ? 'Tree crop — read by its age' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small></span>
                </button>`).join('')}
        </div>`).join('');
    const sift = () => {
        const q = ($id('pbCropSearch').value || '').trim().toLowerCase();
        $id('pbCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#pbCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => { const hit = !q || (r.getAttribute('data-find') || '').includes(q); r.hidden = !hit; if (hit) left++; });
            g.hidden = left === 0;
            shown += left;
        });
        $id('pbCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('pbCropSearch').addEventListener('input', sift);
    $id('pbCropSearchX').addEventListener('click', () => { $id('pbCropSearch').value = ''; sift(); $id('pbCropSearch').focus(); });
    $id('pbCropSheet').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        const key = row.getAttribute('data-crop');
        closeSheet('pbCropSheet');
        if (onPick) onPick(key, FLAT[key] || null);
    });
    window.pbCrop = (key) => FLAT[key] || null;
    /**
     * pbHeadForm(pfx) → { fill(p), read(), check() } over the fields
     * `${pfx}Title`, `${pfx}CropBtn/CropIcon/CropNow`, `${pfx}Variety`,
     * `${pfx}DayTypes` (rows), `${pfx}Desc`.
     */
    window.pbHeadForm = (pfx) => {
        const state = { crop: null, dayType: 'DAS' };
        const paintCrop = () => {
            const c = state.crop ? FLAT[state.crop] : null;
            $id(pfx + 'CropIcon').textContent = c ? c.icon : '🌱';
            const now = $id(pfx + 'CropNow');
            now.textContent = c ? c.label : 'Choose the crop';
            now.classList.toggle('is-none', !c);
        };
        const paintDay = () => {
            document.querySelectorAll('#' + pfx + 'DayTypes [data-day-type]').forEach((r) => r.classList.toggle('is-on', r.getAttribute('data-day-type') === state.dayType));
        };
        $id(pfx + 'CropBtn').addEventListener('click', () => {
            onPick = (key, c) => {
                state.crop = key;
                paintCrop();
                // A crop that is transplanted counts DAS → DAT; the rest are told by the catalogue.
                if (c && c.counter && DAY_TYPES[c.counter]) { state.dayType = c.counter; paintDay(); }
            };
            $id('pbCropSearch').value = ''; sift();
            openSheet('pbCropSheet');
            if (!window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbCropSearch').focus(), 280);
        });
        $id(pfx + 'DayTypes').innerHTML = Object.entries(DAY_TYPES).map(([k, d]) => `
            <button type="button" class="dt-row" data-day-type="${esc(k)}">
                <span class="dt-row-e">${esc(d.icon)}</span>
                <span class="dt-row-body"><b>${esc(d.label)}</b><i>${esc(d.sub)}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        $id(pfx + 'DayTypes').addEventListener('click', (e) => {
            const r = e.target.closest('[data-day-type]');
            if (!r) return;
            state.dayType = r.getAttribute('data-day-type');
            paintDay();
        });
        paintDay();
        return {
            fill(p) {
                $id(pfx + 'Title').value = p.title || '';
                $id(pfx + 'Variety').value = p.variety || '';
                $id(pfx + 'Desc').value = p.description || '';
                window.userTags.set($id(pfx + 'Tags'), p.tags || []);
                state.crop = p.crop || null;
                state.dayType = p.dayType || 'DAS';
                paintCrop(); paintDay();
            },
            read() {
                return {
                    title: $id(pfx + 'Title').value.trim(),
                    crop: state.crop,
                    variety: $id(pfx + 'Variety').value.trim(),
                    dayType: state.dayType,
                    description: $id(pfx + 'Desc').value.trim(),
                    tags: window.userTags.value($id(pfx + 'Tags')),
                };
            },
            check() {
                const r = this.read();
                if (!r.title) { $id(pfx + 'Title').focus(); return 'Give the protocol a name.'; }
                return null;
            },
        };
    };
})();
</script>
@endonce

<div class="space-y-4">
    <div>
        <label class="form-label" for="{{ $pfx }}Title">Name of the protocol</label>
        <input type="text" id="{{ $pfx }}Title" class="form-input" maxlength="190" placeholder="e.g. Rice — my 110-day program">
    </div>
    <div>
        <span class="form-label">Crop</span>
        <button type="button" class="crop-tag" id="{{ $pfx }}CropBtn">
            <span class="crop-tag-e" id="{{ $pfx }}CropIcon">🌱</span>
            <span class="crop-tag-t is-none" id="{{ $pfx }}CropNow">Choose the crop</span>
            <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
    </div>
    <div>
        <label class="form-label" for="{{ $pfx }}Variety">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
        <input type="text" id="{{ $pfx }}Variety" class="form-input" maxlength="120" placeholder="e.g. {{ \App\Support\Region::ph() ? 'NSIC Rc222' : 'Pioneer P1197' }}">
    </div>
    <div>
        <span class="form-label">How the days are counted</span>
        <div class="dt-rows" id="{{ $pfx }}DayTypes"></div>
        <p class="pbh-hint">Every task is pinned to a day of this count. A DAS → DAT protocol counts DAS in the seedbed and DAT from the transplant.</p>
    </div>
    <div>
        <span class="form-label">Tags <span class="text-gray-400 font-normal">(optional)</span></span>
        <div class="ut-mount" id="{{ $pfx }}Tags"></div>
    </div>
    <div>
        <label class="form-label" for="{{ $pfx }}Desc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="{{ $pfx }}Desc" class="form-textarea" rows="3" maxlength="2000" placeholder="What this protocol is for, where it was proven, what it assumes…"></textarea>
    </div>
</div>
