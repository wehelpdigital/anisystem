@extends('layouts.admin')

@section('title', 'Activity')
@section('subtitle', 'What members try to do in the app, and where they stop')

@push('head')
<style>
    /* Same family as the Interests page: ranked lists as bars, people before views. */
    .av-list { display: flex; flex-direction: column; gap: .55rem; }
    .av-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .15rem .6rem; align-items: baseline; }
    .av-name { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .av-num { font-size: .72rem; font-weight: 600; color: var(--color-gray-500); white-space: nowrap; text-align: right; }
    .av-num b { color: var(--color-gray-900); font-weight: 800; }
    .av-track { grid-column: 1 / -1; height: .45rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .av-fill { display: block; height: 100%; width: 0; border-radius: 999px; background: var(--color-brand-500); transition: width .28s cubic-bezier(.22,1,.36,1); }
    .av-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-bottom: .7rem; }
    .av-h p:first-child { font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .av-note { font-size: .72rem; color: var(--color-gray-500); }
    .av-empty { font-size: .8rem; color: var(--color-gray-500); padding: .4rem 0; }
    .av-split { display: grid; gap: .75rem; }
    @media (min-width: 768px) { .av-split { grid-template-columns: 1fr 1fr; } }

    /* A row: what, where, the counts, then the people behind it. */
    .av-row { padding: .65rem 0; border-bottom: 1px solid var(--color-gray-100); }
    .av-row:last-child { border-bottom: 0; }
    .av-top { display: flex; align-items: baseline; gap: .6rem; }
    .av-t { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 700; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .av-m { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; overflow-wrap: anywhere; }
    .av-c { flex: none; font-size: .72rem; color: var(--color-gray-500); text-align: right; white-space: nowrap; }
    .av-c b { color: var(--color-gray-900); font-weight: 800; }
    .av-who { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .av-p { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700;
        background: var(--color-gray-100); color: var(--color-gray-800); text-decoration: none; transition: background .2s ease; }
    .av-p:hover { background: var(--color-brand-100); }
    .av-tag { display: inline-flex; align-items: center; padding: .05rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; }
    .av-tag.is-no { background: #fff1e6; color: #9a3412; }
    .av-tag.is-ok { background: #e7f5dc; color: #2d5016; }
    html.dark .av-tag.is-no { background: rgb(154 52 18 / .28); color: #fdba74; }
    html.dark .av-tag.is-ok { background: rgb(107 159 61 / .25); color: #c9e0ad; }

    /* The first steps of new accounts: one bar per step, and who stopped there. */
    .av-step { display: grid; grid-template-columns: 1.6rem minmax(0, 1fr) auto; gap: .2rem .6rem; align-items: center; padding: .55rem 0; border-bottom: 1px solid var(--color-gray-100); }
    .av-step:last-child { border-bottom: 0; }
    .av-step-i { width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .72rem; font-weight: 800;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    .av-step .av-track { grid-column: 2 / -1; }
    .av-lost { grid-column: 2 / -1; font-size: .72rem; color: var(--color-gray-500); }

    /* The newest lines: a dot that says what kind, the person, the deed. */
    .av-feed li { display: flex; gap: .55rem; align-items: baseline; padding: .4rem 0; border-bottom: 1px solid var(--color-gray-100); font-size: .8rem; }
    .av-feed li:last-child { border-bottom: 0; }
    .av-dot { flex: none; width: .55rem; height: .55rem; border-radius: 999px; background: var(--color-gray-300); transform: translateY(-.05rem); }
    .av-dot.k-did { background: #4a7c2a; } .av-dot.k-refused { background: #ea7a1a; } .av-dot.k-locked { background: #e6b800; }
    .av-dot.k-opened { background: #3b82f6; } .av-dot.k-failed { background: #dc2626; }
    .av-key { display: flex; flex-wrap: wrap; gap: .7rem; font-size: .7rem; color: var(--color-gray-500); margin-top: .6rem; }
    .av-key span { display: inline-flex; align-items: center; gap: .3rem; }
    @media (prefers-reduced-motion: reduce) { .av-fill { transition: none; } }
    html.sm-still .av-fill { transition: none; }
</style>
@endpush

@section('bar')
    <div class="ad-chips" id="avDays" role="group" aria-label="Period">
        <button type="button" class="chip" data-days="7">7 days</button>
        <button type="button" class="chip is-selected" data-days="30">30 days</button>
        <button type="button" class="chip" data-days="90">90 days</button>
    </div>
    <div class="ad-chips" id="avTeam" role="group" aria-label="Our team">
        <button type="button" class="chip is-selected" data-team="0">Farmers only</button>
        <button type="button" class="chip" data-team="1">With our team</button>
    </div>
@endsection

@section('content')
    <div id="avBody">
        <div class="ad-stats">
            @for ($i = 0; $i < 4; $i++)
                <div class="card ad-stat"><div class="ad-skel h-6 w-16 mb-2"></div><div class="ad-skel h-3 w-24"></div></div>
            @endfor
        </div>
        <div class="card p-4 mt-3"><div class="ad-skel w-full h-40"></div></div>
        <div class="av-split mt-3">
            <div class="card p-4"><div class="ad-skel w-full h-40"></div></div>
            <div class="card p-4"><div class="ad-skel w-full h-40"></div></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc;
    const URL_DATA = @json(route('admin.data.activity'));
    const URL_CLIENT = @json(route('admin.clients'));
    const state = { days: 30, team: 0 };
    let seq = 0;
    const plural = (n, one, many) => Number(n).toLocaleString() + ' ' + (n === 1 ? one : (many || one + 's'));
    const people = (n) => plural(n, 'person', 'people');
    const mins = (s) => s < 60 ? 'under a minute' : (s < 3600 ? Math.round(s / 60) + ' min' : (s / 3600).toFixed(1) + ' hours');

    /* The people behind a count: their names, each opening their account. Six shown, the rest behind a tap. */
    const who = (list) => {
        if (!list || !list.length) return '';
        const shown = list.slice(0, 6).map((p) => `<a class="av-p" href="${URL_CLIENT}?client=${p.id}">${esc(p.name)}</a>`).join('');
        const rest = list.slice(6);
        return `<div class="av-who">${shown}${rest.length ? `<button type="button" class="av-p" data-more='${esc(JSON.stringify(rest))}'>+${rest.length} more</button>` : ''}</div>`;
    };
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-more]');
        if (!b) return;
        const rest = JSON.parse(b.getAttribute('data-more'));
        b.insertAdjacentHTML('beforebegin', rest.map((p) => `<a class="av-p" href="${URL_CLIENT}?client=${p.id}">${esc(p.name)}</a>`).join(''));
        b.remove();
    });

    const bars = (items, empty, count) => items && items.length ? '<div class="av-list">' + items.map((x) => `
        <div class="av-item">
            <span class="av-name">${esc(x.label)}</span>
            <span class="av-num">${count(x)}</span>
            <span class="av-track" aria-hidden="true"><span class="av-fill" data-w="${Math.max(2, Math.min(100, x.share || 0))}%"></span></span>
        </div>`).join('') + '</div>' : `<p class="av-empty">${esc(empty)}</p>`;

    const rows = (items, empty, line) => items && items.length ? items.map(line).join('') : `<p class="av-empty">${esc(empty)}</p>`;

    function paint(d) {
        const t = d.totals;
        const stepsCard = d.steps.total ? `
            <div class="card p-4 mt-3">
                <div class="av-h"><p>How far new accounts got</p><p class="av-note">${people(d.steps.total)} signed up since ${esc(d.since)} · under each step, who stopped before it</p></div>
                ${d.steps.list.map((s, i) => `
                    <div class="av-step">
                        <span class="av-step-i">${i + 1}</span>
                        <span class="av-name">${esc(s.label)}</span>
                        <span class="av-num"><b>${s.n}</b> of ${d.steps.total} · ${s.share}%</span>
                        <span class="av-track" aria-hidden="true"><span class="av-fill" data-w="${Math.max(2, s.share)}%"></span></span>
                        ${s.lost.length ? `<span class="av-lost">Stopped before this: ${who(s.lost)}</span>` : ''}
                    </div>`).join('')}
            </div>` : `<div class="card p-4 mt-3"><p class="av-empty">Nobody signed up in this period.</p></div>`;

        $id('avBody').innerHTML = `
            ${d.ready ? '' : `<div class="card p-4 mb-3"><p class="text-sm font-bold text-gray-900">Counting starts with the next update</p><p class="av-note mt-1">Pages and first steps are shown now. What people do and what stops them is kept from Nov 26, 2026.</p></div>`}
            <div class="ad-stats ad-rise">
                <div class="card ad-stat"><b>${t.people.toLocaleString()}</b><span>Members active</span><small>${plural(t.visits, 'visit')} · usually ${esc(mins(t.median))}</small></div>
                <div class="card ad-stat"><b>${t.doers.toLocaleString()}</b><span>Did something</span><small>${plural(t.did, 'thing')} done</small></div>
                <div class="card ad-stat"><b>${t.lockedPeople.toLocaleString()}</b><span>Tapped a locked door</span></div>
                <div class="card ad-stat"><b>${t.stuckPeople.toLocaleString()}</b><span>Got stuck</span><small>${plural(t.stuck, 'time')} the app said no or broke</small></div>
            </div>

            ${stepsCard}

            <div class="card p-4 mt-3">
                <div class="av-h"><p>What they tried to do</p><p class="av-note">Every save, add and ask, by people · the orange tag is how often it did not go through</p></div>
                ${rows(d.tried, 'Nothing done in this period yet.', (x) => `
                    <div class="av-row">
                        <div class="av-top">
                            <span class="av-t">${esc(x.label)} ${x.no ? `<span class="av-tag is-no">${x.no} not through</span>` : ''}</span>
                            <span class="av-c"><b>${people(x.people)}</b><br>${plural(x.times, 'time')}</span>
                        </div>
                        <span class="av-m">${esc(x.area || '')}</span>
                        ${who(x.who)}
                    </div>`)}
            </div>

            <div class="av-split mt-3">
                <div class="card p-4">
                    <div class="av-h"><p>Started, then left</p><p class="av-note">Windows they opened, and how often something was saved after</p></div>
                    ${rows(d.opened, 'No window opened in this period yet.', (x) => `
                        <div class="av-row">
                            <div class="av-top">
                                <span class="av-t">${esc(x.label)} <span class="av-tag ${x.followed ? 'is-ok' : 'is-no'}">saved after ${x.followed} of ${x.times}</span></span>
                                <span class="av-c"><b>${people(x.people)}</b></span>
                            </div>
                            <span class="av-m">${esc(x.area || '')}</span>
                            ${who(x.who)}
                        </div>`)}
                </div>
                <div class="card p-4">
                    <div class="av-h"><p>Locked doors they tapped</p><p class="av-note">What they wanted that their plan does not have</p></div>
                    ${rows(d.locked, 'Nobody tapped a locked door in this period.', (x) => `
                        <div class="av-row">
                            <div class="av-top"><span class="av-t">${esc(x.label)}</span><span class="av-c"><b>${people(x.people)}</b><br>${plural(x.times, 'tap')}</span></div>
                            <span class="av-m">${esc(x.area || '')}</span>
                            ${who(x.who)}
                        </div>`)}
                </div>
            </div>

            <div class="card p-4 mt-3">
                <div class="av-h"><p>Where they got stuck</p><p class="av-note">The app said no, with the reason it gave, or something broke</p></div>
                ${rows(d.stuck, 'Nothing stopped anybody in this period.', (x) => `
                    <div class="av-row">
                        <div class="av-top">
                            <span class="av-t">${esc(x.label)} <span class="av-tag is-no">${x.kind === 'failed' ? 'broke' : 'refused'}</span></span>
                            <span class="av-c"><b>${people(x.people)}</b><br>${plural(x.times, 'time')} · last ${esc(x.last)}</span>
                        </div>
                        <span class="av-m">${esc(x.detail || '')}${x.area ? ' · ' + esc(x.area) : ''}</span>
                        ${who(x.who)}
                    </div>`)}
            </div>

            <div class="av-split mt-3">
                <div class="card p-4">
                    <div class="av-h"><p>What they did, by part of the app</p><p class="av-note">Share of active members</p></div>
                    ${bars(d.areas, 'Nothing done in this period yet.', (x) => `<b>${people(x.people)}</b> · ${plural(x.times, 'time')}`)}
                </div>
                <div class="card p-4">
                    <div class="av-h"><p>Screens they open</p><p class="av-note">Share of members who opened the app</p></div>
                    ${bars(d.screens, 'Nobody opened the app in this period.', (x) => `<b>${people(x.people)}</b> · ${plural(x.views, 'view')}`)}
                </div>
            </div>

            <div class="av-split mt-3">
                <div class="card p-4">
                    <div class="av-h"><p>The last thing before they left</p><p class="av-note">Each person's last page or deed in the period</p></div>
                    ${rows(d.left, 'Nobody to show yet.', (x) => `
                        <div class="av-row">
                            <div class="av-top"><span class="av-t">${esc(x.label)}</span><span class="av-c"><b>${people(x.people)}</b></span></div>
                            ${who(x.who)}
                        </div>`)}
                </div>
                <div class="card p-4">
                    <div class="av-h"><p>Newest</p><p class="av-note">Counted ${esc(d.asOf)} · <button type="button" class="underline" id="avFresh">count again</button></p></div>
                    <ul class="av-feed">${(d.feed || []).map((f) => `
                        <li><span class="av-dot k-${esc(f.kind)}" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1"><a class="font-bold" href="${URL_CLIENT}?client=${f.id}">${esc(f.name)}</a> ${esc(f.label)}${f.detail ? `<span class="av-m">${esc(f.detail)}</span>` : ''}</span>
                            <span class="av-c">${esc(f.when)}</span></li>`).join('') || '<li class="av-empty">Nothing yet.</li>'}</ul>
                    <div class="av-key"><span><i class="av-dot"></i>page</span><span><i class="av-dot k-did"></i>did</span><span><i class="av-dot k-opened"></i>opened</span><span><i class="av-dot k-locked"></i>locked door</span><span><i class="av-dot k-refused"></i>refused</span><span><i class="av-dot k-failed"></i>broke</span></div>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-4">Members only. ${d.team ? 'Our team is counted too.' : `Our team is left out (${plural(d.leftOut, 'account')}: staff, test accounts, and the people working on our farms).`} Never what anybody typed: the deed, the page and the reason the app gave.</p>`;

        requestAnimationFrame(() => requestAnimationFrame(() => {
            $id('avBody').querySelectorAll('.av-fill[data-w]').forEach((b) => { b.style.width = b.getAttribute('data-w'); });
        }));
        window.adminRise?.($id('avBody'));
    }

    async function load(fresh) {
        const mine = ++seq;
        try {
            const qs = new URLSearchParams({ days: state.days, team: state.team });
            if (fresh) qs.set('fresh', '1');
            const res = await api(URL_DATA + '?' + qs.toString(), { method: 'GET' });
            if (mine === seq) paint(res.data);
        } catch (err) {
            if (mine === seq) (window.toast || console.error)(err.message, 'error');
        }
    }

    const pick = (group, attr, key) => $id(group).addEventListener('click', (e) => {
        const b = e.target.closest('[' + attr + ']');
        if (!b || b.classList.contains('is-selected')) return;
        $id(group).querySelectorAll('.chip').forEach((c) => c.classList.toggle('is-selected', c === b));
        state[key] = Number(b.getAttribute(attr));
        $id('avBody').style.opacity = '.55';
        load(false).finally(() => { $id('avBody').style.opacity = ''; });
    });
    pick('avDays', 'data-days', 'days');
    pick('avTeam', 'data-team', 'team');
    document.addEventListener('click', (e) => { if (e.target.closest('#avFresh')) load(true); });
    $id('avBody').style.transition = 'opacity .28s cubic-bezier(.22,1,.36,1)';

    if (window.api) load(false);
    else window.addEventListener('load', () => load(false), { once: true });
})();
</script>
@endpush
