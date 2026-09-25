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
    /* The day-count chooser: a tag that opens a sheet. The rows that do not
       fit the crop stay on the list, greyed, with the reason. */
    .pbh-dt .dt-row { transition: opacity .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pbh-dt .dt-row.is-off { opacity: .45; cursor: not-allowed; background: var(--color-gray-50); }
    .pbh-dt .dt-row.is-off:hover { border-color: var(--color-gray-200); background: var(--color-gray-50); }
    .pbh-dt .dt-row-why { display: none; font-style: normal; font-size: .72rem; font-weight: 700; color: #92400e; margin-top: .25rem; }
    .pbh-dt .dt-row.is-off .dt-row-why { display: block; }
    html.dark .pbh-dt .dt-row.is-off { background: #151b12; }
    html.dark .pbh-dt .dt-row-why { color: #f0d9a8; }
    .pbh-dt-note { font-size: .74rem; color: var(--color-gray-500); margin-top: .6rem; line-height: 1.45; }
    .crop-tag.pbh-moved { animation: pbhMoved .9s cubic-bezier(.22,1,.36,1); }
    @keyframes pbhMoved { 0% { box-shadow: 0 0 0 0 rgba(107,159,61,0); } 25% { box-shadow: 0 0 0 4px rgba(107,159,61,.35); } 100% { box-shadow: 0 0 0 0 rgba(107,159,61,0); } }
    @media (prefers-reduced-motion: reduce) { .pbh-dt .dt-row { transition: none; } .crop-tag.pbh-moved { animation: none; } }
</style>
{{-- How the days are counted: one sheet, shared by every head form on the page. --}}
<div class="sheet hidden" id="pbDayTypeSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">How the days are counted</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body pbh-dt">
        <div class="dt-rows" id="pbDayTypeList"></div>
        <p class="pbh-dt-note" id="pbDayTypeNote"></p>
    </div>
</div>
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
    /* How the days are counted, narrowed by the crop exactly as the Lots
       form narrows its select (CropCatalog::countersFor): a tree is only
       read by its age, each annual keeps the counts that fit it, and no crop
       leaves the three field counts open. */
    const allowedFor = (crop) => (crop && FLAT[crop] && Array.isArray(FLAT[crop].counters) && FLAT[crop].counters.length) ? FLAT[crop].counters : ['DAT', 'DAS', 'DAP'];
    const whyNot = (key, crop) => {
        const c = crop ? FLAT[crop] : null;
        const name = c ? c.label : '';
        if (key === 'TREE') return c ? `Only for tree crops — ${name} is harvested within the season.` : 'Only for tree crops — choose one first.';
        if (c && c.perennial) return `${name} is a standing tree — it is read by its age.`;
        if (key === 'DAT') return `${name} is not raised in a seedbed and transplanted.`;
        if (key === 'DAS') return `${name} is not sown straight into the field.`;
        if (key === 'DAP') return `${name} is sown, not planted from seedlings or cuttings.`;
        return 'Does not fit this crop.';
    };
    let dayFor = null;   // the head form the day-count sheet is answering
    $id('pbDayTypeList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-day-type]');
        if (!r || !dayFor) return;
        if (r.classList.contains('is-off')) { toast(r.querySelector('.dt-row-why')?.textContent || 'That count does not fit this crop.', 'error'); return; }
        dayFor.pickDay(r.getAttribute('data-day-type'));
        closeSheet('pbDayTypeSheet');
    });
    /**
     * pbHeadForm(pfx) → { fill(p), read(), check() } over the fields
     * `${pfx}Title`, `${pfx}CropBtn/CropIcon/CropNow`, `${pfx}Variety`,
     * `${pfx}DayTypeBtn/DayTypeIcon/DayTypeNow/DayTypeHint`, `${pfx}Desc`.
     */
    window.pbHeadForm = (pfx) => {
        // `chosen`: the farmer (or the saved protocol) has said how the days
        // are counted. Until then a crop lands the count on how that crop is
        // usually grown; after it, only a count that no longer fits moves.
        const state = { crop: null, dayType: 'DAS', chosen: false };
        const paintCrop = () => {
            const c = state.crop ? FLAT[state.crop] : null;
            $id(pfx + 'CropIcon').textContent = c ? c.icon : '🌱';
            const now = $id(pfx + 'CropNow');
            now.textContent = c ? c.label : 'Choose the crop';
            now.classList.toggle('is-none', !c);
        };
        const paintDay = () => {
            const d = DAY_TYPES[state.dayType] || {};
            $id(pfx + 'DayTypeIcon').textContent = d.icon || '🗓️';
            $id(pfx + 'DayTypeNow').textContent = d.label || state.dayType;
            const one = allowedFor(state.crop).length <= 1;
            $id(pfx + 'DayTypeHint').textContent = (d.sub || '') + (one && state.crop ? ' It is the only count that fits this crop.' : '');
        };
        const moved = () => {
            const b = $id(pfx + 'DayTypeBtn');
            b.classList.remove('pbh-moved'); void b.offsetWidth; b.classList.add('pbh-moved');
        };
        // A crop that no longer fits the count moves it to the crop's own count.
        const fitToCrop = () => {
            const allow = allowedFor(state.crop);
            if (!state.chosen || !allow.includes(state.dayType)) {
                const was = state.dayType;
                state.dayType = allow[0];
                if (was !== state.dayType) moved();
            }
            paintDay();
        };
        $id(pfx + 'CropBtn').addEventListener('click', () => {
            onPick = (key) => {
                state.crop = key;
                paintCrop();
                fitToCrop();
            };
            $id('pbCropSearch').value = ''; sift();
            openSheet('pbCropSheet');
            if (!window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbCropSearch').focus(), 280);
        });
        const sheetApi = {
            pickDay(key) { state.dayType = key; state.chosen = true; paintDay(); },
        };
        $id(pfx + 'DayTypeBtn').addEventListener('click', () => {
            dayFor = sheetApi;
            const allow = allowedFor(state.crop);
            $id('pbDayTypeList').innerHTML = Object.entries(DAY_TYPES).map(([k, d]) => {
                const off = !allow.includes(k);
                return `
                <button type="button" class="dt-row${k === state.dayType ? ' is-on' : ''}${off ? ' is-off' : ''}" data-day-type="${esc(k)}"${off ? ' aria-disabled="true"' : ''}>
                    <span class="dt-row-e">${esc(d.icon)}</span>
                    <span class="dt-row-body"><b>${esc(d.label)}</b><i>${esc(d.sub)}</i><i class="dt-row-why">${esc(whyNot(k, state.crop))}</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>`;
            }).join('');
            const c = state.crop ? FLAT[state.crop] : null;
            $id('pbDayTypeNote').textContent = !c
                ? 'No crop chosen yet, so the three field counts are open. Mature trees come with a tree crop.'
                : (allow.length <= 1 ? `${c.label} has only one honest way of being counted.` : `Greyed answers do not fit ${c.label}.`);
            openSheet('pbDayTypeSheet');
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
                // A saved protocol has already answered; a blank form has not.
                state.chosen = !!p.id;
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
        <button type="button" class="crop-tag" id="{{ $pfx }}DayTypeBtn">
            <span class="crop-tag-e" id="{{ $pfx }}DayTypeIcon">🗓️</span>
            <span class="crop-tag-t" id="{{ $pfx }}DayTypeNow">DAS only</span>
            <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
        <p class="pbh-hint" id="{{ $pfx }}DayTypeHint"></p>
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
