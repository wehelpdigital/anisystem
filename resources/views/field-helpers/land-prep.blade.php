{{-- The land preparation planner (2026-10-14): pick the crop, the planting
     day and the soil's troubles, and every step of getting the field ready
     comes dated back from that day, with a tick for each one done (kept on
     this phone). The steps are App\Support\LandPrep's; the full guides are
     the public land preparation pages. Needs $book (LandPrep::book()) and
     $defaultCrop. --}}
@push('head')
<style>
    .lp-tools { display: grid; gap: .9rem; padding: 1rem; border-radius: 1.2rem; background: #fff; border: 1px solid var(--color-gray-100); }
    @media (min-width: 768px) { .lp-tools { grid-template-columns: 1.4fr 1fr; } }
    .lp-lab { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700); margin-bottom: .35rem; }
    .lp-soil { display: flex; flex-wrap: wrap; gap: .35rem; }
    .lp-soil button { padding: .4rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-soil button.is-on { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .lp-wide { grid-column: 1 / -1; }
    .lp-sum { margin-top: 1rem; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .9rem; font-size: .85rem; color: var(--color-gray-600); }
    .lp-sum b { color: var(--color-gray-900); }
    .lp-bar { flex: 1 1 10rem; height: .5rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .lp-bar i { display: block; height: 100%; width: 0; border-radius: inherit; background: var(--color-brand-500); transition: width .5s cubic-bezier(.22,1,.36,1); }
    .lp-list { margin-top: .9rem; display: grid; gap: .55rem; }
    .lp-step { display: grid; grid-template-columns: auto 1fr; gap: .75rem; padding: .8rem .9rem; border-radius: 1rem; background: #fff; border: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(8px); transition: opacity .4s cubic-bezier(.22,1,.36,1), transform .4s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-step.is-in { opacity: 1; transform: none; }
    .lp-step.is-now { border-color: #f5c518; background: #fffbea; }
    .lp-step.is-done { background: var(--color-gray-50); }
    .lp-step.is-done .lp-what { text-decoration: line-through; color: var(--color-gray-400); }
    .lp-tick { width: 1.6rem; height: 1.6rem; border-radius: 999px; border: 2px solid var(--color-brand-500); background: #fff; display: grid; place-items: center; margin-top: .1rem;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lp-tick svg { width: .9rem; height: .9rem; color: #fff; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .lp-step.is-done .lp-tick { background: var(--color-brand-500); }
    .lp-step.is-done .lp-tick svg { opacity: 1; }
    .lp-when { display: flex; flex-wrap: wrap; align-items: baseline; gap: .2rem .5rem; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); }
    .lp-when small { font-weight: 700; color: var(--color-gray-400); }
    .lp-when .is-soon { color: #a16207; }
    .lp-when .is-late { color: #b91c1c; }
    .lp-what { margin-top: .15rem; font-weight: 800; color: var(--color-gray-900); font-size: .95rem; }
    .lp-how { margin-top: .25rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-600); }
    .lp-tag { display: inline-block; margin-left: .3rem; padding: .05rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 800; vertical-align: middle; color: #7c2d12; background: #ffedd5; }
    .lp-more { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .5rem 1rem; }
    .lp-more a { font-size: .85rem; font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; }
    html.dark .lp-tools, html.dark .lp-step { background: #151b12; border-color: #2b3a1c; }
    html.dark .lp-step.is-now { background: #2a2413; border-color: #6b5a1c; }
    html.dark .lp-step.is-done { background: #10150d; }
    html.dark .lp-what { color: #e8efe1; }
    html.dark .lp-how, html.dark .lp-sum { color: #b7c2ad; }
    html.dark .lp-sum b { color: #e8efe1; }
    html.dark .lp-soil button { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lp-soil button.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .lp-bar { background: #22301a; }
    html.dark .lp-tick { background: #151b12; }
    @media (prefers-reduced-motion: reduce) { .lp-step, .lp-bar i, .lp-tick, .lp-tick svg, .lp-soil button { transition: none; } .lp-step { opacity: 1; transform: none; } }
</style>
@endpush

<div class="lp-tools">
    <label><span class="lp-lab">Crop</span>
        <select class="form-input" id="lpCrop">
            @foreach (collect($book['crops'])->groupBy('group') as $g => $list)
                <optgroup label="{{ $g }}">
                    @foreach ($list as $c)<option value="{{ $c['key'] }}">{{ $c['icon'] }} {{ $c['label'] }}</option>@endforeach
                </optgroup>
            @endforeach
        </select>
    </label>
    <label><span class="lp-lab">Planting day</span>
        <input type="date" class="form-input" id="lpDate">
    </label>
    <div class="lp-wide"><span class="lp-lab">Trouble with the soil?</span>
        <div class="lp-soil" id="lpSoil">
            @foreach ($book['soilLabels'] as $k => $label)<button type="button" data-soil="{{ $k }}" aria-pressed="false">{{ $label }}</button>@endforeach
        </div>
    </div>
</div>

<div class="lp-sum" aria-live="polite"><span id="lpSumText"></span><span class="lp-bar" aria-hidden="true"><i id="lpBar"></i></span></div>
<div class="lp-list" id="lpList"></div>
<div class="lp-more" id="lpMore"></div>

@push('scripts')
<script>
(() => {
    /* The planner: the plan of the crop, the soil's own steps, every step
       dated back from the planting day and sorted; ticks kept per crop and
       planting day in this browser. */
    const BOOK = @json($book);
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const crop = $('lpCrop'), date = $('lpDate');
    const soil = new Set();
    const PREF = 'anee.landprep';
    const load = (k, d) => { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } };
    const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* private window */ } };
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const fmt = (d) => d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', weekday: 'short' });
    const prefs = load(PREF, {});
    const start = new Date(); start.setDate(start.getDate() + 30);
    crop.value = prefs.crop || @json($defaultCrop ?: 'rice');
    if (!crop.value) crop.value = 'rice';
    date.value = prefs.date || iso(start);
    (prefs.soil || []).forEach((k) => soil.add(k));
    document.querySelectorAll('#lpSoil [data-soil]').forEach((b) => { const on = soil.has(b.dataset.soil); b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });

    function draw() {
        const c = BOOK.crops.find((x) => x.key === crop.value) || BOOK.crops[0];
        const plan = BOOK.plans[c.plan];
        const day0 = new Date(date.value + 'T00:00:00');
        if (!plan || isNaN(day0)) return;
        save(PREF, { crop: c.key, date: date.value, soil: [...soil] });
        const doneKey = PREF + '.done.' + c.plan + '.' + date.value;
        const done = new Set(load(doneKey, []));
        const steps = plan.steps.map((s) => ({ ...s, soil: false }));
        soil.forEach((k) => (BOOK.soil[k] || []).forEach((s) => steps.push({ ...s, soil: BOOK.soilLabels[k] })));
        steps.sort((a, b) => a.from - b.from || a.to - b.to);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const at = (n) => { const d = new Date(day0); d.setDate(d.getDate() + n); return d; };
        const left = Math.round((day0 - today) / 86400000);
        $('lpList').innerHTML = steps.map((s, i) => {
            const id = s.what.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            const a = at(s.from), b = at(s.to);
            const inDays = Math.round((a - today) / 86400000), endDays = Math.round((b - today) / 86400000);
            const isNow = inDays <= 0 && endDays >= 0 && !done.has(id);
            const late = endDays < 0 && !done.has(id);
            const rel = s.from === 0 && s.to === 0 ? 'Planting day' : (s.from < 0 ? (s.to < 0 ? Math.abs(s.from) + (s.to !== s.from ? ' to ' + Math.abs(s.to) : '') + ' days before' : 'Up to planting day') : (s.from + (s.to !== s.from ? ' to ' + s.to : '') + ' days after'));
            const when = isNow ? '<span class="is-soon">Do it now</span>' : (late ? '<span class="is-late">Past due</span>' : (inDays > 0 ? `<small>in ${inDays} ${inDays === 1 ? 'day' : 'days'}</small>` : ''));
            return `<div class="lp-step${isNow ? ' is-now' : ''}${done.has(id) ? ' is-done' : ''}" data-id="${esc(id)}" style="transition-delay:${Math.min(i, 12) * 40}ms">
                <button type="button" class="lp-tick" data-tick aria-label="Mark ${esc(s.what)} done" aria-pressed="${done.has(id)}"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>
                <div>
                    <div class="lp-when">${esc(fmt(a))}${s.to !== s.from ? ' to ' + esc(fmt(b)) : ''} <small>${esc(rel)}</small> ${when}</div>
                    <p class="lp-what">${esc(s.what)}${s.soil ? `<span class="lp-tag">${esc(s.soil)} soil</span>` : ''}</p>
                    <p class="lp-how">${esc(s.how)}</p>
                </div>
            </div>`;
        }).join('');
        requestAnimationFrame(() => document.querySelectorAll('.lp-step').forEach((el) => el.classList.add('is-in')));
        const n = steps.length, d = steps.filter((s) => done.has(s.what.toLowerCase().replace(/[^a-z0-9]+/g, '-'))).length;
        $('lpSumText').innerHTML = `<b>${esc(plan.label)}</b> · ${left > 0 ? left + ' days to planting' : (left === 0 ? 'Planting is today' : 'Planted ' + Math.abs(left) + ' days ago')} · <b>${d} of ${n}</b> done`;
        $('lpBar').style.width = (n ? Math.round(d / n * 100) : 0) + '%';
        $('lpMore').innerHTML = `<a href="${esc(plan.guide)}" target="_blank" rel="noopener">The full guide: ${esc(plan.label.toLowerCase())} ›</a>`
            + [...soil].filter((k) => BOOK.soilGuides[k]).map((k) => `<a href="${esc(BOOK.soilGuides[k])}" target="_blank" rel="noopener">${esc(BOOK.soilLabels[k])} soil ›</a>`).join('');
        $('lpList').onclick = (e) => {
            const t = e.target.closest('[data-tick]');
            if (!t) return;
            const row = t.closest('.lp-step'), key = row.dataset.id;
            if (done.has(key)) done.delete(key); else done.add(key);
            save(doneKey, [...done]);
            draw();
        };
    }
    crop.addEventListener('change', draw);
    date.addEventListener('change', draw);
    document.querySelectorAll('#lpSoil [data-soil]').forEach((b) => b.addEventListener('click', () => {
        const k = b.dataset.soil;
        if (soil.has(k)) soil.delete(k); else soil.add(k);
        b.classList.toggle('is-on', soil.has(k)); b.setAttribute('aria-pressed', soil.has(k) ? 'true' : 'false');
        draw();
    }));
    draw();
})();
</script>
@endpush
