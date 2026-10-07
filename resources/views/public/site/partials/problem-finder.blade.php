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
        /* One row: the picture, the name with its local names and kind, the
           arrow; under them, the whole sign to look for (it is how a farmer
           checks this is the one, so it is never cut short). */
        .fd-item { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr) auto; grid-template-areas: "pic name go" "sign sign sign"; column-gap: .8rem; row-gap: .55rem;
            align-items: center; padding: .55rem .6rem .45rem .55rem; text-decoration: none; }
        .fd-item > img, .fd-item > .none { grid-area: pic; width: 4.6rem; height: 3.6rem; border-radius: .7rem; object-fit: cover; background: hsl(var(--g) 35% 88%); }
        .fd-item > .none { display: grid; place-items: center; color: hsl(var(--g) 40% 45%); }
        .fd-item > .none svg { width: 1.6rem; height: 1.6rem; }
        .fd-t { grid-area: name; min-width: 0; }
        .fd-item b { display: block; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: #14210c; line-height: 1.3; }
        .fd-meta { display: block; margin-top: .2rem; font-size: .8rem; line-height: 1.45; color: #4b5563; }
        .fd-item em { display: inline-block; margin-right: .35rem; padding: .08rem .45rem; border-radius: 999px; font-style: normal; font-size: .64rem; font-weight: 800;
            letter-spacing: .05em; text-transform: uppercase; color: hsl(var(--g) 55% 22%); background: hsl(var(--g) 55% 91%); vertical-align: 1px; }
        .fd-loc { font-style: italic; }
        .fd-item > svg { grid-area: go; width: 1.1rem; height: 1.1rem; color: #86b556; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .fd-item:hover > svg { transform: translateX(3px); }
        .fd-sign { grid-area: sign; display: flex; gap: .45rem; align-items: flex-start; font-size: .86rem; line-height: 1.5; color: #374151; }
        .fd-sign svg { flex: none; width: 1rem; height: 1rem; margin-top: .14rem; color: #6b9f3d; }
        .fd-spray { display: grid; gap: .45rem; padding: .15rem .7rem .7rem; }
        .fd-spray-h { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
        .fd-spray-h svg { width: .95rem; height: .95rem; }
        .fd-ais { display: flex; flex-wrap: wrap; gap: .35rem; }
        .fd-ais li { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .35rem .3rem .6rem; border-radius: .7rem; background: #fff; border: 1px solid #e1e9d7;
            font-size: .86rem; font-weight: 700; color: #14210c; line-height: 1.3; }
        .fd-ais li i { font-style: normal; font-size: .68rem; font-weight: 800; padding: .12rem .42rem; border-radius: 999px; color: #2d5016; background: #e4efd4; white-space: nowrap; }
        .fd-ais li.is-plain { padding-right: .6rem; }
        /* "Before you spray": the advice not to spray, in the same amber as
           the young palay warning, so it reads as a warning, not a footnote. */
        .fd-no { display: flex; gap: .5rem; align-items: flex-start; padding: .6rem .7rem; border-radius: .8rem; font-size: .86rem; line-height: 1.5; color: #6b4a00;
            background: #fff8e6; border: 1px solid #f5d98a; }
        .fd-no b { display: block; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #8a5a00; }
        .fd-no svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .1rem; color: #c79e00; }
        .fd-wait { margin-top: .7rem; }
        .fd-count { font-size: .82rem; font-weight: 800; color: #3d6823; }
        .wc-card.is-swap .fd-res > * { animation: wcIn .34s cubic-bezier(.22,1,.36,1) both; }
        .wc-card.is-swap .fd-res > *:nth-child(2) { animation-delay: .04s; } .wc-card.is-swap .fd-res > *:nth-child(3) { animation-delay: .08s; }
        .wc-card.is-swap .fd-res > *:nth-child(4) { animation-delay: .12s; } .wc-card.is-swap .fd-res > *:nth-child(n+5) { animation-delay: .16s; }
        @media (prefers-reduced-motion: reduce) { .fd-item > svg, .fd-card { transition: none; } .wc-card.is-swap .fd-res > * { animation: none; } }
        /* In the app at night (Field helpers): the amber box and the sign keep their contrast. */
        html.dark .fh-body .fd-no { background: #241d10; border-color: #5c4a24; color: #f3d9a4; }
        html.dark .fh-body .fd-no b { color: #f5c518; }
        html.dark .fh-body .fd-item .fd-sign { color: #d5e3c5; }
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
                    {{-- Below two columns the answer is under these questions: this says what waits there. --}}
                    <button type="button" class="wc-go" id="fdGo" aria-controls="fdCard"><span>See the {{ $words['nouns'] }}</span>
                        <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
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
        'items' => $items->map(fn ($w) => ['name' => $w['name'], 'sci' => $w['sci'], 'local' => $w['local'] ?? '', 'kind' => $w['kind'], 'crops' => $w['crops'],
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
    const eye = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/></svg>';
    const go = document.getElementById('fdGo');
    const goText = go?.querySelector('span');
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    // What to spray against one pest or disease: its active ingredients with
    // their group, or, under "Before you spray", why no spray helps.
    const spray = (i) => (i.ai && i.ai.length)
        ? '<div class="fd-spray"><span class="fd-spray-h">' + drop + 'What to spray</span><ul class="fd-ais">'
            + i.ai.map(([n, g]) => '<li' + (g ? '' : ' class="is-plain"') + '>' + esc(n) + (g ? '<i>' + esc(g) + '</i>' : '') + '</li>').join('') + '</ul></div>'
        : (i.no ? '<div class="fd-spray"><p class="fd-no">' + warn + '<span><b>Before you spray</b>' + esc(i.no) + '</span></p></div>' : '');
    // The local names a farmer would say, when they are names and not a
    // sentence about names (those stay on the page), and not already in the title.
    const localOf = (i) => {
        const s = String(i.local || '').trim(), first = s.split(',')[0].trim();
        if (/\b(is|are|call|called|means)\b/i.test(s)) return '';
        const pick = s.length <= 60 ? s : (first.length <= 48 ? first : '');
        return pick && !i.name.toLowerCase().includes(pick.toLowerCase()) ? pick : '';
    };
    const row = (i) => {
        const loc = localOf(i);
        return '<div class="fd-card" style="--g: ' + i.hue + '"><a class="fd-item" href="' + esc(i.url) + '">'
            + (i.thumb ? '<img src="' + esc(i.thumb) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '<span class="none">' + leaf + '</span>')
            + '<span class="fd-t"><b>' + esc(i.name) + '</b><small class="fd-meta"><em>' + esc(i.kind) + '</em>' + (loc ? '<span class="fd-loc">' + esc(loc) + '</span>' : '') + '</small></span>'
            + arrow
            + (i.hint ? '<small class="fd-sign">' + eye + '<span>' + esc(i.hint) + '</span></small>' : '')
            + '</a>' + spray(i) + '</div>';
    };
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
                ? early + '<div class="fd-res' + (early ? ' fd-wait' : '') + '">' + hits.map(row).join('') + '</div>'
                    + '<p class="wc-legend">' + (D.pests ? 'The IRAC group tells how an insecticide kills.' : 'The FRAC group tells how a fungicide works.')
                    + ' Switch to a different group next time, so the ' + D.noun + ' does not learn to survive it. Open a ' + D.noun + ' for when to spray and the steps to take first.</p>'
                : '<p class="wc-note ok">Nothing in our catalogue matches that yet. Send a photo to Anee, the smart farm technician, and she will tell you what it most likely is.</p>')
            + '</div>';
        if (swap) { card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap'); }
        if (goText) {
            goText.textContent = hits.length ? 'See the ' + hits.length + ' ' + (hits.length === 1 ? D.noun : D.nouns) : 'See the answer';
            if (swap) { go.classList.remove('is-bump'); void go.offsetWidth; go.classList.add('is-bump'); }
        }
    };
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' }));
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
