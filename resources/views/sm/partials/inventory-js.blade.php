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
        /* ONE OF EACH SHEET, EVER.
         *
         * Two roads to a duplicate. The board and the injected module render
         * the same partial, so the document gets two copies outright. And
         * openSheet() re-parents whatever it opens to <body> — so a sheet
         * opened once ESCAPES its module wrapper and survives the teardown
         * that fresh:true performs, shadowing the next injection's copy.
         *
         * Either way, getElementById answers with one element while the user
         * types into another. That is how "+ In" recorded a subtraction, and
         * how editing an item saved as a brand-new one while Close closed the
         * invisible twin. Every inventory sheet gets the same cure: keep the
         * FIRST copy in document order — the one getElementById will answer
         * with — and remove the rest. All behaviour is delegated, so any
         * single copy is a working copy. */
        ['ivMoveSheet', 'ivItemSheet', 'ivStartSheet', 'ivStartEditSheet', 'ivMenuSheet', 'ivConvSheet'].forEach((sid) => {
            const copies = [...document.querySelectorAll('#' + sid)];
            copies.slice(1).forEach((el) => el.remove());
        });

        const SCHEDULE_ID = {{ $schedule->id }};
        const U = {
            list: `{{ route('sm.inventory.list') }}?id=${SCHEDULE_ID}`,
            store: `{{ route('sm.inventory.store') }}?scheduleId=${SCHEDULE_ID}`,
            update: (id) => `{{ route('sm.inventory.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
            destroy: (id) => `{{ route('sm.inventory.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
            move: `{{ route('sm.inventory.move') }}?scheduleId=${SCHEDULE_ID}`,
            restart: `{{ route('sm.inventory.restart') }}?scheduleId=${SCHEDULE_ID}`,
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

        /* The same arithmetic the server does, mirrored ONLY to say what will
           happen before the button is pressed. The book's copy decides. */
        const convert = (qty, from, to) => {
            if (from === to) return qty;
            const a = UNITS[from], b = UNITS[to];
            if (!a || !b || !a.dim || a.dim !== b.dim) return null;
            return qty * a.factor / b.factor;
        };

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
        /* The season's past: how many activities are already ticked done, and
           where the season begins. The Start question is only asked on a
           board that has a past. */
        let CTX = { done: 0, first: null };

        async function load() {
            const res = await api(U.list, { method: 'GET' });
            ITEMS = res.data?.items || [];
            MOVES = res.data?.moves || [];
            CTX = {
                done: Number(res.data?.doneActivities || 0),
                first: res.data?.firstActivityDate || null,
            };
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

        /** One card's inside — shared by fresh cards and refreshed ones. */
        const cardInner = (i) => `
                    <div class="iv-card">
                        <span class="iv-face">${i.icon}</span>
                        <span class="iv-mid">
                            <span class="iv-name">${esc(i.name)}</span>
                            <span class="iv-kind">${esc(i.kindLabel)}</span>
                            {{-- A negative count is the season saying it used what
                                 nobody recorded receiving — the number IS the message,
                                 and "None left" would hide it. --}}
                            {{-- "None left" is for a book that ran dry. A book
                                 that has not begun says so instead — a fresh
                                 item wearing "None left" reads as an accusation
                                 about stock nobody ever recorded. --}}
                            <div class="iv-have ${i.isLow || i.onHand < 0 ? 'is-low' : (i.onHand === 0 ? 'is-none' : '')}">
                                ${i.onHand !== 0 ? convable(i.onHand, i.unit) : (i.hasMoves ? 'None left' : 'No stock recorded yet')}
                                ${i.isLow && i.onHand > 0 ? '<span class="iv-low">low</span>' : ''}
                            </div>
                            ${i.unitPrice != null ? `<div class="iv-note">\u20b1${trim(i.unitPrice)} per ${esc(unitSays(i.unit, true))}</div>` : ''}
                            ${i.note ? `<div class="iv-note">${esc(i.note)}</div>` : ''}
                        </span>
                        <span class="iv-acts">
                            <button type="button" class="iv-kebab" data-iv-menu="${i.id}" title="Edit, add stock, take stock, delete" aria-label="Actions for ${esc(i.name)}">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
                        </span>
                    </div>`;

        function paintShelf() {
            const list = $id('ivList');
            if (!list) return;   // the board includes this without the module

            /* Surgical, not wholesale. A card that already exists is kept —
               node moved into place, content refreshed — so nothing flickers;
               a new card eases in; a vanished one collapses out; a count that
               changed pops once. innerHTML-the-lot killed every transition. */
            const prev = new Map();
            list.querySelectorAll('[data-iv-card]').forEach((n) => prev.set(n.getAttribute('data-iv-card'), n));
            const frag = document.createDocumentFragment();
            ITEMS.forEach((i) => {
                const id = String(i.id);
                let node = prev.get(id);
                if (node) {
                    prev.delete(id);
                    const was = node.querySelector('.iv-have')?.textContent.trim();
                    node.innerHTML = cardInner(i);
                    const have = node.querySelector('.iv-have');
                    if (was !== undefined && have && was !== have.textContent.trim()) {
                        have.classList.add('iv-pop');
                        have.addEventListener('animationend', () => have.classList.remove('iv-pop'), { once: true });
                    }
                } else {
                    node = document.createElement('div');
                    node.className = 'card';
                    node.setAttribute('data-iv-card', id);
                    node.dataset.animated = '1';
                    node.innerHTML = cardInner(i);
                    window.animateIn?.(node);
                }
                frag.appendChild(node);
            });
            // Gone from the data but still on screen: collapse them out.
            prev.forEach((n) => window.animateOut ? window.animateOut(n, () => n.remove()) : n.remove());
            list.appendChild(frag);
            $id('ivEmpty')?.classList.toggle('hidden', ITEMS.length > 0);
            const n = $id('ivCount');
            if (n) n.textContent = ITEMS.length;
            const l = $id('ivCountLabel');
            if (l) l.textContent = ITEMS.length === 1 ? 'item' : 'items';
        }

        /* Which log lines have already been seen, so the first paint arrives
           quietly and only genuine additions slide in. */
        let LOGSEEN = null;

        function paintLog() {
            const box = $id('ivLog');
            if (!box) return;
            const firstPaint = LOGSEEN === null;
            const seen = LOGSEEN || new Set();
            if (!MOVES.length) {
                box.innerHTML = '';
                LOGSEEN = new Set();
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
                const enter = !firstPaint && !seen.has(m.id) ? ' iv-line-enter' : '';
                html += `<div class="iv-move${enter}" data-iv-move="${m.id}">
                    <span class="iv-move-e" title="${esc(m.reasonLabel)}">${m.reasonIcon}</span>
                    <span class="iv-move-t">
                        <span class="iv-move-n">${esc(m.itemName)}</span>
                        <span class="iv-move-s">${esc(m.reasonLabel)}${m.reason === 'created' ? '' : ` · <b>${esc(trim(m.before))}</b> → <b>${esc(trim(m.after))}</b> ${esc(m.unit)}`}${m.typedSays ? ` · <b>typed as ${esc(m.typedSays)}</b>` : ''}${m.note ? ' · ' + esc(m.note) : ''}</span>
                    </span>
                    ${m.reason === 'created'
                        ? '<span class="iv-move-d" style="color:var(--color-gray-300)">·</span>'
                        : `<span class="iv-move-d ${m.isIn ? 'is-in' : 'is-out'}">${m.isIn ? '+' : '−'}${convable(Math.abs(m.delta), itemById(m.itemId)?.unit || 'piece')}</span>`}
                    ${m.reason === 'activity'
                        ? '<span class="iv-move-x" title="This one came from an activity. Untick the activity to take it back.">🔒</span>'
                        : (m.reason === 'open' || m.reason === 'created')
                            ? `<button type="button" class="iv-move-x" data-iv-start-edit="${m.itemId}" data-qty="${m.reason === 'open' ? Math.abs(m.delta) : 0}" data-on="${esc(m.on || '')}" title="Move the start — change the amount or the day and recalculate" aria-label="Move the start">✏️</button>`
                            : `<button type="button" class="iv-move-x" data-iv-move-del="${m.id}" title="Remove this entry" aria-label="Remove this entry">✕</button>`}
                </div>`;
            });
            box.innerHTML = html;
            LOGSEEN = new Set(MOVES.map((m) => m.id));
        }

        let TOTSEEN = new Map();

        function paintTotals() {
            const box = $id('ivTotals');
            if (!box) return;
            box.innerHTML = ITEMS.map((i) => `
                <div class="iv-total">
                    <span class="iv-total-e">${i.icon}</span>
                    <span class="iv-total-n">${esc(i.name)}</span>
                    <span class="iv-total-q ${i.isLow || i.onHand < 0 ? 'is-low' : (i.onHand === 0 ? 'is-none' : '')}">${i.onHand !== 0 ? convable(i.onHand, i.unit) : (i.hasMoves ? 'none' : 'not counted yet')}</span>
                </div>`).join('');
            $id('ivTotalsEmpty')?.classList.toggle('hidden', ITEMS.length > 0);
            // A figure that moved pops once — the eye is told which one.
            const now = new Map(ITEMS.map((i) => [i.id, i.says]));
            box.querySelectorAll('.iv-total').forEach((row, idx) => {
                const i = ITEMS[idx];
                if (i && TOTSEEN.size && TOTSEEN.has(i.id) && TOTSEEN.get(i.id) !== i.says) {
                    const q = row.querySelector('.iv-total-q');
                    q.classList.add('iv-pop');
                    q.addEventListener('animationend', () => q.classList.remove('iv-pop'), { once: true });
                }
            });
            TOTSEEN = now;
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
            const word = unitSays($id('ivUnit')?.value, false);
            const u = $id('ivLowUnit');
            if (u) u.textContent = word;
            // The price is per ONE of whatever this is counted in.
            const p = $id('ivPriceUnit');
            if (p) p.textContent = '\u20b1 per ' + unitSays($id('ivUnit')?.value, true);
        }


        function openItemSheet(item = null) {
            $id('ivItemTitle').textContent = item ? 'Edit item' : 'Add an item';
            $id('ivItemId').value = item ? item.id : '';
            $id('ivName').value = item ? item.name : '';
            $id('ivKind').value = item ? item.kind : 'granular';
            $id('ivUnit').dataset.touched = item ? '1' : '';
            fillUnits($id('ivUnit'), item ? item.kind : 'granular', item ? item.unit : null);
            $id('ivLowAt').value = item && item.lowAt ? item.lowAt : '';
            $id('ivPrice').value = item && item.unitPrice != null ? item.unitPrice : '';
            $id('ivNote').value = item && item.note ? item.note : '';
            /* Stock moves from here now that the card says only Edit/Delete.
               A brand-new item has no stock to move; the row waits. */
            /* Editing is name, kind and note. The unit belongs to the ledger
               already written in it; the price and the warning were set at
               birth. All three stay filled (and hidden), so saving sends them
               back unchanged. */
            $id('ivUnitRow')?.classList.toggle('hidden', !!item);
            $id('ivPriceWrap')?.classList.toggle('hidden', !!item);
            /* The question belongs to creation, and only to a season with a
               past. An existing item's start is moved from its log line. */
            const askStart = !item && CTX.done > 0;
            $id('ivItemStartWrap')?.classList.toggle('hidden', !askStart);
            if (askStart) { START.item = { mode: 'today', date: null }; sayStart('item'); }
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
                unitPrice: $id('ivPrice').value || null,
                note: $id('ivNote').value.trim() || null,
                // Creating on a board with a past: when the count begins.
                countFrom: (!id && CTX.done > 0) ? startDateOf(START.item) : null,
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

        /* ---------------- an amount, translated ----------------
         *
         * Tap a figure with kin and read the same amount in every unit of
         * its kind. Wrapping is decided here, once: a unit whose kin is only
         * itself gets no underline and no sheet. */
        const convable = (qty, unitKey, extra) => {
            const kin = Object.keys(UNITS).filter((k) => UNITS[k].dim && UNITS[unitKey] && UNITS[k].dim === UNITS[unitKey].dim);
            const inner = `${trim(qty)} ${unitSays(unitKey, Math.abs(qty) === 1)}`;
            if (kin.length < 2) return esc(inner);
            return `<button type="button" class="iv-convable" data-conv-qty="${qty}" data-conv-unit="${esc(unitKey)}" title="See this amount in other units">${esc(inner)}${extra || ''}</button>`;
        };

        function openConv(qty, unitKey) {
            qty = Number(qty);
            $id('ivConvTitle').textContent = `${trim(qty)} ${unitSays(unitKey, Math.abs(qty) === 1)}`;
            const kin = Object.keys(UNITS).filter((k) => k !== unitKey && UNITS[k].dim && UNITS[unitKey] && UNITS[k].dim === UNITS[unitKey].dim);
            $id('ivConvRows').innerHTML = kin.map((k) => {
                const v = convert(qty, unitKey, k);
                return `<div class="iv-conv-row"><b>${esc(trim(v))}</b><span>${esc(unitSays(k, Math.abs(v) === 1))}</span></div>`;
            }).join('');
            openSheet('ivConvSheet');
        }

        /* ---------------- when the book begins ----------------
         *
         * One chooser, two callers: the move sheet's first count and the log's
         * Start line. `startTarget` says who asked; each keeps its own answer,
         * so opening the chooser to look never rewrites the other's choice. */
        /* The LOCAL day, not UTC's. toISOString put a Manila evening on
           yesterday's date — the server counts in Asia/Manila and so must the
           tag that claims to say "Today". */
        const todayISO = () => {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        };
        const START = { move: { mode: 'today', date: null }, edit: { mode: 'date', date: null }, item: { mode: 'today', date: null } };
        let startTarget = 'move';

        const startDateOf = (st) => st.mode === 'date' ? (st.date || todayISO())
            : st.mode === 'beginning' ? (CTX.first || todayISO())
            : todayISO();

        const sayDate = (iso) => {
            const d = new Date(iso + 'T00:00:00');
            return isNaN(d) ? iso : d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        };

        /** Paint one tag from one answer. */
        const START_IDS = {
            move: ['ivStartIcon', 'ivStartNow'],
            edit: ['ivStartEditIcon', 'ivStartEditNow'],
            item: ['ivItemStartIcon', 'ivItemStartNow'],
        };

        function sayStart(which) {
            const st = START[which];
            const icon = $id(START_IDS[which][0]);
            const now = $id(START_IDS[which][1]);
            if (!now) return;
            if (st.mode === 'today') { icon.textContent = '\ud83d\uddd3\ufe0f'; now.textContent = 'Today \u00b7 ' + sayDate(todayISO()); }
            else if (st.mode === 'beginning') { icon.textContent = '\u23ee\ufe0f'; now.textContent = 'The beginning \u00b7 ' + sayDate(CTX.first || todayISO()); }
            else { icon.textContent = '\ud83d\udcc5'; now.textContent = sayDate(st.date || todayISO()); }
            const hint = $id('ivStartHint');
            if (which === 'move' && hint) {
                hint.textContent = 'Activities already ticked done from this day take from the count automatically.';
            }
        }

        function openStartChooser(which) {
            startTarget = which;
            const st = START[which];
            document.querySelectorAll('#ivStartRows .dt-row').forEach((r) => {
                const on = r.getAttribute('data-start') === st.mode;
                r.classList.toggle('is-on', on);
                r.querySelector('.dt-row-tick')?.classList.toggle('hidden', !on);
            });
            const say = $id('ivStartBeginningSays');
            if (say) {
                say.textContent = CTX.first
                    ? `The season's first activity is ${sayDate(CTX.first)}. Everything ticked done that used this item comes off the count.`
                    : 'Everything already ticked done that used this item comes off the count.';
            }
            openSheet('ivStartSheet');
        }

        function chooseStart(row) {
            const mode = row.getAttribute('data-start');
            const st = START[startTarget];
            if (mode === 'date') {
                const input = $id('ivStartDateInput');
                input.value = st.mode === 'date' && st.date ? st.date : todayISO();
                raisePicker(input);
                return; // settled by the input's change event
            }
            st.mode = mode;
            sayStart(startTarget);
            closeSheet('ivStartSheet');
        }

        function pickedStartDate(value) {
            if (!value) return;
            const st = START[startTarget];
            st.mode = 'date';
            st.date = value;
            sayStart(startTarget);
            closeSheet('ivStartSheet');
        }

        /* ---------------- the move sheet ---------------- */
        /**
         * Raise a date input's native picker from the tag that fronts it.
         *
         * showPicker() is the good road but a picky one — some browsers
         * refuse it on an insecure origin even with a genuine tap. The old
         * fallback revealed the raw input over the tag and never hid it
         * again, so one refusal turned the tag back into a plain field for
         * the rest of the session. The reveal now tucks itself back in the
         * moment the choice is made or abandoned.
         */
        function raisePicker(input) {
            if (!input) return;
            try { input.showPicker(); return; } catch (_) { /* the long road */ }
            input.style.opacity = '1';
            input.style.pointerEvents = 'auto';
            const tuck = () => {
                input.style.opacity = '';
                input.style.pointerEvents = '';
                input.removeEventListener('blur', tuck);
                input.removeEventListener('change', tuck);
            };
            input.addEventListener('blur', tuck);
            input.addEventListener('change', tuck);
            input.focus();
        }

        /** The When tag, painted from the input it fronts. */
        function sayMoveDate() {
            const v = $id('ivMoveDate')?.value;
            const now = $id('ivMoveDateNow');
            if (!now) return;
            now.textContent = !v ? 'Today · ' + sayDate(todayISO())
                : (v === todayISO() ? 'Today · ' : '') + sayDate(v);
        }

        /** Is the sheet being used to invent an item rather than pick one? */
        const movingNew = () => $id('ivMoveItem')?.value === '__new';

        /**
         * A FIRST count: a new item, or one whose book has not begun.
         * The Start question is asked then, and only on a board with a past —
         * without ticked activities every answer is the same answer.
         */
        function firstCount() {
            if ($id('ivMoveDir')?.value !== 'in') return false;
            if (movingNew()) return true;
            const item = itemById($id('ivMoveItem')?.value);
            return !!item && !item.hasMoves;
        }

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

            /* The Start question, in place of the plain date, for a first
               count on a board with a past. */
            const asking = firstCount() && CTX.done > 0;
            $id('ivStartWrap')?.classList.toggle('hidden', !asking);
            $id('ivMoveDateWrap')?.classList.toggle('hidden', asking);
            if (asking) sayStart('move');

            /* The amount's own unit: a picker of the item's kin when it has
               any, a plain suffix when it does not. New items type in the
               unit they are being given. */
            const sel = $id('ivMoveUnitSel');
            const kin = (!isNew && item && item.kin && item.kin.length > 1) ? item.kin : null;
            if (sel) {
                sel.classList.toggle('hidden', !kin);
                if (kin) sel.innerHTML = kin.map((k) => `<option value="${k}">${esc(unitSays(k, false))}</option>`).join('');
            }
            const unitKey = isNew ? $id('ivMoveNewUnit')?.value : item?.unit;
            if (unit) {
                unit.textContent = (kin || !unitKey) ? '' : unitSays(unitKey, false);
            }
            const pu = $id('ivMoveNewPriceUnit');
            if (pu && isNew) pu.textContent = '\u20b1 per ' + unitSays($id('ivMoveNewUnit')?.value, true);
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
            const typedUnit = (!isNew && item && !$id('ivMoveUnitSel')?.classList.contains('hidden'))
                ? $id('ivMoveUnitSel').value : (item ? item.unit : null);
            const inItem = (item && qty > 0) ? convert(qty, typedUnit || item.unit, item.unit) : null;
            if (after) {
                if (isNew && qty > 0) {
                    const u = $id('ivMoveNewUnit')?.value;
                    after.textContent = `It will start at ${trim(qty)} ${unitSays(u, Math.abs(qty) === 1)}.`;
                } else if (item && inItem !== null && qty > 0) {
                    const now = item.onHand + (out ? -inItem : inItem);
                    // Say the conversion only when there was one to do.
                    const conv = typedUnit && typedUnit !== item.unit ? `= ${say(item, inItem)}. ` : '';
                    after.textContent = `${conv}After this: ${say(item, now)}.`;
                } else {
                    after.textContent = '';
                }
            }
            /* Said, not refused. A farm can run into the negative on paper —
               the bag was opened last week and nobody wrote it down — and a
               form that refuses to record what actually happened just gets
               worked around. */
            if (warn) {
                const short = item && out && inItem !== null && inItem > item.onHand;
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
            $id('ivMoveDate').value = o.date || todayISO();
            sayMoveDate();
            const newPrice = $id('ivMoveNewPrice');
            if (newPrice) newPrice.value = '';
            // Each opening starts from Today; yesterday's choice belonged to
            // yesterday's item.
            START.move = { mode: 'today', date: null };
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
                /* A first count carries its chosen start day and is written
                   as the Start; the server then charges it with what the
                   season's done activities already used from that day. */
                const asking = firstCount() && CTX.done > 0;
                const when = asking ? startDateOf(START.move) : ($id('ivMoveDate').value || null);
                const res = isNew
                    ? await api(U.store, {
                        method: 'POST',
                        body: {
                            name: newName,
                            kind: $id('ivMoveNewKind').value,
                            unit: $id('ivMoveNewUnit').value,
                            unitPrice: $id('ivMoveNewPrice')?.value || null,
                            opening: qty,
                            on: when,
                            openingNote: $id('ivMoveNote').value.trim() || null,
                        },
                    })
                    : await api(U.move, {
                        method: 'POST',
                        body: {
                            itemId: Number(itemId),
                            qty,
                            // The unit the amount was typed in; the book
                            // converts into its own.
                            unit: !$id('ivMoveUnitSel').classList.contains('hidden')
                                ? $id('ivMoveUnitSel').value : null,
                            direction: $id('ivMoveDir').value,
                            reason: asking ? 'open' : null,
                            on: when,
                            note: $id('ivMoveNote').value.trim() || null,
                        },
                    });
                toast(res.message);
                closeSheet('ivMoveSheet');
                await load();
            } catch (err) { toast(err.message, 'error'); }
            finally { btn.disabled = false; }
        }

        /* ---------------- moving the Start ---------------- */
        function openStartEdit(itemId, qty, on) {
            const item = itemById(itemId);
            if (!item) { toast('That item is gone.', 'error'); return; }
            $id('ivStartEditItem').value = String(item.id);
            $id('ivStartEditWhat').textContent = `${item.icon} ${item.name} — the book currently starts ${on ? 'on ' + sayDate(on) : 'today'} with ${say(item, Number(qty) || 0)}.`;
            $id('ivStartEditQty').value = qty || '';
            $id('ivStartEditUnit').textContent = unitSays(item.unit, false);
            START.edit = { mode: 'date', date: on || todayISO() };
            sayStart('edit');
            openSheet('ivStartEditSheet');
        }

        async function startEditGo(btn) {
            const itemId = $id('ivStartEditItem').value;
            const qty = Number($id('ivStartEditQty').value || 0);
            // Zero is an answer: a book can open with nothing on the shelf,
            // and the season's takes then run it honestly negative.
            if (qty < 0) { toast('Started with how much?', 'error'); $id('ivStartEditQty').focus(); return; }
            btn.disabled = true;
            try {
                const res = await api(U.restart, {
                    method: 'POST',
                    body: { itemId: Number(itemId), qty, on: startDateOf(START.edit) },
                });
                toast(res.message);
                closeSheet('ivStartEditSheet');
                await load();
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
        /** The card's ⋮: one item's four verbs, each with its sentence. */
        function openItemMenu(id) {
            const item = itemById(id);
            if (!item) return;
            $id('ivMenuTitle').textContent = item.icon + ' ' + item.name;
            $id('ivMenuSays').textContent = item.onHand !== 0
                ? say(item, item.onHand) + ' on hand'
                : (item.hasMoves ? 'None on hand' : 'No stock recorded yet');
            $id('ivMenuSheet').dataset.item = String(item.id);
            openSheet('ivMenuSheet');
        }

        function itemMenuAct(act) {
            const id = $id('ivMenuSheet').dataset.item;
            closeSheet('ivMenuSheet');
            if (act === 'edit') { openItemSheet(itemById(id)); return; }
            if (act === 'in') { window.ivOpenMove({ direction: 'in', itemId: id }); return; }
            if (act === 'out') { window.ivOpenMove({ direction: 'out', itemId: id }); return; }
            if (act === 'delete') delItem(id);
        }

        /** Take an item off the shed's list. Its log lines stay: history
            does not thin out because somebody stopped stocking a thing. */
        async function delItem(id) {
            const item = itemById(id);
            const ok = window.confirmAction ? await window.confirmAction({
                title: 'Delete ' + (item ? item.name : 'this item') + '?',
                message: 'It comes off the shed\u2019s list. Its log lines stay, and activities that used it keep their record.',
                confirmText: 'Delete',
            }) : true;
            if (!ok) return;
            /* The card leaves NOW — a delete that waits for the server reads
               as a delete that did not take. If the server disagrees, load()
               below repaints the truth and the card returns. */
            const card = document.querySelector(`[data-iv-card="${id}"]`);
            if (card) window.animateOut ? window.animateOut(card, () => card.remove()) : card.remove();
            try {
                const res = await api(U.destroy(id), { method: 'DELETE' });
                toast(res.message);
                await load();
            } catch (err) { toast(err.message, 'error'); await load(); }
        }

        window.__ivApi = {
            openItemSheet, saveItem, load, itemById, sayKind, sayUnit, delItem,
            openItemMenu, itemMenuAct, sayMoveDate, raisePicker, openConv,
            sayMoveItem, sayMoveQty, moveGo, delMove, showTab, fillUnits,
            openStartChooser, chooseStart, pickedStartDate, openStartEdit, startEditGo,
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
                const conv = e.target.closest('[data-conv-qty]');
                if (conv) { A.openConv(conv.getAttribute('data-conv-qty'), conv.getAttribute('data-conv-unit')); return; }
                const menuB = e.target.closest('[data-iv-menu]');
                if (menuB) { A.openItemMenu(menuB.getAttribute('data-iv-menu')); return; }
                const act = e.target.closest('[data-iv-menu-act]');
                if (act) { A.itemMenuAct(act.getAttribute('data-iv-menu-act')); return; }
                const tab = e.target.closest('.iv-tab');
                if (tab) { A.showTab(tab); return; }
                const del = e.target.closest('[data-iv-move-del]');
                if (del) { A.delMove(del.getAttribute('data-iv-move-del')); return; }
                const se = e.target.closest('[data-iv-start-edit]');
                if (se) { A.openStartEdit(se.getAttribute('data-iv-start-edit'), se.getAttribute('data-qty'), se.getAttribute('data-on')); return; }
                if (e.target.closest('#ivStartBtn')) { A.openStartChooser('move'); return; }
                if (e.target.closest('#ivStartEditBtn')) { A.openStartChooser('edit'); return; }
                if (e.target.closest('#ivItemStartBtn')) { A.openStartChooser('item'); return; }
                if (e.target.closest('#ivMoveDateBtn')) { A.raisePicker(document.getElementById('ivMoveDate')); return; }
                const srow = e.target.closest('#ivStartRows .dt-row');
                if (srow) { A.chooseStart(srow); return; }
                const sgo = e.target.closest('#ivStartEditGo');
                if (sgo) { A.startEditGo(sgo); return; }
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
                if (e.target.id === 'ivMoveUnitSel') { A.sayMoveQty(); return; }
                if (e.target.id === 'ivStartDateInput') { A.pickedStartDate(e.target.value); return; }
                if (e.target.id === 'ivMoveDate') { A.sayMoveDate(); return; }
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
