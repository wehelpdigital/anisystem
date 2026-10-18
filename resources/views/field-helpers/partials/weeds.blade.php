{{-- The in-app Weed Control Helper (2026-10-14): the crop, how it was
     planted (rice only), its age and the weeds seen, and back come what to
     do first and the active ingredients that work at that age, each on a row
     of its own with its HRAC group and what that group does, who sells it,
     and the products sold here for that crop and those weeds ("Sold as"). The weeds of every crop wait in a full screen catalogue
     (the strip on top), each weed's page with a way back here for its group.
     Never a link to the public catalogue. The plans come from
     App\Support\FieldCatalogue::weedPlans(), the weeds from
     App\Support\FieldBook::weeds(). Since 2026-10-18 each list is the
     crop's own (pick(), the twin of FieldCatalogue::forCrop()): what is
     registered in the Philippines for the crop, or, where nothing is, the
     rest with a caution; plus the crop's weed free stretch and what keeps
     weeds out.

     Expects $book (FieldBook::weeds()) and may take $defaultCrop.
     ?crop= and ?group= pick the crop and the weed group. --}}
@php
    $W = \App\Support\WeedControl::class;
    $F = \App\Support\FieldCatalogue::class;
    $plans = $F::weedPlans();
    // Each crop's plans: rice has two (how it was planted), every other crop one.
    $cropPlans = [];
    foreach ($plans as $pk => $p) {
        foreach ($p['crops'] as $c) {
            $cropPlans[$c][] = $pk;
        }
    }
    $wcCrops = collect($F::crops())->filter(fn ($c, $k) => isset($cropPlans[$k]))->map(fn ($c) => [...$c, null])->all();
    $asked = strtolower((string) (request('crop') ?: ($defaultCrop ?? '')));
    $wcCrop = $F::cropKey($asked);
    $wcCrop = isset($wcCrops[$wcCrop]) ? $wcCrop : (isset($wcCrops['rice']) ? 'rice' : array_key_first($wcCrops));
    // A direct seeded rice field (from a season's crop) opens on direct seeding.
    $wcPlan = str_starts_with($asked, 'rice_dsr') && isset($plans['rice_direct']) ? 'rice_direct' : ($cropPlans[$wcCrop][0] ?? array_key_first($plans));
    $groupIcons = [
        'grasses' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15',
        'sedges' => 'M12 4.5l7.5 13h-15zM12 17.5V21',
        'broadleaves' => 'M5 19C5 10 10 5 19 5c0 9-5 14-14 14zM5 19L15 9M9.5 14.5h4M12 12V8.5',
    ];
    $groupHue = ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
    // Each weed group's Tagalog help flag.
    $groupTl = ['grasses' => 'weed-grass', 'sedges' => 'weed-sedge', 'broadleaves' => 'weed-broadleaf'];
    $faces = collect(array_keys($W::GROUPS))->map(fn ($g) => collect($book['entries'])->first(fn ($e) => $e['kind'] === $g && $e['thumb']))->filter();
    // Each photo's original page and licence, for the credit under it when a weed's photo is shown large
    // (2026-10-17): known for the catalogue's own photos, not for a page's own picture.
    $wcEntries = collect($book['entries'])->map(function ($e) use ($F) {
        $ph = $e['photo'] ? $F::photo('weeds', $e['k']) : null;

        return $ph && \App\Support\SitePages::img($ph['src']) === $e['photo'] ? $e + ['pg' => (string) ($ph['page'] ?? ''), 'lic' => (string) ($ph['license'] ?? '')] : $e;
    })->all();
