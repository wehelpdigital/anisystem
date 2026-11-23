@extends('layouts.admin')

@section('title', 'Interests')
@section('subtitle', 'What people read and use, and the crops they care about')

@push('head')
<style>
    /* A ranked list as bars: the name, a bar for the share of people, and the counts. Divs, like the dashboard's
       months: the question is "which is bigger", and a bar answers it in no kilobytes. */
    .ir-list { display: flex; flex-direction: column; gap: .55rem; }
    .ir-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .15rem .6rem; align-items: baseline; }
    .ir-name { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); min-width: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ir-num { font-size: .72rem; font-weight: 600; color: var(--color-gray-500); white-space: nowrap; text-align: right; }
    .ir-num b { color: var(--color-gray-900); font-weight: 800; }
    .ir-track { grid-column: 1 / -1; height: .45rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ir-fill { display: block; height: 100%; width: 0; border-radius: 999px; background: var(--color-brand-500);
        transition: width .28s cubic-bezier(.22,1,.36,1); }
    .ir-card-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-bottom: .7rem; }
    .ir-card-h p:first-child { font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .ir-note { font-size: .72rem; color: var(--color-gray-500); }
    .ir-empty { font-size: .8rem; color: var(--color-gray-500); padding: .4rem 0; }
    /* A page in a ranked list: its title (a link when it can be opened), then where it lives. */
    .ir-page { display: flex; align-items: center; gap: .7rem; padding: .6rem 0; border-bottom: 1px solid var(--color-gray-100); }
    .ir-page:last-child { border-bottom: 0; }
    .ir-rank { flex: none; width: 1.5rem; text-align: center; font-size: .72rem; font-weight: 800; color: var(--color-gray-400); }
    .ir-page a.ir-t { color: var(--color-gray-900); text-decoration: underline; text-decoration-color: var(--color-gray-300); text-underline-offset: 2px; }
    .ir-page a.ir-t:hover { text-decoration-color: var(--color-brand-600); }
    .ir-t { display: block; font-size: .83rem; font-weight: 700; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .ir-m { display: block; font-size: .7rem; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .ir-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border-radius: 999px;
        font-size: .78rem; font-weight: 700; background: var(--color-gray-100); color: var(--color-gray-800); }
    .ir-chip i { font-style: normal; font-weight: 600; color: var(--color-gray-500); font-size: .7rem; }
    .ir-split { display: grid; gap: .75rem; }
    @media (min-width: 768px) { .ir-split { grid-template-columns: 1fr 1fr; } }
    /* The trend: many thin bars share the card, so only some carry a label under them. */
    #irTrend .ch-lbl.is-quiet { visibility: hidden; }
    @media (prefers-reduced-motion: reduce) { .ir-fill { transition: none; } }
    html.sm-still .ir-fill { transition: none; }
</style>
@endpush

{{-- The period and the audience ride in the sticky header. --}}
@section('bar')
    <div class="ad-chips" id="irDays" role="group" aria-label="Period">
        <button type="button" class="chip" data-days="7">7 days</button>
        <button type="button" class="chip is-selected" data-days="30">30 days</button>
        <button type="button" class="chip" data-days="90">90 days</button>
        <button type="button" class="chip" data-days="365">12 months</button>
    </div>
    <div class="ad-chips" id="irWho" role="group" aria-label="Who">
        <button type="button" class="chip is-selected" data-who="all">Everyone</button>
        <button type="button" class="chip" data-who="members">Members</button>
        <button type="button" class="chip" data-who="guests">Guests</button>
    </div>
@endsection

@section('content')
    <div id="irBody">
        <div class="ad-stats">
            @for ($i = 0; $i < 4; $i++)
                <div class="card ad-stat"><div class="ad-skel h-6 w-16 mb-2"></div><div class="ad-skel h-3 w-24"></div></div>
            @endfor
        </div>
        <div class="card p-4 mt-3"><div class="ad-skel w-full h-32"></div></div>
        <div class="ir-split mt-3">
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
    const URL_DATA = @json(route('admin.data.interests'));
    const URL_CLIENT = @json(route('admin.clients'));
    const state = { days: 30, who: 'all' };
    let seq = 0;

    const plural = (n, one, many) => n.toLocaleString() + ' ' + (n === 1 ? one : (many || one + 's'));

    /* A ranked list as bars. The bar is the share of people; the words say both counts. */
    function barList(items, opts = {}) {
        if (!items || !items.length) return `<p class="ir-empty">${esc(opts.empty || 'Nothing counted here yet.')}</p>`;
        return '<div class="ir-list">' + items.map((x) => `
            <div class="ir-item" title="${esc(x.label)}: ${x.people} ${x.people === 1 ? 'person' : 'people'}${x.views !== undefined ? ', ' + x.views + ' page views' : ''}">
                <span class="ir-name">${esc(x.label)}</span>
                <span class="ir-num"><b>${plural(x.people, 'person', 'people')}</b>${x.views !== undefined ? ' · ' + plural(x.views, 'view') : ''}</span>
                <span class="ir-track" aria-hidden="true"><span class="ir-fill" data-w="${Math.max(2, Math.min(100, x.share || 0))}%"></span></span>
            </div>`).join('') + '</div>';
    }

    /* A ranked list of pages. */
    function pageList(items, empty, countOf) {
        if (!items || !items.length) return `<p class="ir-empty">${esc(empty)}</p>`;
        return items.map((p, i) => `
            <div class="ir-page">
                <span class="ir-rank">${i + 1}</span>
                <span class="min-w-0 flex-1">
                    ${p.link ? `<a class="ir-t" href="${esc(p.link)}" target="_blank" rel="noopener">${esc(p.title)}</a>` : `<span class="ir-t">${esc(p.title)}</span>`}
                    <span class="ir-m">${esc(p.what)} · ${esc(p.path)}</span>
                </span>
                <span class="ir-num">${countOf(p)}</span>
            </div>`).join('');
    }

    /* The days, weeks or months of the period, the dashboard's bars. Thirty thin bars cannot all carry a label. */
    function trend(el, rows, step) {
        const max = Math.max(1, ...rows.map((r) => r.views));
        // About eight labels on a wide screen, five on a phone, evenly spaced.
        const every = rows.length > 16 ? Math.ceil(rows.length / (el.clientWidth < 600 ? 5 : 8)) : (el.clientWidth < 400 && rows.length > 8 ? 2 : 1);
        const unit = step === 'day' ? 'day' : (step === 'week' ? 'week of' : 'month');
        el.innerHTML = rows.map((r, i) => `
            <div class="ch-col" title="${esc((step === 'week' ? 'Week of ' : '') + r.label)}: ${r.views} page views by ${r.people} ${r.people === 1 ? 'person' : 'people'}">
                <div class="ch-bar" style="height:2px" data-h="${Math.max(2, Math.round((r.views / max) * 100))}%">
                    ${rows.length <= 16 && r.views > 0 ? `<i>${r.views}</i>` : ''}
                </div>
                <span class="ch-lbl ${i % every === 0 ? '' : 'is-quiet'}">${esc(r.label)}</span>
            </div>`).join('');
        el.setAttribute('aria-label', `Page views per ${unit}`);
    }

    function grow(root) {
        requestAnimationFrame(() => requestAnimationFrame(() => {
            root.querySelectorAll('.ch-bar[data-h]').forEach((b) => { b.style.height = b.getAttribute('data-h'); });
            root.querySelectorAll('.ir-fill[data-w]').forEach((b) => { b.style.width = b.getAttribute('data-w'); });
        }));
    }

    function paint(d) {
        const t = d.totals;
        const whoWords = { all: 'everyone', members: 'members', guests: 'guests' }[d.who];
        const stepWords = { day: 'per day', week: 'per week', month: 'per month' }[d.step];
        if (!t.views) {
            $id('irBody').innerHTML = `<div class="card p-6 text-center ad-rise">
                <p class="font-bold text-gray-900">Nothing counted for ${esc(whoWords)} in this period yet</p>
                <p class="text-sm text-gray-500 mt-1">Pages are counted from Nov 23, 2026 on: every page a guest or a member opens on anee.io, inside the app too. Staff and test accounts are left out.</p>
            </div>`;
            return;
        }
        const s = d.signups;
        const searches = d.searches.length
            ? '<div class="flex flex-wrap gap-1.5">' + d.searches.map((x) => `<span class="ir-chip">${esc(x.words)} <i>${plural(x.people, 'person', 'people')}</i></span>`).join('') + '</div>'
            : '<p class="ir-empty">No searches in this period.</p>';
        const members = d.members.length
            ? d.members.map((m) => `
                <a class="ad-row" href="${URL_CLIENT}?client=${m.id}" style="padding-left:0;padding-right:0">
                    <span class="ad-face">${esc((m.name || '?').slice(0, 1).toUpperCase())}</span>
                    <span class="ad-mid">
                        <span class="ad-name">${esc(m.name)}</span>
                        <span class="ad-meta">${plural(m.views, 'page')} on ${plural(m.days, 'day')} · last seen ${esc(m.last)}</span>
                        <span class="ad-meta">${m.top ? 'Opens ' + esc(m.top) + ' most' : ''}${m.crop ? (m.top ? ' · ' : '') + 'reads about ' + esc(m.crop) : ''}</span>
                    </span>
                    <span class="ad-end text-gray-400" aria-hidden="true">›</span>
                </a>`).join('')
            : '<p class="ir-empty">No member opened a page in this period.</p>';

        $id('irBody').innerHTML = `
            <div class="ad-stats ad-rise">
                <div class="card ad-stat"><b>${t.views.toLocaleString()}</b><span>Pages opened</span></div>
                <div class="card ad-stat"><b>${t.people.toLocaleString()}</b><span>People</span><small>${esc(whoWords === 'everyone' ? 'guests and members' : whoWords)}</small></div>
                <div class="card ad-stat"><b>${t.members.toLocaleString()}</b><span>Members among them</span></div>
                <div class="card ad-stat"><b>${t.perPerson}</b><span>Pages per person</span></div>
            </div>

            <div class="card p-4 mt-3">
                <div class="ir-card-h"><p>Pages opened ${esc(stepWords)}</p><p class="ir-note">Since ${esc(d.since)} · counted ${esc(d.asOf)} · <button type="button" class="underline" id="irFresh">count again</button></p></div>
                <div class="ch-wrap" id="irTrend" role="img"></div>
            </div>

            <div class="ir-split mt-3">
                <div class="card p-4">
                    <div class="ir-card-h"><p>What they read</p><p class="ir-note">Website sections, by people</p></div>
                    ${barList(d.sections, { empty: 'Nobody read the website in this period.' })}
                </div>
                <div class="card p-4">
                    <div class="ir-card-h"><p>Crops they care about</p><p class="ir-note">Pages about a crop, by people</p></div>
                    ${barList(d.crops, { empty: 'No page about a single crop was opened in this period.' })}
                </div>
            </div>

            <div class="ir-split mt-3">
                <div class="card p-4">
                    <div class="ir-card-h"><p>What members use in the app</p><p class="ir-note">Share of members who opened the app</p></div>
                    ${barList(d.tools, { empty: d.who === 'guests' ? 'Guests do not see the app.' : 'Nobody opened the app in this period.' })}
                </div>
                <div class="card p-4">
                    <div class="ir-card-h"><p>Where they come from</p><p class="ir-note">Other sites that sent them</p></div>
                    ${barList(d.referrers, { empty: 'Everyone came straight to anee.io or from a bookmark.' })}
                    <div class="flex flex-wrap items-center gap-1.5 mt-3"><span class="ir-note">Pages read on</span> ${d.devices.map((x) => `<span class="ir-chip">${esc(x.label)} <i>${x.share}%</i></span>`).join('')}</div>
                </div>
            </div>

            <div class="card p-4 mt-3">
                <div class="ir-card-h"><p>Most opened pages</p><p class="ir-note">Website pages, by people</p></div>
                ${pageList(d.pages, 'No website page was opened in this period.', (p) => `<b>${plural(p.people, 'person', 'people')}</b><br>${plural(p.views, 'view')}`)}
            </div>

            <div class="card p-4 mt-3">
                <div class="ir-card-h"><p>What won them over</p><p class="ir-note">${s.signups ? `${s.tracked.toLocaleString()} of the ${plural(s.signups, 'person', 'people')} who signed up since ${esc(d.since)} left a trail` : 'Nobody signed up in this period'}</p></div>
                <div class="ir-split">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">The first page they ever opened</p>
                        ${pageList(s.firsts, 'No trail yet.', (p) => `<b>${plural(p.people, 'person', 'people')}</b>`)}
                    </div>
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">Pages they read before signing up</p>
                        ${pageList(s.pages, 'No trail yet.', (p) => `<b>${plural(p.people, 'person', 'people')}</b>`)}
                    </div>
                </div>
            </div>

            <div class="ir-split mt-3">
                <div class="card p-4">
                    <div class="ir-card-h"><p>What they searched</p><p class="ir-note">Words typed into search boxes that open a page</p></div>
                    ${searches}
                </div>
                <div class="card p-4">
                    <div class="ir-card-h"><p>Most active members</p><p class="ir-note">Tap one to open their account</p></div>
                    <div>${d.who === 'guests' ? '<p class="ir-empty">Guests have no account to show.</p>' : members}</div>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-4">People are counted once however many pages they open: a member by their account, a guest by their browser. Members include what they read before signing up. Staff, staff logged in as a member, test accounts and robots are left out.</p>`;
        trend($id('irTrend'), d.trend, d.step);
        grow($id('irBody'));
        window.adminRise?.($id('irBody'));
    }

    async function load(fresh) {
        const mine = ++seq;
        try {
            const qs = new URLSearchParams({ days: state.days, who: state.who });
            if (fresh) qs.set('fresh', '1');
            const res = await api(URL_DATA + '?' + qs.toString(), { method: 'GET' });
            if (mine !== seq) return;
            paint(res.data);
        } catch (err) {
            if (mine === seq) (window.toast || console.error)(err.message, 'error');
        }
    }

    const pick = (group, attr, key) => {
        $id(group).addEventListener('click', (e) => {
            const b = e.target.closest('[' + attr + ']');
            if (!b || b.classList.contains('is-selected')) return;
            $id(group).querySelectorAll('.chip').forEach((c) => c.classList.toggle('is-selected', c === b));
            state[key] = key === 'days' ? Number(b.getAttribute(attr)) : b.getAttribute(attr);
            $id('irBody').style.opacity = '.55';
            load(false).finally(() => { $id('irBody').style.opacity = ''; });
        });
    };
    pick('irDays', 'data-days', 'days');
    pick('irWho', 'data-who', 'who');
    document.addEventListener('click', (e) => {
        if (e.target.closest('#irFresh')) load(true);
    });
    $id('irBody').style.transition = 'opacity .28s cubic-bezier(.22,1,.36,1)';

    if (window.api) load(false);
    else window.addEventListener('load', () => load(false), { once: true });
})();
</script>
@endpush
