{{-- The inventory, in the browser.

     Two callers: the module page, which draws all three tabs, and the
     activities board, which only wants the move sheet for its two day-menu
     rows. `$standalone` says which — the board passes false and gets the
     sheet's machinery without the module's renderers looking for elements
     that are not on its page.

     One fetch fills both. Guarded against a second include. --}}
@once
<script>
(() => {
    const __ivInit = () => {
        if (window.__ivReady) return;
        window.__ivReady = true;

        const SCHEDULE_ID = {{ $schedule->id }};
        const U = {
            list: `{{ route('sm.inventory.list') }}?id=${SCHEDULE_ID}`,
            store: `{{ route('sm.inventory.store') }}?scheduleId=${SCHEDULE_ID}`,
            update: (id) => `{{ route('sm.inventory.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
            destroy: (id) => `{{ route('sm.inventory.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
            move: `{{ route('sm.inventory.move') }}?scheduleId=${SCHEDULE_ID}`,
            moveDelete: (id) => `{{ route('sm.inventory.move.delete') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        };
        const KINDS = @json(\App\Models\AsInventoryItem::KINDS);

        let ITEMS = [];
        let MOVES = [];
        const $id = (x) => document.getElementById(x);
        const esc = window.escapeHtml || ((s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])));

        /** A number without the noise: 12 rather than 12.000. */
        const trim = (n) => {
            const v = Number(n) || 0;
            return (Math.round(v * 1000) / 1000).toLocaleString(undefined, { maximumFractionDigits: 3 });
        };
        /** A quantity said the way the item is spoken about. */
        const say = (item, qty) => {
            const base = `${trim(qty)} ${item.unit}`;
            if (!item.packSize || !item.packLabel) return base;
            const packs = qty / item.packSize;
            const word = Math.abs(Math.round(packs * 100) / 100) === 1 ? item.packLabel : item.packLabel + 's';
            return `${trim(packs)} ${word} · ${base}`;
        };
        const itemById = (id) => ITEMS.find((i) => String(i.id) === String(id)) || null;

        /* ---------------- fetching ---------------- */
        async function load() {
            const res = await api(U.list, { method: 'GET' });
            ITEMS = res.data?.items || [];
            MOVES = res.data?.moves || [];
            window.IV_ITEMS = ITEMS;
            paintAll();
        }
        window.ivReload = load;

        /* ---------------- the module's three tabs ---------------- */
        function paintAll() {
            paintShelf();
            paintLog();
            paintTotals();
            fillMovePicker();
        }

        function paintShelf() {
            const list = $id('ivList');
            if (!list) return;   // the board includes this without the module
            list.innerHTML = ITEMS.map((i) => `
                <div class="card" data-iv-card="${i.id}">
                    <div class="iv-card">
                        <span class="iv-face">${i.icon}</span>
                        <span class="iv-mid">
                            <span class="iv-name">${esc(i.name)}</span>
                            <span class="iv-kind">${esc(i.kindLabel)}</span>
                            <div class="iv-have ${i.isLow ? 'is-low' : (i.onHand <= 0 ? 'is-none' : '')}">
                                ${i.onHand > 0 ? esc(say(i, i.onHand)) : 'None left'}
                                ${i.isLow && i.onHand > 0 ? '<span class="iv-low">low</span>' : ''}
                            </div>
                            ${i.note ? `<div class="iv-note">${esc(i.note)}</div>` : ''}
                        </span>
                        <span class="iv-acts">
                            <button type="button" class="iv-btn is-in" data-iv-in="${i.id}">+ In</button>
                            <button type="button" class="iv-btn is-out" data-iv-out="${i.id}">− Out</button>
                            <button type="button" class="iv-btn" data-iv-edit="${i.id}">Edit</button>
                        </span>
                    </div>
                </div>`).join('');
            $id('ivEmpty')?.classList.toggle('hidden', ITEMS.length > 0);
            const n = $id('ivCount');
            if (n) n.textContent = ITEMS.length;
            const l = $id('ivCountLabel');
            if (l) l.textContent = ITEMS.length === 1 ? 'item' : 'items';
        }

        function paintLog() {
            const box = $id('ivLog');
            if (!box) return;
            if (!MOVES.length) {
                box.innerHTML = '';
                $id('ivLogEmpty')?.classList.remove('hidden');
                return;
            }
            $id('ivLogEmpty')?.classList.add('hidden');
            // Grouped by the day it happened, so a season reads as a diary
            // rather than as one long undifferentiated list.
            let html = '';
            let lastDay = null;
            MOVES.forEach((m) => {
                if (m.on !== lastDay) {
                    html += `<p class="iv-day-h">${esc(m.onSays || m.on || '')}</p>`;
                    lastDay = m.on;
                }
                html += `<div class="iv-move" data-iv-move="${m.id}">
                    <span class="iv-move-e" title="${esc(m.reasonLabel)}">${m.reasonIcon}</span>
                    <span class="iv-move-t">
                        <span class="iv-move-n">${esc(m.itemName)}</span>
                        <span class="iv-move-s">${esc(m.reasonLabel)} · <b>${esc(trim(m.before))}</b> → <b>${esc(trim(m.after))}</b> ${esc(m.unit)}${m.note ? ' · ' + esc(m.note) : ''}</span>
                    </span>
                    <span class="iv-move-d ${m.isIn ? 'is-in' : 'is-out'}">${m.isIn ? '+' : '−'}${esc(trim(Math.abs(m.delta)))}</span>
                    ${m.reason === 'activity'
                        ? '<span class="iv-move-x" title="This one came from an activity. Untick the activity to take it back.">🔒</span>'
                        : `<button type="button" class="iv-move-x" data-iv-move-del="${m.id}" title="Remove this entry" aria-label="Remove this entry">✕</button>`}
                </div>`;
            });
            box.innerHTML = html;
        }

        function paintTotals() {
            const box = $id('ivTotals');
            if (!box) return;
            box.innerHTML = ITEMS.map((i) => `
                <div class="iv-total">
                    <span class="iv-total-e">${i.icon}</span>
                    <span class="iv-total-n">${esc(i.name)}</span>
                    <span class="iv-total-q ${i.isLow ? 'is-low' : (i.onHand <= 0 ? 'is-none' : '')}">${i.onHand > 0 ? esc(say(i, i.onHand)) : 'none'}</span>
                </div>`).join('');
            $id('ivTotalsEmpty')?.classList.toggle('hidden', ITEMS.length > 0);
        }

        /* ---------------- tabs ---------------- */
        document.querySelectorAll('.iv-tab').forEach((tab) => tab.addEventListener('click', () => {
            document.querySelectorAll('.iv-tab').forEach((t) => t.classList.toggle('is-active', t === tab));
            document.querySelectorAll('.iv-pane').forEach((p) => p.classList.toggle('is-active', p.id === tab.dataset.pane));
        }));

        /* ---------------- the item sheet ---------------- */
        function sayKind() {
            const k = $id('ivKind')?.value;
            const hint = $id('ivKindHint');
            if (!hint) return;
            const units = KINDS[k]?.units || [];
            hint.textContent = units.length ? `Usually counted in ${units.join(' or ')}.` : '';
            // Nudge the unit to something sensible, but never overrule a
            // choice already made — a farm may well count its seed in sacks.
            const unitSel = $id('ivUnit');
            if (unitSel && !unitSel.dataset.touched && units[0]) unitSel.value = units[0];
        }
        $id('ivKind')?.addEventListener('change', sayKind);
        $id('ivUnit')?.addEventListener('change', (e) => { e.currentTarget.dataset.touched = '1'; });

        function sayPack() {
            const size = Number($id('ivPackSize')?.value || 0);
            const label = ($id('ivPackLabel')?.value || '').trim();
            const unit = $id('ivUnit')?.value || 'kg';
            const hint = $id('ivPackHint');
            if (!hint) return;
            hint.textContent = (size > 0 && label)
                ? `One ${label} = ${trim(size)} ${unit}. The shelf will read both.`
                : 'Leave both empty for something bought loose.';
        }
        ['ivPackSize', 'ivPackLabel', 'ivUnit'].forEach((id) => $id(id)?.addEventListener('input', sayPack));

        function openItemSheet(item = null) {
            $id('ivItemTitle').textContent = item ? 'Edit item' : 'Add an item';
            $id('ivItemId').value = item ? item.id : '';
            $id('ivName').value = item ? item.name : '';
            $id('ivKind').value = item ? item.kind : 'granular';
            $id('ivUnit').value = item ? item.unit : 'kg';
            $id('ivUnit').dataset.touched = item ? '1' : '';
            $id('ivPackSize').value = item && item.packSize ? item.packSize : '';
            $id('ivPackLabel').value = item && item.packLabel ? item.packLabel : '';
            $id('ivLowAt').value = item && item.lowAt ? item.lowAt : '';
            $id('ivNote').value = item && item.note ? item.note : '';
            $id('ivOpening').value = '';
            // An opening count belongs to adding a thing, not to editing one:
            // a thing already on the shelf changes by moving, which is what
            // the In and Out buttons are for.
            $id('ivOpeningWrap').classList.toggle('hidden', !!item);
            sayKind();
            sayPack();
            openSheet('ivItemSheet');
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-add-item]')) { openItemSheet(); return; }
            const ed = e.target.closest('[data-iv-edit]');
            if (ed) { openItemSheet(itemById(ed.getAttribute('data-iv-edit'))); return; }
            const inBtn = e.target.closest('[data-iv-in]');
            if (inBtn) { window.ivOpenMove({ direction: 'in', itemId: inBtn.getAttribute('data-iv-in') }); return; }
            const outBtn = e.target.closest('[data-iv-out]');
            if (outBtn) { window.ivOpenMove({ direction: 'out', itemId: outBtn.getAttribute('data-iv-out') }); return; }
        });

        $id('ivSaveItem')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const id = $id('ivItemId').value;
            const name = $id('ivName').value.trim();
            if (!name) { toast('Give the item a name.', 'error'); $id('ivName').focus(); return; }
            const body = {
                name,
                kind: $id('ivKind').value,
                unit: $id('ivUnit').value,
                packSize: $id('ivPackSize').value || null,
                packLabel: $id('ivPackLabel').value.trim() || null,
                lowAt: $id('ivLowAt').value || null,
                note: $id('ivNote').value.trim() || null,
                opening: id ? null : ($id('ivOpening').value || null),
            };
            btn.disabled = true;
            try {
                const res = await api(id ? U.update(id) : U.store, { method: id ? 'PUT' : 'POST', body });
                toast(res.message);
                closeSheet('ivItemSheet');
                await load();
            } catch (err) { toast(err.message, 'error'); }
            finally { btn.disabled = false; }
        });

        /* ---------------- the move sheet ---------------- */
        function fillMovePicker(want) {
            const sel = $id('ivMoveItem');
            if (!sel) return;
            sel.innerHTML = ITEMS.length
                ? ITEMS.map((i) => `<option value="${i.id}">${esc(i.icon + ' ' + i.name)}</option>`).join('')
                : '<option value="">Nothing on the shelf yet</option>';
            if (want) sel.value = String(want);
            sayMoveItem();
        }

        function sayMoveItem() {
            const item = itemById($id('ivMoveItem')?.value);
            const have = $id('ivMoveHave');
            const unit = $id('ivMoveUnit');
            if (unit) unit.textContent = item ? item.unit : '';
            if (have) {
                have.textContent = item
                    ? (item.onHand > 0 ? `${say(item, item.onHand)} on hand.` : 'None on hand.')
                    : 'Add something to the inventory first.';
            }
            sayMoveQty();
        }

        function sayMoveQty() {
            const item = itemById($id('ivMoveItem')?.value);
            const qty = Number($id('ivMoveQty')?.value || 0);
            const packs = $id('ivMovePacks');
            const warn = $id('ivMoveWarn');
            const out = $id('ivMoveDir')?.value === 'out';

            if (packs) {
                packs.textContent = (item && item.packSize && item.packLabel && qty > 0)
                    ? `That is ${trim(qty / item.packSize)} ${item.packLabel}${Math.abs(qty / item.packSize) === 1 ? '' : 's'}.`
                    : '';
            }
            /* Said, not refused. A farm can run into the negative on paper —
               the bag was opened last week and nobody wrote it down — and a
               form that refuses to record what actually happened just gets
               worked around. */
            if (warn) {
                const short = item && out && qty > item.onHand;
                warn.classList.toggle('hidden', !short);
                if (short) {
                    warn.textContent = `That is more than the ${say(item, item.onHand)} on record. `
                        + `It will be saved and the count will go below zero — worth checking the shed, or adding what came in first.`;
                }
            }
        }
        $id('ivMoveItem')?.addEventListener('change', sayMoveItem);
        $id('ivMoveQty')?.addEventListener('input', sayMoveQty);

        /**
         * Open the move sheet.
         *
         * `o` takes direction ('in'|'out'), itemId, date and title — all
         * optional. Written out in words rather than as a type literal
         * because a doubled brace in this file is a Blade echo, not a
         * comment, and the page dies with "Undefined constant".
         */
        window.ivOpenMove = async function ivOpenMove(o = {}) {
            const dir = o.direction === 'in' ? 'in' : 'out';
            // The board opens this without the module ever having loaded, so
            // the shelf is fetched on demand the first time.
            if (!ITEMS.length) {
                try { await load(); } catch (_) { /* said below */ }
            }
            $id('ivMoveDir').value = dir;
            $id('ivMoveTitle').textContent = o.title || (dir === 'in' ? 'Add to the inventory' : 'Expense an inventory item');
            $id('ivMoveGo').textContent = dir === 'in' ? 'Add it' : 'Take it out';
            $id('ivMoveQty').value = '';
            $id('ivMoveNote').value = '';
            $id('ivMoveDate').value = o.date || new Date().toISOString().slice(0, 10);
            fillMovePicker(o.itemId);
            openSheet('ivMoveSheet');
            setTimeout(() => $id('ivMoveQty')?.focus(), 280);
        };

        $id('ivMoveGo')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const itemId = $id('ivMoveItem').value;
            const qty = Number($id('ivMoveQty').value || 0);
            if (!itemId) { toast('Add something to the inventory first.', 'error'); return; }
            if (!(qty > 0)) { toast('How much?', 'error'); $id('ivMoveQty').focus(); return; }
            btn.disabled = true;
            try {
                const res = await api(U.move, {
                    method: 'POST',
                    body: {
                        itemId: Number(itemId),
                        qty,
                        direction: $id('ivMoveDir').value,
                        on: $id('ivMoveDate').value || null,
                        note: $id('ivMoveNote').value.trim() || null,
                    },
                });
                toast(res.message);
                closeSheet('ivMoveSheet');
                await load();
                // The board keeps its own copy of the day's rows.
                window.ivDayChanged?.($id('ivMoveDate').value);
            } catch (err) { toast(err.message, 'error'); }
            finally { btn.disabled = false; }
        });

        /* ---------------- undoing one hand-typed entry ---------------- */
        document.addEventListener('click', async (e) => {
            const x = e.target.closest('[data-iv-move-del]');
            if (!x) return;
            const ok = window.confirmAction ? await window.confirmAction({
                title: 'Remove this entry?',
                message: 'The stock goes back to what it was before this line. The lines after it keep the readings they were written with.',
                confirmText: 'Remove',
            }) : true;
            if (!ok) return;
            try {
                const res = await api(U.moveDelete(x.getAttribute('data-iv-move-del')), { method: 'DELETE' });
                toast(res.message);
                await load();
            } catch (err) { toast(err.message, 'error'); }
        });

        @if ($standalone ?? false)
        load().catch((err) => toast(err.message, 'error'));
        @endif
    };

    // First load: wait for app.js (deferred) to define api/toast/openSheet.
    // SPA injection: the document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __ivInit, { once: true });
    else __ivInit();
})();
</script>
@endonce
