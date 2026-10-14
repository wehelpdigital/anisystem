{{-- The rice variety finder (2026-10-13): every variety in PhilRice's
     catalogue of released varieties as a card, with chips, selects and a search box that hide and
     order them. On the public /rice-varieties and in the app's field helpers
     (FieldHelperController). Every card is in the page, so search engines
     read every link; the script only hides and sorts. Needs $varieties
     (App\Support\RiceVarieties::all()). --}}
@php
    $S = \App\Support\SitePages::class;
    $RV = \App\Support\RiceVarieties::class;
    $all = collect($varieties);
    $envCount = fn ($k) => $all->filter(fn ($v) => in_array($k, $v['environments'], true))->count();
    $fmt = fn ($x) => rtrim(rtrim(number_format((float) $x, 1, '.', ''), '0'), '.');
    $cardTarget = $cardTarget ?? null;
    // The members' gate on /rice-varieties (2026-10-14): the card's words
    // when a guest is looking, else null (partials/hub-gate).
    $hubGate = $hubGate ?? null;
    // A company's seed brand finds its varieties too: "arize" finds every Bayer hybrid.
    $brandWords = fn ($breeder) => collect(['bayer' => 'arize', 'syngenta' => 'nk', 'long ping' => 'longping lp', 'longping' => 'longping lp', 'sl agritech' => 'sl',
        'leads' => 'jackpot leads', 'bioseed' => 'biorice', 'seedworks' => 'us th', 'pioneer' => 'phb', 'advanta' => 'pac'])
        ->filter(fn ($w, $k) => str_contains(strtolower($breeder), $k))->implode(' ');
