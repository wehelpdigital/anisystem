{{-- The pest and disease finder (2026-10-07): the crop, then where the
     damage shows (pests) or what is seen (diseases), and the matching pages
     with what to spray. Shared by the public hubs (/pests, /diseases, behind
     the email gate) and the app's Field helpers (no gate: members are in).
     Expects $section, $items, $crops, $askOpts, $words, $isPests, $kindHue
     (ProblemCatalogue::finderFacts) and $gate. --}}
@php $S = \App\Support\SitePages::class; @endphp
@once
@push('head')
    <style>
        /* The finder's answer: each matching pest or disease, its picture and
           its page on top, and under it what to spray (the active ingredients
           and their IRAC or FRAC group), or why no spray helps. */
        .fd-res { display: grid; gap: .65rem; margin-top: .7rem; }
        .fd-card { --g: 98; border-radius: 1rem; background: #f6f8f3; border: 1px solid #e5ebdf; overflow: hidden;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .fd-card:hover { border-color: hsl(var(--g) 40% 70%); background: #fff; }
        .fd-item { display: flex; gap: .8rem; align-items: center; padding: .55rem; text-decoration: none; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .fd-item:hover { transform: translateX(3px); }
        .fd-spray { display: grid; gap: .45rem; padding: .1rem .7rem .7rem; }
        .fd-spray-h { display: inline-flex; align-items: center; gap: .35rem; font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
        .fd-spray-h svg { width: .95rem; height: .95rem; }
        .fd-ais { display: flex; flex-wrap: wrap; gap: .35rem; }
        .fd-ais li { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .35rem .3rem .6rem; border-radius: .7rem; background: #fff; border: 1px solid #e1e9d7;
            font-size: .82rem; font-weight: 700; color: #14210c; line-height: 1.3; }
        .fd-ais li i { font-style: normal; font-size: .64rem; font-weight: 800; padding: .12rem .42rem; border-radius: 999px; color: #2d5016; background: #e4efd4; white-space: nowrap; }
        .fd-ais li.is-plain { padding-right: .6rem; }
        .fd-no { display: flex; gap: .45rem; align-items: flex-start; font-size: .82rem; line-height: 1.5; color: #6b4a00; }
        .fd-no svg { flex: none; width: 1rem; height: 1rem; margin-top: .12rem; color: #c79e00; }
        .fd-wait { margin-top: .7rem; }
        .fd-item img, .fd-item .none { flex: none; width: 4.6rem; height: 3.6rem; border-radius: .7rem; object-fit: cover; background: hsl(var(--g) 35% 88%); }
        .fd-item .none { display: grid; place-items: center; color: hsl(var(--g) 40% 45%); }
        .fd-item .none svg { width: 1.6rem; height: 1.6rem; }
        .fd-item span { min-width: 0; flex: 1; }
        .fd-item b { display: block; font-family: var(--font-heading); font-size: .98rem; font-weight: 800; color: #14210c; line-height: 1.3; }
        .fd-item small { display: block; margin-top: .15rem; font-size: .8rem; line-height: 1.45; color: #4b5563; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .fd-item em { display: inline-block; margin-right: .35rem; padding: .08rem .45rem; border-radius: 999px; font-style: normal; font-size: .62rem; font-weight: 800;
            letter-spacing: .05em; text-transform: uppercase; color: hsl(var(--g) 55% 22%); background: hsl(var(--g) 55% 91%); vertical-align: 1px; }
        .fd-item > svg { flex: none; width: 1rem; height: 1rem; color: #86b556; }
        .fd-count { font-size: .82rem; font-weight: 800; color: #3d6823; }
        .wc-card.is-swap .fd-res > * { animation: wcIn .34s cubic-bezier(.22,1,.36,1) both; }
        .wc-card.is-swap .fd-res > *:nth-child(2) { animation-delay: .04s; } .wc-card.is-swap .fd-res > *:nth-child(3) { animation-delay: .08s; }
        .wc-card.is-swap .fd-res > *:nth-child(4) { animation-delay: .12s; } .wc-card.is-swap .fd-res > *:nth-child(n+5) { animation-delay: .16s; }
        @media (prefers-reduced-motion: reduce) { .fd-item, .fd-card { transition: none; } .wc-card.is-swap .fd-res > * { animation: none; } }
    </style>
@endpush
@endonce
            <div class="wc mt-8">
                <div class="wc-ask">
                    <div class="wc-q">
                        <b><i>1</i>Which crop?</b>
                        <div class="wc-opts" role="radiogroup" aria-label="Which crop?" id="fdCrop">
                            @foreach ($crops as $k => [$local, $en])
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" data-crop="{{ $k }}">{{ $local }}@if ($local !== $en)<small>{{ $en }}</small>@endif</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="wc-q">
                        <b><i>2</i>{{ $words['askWhere'] }}</b>
                        <small>Pick one, or leave it to see all</small>
                        <div class="wc-opts" role="radiogroup" aria-label="{{ $words['askWhere'] }}" id="fdWhere">
                            @foreach ($askOpts as $k => $label)
                                <button type="button" role="radio" aria-checked="false" data-where="{{ $k }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="wc-out" aria-live="polite">
                    <div class="tg" data-tool-gate="{{ $section }}">
                        <div class="wc-card" id="fdCard">
                            <noscript><div class="wc-body"><p>Turn on JavaScript to use the finder, or browse the <a href="#catalogue">catalogue</a>.</p></div></noscript>
                        </div>
                        @if ($gate ?? true)
                            @include('public.site.tool-gate', ['tool' => $section])
                        @endif
                    </div>
                    <p class="wc-fine">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        <span>
                            Many {{ $words['nouns'] }} look alike. Check the signs on each page before you spend on a spray, and when you do, use only products registered with the
                            <a href="{{ $S::url('blog', 'fertilizer-and-pesticide-authority') }}">Fertilizer and Pesticide Authority</a> and follow the label.
                        </span>
                    </p>
                </div>
            </div>
    <script type="application/json" id="fdData">{!! json_encode([
        'items' => $items->map(fn ($w) => ['name' => $w['name'], 'sci' => $w['sci'], 'kind' => $w['kind'], 'crops' => $w['crops'],
            'where' => $isPests ? $w['parts'] : $w['signs'], 'hint' => $w['hint'], 'url' => $w['url'], 'thumb' => $w['thumb'],
            'hue' => $kindHue[$w['kind']] ?? 98, 'ai' => $w['ai'] ?? [], 'no' => $w['no'] ?? ''])->values(),
        'crops' => collect($crops)->map(fn ($c) => $c[1]),
        'where' => $askOpts,
        'noun' => $words['noun'], 'nouns' => $words['nouns'], 'pests' => $isPests,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The finder: the crop, then where the damage shows (pests) or what is
       seen (diseases); the matching pages come back as rows. A second step
       with nothing for that crop is greyed out. */
    const el = document.getElementById('fdData'), card = document.getElementById('fdCard');
    if (!el || !card) return;
    const D = JSON.parse(el.textContent);
    const cropBox = document.getElementById('fdCrop'), whereBox = document.getElementById('fdWhere');
    const params = new URLSearchParams(location.search);
    const st = { crop: D.crops[params.get('crop')] ? params.get('crop') : Object.keys(D.crops)[0], where: '' };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const arrow = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
    const leaf = '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg>';
    const drop = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M12 3.5s6 6.4 6 10.6a6 6 0 01-12 0c0-4.2 6-10.6 6-10.6z"/></svg>';
    const warn = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>';
    // What to spray against one pest or disease: its active ingredients with
    // their group, or the one line that says why no spray helps.
    const spray = (i) => (i.ai && i.ai.length)
        ? '<div class="fd-spray"><span class="fd-spray-h">' + drop + 'What to spray</span><ul class="fd-ais">'
            + i.ai.map(([n, g]) => '<li' + (g ? '' : ' class="is-plain"') + '>' + esc(n) + (g ? '<i>' + esc(g) + '</i>' : '') + '</li>').join('') + '</ul></div>'
        : (i.no ? '<div class="fd-spray"><p class="fd-no">' + warn + '<span>' + esc(i.no) + '</span></p></div>' : '');
    const render = (swap) => {
        const forCrop = D.items.filter((i) => i.crops.includes(st.crop));
        // IRRI and PhilRice: no insecticide on young palay unless a pest is at outbreak level.
        const early = D.pests && st.crop === 'rice'
            ? '<p class="wc-note">Palay younger than 30 to 40 days after transplanting: hold the insecticide unless a pest is at outbreak level. Early sprays kill the spiders and wasps that protect your crop.</p>'
            : '';
        cropBox.querySelectorAll('button').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.crop === st.crop)));
        whereBox.querySelectorAll('button').forEach((b) => {
            const has = forCrop.some((i) => i.where.includes(b.dataset.where));
            b.disabled = !has;
            if (!has && st.where === b.dataset.where) st.where = '';
            b.setAttribute('aria-checked', String(b.dataset.where === st.where));
        });
        const hits = st.where ? forCrop.filter((i) => i.where.includes(st.where)) : forCrop;
        const head = st.where ? D.where[st.where] : 'Every ' + D.noun + ' we cover';
        card.innerHTML = '<div class="wc-when"><span class="wc-timing">' + esc(D.crops[st.crop]) + '</span><b>' + esc(head) + '</b>'
            + '<small>' + hits.length + ' ' + (hits.length === 1 ? D.noun : D.nouns) + (st.where ? '' : '. Pick what you see to narrow it down.') + '</small></div>'
            + '<div class="wc-body">'
            + (hits.length
                ? early + '<div class="fd-res' + (early ? ' fd-wait' : '') + '">' + hits.map((i) => '<div class="fd-card" style="--g: ' + i.hue + '"><a class="fd-item" href="' + esc(i.url) + '">'
                    + (i.thumb ? '<img src="' + esc(i.thumb) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '<span class="none">' + leaf + '</span>')
                    + '<span><b>' + esc(i.name) + '</b><small><em>' + esc(i.kind) + '</em>' + esc(i.hint) + '</small></span>' + arrow + '</a>'
                    + spray(i) + '</div>').join('') + '</div>'
                    + '<p class="wc-legend">' + (D.pests ? 'The IRAC group tells how an insecticide kills.' : 'The FRAC group tells how a fungicide works.')
                    + ' Switch to a different group next time, so the ' + D.noun + ' does not learn to survive it. Open a ' + D.noun + ' for when to spray and the steps to take first.</p>'
                : '<p class="wc-note ok">Nothing in our catalogue matches that yet. Send a photo to Anee, the smart farm technician, and she will tell you what it most likely is.</p>')
            + '</div>';
        if (swap) { card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap'); }
    };
    cropBox.addEventListener('click', (e) => { const b = e.target.closest('button[data-crop]'); if (b && b.dataset.crop !== st.crop) { st.crop = b.dataset.crop; render(true); } });
    whereBox.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-where]');
        if (!b || b.disabled) return;
        st.where = st.where === b.dataset.where ? '' : b.dataset.where;
        render(true);
    });
    render(false);
})();
</script>
@endpush
