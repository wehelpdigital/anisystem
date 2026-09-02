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
        /* No early return.
         *
         * This file is included twice on the same page: once by the activities
         * board, which wants the move sheet for its day menu, and again by the
         * Inventory module when the board injects it. The old guard let the
         * first copy win, so the injected copy never bound its own Save button
         * and never painted its own shelf. What must happen once is the
         * document-level delegation at the bottom; everything else has to
         * happen every time, because every injection brings new elements. */
        /* ONE MOVE SHEET, NOT TWO.
         *
         * The board renders this sheet for its day menu, and the injected
         * Inventory module renders another copy of the same partial — so the
         * document ends up with two of every element in it. getElementById
         * answers with whichever comes first while openSheet may show the
         * other, and the two disagree: pressing "+ In" wrote the direction to
         * one sheet and read it back from the other, which came out as "out"
         * and recorded a subtraction.
         *
         * The board's copy is the one kept, because it outlives the module —
         * the injected one is torn down every time you leave, and the day
         * menu still needs a sheet to open afterwards. */
        (() => {
            const host = document.getElementById('moduleHost');
            const sheets = [...document.querySelectorAll('#ivMoveSheet')];
            if (sheets.length < 2) return;
            const keep = sheets.find((el) => !host || !host.contains(el)) || sheets[0];
            sheets.forEach((el) => { if (el !== keep) el.remove(); });
        })();

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
        const UNITS = @json(\App\Models\AsInventoryItem::UNITS);

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
        /**
         * A unit as a word — "bags (50 kg)", "kg", "piece".
         *
         * The same list the model holds, so the two cannot drift into saying
         * different things about the same stored value.
         */
        const unitSays = (key, singular) => {
            const u = UNITS[key];
            if (!u) return String(key || '');   // from before this list existed
            const word = singular ? u.one : u.many;
            return u.of ? `${word} (${u.of})` : word;
        };

        /** A quantity said the way the item is counted. */
        const say = (item, qty) => `${trim(qty)} ${unitSays(item.unit, Math.abs(qty) === 1)}`;

        /**
         * Fill a unit dropdown with the units a kind is actually bought in.
         *
         * Which units appear follows from the kind — a fuel is not sold in
         * sachets — and the first is the common answer, so it is already given.
         */
        const fillUnits = (sel, kind, want) => {
            if (!sel) return;
            const keys = KINDS[kind]?.units || Object.keys(UNITS);
            // A unit already chosen stays offered even if the kind moved on,
            // so switching kind to look at the list never silently rewrites
            // what something is counted in.
            if (want && !keys.includes(want) && UNITS[want]) keys.unshift(want);
            sel.innerHTML = keys.map((k) => `<option value="${k}">${esc(unitSays(k, false))}</option>`).join('');
            sel.value = want && keys.includes(want) ? want : keys[0];
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
        function showTab(tab) {
            document.querySelectorAll('.iv-tab').forEach((t) => t.classList.toggle('is-active', t === tab));
            document.querySelectorAll('.iv-pane').forEach((p) => p.classList.toggle('is-active', p.id === tab.dataset.pane));
        }

        /* ---------------- the item sheet ---------------- */
        /* The kind decides which units are on offer. Changing it refills the
           list, keeping whatever was already chosen if that kind still sells
           it — so opening the list to look is never a way to lose an answer. */
        function sayKind() {
            const k = $id('ivKind')?.value;
            const unitSel = $id('ivUnit');
            if (unitSel) fillUnits(unitSel, k, unitSel.dataset.touched ? unitSel.value : null);
            const hint = $id('ivKindHint');
            if (hint) {
                const first = (KINDS[k]?.units || [])[0];
                hint.textContent = first ? `Usually counted in ${unitSays(first, false)}.` : '';
            }
            sayUnit();
        }

        /** The unit, echoed beside the one box on this form that takes a number. */
        function sayUnit() {
            const u = $id('ivLowUnit');
            if (u) u.textContent = unitSays($id('ivUnit')?.value, false);
        }


        function openItemSheet(item = null) {
            $id('ivItemTitle').textContent = item ? 'Edit item' : 'Add an item';
            $id('ivItemId').value = item ? item.id : '';
            $id('ivName').value = item ? item.name : '';
            $id('ivKind').value = item ? item.kind : 'granular';
            $id('ivUnit').dataset.touched = item ? '1' : '';
            fillUnits($id('ivUnit'), item ? item.kind : 'granular', item ? item.unit : null);
            $id('ivLowAt').value = item && item.lowAt ? item.lowAt : '';
            $id('ivNote').value = item && item.note ? item.note : '';
            sayKind();
            openSheet('ivItemSheet');
        }

        async function saveItem(btn) {
            const id = $id('ivItemId').value;
            const name = $id('ivName').value.trim();
            if (!name) { toast('Give the item a name.', 'error'); $id('ivName').focus(); return; }
            const body = {
                name,
                kind: $id('ivKind').value,
                unit: $id('ivUnit').value,
                lowAt: $id('ivLowAt').value || null,
                note: $id('ivNote').value.trim() || null,
            };
            btn.disabled = true;
            try {
                const res = await api(id ? U.update(id) : U.store, { method: id ? 'PUT' : 'POST', body });
                toast(res.message);
                closeSheet('ivItemSheet');
                await load();
            } catch (err) { toast(err.message, 'error'); }
            finally { btn.disabled = false; }
        }

        /* ---------------- the move sheet ---------------- */
        /** Is the sheet being used to invent an item rather than pick one? */
        const movingNew = () => $id('ivMoveItem')?.value === '__new';

        function fillMovePicker(want) {
            const sel = $id('ivMoveItem');
            if (!sel) return;
            const dir = $id('ivMoveDir')?.value || 'out';
            const rows = ITEMS.map((i) => `<option value="${i.id}">${esc(i.icon + ' ' + i.name)}</option>`);
            /* Stock arriving can be stock of something the shed has never held.
               Stock leaving cannot: there is nothing to take it from. */
            if (dir === 'in') rows.unshift('<option value="__new">➕ Something not on the shelf yet</option>');
            sel.innerHTML = rows.length ? rows.join('') : '<option value="">Nothing on the shelf yet</option>';
            // An empty shed answers its own question: the only thing that can
            // happen is a first item.
            sel.value = want ? String(want) : (ITEMS.length || dir !== 'in' ? sel.options[0]?.value ?? '' : '__new');
            sayMoveItem();
        }

        function sayMoveItem() {
            const isNew = movingNew();
            const item = isNew ? null : itemById($id('ivMoveItem')?.value);
            const have = $id('ivMoveHave');
            const unit = $id('ivMoveUnit');

            $id('ivMoveNewWrap')?.classList.toggle('hidden', !isNew);
            if (isNew) fillUnits($id('ivMoveNewUnit'), $id('ivMoveNewKind')?.value, $id('ivMoveNewUnit')?.value);

            const unitKey = isNew ? $id('ivMoveNewUnit')?.value : item?.unit;
            if (unit) unit.textContent = unitKey ? unitSays(unitKey, false) : '';
            if (have) {
                have.textContent = isNew
                    ? 'New to the shed. What you type below becomes its opening count.'
                    : (item
                        ? (item.onHand > 0 ? `${say(item, item.onHand)} on hand.` : 'None on hand.')
                        : 'Nothing on the shelf yet — use Add new inventory first.');
            }
            sayMoveQty();
        }

        function sayMoveQty() {
            const isNew = movingNew();
            const item = isNew ? null : itemById($id('ivMoveItem')?.value);
            const qty = Number($id('ivMoveQty')?.value || 0);
            const after = $id('ivMovePacks');
            const warn = $id('ivMoveWarn');
            const out = $id('ivMoveDir')?.value === 'out';

            /* What the count will BE. "Have I enough" answered before the
               button is pressed rather than after it, and it is the same
               figure the Totals tab will show a second later. */
            if (after) {
                if (isNew && qty > 0) {
                    const u = $id('ivMoveNewUnit')?.value;
                    after.textContent = `It will start at ${trim(qty)} ${unitSays(u, Math.abs(qty) === 1)}.`;
                } else if (item && qty > 0) {
                    const now = item.onHand + (out ? -qty : qty);
                    after.textContent = `After this: ${say(item, now)}.`;
                } else {
                    after.textContent = '';
                }
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
            const newName = $id('ivMoveNewName');
            if (newName) newName.value = '';
            $id('ivMoveDate').value = o.date || new Date().toISOString().slice(0, 10);
            fillMovePicker(o.itemId);
            openSheet('ivMoveSheet');
            setTimeout(() => $id('ivMoveQty')?.focus(), 280);
        };

        async function moveGo(btn) {
            const itemId = $id('ivMoveItem').value;
            const isNew = itemId === '__new';
            const qty = Number($id('ivMoveQty').value || 0);
            const newName = isNew ? $id('ivMoveNewName').value.trim() : '';
            if (!isNew && !itemId) { toast('Nothing on the shelf yet — add a new item first.', 'error'); return; }
            if (isNew && !newName) { toast('What is it?', 'error'); $id('ivMoveNewName').focus(); return; }
            if (!(qty > 0)) { toast('How much?', 'error'); $id('ivMoveQty').focus(); return; }
            btn.disabled = true;
            try {
                /* Two errands, one button. A new item is created WITH what
                   just arrived as its opening count, dated to the day this was
                   opened from — one round trip, and the shed is never left
                   holding a thing it has none of. */
                const res = isNew
                    ? await api(U.store, {
                        method: 'POST',
                        body: {
                            name: newName,
                            kind: $id('ivMoveNewKind').value,
                            unit: $id('ivMoveNewUnit').value,
                            opening: qty,
                            on: $id('ivMoveDate').value || null,
                            openingNote: $id('ivMoveNote').value.trim() || null,
                        },
                    })
                    : await api(U.move, {
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
        }

        /* ---------------- undoing one hand-typed entry ---------------- */
        async function delMove(id) {
            const ok = window.confirmAction ? await window.confirmAction({
                title: 'Remove this entry?',
                message: 'The stock goes back to what it was before this line. The lines after it keep the readings they were written with.',
                confirmText: 'Remove',
            }) : true;
            if (!ok) return;
            try {
                const res = await api(U.moveDelete(id), { method: 'DELETE' });
                toast(res.message);
                await load();
            } catch (err) { toast(err.message, 'error'); }
        }

        /* ---------------- who answers ----------------
         *
         * The newest copy of this script, because it is the one whose elements
         * are on screen. The listeners below are attached to `document` once
         * and read this on every event rather than closing over one run's
         * functions — which is what made the injected copy inert. */
        window.__ivApi = {
            openItemSheet, saveItem, load, itemById, sayKind, sayUnit,
            sayMoveItem, sayMoveQty, moveGo, delMove, showTab, fillUnits,
        };
        window.ivReload = load;

        /* ---------------- bound once, to the document ---------------- */
        if (!window.__ivBound) {
            window.__ivBound = true;

            document.addEventListener('click', (e) => {
                const A = window.__ivApi;
                if (!A) return;
                if (e.target.closest('[data-add-item]')) { A.openItemSheet(); return; }
                const ed = e.target.closest('[data-iv-edit]');
                if (ed) { A.openItemSheet(A.itemById(ed.getAttribute('data-iv-edit'))); return; }
                const inBtn = e.target.closest('[data-iv-in]');
                if (inBtn) { window.ivOpenMove({ direction: 'in', itemId: inBtn.getAttribute('data-iv-in') }); return; }
                const outBtn = e.target.closest('[data-iv-out]');
                if (outBtn) { window.ivOpenMove({ direction: 'out', itemId: outBtn.getAttribute('data-iv-out') }); return; }
                const tab = e.target.closest('.iv-tab');
                if (tab) { A.showTab(tab); return; }
                const del = e.target.closest('[data-iv-move-del]');
                if (del) { A.delMove(del.getAttribute('data-iv-move-del')); return; }
                const save = e.target.closest('#ivSaveItem');
                if (save) { A.saveItem(save); return; }
                const go = e.target.closest('#ivMoveGo');
                if (go) { A.moveGo(go); return; }
            });

            /* change and input bubble, so the same trick works for the
               selects — and a select that is replaced by an injection needs no
               re-attaching. */
            document.addEventListener('change', (e) => {
                const A = window.__ivApi;
                if (!A || !e.target.id) return;
                if (e.target.id === 'ivKind') { A.sayKind(); return; }
                if (e.target.id === 'ivUnit') { e.target.dataset.touched = '1'; A.sayUnit(); return; }
                if (e.target.id === 'ivMoveItem') { A.sayMoveItem(); return; }
                if (e.target.id === 'ivMoveNewKind') {
                    A.fillUnits(document.getElementById('ivMoveNewUnit'), e.target.value);
                    A.sayMoveItem();
                    return;
                }
                if (e.target.id === 'ivMoveNewUnit') { A.sayMoveItem(); return; }
            });

            document.addEventListener('input', (e) => {
                const A = window.__ivApi;
                if (!A) return;
                if (e.target.id === 'ivMoveQty') A.sayMoveQty();
            });
        }

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
