@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Growth Stages — ' . $schedule->title)
@section('page-title', 'Growth Stages')
@section('page-subtitle', $schedule->title)
@section('help-key', 'growth')
@section('back', \App\Support\BackTo::url(route('sm.hub', ['id' => $schedule->id]), $schedule->id))

@push('head')
<style>
    /* One card per lot, and each card reads top to bottom as an answer:
       where the crop is, what that means, what to do, what to watch, and
       where it sits in the season. */
    .gr-date { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .85rem; }
    .gr-date-lbl { font-size: .78rem; font-weight: 700; color: var(--color-gray-500); }
    /* The date is a tag, not a form field: tap it and the picker comes up.
       The real input rides along invisibly — the label forwards the tap to
       it, and the input is what knows how to show a calendar. */
    .gr-date-tag { position: relative; display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .85rem; border-radius: 999px; border: 1px solid #cfe3b8; background: #f0f7e8;
        font-size: .8rem; font-weight: 700; color: #3d6823; cursor: pointer; user-select: none;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            transform .28s cubic-bezier(.22,1,.36,1); }
    .gr-date-tag:hover { background: #e4efd4; border-color: #b7d597; }
    .gr-date-tag:active { transform: scale(.96); }
    .gr-date-tag svg { width: .95rem; height: .95rem; }
    .gr-date-tag input[type="date"] { position: absolute; inset: 0; opacity: 0; pointer-events: none; }
    html.dark .gr-date-tag { background: rgb(61 104 35 / .25); border-color: #3f5626; color: #bfe19a; }
    html.dark .gr-date-tag:hover { background: rgb(61 104 35 / .4); }
    @media (prefers-reduced-motion: reduce) { .gr-date-tag { transition: none; } }

    /* The card wears the Tip of the Day's clothes: the same deep-green
       gradient ground, light words, and the slow drifting glow — one
       committed look in both modes, the way the tip card is. The stage
       chip and the progress bar wear the SAME deep green, animated —
       the per-stage colour bands (soil brown, water teal, flowering gold)
       read as random next to each other and are gone by request. */
    .gr-stage-chip { display: inline-flex; align-items: center; max-width: 100%;
        margin-top: .3rem; padding: .18rem .6rem; border-radius: 999px;
        font-size: .68rem; font-weight: 800; letter-spacing: .01em;
        color: #dcedc8; border: 1px solid rgb(168 204 126 / .3);
        background: linear-gradient(115deg, #223618, #3d6823, #4a7c2a, #223618);
        background-size: 280% 100%;
        animation: grTide 7s ease-in-out infinite alternate; }
    @keyframes grTide { from { background-position: 0% 0; } to { background-position: 100% 0; } }
    @media (prefers-reduced-motion: reduce) { .gr-stage-chip { animation: none; } }
    /* The deep-green gradient belongs to the HEADER alone: the fold below it
       opens onto the plain card ground, where a paragraph is actually
       readable. The header keeps the Tip-of-the-Day look — the same header
       in light and dark mode — and the body follows the app's theme. */
    .gr-card { position: relative; border: 1px solid var(--color-gray-200); border-radius: 1rem;
        overflow: hidden; margin-bottom: .9rem;
        background: var(--color-white);
        box-shadow: 0 16px 40px -30px rgb(16 22 12 / .35); }
    .gr-top, .gr-fold { position: relative; }
    .gr-top { display: flex; align-items: center; gap: .7rem; padding: .8rem .9rem;
        cursor: pointer; user-select: none; overflow: hidden; color: #e8efe1;
        background: linear-gradient(135deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    /* The tip card's slow sweep of light, borrowed whole (its keyframes live
       with the tip partial, which this page does not include — so the sweep
       is restated here under its own name). */
    .gr-top::before { content: ''; position: absolute; inset: -40% -10%; pointer-events: none;
        background: radial-gradient(closest-side, rgb(134 181 86 / .28), transparent 70%);
        animation: grGlow 7s ease-in-out infinite; }
    @keyframes grGlow {
        0%, 100% { transform: translateX(-30%) scale(.9); opacity: .5; }
        50% { transform: translateX(30%) scale(1.1); opacity: .85; }
    }
    @media (prefers-reduced-motion: reduce) { .gr-top::before { animation: none; } }
    .gr-top:hover { filter: brightness(1.12); }
    .gr-top > * { position: relative; }
    /* Accordion, the same one the activities board uses: a lot folds down to
       its header, the chevron flags state, and max-height carries the fold —
       the shared concertina in app.js supplies the slide. */
    .gr-chev { width: 1rem; height: 1rem; flex-shrink: 0; color: #a8cc7e; transition: transform .18s ease; }
    .gr-card:not(.is-folded) .gr-chev { transform: rotate(90deg); }
    .gr-fold { overflow: hidden; transition: max-height .28s cubic-bezier(.22,1,.36,1); }
    /* Sliding and fading together, as every other fold in the app now
       does. The slide alone leaves the contents at full strength against a
       shutting edge, which reads as a clip rather than a movement. */
    .gr-fold-inner { min-height: 0; opacity: 1;
        transition: opacity .22s ease; }
    .gr-card.is-folded .gr-fold-inner { opacity: 0; }
    .gr-card.is-folded .gr-fold { max-height: 0; }
    /* Folded, the header answers for the body: the chip already names the
       stage, so a folded page reads lot | chip | day and only the counter
       explainer steps aside. */
    .gr-card.is-folded .gr-mode { display: none; }
    /* Restoring the remembered folds on load applies instantly. */
    #grCards.no-fold-anim .gr-fold, #grCards.no-fold-anim .gr-chev,
    #grCards.no-fold-anim .gr-fold-inner { transition: none; }
    @media (prefers-reduced-motion: reduce) { .gr-fold, .gr-chev { transition: none; } }
    /* Collapse all leads the row rather than trailing it: it acts on
       everything below, and a control for the whole list belongs at the
       start of the line the list begins on. */
    .gr-foldall { margin-right: auto; }
    .gr-emoji { font-size: 1.7rem; line-height: 1; }
    .gr-lot { font-size: .98rem; font-weight: 800; color: #f1f6ec; }
    .gr-mode { display: block; font-size: .68rem; color: #8fa383; margin-top: .1rem; }
    .gr-crop { font-size: .72rem; font-weight: 700; color: #cdd8c0; }
    .gr-age { margin-left: auto; text-align: right; flex: 0 0 auto; }
    .gr-age-n { font-size: 1.35rem; font-weight: 800; line-height: 1; color: #d6e8bf; }
    .gr-age-l { font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #9dc178; }

    /* The body reads on the plain card ground — tokens, so both themes come
       out right; the greens go deep in the light theme and pale in the dark
       one, the same trade the amber note below already makes. */
    .gr-body { padding: .85rem .9rem; }
    .gr-stage { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .gr-what { font-size: .85rem; line-height: 1.5; color: var(--color-gray-600); margin-top: .2rem; }
    /* The one line of guidance a patterned crop carries. Same shape as the
       do-list below it, so a crop with the short answer and a crop with the
       long one read as the same kind of page. */
    .gr-needs { font-size: .84rem; line-height: 1.5; margin-top: .7rem;
        padding: .6rem .7rem; border-radius: .7rem;
        background: rgb(107 159 61 / .14); color: #3f6220; }
    html.dark .gr-needs { background: rgb(107 159 61 / .18); color: #cfe6b5; }
    .gr-needs b { font-weight: 800; }
    .gr-bar { height: .4rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .6rem; }
    .gr-bar span { display: block; height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #4a7c2a, #a8cc7e, #4a7c2a);
        background-size: 220% 100%;
        animation: grTide 7s ease-in-out infinite alternate; }
    @media (prefers-reduced-motion: reduce) { .gr-bar span { animation: none; } }
    .gr-next { font-size: .72rem; color: var(--color-gray-500); margin-top: .3rem; }

    .gr-lists { display: grid; gap: .5rem; margin-top: .8rem; }
    @media (min-width: 720px) { .gr-lists { grid-template-columns: 1fr 1fr; } }
    .gr-list { border-radius: .8rem; padding: .65rem .75rem; }
    .gr-list h4 { font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        display: flex; align-items: center; gap: .35rem; margin-bottom: .35rem; }
    .gr-list ul { display: grid; gap: .3rem; }
    .gr-list li { font-size: .8rem; line-height: 1.45; display: flex; gap: .4rem; }
    .gr-list li::before { content: ''; flex: 0 0 auto; width: .35rem; height: .35rem; border-radius: 999px;
        margin-top: .5rem; background: currentColor; opacity: .5; }
    .gr-do { background: rgb(107 159 61 / .14); color: #3f6220; }
    .gr-watch { background: rgb(217 130 20 / .12); color: #92400e; }
    html.dark .gr-do { background: rgb(107 159 61 / .2); color: #cfe6b5; }
    html.dark .gr-watch { background: rgb(217 130 20 / .18); color: #f3c08a; }

    .gr-steps { margin-top: .85rem; border-top: 1px dashed var(--color-gray-200); padding-top: .65rem; display: grid; gap: .3rem; }
    .gr-step { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; color: var(--color-gray-500); }
    .gr-dot { flex: 0 0 auto; width: .6rem; height: .6rem; border-radius: 999px; margin-top: .35rem; background: var(--color-gray-300); }
    .gr-step.is-past .gr-dot { background: #6b9f3d; }
    .gr-step.is-now { color: var(--color-gray-900); font-weight: 700; }
    .gr-step.is-now .gr-dot { background: #6b9f3d; box-shadow: 0 0 0 3px rgb(107 159 61 / .25); }
    html.dark .gr-step.is-past .gr-dot, html.dark .gr-step.is-now .gr-dot { background: #a8cc7e; }
    .gr-when { margin-left: auto; flex: 0 0 auto; font-variant-numeric: tabular-nums; opacity: .7; }

    .gr-card.is-refreshed { animation: grFresh 1.1s cubic-bezier(.22,1,.36,1); }
    @keyframes grFresh { 0% { box-shadow: 0 0 0 0 rgb(107 159 61 / .0); } 25% { box-shadow: 0 0 0 4px rgb(107 159 61 / .35); } 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / 0); } }
    @media (prefers-reduced-motion: reduce) { .gr-card.is-refreshed { animation: none; } }
    .gr-blocked { padding: .9rem; font-size: .83rem; line-height: 1.5; color: var(--color-gray-600);
        background: var(--color-gray-100); border-radius: .7rem; }
    .gr-note { display: flex; gap: .6rem; align-items: flex-start; margin: .2rem 0 .6rem;
        padding: .7rem .8rem; border-radius: .8rem; background: #fffbeb; border: 1px solid #fde68a; }
    .gr-note p { font-size: .78rem; line-height: 1.5; color: #92400e; margin: 0; }
    .gr-note-ico { flex: 0 0 auto; color: #b45309; }
    .gr-note-ico svg { width: 1.1rem; height: 1.1rem; }
    html.dark .gr-note { background: rgb(180 83 9 / .16); border-color: rgb(180 83 9 / .45); }
    html.dark .gr-note p { color: #fcd34d; }
    html.dark .gr-note-ico { color: #fcd34d; }

    /* One committed look for the HEADER: the deep-green band is the same in
       light and dark mode, exactly as the Tip of the Day is. The body under
       it follows the app's theme, because guidance is for reading. */
</style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'growth'])

{{-- The whole page is "on this date": today by default, any date on request,
     because planning next week's spray means reading next week's stage. --}}
<form method="GET" action="{{ route('sm.growth') }}" class="gr-date">
    <input type="hidden" name="id" value="{{ $schedule->id }}">
    <span class="gr-date-lbl">Reading the crop on</span>
    <label class="gr-date-tag" title="Pick another date to read the crop on">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>{{ $on->isToday() ? 'Today — ' . $on->format('M j, Y') : $on->format('D, M j, Y') }}</span>
        <input type="date" name="on" value="{{ $on->toDateString() }}" aria-label="Reading the crop on"
            onchange="this.form.submit()"
            onclick="try { this.showPicker && this.showPicker(); } catch (_) {}">
    </label>
    @if (! $on->isToday())
        <a class="btn btn-white btn-sm" href="{{ route('sm.growth', ['id' => $schedule->id]) }}">Back to today</a>
    @endif
    @if (count($rows))
        <button type="button" id="grFoldAll" class="btn btn-white btn-sm gr-foldall">Collapse all</button>
    @endif
</form>

<div id="grCards">
@forelse ($rows as $r)
    @include('sm.partials.growth-card', ['r' => $r, 'schedule' => $schedule])
@empty
    <div class="card card-body text-center text-gray-500 py-10">
        <p class="font-bold text-gray-800 mb-1">No lots yet</p>
        <p class="text-sm">Add a lot, say what is growing on it, and this page will read the crop for you.</p>
        <a class="btn btn-primary mt-4 inline-flex" href="{{ route('sm.lots', ['id' => $schedule->id]) }}">Open Lots</a>
    </div>
@endforelse
</div>

@if (count($rows))
    {{-- Said properly, and where it cannot be missed: a stage read off a
         calendar is a guess about a plant, and a season that ran through a
         typhoon is not the season this table describes. --}}
    <div class="gr-note">
        <span class="gr-note-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/></svg>
        </span>
        <p>These stages are counted from the calendar, not from the plant. A crop runs late or early with the weather it gets — a cold spell, a drought, flooding, a typhoon, pest damage or a hungry field all shift it, and so do the variety and how it was established. Walk the field and believe what you see there over what this page says.</p>
    </div>
@endif

<script>
    /* The lots fold like the board's days do, and the set of folded lots is
       remembered per schedule — this module re-fetches on every open (the
       shell marks it fresh), so the memory has to live in the browser. */
    (() => {
        const KEY = 'growthFolded:' + @json($schedule->id);
        const cards = document.getElementById('grCards');
        const btn = document.getElementById('grFoldAll');
        if (!cards) return;
        const all = () => Array.from(cards.querySelectorAll('.gr-card[data-lot]'));
        const folded = new Set((() => {
            try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (_) { return []; }
        })());
        const save = () => { try { localStorage.setItem(KEY, JSON.stringify([...folded])); } catch (_) { /* private mode */ } };
        const sayBtn = () => {
            if (!btn) return;
            btn.textContent = all().some((c) => !c.classList.contains('is-folded')) ? 'Collapse all' : 'Expand all';
        };

        // Apply the remembered folds instantly — restoring is not a change,
        // and a wave of closing animations on load reads as one.
        cards.classList.add('no-fold-anim');
        all().forEach((c) => c.classList.toggle('is-folded', folded.has(c.getAttribute('data-lot'))));
        void cards.offsetWidth;
        requestAnimationFrame(() => cards.classList.remove('no-fold-anim'));
        sayBtn();

        cards.addEventListener('click', (e) => {
            const top = e.target.closest('.gr-top');
            const card = top && top.closest('.gr-card[data-lot]');
            if (!card) return;
            const id = card.getAttribute('data-lot');
            if (card.classList.toggle('is-folded')) folded.add(id);
            else folded.delete(id);
            save();
            sayBtn();
        });

        btn?.addEventListener('click', () => {
            // If anything is open, the button means "close everything";
            // only a fully folded page flips it to mean the opposite.
            const fold = all().some((c) => !c.classList.contains('is-folded'));
            all().forEach((c) => {
                c.classList.toggle('is-folded', fold);
                const id = c.getAttribute('data-lot');
                if (fold) folded.add(id);
                else folded.delete(id);
            });
            save();
            sayBtn();
        });
    })();
</script>
{{-- Loaded inside the activities shell (?partial=1) the board already
     carries the sheets and the script; a second copy gave the page two
     #grRealignSheet -- the visible one nobody's handler could close or run
     (the owner's "hangs on Reading the calendar", 2026-09-15). --}}
@unless (request()->boolean('partial'))
    @include('sm.partials.growth-realign', ['schedule' => $schedule])
@endunless
<script>
    /* Every lot's block, drawn by the shared renderer; and once she has
       spoken, that lot's card is re-read from the server and swapped in
       place -- the stage chip, the bar, the tips, the timeline and her
       note all read at the new day by the time the reading's sheet is
       closed, with no reload and no going back to the page. */
    (() => {
        const CARD_URL = @json(route('sm.growth.card', ['id' => $schedule->id])) + '&lot=';
        const mountIn = (root) => root.querySelectorAll('[data-grx-mount]').forEach((m) => {
            let realign = null;
            try { realign = JSON.parse(m.dataset.realign || 'null'); } catch (_) {}
            const lotId = Number(m.dataset.lotId);
            window.growthRealign.known[lotId] = realign;
            window.growthRealign.names[lotId] = m.dataset.lotName || '';
            m.innerHTML = window.growthRealign.block({ lotId, lotName: m.dataset.lotName || '', realign, calendar: m.dataset.calendar || '' });
        });
        mountIn(document);
        const refresh = async (lotId, realign) => {
            const old = document.querySelector(`.gr-card[data-lot="${Number(lotId)}"]`);
            // Her note shows at once, from what just landed; the rest of the
            // card follows when the server has re-read the lot.
            if (realign) {
                window.growthRealign.known[lotId] = realign;
                const m = old && old.querySelector('[data-grx-mount]');
                if (m) { m.dataset.realign = JSON.stringify(realign); mountIn(old); }
            }
            try {
                const res = await api(CARD_URL + Number(lotId), { method: 'GET' });
                if (!res.data || !res.data.html || !old) return;
                const tmp = document.createElement('div');
                tmp.innerHTML = res.data.html.trim();
                const fresh = tmp.firstElementChild;
                if (!fresh) return;
                fresh.classList.toggle('is-folded', old.classList.contains('is-folded'));
                old.replaceWith(fresh);
                mountIn(fresh);
                fresh.classList.add('is-refreshed');
                setTimeout(() => fresh.classList.remove('is-refreshed'), 1200);
            } catch (err) {
                // The reading is applied either way; the next open reads it.
                console.warn('growth card refresh', err);
            }
        };
        window.growthRealign.onApplied = (lotId, realign) => { refresh(lotId, realign); };
    })();
</script>
@endsection
