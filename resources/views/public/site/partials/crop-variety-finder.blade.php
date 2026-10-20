{{-- One crop's variety finder (2026-10-13): every variety of the crop as a
     card, with type chips, an area and a sort select and a search box that
     hide and order them. On /crop-varieties/{crop} and in the app's field
     helpers (FieldHelperController). Every card is in the page, so search
     engines read every link; the script only hides and sorts. Needs
     $varieties (App\Support\CropVarieties::forCrop()) and $crop (one row of
     CropVarieties::crops()). --}}
@php
    $S = \App\Support\SitePages::class;
    $CV = \App\Support\CropVarieties::class;
    $all = collect($varieties);
    $fmt = fn ($x, $d = 1) => $d > 0 ? rtrim(rtrim(number_format((float) $x, $d, '.', ''), '0'), '.') : number_format((float) $x, 0, '.', '');
    $cardTarget = $cardTarget ?? null;
    // The members' gate on /crop-varieties/{crop} (2026-10-14): the card's
    // words when a guest is looking, else null (partials/hub-gate).
    $hubGate = $hubGate ?? null;
    // The type chips: corn by its kinds, every other crop by the kinds it has.
    $chipNames = ['hybrid' => 'Yellow hybrid', 'gm' => 'GM hybrid', 'white-hybrid' => 'White hybrid', 'opv' => 'Open pollinated', 'glutinous' => 'Glutinous', 'sweet' => 'Sweet corn'];
    if ($crop['key'] === 'corn') {
        $chips = collect($chipNames)->map(fn ($label, $k) => [$label, $all->filter(fn ($v) => $CV::chip($v) === $k)->count()])->filter(fn ($c) => $c[1] > 0);
        $chipOf = fn ($v) => $CV::chip($v);
    } else {
        $kinds = $all->map(fn ($v) => $CV::kind($v))->filter()->countBy()->sortDesc();
        $chips = $kinds->count() >= 2 && $kinds->count() <= 7
            ? $kinds->mapWithKeys(fn ($n, $k) => [\Illuminate\Support\Str::slug($k) => [$k, $n]])
            : collect();
        $chipOf = fn ($v) => \Illuminate\Support\Str::slug($CV::kind($v));
    }
    $unit = $all->pluck('maturity.unit')->filter()->countBy()->sortDesc()->keys()->first() ?? 'days';
    $yUnit = $all->pluck('yield.unit')->filter()->countBy()->sortDesc()->keys()->first();
    $hasDom = $all->filter(fn ($v) => ! empty($v['domain']))->count() >= 3;
    $hasDays = $all->filter(fn ($v) => ! empty($v['maturity']))->count() >= 3;
    $hasYield = $all->filter(fn ($v) => isset($v['yield']['value']))->count() >= 3;
    $cropName = strtolower($crop['name']);
    // A short line of facts for a card with no day count or yield.
    $factLine = function ($v) {
        $want = ['/^(whole )?fruit weight/i' => 'Fruit %s g', '/sweetness/i' => 'Sweetness %s Brix', '/^flesh color/i' => '%s flesh', '/dry matter/i' => 'Dry matter %s',
            '/^nuts\/palm/i' => '%s nuts a palm', '/^kernel weight/i' => 'Kernel %s g', '/^pod length/i' => 'Pods %s cm'];
        $out = [];
        foreach ($want as $re => $f) {
            foreach ($v['traits'] ?? [] as [$lab, $val]) {
                if (preg_match($re, $lab) && $val !== '' && count($out) < 3) {
                    $val = is_numeric($val) ? ((float) $val >= 100 ? number_format((float) $val) : rtrim(rtrim(number_format((float) $val, 1, '.', ''), '0'), '.')) : $val;
                    $out[] = sprintf($f, $val);
                    break;
                }
            }
        }

        return implode(' · ', $out);
    };