@endphp
@include('public.site.partials.variety-finder-css')

            <div class="rv-tools" data-rv-tools>
                <label class="wk-search" style="max-width: none">
                    <span class="sr-only">Search a variety</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" data-rv-q placeholder="Search a name, number or brand, like 222 or Jackpot" autocomplete="off">
                </label>
                <div class="rv-row">
                    <span class="rv-lab" id="rvEnvL">Kind of farm</span>
                    <div class="rv-chips" role="group" aria-labelledby="rvEnvL">
                        <button type="button" class="rv-chip is-on" data-rv-env="">All <small>{{ $all->count() }}</small></button>
                        @foreach ($RV::ENVIRONMENTS as $k => [$long, $chip])
                            @if ($envCount($k))
                                <button type="button" class="rv-chip" data-rv-env="{{ $k }}">{{ $chip }} <small>{{ $envCount($k) }}</small></button>
                            @endif
                        @endforeach
                        <button type="button" class="rv-chip" data-rv-env="glutinous">Glutinous <small>{{ $all->where('special', 'glutinous')->count() }}</small></button>
                    </div>
                </div>
                <div class="rv-row">
                    <span class="rv-lab" id="rvResL">Holds up against</span>
                    <div class="rv-chips" role="group" aria-labelledby="rvResL">
                        @foreach ($RV::PROBLEMS as $k => [$label, $url])
                            <button type="button" class="rv-chip" data-rv-res="{{ $k }}" aria-pressed="false">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="rv-two">
                    <label class="rv-row"><span class="rv-lab">Type</span>
                        <select class="rv-sel" data-rv-type><option value="">Inbred and hybrid</option><option value="inbred">Inbred only</option><option value="hybrid">Hybrid only</option></select></label>
                    <label class="rv-row"><span class="rv-lab">Days to harvest</span>
                        <select class="rv-sel" data-rv-days><option value="">Any</option><option value="110">110 days or fewer</option><option value="120">111 to 120 days</option><option value="999">More than 120 days</option></select></label>
                    <label class="rv-row"><span class="rv-lab">Sort by</span>
                        <select class="rv-sel" data-rv-sort><option value="year">Newest first</option><option value="yield">Highest average yield</option><option value="days">Fewest days to harvest</option><option value="name">Name</option></select></label>
                </div>
            </div>
            <div class="rv-count" aria-live="polite"><span><b data-rv-n>{{ $all->count() }}</b> of {{ $all->count() }} varieties</span><button type="button" class="rv-reset" data-rv-reset hidden>Clear the filters</button></div>
            {{-- Where the t/ha on the cards come from, and why a farm's harvest can differ (2026-10-15). --}}
            @include('partials.yield-note')

            @if ($hubGate)<div class="hg" data-hg data-hg-one="variety" data-hg-many="varieties">@endif
            <div class="rv-grid hg-grid" data-rv-grid data-hg-grid data-hg-card=".rv-card">
                @foreach ($all as $i => $v)
                    @php
                        $res = collect($v['reactions'])->map(fn ($r) => $RV::code($r))->filter()->all();
                        $okRes = collect($res)->filter(fn ($c) => in_array($c, ['R', 'MR'], true))->keys()->implode(' ');
                        $envs = collect($v['environments'])->map(fn ($e) => $RV::ENVIRONMENTS[$e][0] ?? null)->filter()->implode(', ');
                    @endphp
                    <a href="{{ $S::url('rice-varieties', $v['slug']) }}" @if ($cardTarget) target="{{ $cardTarget }}" @endif class="rv-card {{ $v['type'] === 'hybrid' ? 'is-hybrid' : '' }}"
                       data-i="{{ $i }}" data-q="{{ strtolower($v['name'] . ' ' . str_replace(' ', '', $v['name']) . ' ' . ($v['localName'] ?? '') . ' ' . ($v['breeder'] ?? '') . ' ' . implode(' ', $v['tradeNames'] ?? []) . ' ' . str_replace([' ', '-'], '', implode(' ', $v['tradeNames'] ?? [])) . ' ' . $brandWords($v['breeder'] ?? '')) }}"
                       data-env="{{ implode(' ', $v['environments']) }}{{ $v['special'] === 'glutinous' ? ' glutinous' : '' }}" data-type="{{ $v['type'] }}"
                       data-days="{{ $v['maturity'] ? (int) $v['maturity'] : '' }}" data-yield="{{ $v['yieldAvg'] ?? 0 }}" data-year="{{ $v['year'] }}" data-res="{{ $okRes }}"
                       @if ($i >= 24) hidden @endif>
                        <span class="rv-top">
                            <span><span class="rv-name">{{ $v['name'] }}</span>@if (! empty($v['tradeNames']))<span class="rv-trade">{{ implode(', ', array_slice($v['tradeNames'], 0, 2)) }}</span>@endif @if ($v['localName'])<span class="rv-local">{{ $v['localName'] }}</span>@endif</span>
                            <span class="rv-year">{{ $v['year'] }}</span>
                        </span>
                        <span class="rv-tags">
                            @if ($v['type'] === 'hybrid')<span class="rv-tag is-h">Hybrid</span>@else<span class="rv-tag">Inbred</span>@endif
                            @if ($v['special'] === 'glutinous')<span class="rv-tag is-g">Glutinous</span>@endif
                            @if ($envs)<span class="rv-tag">{{ $envs }}</span>@endif
                        </span>
                        @if ($v['maturity'] && $v['yieldAvg'])
                        <span class="rv-nums">
                            <span><b>{{ $v['maturity'] ? (int) $v['maturity'] : 'n/a' }}</b><span>days</span></span>
                            <span><b>{{ $v['yieldAvg'] ? $fmt($v['yieldAvg']) : 'n/a' }}</b><span>t/ha avg</span></span>
                            <span><b>{{ $v['yieldMax'] ? $fmt($v['yieldMax']) : 'n/a' }}</b><span>t/ha top</span></span>
                        </span>
                        <span class="rv-bar" aria-hidden="true"><i style="width: {{ min(100, round(($v['yieldAvg'] ?? 0) / 8 * 100)) }}%"></i></span>
                        @else
                            <span class="rv-nodata">Days to harvest and yield not listed by PhilRice yet</span>
                        @endif
                        @if ($res)
                            <span class="rv-res">
                                @foreach ($res as $k => $c)
                                    <i class="{{ in_array($c, ['R', 'MR'], true) ? 'r' : ($c === 'I' ? 'm' : 's') }}" title="{{ $RV::PROBLEMS[$k][0] }}: {{ $c }}">{{ ['blast' => 'Blast', 'blb' => 'BLB', 'tungro' => 'Tungro', 'bph' => 'BPH', 'glh' => 'GLH', 'sb' => 'SB', 'shb' => 'ShB'][$k] ?? strtoupper($k) }} {{ $c }}</i>
                                @endforeach
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
            <p class="rv-none" data-rv-none hidden>No variety in PhilRice's list matches all of these. Clear a filter or two.</p>
            <button type="button" class="rv-more" data-rv-more>Show more varieties</button>
            @if ($hubGate)
                @include('public.site.partials.content-gate-card', $hubGate)
            </div>
            @endif

@once
@push('scripts')
<script>
(() => {
    /* The finder: chips, selects and the search box hide the cards that do
       not match and order the rest; 24 show at first, more on a tap. */
    const grid = document.querySelector('[data-rv-grid]');
    if (!grid) return;
    const cards = [...grid.querySelectorAll('.rv-card')];
    const q = document.querySelector('[data-rv-q]'), type = document.querySelector('[data-rv-type]'), days = document.querySelector('[data-rv-days]'), sort = document.querySelector('[data-rv-sort]');
    const more = document.querySelector('[data-rv-more]'), none = document.querySelector('[data-rv-none]'), nOut = document.querySelector('[data-rv-n]'), reset = document.querySelector('[data-rv-reset]');
    const envs = [...document.querySelectorAll('[data-rv-env]')], ress = [...document.querySelectorAll('[data-rv-res]')];
    // Twelve at a time on a phone (a card is a quarter of its screen), 24 on a desk.
    const STEP = window.matchMedia('(max-width: 639.98px)').matches ? 12 : 24;
    // Behind the members' gate every match is in order and the gate decides
    // what shows (partials/hub-gate).
    const gated = !!grid.closest('[data-hg]'), FIRST = gated ? 1e9 : STEP;
    let env = '', want = new Set(), shown = FIRST;
    const norm = (s) => s.toLowerCase().replace(/\s+/g, ' ').trim();
    let booted = false;
    const paint = () => {
        const t = norm(q.value), tc = t.replace(/\s/g, ''), ty = type.value, d = Number(days.value || 0);
        const ok = cards.filter((c) => {
            if (t && !(c.dataset.q.includes(t) || c.dataset.q.includes(tc))) return false;
            if (env && !c.dataset.env.split(' ').includes(env)) return false;
            if (ty && c.dataset.type !== ty) return false;
            const dd = Number(c.dataset.days || 0);
            if (d && !dd) return false;
            if (d === 110 && dd > 110) return false;
            if (d === 120 && (dd <= 110 || dd > 120)) return false;
            if (d === 999 && dd <= 120) return false;
            const res = c.dataset.res.split(' ');
            for (const w of want) if (!res.includes(w)) return false;
            return true;
        });
        const by = sort.value;
        ok.sort((a, b) => by === 'yield' ? b.dataset.yield - a.dataset.yield : by === 'days' ? (Number(a.dataset.days) || 999) - (Number(b.dataset.days) || 999)
            : by === 'name' ? a.textContent.trim().localeCompare(b.textContent.trim(), undefined, { numeric: true }) : ((b.dataset.days ? 1 : 0) - (a.dataset.days ? 1 : 0)) || (b.dataset.year - a.dataset.year) || (a.dataset.i - b.dataset.i));
        cards.forEach((c) => { c.hidden = true; });
        ok.forEach((c, i) => { grid.appendChild(c); c.hidden = i >= shown; });
        nOut.textContent = ok.length;
        none.hidden = ok.length > 0;
        more.hidden = gated || ok.length <= shown;
        reset.hidden = !(t || env || ty || d || want.size || by !== 'year');
        window.aneeHubGate?.(grid);
    };
    // After the first paint every change moves the cards (partials/hub-gate).
    const apply = () => (booted && window.aneeFlip ? window.aneeFlip(grid, paint) : paint());
    envs.forEach((b) => b.addEventListener('click', () => { env = b.dataset.rvEnv; envs.forEach((x) => x.classList.toggle('is-on', x === b)); shown = FIRST; apply(); }));
    ress.forEach((b) => b.addEventListener('click', () => {
        const k = b.dataset.rvRes;
        if (want.has(k)) want.delete(k); else want.add(k);
        b.classList.toggle('is-on', want.has(k)); b.setAttribute('aria-pressed', want.has(k) ? 'true' : 'false');
        shown = FIRST; apply();
    }));
    [type, days, sort].forEach((s) => s.addEventListener('change', () => { shown = FIRST; apply(); }));
    let qt = 0;
    q.addEventListener('input', () => { clearTimeout(qt); qt = setTimeout(() => { shown = FIRST; apply(); }, 120); });
    more.addEventListener('click', () => { shown += STEP * 2; apply(); });
    reset.addEventListener('click', () => {
        q.value = ''; type.value = ''; days.value = ''; sort.value = 'year'; env = ''; want.clear();
        envs.forEach((x) => x.classList.toggle('is-on', x.dataset.rvEnv === '')); ress.forEach((x) => { x.classList.remove('is-on'); x.setAttribute('aria-pressed', 'false'); });
        shown = FIRST; apply();
    });
    apply();
    booted = true;
})();
</script>
@endpush
@endonce