@endphp
@include('field-helpers.partials.kit')
@once
@push('head')
<style>
    /* The planting question shows only for a crop planted more than one
       way (rice); it folds away for the rest instead of jumping. */
    .wc-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), margin .28s cubic-bezier(.22,1,.36,1); }
    .wc-fold > div { min-height: 0; overflow: hidden; }
    .wc-fold.is-shut { grid-template-rows: 0fr; opacity: 0; margin-top: -1.1rem; }
    .wc-fold.is-shut .wc-q { visibility: hidden; }
    /* The age windows: short day counts three across, worded windows two across. */
    .wc-days.is-words { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .wc-days.is-words button { white-space: normal; text-align: left; line-height: 1.3; }
    .wc-src { margin-top: .2rem; font-size: .78rem; line-height: 1.5; color: #6b7280; }
    .wc-src a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
    .fhw-split > div > b { display: flex; align-items: center; gap: .35rem; }
    .fhw-split > div > b svg { width: 1rem; height: 1rem; }
    .fhw-split .fh-ings { grid-template-columns: minmax(0, 1fr); }
    /* The crop's weed free stretch, under the steps. */
    .wc-crit { display: flex; gap: .6rem; align-items: flex-start; margin-top: .75rem; padding: .7rem .85rem; border-radius: .9rem; background: #eef6e4; border: 1px solid #cfe3b5; color: #24400f; font-size: .88rem; line-height: 1.5; }
    .wc-crit svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .12rem; color: #3d6823; }
    .wc-crit b { display: block; font-size: .78rem; font-weight: 800; letter-spacing: .02em; color: #3d6823; }
    .wc-crit.is-now { background: #fff7e0; border-color: #f2d68c; color: #5c3d00; }
    .wc-crit.is-now svg, .wc-crit.is-now b { color: #8a5a00; }
    /* What keeps weeds out: a plain list. */
    .wc-keep { margin-top: .5rem; display: grid; gap: .4rem; }
    .wc-keep li { position: relative; padding-left: 1.35rem; font-size: .9rem; line-height: 1.55; color: #374151; }
    .wc-keep li::before { content: ''; position: absolute; left: .2rem; top: .55rem; width: .5rem; height: .5rem; border-radius: 999px; background: #8fbf5a; }
    /* A long list folds after its first rows; the rest opens with the height easing. */
    .wc-more { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .wc-more > div { min-height: 0; overflow: hidden; }
    .wc-more.is-open { grid-template-rows: 1fr; opacity: 1; }
    .wc-more .fh-ings { margin-top: .45rem; }
    .wc-more-btn { margin-top: .55rem; display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; border-radius: 999px; border: 1px solid #c9dcb3; background: #f6faf0; color: #2f4f1a; font-size: .82rem; font-weight: 800; }
    .wc-more-btn svg { width: .9rem; height: .9rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .wc-more-btn[aria-expanded="true"] svg { transform: rotate(180deg); }
    @media (prefers-reduced-motion: reduce) { .wc-fold, .wc-more, .wc-more-btn svg { transition: none; } }
    html.dark .fh-body .wc-src { color: #b9caa8; }
    html.dark .wc-crit { background: #1c2a12; border-color: #34501f; color: #d6e6c4; }
    html.dark .wc-crit svg, html.dark .wc-crit b { color: #b6dc8a; }
    html.dark .wc-crit.is-now { background: #33270c; border-color: #5c4510; color: #f3dfb0; }
    html.dark .wc-crit.is-now svg, html.dark .wc-crit.is-now b { color: #f8d58a; }
    html.dark .wc-keep li { color: #cdd8c0; }
    html.dark .wc-more-btn { background: #1c2616; border-color: #34501f; color: #cfe5b4; }
    html.dark .fh-body .wc-src a { color: #c5e09f; }
</style>
@endpush
@endonce

<button type="button" class="fh-cat-open" data-fh-cat>
    <span class="fh-faces" aria-hidden="true">@foreach ($faces as $e)<img src="{{ $e['thumb'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endforeach</span>
    <span class="fh-cat-t"><b>Open the weed catalogue</b><small>{{ count($book['entries']) }} weeds of {{ count($book['crops']) }} crops, with photos and local names</small></span>
    <span class="fh-cat-go"><span>Open</span><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
</button>

<div class="wc">
    <div class="wc-ask">
        <div class="wc-q">
            <b><i>1</i>Which crop?</b>
            <small>{{ count($wcCrops) }} crops, from palay to durian</small>
            <div id="fhwCrop">
                @include('public.site.partials.crop-pick', ['id' => 'fhwCropSheet', 'crops' => $wcCrops, 'current' => $wcCrop, 'title' => 'Which crop?', 'noun' => null])
            </div>
        </div>
        <div class="wc-fold {{ count($cropPlans[$wcCrop] ?? []) > 1 ? '' : 'is-shut' }}" id="fhwMethodFold">
            <div>
                <div class="wc-q">
                    <b><i>2</i>How did you plant?</b>
                    <div class="wc-opts wc-seg" role="radiogroup" aria-label="How did you plant?" id="fhwMethod"></div>
                </div>
            </div>
        </div>
        <div class="wc-q">
            <b><i data-n="age">{{ count($cropPlans[$wcCrop] ?? []) > 1 ? 3 : 2 }}</i><span id="fhwAgeAsk">How old is your {{ $F::cropWord($wcCrop) }}?</span></b>
            <small id="fhwDaysHint">Days after planting</small>
            <div class="wc-opts wc-days" role="radiogroup" aria-label="How old is your crop?" id="fhwDays"></div>
        </div>
        <div class="wc-q">
            <b><i data-n="weeds">{{ count($cropPlans[$wcCrop] ?? []) > 1 ? 4 : 3 }}</i>Which weeds do you see?</b>
            <small>Pick one or more</small>
            <div class="wc-opts wc-gs" id="fhwGroups">
                @foreach ($W::GROUPS as $g => $info)
                    <button type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-g="{{ $g }}" style="--g: {{ $groupHue[$g] }}">
                        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$g] }}"/></svg></span>
                        <span>{{ $info['label'] }}{{ \App\Support\Tagalog::flag($groupTl[$g] ?? '') }}<small>{{ $info['hint'] }}</small></span>
                        <span class="tick"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                    </button>
                @endforeach
            </div>
        </div>
        <button type="button" class="wc-go" id="fhwGo" aria-controls="fhwCard"><span>See what to do</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
    </div>
    <div class="wc-out" aria-live="polite">
        <div class="wc-card" id="fhwCard">
            <noscript><div class="wc-body"><p>Turn on JavaScript to use the helper.</p></div></noscript>
        </div>
        <p class="wc-fine">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            <span>Use only herbicides registered with the Fertilizer and Pesticide Authority (FPA) for your crop, and follow the label for the rate, the timing and the protective clothing. Herbicides vary by region and weed, so check with your municipal agriculturist.</span>
        </p>
    </div>
</div>

@include('field-helpers.partials.catalogue', [
    'id' => 'fhwCat', 'title' => 'Weed catalogue', 'sub' => count($book['entries']) . ' weeds of ' . count($book['crops']) . ' crops',
    'placeholder' => 'Search any name, like kulape or ubod ubod',
    'crops' => $book['crops'], 'nouns' => 'weeds', 'allLine' => 'Every weed',
    'facetAll' => 'Every group', 'facetTitle' => 'Grasses, sedges, broadleaves',
])

<script type="application/json" id="fhwData">{!! json_encode([
    'plans' => collect($plans)->map(fn ($p) => ['label' => $p['label'], 'local' => $p['local'], 'after' => $p['after'], 'windows' => $p['windows'], 'sources' => $p['sources'],
        'prevent' => $p['prevent'] ?? [], 'critical' => (object) ($p['critical'] ?? [])]),
    'cropPlans' => $cropPlans,
    'cropWord' => collect($wcCrops)->map(fn ($c, $k) => $F::cropWord($k)),
    'crop' => $wcCrop,
    'plan' => $wcPlan,
    'groups' => collect($W::GROUPS)->map(fn ($g) => $g['label']),
    'groupHints' => collect($W::GROUPS)->map(fn ($g) => $g['hint']),
    'groupIcons' => $groupIcons,
    'ingredients' => $F::ingredients(),
    'modes' => $W::MODES,
    'entries' => $wcEntries,
    'facets' => $book['facets'],
    'cropNames' => $book['cropNames'],
    // Only the products this page can show (FieldBook::weedProducts).
    'products' => $book['products'],
    // Systemic, translaminar or contact, per active ingredient (App\Support\AiMobility).
    'mobility' => $book['mobility'] ?? null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The helper: the crop picks its plan (rice: how it was planted picks
       one of two), the age picks one window of it, and the groups seen pick
       the ingredients. Several groups at once take the ingredients every
       one of them lists; none in common shows each group's own list. */
    const el = document.getElementById('fhwData'), card = document.getElementById('fhwCard');
    if (!el || !card || !window.FH) return;
    const FH = window.FH, esc = FH.esc, I = FH.icon;
    const D = JSON.parse(el.textContent);
    const asked = new URLSearchParams(location.search).get('group');
    const st = { crop: D.crop, plan: D.plan, days: '', groups: [D.groups[asked] ? asked : 'grasses'] };
    const cropTag = document.querySelector('#fhwCrop .cp-tag');
    const fold = document.getElementById('fhwMethodFold'), methodBox = document.getElementById('fhwMethod'), daysBox = document.getElementById('fhwDays');
    const groupBox = document.getElementById('fhwGroups'), hint = document.getElementById('fhwDaysHint'), ageAsk = document.getElementById('fhwAgeAsk');
    const go = document.getElementById('fhwGo'), goText = go?.querySelector('span');
    const ORDER = ['grasses', 'sedges', 'broadleaves'];
    const HUE = { grasses: 98, sedges: 168, broadleaves: 38 };
    const TIMING = { prep: 'Land preparation', pre: 'Pre emergence', early: 'Early post emergence', post: 'Post emergence', rescue: 'Rescue only', young: 'Young crop', established: 'Established crop' };
    // Tagalog help flags: the timings, the weed groups, how it was planted and what the days count from.
    const TIMING_TL = { pre: 'pre-emergence', early: 'early-post', post: 'post-emergence' };
    const GROUP_TL = { grasses: 'weed-grass', sedges: 'weed-sedge', broadleaves: 'weed-broadleaf' };
    const planTl = (k) => (/transplant/.test(k) ? 'transplanting' : (/direct/.test(k) ? 'direct-seeding' : ''));
    const afterTl = (a) => (/transplant/.test(a) ? 'dat' : (/sow|seed/.test(a) ? 'das' : 'dap'));
    const tl = (k) => (k && window.aneeTl ? window.aneeTl.flag(k) : '');
    const join = (a) => a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const gIcon = (g) => '<svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' + esc(D.groupIcons[g] || '') + '"/></svg>';

    const P = D.products || {};
    Object.assign(FH.prodCards, P.cards || {});
    FH.setMobility(D.mobility);
    // Weed killers: the explainer adds selective or not, and pre, early post, post and burndown.
    FH.mobHerb = true;
    // The products sold here for one ingredient on this crop that name every weed group seen (four at most).
    const prodsOf = (name, groups) => {
        const m = ((P.ip || {})[FH.norm(name)] || {})[st.crop] || {};
        let keys = null;
        groups.forEach((g) => { const l = m[g] || []; keys = keys === null ? l.slice() : keys.filter((k) => l.includes(k)); });
        return (keys || []).slice(0, 4).map((k) => FH.prodCards[k]).filter(Boolean);
    };
    // One ingredient as a record: its HRAC group or groups, what they do, and the products sold here.
    const ing = (name, groups) => {
        const g = (D.ingredients[name] || []).slice().sort((a, b) => a - b);
        const group = g.length ? (g.length > 1 ? 'Groups ' + g.join(' and ') : 'Group ' + g[0]) : '';
        const mode = g.map((n) => D.modes[n] || '').filter(Boolean).map((m, x) => x ? m.charAt(0).toLowerCase() + m.slice(1) : m).join(', plus ');
        return FH.ing(name, group, mode, prodsOf(name, groups));
    };
    /* FieldCatalogue::forCrop's twin: a window's names for this crop and group as [name, mark].
       Scoped to other crops: left out. Registered in the Philippines for this crop: only those.
       None is: the rest, each marked 'abroad' (no product registered here) or 'other'. */
    const pick = (w, g) => {
        const on = w.on || {}, only = w.only || {}, abroad = w.abroad || [];
        const cand = (w[g] || []).filter((n) => !only[n] || only[n].includes(st.crop));
        if (!Object.keys(on).length) return cand.map((n) => [n, null]);
        const reg = cand.filter((n) => (on[n] || []).includes(st.crop));
        return reg.length ? reg.map((n) => [n, null]) : cand.map((n) => [n, abroad.includes(n) ? 'abroad' : 'other']);
    };
    const cropName = () => (D.cropWord[st.crop] || 'this crop');
    const FLAG = {
        abroad: () => 'No product with this is registered in the Philippines. Shown only because nothing registered here covers this. Use a product only if its label names your crop.',
        other: () => 'Registered here for other crops, not for ' + cropName() + '. Use a product only if its label names ' + cropName() + '.',
    };
    let anySold = false, anyFlag = false;
    const SHOW = 8;
    // A list of [name, mark]: the first rows, and a fold for the rest when it runs long.
    const list = (items, groups) => {
        const recs = items.map(([n, mark]) => { const r = ing(n, groups); if (mark) { r.flag = FLAG[mark](); anyFlag = true; } return r; });
        anySold = anySold || recs.some((r) => r.products.length);
        if (recs.length <= SHOW + 2) return FH.ingList(recs);
        const rest = recs.length - SHOW;
        return FH.ingList(recs.slice(0, SHOW)) + '<div class="wc-more" aria-hidden="true"><div>' + FH.ingList(recs.slice(SHOW)) + '</div></div>'
            + '<button type="button" class="wc-more-btn" aria-expanded="false" data-more="' + rest + '"><span>Show ' + rest + ' more</span>'
            + '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>';
    };
    card.addEventListener('click', (e) => {
        const b = e.target.closest('.wc-more-btn');
        if (!b) return;
        const box = b.previousElementSibling, open = b.getAttribute('aria-expanded') !== 'true';
        b.setAttribute('aria-expanded', String(open));
        box.classList.toggle('is-open', open);
        box.setAttribute('aria-hidden', String(!open));
        b.querySelector('span').textContent = open ? 'Show fewer' : 'Show ' + b.dataset.more + ' more';
    });
    // The window's days on the plan's count, where its key says them ("11-20"); null for a worded window.
    const span = (k) => { const m = /^(\d+)-(\d+)$/.exec(k); return m ? [+m[1], +m[2]] : null; };
    const CRIT_ICON = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const critBox = (p, key) => {
        const c = (p.critical || {})[st.crop] || (p.critical || {})['*'];
        if (!c || !c.say) return '';
        const d = span(key), now = !!(d && c.to != null && d[0] <= c.to && d[1] >= c.from);
        return '<div class="wc-crit' + (now ? ' is-now' : '') + '">' + CRIT_ICON + '<div><b>' + (now ? 'Weed free period: you are in it now' : 'Weed free period') + '</b>' + esc(c.say) + '</div></div>';
    };
    const counted = (label) => /^\d/.test(label);
    const dayChip = (label) => { const m = label.match(/^(.*\d)\s+(days?|weeks?|months?)$/i); return m ? esc(m[1]) + '<small>' + esc(m[2]) + '</small>' : esc(label); };

    const drawControls = () => {
        const plans = D.cropPlans[st.crop] || [];
        if (!plans.includes(st.plan)) st.plan = plans[0];
        const many = plans.length > 1;
        fold.classList.toggle('is-shut', !many);
        fold.setAttribute('aria-hidden', String(!many));
        methodBox.innerHTML = many ? plans.map((k) => '<button type="button" role="radio" aria-checked="' + (k === st.plan) + '" data-plan="' + esc(k) + '">'
            + esc(D.plans[k].label) + tl(planTl(k)) + (D.plans[k].local ? '<small>' + esc(D.plans[k].local) + '</small>' : '') + '</button>').join('') : '';
        const p = D.plans[st.plan];
        const keys = Object.keys(p.windows);
        if (!keys.includes(st.days)) st.days = keys[0];
        const words = keys.some((k) => !counted(p.windows[k].short));
        daysBox.classList.toggle('is-words', words);
        daysBox.innerHTML = keys.map((k) => '<button type="button" role="radio" aria-checked="' + (k === st.days) + '" data-days="' + esc(k) + '">' + dayChip(p.windows[k].short) + '</button>').join('');
        ageAsk.textContent = 'How old is your ' + (D.cropWord[st.crop] || 'crop') + '?';
        hint.textContent = words ? 'Pick where your field is now' : 'Days ' + p.after;
        if (words) hint.removeAttribute('data-tl'); else hint.setAttribute('data-tl', afterTl(p.after));
        document.querySelectorAll('.wc-ask .wc-q > b > i[data-n]').forEach((i) => { i.textContent = String((i.dataset.n === 'age' ? 2 : 3) + (many ? 1 : 0)); });
    };

    const render = (swap) => {
        const p = D.plans[st.plan], w = p.windows[st.days];
        methodBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.plan === st.plan)));
        daysBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.days === st.days)));
        groupBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(st.groups.includes(b.dataset.g))));
        const gs = ORDER.filter((g) => st.groups.includes(g));
        const names = gs.map((g) => D.groups[g].toLowerCase());
        let ings = '';
        anySold = false;
        anyFlag = false;
        if (!gs.length) {
            ings = '<div><h3>Active ingredients' + tl('active-ingredient') + '</h3><p class="wc-note ok">Pick the weeds you see and the active ingredients for them show here.</p></div>';
        } else {
            const lists = gs.map((g) => pick(w, g));
            // In every group's list; a name keeps its mark if any group marked it.
            const common = lists.reduce((a, l) => a.filter(([x]) => l.some(([y]) => y === x)).map(([x, m]) => [x, m || (l.find(([y]) => y === x) || [])[1] || null]));
            if (common.length) {
                ings = '<div><h3>Active ingredients' + tl('active-ingredient') + ' that work on ' + esc(join(names)) + '</h3>' + list(common, gs) + '</div>';
            } else if (gs.length === 1) {
                ings = '<div><h3>Active ingredients' + tl('active-ingredient') + ' for ' + esc(names[0]) + '</h3><p class="wc-note">No herbicide is listed for ' + esc(names[0]) + ' at this age. Weed by hand or with a hoe, and keep them from seeding.</p></div>';
            } else {
                ings = '<div><h3>Active ingredients' + tl('active-ingredient') + ' by group</h3><p class="wc-note">No single active ingredient covers ' + esc(join(names)) + ' at this age. Treat each group with its own, and space the sprays as the labels say.</p></div>'
                    + '<div class="wc-split fhw-split">' + gs.map((g, x) => '<div style="--g: ' + HUE[g] + '"><b' + FH.tlAttr(GROUP_TL[g]) + '>' + gIcon(g) + esc(D.groups[g]) + '</b>'
                        + (lists[x].length ? list(lists[x], [g]) : '<p class="wc-legend" style="margin-top:.4rem">None listed at this age. Weed by hand.</p>') + '</div>').join('') + '</div>';
            }
            if (ings.includes('fh-ings')) ings += '<div><p class="wc-legend">' + (anyFlag ? 'An ingredient with a caution under it is not on a Philippine label for ' + esc(cropName()) + '. ' : 'Each active ingredient here is in a product registered in the Philippines for ' + esc(cropName()) + '. ')
                + 'The group number tells how a herbicide kills. Use a different group next season so the weeds do not learn to survive it.</p>' + (anySold ? FH.rateLine('weeds') : '') + FH.SHOP_NOTE + '</div>';
        }
        const src = (p.sources || []).filter((s) => s && s.label).slice(0, 3);
        const based = src.length ? '<p class="wc-src">Based on ' + join(src.map((s) => s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener nofollow">' + esc(s.label) + '</a>' : esc(s.label))) + '.</p>' : '';
        const cw = D.cropWord[st.crop] || '';
        const draw = () => {
            card.innerHTML = '<div class="wc-when"><span class="wc-timing"' + FH.tlAttr(TIMING_TL[w.timing]) + '>' + esc(TIMING[w.timing] || '') + '</span><b>' + esc(w.title) + '</b>'
                + '<small>' + esc((cw ? cw.charAt(0).toUpperCase() + cw.slice(1) + '. ' : '') + w.stage) + '</small></div>'
                + '<div class="wc-body"><div><h3>Do this first</h3><ol class="wc-steps">' + w.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol>' + critBox(p, st.days) + '</div>' + ings
                + ((p.prevent || []).length ? '<div><h3>Keep weeds out of ' + esc(cw || 'the field') + '</h3><ul class="wc-keep">' + p.prevent.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul></div>' : '')
                + based + '</div>';
        };
        if (swap) FH.swap(card, draw); else draw();
        if (goText) {
            goText.textContent = counted(w.short) ? 'See what to do at ' + w.short.toLowerCase() : 'See what to do: ' + w.short.toLowerCase();
            if (swap) FH.bump(go);
        }
    };
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: FH.reduce() ? 'auto' : 'smooth', block: 'start' }));
    cropTag?.addEventListener('croppick', (e) => { st.crop = e.detail.crop; st.days = ''; drawControls(); render(true); });
    methodBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-plan]'); if (b && b.dataset.plan !== st.plan) { st.plan = b.dataset.plan; st.days = ''; drawControls(); render(true); } });
    daysBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-days]'); if (b && b.dataset.days !== st.days) { st.days = b.dataset.days; render(true); } });
    groupBox.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-g]');
        if (!b) return;
        const g = b.dataset.g;
        st.groups = st.groups.includes(g) ? st.groups.filter((x) => x !== g) : st.groups.concat(g);
        render(true);
    });
    // Arrow keys move within a radio group, as a radio group should.
    [methodBox, daysBox].forEach((box) => box.addEventListener('keydown', (e) => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
        const bs = [...box.querySelectorAll('button:not([disabled])')];
        const i = bs.indexOf(document.activeElement);
        if (i < 0) return;
        e.preventDefault();
        const n = bs[(i + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : bs.length - 1)) % bs.length];
        n.focus(); n.click();
    }));
    drawControls();
    render(false);

    /* ---- The weed catalogue ------------------------------------------ */
    // The active ingredients whose Philippine labels name this weed, on the catalogue's crop (else any).
    const namedOf = (i, crop) => (i.named || []).filter((n) => !crop || (n[2] || []).includes(crop)).slice(0, 8);
    const detail = (i, ctx) => {
        const gl = D.groups[i.kind] || i.kind;
        // The herbicides whose label names this weed's group, on the crop the catalogue is on (else any crop).
        const crop = ctx && ctx.crop && i.crops.includes(ctx.crop) ? ctx.crop : '';
        const named = ((((P.wp || {})[i.kind] || {})[crop || '*']) || []).map((k) => FH.prodCards[k]).filter(Boolean);
        return '<article class="fhd" style="--g: ' + i.hue + '">' + FH.photo(i)
            + '<div class="fhd-body">'
            + '<header class="fhd-head"><span class="fhd-pill"' + FH.tlAttr(GROUP_TL[i.kind]) + '>' + gIcon(i.kind) + esc(gl) + '</span><h2>' + esc(i.name) + '</h2>'
            + (i.sci ? '<p class="fhd-sci">' + esc(i.sci) + '</p>' : '')
            + (i.local ? '<p class="fhd-local"><b>Local names:</b> ' + esc(i.local) + '</p>' : '') + '</header>'
            + '<div class="fhd-facts"><div><small>Group</small><b>' + esc(gl) + '</b></div>' + (i.life ? '<div><small>Life</small><b>' + esc(i.life) + '</b></div>' : '') + '</div>'
            + (i.hint ? FH.sec(I.eye, 'How to spot it', '<p>' + esc(i.hint) + '</p>') : '')
            + FH.sec(I.eye, 'How to tell ' + gl.toLowerCase(), '<p>' + esc(D.groupHints[i.kind] || '') + '</p>')
            + FH.sec(I.sprout, 'Crops it troubles (' + i.crops.length + ')', FH.cropChips(i.crops, D.cropNames, st.crop))
            + (i.stop ? FH.sec(I.eye, 'How to keep it out', '<p>' + esc(i.stop) + '</p>') : '')
            + (namedOf(i, crop).length ? FH.sec(I.drop, 'Named on Philippine labels' + (crop && D.cropNames[crop] ? ' for ' + D.cropNames[crop][0].toLowerCase() : ''),
                FH.ingList(namedOf(i, crop).map((n) => ing(n[0], [i.kind])))) : '')
            + (named.length ? FH.sec(I.bag, 'Herbicides labelled for ' + gl.toLowerCase() + (crop && D.cropNames[crop] ? ' on ' + D.cropNames[crop][0].toLowerCase() : ''), FH.prodStrip(named, { help: true }) + FH.rateLine('weeds')) : '')
            + '<button type="button" class="fhd-cta" data-weed-go="' + esc(i.kind) + '">' + I.drop + 'What to spray on ' + esc(gl.toLowerCase()) + ' at your crop\'s age</button>'
            + FH.sources(i.sources)
            + '</div></article>';
    };
    const dlg = document.getElementById('fhwCat');
    const cat = FH.catalogue({
        dlg, items: D.entries, noun: 'weed', nouns: 'weeds', tag: (i) => D.groups[i.kind] || i.kind, detail,
        facet: { title: 'Which group?', all: 'Every group', allIcon: '🌿', opts: D.facets, icon: gIcon },
    });
    document.querySelectorAll('[data-fh-cat]').forEach((b) => b.addEventListener('click', () => cat.open({ crop: '' })));
    // A weed's page sends the farmer back to the helper, on its group.
    dlg?.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-weed-go]');
        if (!b) return;
        st.groups = [b.dataset.weedGo];
        await cat.close();
        render(true);
        card.closest('.wc-out').scrollIntoView({ behavior: FH.reduce() ? 'auto' : 'smooth', block: 'start' });
    });
})();
</script>
@endpush
