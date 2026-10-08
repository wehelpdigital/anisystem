{{-- The weed control helper (2026-10-06): the crop, how it was planted
     (rice only: transplanted or direct seeded), its age and the weeds seen,
     and back come what to do first and the active ingredients that work at
     that age. Every crop of the catalog since 2026-10-08, from the plans of
     the field catalogue (App\Support\FieldCatalogue::weedPlans()). Shared by
     /weeds (behind the email gate) and the app's Field helpers (no gate).
     Expects $gate; may take $defaultCrop. --}}
@php
    $S = \App\Support\SitePages::class;
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
    // The crop asked for (?crop=), else the one the page brings (the app: the newest season's).
    $asked = strtolower((string) (request('crop') ?: ($defaultCrop ?? '')));
    $wcCrop = $F::cropKey($asked);
    $wcCrop = isset($wcCrops[$wcCrop]) ? $wcCrop : (isset($wcCrops['rice']) ? 'rice' : array_key_first($wcCrops));
    // A direct seeded rice field (from a season's crop) opens on direct seeding.
    $wcPlan = str_starts_with($asked, 'rice_dsr') && isset($plans['rice_direct']) ? 'rice_direct' : ($cropPlans[$wcCrop][0] ?? array_key_first($plans));
    $groupIcons = $groupIcons ?? [
        'grasses' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15',
        'sedges' => 'M12 4.5l7.5 13h-15zM12 17.5V21',
        'broadleaves' => 'M5 19C5 10 10 5 19 5c0 9-5 14-14 14zM5 19L15 9M9.5 14.5h4M12 12V8.5',
    ];
    $groupHue = $groupHue ?? ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