@endphp
@include('public.site.partials.variety-finder-css')

            <div class="rv-tools" data-cv-tools>
                <label class="wk-search" style="max-width: none">
                    <span class="sr-only">Search a {{ $cropName }} variety</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" data-cv-q placeholder="Search a name, brand or company" autocomplete="off">
                </label>
                @if ($chips->count())
                <div class="rv-row">
                    <span class="rv-lab" id="cvTypeL">Type</span>
                    <div class="rv-chips" role="group" aria-labelledby="cvTypeL">
                        <button type="button" class="rv-chip is-on" data-cv-chip="">All <small>{{ $all->count() }}</small></button>
                        @foreach ($chips as $k => [$label, $n])
                            <button type="button" class="rv-chip" data-cv-chip="{{ $k }}">{{ $label }} <small>{{ $n }}</small></button>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="rv-two">
                    @if ($hasDom)
                    <label class="rv-row"><span class="rv-lab">Recommended for</span>
                        <select class="rv-sel" data-cv-dom><option value="">Anywhere</option><option value="luzon">Luzon</option><option value="visayas">Visayas</option><option value="mindanao">Mindanao</option></select></label>
                    @endif
                    @if ($hasDays)
                    <label class="rv-row"><span class="rv-lab">{{ $unit === 'months' ? 'Months' : 'Days' }} to harvest</span>
                        <select class="rv-sel" data-cv-days><option value="">Any</option><option value="short">Shortest third</option><option value="mid">Middle third</option><option value="long">Longest third</option></select></label>
                    @endif
                    <label class="rv-row"><span class="rv-lab">Sort by</span>
                        <select class="rv-sel" data-cv-sort><option value="year">Newest first</option>@if ($hasYield)<option value="yield">Highest yield</option>@endif @if ($hasDays)<option value="days">Fewest {{ $unit }} to harvest</option>@endif<option value="name">Name</option></select></label>
                </div>
            </div>
            <div class="rv-count" aria-live="polite"><span><b data-cv-n>{{ $all->count() }}</b> of {{ $all->count() }} {{ $cropName }} varieties</span><button type="button" class="rv-reset" data-cv-reset hidden>Clear the filters</button></div>
            {{-- Where the yields on the cards come from, and why a farm's harvest can differ (2026-10-15). --}}
            @if ($all->contains(fn ($v) => isset($v['yield']['value'])))
                @include('partials.yield-note')
            @endif

            @if ($hubGate)<div class="hg" data-hg data-hg-one="variety" data-hg-many="varieties">@endif
            <div class="rv-grid hg-grid" data-cv-grid data-hg-grid data-hg-card=".rv-card">
                @foreach ($all as $i => $v)
                    @php
                        $kind = $CV::kind($v);
                        $days = $CV::days($v);
                        $y = $v['yield'] ?? null;
                        $alias = array_slice($v['aliases'] ?? [], 0, 2);
                        $dom = in_array('National', $v['domain'] ?? [], true) ? 'luzon visayas mindanao national' : strtolower(implode(' ', $v['domain'] ?? []));
                        $res = collect($v['reactions'] ?? [])->filter(fn ($r) => in_array($r[1], ['HR', 'R', 'MR', 'T', 'MS', 'S', 'HS'], true))->take(4);
                        $q = strtolower(implode(' ', array_filter([$v['name'], str_replace([' ', '-'], '', $v['name']), implode(' ', $v['aliases'] ?? []), str_replace([' ', '-'], '', implode(' ', $v['aliases'] ?? [])), $v['localName'] ?? '', $v['owner'] ?? '', $v['reg'] ?? '', $kind])));
                        $isHybrid = str_contains(strtolower($kind), 'hybrid');
                    @endphp
                    <a href="{{ $S::url('crop-varieties', $v['slug']) }}" @if ($cardTarget) target="{{ $cardTarget }}" @endif class="rv-card {{ $isHybrid ? 'is-hybrid' : '' }}"
                       data-i="{{ $i }}" data-q="{{ $q }}" data-chip="{{ $chipOf($v) }}" data-dom="{{ $dom }}"
                       data-days="{{ $days ?? '' }}" data-yield="{{ $y['value'] ?? 0 }}" data-year="{{ $v['year'] ?? 0 }}"
                       @if ($i >= 24) hidden @endif>
                        <span class="rv-top">
                            <span><span class="rv-name">{{ $v['name'] }}</span>@if ($alias)<span class="rv-trade">{{ implode(', ', $alias) }}</span>@endif @if (! empty($v['localName']))<span class="rv-local">{{ $v['localName'] }}</span>@endif</span>
                            <span class="rv-year">{{ $v['year'] ?? 'Seller' }}</span>
                        </span>
                        <span class="rv-tags">
                            @if ($kind)<span class="rv-tag {{ $isHybrid ? 'is-h' : '' }}">{{ $kind }}</span>@endif
                            @if (! empty($v['domain']))<span class="rv-tag">{{ in_array('National', $v['domain'], true) ? 'Nationwide' : implode(', ', $v['domain']) }}</span>@endif
                            @if (empty($v['reg']))<span class="rv-tag is-g">Not on the registry</span>@endif
                        </span>
                        @if ($days || isset($y['value']))
                        <span class="rv-nums">
                            <span><b>{{ $days ?? 'n/a' }}</b><span>{{ ($v['maturity']['unit'] ?? $unit) === 'months' ? 'months' : 'days' }}</span></span>
                            <span><b>{{ isset($y['value']) ? $fmt($y['value'], ($y['unit'] ?? '') === 't/ha' ? 1 : 0) : 'n/a' }}</b><span>{{ ($y['unit'] ?? $yUnit) ?: 'yield' }}</span></span>
                            <span><b>{{ isset($v['height']['value']) ? $fmt($v['height']['value'], 0) : 'n/a' }}</b><span>{{ ($v['height']['unit'] ?? 'cm') === 'm' ? 'm tall' : 'cm tall' }}</span></span>
                        </span>
                        @elseif ($line = $factLine($v))
                            <span class="rv-nodata">{{ $line }}</span>
                        @else
                            <span class="rv-nodata">{{ empty($v['reg']) ? 'Facts from its seller, on its page' : 'Traits and ratings on its page' }}</span>
                        @endif
                        @if ($res->count())
                            <span class="rv-res">
                                @foreach ($res as [$lab, $c])
                                    <i class="{{ in_array($c, ['HR', 'R', 'MR', 'T'], true) ? 'r' : 's' }}" title="{{ $lab }}: {{ $c }}">{{ \Illuminate\Support\Str::limit($lab, 16, '') }} {{ $c }}</i>
                                @endforeach
                            </span>
                        @endif
                        {{-- Where its facts come from (2026-10-21, the owner); linked on its page. --}}
                        <span class="rv-src">Source: {{ \App\Support\VarietySources::line(\App\Support\VarietySources::crop($v)) }}</span>
                    </a>
                @endforeach
            </div>
            <p class="rv-none" data-cv-none hidden>No {{ $cropName }} variety matches all of these. Clear a filter or two.</p>
            <button type="button" class="rv-more" data-cv-more>Show more varieties</button>
            @if ($hubGate)
                @include('public.site.partials.content-gate-card', $hubGate)
            </div>
            @endif

@once
@push('scripts')
<script>
(() => {
    /* The crop finder: chips, selects and the search box hide the cards that
       do not match and order the rest; 24 show at first, more on a tap. */
    const grid = document.querySelector('[data-cv-grid]');
    if (!grid) return;
    const $ = (s) => document.querySelector(s);
    const cards = [...grid.querySelectorAll('.rv-card')];
    const q = $('[data-cv-q]'), dom = $('[data-cv-dom]'), days = $('[data-cv-days]'), sort = $('[data-cv-sort]');
    const more = $('[data-cv-more]'), none = $('[data-cv-none]'), nOut = $('[data-cv-n]'), reset = $('[data-cv-reset]');
    const chips = [...document.querySelectorAll('[data-cv-chip]')];
    const STEP = window.matchMedia('(max-width: 639.98px)').matches ? 12 : 24;
    // Behind the members' gate every match is in order and the gate decides
    // what shows (partials/hub-gate).
    const gated = !!grid.closest('[data-hg]'), FIRST = gated ? 1e9 : STEP;
    // The thirds of the day counts, for the short, middle and long choices.
    const ds = cards.map((c) => Number(c.dataset.days || 0)).filter(Boolean).sort((a, b) => a - b);
    const t1 = ds[Math.floor(ds.length / 3)] || 0, t2 = ds[Math.floor(ds.length * 2 / 3)] || 0;
    let chip = '', shown = FIRST;
    const norm = (s) => s.toLowerCase().replace(/\s+/g, ' ').trim();
    let booted = false;
    const paint = () => {
        const t = norm(q.value), tc = t.replace(/[\s-]/g, ''), d = days ? days.value : '', a = dom ? dom.value : '';
        const ok = cards.filter((c) => {
            if (t && !(c.dataset.q.includes(t) || c.dataset.q.includes(tc))) return false;
            if (chip && c.dataset.chip !== chip) return false;
            if (a && !c.dataset.dom.split(' ').includes(a)) return false;
            const dd = Number(c.dataset.days || 0);
            if (d && !dd) return false;
            if (d === 'short' && dd > t1) return false;
            if (d === 'mid' && (dd <= t1 || dd > t2)) return false;
            if (d === 'long' && dd <= t2) return false;
            return true;
        });
        const by = sort.value;
        ok.sort((x, y) => by === 'yield' ? y.dataset.yield - x.dataset.yield : by === 'days' ? (Number(x.dataset.days) || 9999) - (Number(y.dataset.days) || 9999)
            : by === 'name' ? x.querySelector('.rv-name').textContent.localeCompare(y.querySelector('.rv-name').textContent, undefined, { numeric: true })
            : (y.dataset.year - x.dataset.year) || (x.dataset.i - y.dataset.i));
        cards.forEach((c) => { c.hidden = true; });
        ok.forEach((c, i) => { grid.appendChild(c); c.hidden = i >= shown; });
        nOut.textContent = ok.length;
        none.hidden = ok.length > 0;
        more.hidden = gated || ok.length <= shown;
        reset.hidden = !(t || chip || d || a || by !== 'year');
        window.aneeHubGate?.(grid);
    };
    // After the first paint every change moves the cards (partials/hub-gate).
    const apply = () => (booted && window.aneeFlip ? window.aneeFlip(grid, paint) : paint());
    chips.forEach((b) => b.addEventListener('click', () => { chip = b.dataset.cvChip; chips.forEach((x) => x.classList.toggle('is-on', x === b)); shown = FIRST; apply(); }));
    [dom, days, sort].filter(Boolean).forEach((s) => s.addEventListener('change', () => { shown = FIRST; apply(); }));
    let qt = 0;
    q.addEventListener('input', () => { clearTimeout(qt); qt = setTimeout(() => { shown = FIRST; apply(); }, 120); });
    more.addEventListener('click', () => { shown += STEP * 2; apply(); });
    reset.addEventListener('click', () => {
        q.value = ''; chip = ''; sort.value = 'year';
        if (dom) dom.value = '';
        if (days) days.value = '';
        chips.forEach((x) => x.classList.toggle('is-on', x.dataset.cvChip === ''));
        shown = FIRST; apply();
    });
    // A search carried in the address (?q=), from the crop list's search box.
    const pre = new URLSearchParams(location.search).get('q');
    if (pre) q.value = pre;
    apply();
    booted = true;
})();
</script>
@endpush
@endonce
