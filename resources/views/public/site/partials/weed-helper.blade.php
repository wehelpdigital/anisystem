{{-- The weed control helper (2026-10-06): how the rice was planted, its
     age and the weeds seen, and back come what to do first and the active
     ingredients that work at that age. Shared by /weeds (behind the email
     gate) and the app's Field helpers (no gate). Expects $gate. --}}
@php
    $S = \App\Support\SitePages::class;
    $W = \App\Support\WeedControl::class;
    $groupIcons = $groupIcons ?? [
        'grasses' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15',
        'sedges' => 'M12 4.5l7.5 13h-15zM12 17.5V21',
        'broadleaves' => 'M5 19C5 10 10 5 19 5c0 9-5 14-14 14zM5 19L15 9M9.5 14.5h4M12 12V8.5',
    ];
    $groupHue = $groupHue ?? ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
@endphp
            <div class="wc mt-8">
                <div class="wc-ask">
                    <div class="wc-q">
                        <b><i>1</i>How did you plant?</b>
                        <div class="wc-opts wc-seg" role="radiogroup" aria-label="How did you plant?" id="wcMethod">
                            @foreach ($W::METHODS as $k => $m)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" data-method="{{ $k }}">{{ $m['label'] }}<small>{{ $m['local'] }}</small></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i>2</i>How old is your rice?</b>
                        <small id="wcDaysHint">Days after transplanting</small>
                        <div class="wc-opts wc-days" role="radiogroup" aria-label="How old is your rice?" id="wcDays">
                            @foreach (['0-5', '6-10', '11-20', '21-30', '31-40'] as $d)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" data-days="{{ $d }}">{{ str_replace('-', ' to ', $d) }}<small>days</small></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i>3</i>Which weeds do you see?</b>
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
                            Use only herbicides registered with the <a href="{{ $S::url('blog', 'fertilizer-and-pesticide-authority') }}">Fertilizer and Pesticide Authority</a>, and follow the label for the rate, the timing and the protective clothing.
                            Herbicides vary by region and weed, so check with your municipal agriculturist. Our <a href="{{ $S::url('weeds', 'herbicides-for-rice-weeds') }}">herbicide guide</a> explains the groups.
                        </span>
                    </p>
                </div>
            </div>
    <script type="application/json" id="wcData">{!! json_encode([
        'table' => $W::TABLE,
        'methods' => $W::METHODS,
        'groups' => collect($W::GROUPS)->map(fn ($g) => $g['label']),
        'ingredients' => $W::INGREDIENTS,
        'modes' => $W::MODES,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The weed control helper: method, rice age and the groups seen pick one
       window of WeedControl::TABLE. Several groups at once take the
       ingredients every one of them lists; none in common shows each group's
       own list instead. */
    const el = document.getElementById('wcData');
    const card = document.getElementById('wcCard');
    if (!el || !card) return;
    const D = JSON.parse(el.textContent);
    // A weed page's "Open the helper" link names its group (?group=).
    const asked = new URLSearchParams(location.search).get('group');
    const st = { method: 'transplanted', days: '0-5', groups: [D.groups[asked] ? asked : 'grasses'] };
    const methodBox = document.getElementById('wcMethod');
    const daysBox = document.getElementById('wcDays');
    const groupBox = document.getElementById('wcGroups');
    const hint = document.getElementById('wcDaysHint');
    const ORDER = ['grasses', 'sedges', 'broadleaves'];
    const TIMING = { pre: 'Pre emergence', early: 'Early post emergence', post: 'Post emergence', rescue: 'Rescue only' };
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const join = (a) => a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const chip = (name) => {
        const g = (D.ingredients[name] || []).slice().sort((a, b) => a - b);
        if (!g.length) return '';
        const title = g.map((n) => 'Group ' + n + ': ' + (D.modes[n] || '')).join('. ');
        return '<em title="' + esc(title) + '">' + (g.length > 1 ? 'Groups ' + g.join(' and ') : 'Group ' + g[0]) + '</em>';
    };
    const list = (names) => '<ul class="wc-ings">' + names.map((n) => '<li><span>' + esc(n) + '</span>' + chip(n) + '</li>').join('') + '</ul>';
    const HUE = { grasses: 98, sedges: 168, broadleaves: 38 };

    const render = (swap) => {
        const m = D.table[st.method];
        if (!m[st.days]) st.days = Object.keys(m).pop();
        const w = m[st.days];
        // the controls
        methodBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.method === st.method)));
        daysBox.querySelectorAll('button').forEach((b) => {
            b.disabled = !m[b.dataset.days];
            b.setAttribute('aria-checked', String(b.dataset.days === st.days));
        });
        groupBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(st.groups.includes(b.dataset.g))));
        hint.textContent = 'Days ' + D.methods[st.method].after;

        const gs = ORDER.filter((g) => st.groups.includes(g));
        const names = gs.map((g) => D.groups[g].toLowerCase());
        let ings = '';
        if (!gs.length) {
            ings = '<div><h3>Active ingredients</h3><p class="wc-note ok">Pick the weeds you see in step 3 and the active ingredients for them show here.</p></div>';
        } else {
            const lists = gs.map((g) => w[g] || []);
            const common = lists.reduce((a, l) => a.filter((x) => l.includes(x)));
            if (common.length) {
                ings = '<div><h3>Active ingredients that work on ' + esc(join(names)) + '</h3>' + list(common) + '</div>';
            } else if (gs.length === 1) {
                ings = '<div><h3>Active ingredients for ' + esc(names[0]) + '</h3><p class="wc-note">No herbicide is listed for ' + esc(names[0]) + ' at this age. Pull them by hand and keep the water up.</p></div>';
            } else {
                ings = '<div><h3>Active ingredients by group</h3><p class="wc-note">No single active ingredient covers ' + esc(join(names)) + ' at this age. Treat each group with its own, and space the sprays as the labels say.</p></div>'
                    + '<div class="wc-split">' + gs.map((g) => '<div style="--g: ' + HUE[g] + '"><b>' + esc(D.groups[g]) + '</b>'
                        + (w[g] && w[g].length ? list(w[g]) : '<p class="wc-legend" style="margin-top:.4rem">None listed at this age. Hand weed.</p>') + '</div>').join('') + '</div>';
            }
            if (ings.includes('wc-ings')) ings += '<p class="wc-legend">The group number tells how a herbicide kills. Use a different group next season so the weeds do not learn to survive it.</p>';
        }
        card.innerHTML =
            '<div class="wc-when"><span class="wc-timing">' + esc(TIMING[w.timing] || '') + '</span><b>' + esc(w.label) + ' ' + esc(D.methods[st.method].after) + '</b>'
            + '<small>' + esc(w.stage) + '</small></div>'
            + '<div class="wc-body"><div><h3>Do this first</h3><ol class="wc-steps">' + w.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol></div>' + ings + '</div>';
        if (swap) { card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap'); }
        if (goText) {
            goText.textContent = 'See what to do at ' + w.label.toLowerCase();
            if (swap) { go.classList.remove('is-bump'); void go.offsetWidth; go.classList.add('is-bump'); }
        }
    };
    const go = document.getElementById('wcGo');
    const goText = go?.querySelector('span');
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
    methodBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-method]'); if (b && b.dataset.method !== st.method) { st.method = b.dataset.method; render(true); } });
    daysBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-days]'); if (b && !b.disabled && b.dataset.days !== st.days) { st.days = b.dataset.days; render(true); } });
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
    render(false);
})();
</script>
@endpush
