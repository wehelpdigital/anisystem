<script>
/**
 * Calendar view for the activities board.
 *
 * It owns no data. Every render reads the list's own `.activity-card` nodes,
 * which is what keeps it honest: filters, hidden activities, drags, edits and
 * version switches all land here for free, because they all end up changing
 * those same nodes. `activities:rendered` is the signal to redraw.
 */
(() => {
const __init = () => {
    const SCHEDULE_ID = @json($schedule->id);
    const STORE_KEY = 'anisystem-activities-view-' + SCHEDULE_ID;

    const byId = (id) => document.getElementById(id);
    const grid = byId('calGrid');
    const root = byId('calendarRoot');
    const list = byId('activitiesList');
    const toggle = byId('viewToggleBtn');
    if (!grid || !root || !list || !toggle) return;

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const MAX_CHIPS = 3;

    // Local-noon dates throughout: parsing 'YYYY-MM-DD' as UTC would shift the
    // day backwards for anyone east of Greenwich, which is everyone here.
    const parse = (iso) => {
        if (!iso) return null;
        const [y, m, d] = iso.split('-').map(Number);
        return (y && m && d) ? new Date(y, m - 1, d, 12) : null;
    };
    const isoOf = (date) => date.getFullYear() + '-'
        + String(date.getMonth() + 1).padStart(2, '0') + '-'
        + String(date.getDate()).padStart(2, '0');
    const addDays = (date, n) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + n, 12);
    const sameDay = (a, b) => a && b && isoOf(a) === isoOf(b);

    let cursor = null;          // first of the month being shown
    let mode = 'list';

    /* ---------------------------------------------------------------- *
     * Reading the board                                                 *
     * ---------------------------------------------------------------- */

    /** Every card the list is currently showing, keyed by the day it covers. */
    function collectByDate() {
        const showHidden = document.body.classList.contains('show-hidden-activities');
        const byDate = new Map();
        let earliest = null;
        let latest = null;

        list.querySelectorAll('.activity-card[data-id]').forEach((card) => {
            if (card.classList.contains('filter-hidden')) return;
            if (card.classList.contains('is-hidden') && !showHidden) return;

            const start = parse((card.getAttribute('data-target-date') || '').trim());
            if (!start) return;                       // "no date" cards have no home here
            const end = parse((card.getAttribute('data-target-end-date') || '').trim()) || start;

            const entry = {
                id: card.getAttribute('data-id'),
                title: (card.querySelector('.activity-card-title')?.textContent || 'Activity').trim(),
                priority: (card.className.match(/prio-(critical|high|medium|low)/) || [])[1] || 'low',
                isDayZero: card.getAttribute('data-is-day-zero') === '1',
                lots: [...card.querySelectorAll('.activity-card-lots .item-tag[data-lot-name]')]
                    .map((t) => t.getAttribute('data-lot-name')).filter(Boolean),
                startIso: isoOf(start),
            };

            // A multi-day activity appears on every day it covers, with the
            // later days marked so the first one still reads as the start.
            let day = start;
            let guard = 0;
            while (day <= end && guard++ < 400) {
                const key = isoOf(day);
                if (!byDate.has(key)) byDate.set(key, []);
                byDate.get(key).push({ ...entry, isContinuation: key !== entry.startIso });
                if (!earliest || day < earliest) earliest = day;
                if (!latest || day > latest) latest = day;
                day = addDays(day, 1);
            }
        });

        return { byDate, earliest, latest };
    }

    /* ---------------------------------------------------------------- *
     * Rendering                                                         *
     * ---------------------------------------------------------------- */

    function render() {
        const { byDate, earliest, latest } = collectByDate();
        const today = new Date();

        if (!cursor) {
            // Open where the work is: this month if the season covers it,
            // otherwise the first month that has anything.
            const withinSeason = earliest && latest
                && today >= new Date(earliest.getFullYear(), earliest.getMonth(), 1)
                && today <= new Date(latest.getFullYear(), latest.getMonth() + 1, 0);
            const anchor = withinSeason ? today : (earliest || today);
            cursor = new Date(anchor.getFullYear(), anchor.getMonth(), 1, 12);
        }

        const year = cursor.getFullYear();
        const month = cursor.getMonth();
        byId('calMonthLabel').textContent = MONTHS[month] + ' ' + year;

        // The grid always starts on the Sunday on or before the 1st.
        const first = new Date(year, month, 1, 12);
        const gridStart = addDays(first, -first.getDay());

        let html = '';
        let monthCount = 0;

        for (let i = 0; i < 42; i++) {
            const day = addDays(gridStart, i);
            const key = isoOf(day);
            const outside = day.getMonth() !== month;
            const items = byDate.get(key) || [];
            if (!outside) monthCount += items.filter((it) => !it.isContinuation).length;

            // Stop after a whole week of trailing days from the next month.
            if (i >= 35 && outside) break;

            const dayZero = items.some((it) => it.isDayZero && !it.isContinuation);
            const shown = items.slice(0, MAX_CHIPS);
            const extra = items.length - shown.length;

            html += `<button type="button" class="cal-day${outside ? ' is-outside' : ''}`
                + `${sameDay(day, today) ? ' is-today' : ''}${dayZero ? ' is-dayzero' : ''}"`
                + ` data-date="${key}" data-count="${items.length}">`
                + `<span class="cal-daynum">${day.getDate()}</span>`
                + `<span class="cal-dots">`
                + shown.map((it) =>
                    `<span class="cal-chip prio-${escapeHtml(it.priority)}${it.isContinuation ? ' is-continuation' : ''}"`
                    + ` title="${escapeHtml(it.title)}">${escapeHtml(it.title)}</span>`).join('')
                + `</span>`
                + (extra > 0 ? `<span class="cal-more">+${extra}</span>` : '')
                + `</button>`;
        }

        grid.innerHTML = html;

        const total = [...byDate.values()].flat().filter((it) => !it.isContinuation).length;
        byId('calMonthMeta').textContent = monthCount === 0
            ? (total === 0 ? 'Nothing scheduled' : 'Nothing this month')
            : monthCount + (monthCount === 1 ? ' activity' : ' activities') + ' this month';

        byId('calEmpty').classList.toggle('hidden', total === 0 || monthCount > 0);
        byId('calEmptyHint').textContent = total === 0
            ? 'Add an activity, or clear your filters if you have any set.'
            : 'Use the arrows to find the months with work in them.';
    }

    /* ---------------------------------------------------------------- *
     * Switching views                                                   *
     * ---------------------------------------------------------------- */

    function setMode(next, remember = true) {
        mode = next === 'calendar' ? 'calendar' : 'list';
        const isCal = mode === 'calendar';

        // Rest-day markers, date notes and progress markers all live inside
        // #activitiesList, so hiding it takes them with it.
        root.classList.toggle('hidden', !isCal);
        list.classList.toggle('hidden', isCal);

        byId('viewToggleLabel').textContent = isCal ? 'List' : 'Calendar';
        byId('viewIconCalendar').classList.toggle('hidden', isCal);
        byId('viewIconList').classList.toggle('hidden', !isCal);
        toggle.title = isCal ? 'Switch to list view' : 'Switch to calendar view';
        toggle.setAttribute('aria-pressed', isCal ? 'true' : 'false');

        if (remember) {
            try { localStorage.setItem(STORE_KEY, mode); } catch (_) { /* private mode */ }
        }
        if (isCal) render();
    }

    toggle.addEventListener('click', () => setMode(mode === 'calendar' ? 'list' : 'calendar'));

    /** Step months. `cursor` is set by the first render; fall back to now. */
    const stepMonth = (delta) => {
        const from = cursor || new Date();
        cursor = new Date(from.getFullYear(), from.getMonth() + delta, 1, 12);
        render();
    };
    byId('calPrev').addEventListener('click', () => stepMonth(-1));
    byId('calNext').addEventListener('click', () => stepMonth(1));
    byId('calToday').addEventListener('click', () => {
        const now = new Date();
        cursor = new Date(now.getFullYear(), now.getMonth(), 1, 12);
        render();
    });

    /* ---------------------------------------------------------------- *
     * A day                                                             *
     * ---------------------------------------------------------------- */

    let openDayIso = null;

    grid.addEventListener('click', (e) => {
        const cell = e.target.closest('.cal-day');
        if (cell) openDay(cell.dataset.date);
    });

    function openDay(iso) {
        const date = parse(iso);
        if (!date) return;
        openDayIso = iso;

        const { byDate } = collectByDate();
        const items = byDate.get(iso) || [];

        byId('calDayTitle').textContent = date.toLocaleDateString('en-PH',
            { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        byId('calDayMeta').textContent = items.length === 0
            ? 'Nothing scheduled'
            : items.length + (items.length === 1 ? ' activity' : ' activities');

        byId('calDayList').innerHTML = items.length === 0
            ? '<p class="text-sm text-gray-500 text-center py-6">Nothing scheduled on this day.</p>'
            : items.map((it) => `
                <button type="button" class="cal-day-row prio-${escapeHtml(it.priority)} js-cal-open"
                        data-id="${escapeHtml(it.id)}">
                    <span class="cal-day-rail"></span>
                    <span class="min-w-0 grow">
                        <span class="block font-bold text-gray-900 text-sm leading-snug">${escapeHtml(it.title)}</span>
                        ${it.lots.length ? `<span class="block text-xs text-gray-500 mt-0.5">${escapeHtml(it.lots.join(', '))}</span>` : ''}
                        ${it.isContinuation ? '<span class="block text-xs text-gray-400 mt-0.5">Continues from an earlier day</span>' : ''}
                    </span>
                    <svg class="w-4 h-4 text-gray-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');

        openSheet('calDaySheet');
    }

    byId('calDayList').addEventListener('click', (e) => {
        const row = e.target.closest('.js-cal-open');
        if (!row) return;
        closeSheet('calDaySheet');
        // Let the sheet finish closing before the editor opens over it.
        setTimeout(() => window.smOpenActivity?.(row.dataset.id), 240);
    });

    byId('calDayAddBtn').addEventListener('click', () => {
        const iso = openDayIso;
        closeSheet('calDaySheet');
        setTimeout(() => window.smAddActivityOn?.(iso), 240);
    });

    /* ---------------------------------------------------------------- */

    // Any change to the board redraws the month, but only while it is showing.
    document.addEventListener('activities:rendered', () => {
        if (mode === 'calendar') render();
    });

    let stored = null;
    try { stored = localStorage.getItem(STORE_KEY); } catch (_) { /* private mode */ }
    setMode(stored === 'calendar' ? 'calendar' : 'list', false);

    window.smSetActivitiesView = setMode;
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
