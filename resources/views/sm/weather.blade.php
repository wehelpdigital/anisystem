@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Weather — ' . $schedule->title)
@section('page-title', 'Weather')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* Tabs: the underline slides between them, and each panel fades up as
           it takes over — the same easing the rest of the app uses. */
        .wx-tabs { position: relative; display: flex; gap: .25rem; padding: .25rem;
            background: var(--color-gray-100); border-radius: .9rem; }
        .wx-tab { flex: 1 1 0; position: relative; z-index: 1; padding: .55rem .5rem;
            border-radius: .7rem; font-size: .85rem; font-weight: 800;
            color: var(--color-gray-500); display: inline-flex; align-items: center;
            justify-content: center; gap: .4rem;
            transition: color .28s cubic-bezier(.22,1,.36,1); }
        .wx-tab.is-on { color: var(--color-brand-800); }
        .wx-tab-pill { position: absolute; top: .25rem; bottom: .25rem; left: .25rem;
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

        /* A day in the 6-day strip. */
        .wx-day { flex: 1 1 0; min-width: 0; text-align: center; padding: .5rem .15rem;
            border-radius: .7rem; }
        .wx-day.is-today { background: var(--color-brand-50); }
        .wx-day-dow { font-size: .68rem; font-weight: 800; color: var(--color-gray-500);
            text-transform: uppercase; }
        .wx-day.is-today .wx-day-dow { color: var(--color-brand-700); }
        .wx-day-emoji { font-size: 1.5rem; line-height: 1.1; margin: .15rem 0; }
        .wx-day-temp { font-size: .8rem; font-weight: 800; color: var(--color-gray-800); }
        .wx-day-temp small { color: var(--color-gray-400); font-weight: 600; }
        .wx-day-pop { font-size: .68rem; font-weight: 700; color: #2563eb; }

        /* One hour in the hourly rail. */
        .wx-hours { display: flex; gap: .45rem; overflow-x: auto; padding: .15rem .1rem .5rem;
            scrollbar-width: none; }
        .wx-hours::-webkit-scrollbar { display: none; }
        .wx-hour { flex: 0 0 auto; width: 4.6rem; text-align: center; padding: .6rem .3rem;
            border: 1px solid var(--color-gray-200); border-radius: .85rem;
            background: var(--color-white); }
        .wx-hour.is-now { border-color: var(--color-brand-400); background: var(--color-brand-50); }
        .wx-hour-time { font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
        .wx-hour.is-now .wx-hour-time { color: var(--color-brand-700); }
        .wx-hour-emoji { font-size: 1.35rem; line-height: 1.2; margin: .2rem 0; }
        .wx-hour-temp { font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
        .wx-hour-pop { font-size: .7rem; font-weight: 700; color: #2563eb; margin-top: .1rem; }
        .wx-hour-pop.is-dry { color: var(--color-gray-400); }

        /* The plain-language line above the rail. */
        .wx-verdict { display: flex; align-items: flex-start; gap: .6rem; padding: .7rem .8rem;
            border-radius: .9rem; background: var(--color-gray-50);
            border: 1px solid var(--color-gray-200); }
        .wx-verdict-emoji { font-size: 1.4rem; line-height: 1; }
        .wx-verdict-text { font-size: .84rem; color: var(--color-gray-700); line-height: 1.45; }
        .wx-verdict-text b { color: var(--color-gray-900); }

        .wx-legend { font-size: .72rem; color: var(--color-gray-500); }
        .wx-place { font-size: .78rem; color: var(--color-gray-500); }
        .wx-lotpills { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
        .wx-lotpill { font-size: .68rem; font-weight: 700; color: var(--color-gray-600);
            background: var(--color-gray-100); border-radius: 999px; padding: .1rem .5rem; }
    </style>
@endpush

@section('content')
    <div class="mb-3">
        <div class="wx-tabs" id="wxTabs" role="tablist">
            <span class="wx-tab-pill" id="wxTabPill" aria-hidden="true"></span>
            <button type="button" class="wx-tab is-on" data-wx-tab="general" role="tab" aria-selected="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg>
                6-day
            </button>
            <button type="button" class="wx-tab" data-wx-tab="hourly" role="tab" aria-selected="false">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                Hourly
            </button>
        </div>
    </div>

    <div class="wx-panel is-on" data-wx-panel="general" id="wxGeneral">
        <div class="card"><div class="card-body text-center text-sm text-gray-500" id="wxGeneralLoading">Loading the forecast…</div></div>
    </div>
    <div class="wx-panel" data-wx-panel="hourly" id="wxHourly">
        <div class="card"><div class="card-body text-center text-sm text-gray-500">Loading the next 24 hours…</div></div>
    </div>

    <script>
    (() => {
        const URL_WX = @json(route('sm.weather'));
        const SID = @json($schedule->id);
        const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        /* ---- tabs ---- */
        const tabs = [...document.querySelectorAll('.wx-tab')];
        const pill = document.getElementById('wxTabPill');
        function movePill(btn) {
            pill.style.width = btn.offsetWidth + 'px';
            pill.style.transform = 'translateX(' + (btn.offsetLeft - btn.parentElement.offsetLeft) + 'px)';
        }
        function showTab(key) {
            tabs.forEach((t) => {
                const on = t.dataset.wxTab === key;
                t.classList.toggle('is-on', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                if (on) movePill(t);
            });
            document.querySelectorAll('.wx-panel').forEach((p) => p.classList.toggle('is-on', p.dataset.wxPanel === key));
        }
        tabs.forEach((t) => t.addEventListener('click', () => showTab(t.dataset.wxTab)));
        // The pill can only be placed once the tabs have been laid out.
        requestAnimationFrame(() => movePill(tabs[0]));
        window.addEventListener('resize', () => movePill(document.querySelector('.wx-tab.is-on') || tabs[0]));

        /* ---- data ---- */
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
                    <div class="wx-day-temp">${d.max != null ? d.max + '°' : '–'}<small>${d.min != null ? '/' + d.min + '°' : ''}</small></div>
                    <div class="wx-day-pop">${d.pop != null ? '💧' + d.pop + '%' : '&nbsp;'}</div>
                </div>`).join('') + '</div>';
        }

        function generalCard(loc, lots) {
            const today = (loc.days || [])[0];
            const verdict = today
                ? `Today around <b>${esc(loc.place)}</b>: ${esc(today.text.toLowerCase())}, `
                  + `${today.max != null ? '<b>' + today.max + '°</b> at the warmest' : 'temperature unavailable'}`
                  + `${today.min != null ? ', down to <b>' + today.min + '°</b>' : ''}. `
                  + `There is a <b>${today.pop != null ? today.pop + '%' : '—'}</b> chance of rain — ${rainWord(today.pop)}.`
                : 'No reading for today.';
            return `<div class="card mb-3"><div class="card-body">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-sm">${esc(loc.place || 'Location')}</p>
                        <div class="wx-lotpills">${lots.map((l) => `<span class="wx-lotpill">${esc(l.name)}</span>`).join('')}</div>
                    </div>
                    <span class="text-3xl leading-none">${today ? today.emoji : '⛅'}</span>
                </div>
                <div class="wx-verdict mt-3"><span class="wx-verdict-emoji">🌦️</span><span class="wx-verdict-text">${verdict}</span></div>
                <div class="mt-3">${dayStrip(loc.days || [])}</div>
                <p class="wx-legend mt-2">💧 is the chance of rain that day. Two figures are the day's high and low.</p>
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
            const verdict = nextWet
                ? `Rain looks likely from <b>${esc(nextWet.hour)}</b> (${nextWet.pop}% chance). `
                  + `Wettest hour is <b>${esc(peak.hour)}</b> at <b>${peak.pop}%</b>. `
                  + `${wet.length} of the next ${hours.length} hours are at or above 50%.`
                : `No hour in the next ${hours.length} reaches a 50% chance of rain — `
                  + `the wettest is <b>${esc(peak.hour)}</b> at <b>${peak.pop != null ? peak.pop + '%' : '—'}</b>. A good window for field work.`;
            const now = hours[0];
            return `<div class="card mb-3"><div class="card-body">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-sm">${esc(loc.place || 'Location')}</p>
                        <div class="wx-lotpills">${lots.map((l) => `<span class="wx-lotpill">${esc(l.name)}</span>`).join('')}</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-2xl leading-none">${now.emoji}</div>
                        <div class="text-sm font-extrabold text-gray-900 mt-0.5">${now.temp != null ? now.temp + '°' : '–'}</div>
                        <div class="text-[11px] text-gray-500">now</div>
                    </div>
                </div>
                <div class="wx-verdict mt-3"><span class="wx-verdict-emoji">⏱️</span><span class="wx-verdict-text">${verdict}</span></div>
                <div class="wx-hours mt-3">${hours.map((h) => `
                    <div class="wx-hour ${h.isNow ? 'is-now' : ''}" title="${esc(h.text)}${h.mm != null ? ' · ' + h.mm + ' mm' : ''}">
                        <div class="wx-hour-time">${esc(h.isNow ? 'Now' : h.hour)}</div>
                        <div class="wx-hour-emoji">${h.emoji}</div>
                        <div class="wx-hour-temp">${h.temp != null ? h.temp + '°' : '–'}</div>
                        <div class="wx-hour-pop ${(h.pop || 0) < 20 ? 'is-dry' : ''}">💧${h.pop != null ? h.pop + '%' : '—'}</div>
                    </div>`).join('')}</div>
                <p class="wx-legend mt-1">Swipe the hours sideways. 💧 is the chance of rain in that hour; humidity ${now.humidity != null ? '<b>' + now.humidity + '%</b>' : '—'}, wind ${now.wind != null ? '<b>' + now.wind + ' km/h</b>' : '—'} right now.</p>
            </div></div>`;
        }

        const EMPTY = `<div class="card"><div class="card-body text-center py-8">
            <p class="text-sm font-bold text-gray-700">No forecast yet</p>
            <p class="text-sm text-gray-500 mt-1">Give your lots a town and province in the Lots module, and the weather for each one shows up here.</p>
        </div></div>`;

        fetch(`${URL_WX}?scheduleId=${SID}&hourly=1`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((res) => {
                const d = (res && res.data) || {};
                const locs = d.locations || {};
                const keys = Object.keys(locs).filter((k) => locs[k] && locs[k].ok);
                const gen = document.getElementById('wxGeneral');
                const hr = document.getElementById('wxHourly');
                if (!keys.length) { gen.innerHTML = EMPTY; hr.innerHTML = EMPTY; return; }
                const lotsFor = (key) => (d.lots || []).filter((l) => l.locationKey === key);
                gen.innerHTML = keys.map((k) => generalCard(locs[k], lotsFor(k))).join('');
                hr.innerHTML = keys.map((k) => hourlyCard(locs[k], lotsFor(k))).join('');
            })
            .catch(() => {
                const msg = `<div class="card"><div class="card-body text-center text-sm text-gray-500">Could not load the forecast just now.</div></div>`;
                document.getElementById('wxGeneral').innerHTML = msg;
                document.getElementById('wxHourly').innerHTML = msg;
            });
    })();
    </script>
@endsection
