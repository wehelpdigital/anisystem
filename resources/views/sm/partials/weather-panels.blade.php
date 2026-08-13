{{-- Weather panels — six days, and the hours inside whichever day you open —
     built into
     whatever host element is handed to window.wxRenderPanels(host, data).

     Shared on purpose: the Weather module and the activities weather sheet are
     the same forecast seen through two doors, and this module has been bitten
     before by one thing rendered twice in two places drifting apart.
     Self-guarded, so including it in both is safe. --}}
<style>
    /* Tabs: the underline slides between them, and each panel fades up as it
       takes over — the house easing, with the reduced-motion guard. */
        background: var(--color-gray-100); border-radius: .9rem; margin-bottom: .75rem; }
        border-radius: .7rem; font-size: .85rem; font-weight: 800;
        color: var(--color-gray-500); display: inline-flex; align-items: center;
        justify-content: center; gap: .4rem;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    /* left:0, not left:.25rem. The offset used to be applied twice — once by
       this rule and again by the transform, which measured from the track's
       padding edge — so the second tab's pill hung past the right end of the
       track it is supposed to sit inside. */
        border-radius: .7rem; background: var(--color-white);
        box-shadow: 0 1px 3px rgb(0 0 0 / .12);
        transition: transform .28s cubic-bezier(.22,1,.36,1), width .28s cubic-bezier(.22,1,.36,1); }
    .wx-panel { display: none; }
    .wx-panel.is-on { display: block; animation: wxIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes wxIn { from { opacity: 0; transform: translateY(.5rem); } }
    @media (prefers-reduced-motion: reduce) {
        .wx-panel.is-on { animation: none; }
    }


    /* A day you can open. The hours used to be a tab of their own, which
       could only ever answer "what about today" — the question is nearly
       always about a particular day, so the day is the way in. */
    .wx-day { cursor: pointer; }
    .wx-day.is-open { border-color: #4a7c2a; box-shadow: 0 0 0 2px rgb(74 124 42 / .25); }
    .wx-day-caret { display: block; margin: .1rem auto 0; width: .7rem; height: .7rem; color: var(--color-gray-400);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .wx-day.is-open .wx-day-caret { transform: rotate(180deg); color: #4a7c2a; }
    .wx-open { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .wx-open.is-on { grid-template-rows: 1fr; }
    .wx-open-in { overflow: hidden; min-height: 0; }
    .wx-open-hd { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem;
        margin: .75rem 0 .35rem; }
    .wx-open-day { font-size: .82rem; font-weight: 800; color: var(--color-gray-900); }
    .wx-open-hint { font-size: .7rem; color: var(--color-gray-400); }
    html.dark .wx-open-day { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .wx-open, .wx-day-caret { transition: none; }
    }

    /* One day in the 6-day strip. */
    .wx-day { flex: 1 1 0; min-width: 0; text-align: center; padding: .5rem .15rem; border-radius: .7rem;
        transition: background .25s cubic-bezier(.22,1,.36,1); }
    .wx-day:hover { background: var(--color-gray-50); }
    .wx-day.is-today { background: var(--color-brand-50); box-shadow: inset 0 0 0 1px var(--color-brand-200); }
    html.dark .wx-day.is-today { background: rgb(61 104 35 / .3); box-shadow: inset 0 0 0 1px rgb(107 159 61 / .5); }
    html.dark .wx-day:hover { background: rgb(255 255 255 / .04); }
    @media (prefers-reduced-motion: reduce) { .wx-day { transition: none; } }
    .wx-day-dow { font-size: .68rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; }
    .wx-day.is-today .wx-day-dow { color: var(--color-brand-700); }
    .wx-day-emoji { font-size: 1.5rem; line-height: 1.1; margin: .15rem 0; }
    .wx-day-temp { font-size: .8rem; font-weight: 800; color: var(--color-gray-800); }
    .wx-day-temp small { color: var(--color-gray-400); font-weight: 600; }
    .wx-day-pop { font-size: .68rem; font-weight: 700; color: #2563eb; }

    /* One hour in the hourly rail. */
    .wx-hours { display: flex; gap: .45rem; overflow-x: auto; padding: .15rem .1rem .5rem; scrollbar-width: none; }
    .wx-hours::-webkit-scrollbar { display: none; }
    .wx-hour { flex: 0 0 auto; width: 4.6rem; text-align: center; padding: .6rem .3rem;
        border: 1px solid var(--color-gray-200); border-radius: .85rem; background: var(--color-white); }
    .wx-hour.is-now { border-color: var(--color-brand-400); background: var(--color-brand-50); }
    .wx-hour-time { font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .wx-hour.is-now .wx-hour-time { color: var(--color-brand-700); }
    .wx-hour-emoji { font-size: 1.35rem; line-height: 1.2; margin: .2rem 0; }
    .wx-hour-temp { font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .wx-hour-pop { font-size: .7rem; font-weight: 700; color: #2563eb; margin-top: .1rem; }
    .wx-hour-pop.is-dry { color: var(--color-gray-400); }

    /* The plain-language line above each strip. */
    .wx-verdict { display: flex; align-items: flex-start; gap: .6rem; padding: .7rem .8rem;
        border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    /* Outline glyphs like the rest of the app, not emoji: an emoji renders in
       whatever style the device feels like and sat oddly against the UI. */
    .wx-verdict-ico { flex-shrink: 0; width: 1.3rem; height: 1.3rem; color: var(--color-brand-600); }
    .wx-verdict-ico svg { width: 100%; height: 100%; }
    .wx-verdict-text { font-size: .84rem; color: var(--color-gray-700); line-height: 1.45; }
    .wx-verdict-text b { color: var(--color-gray-900); }

    .wx-legend { font-size: .72rem; color: var(--color-gray-500); }
    .wx-lotpills { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .wx-lotpill { font-size: .68rem; font-weight: 700; color: var(--color-gray-600);
        background: var(--color-gray-100); border-radius: 999px; padding: .1rem .5rem; }
</style>
<script>
(() => {
    if (window.wxRenderPanels) return;   // included by both doors; wire once

    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /* Numbers are for the strip; this is for the glance. */
    const rainWord = (pop) => pop == null ? 'no rain figure'
        : pop >= 70 ? 'rain very likely'
        : pop >= 40 ? 'rain possible'
        : pop >= 20 ? 'a slight chance of rain'
        : 'little chance of rain';

    function dayStrip(days) {
        return '<div class="flex gap-1">' + days.map((d) => `
            <button type="button" class="wx-day ${d.isToday ? 'is-today' : ''}" data-wx-day="${esc(d.date || '')}"
                    aria-expanded="false" title="${esc(d.text)} — tap for this day's hours">
                <div class="wx-day-dow">${esc(d.isToday ? 'Today' : d.dow)}</div>
                <div class="wx-day-emoji">${d.emoji}</div>
                <div class="wx-day-temp">${d.max != null ? d.max + '&deg;' : '&ndash;'}<small>${d.min != null ? '/' + d.min + '&deg;' : ''}</small></div>
                <div class="wx-day-pop">${d.pop != null ? '&#128167;' + d.pop + '%' : '&nbsp;'}</div>
                <svg class="wx-day-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>`).join('') + '</div>';
    }

    /* The hours of one day, with the sentence a grower actually wants: when
       is it going to rain, and how hard. */
    function hoursPanel(day, hours) {
        if (!hours || !hours.length) {
            return `<p class="text-sm text-gray-500 mt-2">No hour-by-hour reading for ${esc(day.isToday ? 'today' : day.dow)}.</p>`;
        }
        const wet = hours.filter((h) => (h.pop || 0) >= 50);
        const peak = hours.reduce((a, b) => ((b.pop || 0) > (a.pop || 0) ? b : a), hours[0]);
        const verdict = wet.length
            ? `Rain looks likely from <b>${esc(wet[0].hour)}</b> (${wet[0].pop}%). `
              + `Wettest hour is <b>${esc(peak.hour)}</b> at <b>${peak.pop}%</b>, and ${wet.length} `
              + `${wet.length === 1 ? 'hour is' : 'hours are'} at or above 50%.`
            : `No hour reaches a 50% chance of rain — the wettest is <b>${esc(peak.hour)}</b> at `
              + `<b>${peak.pop != null ? peak.pop + '%' : '&mdash;'}</b>. A good window for field work.`;
        return `
            <div class="wx-open-hd">
                <span class="wx-open-day">${esc(day.isToday ? 'Today' : day.dow)}${day.text ? ' &middot; ' + esc(day.text) : ''}</span>
                <span class="wx-open-hint">${hours.length} ${hours.length === 1 ? 'hour' : 'hours'}</span>
            </div>
            <div class="wx-verdict"><span class="wx-verdict-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg></span><span class="wx-verdict-text">${verdict}</span></div>
            <div class="wx-hours mt-2">${hours.map((h) => `
                <div class="wx-hour ${h.isNow ? 'is-now' : ''}" title="${esc(h.text)}${h.mm != null ? ' &middot; ' + h.mm + ' mm' : ''}">
                    <div class="wx-hour-time">${esc(h.isNow ? 'Now' : h.hour)}</div>
                    <div class="wx-hour-emoji">${h.emoji}</div>
                    <div class="wx-hour-temp">${h.temp != null ? h.temp + '&deg;' : '&ndash;'}</div>
                    <div class="wx-hour-pop ${(h.pop || 0) < 20 ? 'is-dry' : ''}">&#128167;${h.pop != null ? h.pop + '%' : '&mdash;'}</div>
                </div>`).join('')}</div>
            <p class="wx-legend mt-1">Swipe the hours sideways. &#128167; is the chance of rain in that hour.</p>`;
    }

    const lotPills = (lots) => lots.map((l) => `<span class="wx-lotpill">${esc(l.name)}</span>`).join('');

    function generalCard(loc, lots) {
        const today = (loc.days || [])[0];
        const verdict = today
            ? `Today around <b>${esc(loc.place)}</b>: ${esc(String(today.text).toLowerCase())}, `
              + `${today.max != null ? '<b>' + today.max + '&deg;</b> at the warmest' : 'temperature unavailable'}`
              + `${today.min != null ? ', down to <b>' + today.min + '&deg;</b>' : ''}. `
              + `There is a <b>${today.pop != null ? today.pop + '%' : '&mdash;'}</b> chance of rain &mdash; ${rainWord(today.pop)}.`
            : 'No reading for today.';
        return `<div class="card mb-3"><div class="card-body">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-sm">${esc(loc.place || 'Location')}</p>
                    <div class="wx-lotpills">${lotPills(lots)}</div>
                </div>
                <span class="text-3xl leading-none">${today ? today.emoji : '&#9925;'}</span>
            </div>
            <div class="wx-verdict mt-3"><span class="wx-verdict-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg></span><span class="wx-verdict-text">${verdict}</span></div>
            <div class="mt-3" data-wx-strip>${dayStrip(loc.days || [])}</div>
            <div class="wx-open" data-wx-open><div class="wx-open-in" data-wx-open-in></div></div>
            <p class="wx-legend mt-2">&#128167; is the chance of rain that day. Two figures are the day's high and low. Tap a day for its hours.</p>
        </div></div>`;
    }

    const EMPTY = `<div class="card"><div class="card-body text-center py-8">
        <p class="text-sm font-bold text-gray-700">No forecast yet</p>
        <p class="text-sm text-gray-500 mt-1">Give your lots a town and province in the Lots module, and the weather for each one shows up here.</p>
    </div></div>`;

    /**
     * Build the tabs and both panels inside `host` from a /app/sm-weather
     * payload. Everything is scoped to the host, so several instances can sit
     * on one page — the module and the sheet, for instance.
     */
    window.wxRenderPanels = function (host, data) {
        if (!host) return;
        host.innerHTML = '<div class="wx-panel is-on" data-wx-panel="general"></div>';

        const locs = (data && data.locations) || {};
        const keys = Object.keys(locs).filter((k) => locs[k] && locs[k].ok);
        const gen = host.querySelector('[data-wx-panel="general"]');
        if (!keys.length) { gen.innerHTML = EMPTY; return; }
        const lotsFor = (key) => ((data && data.lots) || []).filter((l) => l.locationKey === key);
        gen.innerHTML = keys.map((k) => generalCard(locs[k], lotsFor(k))).join('');

        // Tapping a day opens that day's hours under its own card, and tapping
        // it again closes them. One open day per location, because two rails
        // of 24 hours in one card is not a comparison anyone can read.
        gen.querySelectorAll('.card').forEach((card, i) => {
            const loc = locs[keys[i]];
            const strip = card.querySelector('[data-wx-strip]');
            const fold = card.querySelector('[data-wx-open]');
            const inner = card.querySelector('[data-wx-open-in]');
            if (!strip || !fold) return;
            strip.addEventListener('click', (e) => {
                const btn = e.target.closest('.wx-day');
                if (!btn) return;
                const date = btn.getAttribute('data-wx-day');
                const already = btn.classList.contains('is-open');
                strip.querySelectorAll('.wx-day').forEach((b) => {
                    b.classList.remove('is-open');
                    b.setAttribute('aria-expanded', 'false');
                });
                if (already) { fold.classList.remove('is-on'); return; }
                btn.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                const day = (loc.days || []).find((d) => d.date === date) || {};
                const hours = ((loc.hoursByDay || {})[date]) || [];
                inner.innerHTML = hoursPanel(day, hours);
                fold.classList.add('is-on');
            });
        });
    };
})();
</script>
