{{--
    Activities module page JS. Vanilla JS against the global helpers in
    resources/js/app.js (api, toast, openSheet/closeSheet, confirmAction,
    chipValues, escapeHtml, fmtPeso, fmtNumber).

    Sections:
      1. Constants, lookups, URL map, tiny helpers
      2. Day-0 / DAS machinery (recomputeLotDayZero, computeDasLabel)
      3. Undo stack (10-step LIFO + Ctrl+Z)
      4. Card renderer + timeline rebuild (dual-render twin of the blade partials)
      5. Filters (search / type chips / hide-lots chips / show-hidden toggle)
      6. Add–edit activity sheet (lots, workers, DAS lens, Day-0, Quill, image, items)
      7. Per-card actions (edit, duplicate, draft, delete, hide, mobile menu, move-to-date)
      8. Date-group actions (add-to-date, change date, delete group)
      9. Date notes
     10. Progress markers
     11. Drag & drop
     12. Drafts
     13. Labor summary (render, filters, copy, print)
     14. Versions
--}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /* ================================================================
     * 1. CONSTANTS + LOOKUPS
     * ================================================================ */

    const SCHEDULE_ID = @json($schedule->id);
    const DAY_TYPE_DEFAULT = @json($schedule->dayType ?: 'DAS');
    const STORAGE_BASE = @json(asset('storage'));

    const ACTIVITY_TYPE_LABELS = @json($activityTypes);
    const LOT_NAMES = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->lotName]));
    const LOT_VARIETIES = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->variety]));
    const WORKER_NAMES = @json($schedule->workers->mapWithKeys(fn ($w) => [$w->id => $w->workerName]));
    const LOT_MANUAL_DAY_ZERO = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null]));
    const MATERIALS = @json($schedule->materials->map(fn ($m) => ['id' => $m->id, 'name' => $m->materialName, 'unit' => $m->unitOfMeasure])->values());
    const SERVICES = @json($schedule->services->map(fn ($s) => ['id' => $s->id, 'name' => $s->serviceName])->values());

    const U = {
        store:            ()  => `{{ route('sm.activities.store') }}?scheduleId=${SCHEDULE_ID}`,
        show:             (id) => `{{ route('sm.activities.show') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        update:           (id) => `{{ route('sm.activities.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        destroy:          (id) => `{{ route('sm.activities.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        imageUpload:      ()  => `{{ route('sm.activities.image-upload') }}?scheduleId=${SCHEDULE_ID}`,
        toggleHidden:     (id) => `{{ route('sm.activities.toggle-hidden') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        duplicate:        (id) => `{{ route('sm.activities.duplicate') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        reorder:          ()  => `{{ route('sm.activities.reorder') }}?scheduleId=${SCHEDULE_ID}`,
        restore:          (id) => `{{ route('sm.activities.restore') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        toDraft:          (id) => `{{ route('sm.activities.to-draft') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        fromDraft:        (id) => `{{ route('sm.activities.from-draft') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        drafts:           ()  => `{{ route('sm.activities.drafts') }}?scheduleId=${SCHEDULE_ID}`,
        labor:            ()  => `{{ route('sm.activities.labor') }}?scheduleId=${SCHEDULE_ID}`,
        dateNoteSave:     ()  => `{{ route('sm.activities.date-note.save') }}?scheduleId=${SCHEDULE_ID}`,
        dateNoteDelete:   ()  => `{{ route('sm.activities.date-note.delete') }}?scheduleId=${SCHEDULE_ID}`,
        markerSave:       ()  => `{{ route('sm.markers.save') }}?scheduleId=${SCHEDULE_ID}`,
        markerDelete:     (id) => `{{ route('sm.markers.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionStore:     ()  => `{{ route('sm.activity-versions.store') }}?scheduleId=${SCHEDULE_ID}`,
        versionUpdate:    (id) => `{{ route('sm.activity-versions.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionDelete:    (id) => `{{ route('sm.activity-versions.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionSetActive: (id) => `{{ route('sm.activity-versions.set-active') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
    };

    const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const DAY_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const MONTH_LONG = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DAY_LONG = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    const $id = (i) => document.getElementById(i);
    const $qs = (sel, root) => (root || document).querySelector(sel);
    const $qsa = (sel, root) => Array.from((root || document).querySelectorAll(sel));
    const esc = window.escapeHtml;

    const dayType = () => ($qs('.day-type-label')?.textContent || DAY_TYPE_DEFAULT).trim() || DAY_TYPE_DEFAULT;

    function parseLocalDate(s) {
        if (!s) return null;
        const [y, m, d] = String(s).slice(0, 10).split('-').map((n) => parseInt(n, 10));
        if (!y || !m || !d) return null;
        return new Date(y, m - 1, d);
    }
    function isoFromDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }
    function isoAddDays(iso, days) {
        const d = parseLocalDate(iso);
        if (!d) return '';
        d.setDate(d.getDate() + days);
        return isoFromDate(d);
    }
    function isoDaysBetween(a, b) {
        const da = parseLocalDate(a);
        const db = parseLocalDate(b);
        if (!da || !db) return 0;
        return Math.round((db - da) / 86400000);
    }
    function prettyDate(iso) {   // "Mar 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${MONTH_SHORT[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function prettyDateFull(iso) {   // "Thu, Mar 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${DAY_SHORT[d.getDay()]}, ${MONTH_SHORT[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function prettyDateLong(iso) {   // "Thursday, March 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${DAY_LONG[d.getDay()]}, ${MONTH_LONG[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function timeRequiredLabel(v) {
        if (v === 'whole') return 'Whole day';
        if (v === 'n/a') return 'N/A';
        return 'Half day';
    }
    function timeRequiredShortLabel(v) {
        if (v === 'whole') return 'Whole';
        if (v === 'n/a') return 'N/A';
        return 'Half';
    }
    function trimQty(q) {
        const n = Number(q);
        return isFinite(n) ? String(n) : String(q || '1');
    }
    function boolFlag(v) {
        return (v === true || v === 1 || v === '1') ? 1 : 0;
    }
    async function copyToClipboard(txt) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(txt);
        }
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }

    // ---- Shared inline SVG snippets (identical to the blade partials) ----
    const SVG = {
        moon: '<svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
        plus: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>',
        note: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        bookmark: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>',
        bookmarkSolid: '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>',
        calendarEdit: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        trash: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        edit: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        eye: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
        duplicate: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>',
        archive: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
        kebab: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>',
        star: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
        clock: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };

    /* ================================================================
     * 2. DAY-0 / DAS MACHINERY
     * Effective anchor per lot = manual lot dayZeroDate overridden by the
     * EARLIEST isDayZero activity covering that lot.
     * ================================================================ */

    let LOT_DAY_ZERO_DATES = Object.assign({}, LOT_MANUAL_DAY_ZERO);
    let LOT_DAY_ZERO_SOURCE = {};

    function computeDasLabel(lotId, targetDate) {
        if (!targetDate) return '';
        const anchor = LOT_DAY_ZERO_DATES[lotId];
        if (!anchor) return '';
        const a = parseLocalDate(anchor);
        const b = parseLocalDate(targetDate);
        if (!a || !b) return '';
        const delta = Math.round((b - a) / 86400000);
        const sign = delta > 0 ? '+' : '';
        return ' · ' + dayType() + sign + delta;
    }

    function refreshActivityCardDasLabels() {
        $qsa('#activitiesList .activity-card[data-id]').forEach((card) => {
            const targetDate = (card.getAttribute('data-target-date') || '').trim();
            $qsa('.activity-card-lots .item-tag[data-lot-id]', card).forEach((tag) => {
                const lotId = parseInt(tag.getAttribute('data-lot-id'), 10);
                const lotName = tag.getAttribute('data-lot-name') || '';
                const variety = tag.getAttribute('data-lot-variety') || '';
                tag.textContent = lotName + (variety ? ' · ' + variety : '') + computeDasLabel(lotId, targetDate);
            });
        });
    }

    function recomputeLotDayZero() {
        const map = Object.assign({}, LOT_MANUAL_DAY_ZERO || {});
        const source = {};
        Object.keys(map).forEach((lotId) => {
            if (map[lotId]) source[lotId] = 'manual';
        });
        $qsa('#activitiesList .activity-card[data-is-day-zero="1"]').forEach((card) => {
            const activityId = parseInt(card.getAttribute('data-id'), 10);
            const targetDate = (card.getAttribute('data-target-date') || '').trim();
            if (!targetDate) return;
            $qsa('.activity-card-lots .item-tag[data-lot-id]', card).forEach((tag) => {
                const lotId = parseInt(tag.getAttribute('data-lot-id'), 10);
                if (!lotId) return;
                const existing = map[lotId];
                if (!existing || targetDate < existing) {
                    map[lotId] = targetDate;
                    source[lotId] = activityId;
                }
            });
        });
        LOT_DAY_ZERO_DATES = map;
        LOT_DAY_ZERO_SOURCE = source;
        refreshActivityCardDasLabels();
    }

    /* ================================================================
     * 3. UNDO STACK — 10-step LIFO + Ctrl+Z
     * ================================================================ */

    const UNDO_STACK = [];
    const UNDO_MAX = 10;

    function pushUndo(label, undoFn) {
        UNDO_STACK.push({ label, undoFn });
        if (UNDO_STACK.length > UNDO_MAX) UNDO_STACK.shift();
        refreshUndoBtn();
    }

    function refreshUndoBtn() {
        const n = UNDO_STACK.length;
        const btn = $id('activityUndoBtn');
        const count = $id('activityUndoCount');
        if (!btn) return;
        if (n === 0) {
            btn.disabled = true;
            btn.title = 'Nothing to undo';
            count.classList.add('hidden');
            count.classList.remove('inline-flex');
        } else {
            btn.disabled = false;
            btn.title = 'Undo: ' + UNDO_STACK[n - 1].label + ' (' + n + ' available, Ctrl+Z)';
            count.textContent = n;
            count.classList.remove('hidden');
            count.classList.add('inline-flex');
        }
    }

    async function performUndo() {
        const action = UNDO_STACK.pop();
        refreshUndoBtn();
        if (!action) {
            toast('Nothing to undo', 'info');
            return;
        }
        try {
            await action.undoFn();
            toast('Undone: ' + action.label);
        } catch (err) {
            toast('Undo failed: ' + (err && err.message ? err.message : 'unknown error'), 'error');
        }
    }

    $id('activityUndoBtn')?.addEventListener('click', () => {
        if (!$id('activityUndoBtn').disabled) performUndo();
    });

    document.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey) || e.shiftKey) return;
        if (e.key !== 'z' && e.key !== 'Z') return;
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        e.preventDefault();
        performUndo();
    });

    /* ================================================================
     * 4. CARD RENDERER + TIMELINE REBUILD
     * renderActivityCard(a) emits markup IDENTICAL to
     * sm/partials/activity-card.blade.php; reorderAndRenumberActivities()
     * rebuilds the whole timeline (groups, colors, rest days, notes,
     * markers, all-hidden substitutes) from the DOM cards.
     * ================================================================ */

    function renderActivityCard(a) {
        const priority = a.priority || 'medium';
        const priorityCap = priority.charAt(0).toUpperCase() + priority.slice(1);
        const targetDateStr = (a.targetDate || '').slice(0, 10);
        const targetEndDateStr = (a.targetEndDate || '').slice(0, 10);
        const startObj = parseLocalDate(targetDateStr);
        const endObj = parseLocalDate(targetEndDateStr);
        const isRange = !!(startObj && endObj && endObj > startObj);
        const rangeDays = isRange ? Math.round((endObj - startObj) / 86400000) + 1 : 0;
        const typeLabel = a.activityType && ACTIVITY_TYPE_LABELS[a.activityType] ? ACTIVITY_TYPE_LABELS[a.activityType] : '';
        const dt = dayType();
        const isDayZeroFlag = boolFlag(a.isDayZero);
        const isHiddenFlag = boolFlag(a.isHidden);
        const seqOrder = (a.sequenceOrder !== undefined && a.sequenceOrder !== null) ? parseInt(a.sequenceOrder, 10) || 0 : 0;

        const lotIds = (a.lotIds || (a.lots || []).map((l) => l.id ?? l)).map(Number);
        const workerIds = (a.workerIds || (a.workers || []).map((w) => w.id ?? w)).map(Number);
        const lotSig = lotIds.slice().sort((x, y) => x - y).join(',');

        // data-search: lowercased title + type + lots(name variety) + workers + items
        const searchBits = [String(a.activityTitle || '').toLowerCase(), typeLabel.toLowerCase()];
        lotIds.forEach((id) => searchBits.push(((LOT_NAMES[id] || '') + ' ' + (LOT_VARIETIES[id] || '')).toLowerCase()));
        workerIds.forEach((id) => searchBits.push((WORKER_NAMES[id] || '').toLowerCase()));
        (a.items || []).forEach((it) => {
            searchBits.push(String(it.itemType === 'material' ? (it.material?.materialName || '') : (it.service?.serviceName || '')).toLowerCase());
        });
        const searchText = searchBits.filter(Boolean).join(' ').trim();

        // Lots row
        let lotsRow;
        if (lotIds.length) {
            lotsRow = lotIds.map((id) => {
                const name = LOT_NAMES[id] || ('Lot #' + id);
                const variety = LOT_VARIETIES[id] || '';
                const text = name + (variety ? ' · ' + variety : '') + computeDasLabel(id, targetDateStr);
                return `<span class="item-tag lot-tag" data-lot-id="${id}" data-lot-name="${esc(name)}" data-lot-variety="${esc(variety)}">${esc(text)}</span>`;
            }).join('');
        } else {
            lotsRow = '<span class="item-tag activity-na-tag" title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</span>';
        }

        // Badges
        const typeBadge = typeLabel ? `<span class="badge badge-green activity-type-badge">${esc(typeLabel)}</span>` : '';
        const dayZeroBadge = isDayZeroFlag
            ? `<span class="badge day-zero-badge" title="This activity's start date becomes ${esc(dt)} 0 for every lot it covers">${SVG.star} ${esc(dt)} 0</span>`
            : '';
        const rangeBadge = isRange
            ? `<span class="badge badge-gray range-badge" title="Multi-day range">&rarr; ${esc(MONTH_SHORT[endObj.getMonth()] + ' ' + endObj.getDate())} (${rangeDays}d)</span>`
            : '';
        const hiddenTag = `<span class="badge badge-gray hide-activity-tag"${isHiddenFlag ? '' : ' style="display:none;"'}>Hidden</span>`;

        // Meta strip: workers then materials/services (time chip is added inline below).
        const workerTags = workerIds
            .map((id) => `<span class="item-tag worker-tag">${esc(WORKER_NAMES[id] || ('Worker #' + id))}</span>`)
            .join('');

        let itemTags = '';
        (a.items || []).forEach((it) => {
            if (it.itemType === 'material') {
                const name = it.material?.materialName || ('Material #' + (it.materialId ?? ''));
                const unit = it.unitOfMeasure || it.material?.unitOfMeasure || '';
                itemTags += `<span class="item-tag material-tag">${esc(name)} &times;${esc(trimQty(it.quantity))} ${esc(unit)}</span>`;
            } else {
                const name = it.service?.serviceName || ('Service #' + (it.serviceId ?? ''));
                itemTags += `<span class="item-tag service-tag">${esc(name)}</span>`;
            }
        });

        const descHtml = a.description || '';
        const imageUrl = a.imageUrl || (a.imagePath ? STORAGE_BASE + '/' + String(a.imagePath).replace(/^\/+/, '') : '');
        const nameAttr = esc(a.activityTitle || '');

        return `<div class="activity-card prio-${esc(priority)}${isHiddenFlag ? ' is-hidden' : ''}" draggable="true"
     data-id="${a.id}"
     data-target-date="${esc(targetDateStr)}"
     data-target-end-date="${esc(targetEndDateStr)}"
     data-lot-signature="${esc(lotSig)}"
     data-sequence-order="${seqOrder}"
     data-is-day-zero="${isDayZeroFlag}"
     data-activity-type="${esc(a.activityType || '')}"
     data-is-hidden="${isHiddenFlag}"
     data-search="${esc(searchText)}">
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 grow">
            <h3 class="activity-card-title">${esc(a.activityTitle || '')}</h3>
            <div class="activity-card-badges">
                <span class="pill pill-${esc(priority)}">${esc(priorityCap)}</span>
                ${typeBadge}
                ${dayZeroBadge}
                ${rangeBadge}
                ${hiddenTag}
            </div>
            <div class="activity-card-lots">${lotsRow}</div>
        </div>
        <div class="flex items-center shrink-0">
            <div class="hidden md:flex items-center gap-0.5">
                <button type="button" class="icon-btn hide-activity-toggle" data-id="${a.id}" title="Toggle visibility in presentations and exports" aria-pressed="${isHiddenFlag ? 'true' : 'false'}">${SVG.eye}</button>
                <button type="button" class="icon-btn edit-activity-btn" data-id="${a.id}" title="Edit">${SVG.edit}</button>
                <button type="button" class="icon-btn duplicate-activity-btn" data-id="${a.id}" data-name="${nameAttr}" title="Duplicate">${SVG.duplicate}</button>
                <button type="button" class="icon-btn to-draft-activity-btn" data-id="${a.id}" data-name="${nameAttr}" title="Move to drafts (hide without deleting)">${SVG.archive}</button>
                <button type="button" class="icon-btn icon-btn-danger delete-activity-btn" data-id="${a.id}" data-name="${nameAttr}" title="Delete">${SVG.trash}</button>
            </div>
            <button type="button" class="icon-btn card-menu-btn md:hidden" data-id="${a.id}" data-name="${nameAttr}" title="Actions">${SVG.kebab}</button>
        </div>
    </div>
    ${descHtml ? `<div class="activity-description-content text-sm text-gray-700 mt-2">${descHtml}</div>` : ''}
    ${imageUrl ? `<div class="activity-card-image mt-2"><img src="${esc(imageUrl)}" alt="Reference image" loading="lazy"></div>` : ''}
    <div class="activity-meta">
        <span class="meta-time">${SVG.clock} ${esc(timeRequiredLabel(a.timeRequired))}</span>
        ${workerTags}
        ${itemTags}
    </div>
</div>`;
    }

    function buildRestDayHtml(dateKey, substitute) {
        return `<div class="rest-day-marker${substitute ? ' rest-day-substitute' : ''}" data-date="${esc(dateKey)}">
            ${SVG.moon}
            <div class="grow min-w-0">
                <span class="rest-day-date">${esc(prettyDateLong(dateKey))}</span>
                <span class="rest-day-tag">No activities scheduled</span>
            </div>
            <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0" data-date="${esc(dateKey)}">+ Add</button>
        </div>`;
    }

    function buildMarkerHtml(dateKey, info) {
        const noteRaw = info.note || '';
        return `<div class="progress-marker" data-marker-id="${esc(String(info.id || ''))}" data-date="${esc(dateKey)}">
            <div class="progress-marker-line">
                <span class="progress-marker-bookmark">${SVG.bookmarkSolid} Resume here — ${esc(prettyDate(dateKey))}</span>
                <span class="flex items-center gap-0.5">
                    <button type="button" class="icon-btn progress-marker-edit-btn" data-date="${esc(dateKey)}" title="Edit marker note">${SVG.edit}</button>
                    <button type="button" class="icon-btn icon-btn-danger progress-marker-delete-btn" data-marker-id="${esc(String(info.id || ''))}" data-date="${esc(dateKey)}" title="Remove marker">${SVG.trash}</button>
                </span>
            </div>
            ${noteRaw ? `<div class="progress-marker-note">${esc(noteRaw)}</div>` : ''}
        </div>`;
    }

    const EMPTY_STATE_HTML = `<div id="activitiesEmpty" class="card card-body text-center text-gray-500 py-10">
        <p class="font-bold text-gray-800 mb-1">No activities defined yet.</p>
        <p class="text-sm">Tap <strong>Add Activity</strong> to define your first step.</p>
    </div>`;

    function buildDateGroupShell(dateKey, colorIdx, cards, noteContent, hasMarker, allHidden) {
        const isNoDate = dateKey === '__no-date__';
        const dateObj = isNoDate ? null : parseLocalDate(dateKey);
        const count = cards.length;

        // Latest end-date across the group's cards → range badge.
        let latestEndObj = null;
        if (dateObj) {
            cards.forEach((el) => {
                const endStr = (el.getAttribute('data-target-end-date') || '').trim();
                if (!endStr) return;
                const end = parseLocalDate(endStr);
                if (end && end > dateObj && (!latestEndObj || end > latestEndObj)) latestEndObj = end;
            });
        }
        let rangeBadge = '';
        if (latestEndObj) {
            const spanDays = Math.round((latestEndObj - dateObj) / 86400000) + 1;
            const showYear = latestEndObj.getFullYear() !== dateObj.getFullYear();
            const endLabel = `${MONTH_SHORT[latestEndObj.getMonth()]} ${latestEndObj.getDate()}${showYear ? ', ' + latestEndObj.getFullYear() : ''}`;
            rangeBadge = `<span class="date-header-range" title="At least one activity extends through ${esc(prettyDate(isoFromDate(latestEndObj)))}">&rarr; ${esc(endLabel)} (${spanDays}d)</span>`;
        }

        const headerDate = dateObj
            ? `<span class="date-header-day">${DAY_SHORT[dateObj.getDay()]}</span><span class="date-header-date">${esc(prettyDate(dateKey))}</span>${rangeBadge}`
            : '<span class="date-header-date">No date</span>';

        const hasNote = !isNoDate && (noteContent || '') !== '';
        const buttons = isNoDate ? '' : `
            <button type="button" class="date-header-btn group-add-activity-btn" data-date="${esc(dateKey)}" title="Add a new activity to this date">${SVG.plus}</button>
            <span class="hidden md:flex items-center gap-0.5">
                <button type="button" class="date-header-btn date-note-btn${hasNote ? ' has-note' : ''}" data-date="${esc(dateKey)}" title="${hasNote ? 'Edit the note for this date' : 'Add a note for this date'}">${SVG.note}</button>
                <button type="button" class="date-header-btn date-marker-btn${hasMarker ? ' has-marker' : ''}" data-date="${esc(dateKey)}" title="${hasMarker ? 'Edit the resume-here marker' : 'Drop a resume-here marker after this date'}">${SVG.bookmark}</button>
                <button type="button" class="date-header-btn change-group-date-btn" data-date="${esc(dateKey)}" title="Change date for all activities in this group">${SVG.calendarEdit}</button>
                <button type="button" class="date-header-btn date-header-delete-btn delete-group-date-btn" data-date="${esc(dateKey)}" title="Delete every activity in this group">${SVG.trash}</button>
            </span>
            <button type="button" class="date-header-btn day-menu-btn md:hidden" data-date="${esc(dateKey)}" title="More actions for this day">${SVG.kebab}</button>`;

        const noteBlock = isNoDate ? ''
            : `<div class="date-note-block" data-date="${esc(dateKey)}"${hasNote ? '' : ' style="display:none;"'}>${esc(noteContent || '')}</div>`;

        const wrap = document.createElement('div');
        wrap.innerHTML = `<div class="date-group date-color-${colorIdx}${allHidden ? ' all-hidden' : ''}" data-date="${esc(dateKey)}">
            <div class="date-header"${dateObj ? ' draggable="true" title="Drag this header to move the whole day to another date"' : ''}>
                ${headerDate}
                <span class="date-header-count">${count} ${count === 1 ? 'activity' : 'activities'}</span>
                ${buttons}
            </div>
            ${noteBlock}
            <div class="date-activities" data-date="${esc(dateKey)}"></div>
        </div>`;
        return wrap.firstElementChild;
    }

    function reorderAndRenumberActivitiesCore() {
        const list = $id('activitiesList');
        if (!list) return;
        const cards = $qsa('.activity-card[data-id]', list);

        if (cards.length === 0) {
            // Preserve marker rows even when the board is empty (orphans).
            const markerSnaps = snapshotMarkers(list);
            list.innerHTML = EMPTY_STATE_HTML;
            Object.keys(markerSnaps).forEach((k) => list.insertAdjacentHTML('beforeend', buildMarkerHtml(k, markerSnaps[k])));
            return;
        }

        // Group by date (sentinel key sorts last).
        const groups = {};
        cards.forEach((el) => {
            const key = (el.getAttribute('data-target-date') || '').trim() || '__no-date__';
            (groups[key] = groups[key] || []).push(el);
        });

        // Within a date: manual sequenceOrder → lot signature → id.
        Object.values(groups).forEach((arr) => arr.sort((a, b) => {
            const seqA = parseInt(a.getAttribute('data-sequence-order'), 10) || 0;
            const seqB = parseInt(b.getAttribute('data-sequence-order'), 10) || 0;
            if (seqA !== seqB) return seqA - seqB;
            const sa = a.getAttribute('data-lot-signature') || '';
            const sb = b.getAttribute('data-lot-signature') || '';
            if (sa !== sb) return sa.localeCompare(sb);
            return (parseInt(a.getAttribute('data-id'), 10) || 0) - (parseInt(b.getAttribute('data-id'), 10) || 0);
        }));

        // Covered-day set + overall span for rest-day interleaving.
        const covered = new Set();
        let firstDate = null;
        let lastDate = null;
        cards.forEach((el) => {
            const startStr = (el.getAttribute('data-target-date') || '').trim();
            if (!startStr) return;
            const endStr = (el.getAttribute('data-target-end-date') || '').trim() || startStr;
            const start = parseLocalDate(startStr);
            const end = parseLocalDate(endStr);
            if (!start || !end) return;
            const cur = new Date(start.getTime());
            while (cur <= end) {
                covered.add(isoFromDate(cur));
                cur.setDate(cur.getDate() + 1);
            }
            if (!firstDate || start < firstDate) firstDate = new Date(start.getTime());
            if (!lastDate || end > lastDate) lastDate = new Date(end.getTime());
        });

        const timeline = [];
        let colorCursor = 0;
        if (firstDate && lastDate) {
            const cur = new Date(firstDate.getTime());
            while (cur <= lastDate) {
                const key = isoFromDate(cur);
                if (groups[key]) {
                    timeline.push({ type: 'group', key, color: colorCursor });
                    colorCursor = (colorCursor + 1) % 8;
                } else if (!covered.has(key)) {
                    timeline.push({ type: 'rest', key });
                }
                cur.setDate(cur.getDate() + 1);
            }
        }
        if (groups['__no-date__']) timeline.push({ type: 'group', key: '__no-date__', color: 0 });

        // Snapshot notes + markers BEFORE the wipe.
        const notesByDate = {};
        $qsa('.date-note-block[data-date]', list).forEach((el) => {
            const key = (el.getAttribute('data-date') || '').trim();
            const content = (el.textContent || '').trim();
            if (key && el.style.display !== 'none' && content !== '') notesByDate[key] = content;
        });
        const markersByDate = snapshotMarkers(list);

        // Wipe + rebuild.
        list.innerHTML = '';
        timeline.forEach((item) => {
            if (item.type === 'rest') {
                list.insertAdjacentHTML('beforeend', buildRestDayHtml(item.key, false));
                return;
            }
            const key = item.key;
            const groupCards = groups[key];
            const allHidden = key !== '__no-date__'
                && groupCards.length > 0
                && groupCards.every((el) => el.classList.contains('is-hidden'));
            if (allHidden) {
                list.insertAdjacentHTML('beforeend', buildRestDayHtml(key, true));
            }
            const markerInfo = key !== '__no-date__' ? (markersByDate[key] || null) : null;
            const groupEl = buildDateGroupShell(key, item.color, groupCards, notesByDate[key] || '', !!markerInfo, allHidden);
            const holder = $qs('.date-activities', groupEl);
            groupCards.forEach((el) => holder.appendChild(el));
            list.appendChild(groupEl);
            if (markerInfo) {
                list.insertAdjacentHTML('beforeend', buildMarkerHtml(key, markerInfo));
                delete markersByDate[key];
            }
        });

        // Orphan markers (no matching group) at the bottom.
        Object.keys(markersByDate).forEach((k) => list.insertAdjacentHTML('beforeend', buildMarkerHtml(k, markersByDate[k])));
    }

    function snapshotMarkers(list) {
        const map = {};
        $qsa('.progress-marker[data-date]', list).forEach((el) => {
            const key = (el.getAttribute('data-date') || '').trim();
            if (!key) return;
            map[key] = {
                id: el.getAttribute('data-marker-id') || '',
                note: ($qs('.progress-marker-note', el)?.textContent || '').trim(),
            };
        });
        return map;
    }

    // Wrapped so the active filters re-apply after every rebuild.
    function reorderAndRenumberActivities() {
        reorderAndRenumberActivitiesCore();
        refreshHiddenActivityCount();
        if (hasActiveFilters()) applyActivityFilter();
    }

    function _renderCardOrReplace(activityData) {
        const list = $id('activitiesList');
        const html = renderActivityCard(activityData);
        const existing = $qs(`.activity-card[data-id="${activityData.id}"]`, list);
        if (existing) {
            existing.outerHTML = html;
        } else {
            $id('activitiesEmpty')?.remove();
            list.insertAdjacentHTML('beforeend', html);
        }
        reorderAndRenumberActivities();
        recomputeLotDayZero();
    }

    function _removeCardById(id) {
        const el = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        const finish = () => {
            el?.remove();
            reorderAndRenumberActivities();
            recomputeLotDayZero();
        };
        // Animate the card out so the change is visible rather than instant.
        if (el && window.animateOut) window.animateOut(el, finish);
        else finish();
    }

    function captureBoardSnapshot() {
        return $qsa('#activitiesList .activity-card[data-id]').map((el) => ({
            id: parseInt(el.getAttribute('data-id'), 10),
            targetDate: (el.getAttribute('data-target-date') || '').trim(),
            targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
            sequenceOrder: parseInt(el.getAttribute('data-sequence-order'), 10) || 0,
        }));
    }

    async function restoreBoardSnapshot(snapshot) {
        const r = await api(U.reorder(), { method: 'POST', body: { items: snapshot } });
        if (!r || !r.success) throw new Error((r && r.message) || 'reorder failed');
        snapshot.forEach((it) => {
            const el = $qs(`#activitiesList .activity-card[data-id="${it.id}"]`);
            if (!el) return;
            el.setAttribute('data-target-date', it.targetDate || '');
            el.setAttribute('data-target-end-date', it.targetEndDate || '');
            el.setAttribute('data-sequence-order', it.sequenceOrder);
        });
        reorderAndRenumberActivities();
        recomputeLotDayZero();
    }

    /* ================================================================
     * 5. FILTERS — search, type chips, hide-lots chips, show-hidden
     * ================================================================ */

    function hasActiveFilters() {
        const q = ($id('activitySearchInput')?.value || '').trim();
        const types = $id('typeFilterChips') ? chipValues($id('typeFilterChips')) : [];
        const lots = $id('lotFilterChips') ? chipValues($id('lotFilterChips')) : [];
        return q !== '' || types.length > 0 || lots.length > 0;
    }

    function _cardAllLotsHidden(card, hiddenLotIds) {
        const sig = String(card.getAttribute('data-lot-signature') || '').trim();
        const lotIds = sig ? sig.split(',').filter(Boolean) : [];
        if (lotIds.length === 0) return hiddenLotIds.includes('__na__');
        return lotIds.every((id) => hiddenLotIds.includes(id));
    }

    function applyActivityFilter() {
        const list = $id('activitiesList');
        if (!list) return;
        const raw = ($id('activitySearchInput')?.value || '').trim().toLowerCase();
        const needle = raw.replace(/\s+/g, ' ');
        const activeTypes = $id('typeFilterChips') ? chipValues($id('typeFilterChips')) : [];
        const hiddenLotIds = $id('lotFilterChips') ? chipValues($id('lotFilterChips')) : [];
        const hasType = activeTypes.length > 0;
        const hasLots = hiddenLotIds.length > 0;
        const cards = $qsa('.activity-card[data-id]', list);

        $id('lotFilterClearBtn')?.classList.toggle('hidden', !hasLots);

        if (!needle && !hasType && !hasLots) {
            cards.forEach((c) => c.classList.remove('filter-hidden'));
            $qsa('.date-group', list).forEach((g) => g.classList.remove('group-collapsed'));
            $qsa('.rest-day-marker', list).forEach((r) => r.classList.remove('filters-active'));
            const count = $id('activitySearchCount');
            if (count) count.textContent = '';
            return;
        }

        let visible = 0;
        cards.forEach((card) => {
            const text = ((card.getAttribute('data-search') || '') + ' ' + card.textContent.toLowerCase()).replace(/\s+/g, ' ');
            const cardType = String(card.getAttribute('data-activity-type') || '');
            const matches = (!needle || text.includes(needle))
                && (!hasType || activeTypes.includes(cardType))
                && (!hasLots || !_cardAllLotsHidden(card, hiddenLotIds));
            card.classList.toggle('filter-hidden', !matches);
            if (matches) visible++;
        });

        // Collapse date groups whose every card is filtered out; hide rest days.
        $qsa('.date-group', list).forEach((g) => {
            const hasVisible = $qsa('.activity-card[data-id]:not(.filter-hidden)', g).length > 0;
            g.classList.toggle('group-collapsed', !hasVisible);
        });
        $qsa('.rest-day-marker', list).forEach((r) => r.classList.add('filters-active'));

        const count = $id('activitySearchCount');
        if (count) count.textContent = `${visible} shown`;
    }

    let searchTimer = null;
    $id('activitySearchInput')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyActivityFilter, 80);
    });

    document.addEventListener('chips:change', (e) => {
        const group = e.target;
        if (!group || !group.id) return;
        if (group.id === 'typeFilterChips' || group.id === 'lotFilterChips') applyActivityFilter();
        if (group.id === 'laborGroupsContainer' || group.id === 'laborWorkersContainer') updateLaborFilterHint();
    });

    $id('lotFilterAllBtn')?.addEventListener('click', () => {
        $qsa('#lotFilterChips .chip').forEach((c) => c.classList.add('is-selected'));
        applyActivityFilter();
    });
    $id('lotFilterClearBtn')?.addEventListener('click', () => {
        $qsa('#lotFilterChips .chip').forEach((c) => c.classList.remove('is-selected'));
        applyActivityFilter();
    });

    // ---- Show-hidden toggle (persisted per schedule) ----
    const HIDDEN_TOGGLE_KEY = 'showHiddenActivities:' + SCHEDULE_ID;

    function refreshHiddenActivityCount() {
        const n = $qsa('#activitiesList .activity-card.is-hidden').length;
        const btn = $id('toggleHiddenBtn');
        if (!btn) return;
        const showing = document.body.classList.contains('show-hidden-activities');
        $id('toggleHiddenLabel').textContent = (showing ? 'Hide Hidden' : 'Show Hidden') + ' (' + n + ')';
        btn.classList.toggle('hidden', n === 0);
        if (n === 0 && showing) {
            document.body.classList.remove('show-hidden-activities');
            localStorage.setItem(HIDDEN_TOGGLE_KEY, '0');
        }
    }

    $id('toggleHiddenBtn')?.addEventListener('click', () => {
        const next = !document.body.classList.contains('show-hidden-activities');
        document.body.classList.toggle('show-hidden-activities', next);
        localStorage.setItem(HIDDEN_TOGGLE_KEY, next ? '1' : '0');
        refreshHiddenActivityCount();
    });

    if (localStorage.getItem(HIDDEN_TOGGLE_KEY) === '1') {
        document.body.classList.add('show-hidden-activities');
    }
    refreshHiddenActivityCount();

    /* ================================================================
     * 6. ADD / EDIT ACTIVITY SHEET
     * ================================================================ */

    // ---- Lots chips (mutual exclusion with the N/A pseudo-chip) ----
    $id('activityLotsContainer')?.addEventListener('click', (e) => {
        const chip = e.target.closest('.lot-chip');
        if (!chip) return;
        if (chip.hasAttribute('data-lot-na')) {
            const willActivate = !chip.classList.contains('is-selected');
            if (willActivate) {
                $qsa('#activityLotsContainer .lot-chip:not([data-lot-na])').forEach((c) => {
                    c.classList.remove('is-selected');
                    c.setAttribute('aria-pressed', 'false');
                });
            }
            chip.classList.toggle('is-selected', willActivate);
            chip.setAttribute('aria-pressed', willActivate ? 'true' : 'false');
        } else {
            const na = $qs('#activityLotsContainer .lot-chip[data-lot-na]');
            if (na) {
                na.classList.remove('is-selected');
                na.setAttribute('aria-pressed', 'false');
            }
            chip.classList.toggle('is-selected');
            chip.setAttribute('aria-pressed', chip.classList.contains('is-selected') ? 'true' : 'false');
        }
        refreshActivityModalLotState();
    });

    function setActivityLots(lotIds) {
        const ids = (lotIds || []).map(Number);
        const useNa = ids.length === 0;
        $qsa('#activityLotsContainer .lot-chip').forEach((c) => {
            if (c.hasAttribute('data-lot-na')) {
                c.classList.toggle('is-selected', useNa);
                c.setAttribute('aria-pressed', useNa ? 'true' : 'false');
            } else {
                const on = ids.includes(parseInt(c.getAttribute('data-lot-id'), 10));
                c.classList.toggle('is-selected', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            }
        });
    }

    function getActivityLotIds() {
        if ($qs('#activityLotsContainer .lot-chip[data-lot-na].is-selected')) return [];
        return $qsa('#activityLotsContainer .lot-chip.is-selected:not([data-lot-na])')
            .map((c) => parseInt(c.getAttribute('data-lot-id'), 10));
    }

    // ---- Worker chips ----
    $id('activityWorkersContainer')?.addEventListener('click', (e) => {
        const chip = e.target.closest('.worker-chip');
        if (!chip) return;
        chip.classList.toggle('is-selected');
        chip.setAttribute('aria-pressed', chip.classList.contains('is-selected') ? 'true' : 'false');
    });

    function setActivityWorkers(workerIds) {
        const ids = (workerIds || []).map(Number);
        $qsa('#activityWorkersContainer .worker-chip').forEach((c) => {
            const on = ids.includes(parseInt(c.getAttribute('data-worker-id'), 10));
            c.classList.toggle('is-selected', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function getActivityWorkerIds() {
        return $qsa('#activityWorkersContainer .worker-chip.is-selected')
            .map((c) => parseInt(c.getAttribute('data-worker-id'), 10));
    }

    // ---- Day 0 toggle visibility ----
    // Show unless a selected lot is already anchored by ANOTHER source
    // (a different activity or a manual lot date).
    function shouldShowDayZeroToggle() {
        const currentId = parseInt($id('activityId').value, 10);
        const hasCurrentId = !isNaN(currentId) && currentId > 0;
        const selected = getActivityLotIds();
        if (selected.length === 0) return true;
        for (const lotId of selected) {
            const src = LOT_DAY_ZERO_SOURCE[lotId];
            if (!src) continue;
            if (hasCurrentId && src === currentId) continue;
            return false;
        }
        return true;
    }

    function refreshDayZeroToggleVisibility() {
        const panel = $id('activityDayZeroPanel');
        if (!panel) return;
        if (shouldShowDayZeroToggle()) {
            panel.classList.remove('hidden');
        } else {
            $id('activityIsDayZero').checked = false;
            panel.classList.add('hidden');
        }
    }

    // ---- DAS day-number lens over the date inputs ----
    function _activityDasAnchor() {
        const lotId = parseInt($id('activityDasRefLot')?.value, 10);
        if (!lotId) return null;
        return LOT_DAY_ZERO_DATES[lotId] || null;
    }

    function _dasToDateStr(das, anchorStr) {
        const a = parseLocalDate(anchorStr);
        const n = parseInt(das, 10);
        if (!a || isNaN(n)) return '';
        return isoFromDate(new Date(a.getFullYear(), a.getMonth(), a.getDate() + n));
    }

    function _dateStrToDas(dateStr, anchorStr) {
        const a = parseLocalDate(anchorStr);
        const b = parseLocalDate(dateStr);
        if (!a || !b) return '';
        return Math.round((b - a) / 86400000);
    }

    function syncActivityDasFromDates() {
        const anchor = _activityDasAnchor();
        if (!anchor) return;
        $id('activityStartDas').value = _dateStrToDas($id('activityTargetDate').value, anchor);
        $id('activityEndDas').value = _dateStrToDas($id('activityTargetEndDate').value, anchor);
        const dt = dayType();
        const sel = $id('activityDasRefLot');
        const lotName = sel.options[sel.selectedIndex]?.textContent || '';
        $id('activityDasAnchorNote').innerHTML =
            `<strong>${esc(dt)} 0</strong> for <strong>${esc(lotName)}</strong> = ${esc(prettyDate(anchor))}. ` +
            `Typing a ${esc(dt)} number rewrites the date above — the date is what gets saved.`;
    }

    function refreshActivityDasRow() {
        const row = $id('activityDasRow');
        if (!row) return;
        const currentId = parseInt($id('activityId').value, 10);
        const hasCurrentId = !isNaN(currentId) && currentId > 0;

        const candidates = getActivityLotIds().filter((lotId) => {
            if (!LOT_DAY_ZERO_DATES[lotId]) return false;
            if (hasCurrentId && LOT_DAY_ZERO_SOURCE[lotId] === currentId) return false;
            return true;
        });

        if ($id('activityIsDayZero').checked || candidates.length === 0) {
            row.classList.add('hidden');
            $id('activityStartDas').value = '';
            $id('activityEndDas').value = '';
            return;
        }

        const sel = $id('activityDasRefLot');
        const prev = parseInt(sel.value, 10);
        sel.innerHTML = '';
        candidates.forEach((lotId) => {
            const opt = document.createElement('option');
            opt.value = lotId;
            opt.textContent = LOT_NAMES[lotId] || ('Lot #' + lotId);
            sel.appendChild(opt);
        });
        if (candidates.includes(prev)) sel.value = prev;

        row.classList.remove('hidden');
        syncActivityDasFromDates();
    }

    function refreshActivityModalLotState() {
        refreshDayZeroToggleVisibility();   // may force-uncheck Day 0…
        refreshActivityDasRow();            // …which this then reads
    }

    $id('activityStartDas')?.addEventListener('input', function () {
        const anchor = _activityDasAnchor();
        if (!anchor || this.value === '') return;
        $id('activityTargetDate').value = _dasToDateStr(this.value, anchor);
    });
    $id('activityEndDas')?.addEventListener('input', function () {
        const anchor = _activityDasAnchor();
        if (!anchor) return;
        if (this.value === '') {
            $id('activityTargetEndDate').value = '';
            return;
        }
        $id('activityTargetEndDate').value = _dasToDateStr(this.value, anchor);
    });
    $id('activityTargetDate')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityTargetEndDate')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityDasRefLot')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityIsDayZero')?.addEventListener('change', refreshActivityDasRow);
    $id('activityTargetEndDateClear')?.addEventListener('click', () => {
        $id('activityTargetEndDate').value = '';
        syncActivityDasFromDates();
    });

    // ---- Quill description editor (+ HTML source mode) ----
    const SM_QUILL_TOOLBAR = [
        [{ header: [1, 2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ indent: '-1' }, { indent: '+1' }],
        ['blockquote', 'code-block'],
        ['link'],
        ['clean'],
    ];
    let descQuill = null;
    let descMode = 'visual';
    let pendingDescription;

    function initDescriptionEditor() {
        if (typeof Quill === 'undefined' || descQuill) return;
        descQuill = new Quill('#activityDescription', {
            theme: 'snow',
            placeholder: 'Describe this activity…',
            modules: { toolbar: SM_QUILL_TOOLBAR },
        });
        if (pendingDescription !== undefined) {
            descQuill.clipboard.dangerouslyPasteHTML(pendingDescription || '');
            pendingDescription = undefined;
        }
    }

    function destroyDescriptionEditor() {
        if (!descQuill) return;
        // Quill has no destroy — strip the injected toolbar/container.
        $qs('#activityDescriptionWrap .ql-toolbar')?.remove();
        const host = $id('activityDescription');
        host.innerHTML = '';
        host.classList.remove('ql-container', 'ql-snow');
        host.removeAttribute('style');
        descQuill = null;
    }

    function getDescriptionContent() {
        if (descMode === 'html') return $id('activityDescriptionSource').value || '';
        if (descQuill) {
            const html = descQuill.root.innerHTML;
            return html === '<p><br></p>' ? '' : html;
        }
        return pendingDescription || '';
    }

    function setDescriptionContent(html) {
        if (descMode === 'html') {
            $id('activityDescriptionSource').value = html || '';
            return;
        }
        if (descQuill) {
            descQuill.clipboard.dangerouslyPasteHTML(html || '');
        } else {
            pendingDescription = html || '';
        }
    }

    function setDescriptionMode(mode) {
        if (mode === descMode) return;
        const wrap = $id('activityDescriptionWrap');
        if (mode === 'html') {
            const html = getDescriptionContent();
            descMode = 'html';
            $id('activityDescriptionSource').value = html;
            wrap.classList.add('is-html-mode');
            $id('toggleDescriptionModeLabel').textContent = 'Back to visual editor';
        } else {
            const html = $id('activityDescriptionSource').value || '';
            descMode = 'visual';
            wrap.classList.remove('is-html-mode');
            if (descQuill) descQuill.clipboard.dangerouslyPasteHTML(html);
            $id('toggleDescriptionModeLabel').textContent = 'Edit HTML source';
        }
    }

    $id('toggleDescriptionMode')?.addEventListener('click', (e) => {
        e.preventDefault();
        setDescriptionMode(descMode === 'visual' ? 'html' : 'visual');
    });

    const activitySheetEl = $id('activitySheet');
    activitySheetEl?.addEventListener('sheet:open', () => {
        setTimeout(initDescriptionEditor, 40);   // Quill needs a visible mount
    });
    activitySheetEl?.addEventListener('sheet:close', () => {
        destroyDescriptionEditor();
        descMode = 'visual';
        $id('activityDescriptionWrap').classList.remove('is-html-mode');
        $id('activityDescriptionSource').value = '';
        $id('toggleDescriptionModeLabel').textContent = 'Edit HTML source';
    });

    // ---- Image upload (two-phase: upload immediately, persist on save) ----
    function setActivityImage(path, url) {
        $id('activityImagePath').value = path || '';
        if (path && url) {
            $id('activityImagePreview').src = url;
            $id('activityImageWrap').classList.remove('hidden');
            $id('activityImageEmpty').classList.add('hidden');
        } else {
            $id('activityImagePreview').src = '';
            $id('activityImageWrap').classList.add('hidden');
            $id('activityImageEmpty').classList.remove('hidden');
        }
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('#activityImageUploadBtn') || e.target.closest('#activityImageReplaceBtn')) {
            const fi = $id('activityImageFileInput');
            fi.value = '';
            fi.click();
        }
        if (e.target.closest('#activityImageRemoveBtn')) {
            setActivityImage('', '');
        }
    });

    $id('activityImageFileInput')?.addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        if (!/^image\//.test(file.type)) {
            toast('Pick an image file (JPG, PNG, WebP, GIF).', 'error');
            return;
        }
        const fd = new FormData();
        fd.append('image', file);
        const uploadBtn = $id('activityImageUploadBtn');
        const prevLabel = uploadBtn.innerHTML;
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';
        try {
            const res = await api(U.imageUpload(), { method: 'POST', body: fd });
            setActivityImage(res.data.imagePath, res.data.imageUrl);
            toast(res.message || 'Image uploaded.');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = prevLabel;
        }
    });

    // ---- Materials & services item picker ----
    function refreshItemsEmptyState() {
        const hasItems = $qsa('#itemsContainer > span').length > 0;
        $id('itemsContainerEmpty').classList.toggle('hidden', hasItems);
    }

    function rebuildItemPickerOptions() {
        const type = $id('itemPickerType').value;
        const sel = $id('itemPickerId');
        sel.innerHTML = '';
        const rows = type === 'material' ? MATERIALS : SERVICES;
        rows.forEach((row) => {
            const opt = document.createElement('option');
            opt.value = type + '::' + row.id;
            opt.textContent = type === 'material' ? `${row.name} (${row.unit})` : row.name;
            if (row.unit) opt.setAttribute('data-unit', row.unit);
            sel.appendChild(opt);
        });
        if (rows.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = type === 'material' ? 'No materials defined' : 'No services defined';
            sel.appendChild(opt);
        }
        syncItemPickerUnit();
    }

    function syncItemPickerUnit() {
        const sel = $id('itemPickerId');
        const unit = sel.options[sel.selectedIndex]?.getAttribute('data-unit') || '';
        $id('itemPickerUnit').value = unit;
    }

    $id('itemPickerType')?.addEventListener('change', rebuildItemPickerOptions);
    $id('itemPickerId')?.addEventListener('change', syncItemPickerUnit);

    function appendItemTag(type, itemId, label, qty, unit) {
        const unitSafe = unit || '';
        const html = `<span class="item-tag ${type === 'service' ? 'service-tag' : 'material-tag'}"
            data-type="${esc(type)}" data-id="${esc(String(itemId))}" data-qty="${esc(trimQty(qty))}" data-unit="${esc(unitSafe)}">
            <strong>${esc(label)}</strong>&nbsp;×${esc(trimQty(qty))}${unitSafe ? ' ' + esc(unitSafe) : ''}
            <button type="button" class="remove-item-tag ml-1 font-bold" aria-label="Remove">✕</button>
        </span>`;
        $id('itemsContainer').insertAdjacentHTML('beforeend', html);
        refreshItemsEmptyState();
    }

    $id('itemsContainer')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-item-tag');
        if (!btn) return;
        btn.closest('span').remove();
        refreshItemsEmptyState();
    });

    $id('addItemBtn')?.addEventListener('click', () => {
        const v = $id('itemPickerId').value;
        if (!v) {
            toast('Pick a material or service', 'error');
            return;
        }
        const [type, itemId] = v.split('::');
        const sel = $id('itemPickerId');
        const baseLabel = (sel.options[sel.selectedIndex]?.textContent || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
        const qty = parseFloat($id('itemPickerQty').value) || 1;
        const unit = ($id('itemPickerUnit').value || '').trim();
        if ($qs(`#itemsContainer span[data-type="${type}"][data-id="${itemId}"]`)) {
            toast('Already added — remove and re-add to update quantity/unit.', 'info');
            return;
        }
        appendItemTag(type, itemId, baseLabel, qty, unit);
    });

    // ---- Sheet open/reset/fill ----
    function resetActivitySheet() {
        $id('activityId').value = '';
        $id('activityTitle').value = '';
        $id('activityTargetDate').value = '';
        $id('activityTargetEndDate').value = '';
        $id('activityPriority').value = 'medium';
        $id('activityType').value = '';
        $id('activityTimeRequired').value = 'half';
        $id('activityIsDayZero').checked = false;
        setDescriptionContent('');
        pendingDescription = '';
        setActivityLots([]);
        setActivityWorkers([]);
        setActivityImage('', '');
        $id('itemsContainer').innerHTML = '';
        refreshItemsEmptyState();
    }

    let BEFORE_SNAPSHOT = null;   // pre-edit payload for the edit-undo path

    function openAddActivitySheet(prefillDate) {
        $id('activitySheetTitle').textContent = 'Add Activity';
        resetActivitySheet();
        BEFORE_SNAPSHOT = null;
        if (prefillDate) $id('activityTargetDate').value = prefillDate;
        refreshActivityModalLotState();
        openSheet('activitySheet');
    }

    async function openEditActivitySheet(id) {
        try {
            const res = await api(U.show(id));
            const a = res.data;
            $id('activitySheetTitle').textContent = 'Edit Activity';
            resetActivitySheet();
            BEFORE_SNAPSHOT = JSON.parse(JSON.stringify(a));
            $id('activityId').value = a.id;
            $id('activityTitle').value = a.activityTitle || '';
            $id('activityTargetDate').value = (a.targetDate || '').slice(0, 10);
            $id('activityTargetEndDate').value = (a.targetEndDate || '').slice(0, 10);
            $id('activityPriority').value = a.priority || 'medium';
            $id('activityType').value = a.activityType || '';
            $id('activityTimeRequired').value = a.timeRequired || 'half';
            $id('activityIsDayZero').checked = !!boolFlag(a.isDayZero);
            setActivityLots(a.lotIds || (a.lots || []).map((l) => l.id));
            setActivityWorkers(a.workerIds || (a.workers || []).map((w) => w.id));
            setDescriptionContent(a.description || '');
            setActivityImage(a.imagePath || '', a.imageUrl || '');
            (a.items || []).forEach((it) => {
                if (it.itemType === 'material' && it.material) {
                    appendItemTag('material', it.materialId, it.material.materialName, it.quantity, it.unitOfMeasure || it.material.unitOfMeasure || '');
                } else if (it.itemType === 'service' && it.service) {
                    appendItemTag('service', it.serviceId, it.service.serviceName, it.quantity, it.unitOfMeasure || '');
                }
            });
            refreshActivityModalLotState();
            openSheet('activitySheet');
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    $id('addActivityBtn')?.addEventListener('click', () => openAddActivitySheet());
    $id('fabAddActivity')?.addEventListener('click', () => openAddActivitySheet());

    // Convert an Activity model JSON back into an /update payload — edit-undo path.
    function activityToPayload(a) {
        const lotIds = a.lotIds || (a.lots || []).map((l) => l.id);
        const workerIds = a.workerIds || (a.workers || []).map((w) => w.id);
        const items = (a.items || []).map((it) => ({
            itemType: it.itemType,
            itemId: it.itemType === 'material' ? it.materialId : it.serviceId,
            quantity: it.quantity,
            unitOfMeasure: it.unitOfMeasure || '',
            notes: it.notes || '',
        }));
        return {
            activityTitle: a.activityTitle,
            targetDate: (a.targetDate || '').slice(0, 10),
            targetEndDate: a.targetEndDate ? a.targetEndDate.slice(0, 10) : null,
            priority: a.priority,
            activityType: a.activityType || '',
            description: a.description || '',
            imagePath: a.imagePath || '',
            timeRequired: a.timeRequired,
            isDayZero: boolFlag(a.isDayZero),
            lotIds,
            workerIds,
            items,
        };
    }

    $id('saveActivityBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = $id('activityId').value;
        const startDateVal = ($id('activityTargetDate').value || '').trim();
        const endDateVal = ($id('activityTargetEndDate').value || '').trim();
        if (endDateVal && startDateVal && endDateVal < startDateVal) {
            toast('End date must be on or after the start date.', 'error');
            return;
        }
        const items = $qsa('#itemsContainer > span').map((tag) => ({
            itemType: tag.getAttribute('data-type'),
            itemId: parseInt(tag.getAttribute('data-id'), 10),
            quantity: tag.getAttribute('data-qty'),
            unitOfMeasure: tag.getAttribute('data-unit') || '',
        }));
        const payload = {
            activityTitle: $id('activityTitle').value,
            targetDate: startDateVal,
            targetEndDate: endDateVal || null,
            priority: $id('activityPriority').value,
            activityType: $id('activityType').value || '',
            description: getDescriptionContent(),
            imagePath: ($id('activityImagePath').value || '').trim(),
            timeRequired: $id('activityTimeRequired').value,
            isDayZero: $id('activityIsDayZero').checked ? 1 : 0,
            lotIds: getActivityLotIds(),   // empty = N/A (not lot-specific)
            workerIds: getActivityWorkerIds(),
            items,
        };
        if (!payload.activityTitle) {
            toast('Activity title is required', 'error');
            return;
        }
        if (!payload.targetDate) {
            toast('Pick a start date', 'error');
            return;
        }

        btn.disabled = true;
        try {
            const res = await api(id ? U.update(id) : U.store(), { method: id ? 'PUT' : 'POST', body: payload });
            toast(res.message);
            closeSheet('activitySheet');
            const savedTitle = res.data.activityTitle || payload.activityTitle || 'activity';
            const html = renderActivityCard(res.data);

            if (id) {
                const existing = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
                if (existing) existing.outerHTML = html;
                const before = BEFORE_SNAPSHOT;
                if (before) {
                    pushUndo(`Edit '${savedTitle}'`, async () => {
                        const r = await api(U.update(before.id), { method: 'PUT', body: activityToPayload(before) });
                        if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                        _renderCardOrReplace(r.data);
                    });
                }
            } else {
                $id('activitiesEmpty')?.remove();
                $id('activitiesList').insertAdjacentHTML('beforeend', html);
                const newId = res.data.id;
                pushUndo(`Add '${savedTitle}'`, async () => {
                    const r = await api(U.destroy(newId), { method: 'DELETE' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                    _removeCardById(newId);
                });
            }
            reorderAndRenumberActivities();
            recomputeLotDayZero();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================
     * 7. PER-CARD ACTIONS
     * ================================================================ */

    async function duplicateActivity(id, name) {
        try {
            const res = await api(U.duplicate(id), { method: 'POST' });
            toast(`Duplicated "${name}". Edit and save when ready.`);
            $id('activitiesEmpty')?.remove();
            $id('activitiesList').insertAdjacentHTML('beforeend', renderActivityCard(res.data));
            reorderAndRenumberActivities();
            recomputeLotDayZero();
            const copyId = res.data.id;
            pushUndo(`Duplicate '${name}'`, async () => {
                const r = await api(U.destroy(copyId), { method: 'DELETE' });
                if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                _removeCardById(copyId);
            });
            openEditActivitySheet(copyId);   // open the copy for editing right away
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function deleteActivity(id, name) {
        const ok = await confirmAction({
            title: 'Delete activity',
            message: `Delete activity "${name}"?`,
            detail: 'You can immediately undo this (Ctrl+Z) — the activity is soft-deleted and can be restored.',
            confirmText: 'Delete Activity',
        });
        if (!ok) return;
        try {
            const res = await api(U.destroy(id), { method: 'DELETE' });
            toast(res.message);
            _removeCardById(id);
            pushUndo(`Delete '${name}'`, async () => {
                const r = await api(U.restore(id), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
            });
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function moveActivityToDrafts(id, name) {
        try {
            await api(U.toDraft(id), { method: 'POST' });
            toast(`"${name}" moved to drafts`);
            _removeCardById(id);
            bumpDraftsBadge(1);
            pushUndo(`Move '${name}' to drafts`, async () => {
                const r = await api(U.fromDraft(id), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
                bumpDraftsBadge(-1);
            });
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function toggleActivityHidden(id) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (!card) return;
        const wantHide = !card.classList.contains('is-hidden');

        // Optimistic flip; revert on failure.
        const applyState = (hidden) => {
            card.classList.toggle('is-hidden', hidden);
            card.setAttribute('data-is-hidden', hidden ? 1 : 0);
            const tag = $qs('.hide-activity-tag', card);
            if (tag) tag.style.display = hidden ? '' : 'none';
            const btn = $qs('.hide-activity-toggle', card);
            if (btn) btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        };
        applyState(wantHide);
        reorderAndRenumberActivities();   // recompute all-hidden groups + substitutes

        try {
            const res = await api(U.toggleHidden(id), { method: 'POST' });
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
            const cardNow = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
            if (cardNow) {
                cardNow.classList.toggle('is-hidden', !wantHide);
                cardNow.setAttribute('data-is-hidden', !wantHide ? 1 : 0);
                const tag = $qs('.hide-activity-tag', cardNow);
                if (tag) tag.style.display = !wantHide ? '' : 'none';
                const btn = $qs('.hide-activity-toggle', cardNow);
                if (btn) btn.setAttribute('aria-pressed', !wantHide ? 'true' : 'false');
            }
            reorderAndRenumberActivities();
        }
    }

    // Single-card move-to-date (mobile fallback for drag & drop).
    let CARD_MENU = { id: null, name: '' };

    function moveSingleActivity(id, newDate) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (!card || !newDate) return;
        const oldDate = (card.getAttribute('data-target-date') || '').trim();
        if (newDate === oldDate) {
            toast('That is already the current date.', 'info');
            return;
        }
        const snapshot = captureBoardSnapshot();
        const oldEnd = (card.getAttribute('data-target-end-date') || '').trim();
        let newEnd = '';
        if (oldEnd && oldDate) {
            newEnd = isoAddDays(oldEnd, isoDaysBetween(oldDate, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEnd);
        card.setAttribute('data-sequence-order', 0);

        const items = [{ id: parseInt(id, 10), targetDate: newDate, targetEndDate: newEnd || null, sequenceOrder: 0 }];
        reorderAndRenumberActivities();

        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast('Moved to ' + newDate);
                recomputeLotDayZero();
                pushUndo('Move activity to ' + newDate, () => restoreBoardSnapshot(snapshot));
            })
            .catch((err) => toast(err.message, 'error'));
    }

    // Day overflow menu (phones). Rows forward to the real date-header buttons,
    // which stay in the DOM (desktop-only visually), so no handler is duplicated.
    let dayMenuDate = null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.day-menu-btn');
        if (!btn) return;
        dayMenuDate = btn.getAttribute('data-date') || '';
        const label = $id('dayMenuTitle');
        if (label) label.textContent = dayMenuDate ? prettyDateFull(dayMenuDate) : 'This day';
        openSheet('dayMenuSheet');
    });

    document.addEventListener('click', (e) => {
        const row = e.target.closest('.day-menu-action');
        if (!row || !dayMenuDate) return;
        const cls = row.getAttribute('data-action');
        closeSheet('dayMenuSheet');
        const target = $qs(`#activitiesList .date-group[data-date="${dayMenuDate}"] .${cls}`);
        // Defer so the sheet is closed before the next one opens.
        setTimeout(() => target?.click(), 260);
    });

    // Tapping the card body opens the editor — the primary action on a phone,
    // where aiming for a small pencil icon is awkward. Interactive bits and the
    // lot chips (used by the lot filter) keep their own behaviour.
    document.addEventListener('click', (e) => {
        if (e.target.closest('button, a, input, textarea, select, label, .item-tag')) return;
        const card = e.target.closest('#activitiesList .activity-card[data-id]');
        if (!card) return;
        openEditActivitySheet(card.getAttribute('data-id'));
    });

    // Card + timeline click delegation.
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-activity-btn');
        if (editBtn) {
            openEditActivitySheet(editBtn.getAttribute('data-id'));
            return;
        }
        const dupBtn = e.target.closest('.duplicate-activity-btn');
        if (dupBtn) {
            duplicateActivity(dupBtn.getAttribute('data-id'), dupBtn.getAttribute('data-name') || 'activity');
            return;
        }
        const draftBtn = e.target.closest('.to-draft-activity-btn');
        if (draftBtn) {
            moveActivityToDrafts(draftBtn.getAttribute('data-id'), draftBtn.getAttribute('data-name') || 'activity');
            return;
        }
        const delBtn = e.target.closest('.delete-activity-btn');
        if (delBtn) {
            deleteActivity(delBtn.getAttribute('data-id'), delBtn.getAttribute('data-name') || 'activity');
            return;
        }
        const hideBtn = e.target.closest('.hide-activity-toggle');
        if (hideBtn) {
            toggleActivityHidden(hideBtn.getAttribute('data-id'));
            return;
        }
        const menuBtn = e.target.closest('.card-menu-btn');
        if (menuBtn) {
            CARD_MENU = { id: menuBtn.getAttribute('data-id'), name: menuBtn.getAttribute('data-name') || 'Activity' };
            $id('cardMenuTitle').textContent = CARD_MENU.name;
            const card = $qs(`#activitiesList .activity-card[data-id="${CARD_MENU.id}"]`);
            $id('cardMenuHideLabel').textContent = card && card.classList.contains('is-hidden')
                ? 'Show in presentations'
                : 'Hide from presentations';
            openSheet('cardMenuSheet');
            return;
        }
        const menuAction = e.target.closest('[data-card-menu-action]');
        if (menuAction && CARD_MENU.id) {
            const action = menuAction.getAttribute('data-card-menu-action');
            const { id, name } = CARD_MENU;
            closeSheet('cardMenuSheet');
            if (action === 'edit') openEditActivitySheet(id);
            else if (action === 'duplicate') duplicateActivity(id, name);
            else if (action === 'draft') moveActivityToDrafts(id, name);
            else if (action === 'delete') deleteActivity(id, name);
            else if (action === 'hide') toggleActivityHidden(id);
            else if (action === 'move') {
                const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
                $id('moveDateName').textContent = name;
                $id('moveDateInput').value = card ? (card.getAttribute('data-target-date') || '') : '';
                openSheet('moveDateSheet');
            }
            return;
        }
    });

    $id('confirmMoveDateBtn')?.addEventListener('click', () => {
        const newDate = $id('moveDateInput').value;
        if (!newDate) {
            toast('Pick a date.', 'error');
            return;
        }
        closeSheet('moveDateSheet');
        moveSingleActivity(CARD_MENU.id, newDate);
    });

    /* ================================================================
     * 8. DATE-GROUP ACTIONS
     * ================================================================ */

    document.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.rest-day-add-btn, .group-add-activity-btn');
        if (addBtn) {
            e.preventDefault();
            openAddActivitySheet((addBtn.getAttribute('data-date') || '').trim());
            return;
        }
        const changeBtn = e.target.closest('.change-group-date-btn');
        if (changeBtn) {
            e.preventDefault();
            const oldDate = (changeBtn.getAttribute('data-date') || '').trim();
            if (!oldDate || oldDate === '__no-date__') return;
            const cards = $qsa(`#activitiesList .date-group[data-date="${oldDate}"] .activity-card[data-id]`);
            $id('changeGroupDateCount').textContent = cards.length;
            $id('changeGroupDateCurrent').textContent = prettyDateFull(oldDate);
            $id('changeGroupDateOld').value = oldDate;
            $id('changeGroupDateNew').value = oldDate;
            openSheet('changeGroupDateSheet');
            return;
        }
        const delGroupBtn = e.target.closest('.delete-group-date-btn');
        if (delGroupBtn) {
            e.preventDefault();
            deleteDateGroup((delGroupBtn.getAttribute('data-date') || '').trim());
            return;
        }
    });

    /**
     * Move every activity of one date group to another date, preserving each
     * card's duration and order. Shared by the "change group date" sheet and by
     * dragging a date header onto another day.
     */
    async function moveGroupToDate(oldDate, newDate) {
        if (!oldDate || !newDate) return false;
        if (newDate === oldDate) {
            toast('That is already the current date.', 'info');
            return false;
        }
        const cards = $qsa(`#activitiesList .date-group[data-date="${oldDate}"] .activity-card[data-id]`);
        if (cards.length === 0) {
            toast('No activities to move.', 'error');
            return false;
        }

        const delta = isoDaysBetween(oldDate, newDate);
        const snapshot = captureBoardSnapshot();
        const items = cards.map((card) => {
            const oldEnd = (card.getAttribute('data-target-end-date') || '').trim();
            return {
                id: parseInt(card.getAttribute('data-id'), 10),
                targetDate: newDate,
                targetEndDate: oldEnd ? isoAddDays(oldEnd, delta) : null,
                sequenceOrder: parseInt(card.getAttribute('data-sequence-order'), 10) || 0,
            };
        });

        try {
            await api(U.reorder(), { method: 'POST', body: { items } });
            items.forEach((it) => {
                const el = $qs(`#activitiesList .activity-card[data-id="${it.id}"]`);
                if (!el) return;
                el.setAttribute('data-target-date', it.targetDate);
                el.setAttribute('data-target-end-date', it.targetEndDate || '');
            });
            reorderAndRenumberActivities();
            recomputeLotDayZero();
            toast(`Moved ${items.length} ${items.length === 1 ? 'activity' : 'activities'} to ${newDate}`);
            pushUndo(`Move group from ${oldDate} to ${newDate}`, () => restoreBoardSnapshot(snapshot));
            return true;
        } catch (err) {
            toast(err.message, 'error');
            return false;
        }
    }

    $id('confirmChangeGroupDateBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const oldDate = ($id('changeGroupDateOld').value || '').trim();
        const newDate = ($id('changeGroupDateNew').value || '').trim();
        if (!oldDate) return;
        if (!newDate) {
            toast('Pick a new date.', 'error');
            return;
        }

        btn.disabled = true;
        try {
            if (await moveGroupToDate(oldDate, newDate)) {
                closeSheet('changeGroupDateSheet');
            }
        } finally {
            btn.disabled = false;
        }
    });

    async function deleteDateGroup(dateKey) {
        if (!dateKey || dateKey === '__no-date__') return;
        const cards = $qsa(`#activitiesList .date-group[data-date="${dateKey}"] .activity-card[data-id]`);
        if (cards.length === 0) {
            toast('No activities to delete in this group.', 'error');
            return;
        }
        const targets = cards.map((card) => ({
            id: parseInt(card.getAttribute('data-id'), 10),
            name: ($qs('h3', card)?.textContent || '').trim() || ('Activity #' + card.getAttribute('data-id')),
        }));
        const ok = await confirmAction({
            title: 'Delete entire date group',
            message: `Delete all ${targets.length} ${targets.length === 1 ? 'activity' : 'activities'} on ${prettyDateFull(dateKey)}?`,
            detail: 'You can immediately undo this (Ctrl+Z) — every activity is soft-deleted and can be restored together.',
            confirmText: targets.length === 1 ? 'Delete 1 Activity' : `Delete ${targets.length} Activities`,
        });
        if (!ok) return;

        // Parallel deletes; only act on the ones that succeed.
        const results = await Promise.all(targets.map((t) =>
            api(U.destroy(t.id), { method: 'DELETE' })
                .then(() => ({ id: t.id, name: t.name, ok: true }))
                .catch(() => ({ id: t.id, name: t.name, ok: false }))
        ));
        const succeeded = results.filter((r) => r.ok);
        const failed = results.filter((r) => !r.ok);

        succeeded.forEach((r) => $qs(`#activitiesList .activity-card[data-id="${r.id}"]`)?.remove());
        reorderAndRenumberActivities();
        recomputeLotDayZero();

        if (succeeded.length > 0) toast(`Deleted ${succeeded.length} ${succeeded.length === 1 ? 'activity' : 'activities'} on ${dateKey}`);
        if (failed.length > 0) toast(`${failed.length} could not be deleted — refresh and try again.`, 'error');

        if (succeeded.length > 0) {
            const ids = succeeded.map((r) => r.id);
            const label = succeeded.length === 1
                ? `Delete '${succeeded[0].name}'`
                : `Delete ${succeeded.length} activities on ${dateKey}`;
            pushUndo(label, async () => {
                const restored = (await Promise.all(ids.map((id) =>
                    api(U.restore(id), { method: 'POST' }).then((r) => r.data).catch(() => null)
                ))).filter(Boolean);
                if (restored.length === 0) throw new Error('no activities could be restored');
                restored.forEach((d) => _renderCardOrReplace(d));
            });
        }
    }

    /* ================================================================
     * 9. DATE NOTES (per-date, version-scoped)
     * ================================================================ */

    function _dateNoteContentFor(dateKey) {
        const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
        if (!block || block.style.display === 'none') return '';
        return (block.textContent || '').trim();
    }

    function _refreshDateNoteUI(dateKey, content) {
        const btn = $qs(`#activitiesList .date-note-btn[data-date="${dateKey}"]`);
        const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
        const safe = String(content || '').trim();
        if (btn) {
            btn.classList.toggle('has-note', safe !== '');
            btn.title = safe !== '' ? 'Edit the note for this date' : 'Add a note for this date';
        }
        if (block) {
            block.textContent = safe;
            block.style.display = safe !== '' ? '' : 'none';
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.date-note-btn');
        if (!btn) return;
        e.preventDefault();
        const dateKey = (btn.getAttribute('data-date') || '').trim();
        if (!dateKey || dateKey === '__no-date__') return;
        const existing = _dateNoteContentFor(dateKey);
        $id('dateNoteDate').value = dateKey;
        $id('dateNoteSheetDate').textContent = prettyDateFull(dateKey);
        $id('dateNoteContent').value = existing;
        $id('dateNoteSheetTitle').textContent = existing ? 'Edit note for this date' : 'Add note for this date';
        $id('dateNoteClearBtn').classList.toggle('hidden', !existing);
        openSheet('dateNoteSheet');
        setTimeout(() => $id('dateNoteContent').focus(), 250);
    });

    $id('dateNoteSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('dateNoteDate').value;
        const content = $id('dateNoteContent').value;
        if (!dateKey) return;
        btn.disabled = true;
        try {
            await api(U.dateNoteSave(), { method: 'POST', body: { noteDate: dateKey, noteContent: content } });
            const saved = (content || '').trim();
            _refreshDateNoteUI(dateKey, saved);
            toast(saved === '' ? 'Note cleared.' : 'Note saved.');
            closeSheet('dateNoteSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    $id('dateNoteClearBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('dateNoteDate').value;
        if (!dateKey) return;
        btn.disabled = true;
        try {
            await api(U.dateNoteDelete(), { method: 'DELETE', body: { noteDate: dateKey } });
            _refreshDateNoteUI(dateKey, '');
            toast('Note cleared.');
            closeSheet('dateNoteSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================
     * 10. PROGRESS MARKERS ("resume here" bookmarks)
     * ================================================================ */

    function _markerInfoFor(dateKey) {
        const row = $qs(`#activitiesList .progress-marker[data-date="${dateKey}"]`);
        if (!row) return null;
        return {
            id: row.getAttribute('data-marker-id') || '',
            note: ($qs('.progress-marker-note', row)?.textContent || '').trim(),
        };
    }

    function _refreshProgressMarkerUI(dateKey, info) {
        const btn = $qs(`#activitiesList .date-marker-btn[data-date="${dateKey}"]`);
        const row = $qs(`#activitiesList .progress-marker[data-date="${dateKey}"]`);
        if (!info) {
            if (btn) {
                btn.classList.remove('has-marker');
                btn.title = 'Drop a resume-here marker after this date';
            }
            row?.remove();
            return;
        }
        if (btn) {
            btn.classList.add('has-marker');
            btn.title = 'Edit the resume-here marker';
        }
        const html = buildMarkerHtml(dateKey, info);
        if (row) {
            row.outerHTML = html;
        } else {
            const group = $qs(`#activitiesList .date-group[data-date="${dateKey}"]`);
            if (group) {
                group.insertAdjacentHTML('afterend', html);
            } else {
                $id('activitiesList').insertAdjacentHTML('beforeend', html);
            }
        }
    }

    function openMarkerSheet(dateKey) {
        const info = _markerInfoFor(dateKey);
        $id('progressMarkerDate').value = dateKey;
        $id('progressMarkerId').value = info ? info.id : '';
        $id('markerSheetDate').textContent = prettyDateFull(dateKey);
        $id('progressMarkerNote').value = info ? info.note : '';
        $id('markerSheetTitle').textContent = info ? 'Edit resume-here marker' : 'Drop resume-here marker';
        $id('progressMarkerClearBtn').classList.toggle('hidden', !info);
        openSheet('markerSheet');
        setTimeout(() => $id('progressMarkerNote').focus(), 250);
    }

    document.addEventListener('click', async (e) => {
        const openBtn = e.target.closest('.date-marker-btn, .progress-marker-edit-btn');
        if (openBtn) {
            e.preventDefault();
            const dateKey = (openBtn.getAttribute('data-date') || '').trim();
            if (dateKey && dateKey !== '__no-date__') openMarkerSheet(dateKey);
            return;
        }
        const delBtn = e.target.closest('.progress-marker-delete-btn');
        if (delBtn) {
            e.preventDefault();
            const markerId = delBtn.getAttribute('data-marker-id') || '';
            const dateKey = (delBtn.getAttribute('data-date') || '').trim();
            if (!markerId) return;
            const ok = await confirmAction({
                title: 'Remove resume-here marker',
                message: `Remove the marker on ${prettyDateFull(dateKey)}?`,
                detail: 'The note attached to it (if any) will be cleared too.',
                confirmText: 'Remove Marker',
            });
            if (!ok) return;
            try {
                await api(U.markerDelete(markerId), { method: 'DELETE' });
                _refreshProgressMarkerUI(dateKey, null);
                toast('Marker removed.');
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    $id('progressMarkerSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('progressMarkerDate').value;
        const content = $id('progressMarkerNote').value;
        if (!dateKey) return;
        btn.disabled = true;
        try {
            const res = await api(U.markerSave(), { method: 'POST', body: { markerDate: dateKey, noteContent: content || '' } });
            const data = res.data || {};
            _refreshProgressMarkerUI(dateKey, { id: String(data.id || ''), note: data.noteContent || '' });
            toast('Marker saved.');
            closeSheet('markerSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    $id('progressMarkerClearBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('progressMarkerDate').value;
        const markerId = $id('progressMarkerId').value;
        if (!markerId) return;
        btn.disabled = true;
        try {
            await api(U.markerDelete(markerId), { method: 'DELETE' });
            _refreshProgressMarkerUI(dateKey, null);
            toast('Marker removed.');
            closeSheet('markerSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================
     * 11. DRAG & DROP (desktop) — reorder within a date, move across
     * dates, or drop onto a rest-day marker. Multi-day cards keep their
     * duration; all groups renumber sequenceOrder = idx * 10; one POST.
     * ================================================================ */

    let dragSourceCard = null;
    let dragOrigin = null;
    let dragBoardSnapshot = null;
    let dragGroupDate = null; // set while dragging a date header (whole-day move)

    document.addEventListener('dragstart', (e) => {
        // Dragging a date header moves that whole day's activities.
        const header = e.target.closest && e.target.closest('.date-header[draggable="true"]');
        if (header) {
            const groupDate = (header.closest('.date-group')?.getAttribute('data-date') || '').trim();
            if (!groupDate || groupDate === '__no-date__') return;
            dragGroupDate = groupDate;
            header.classList.add('dragging');
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', 'group:' + groupDate); } catch (_) { /* noop */ }
            }
            return;
        }

        const card = e.target.closest && e.target.closest('.activity-card[data-id]');
        if (!card) return;
        dragSourceCard = card;
        dragOrigin = {
            date: (card.getAttribute('data-target-date') || '').trim(),
            endDate: (card.getAttribute('data-target-end-date') || '').trim(),
            parent: card.parentNode,
            nextSibling: card.nextElementSibling,
        };
        dragBoardSnapshot = captureBoardSnapshot();
        card.classList.add('dragging');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', card.getAttribute('data-id')); } catch (_) { /* noop */ }
        }
    });

    document.addEventListener('dragend', (e) => {
        const card = e.target.closest && e.target.closest('.activity-card');
        card?.classList.remove('dragging');
        $qsa('.date-header.dragging').forEach((el) => el.classList.remove('dragging'));
        $qsa('.date-activities.drag-over, .rest-day-marker.drag-over, .date-group.drag-over-group')
            .forEach((el) => el.classList.remove('drag-over', 'drag-over-group'));
        dragSourceCard = null;
        dragOrigin = null;
        dragGroupDate = null;
    });

    function dragoverPosition(container, cursorY) {
        const cards = $qsa('.activity-card[data-id]', container).filter((c) => c !== dragSourceCard);
        for (const card of cards) {
            const rect = card.getBoundingClientRect();
            if (cursorY < rect.top + rect.height / 2) return card;
        }
        return null;
    }

    document.addEventListener('dragover', (e) => {
        // Whole-day move: highlight the day (or empty day) being hovered.
        if (dragGroupDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetRest = e.target.closest && e.target.closest('.rest-day-marker');
            const targetDate = (targetGroup?.getAttribute('data-date') || targetRest?.getAttribute('data-date') || '').trim();
            if (!targetDate || targetDate === '__no-date__' || targetDate === dragGroupDate) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            $qsa('.date-group.drag-over-group, .rest-day-marker.drag-over')
                .forEach((el) => el.classList.remove('drag-over-group', 'drag-over'));
            if (targetRest) targetRest.classList.add('drag-over');
            else targetGroup.classList.add('drag-over-group');
            return;
        }

        if (!dragSourceCard) return;
        const container = e.target.closest && e.target.closest('.date-activities');
        if (container) {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            container.classList.add('drag-over');
            const insertBefore = dragoverPosition(container, e.clientY);
            if (insertBefore) {
                if (insertBefore.previousElementSibling !== dragSourceCard) {
                    insertBefore.parentNode.insertBefore(dragSourceCard, insertBefore);
                }
            } else if (container.lastElementChild !== dragSourceCard) {
                container.appendChild(dragSourceCard);
            }
            return;
        }
        const rest = e.target.closest && e.target.closest('.rest-day-marker');
        if (rest) {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            rest.classList.add('drag-over');
        }
    });

    document.addEventListener('dragleave', (e) => {
        const el = e.target.closest && e.target.closest('.date-activities, .rest-day-marker');
        if (el && e.target === el) el.classList.remove('drag-over');
    });

    document.addEventListener('drop', (e) => {
        // Whole-day move via the date header.
        if (dragGroupDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetRest = e.target.closest && e.target.closest('.rest-day-marker');
            const targetDate = (targetGroup?.getAttribute('data-date') || targetRest?.getAttribute('data-date') || '').trim();
            const sourceDate = dragGroupDate;
            $qsa('.date-group.drag-over-group, .rest-day-marker.drag-over')
                .forEach((el) => el.classList.remove('drag-over-group', 'drag-over'));
            dragGroupDate = null;
            if (!targetDate || targetDate === '__no-date__' || targetDate === sourceDate) return;
            e.preventDefault();
            moveGroupToDate(sourceDate, targetDate);
            return;
        }

        if (!dragSourceCard) return;
        const container = e.target.closest && e.target.closest('.date-activities');
        if (container) {
            e.preventDefault();
            container.classList.remove('drag-over');
            handleDropIntoContainer(container);
            return;
        }
        const rest = e.target.closest && e.target.closest('.rest-day-marker');
        if (rest) {
            e.preventDefault();
            rest.classList.remove('drag-over');
            handleDropOntoRestDay(rest);
        }
    });

    function handleDropIntoContainer(container) {
        const card = dragSourceCard;
        const group = container.closest('.date-group');
        const newDate = (group?.getAttribute('data-date') || '').trim();
        const oldDate = (dragOrigin && dragOrigin.date) || '';

        if (!newDate || newDate === '__no-date__') {
            toast('Cannot drop on "No date". Edit the activity instead.', 'error');
            if (dragOrigin && dragOrigin.parent) {
                if (dragOrigin.nextSibling) dragOrigin.parent.insertBefore(card, dragOrigin.nextSibling);
                else dragOrigin.parent.appendChild(card);
            }
            return;
        }

        // Multi-day cards keep duration: shift end by the same delta.
        let newEndDate = '';
        if (dragOrigin.endDate && dragOrigin.date) {
            newEndDate = isoAddDays(dragOrigin.endDate, isoDaysBetween(dragOrigin.date, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEndDate);

        // Renumber destination + (when the date changed) source containers.
        const containers = new Set([container]);
        if (dragOrigin && dragOrigin.parent && dragOrigin.parent !== container) containers.add(dragOrigin.parent);
        const items = [];
        containers.forEach((cont) => {
            const contDate = (cont.closest?.('.date-group')?.getAttribute('data-date') || '').trim();
            if (!contDate || contDate === '__no-date__') return;
            Array.from(cont.children).filter((el) => el.matches?.('.activity-card[data-id]')).forEach((el, idx) => {
                items.push({
                    id: parseInt(el.getAttribute('data-id'), 10),
                    targetDate: contDate,
                    targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
                    sequenceOrder: idx * 10,
                });
                el.setAttribute('data-sequence-order', idx * 10);
            });
        });

        reorderAndRenumberActivities();   // optimistic rebuild

        const snapshot = dragBoardSnapshot;
        dragBoardSnapshot = null;
        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast(oldDate && oldDate !== newDate ? 'Moved to ' + newDate : 'Order saved');
                recomputeLotDayZero();
                if (snapshot && snapshot.length) {
                    const label = (oldDate && oldDate !== newDate) ? 'Move activity to ' + newDate : 'Reorder activities';
                    pushUndo(label, () => restoreBoardSnapshot(snapshot));
                }
            })
            .catch((err) => toast(err.message + ' — refresh to see saved order.', 'error'));
    }

    function handleDropOntoRestDay(rest) {
        const card = dragSourceCard;
        const newDate = (rest.getAttribute('data-date') || '').trim();
        if (!newDate) return;

        let newEndDate = '';
        if (dragOrigin && dragOrigin.endDate && dragOrigin.date) {
            newEndDate = isoAddDays(dragOrigin.endDate, isoDaysBetween(dragOrigin.date, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEndDate);
        card.setAttribute('data-sequence-order', 0);

        const items = [{
            id: parseInt(card.getAttribute('data-id'), 10),
            targetDate: newDate,
            targetEndDate: newEndDate || null,
            sequenceOrder: 0,
        }];
        // Tighten the source group's remaining sequence numbers.
        if (dragOrigin && dragOrigin.parent && dragOrigin.parent.nodeType === 1) {
            const sourceDate = (dragOrigin.parent.closest?.('.date-group')?.getAttribute('data-date') || '').trim();
            if (sourceDate && sourceDate !== newDate && sourceDate !== '__no-date__') {
                Array.from(dragOrigin.parent.children).filter((el) => el.matches?.('.activity-card[data-id]')).forEach((el, idx) => {
                    const cid = parseInt(el.getAttribute('data-id'), 10);
                    if (cid === items[0].id) return;
                    items.push({
                        id: cid,
                        targetDate: sourceDate,
                        targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
                        sequenceOrder: idx * 10,
                    });
                    el.setAttribute('data-sequence-order', idx * 10);
                });
            }
        }

        reorderAndRenumberActivities();

        const snapshot = dragBoardSnapshot;
        dragBoardSnapshot = null;
        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast('Moved to ' + newDate);
                recomputeLotDayZero();
                if (snapshot && snapshot.length) {
                    pushUndo('Move activity to ' + newDate, () => restoreBoardSnapshot(snapshot));
                }
            })
            .catch((err) => toast(err.message + ' — refresh to see saved order.', 'error'));
    }

    /* ================================================================
     * 12. DRAFTS
     * ================================================================ */

    function bumpDraftsBadge(delta) {
        const badge = $id('draftsBadge');
        if (!badge) return;
        badge.textContent = Math.max(0, (parseInt(badge.textContent, 10) || 0) + delta);
    }

    function renderDraftRow(d) {
        const lots = (d.lots || []).map((l) => esc(l.lotName)).join(', ') || '—';
        const dateLabel = d.targetDate ? prettyDateFull(d.targetDate) : 'No date';
        const priority = d.priority || 'medium';
        const priorityCap = priority.charAt(0).toUpperCase() + priority.slice(1);
        return `<div class="card p-3 draft-row" data-id="${d.id}" style="border-left:3px solid #50a5f1;">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0 grow">
                    <p class="font-bold text-gray-900 text-sm">${esc(d.activityTitle)}</p>
                    <p class="text-xs text-gray-500 mt-0.5">${esc(dateLabel)} · Lots: ${lots}</p>
                    ${d.updatedAt ? `<p class="text-xs text-gray-400 mt-0.5">Drafted ${esc(d.updatedAt)}</p>` : ''}
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="pill pill-${esc(priority)}">${esc(priorityCap)}</span>
                    <button type="button" class="btn btn-primary btn-sm restore-draft-btn" data-id="${d.id}" data-name="${esc(d.activityTitle)}">Restore</button>
                    <button type="button" class="icon-btn icon-btn-danger delete-draft-btn" data-id="${d.id}" data-name="${esc(d.activityTitle)}" title="Delete draft">${SVG.trash}</button>
                </div>
            </div>
        </div>`;
    }

    function renderDraftsList(drafts) {
        const container = $id('draftsListContainer');
        if (!drafts || drafts.length === 0) {
            container.innerHTML = '';
            container.classList.add('hidden');
            $id('draftsEmpty').classList.remove('hidden');
            return;
        }
        $id('draftsEmpty').classList.add('hidden');
        container.classList.remove('hidden');
        container.innerHTML = drafts.map(renderDraftRow).join('');
    }

    $id('openDraftsBtn')?.addEventListener('click', async () => {
        const container = $id('draftsListContainer');
        container.classList.remove('hidden');
        container.innerHTML = '<div class="text-center text-gray-400 py-6 text-sm">Loading drafts…</div>';
        $id('draftsEmpty').classList.add('hidden');
        openSheet('draftsSheet');
        try {
            const res = await api(U.drafts());
            const n = (res.data || []).length;
            const badge = $id('draftsBadge');
            if (badge) badge.textContent = n;
            renderDraftsList(res.data || []);
        } catch (err) {
            toast(err.message, 'error');
            renderDraftsList([]);
        }
    });

    document.addEventListener('click', async (e) => {
        const restoreBtn = e.target.closest('.restore-draft-btn');
        if (restoreBtn) {
            const id = restoreBtn.getAttribute('data-id');
            const name = restoreBtn.getAttribute('data-name') || 'activity';
            restoreBtn.disabled = true;
            try {
                const res = await api(U.fromDraft(id), { method: 'POST' });
                toast(`"${name}" restored`);
                _renderCardOrReplace(res.data);
                bumpDraftsBadge(-1);
                const row = restoreBtn.closest('.draft-row');
                row?.remove();
                if ($qsa('#draftsListContainer .draft-row').length === 0) renderDraftsList([]);
                pushUndo(`Restore '${name}' from drafts`, async () => {
                    const r = await api(U.toDraft(id), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'undo failed');
                    _removeCardById(id);
                    bumpDraftsBadge(1);
                });
            } catch (err) {
                toast(err.message, 'error');
                restoreBtn.disabled = false;
            }
            return;
        }
        const deleteBtn = e.target.closest('.delete-draft-btn');
        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-id');
            const name = deleteBtn.getAttribute('data-name') || 'draft';
            const ok = await confirmAction({
                title: 'Delete drafted activity',
                message: `Permanently delete drafted activity "${name}"?`,
                detail: 'You can immediately undo this (Ctrl+Z) — the draft is soft-deleted and can be restored back into the drafts list.',
                confirmText: 'Delete Draft',
            });
            if (!ok) return;
            try {
                await api(U.destroy(id), { method: 'DELETE' });
                toast(`Draft "${name}" deleted`);
                $qs(`#draftsListContainer .draft-row[data-id="${id}"]`)?.remove();
                bumpDraftsBadge(-1);
                if ($qsa('#draftsListContainer .draft-row').length === 0) renderDraftsList([]);
                pushUndo(`Delete draft '${name}'`, async () => {
                    const r = await api(U.restore(id), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                    bumpDraftsBadge(1);
                    // If the drafts sheet is open, surface the returned row again.
                    const sheet = $id('draftsSheet');
                    if (sheet && sheet.classList.contains('is-open')) {
                        $id('draftsEmpty').classList.add('hidden');
                        const container = $id('draftsListContainer');
                        container.classList.remove('hidden');
                        container.insertAdjacentHTML('beforeend', renderDraftRow({
                            id: r.data.id,
                            activityTitle: r.data.activityTitle,
                            targetDate: r.data.targetDate,
                            lots: r.data.lots || [],
                            priority: r.data.priority,
                            updatedAt: r.data.updated_at || null,
                        }));
                    }
                });
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    /* ================================================================
     * 13. LABOR SUMMARY
     * ================================================================ */

    let LATEST_LABOR_DATA = null;

    function getLaborFilterPayload() {
        const payload = {};
        const groupIds = $id('laborGroupsContainer') ? chipValues($id('laborGroupsContainer')).map(Number) : [];
        const workerIds = $id('laborWorkersContainer') ? chipValues($id('laborWorkersContainer')).map(Number) : [];
        const dasMinRaw = ($id('laborDasMin')?.value || '').trim();
        const dasMaxRaw = ($id('laborDasMax')?.value || '').trim();
        const startDateRaw = ($id('laborStartDate')?.value || '').trim();
        const endDateRaw = ($id('laborEndDate')?.value || '').trim();
        if (groupIds.length) payload.groupIds = groupIds;
        if (workerIds.length) payload.workerIds = workerIds;
        if (dasMinRaw !== '' && !isNaN(parseInt(dasMinRaw, 10))) payload.dasMin = parseInt(dasMinRaw, 10);
        if (dasMaxRaw !== '' && !isNaN(parseInt(dasMaxRaw, 10))) payload.dasMax = parseInt(dasMaxRaw, 10);
        if (startDateRaw !== '') payload.startDate = startDateRaw;
        if (endDateRaw !== '') payload.endDate = endDateRaw;
        return payload;
    }

    function laborQueryString() {
        const f = getLaborFilterPayload();
        const parts = [];
        (f.groupIds || []).forEach((id) => parts.push(`groupIds[]=${id}`));
        (f.workerIds || []).forEach((id) => parts.push(`workerIds[]=${id}`));
        if (f.dasMin !== undefined) parts.push(`dasMin=${f.dasMin}`);
        if (f.dasMax !== undefined) parts.push(`dasMax=${f.dasMax}`);
        if (f.startDate) parts.push(`startDate=${encodeURIComponent(f.startDate)}`);
        if (f.endDate) parts.push(`endDate=${encodeURIComponent(f.endDate)}`);
        return parts.length ? '&' + parts.join('&') : '';
    }

    function updateLaborFilterHint() {
        const f = getLaborFilterPayload();
        const parts = [];
        if (f.groupIds) parts.push(`${f.groupIds.length} ${f.groupIds.length === 1 ? 'group' : 'groups'}`);
        if (f.workerIds) parts.push(`${f.workerIds.length} ${f.workerIds.length === 1 ? 'worker' : 'workers'}`);
        if (f.dasMin !== undefined || f.dasMax !== undefined) {
            parts.push(`${dayType()} [${f.dasMin !== undefined ? f.dasMin : '−∞'}, ${f.dasMax !== undefined ? f.dasMax : '+∞'}]`);
        }
        if (f.startDate || f.endDate) parts.push(`Date [${f.startDate || '—'}, ${f.endDate || '—'}]`);
        const hint = $id('laborFilterHint');
        if (hint) hint.textContent = parts.length ? `Filters active: ${parts.join(' · ')}` : '';
    }

    async function reloadLaborSummary() {
        const body = $id('laborSummaryBody');
        body.innerHTML = '<div class="text-center text-gray-400 py-8 text-sm">Calculating…</div>';
        updateLaborFilterHint();
        try {
            const res = await api(U.labor() + laborQueryString());
            LATEST_LABOR_DATA = res.data;
            body.innerHTML = renderLaborSummary(res.data);
        } catch (err) {
            LATEST_LABOR_DATA = null;
            body.innerHTML = `<div class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">${esc(err.message)}</div>`;
        }
    }

    function renderLaborSummary(d) {
        if (!d || d.totalActivities === 0) {
            return `<div class="text-center text-gray-500 py-8">
                <p class="font-bold text-gray-800 mb-1">No activities matched.</p>
                <p class="text-sm">Once activities with workers assigned exist (and pass the filters), the running labor cost shows here.</p>
            </div>`;
        }
        const t = d.totals || { halfDays: 0, wholeDays: 0, naCount: 0, totalAssignments: 0 };
        const dt = d.dayType || 'DAS';
        const phases = d.phases || {};
        const pre = phases.preDayZero || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
        const main = phases.cropping || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
        const una = phases.unanchored || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
        const showUna = una.count > 0;
        const pctPre = d.grandTotal > 0 ? Math.round((pre.cost / d.grandTotal) * 100) : 0;
        const pctMain = d.grandTotal > 0 ? Math.round((main.cost / d.grandTotal) * 100) : 0;

        const phaseCard = (label, sub, cost, meta, extraStyle) => `
            <div class="rounded-xl px-3.5 py-2.5 min-w-44" style="background:rgba(255,255,255,.14);${extraStyle || ''}">
                <div class="text-[10.5px] uppercase tracking-wide opacity-85">${label} <span class="opacity-75">${sub}</span></div>
                <div class="text-xl font-bold leading-tight mt-0.5">${fmtPeso(cost)}</div>
                <div class="text-[11px] opacity-90">${meta}</div>
            </div>`;

        const hero = `
            <div class="rounded-2xl text-white p-4 mb-3" style="background:linear-gradient(135deg,#0f8a5f 0%,#1abc9c 100%);">
                <div class="flex items-end justify-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="text-xs uppercase tracking-wide opacity-85">Total labor expense so far</div>
                        <div class="text-3xl font-bold" style="font-family:var(--font-heading);">${fmtPeso(d.grandTotal)}</div>
                    </div>
                    <div class="text-right text-xs leading-relaxed opacity-95">
                        ${d.totalActivities} ${d.totalActivities === 1 ? 'activity' : 'activities'} ·
                        ${t.totalAssignments} ${t.totalAssignments === 1 ? 'worker assignment' : 'worker assignments'}<br>
                        ${t.halfDays} half-day · ${t.wholeDays} whole-day · ${t.naCount} N/A
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    ${phaseCard('Land Preparation', `(${esc(dt)} &lt; 0)`, pre.cost, `${pre.count} ${pre.count === 1 ? 'activity' : 'activities'}${(pre.halfDays || pre.wholeDays || pre.naCount) ? ` · ${pre.halfDays}H / ${pre.wholeDays}W / ${pre.naCount}N` : ''}${d.grandTotal > 0 ? ` · ${pctPre}%` : ''}`)}
                    ${phaseCard('Main Cropping', `(${esc(dt)} 0 onwards)`, main.cost, `${main.count} ${main.count === 1 ? 'activity' : 'activities'}${(main.halfDays || main.wholeDays || main.naCount) ? ` · ${main.halfDays}H / ${main.wholeDays}W / ${main.naCount}N` : ''}${d.grandTotal > 0 ? ` · ${pctMain}%` : ''}`)}
                    ${showUna ? phaseCard('Unanchored', `(no ${esc(dt)} 0)`, una.cost, `${una.count} ${una.count === 1 ? 'activity' : 'activities'}`, 'background:rgba(0,0,0,.18);') : ''}
                </div>
            </div>`;

        let workerRows;
        if ((d.perWorker || []).length === 0) {
            workerRows = `<tr><td colspan="${showUna ? 7 : 6}" class="text-center text-gray-400 py-4">No workers have been assigned to any activity yet.</td></tr>`;
        } else {
            workerRows = d.perWorker.map((w) => `
                <tr>
                    <td><strong class="text-gray-900">${esc(w.name)}</strong> <span class="text-gray-400">#${w.priority}</span></td>
                    <td class="num">${fmtPeso(w.costPerHalfDay)}</td>
                    <td class="num">${w.halfDays}H / ${w.wholeDays}W${w.naCount > 0 ? ` / ${w.naCount}N` : ''}</td>
                    <td class="num" style="color:#a05a00;">${fmtPeso(w.preDayZeroTotal || 0)}</td>
                    <td class="num" style="color:#0f6f4d;">${fmtPeso(w.croppingTotal || 0)}</td>
                    ${showUna ? `<td class="num text-gray-500">${fmtPeso(w.unanchoredTotal || 0)}</td>` : ''}
                    <td class="num"><strong>${fmtPeso(w.total)}</strong></td>
                </tr>`).join('');
        }
        const workerTable = `
            <p class="font-bold text-gray-900 text-sm mb-2">By Worker</p>
            <div class="overflow-x-auto mb-3">
                <table class="labor-table">
                    <thead><tr>
                        <th>Worker</th><th class="num">Rate</th><th class="num">Assignments</th>
                        <th class="num" style="background:#fff8e1;color:#a05a00;">Land Prep</th>
                        <th class="num" style="background:#e8f5ee;color:#0f6f4d;">Cropping</th>
                        ${showUna ? '<th class="num">Unanchored</th>' : ''}
                        <th class="num">Earned</th>
                    </tr></thead>
                    <tbody>${workerRows}</tbody>
                </table>
            </div>`;

        const renderActivityRow = (a) => {
            let pretty;
            const s = a.targetDate ? parseLocalDate(a.targetDate) : null;
            const en = a.targetEndDate ? parseLocalDate(a.targetEndDate) : null;
            if (s && en && en > s) pretty = `${MONTH_SHORT[s.getMonth()]} ${s.getDate()} → ${MONTH_SHORT[en.getMonth()]} ${en.getDate()}, ${en.getFullYear()}`;
            else if (s) pretty = prettyDate(a.targetDate);
            else pretty = 'No date';
            const days = a.rangeDays || 1;
            const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
            const dasLbl = (a.das === null || a.das === undefined) ? '—' : `${dt}${a.das >= 0 ? '+' : ''}${a.das}`;
            const formula = `${fmtPeso(a.workerRateSum || 0)} × ${units} × ${days}`;
            return `<tr class="${a.cost === 0 ? 'text-gray-400' : ''}">
                <td><strong class="text-gray-900">${esc(a.activityTitle)}</strong></td>
                <td>${esc(pretty)}${days > 1 ? ` <span class="badge badge-yellow">${days}d</span>` : ''}</td>
                <td><span class="badge badge-gray">${esc(dasLbl)}</span></td>
                <td><span class="badge badge-gray">${timeRequiredShortLabel(a.timeRequired)}</span></td>
                <td class="num">${a.workerCount}</td>
                <td class="num text-gray-400" style="font-size:11px;" title="Σ(rates) × units/day × days = cost">${esc(formula)}</td>
                <td class="num"><strong>${fmtPeso(a.cost)}</strong></td>
            </tr>`;
        };

        const renderSection = (items, label, accent, subtotal) => {
            if (!items.length) return '';
            return `<div class="mt-3 pl-3" style="border-left:3px solid ${accent};">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                    <p class="font-bold text-sm mb-0" style="color:${accent};">${esc(label)}
                        <span class="badge" style="background:${accent}1a;color:${accent};">${items.length} ${items.length === 1 ? 'activity' : 'activities'}</span>
                    </p>
                    <p class="text-sm text-gray-700 mb-0">Subtotal: <strong style="color:${accent};">${fmtPeso(subtotal)}</strong></p>
                </div>
                <div class="overflow-x-auto">
                    <table class="labor-table">
                        <thead><tr><th>Activity</th><th>Date</th><th>${esc(dt)}</th><th>Time</th><th class="num">Workers</th><th class="num">Formula</th><th class="num">Cost</th></tr></thead>
                        <tbody>${items.map(renderActivityRow).join('')}</tbody>
                    </table>
                </div>
            </div>`;
        };

        const preItems = (d.perActivity || []).filter((a) => a.phase === 'preDayZero');
        const cropItems = (d.perActivity || []).filter((a) => a.phase === 'cropping');
        const unaItems = (d.perActivity || []).filter((a) => a.phase === 'unanchored');
        const activitySection = (d.perActivity || []).length ? `
            <details class="mt-3" open>
                <summary class="font-bold text-gray-900 text-sm cursor-pointer">By Activity (${d.perActivity.length}) <span class="font-normal text-gray-400">— grouped by phase</span></summary>
                ${renderSection(preItems, `Land Preparation · ${dt} < 0`, '#a05a00', pre.cost)}
                ${renderSection(cropItems, `Main Cropping · ${dt} 0 onwards`, '#0f6f4d', main.cost)}
                ${renderSection(unaItems, `Unanchored · no ${dt} 0 set`, '#74788d', una.cost)}
            </details>` : '';

        return hero + workerTable + activitySection;
    }

    $id('openLaborBtn')?.addEventListener('click', () => {
        // Reset filters every open so the unfiltered grand total shows first.
        $qsa('#laborGroupsContainer .chip, #laborWorkersContainer .chip').forEach((c) => c.classList.remove('is-selected'));
        ['laborDasMin', 'laborDasMax', 'laborStartDate', 'laborEndDate'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        openSheet('laborSheet');
        reloadLaborSummary();
    });

    $id('laborApplyFiltersBtn')?.addEventListener('click', reloadLaborSummary);
    $id('laborResetFiltersBtn')?.addEventListener('click', () => {
        $qsa('#laborGroupsContainer .chip, #laborWorkersContainer .chip').forEach((c) => c.classList.remove('is-selected'));
        ['laborDasMin', 'laborDasMax', 'laborStartDate', 'laborEndDate'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        reloadLaborSummary();
    });
    $id('laborSelectAllGroups')?.addEventListener('click', () => {
        $qsa('#laborGroupsContainer .chip').forEach((c) => c.classList.add('is-selected'));
        updateLaborFilterHint();
    });
    $id('laborClearGroups')?.addEventListener('click', () => {
        $qsa('#laborGroupsContainer .chip').forEach((c) => c.classList.remove('is-selected'));
        updateLaborFilterHint();
    });
    $id('laborSelectAllWorkers')?.addEventListener('click', () => {
        $qsa('#laborWorkersContainer .chip').forEach((c) => c.classList.add('is-selected'));
        updateLaborFilterHint();
    });
    $id('laborClearWorkers')?.addEventListener('click', () => {
        $qsa('#laborWorkersContainer .chip').forEach((c) => c.classList.remove('is-selected'));
        updateLaborFilterHint();
    });
    $id('laborDasMin')?.addEventListener('input', updateLaborFilterHint);
    $id('laborDasMax')?.addEventListener('input', updateLaborFilterHint);
    $id('laborStartDate')?.addEventListener('change', () => { updateLaborFilterHint(); reloadLaborSummary(); });
    $id('laborEndDate')?.addEventListener('change', () => { updateLaborFilterHint(); reloadLaborSummary(); });

    function buildLaborPlainText(d) {
        if (!d) return '';
        const lines = [];
        const t = d.totals || {};
        const phases = d.phases || {};
        const pre = phases.preDayZero || { count: 0, cost: 0 };
        const main = phases.cropping || { count: 0, cost: 0 };
        const una = phases.unanchored || { count: 0, cost: 0 };
        const dt = d.dayType || 'DAS';
        const generatedAt = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
        lines.push(`LABOR EXPENSE SUMMARY — ${d.scheduleTitle || ''}`);
        lines.push('='.repeat(50));
        lines.push(`Generated: ${generatedAt}`);
        lines.push('');
        lines.push(`TOTAL: ${fmtPeso(d.grandTotal)}`);
        lines.push(`  Land Preparation (${dt} < 0):      ${fmtPeso(pre.cost)}  (${pre.count} ${pre.count === 1 ? 'activity' : 'activities'})`);
        lines.push(`  Main Cropping (${dt} 0 onwards):   ${fmtPeso(main.cost)}  (${main.count} ${main.count === 1 ? 'activity' : 'activities'})`);
        if (una.count > 0) lines.push(`  Unanchored (no ${dt} 0):           ${fmtPeso(una.cost)}  (${una.count} ${una.count === 1 ? 'activity' : 'activities'})`);
        lines.push(`Activities: ${d.totalActivities} · Worker assignments: ${t.totalAssignments || 0}`);
        lines.push(`${t.halfDays || 0} half-day · ${t.wholeDays || 0} whole-day · ${t.naCount || 0} N/A`);
        lines.push('');
        const f = d.filters || {};
        const fParts = [];
        if (f.groupIds && f.groupIds.length) fParts.push(`Groups: ${f.groupIds.length}`);
        else if (f.lotIds && f.lotIds.length) fParts.push(`Lots: ${f.lotIds.length}`);
        if (f.workerIds && f.workerIds.length) fParts.push(`Workers: ${f.workerIds.length}`);
        if (f.dasMin !== null || f.dasMax !== null) {
            fParts.push(`${dt} [${f.dasMin !== null ? f.dasMin : '−∞'}, ${f.dasMax !== null ? f.dasMax : '+∞'}]`);
        }
        if (f.startDate || f.endDate) fParts.push(`Date [${f.startDate || '—'}, ${f.endDate || '—'}]`);
        lines.push(`Filters: ${fParts.length ? fParts.join(' · ') : '(none)'}`);
        lines.push('Formula:  cost = Σ(worker half-day rates) × units/day × days  (units: whole=2, half=1, n/a=0)');
        lines.push('');
        lines.push('BY WORKER');
        lines.push('-'.repeat(64));
        lines.push('Worker'.padEnd(24) + 'Rate'.padStart(10) + 'LandPrep'.padStart(12) + 'Cropping'.padStart(12) + 'Total'.padStart(12));
        (d.perWorker || []).forEach((w) => {
            lines.push(
                `${w.name} #${w.priority}`.slice(0, 22).padEnd(24)
                + fmtPeso(w.costPerHalfDay).padStart(10)
                + fmtPeso(w.preDayZeroTotal || 0).padStart(12)
                + fmtPeso(w.croppingTotal || 0).padStart(12)
                + fmtPeso(w.total).padStart(12)
            );
            if (una.count > 0 && (w.unanchoredTotal || 0) > 0) {
                lines.push(' '.repeat(24) + ('(unanchored: ' + fmtPeso(w.unanchoredTotal) + ')').padStart(40));
            }
        });
        if ((d.perWorker || []).length === 0) lines.push('(none)');
        lines.push('');
        const writeSection = (items, label, subtotal) => {
            if (items.length === 0) return;
            lines.push(`${label}  (subtotal: ${fmtPeso(subtotal)})`);
            lines.push('-'.repeat(50));
            items.forEach((a) => {
                const date = a.targetEndDate && a.targetEndDate !== a.targetDate
                    ? `${a.targetDate} → ${a.targetEndDate} (${a.rangeDays}d)`
                    : (a.targetDate || 'No date');
                const das = (a.das === null || a.das === undefined) ? '—' : (a.das >= 0 ? '+' : '') + a.das;
                const days = a.rangeDays || 1;
                const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
                lines.push(`• ${a.activityTitle}`);
                lines.push(`    ${date}  ${dt}${das}  [${timeRequiredShortLabel(a.timeRequired)}]  ${a.workerCount} worker(s)`);
                lines.push(`    ${fmtPeso(a.workerRateSum || 0)} × ${units} × ${days} = ${fmtPeso(a.cost)}`);
            });
            lines.push('');
        };
        const all = d.perActivity || [];
        writeSection(all.filter((a) => a.phase === 'preDayZero'), `BY ACTIVITY — LAND PREPARATION (${dt} < 0)`, pre.cost);
        writeSection(all.filter((a) => a.phase === 'cropping'), `BY ACTIVITY — MAIN CROPPING (${dt} 0 onwards)`, main.cost);
        writeSection(all.filter((a) => a.phase === 'unanchored'), `BY ACTIVITY — UNANCHORED (no ${dt} 0)`, una.cost);
        if (all.length === 0) lines.push('BY ACTIVITY: (none)');
        return lines.join('\n');
    }

    function buildLaborPrintHtml(d) {
        if (!d) return '<html><body><p>No data.</p></body></html>';
        const t = d.totals || {};
        const filters = d.filters || {};
        const dt = d.dayType || 'DAS';
        const phases = d.phases || {};
        const pre = phases.preDayZero || { count: 0, cost: 0 };
        const main = phases.cropping || { count: 0, cost: 0 };
        const una = phases.unanchored || { count: 0, cost: 0 };
        const showUna = una.count > 0;
        const generatedAt = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });

        const filterChips = [];
        if (filters.groupIds && filters.groupIds.length) filterChips.push(`<span class="f-chip">Groups: ${filters.groupIds.length}</span>`);
        else if (filters.lotIds && filters.lotIds.length) filterChips.push(`<span class="f-chip">Lots: ${filters.lotIds.length}</span>`);
        if (filters.workerIds && filters.workerIds.length) filterChips.push(`<span class="f-chip">Workers: ${filters.workerIds.length}</span>`);
        if (filters.dasMin !== null || filters.dasMax !== null) {
            filterChips.push(`<span class="f-chip">${esc(dt)} [${filters.dasMin !== null ? filters.dasMin : '−∞'}, ${filters.dasMax !== null ? filters.dasMax : '+∞'}]</span>`);
        }
        if (filters.startDate || filters.endDate) filterChips.push(`<span class="f-chip">Date [${esc(filters.startDate || '—')}, ${esc(filters.endDate || '—')}]</span>`);
        const filterStrip = filterChips.length
            ? `<div class="filter-strip">Filters applied: ${filterChips.join(' ')}</div>`
            : '<div class="filter-strip muted">No filters applied — all activities included.</div>';

        const workerRows = (d.perWorker || []).map((w) => `
            <tr>
                <td>${esc(w.name)} <span class="muted">#${w.priority}</span></td>
                <td class="num">${fmtPeso(w.costPerHalfDay)}</td>
                <td class="num">${w.halfDays}</td>
                <td class="num">${w.wholeDays}</td>
                <td class="num">${w.naCount}</td>
                <td class="num phase-pre">${fmtPeso(w.preDayZeroTotal || 0)}</td>
                <td class="num phase-crop">${fmtPeso(w.croppingTotal || 0)}</td>
                ${showUna ? `<td class="num">${fmtPeso(w.unanchoredTotal || 0)}</td>` : ''}
                <td class="num"><strong>${fmtPeso(w.total)}</strong></td>
            </tr>`).join('')
            || `<tr><td colspan="${showUna ? 9 : 8}" class="muted center">No workers contributed to the cost under current filters.</td></tr>`;

        const printRow = (a) => {
            const dateStr = a.targetEndDate && a.targetEndDate !== a.targetDate
                ? `${a.targetDate} → ${a.targetEndDate}`
                : (a.targetDate || 'No date');
            const dasLbl = (a.das === null || a.das === undefined) ? '—' : (a.das >= 0 ? '+' : '') + a.das;
            const days = a.rangeDays || 1;
            const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
            return `<tr>
                <td>${esc(a.activityTitle)}</td>
                <td>${esc(dateStr)}${days > 1 ? ` <span class="muted">(${days}d)</span>` : ''}</td>
                <td class="center">${esc(dt)}${esc(String(dasLbl))}</td>
                <td class="center">${timeRequiredShortLabel(a.timeRequired)}</td>
                <td class="num">${a.workerCount}</td>
                <td class="num muted" style="font-size:8.5pt;">${esc(`${fmtPeso(a.workerRateSum || 0)} × ${units} × ${days}`)}</td>
                <td class="num"><strong>${fmtPeso(a.cost)}</strong></td>
            </tr>`;
        };
        const head = `<thead><tr><th style="width:24%;">Activity</th><th style="width:14%;">Date</th><th class="center" style="width:9%;">${esc(dt)}</th><th class="center" style="width:9%;">Time</th><th class="num" style="width:8%;">Workers</th><th class="num" style="width:20%;">Formula</th><th class="num" style="width:16%;">Cost</th></tr></thead>`;
        const section = (items, label, subtotal, accent) => {
            if (items.length === 0) return '';
            return `<div style="border-left:4px solid ${accent};padding-left:8px;margin-top:14px;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;">
                    <h3 style="margin:0 0 4px;font-size:11pt;color:${accent};">${esc(label)} <span class="muted" style="font-weight:400;">(${items.length})</span></h3>
                    <div style="font-size:10pt;">Subtotal: <strong style="color:${accent};">${fmtPeso(subtotal)}</strong></div>
                </div>
                <table>${head}<tbody>${items.map(printRow).join('')}</tbody></table>
            </div>`;
        };
        const all = d.perActivity || [];
        const phaseSections = all.length === 0
            ? `<table>${head}<tbody><tr><td colspan="7" class="muted center">No activities matched the current filters.</td></tr></tbody></table>`
            : section(all.filter((a) => a.phase === 'preDayZero'), `Land Preparation · ${dt} < 0`, pre.cost, '#a05a00')
              + section(all.filter((a) => a.phase === 'cropping'), `Main Cropping · ${dt} 0 onwards`, main.cost, '#0f6f4d')
              + section(all.filter((a) => a.phase === 'unanchored'), `Unanchored · no ${dt} 0 set`, una.cost, '#74788d');

        return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Labor Summary — ${esc(d.scheduleTitle || '')}</title>
<style>
    @page { size: A4; margin: 18mm 14mm 20mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1a1f2b; font-size: 10.5pt; word-break: break-word; }
    h1 { margin: 0 0 4px; font-size: 18pt; }
    h2 { margin: 18px 0 8px; font-size: 12pt; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #d9dde3; padding-bottom: 4px; }
    .meta { color: #6b7280; font-size: 9.5pt; margin-bottom: 12px; }
    .hero { background: #f1f3f7; border-left: 5px solid #0f8a5f; padding: 14px 18px; border-radius: 4px; margin: 8px 0 14px; }
    .hero .label { font-size: 9pt; color: #6b7280; text-transform: uppercase; }
    .hero .grand { font-size: 22pt; font-weight: 700; color: #0f8a5f; margin-top: 2px; }
    .hero .sub { font-size: 9.5pt; color: #4a5160; margin-top: 4px; }
    .filter-strip { background: #fff8e1; border: 1px solid #ffe0a8; padding: 6px 10px; border-radius: 4px; font-size: 9.5pt; margin-bottom: 12px; }
    .filter-strip.muted { background: #f8f9fa; border-color: #e6e8ec; color: #6b7280; }
    .f-chip { display: inline-block; background: #fff; border: 1px solid #d9dde3; padding: 1px 8px; border-radius: 10px; margin-right: 4px; font-size: 9pt; }
    .phase-row { display: flex; gap: 8px; margin: 8px 0 14px; }
    .phase-card { flex: 1; border-left: 4px solid #ccc; padding: 8px 10px; background: #fafafa; border-radius: 3px; }
    .phase-card .lbl { font-size: 8.5pt; text-transform: uppercase; color: #6b7280; }
    .phase-card .amt { font-size: 14pt; font-weight: 700; margin-top: 2px; }
    .phase-card .sub { font-size: 8.5pt; color: #6b7280; }
    .phase-card.pre { border-color: #a05a00; } .phase-card.pre .amt { color: #a05a00; }
    .phase-card.crop { border-color: #0f6f4d; } .phase-card.crop .amt { color: #0f6f4d; }
    .phase-card.una { border-color: #74788d; } .phase-card.una .amt { color: #74788d; }
    td.phase-pre { background: #fff8e1; color: #a05a00; }
    td.phase-crop { background: #e8f5ee; color: #0f6f4d; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed; }
    th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #ecedf0; word-break: break-word; vertical-align: top; font-size: 10pt; }
    th { color: #6b7280; font-size: 9pt; font-weight: 600; text-transform: uppercase; }
    .num { text-align: right; } .center { text-align: center; } .muted { color: #9aa0a6; }
    footer { margin-top: 18px; font-size: 8.5pt; color: #9aa0a6; text-align: center; border-top: 1px solid #ecedf0; padding-top: 8px; }
</style></head>
<body>
    <h1>Labor Expense Summary</h1>
    <div class="meta">${esc(d.scheduleTitle || '')} · Generated ${esc(generatedAt)}</div>
    <div class="hero">
        <div class="label">Total Labor Expense</div>
        <div class="grand">${fmtPeso(d.grandTotal)}</div>
        <div class="sub">${d.totalActivities} ${d.totalActivities === 1 ? 'activity' : 'activities'} · ${t.totalAssignments || 0} ${t.totalAssignments === 1 ? 'worker assignment' : 'worker assignments'} · ${t.halfDays || 0} half-day · ${t.wholeDays || 0} whole-day · ${t.naCount || 0} N/A</div>
    </div>
    <div class="phase-row">
        <div class="phase-card pre">
            <div class="lbl">Land Preparation (${esc(dt)} &lt; 0)</div>
            <div class="amt">${fmtPeso(pre.cost)}</div>
            <div class="sub">${pre.count} ${pre.count === 1 ? 'activity' : 'activities'} · ${pre.halfDays || 0}H / ${pre.wholeDays || 0}W / ${pre.naCount || 0}N</div>
        </div>
        <div class="phase-card crop">
            <div class="lbl">Main Cropping (${esc(dt)} 0 onwards)</div>
            <div class="amt">${fmtPeso(main.cost)}</div>
            <div class="sub">${main.count} ${main.count === 1 ? 'activity' : 'activities'} · ${main.halfDays || 0}H / ${main.wholeDays || 0}W / ${main.naCount || 0}N</div>
        </div>
        ${showUna ? `<div class="phase-card una">
            <div class="lbl">Unanchored (no ${esc(dt)} 0)</div>
            <div class="amt">${fmtPeso(una.cost)}</div>
            <div class="sub">${una.count} ${una.count === 1 ? 'activity' : 'activities'}</div>
        </div>` : ''}
    </div>
    ${filterStrip}
    <h2>By Worker</h2>
    <table>
        <thead><tr><th style="width:22%;">Worker</th><th class="num" style="width:11%;">Rate</th><th class="num" style="width:7%;">H</th><th class="num" style="width:7%;">W</th><th class="num" style="width:7%;">N</th><th class="num phase-pre" style="width:13%;">Land Prep</th><th class="num phase-crop" style="width:13%;">Cropping</th>${showUna ? '<th class="num" style="width:10%;">Unanch.</th>' : ''}<th class="num" style="width:${showUna ? '10%' : '20%'};">Total</th></tr></thead>
        <tbody>${workerRows}</tbody>
    </table>
    <h2>By Activity</h2>
    ${phaseSections}
    <footer>${esc(d.scheduleTitle || '')} — Labor summary · ${esc(generatedAt)}</footer>
</body></html>`;
    }

    $id('laborCopyBtn')?.addEventListener('click', () => {
        if (!LATEST_LABOR_DATA) {
            toast('Wait for the summary to finish loading.', 'info');
            return;
        }
        copyToClipboard(buildLaborPlainText(LATEST_LABOR_DATA))
            .then(() => toast('Labor summary copied to clipboard.'))
            .catch((err) => toast('Copy failed: ' + (err?.message || err), 'error'));
    });

    $id('laborPrintBtn')?.addEventListener('click', () => {
        if (!LATEST_LABOR_DATA) {
            toast('Wait for the summary to finish loading.', 'info');
            return;
        }
        let iframe = $id('laborPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'laborPrintFrame';
            iframe.style.position = 'fixed';
            iframe.style.left = '-9999px';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.setAttribute('aria-hidden', 'true');
            document.body.appendChild(iframe);
        }
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(buildLaborPrintHtml(LATEST_LABOR_DATA));
        doc.close();
        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (err) {
                toast('Print failed: ' + err.message, 'error');
            }
        }, 150);
    });

    /* ================================================================
     * 14. VERSIONS
     * ================================================================ */

    function activeVersionChip() {
        return $qs('#versionStrip .version-chip[data-is-active="1"]');
    }

    $id('versionStrip')?.addEventListener('click', async (e) => {
        if (e.target.closest('#addVersionBtn')) {
            $id('newVersionName').value = '';
            $id('newVersionDescription').value = '';
            const active = activeVersionChip();
            if (active) $id('newVersionSource').value = active.getAttribute('data-version-id');
            $id('newVersionSetActive').checked = true;
            openSheet('versionSheet');
            setTimeout(() => $id('newVersionName').focus(), 250);
            return;
        }
        const chip = e.target.closest('.version-chip');
        if (!chip) return;
        if (chip.getAttribute('data-is-active') === '1') return;
        const id = chip.getAttribute('data-version-id');
        const name = chip.getAttribute('data-version-name') || 'version';
        const ok = await confirmAction({
            title: 'Switch version?',
            message: `Make "${name}" the active version? The whole timeline (plus exports, presentations and labor) will follow it.`,
            confirmText: 'Switch',
            confirmClass: 'btn-primary',
        });
        if (!ok) return;
        try {
            await api(U.versionSetActive(id), { method: 'POST' });
            toast(`Switched to "${name}". Reloading…`);
            setTimeout(() => location.reload(), 350);
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    $id('saveNewVersionBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const versionName = ($id('newVersionName').value || '').trim();
        if (!versionName) {
            toast('Give the version a name.', 'error');
            $id('newVersionName').focus();
            return;
        }
        const body = {
            versionName,
            description: $id('newVersionDescription').value || '',
            setActive: $id('newVersionSetActive').checked ? 1 : 0,
        };
        const sourceId = $id('newVersionSource').value;
        if (sourceId) body.sourceVersionId = parseInt(sourceId, 10);
        btn.disabled = true;
        try {
            await api(U.versionStore(), { method: 'POST', body });
            toast(`Version "${versionName}" created. Reloading…`);
            closeSheet('versionSheet');
            setTimeout(() => location.reload(), 400);
        } catch (err) {
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    $id('manageVersionBtn')?.addEventListener('click', () => {
        const active = activeVersionChip();
        if (!active) {
            toast('No active version to manage.', 'error');
            return;
        }
        const isOriginal = active.getAttribute('data-is-original') === '1';
        $id('renameVersionId').value = active.getAttribute('data-version-id');
        $id('renameVersionName').value = active.getAttribute('data-version-name') || '';
        $id('renameVersionDescription').value = active.getAttribute('data-version-description') || '';
        $id('deleteVersionZone').classList.toggle('hidden', isOriginal);
        $id('originalVersionHint').classList.toggle('hidden', !isOriginal);
        openSheet('manageVersionSheet');
    });

    $id('saveRenameVersionBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = $id('renameVersionId').value;
        const versionName = ($id('renameVersionName').value || '').trim();
        if (!id || !versionName) {
            toast('Give the version a name.', 'error');
            return;
        }
        btn.disabled = true;
        try {
            await api(U.versionUpdate(id), {
                method: 'PUT',
                body: { versionName, description: $id('renameVersionDescription').value || '' },
            });
            toast('Version updated. Reloading…');
            closeSheet('manageVersionSheet');
            setTimeout(() => location.reload(), 300);
        } catch (err) {
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    $id('deleteVersionBtn')?.addEventListener('click', async () => {
        const active = activeVersionChip();
        if (!active) return;
        if (active.getAttribute('data-is-original') === '1') {
            toast('The Original version is the baseline and cannot be deleted.', 'error');
            return;
        }
        const id = active.getAttribute('data-version-id');
        const name = active.getAttribute('data-version-name') || 'version';
        const ok = await confirmAction({
            title: 'Delete version',
            message: `Delete the entire "${name}" version?`,
            detail: 'Every activity inside this version will be soft-deleted with it. The Original version will become active again. This cannot be undone from the activity-level Undo stack.',
            confirmText: 'Delete Version',
        });
        if (!ok) return;
        try {
            await api(U.versionDelete(id), { method: 'DELETE' });
            toast(`Version "${name}" deleted. Reloading…`);
            closeSheet('manageVersionSheet');
            setTimeout(() => location.reload(), 350);
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    /* ================================================================
     * INIT
     * ================================================================ */

    recomputeLotDayZero();
    rebuildItemPickerOptions();
    refreshUndoBtn();
    refreshItemsEmptyState();
});
</script>