@endphp
@once
@push('head')
    <style>
        /* The planting question shows only for a crop planted more than one
           way (rice); it folds away for the rest instead of jumping. */
        .wc-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        .wc-fold > div { min-height: 0; overflow: hidden; }
        .wc-fold.is-shut { grid-template-rows: 0fr; opacity: 0; margin-top: -1.1rem; }
        .wc-fold.is-shut .wc-q { visibility: hidden; }
        /* The age windows differ by crop: short day counts sit three across,
           worded windows ("Before planting") wrap two across. */
        .wc-days.is-words { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .wc-days.is-words button { white-space: normal; text-align: left; line-height: 1.3; }
        .wc-src { margin-top: .2rem; font-size: .78rem; line-height: 1.5; color: #6b7280; }
        .wc-src a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
        @media (prefers-reduced-motion: reduce) { .wc-fold { transition: none; } }
        html.dark .fh-body .wc-src { color: #b9caa8; }
        html.dark .fh-body .wc-src a { color: #c5e09f; }
    </style>
@endpush
@endonce
            <div class="wc mt-8">
                <div class="wc-ask">
                    <div class="wc-q">
                        <b><i>1</i>Which crop?</b>
                        <small>{{ count($wcCrops) }} crops, from palay to durian</small>
                        <div id="wcCrop">
                            @include('public.site.partials.crop-pick', ['id' => 'wcCropSheet', 'crops' => $wcCrops, 'current' => $wcCrop, 'title' => 'Which crop?', 'noun' => null])
                        </div>
                    </div>
                    <div class="wc-fold {{ count($cropPlans[$wcCrop] ?? []) > 1 ? '' : 'is-shut' }}" id="wcMethodFold">
                        <div>
                            <div class="wc-q">
                                <b><i>2</i>How did you plant?</b>
                                <div class="wc-opts wc-seg" role="radiogroup" aria-label="How did you plant?" id="wcMethod"></div>
                            </div>
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i data-n="age">{{ count($cropPlans[$wcCrop] ?? []) > 1 ? 3 : 2 }}</i><span id="wcAgeAsk">How old is your {{ $F::cropWord($wcCrop) }}?</span></b>
                        <small id="wcDaysHint">Days after planting</small>
                        <div class="wc-opts wc-days" role="radiogroup" aria-label="How old is your crop?" id="wcDays"></div>
                    </div>
                    <div class="wc-q">
                        <b><i data-n="weeds">{{ count($cropPlans[$wcCrop] ?? []) > 1 ? 4 : 3 }}</i>Which weeds do you see?</b>
                        <small>Pick one or more</small>
                        <div class="wc-opts wc-gs" id="wcGroups">
                            @foreach ($W::GROUPS as $g => $info)
                                <button type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-g="{{ $g }}" style="--g: {{ $groupHue[$g] }}">
                                    <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$g] }}"/></svg></span>
                                    <span>{{ $info['label'] }}<small>{{ $info['hint'] }}</small></span>
                                    <span class="tick"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    {{-- Below two columns the answer is under these questions: this says what waits there. --}}
                    <button type="button" class="wc-go" id="wcGo" aria-controls="wcCard"><span>See what to do</span>
                        <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                </div>
                <div class="wc-out" aria-live="polite">
                    <div class="tg" data-tool-gate="weeds">
                        <div class="wc-card" id="wcCard">
                            <noscript><div class="wc-body"><p>Turn on JavaScript to use the helper, or read the <a href="{{ $S::url('weeds', 'herbicides-for-rice-weeds') }}">tables in our herbicide guide</a>.</p></div></noscript>
                        </div>
                        @if ($gate ?? true)
                            @include('public.site.tool-gate', ['tool' => 'weeds'])
                        @endif
                    </div>
                    <p class="wc-fine">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        <span>
                            Use only herbicides registered with the <a href="{{ $S::url('blog', 'fertilizer-and-pesticide-authority') }}">Fertilizer and Pesticide Authority</a> for your crop, and follow the label for the rate, the timing and the protective clothing.
                            Herbicides vary by region and weed, so check with your municipal agriculturist. Our <a href="{{ $S::url('weeds', 'herbicides-for-rice-weeds') }}">herbicide guide</a> explains the groups.
                        </span>
                    </p>
                </div>
            </div>
    <script type="application/json" id="wcData">{!! json_encode([
        'plans' => collect($plans)->map(fn ($p) => ['label' => $p['label'], 'local' => $p['local'], 'after' => $p['after'], 'windows' => $p['windows'], 'sources' => $p['sources']]),
        'cropPlans' => $cropPlans,
        'cropWord' => collect($wcCrops)->map(fn ($c, $k) => $F::cropWord($k)),
        'crop' => $wcCrop,
        'plan' => $wcPlan,
        'groups' => collect($W::GROUPS)->map(fn ($g) => $g['label']),
        'ingredients' => $F::ingredients(),
        'modes' => $W::MODES,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The weed control helper: the crop picks its plan (rice: how it was
       planted picks one of two), the age picks one window of it, and the
       groups seen pick the ingredients. Several groups at once take the
       ingredients every one of them lists; none in common shows each
       group's own list instead. */
    const el = document.getElementById('wcData');
    const card = document.getElementById('wcCard');
    if (!el || !card) return;
    const D = JSON.parse(el.textContent);
    // A weed page's "Open the helper" link names its group (?group=).
    const asked = new URLSearchParams(location.search).get('group');
    const st = { crop: D.crop, plan: D.plan, days: '', groups: [D.groups[asked] ? asked : 'grasses'] };
    const cropTag = document.querySelector('#wcCrop .cp-tag');
    const fold = document.getElementById('wcMethodFold');
    const methodBox = document.getElementById('wcMethod');
    const daysBox = document.getElementById('wcDays');
    const groupBox = document.getElementById('wcGroups');
    const hint = document.getElementById('wcDaysHint');
    const ageAsk = document.getElementById('wcAgeAsk');
    const ORDER = ['grasses', 'sedges', 'broadleaves'];
    const TIMING = { prep: 'Land preparation', pre: 'Pre emergence', early: 'Early post emergence', post: 'Post emergence', rescue: 'Rescue only', young: 'Young crop', established: 'Established crop' };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const join = (a) => a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const chip = (name) => {
        const g = (D.ingredients[name] || []).slice().sort((a, b) => a - b);
        if (!g.length) return '';
        const title = g.map((n) => 'Group ' + n + ': ' + (D.modes[n] || '')).join('. ');
        return '<em title="' + esc(title) + '">' + (g.length > 1 ? 'Groups ' + g.join(' and ') : 'Group ' + g[0]) + '</em>';
    };
    const list = (names) => '<ul class="wc-ings">' + names.map((n) => '<li><span>' + esc(n) + '</span>' + chip(n) + '</li>').join('') + '</ul>';
    const HUE = { grasses: 98, sedges: 168, broadleaves: 38 };
    // "0 to 5 days" sits as "0 to 5" over "days"; a worded window stays whole.
    const counted = (label) => /^\d/.test(label);
    const dayChip = (label) => { const m = label.match(/^(.*\d)\s+(days?|weeks?|months?)$/i); return m ? esc(m[1]) + '<small>' + esc(m[2]) + '</small>' : esc(label); };

    // The controls that change with the crop: the plantings, the age windows.
    const drawControls = () => {
        const plans = D.cropPlans[st.crop] || [];
        if (!plans.includes(st.plan)) st.plan = plans[0];
        const many = plans.length > 1;
        fold.classList.toggle('is-shut', !many);
        fold.setAttribute('aria-hidden', String(!many));
        methodBox.innerHTML = many ? plans.map((k) => '<button type="button" role="radio" aria-checked="' + (k === st.plan) + '" data-plan="' + esc(k) + '">'
            + esc(D.plans[k].label) + (D.plans[k].local ? '<small>' + esc(D.plans[k].local) + '</small>' : '') + '</button>').join('') : '';
        const p = D.plans[st.plan];
        const keys = Object.keys(p.windows);
        if (!keys.includes(st.days)) st.days = keys[0];
        const words = keys.some((k) => !counted(p.windows[k].short));
        daysBox.classList.toggle('is-words', words);
        daysBox.innerHTML = keys.map((k) => '<button type="button" role="radio" aria-checked="' + (k === st.days) + '" data-days="' + esc(k) + '">' + dayChip(p.windows[k].short) + '</button>').join('');
        ageAsk.textContent = 'How old is your ' + (D.cropWord[st.crop] || 'crop') + '?';
        hint.textContent = words ? 'Pick where your field is now' : 'Days ' + p.after;
        document.querySelectorAll('.wc-ask .wc-q > b > i[data-n]').forEach((i) => { i.textContent = String((i.dataset.n === 'age' ? 2 : 3) + (many ? 1 : 0)); });
    };

    const render = (swap) => {
        const p = D.plans[st.plan];
        const w = p.windows[st.days];
        methodBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.plan === st.plan)));
        daysBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.days === st.days)));
        groupBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(st.groups.includes(b.dataset.g))));

        const gs = ORDER.filter((g) => st.groups.includes(g));
        const names = gs.map((g) => D.groups[g].toLowerCase());
        let ings = '';
        if (!gs.length) {
            ings = '<div><h3>Active ingredients</h3><p class="wc-note ok">Pick the weeds you see and the active ingredients for them show here.</p></div>';
        } else {
            const lists = gs.map((g) => w[g] || []);
            const common = lists.reduce((a, l) => a.filter((x) => l.includes(x)));
            if (common.length) {
                ings = '<div><h3>Active ingredients that work on ' + esc(join(names)) + '</h3>' + list(common) + '</div>';
            } else if (gs.length === 1) {
                ings = '<div><h3>Active ingredients for ' + esc(names[0]) + '</h3><p class="wc-note">No herbicide is listed for ' + esc(names[0]) + ' at this age. Weed by hand or with a hoe, and keep them from seeding.</p></div>';
            } else {
                ings = '<div><h3>Active ingredients by group</h3><p class="wc-note">No single active ingredient covers ' + esc(join(names)) + ' at this age. Treat each group with its own, and space the sprays as the labels say.</p></div>'
                    + '<div class="wc-split">' + gs.map((g) => '<div style="--g: ' + HUE[g] + '"><b>' + esc(D.groups[g]) + '</b>'
                        + (w[g] && w[g].length ? list(w[g]) : '<p class="wc-legend" style="margin-top:.4rem">None listed at this age. Weed by hand.</p>') + '</div>').join('') + '</div>';
            }
            if (ings.includes('wc-ings')) ings += '<p class="wc-legend">The group number tells how a herbicide kills. Use a different group next season so the weeds do not learn to survive it.</p>';
        }
        const src = (p.sources || []).filter((s) => s && s.label).slice(0, 3);
        const based = src.length ? '<p class="wc-src">Based on ' + join(src.map((s) => s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + esc(s.label) + '</a>' : esc(s.label))) + '.</p>' : '';
        const title = w.title;
        card.innerHTML =
            '<div class="wc-when"><span class="wc-timing">' + esc(TIMING[w.timing] || '') + '</span><b>' + esc(title) + '</b>'
            + '<small>' + esc((D.cropWord[st.crop] ? D.cropWord[st.crop].charAt(0).toUpperCase() + D.cropWord[st.crop].slice(1) + '. ' : '') + w.stage) + '</small></div>'
            + '<div class="wc-body"><div><h3>Do this first</h3><ol class="wc-steps">' + w.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol></div>' + ings + based + '</div>';
        if (swap) { card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap'); }
        if (goText) {
            goText.textContent = counted(w.short) ? 'See what to do at ' + w.short.toLowerCase() : 'See what to do: ' + w.short.toLowerCase();
            if (swap) { go.classList.remove('is-bump'); void go.offsetWidth; go.classList.add('is-bump'); }
        }
    };
    const go = document.getElementById('wcGo');
    const goText = go?.querySelector('span');
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
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
})();
</script>
@endpush
