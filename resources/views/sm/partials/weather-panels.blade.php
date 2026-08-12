{{-- Weather panels — a 6-day tab and an hour-by-hour tab — built into
     whatever host element is handed to window.wxRenderPanels(host, data).

     Shared on purpose: the Weather module and the activities weather sheet are
     the same forecast seen through two doors, and this module has been bitten
     before by one thing rendered twice in two places drifting apart.
     Self-guarded, so including it in both is safe. --}}
<style>
    /* Tabs: the underline slides between them, and each panel fades up as it
       takes over — the house easing, with the reduced-motion guard. */
    .wx-tabs { position: relative; display: flex; gap: .25rem; padding: .25rem;
        background: var(--color-gray-100); border-radius: .9rem; margin-bottom: .75rem; }
    .wx-tab { flex: 1 1 0; position: relative; z-index: 1; padding: .55rem .5rem;
        border-radius: .7rem; font-size: .85rem; font-weight: 800;
        color: var(--color-gray-500); display: inline-flex; align-items: center;
        justify-content: center; gap: .4rem;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .wx-tab.is-on { color: var(--color-brand-800); }
    /* left:0, not left:.25rem. The offset used to be applied twice — once by
       this rule and again by the transform, which measured from the track's
       padding edge — so the second tab's pill hung past the right end of the
       track it is supposed to sit inside. */
    .wx-tab-pill { position: absolute; top: .25rem; bottom: .25rem; left: 0;
        border-radius: .7rem; background: var(--color-white);
        box-shadow: 0 1px 3px rgb(0 0 0 / .12);
        transition: transform .28s cubic-bezier(.22,1,.36,1), width .28s cubic-bezier(.22,1,.36,1); }
    .wx-panel { display: none; }
    .wx-panel.is-on { display: block; animation: wxIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes wxIn { from { opacity: 0; transform: translateY(.5rem); } }
    @media (prefers-reduced-motion: reduce) {
        .wx-tab, .wx-tab-pill { transition: none; }
        .wx-panel.is-on { animation: none; }
    }

    html.dark .wx-tabs { background: #1c2136; }
    html.dark .wx-tab { color: #94a3b8; }
    html.dark .wx-tab.is-on { color: #fff; }
    html.dark .wx-tab-pill { background: #4a7c2a; box-shadow: 0 2px 10px -4px rgb(0 0 0 / .8); }

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
            <div class="wx-day ${d.isToday ? 'is-today' : ''}" title="${esc(d.text)}">
                <div class="wx-day-dow">${esc(d.isToday ? 'Today' : d.dow)}</div>
                <div class="wx-day-emoji">${d.emoji}</div>
                <div class="wx-day-temp">${d.max != null ? d.max + '&deg;' : '&ndash;'}<small>${d.min != null ? '/' + d.min + '&deg;' : ''}</small></div>
                <div class="wx-day-pop">${d.pop != null ? '&#128167;' + d.pop + '%' : '&nbsp;'}</div>
            </div>`).join('') + '</div>';
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
            <div class="mt-3">${dayStrip(loc.days || [])}</div>
            <p class="wx-legend mt-2">&#128167; is the chance of rain that day. Two figures are the day's high and low.</p>
        </div></div>`;
    }

    function hourlyCard(loc, lots) {
        const hours = loc.hours || [];
        if (!hours.length) {
            return `<div class="card mb-3"><div class="card-body">
                <p class="font-bold text-gray-900 text-sm">${esc(loc.place || 'Location')}</p>
                <p class="text-sm text-gray-500 mt-1">No hour-by-hour reading for this location right now.</p>
            </div></div>`;
        }
        const wet = hours.filter((h) => (h.pop || 0) >= 50);
        const nextWet = wet[0];
        const peak = hours.reduce((a, b) => ((b.pop || 0) > (a.pop || 0) ? b : a), hours[0]);
        const now = hours[0];
        const verdict = nextWet
            ? `Rain looks likely from <b>${esc(nextWet.hour)}</b> (${nextWet.pop}% chance). `
              + `Wettest hour is <b>${esc(peak.hour)}</b> at <b>${peak.pop}%</b>. `
              + `${wet.length} of the next ${hours.length} hours are at or above 50%.`
            : `No hour in the next ${hours.length} reaches a 50% chance of rain &mdash; `
              + `the wettest is <b>${esc(peak.hour)}</b> at <b>${peak.pop != null ? peak.pop + '%' : '&mdash;'}</b>. A good window for field work.`;
        return `<div class="card mb-3"><div class="card-body">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-sm">${esc(loc.place || 'Location')}</p>
                    <div class="wx-lotpills">${lotPills(lots)}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-2xl leading-none">${now.emoji}</div>
                    <div class="text-sm font-extrabold text-gray-900 mt-0.5">${now.temp != null ? now.temp + '&deg;' : '&ndash;'}</div>
                    <div class="text-[11px] text-gray-500">now</div>
                </div>
            </div>
            <div class="wx-verdict mt-3"><span class="wx-verdict-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg></span><span class="wx-verdict-text">${verdict}</span></div>
            <div class="wx-hours mt-3">${hours.map((h) => `
                <div class="wx-hour ${h.isNow ? 'is-now' : ''}" title="${esc(h.text)}${h.mm != null ? ' &middot; ' + h.mm + ' mm' : ''}">
                    <div class="wx-hour-time">${esc(h.isNow ? 'Now' : h.hour)}</div>
                    <div class="wx-hour-emoji">${h.emoji}</div>
                    <div class="wx-hour-temp">${h.temp != null ? h.temp + '&deg;' : '&ndash;'}</div>
                    <div class="wx-hour-pop ${(h.pop || 0) < 20 ? 'is-dry' : ''}">&#128167;${h.pop != null ? h.pop + '%' : '&mdash;'}</div>
                </div>`).join('')}</div>
            <p class="wx-legend mt-1">Swipe the hours sideways. &#128167; is the chance of rain in that hour; humidity ${now.humidity != null ? '<b>' + now.humidity + '%</b>' : '&mdash;'}, wind ${now.wind != null ? '<b>' + now.wind + ' km/h</b>' : '&mdash;'} right now.</p>
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
        host.innerHTML = `
            <div class="wx-tabs" role="tablist">
                <span class="wx-tab-pill" aria-hidden="true"></span>
                <button type="button" class="wx-tab is-on" data-wx-tab="general" role="tab" aria-selected="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg>
                    6-day
                </button>
                <button type="button" class="wx-tab" data-wx-tab="hourly" role="tab" aria-selected="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                    Hourly
                </button>
            </div>
            <div class="wx-panel is-on" data-wx-panel="general"></div>
            <div class="wx-panel" data-wx-panel="hourly"></div>`;

        const tabs = [...host.querySelectorAll('.wx-tab')];
        const pill = host.querySelector('.wx-tab-pill');
        const movePill = (btn) => {
            // Measured against the track itself rather than through
            // offsetParent, which is whatever happens to be positioned above
            // this partial — and differs between the module page and the
            // sheet the same panels are shown in.
            const track = btn.parentElement.getBoundingClientRect();
            const r = btn.getBoundingClientRect();
            pill.style.width = r.width + 'px';
            pill.style.transform = 'translateX(' + Math.round(r.left - track.left) + 'px)';
        };
        // The first paint happens while the panel is still being laid out, so
        // the pill is placed again once the browser has finished, and again on
        // resize — a rotated phone is a different track.
        const settle = () => {
            const on = tabs.find((t) => t.classList.contains('is-on'));
            if (on) movePill(on);
        };
        requestAnimationFrame(() => requestAnimationFrame(settle));
        // Belt and braces: if the first frame measured a track that was not
        // laid out yet, the pill would be a zero-width sliver until the first
        // tap. Cheap to check again a moment later than to leave it invisible.
        setTimeout(settle, 60);
        setTimeout(settle, 300);
        window.addEventListener('resize', settle);

        const show = (key) => {
            tabs.forEach((t) => {
                const on = t.dataset.wxTab === key;
                t.classList.toggle('is-on', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                if (on) movePill(t);
            });
            host.querySelectorAll('.wx-panel').forEach((p) => p.classList.toggle('is-on', p.dataset.wxPanel === key));
        };
        tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.wxTab)));
        // The pill can only be placed once the tabs have a width; inside a
        // sheet that is after it has opened, hence the second frame.
        requestAnimationFrame(() => requestAnimationFrame(() => movePill(tabs[0])));

        const locs = (data && data.locations) || {};
        const keys = Object.keys(locs).filter((k) => locs[k] && locs[k].ok);
        const gen = host.querySelector('[data-wx-panel="general"]');
        const hrs = host.querySelector('[data-wx-panel="hourly"]');
        if (!keys.length) { gen.innerHTML = EMPTY; hrs.innerHTML = EMPTY; return; }
        const lotsFor = (key) => ((data && data.lots) || []).filter((l) => l.locationKey === key);
        gen.innerHTML = keys.map((k) => generalCard(locs[k], lotsFor(k))).join('');
        hrs.innerHTML = keys.map((k) => hourlyCard(locs[k], lotsFor(k))).join('');
    };
})();
</script>
